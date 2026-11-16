<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCustomization;
use App\Models\OrderItemRemoval;
use App\Models\Payment;
use App\Models\Product;
use App\Support\MenuSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    private const CART_KEY = 'crispycloud_cart';
    private const DELIVERY_FEE_CENTS = 3000; // Demo fee: ₱30. Confirm with CHICK'S POPS before real use.

    public function index(Request $request): View|RedirectResponse
    {
        $items = $request->session()->get(self::CART_KEY, []);

        if (!$items) {
            return redirect()->route('customer.cart.index')->with('error', 'Your basket is empty.');
        }

        $subtotalCents = array_reduce(
            $items,
            fn(int $sum, array $line): int => $sum + (int) $line['unit_price_cents'] * (int) $line['quantity'],
            0
        );

        $user = Auth::guard('web')->user();

$defaultAddress = $user->addresses()
    ->where('is_default', true)
    ->first();
        return view('customer.checkout.index', [
            'items' => $items,
            'subtotalCents' => $subtotalCents,
            'deliveryFeeCents' => self::DELIVERY_FEE_CENTS,
            'totalCents' => $subtotalCents + self::DELIVERY_FEE_CENTS,
            'user' => $user,
            'defaultAddress' => $defaultAddress,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_name' => ['required', 'string', 'max:255'],
            'recipient_phone' => ['required', 'string', 'max:30'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'payment_method' => ['required', Rule::in(['COD', 'GCash'])],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        $cart = $request->session()->get(self::CART_KEY, []);
        if (empty($cart)) {
            return redirect()->route('customer.cart.index')->with('error', 'Your basket is empty.');
        }

        $this->ensureSchemaIsReady();

        // Re-evaluate product, option availability, and price BEFORE making any database writes.
        $validatedLines = [];
        $subtotalCents = 0;

        foreach ($cart as $lineId => $line) {
            $product = Product::with('category')->where('is_available', true)->find($line['product_id'] ?? null);
            if (!$product) {
                throw ValidationException::withMessages(['cart' => 'A product in your cart is no longer available. Please review your basket.']);
            }

            $quantity = (int) ($line['quantity'] ?? 0);
            if ($quantity < 1 || $quantity > 20) {
                throw ValidationException::withMessages(['cart' => 'An item has an invalid quantity. Please review your basket.']);
            }

            $rawAddons = $line['add_ons'] ?? [];
            if (!is_array($rawAddons) || count($rawAddons) > 30) {
                throw ValidationException::withMessages(['cart' => 'Invalid food extras in your basket. Please customize the item again.']);
            }
            $addonQuantities = [];
            foreach ($rawAddons as $rawAddon) {
                if (!is_array($rawAddon)) {
                    throw ValidationException::withMessages(['cart' => 'Invalid food extras in your basket. Please customize the item again.']);
                }
                $addonId = filter_var($rawAddon['addon_id'] ?? null, FILTER_VALIDATE_INT);
                // Old basket entries without quantity represent one portion.
                $addonQuantity = filter_var($rawAddon['quantity'] ?? 1, FILTER_VALIDATE_INT);
                if (
                    $addonId === false || $addonId < 1 || $addonQuantity === false ||
                    $addonQuantity < 1 || $addonQuantity > 10 || isset($addonQuantities[$addonId])
                ) {
                    throw ValidationException::withMessages(['cart' => 'Invalid or duplicate food extra quantity. Please customize the item again.']);
                }
                $addonQuantities[$addonId] = $addonQuantity;
            }
            $addonIds = array_keys($addonQuantities);
            $addons = $product->addOns()->where('is_available', true)
                ->whereIn('add_ons.addon_id', $addonIds)->get();
            if ($addons->count() !== count($addonIds) || $addons->contains(
                fn($addon) => MenuSections::isDrinkAddOnName($addon->name)
            )) {
                throw ValidationException::withMessages(['cart' => 'A selected add-on is unavailable. Remove and customize that product again.']);
            }

            $rawRemovals = $line['removals'] ?? [];
            $componentIds = array_map('intval', array_column($rawRemovals, 'component_id'));
            if (count($componentIds) !== count(array_unique($componentIds))) {
                throw ValidationException::withMessages(['cart' => 'A cart item has duplicate removals. Please add it again.']);
            }
            $components = $product->components()->where('is_removable', true)
                ->whereIn('component_id', $componentIds)->get();
            if ($components->count() !== count($componentIds)) {
                throw ValidationException::withMessages(['cart' => 'A removed component is no longer removable. Please customize that product again.']);
            }

            $sauce = $line['sauce_choice'] ?? null;
            if ($sauce !== null && !in_array($sauce, ['Honey Mustard', 'Smoky BBQ', 'Spicy Garlic Mayo'], true)) {
                throw ValidationException::withMessages(['cart' => 'Invalid sauce choice. Please customize the product again.']);
            }
            $hasSauce = $product->components()->where('is_removable', true)
                ->where('component_name', 'like', '%sauce%')->exists();
            $sauceRemoved = $components->contains(
                fn($component) => str_contains(strtolower($component->component_name), 'sauce')
            );
            if (
                MenuSections::isBeverageCategory($product->category?->name) &&
                (count($addonIds) || count($componentIds) || $sauce)
            ) {
                throw ValidationException::withMessages([
                    'cart' => 'Drinks cannot have food extras or ingredient removals. Please re-add the drink.',
                ]);
            }
            if ($sauce && (!$hasSauce || $sauceRemoved)) {
                throw ValidationException::withMessages(['cart' => 'Sauce selection conflicts with the removal. Please customize again.']);
            }

            $basePriceCents = self::toCents($product->base_price);
            $addonTotalCents = $addons->sum(fn($addon) =>
            self::toCents($addon->price) * $addonQuantities[(int) $addon->addon_id]);
            $unitPriceCents = $basePriceCents + $addonTotalCents;

            // Never silently charge a higher price than the customer saw in the basket.
            if ($unitPriceCents !== (int) ($line['unit_price_cents'] ?? -1)) {
                throw ValidationException::withMessages(['cart' => 'A product price changed. Please remove it from your basket and customize it again.']);
            }

            $notes = trim((string) ($line['item_notes'] ?? ''));
            if (mb_strlen($notes) > 500) {
                throw ValidationException::withMessages(['cart' => 'An item note is too long. Please customize again.']);
            }

            $validatedLines[] = compact('product', 'quantity', 'addons', 'addonQuantities', 'components', 'sauce', 'notes', 'basePriceCents', 'unitPriceCents');
            $subtotalCents += $unitPriceCents * $quantity;
        }

        $order = DB::transaction(function () use ($validated, $validatedLines, $subtotalCents) {
            $order = Order::create([
                'customer_id' => Auth::guard('web')->id(),
                'order_date' => now(),
                'total_amount' => self::asDecimal($subtotalCents + self::DELIVERY_FEE_CENTS),
                'order_status' => 'Pending',
                'order_type' => 'Delivery',
            ]);

            foreach ($validatedLines as $line) {
                // Sauce is stored as a note until a dedicated sauce table/column is introduced.
                $instructions = $line['notes'];
                if ($line['sauce']) {
                    $instructions = 'Sauce: ' . $line['sauce'] . ($instructions !== '' ? "\n" . $instructions : '');
                }

                $orderItem = OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $line['product']->product_id,
                    'quantity' => $line['quantity'],
                    'unit_price' => self::asDecimal($line['basePriceCents']),
                    'subtotal' => self::asDecimal($line['unitPriceCents'] * $line['quantity']),
                    'item_notes' => $instructions !== '' ? $instructions : null,
                ]);

                foreach ($line['addons'] as $addon) {
                    OrderItemCustomization::create([
                        'order_item_id' => $orderItem->order_item_id,
                        'addon_id' => $addon->addon_id,
                        'addon_quantity' => $line['addonQuantities'][(int) $addon->addon_id],
                        'addon_price' => self::asDecimal(self::toCents($addon->price)),
                    ]);
                }
                foreach ($line['components'] as $component) {
                    OrderItemRemoval::create([
                        'order_item_id' => $orderItem->order_item_id,
                        'component_id' => $component->component_id,
                    ]);
                }
            }

            Payment::create([
                'order_id' => $order->order_id,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'Pending', // No real payment gateway or auto-confirmation.
                'payment_date' => null,
                'reference_number' => $validated['payment_method'] === 'GCash'
                    ? ($validated['reference_number'] ?? null) : null,
            ]);

            $delivery = new Delivery();
            $delivery->order_id = $order->order_id;
            // Keep delivery tracking in sync with the newly created Pending order.
            $delivery->tracking_status = 'Pending';
            $delivery->recipient_name = $validated['recipient_name'];
            $delivery->recipient_phone = $validated['recipient_phone'];
            $delivery->address_line = $validated['address_line'];
            $delivery->city = $validated['city'];
            $delivery->postal_code = $validated['postal_code'];
            $delivery->delivery_fee = self::asDecimal(self::DELIVERY_FEE_CENTS);
            $delivery->save();

            return $order;
        });

        // Only empty the cart AFTER the transaction has committed successfully.
        $request->session()->forget(self::CART_KEY);

        return redirect()->route('customer.orders.show', $order->order_id)
            ->with('success', 'Your order was placed successfully. Payment is pending.');
    }

    private function ensureSchemaIsReady(): void
    {
        $required = [
            'orders' => ['order_id', 'customer_id', 'order_date', 'total_amount', 'order_status', 'order_type'],
            'order_items' => ['order_item_id', 'order_id', 'product_id', 'quantity', 'unit_price', 'subtotal', 'item_notes'],
            'order_item_customizations' => ['order_item_id', 'addon_id', 'addon_quantity', 'addon_price'],
            'order_item_removals' => ['order_item_id', 'component_id'],
            'payments' => ['order_id', 'payment_method', 'payment_status', 'payment_date', 'reference_number'],
            'deliveries' => ['order_id', 'tracking_status', 'recipient_name', 'recipient_phone', 'address_line', 'city', 'postal_code', 'delivery_fee'],
        ];
        foreach ($required as $table => $columns) {
            if (!Schema::hasTable($table)) {
                throw ValidationException::withMessages(['checkout' => "Missing {$table} table. Stop and check migrations before proceeding."]);
            }
            foreach ($columns as $column) {
                if (!Schema::hasColumn($table, $column)) {
                    throw ValidationException::withMessages(['checkout' => "Missing {$table}.{$column} column. Stop and compare your migrations."]);
                }
            }
        }
    }

    private static function toCents(string|float|int $price): int
    {
        return (int) round(((float) $price) * 100);
    }

    private static function asDecimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
