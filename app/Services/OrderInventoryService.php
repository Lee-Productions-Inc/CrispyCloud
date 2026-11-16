<?php

namespace App\Services;

use App\Models\Ingredient;
use App\Models\InventoryLog;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Called INSIDE the same DB transaction as the transition to Delivered.
 * Inventory is consumed only once per order; no writes occur during checkout.
 * Ingredients use 2-decimal stock balances; recipes must yield exact 0.01 units.
 */
class OrderInventoryService
{
    public function consume(Order $order): void
    {
        if (DB::table('order_stock_deductions')->where('order_id', $order->order_id)->exists()) {
            throw ValidationException::withMessages([
                'order_status' => 'Stock for this order was already deducted. No second deduction was made.',
            ]);
        }

        $items = $order->items()->with([
            'product.ingredientRecipes',
            'customizations.addOn',
            'removals.component',
        ])->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['order_status' => 'This order has no items; stock cannot be deducted.']);
        }

        $addonIds = $items->flatMap(fn ($item) => $item->customizations->pluck('addon_id'))->unique()->values()->all();
        $componentIds = $items->flatMap(fn ($item) => $item->removals->pluck('component_id'))->unique()->values()->all();
        $addonRecipes = DB::table('addon_ingredient_recipes')
            ->whereIn('addon_id', $addonIds)->get()->groupBy('addon_id');
        $componentRecipes = DB::table('component_ingredient_recipes')
            ->whereIn('component_id', $componentIds)->get()->groupBy('component_id');

        // Required amounts are accumulated in thousandths to avoid float subtraction.
        $millis = [];
        foreach ($items as $item) {
            $lineMillis = [];
            $product = $item->product;
            $quantity = (int) $item->quantity;

            if (!$product || !$product->inventory_recipe_configured) {
                $name = $product?->name ?? ('Product #' . $item->product_id);
                $this->notConfigured('product ' . $name);
            }
            if ($quantity < 1) {
                throw ValidationException::withMessages(['order_status' => 'Order contains an invalid product quantity.']);
            }

            foreach ($product->ingredientRecipes as $recipe) {
                $this->change($lineMillis, $recipe->ingredient_id, $recipe->quantity_required, $quantity);
            }

            foreach ($item->customizations as $customization) {
                $addon = $customization->addOn;
                if (!$addon || !$addon->inventory_recipe_configured) {
                    $this->notConfigured('add-on ' . ($addon?->name ?? ('#' . $customization->addon_id)));
                }
                $addonQuantity = (int) $customization->addon_quantity;
                if ($addonQuantity < 1) {
                    throw ValidationException::withMessages(['order_status' => 'Invalid add-on quantity on an order item.']);
                }
                foreach ($addonRecipes->get($addon->addon_id, collect()) as $recipe) {
                    $this->change($lineMillis, $recipe->ingredient_id, $recipe->quantity_required, $quantity * $addonQuantity);
                }
            }

            foreach ($item->removals as $removal) {
                $component = $removal->component;
                if (!$component || (int) $component->product_id !== (int) $product->product_id) {
                    throw ValidationException::withMessages(['order_status' => 'An order item contains an invalid removed component.']);
                }
                if (!$component->inventory_recipe_configured) {
                    $this->notConfigured('removable component ' . $component->component_name . ' (' . $product->name . ')');
                }
                foreach ($componentRecipes->get($component->component_id, collect()) as $recipe) {
                    $this->change($lineMillis, $recipe->ingredient_id, $recipe->quantity_required, -$quantity);
                }
            }

            // A removal must never deduct a negative amount for one menu item.
            // Validate before combining multiple products in the same order.
            foreach ($lineMillis as $ingredientId => $amount) {
                if ($amount < 0) {
                    throw ValidationException::withMessages([
                        'order_status' => 'A removal on ' . $product->name . ' uses more stock than its base recipe. Review removable-component recipes.',
                    ]);
                }
                $millis[$ingredientId] = ($millis[$ingredientId] ?? 0) + $amount;
            }
        }

        ksort($millis);
        foreach ($millis as $ingredientId => $required) {
            if ($required < 0) {
                throw ValidationException::withMessages([
                    'order_status' => 'A removed component subtracts more ingredients than the base recipe provides. Review the recipe mappings.',
                ]);
            }
        }

        // Lock and validate ALL ingredients before writing ANY change.
        $ingredients = Ingredient::query()->whereIn('ingredient_id', array_keys($millis))
            ->orderBy('ingredient_id')->lockForUpdate()->get()->keyBy('ingredient_id');
        $balances = [];
        foreach ($millis as $ingredientId => $required) {
            if ($required === 0) {
                continue;
            }
            $ingredient = $ingredients->get($ingredientId);
            if (!$ingredient) {
                throw ValidationException::withMessages(['order_status' => 'A recipe ingredient is missing from inventory.']);
            }
            // Inventory stock column is decimal(..., 2) in the existing project.
            if ($required % 10 !== 0) {
                throw ValidationException::withMessages([
                    'order_status' => 'Recipe for ' . $ingredient->ingredient_name . ' uses a quantity smaller than 0.01 ' . $ingredient->unit_of_measure . '. Adjust recipes to 2 decimals.',
                ]);
            }
            $current = $this->toHundredths($ingredient->current_stock);
            $needed = intdiv($required, 10);
            if ($current < $needed) {
                throw ValidationException::withMessages([
                    'order_status' => 'Insufficient ' . $ingredient->ingredient_name . ' stock. Need ' . $this->formatted($needed) . ' ' . $ingredient->unit_of_measure . ', available ' . $this->formatted($current) . '. Restock before marking Delivered.',
                ]);
            }
            $balances[$ingredientId] = ['after' => $current - $needed, 'deduct' => $needed];
        }

        $adminId = Auth::guard('admin')->id();
        if (!$adminId) {
            throw ValidationException::withMessages(['order_status' => 'Administrator authentication is required.']);
        }

        foreach ($balances as $ingredientId => $entry) {
            $ingredients[$ingredientId]->update(['current_stock' => $this->formatted($entry['after'])]);
            InventoryLog::create([
                'ingredient_id' => $ingredientId,
                'updated_by_user_id' => $adminId,
                'log_type' => 'Order Fulfilled',
                'quantity_changed' => number_format(-$entry['deduct'] / 100, 3, '.', ''),
                'log_date' => now(),
                'reason' => 'Automatic stock deduction for delivered order #CP-' . $order->order_id,
            ]);
        }

        // A receipt is saved even when all explicitly configured recipes have zero stock effect.
        DB::table('order_stock_deductions')->insert([
            'order_id' => $order->order_id,
            'admin_user_id' => $adminId,
            'deducted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function change(array &$millis, int $id, string|float|int $perItem, int $count): void
    {
        $millis[$id] = ($millis[$id] ?? 0) + (int) round((float) $perItem * 1000) * $count;
    }

    private function toHundredths(string|float|int $quantity): int
    {
        return (int) round((float) $quantity * 100);
    }

    private function formatted(int $hundredths): string
    {
        return number_format($hundredths / 100, 2, '.', '');
    }

    private function notConfigured(string $what): never
    {
        throw ValidationException::withMessages([
            'order_status' => 'Inventory recipe not configured for ' . $what . '. Open Admin → Inventory → Recipes to configure it or explicitly mark No stock effect.',
        ]);
    }
}
