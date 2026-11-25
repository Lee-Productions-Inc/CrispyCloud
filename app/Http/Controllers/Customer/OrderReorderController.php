<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Support\ChicksPopsSauces;
use App\Support\MenuSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderReorderController extends Controller
{
    private const CART_KEY = 'crispycloud_cart';
    private const SELECTION_KEY = 'crispycloud_checkout_lines';

    public function store(Request $request, int $order): RedirectResponse
    {
        // Reorder belongs only to the authenticated owner of a completed delivery order.
        $previousOrder = Order::query()
            ->where('customer_id', Auth::guard('web')->id())
            ->where('order_type', 'Delivery')
            ->where('order_status', 'Delivered')
            ->with([
                'items.product.category',
                'items.customizations.addOn',
                'items.removals.component',
            ])
            ->findOrFail($order);

        if ($previousOrder->items->isEmpty()) {
            return back()->with('reorder_error', 'This order has no items to reorder.');
        }

        $cart = $request->session()->get(self::CART_KEY, []);
        if (count($cart) + $previousOrder->items->count() > 50) {
            return back()->with('reorder_error', 'Your cart has too many items. Remove a few before reordering.');
        }

        $newLines = [];
        foreach ($previousOrder->items as $orderItem) {
            $product = $orderItem->product;
            if (!$product || !$product->is_available) {
                return back()->with('reorder_error', 'An item is no longer available. Please choose it from the menu.');
            }

            $quantity = (int) $orderItem->quantity;
            if ($quantity < 1 || $quantity > 20) {
                return back()->with('reorder_error', 'An old item quantity is no longer supported. Please order from the menu.');
            }

            $isBeverage = MenuSections::isBeverageCategory($product->category?->name);
            $availableAddOns = $product->addOns()->where('is_available', true)->get()->keyBy('addon_id');
            $addons = [];
            $seenAddonIds = [];
            foreach ($orderItem->customizations as $customization) {
                $addon = $availableAddOns->get((int) $customization->addon_id);
                $addonQty = (int) $customization->addon_quantity;
                if (!$addon || isset($seenAddonIds[(int) $customization->addon_id]) ||
                    $addonQty < 1 || $addonQty > 10 ||
                    MenuSections::isDrinkAddOnName($addon->name) || $isBeverage) {
                    return back()->with('reorder_error', 'A previous food extra is unavailable. Please customize a new order.');
                }
                $seenAddonIds[(int) $customization->addon_id] = true;
                $addons[] = [
                    'addon_id' => (int) $addon->addon_id,
                    'name' => $addon->name,
                    'price_cents' => self::toCents($addon->price),
                    'quantity' => $addonQty,
                ];
            }

            $availableComponents = $product->components()->where('is_removable', true)->get()->keyBy('component_id');
            $removals = [];
            $seenComponentIds = [];
            foreach ($orderItem->removals as $removed) {
                $component = $availableComponents->get((int) $removed->component_id);
                if (!$component || $isBeverage || isset($seenComponentIds[(int) $removed->component_id])) {
                    return back()->with('reorder_error', 'A previously removed ingredient is no longer available. Please customize a new order.');
                }
                $seenComponentIds[(int) $removed->component_id] = true;
                $removals[] = [
                    'component_id' => (int) $component->component_id,
                    'name' => $component->component_name,
                ];
            }

            // Included sauces are saved in notes, whereas paid extras are stored as rows.
            $notes = trim((string) ($orderItem->item_notes ?? ''));
            $sauce = null;
            if (preg_match('/^Included sauce\(s\): ([^\r\n]*)(?:\r?\n|$)/u', $notes, $match)) {
                $sauce = trim($match[1]);
                $notes = trim(substr($notes, strlen($match[0])));
            }
            $removedSauce = collect($removals)->contains(
                fn (array $removed) => str_contains(strtolower($removed['name']), 'sauce')
            );
            if ($isBeverage && ($addons || $removals || $sauce)) {
                return back()->with('reorder_error', 'This beverage configuration is no longer supported.');
            }
            try {
                $sauce = $isBeverage ? null : ChicksPopsSauces::validateStored(
                    $product->name, $sauce, $removedSauce
                );
            } catch (ValidationException $e) {
                return back()->with('reorder_error', 'A previous sauce selection needs updating. Please customize the product again.');
            }
            if (mb_strlen($notes) > 500) {
                return back()->with('reorder_error', 'An old item note is too long to reuse.');
            }

            $basePrice = self::toCents($product->base_price);
            $extraPrice = array_sum(array_map(
                fn (array $addon) => $addon['price_cents'] * $addon['quantity'], $addons
            ));
            $newLines[(string) Str::uuid()] = [
                'product_id' => (int) $product->product_id,
                'name' => $product->name,
                'quantity' => $quantity,
                'base_price_cents' => $basePrice,
                'unit_price_cents' => $basePrice + $extraPrice,
                'add_ons' => $addons,
                'removals' => $removals,
                'sauce_choice' => $sauce,
                'item_notes' => $notes,
            ];
        }

        // Commit to the session only after every previous item has passed validation.
        $request->session()->put(self::CART_KEY, array_merge($cart, $newLines));
        $request->session()->forget(self::SELECTION_KEY);

        return redirect()->route('customer.cart.index')
            ->with('success', 'Order added to your cart. Review current prices and select items to checkout.');
    }

    private static function toCents(string|float|int $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
