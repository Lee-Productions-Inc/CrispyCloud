<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemCustomization;
use App\Models\OrderItemRemoval;
use App\Models\Payment;
use App\Models\Product;
use App\Support\MenuSections;
use App\Services\OrderInventoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Admin-only walk-in sales. Shares accounting, products and inventory with the
 * online order system but never creates a fake customer or delivery record.
 */
class WalkInPosController extends Controller
{
    public function __construct(private readonly OrderInventoryService $inventory)
    {
    }

    public function index(): View
    {
        $products = Product::query()
            ->with([
                'category',
                'addOns' => fn ($q) => $q->where('is_available', true),
                'components' => fn ($q) => $q->where('is_removable', true),
            ])
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        $catalog = $products->map(fn ($p) => [
            'id' => (int) $p->product_id,
            'name' => $p->name,
            'category' => $p->category?->name ?? 'Menu',
            'kind' => MenuSections::isBeverageCategory($p->category?->name ?? '') ? 'beverage' : 'food',
            'image_url' => is_file(public_path('images/products/' . Str::slug($p->name) . '.png'))
                ? asset('images/products/' . Str::slug($p->name) . '.png') : null,
            'price_cents' => self::toCents($p->base_price),
            'addons' => (MenuSections::isBeverageCategory($p->category?->name ?? '') ? collect() : $p->addOns->reject(fn ($a) => MenuSections::isDrinkAddOnName($a->name)))->map(fn ($a) => [
                'id' => (int) $a->addon_id,
                'name' => $a->name,
                'price_cents' => self::toCents($a->price),
            ])->values()->all(),
            'components' => (MenuSections::isBeverageCategory($p->category?->name ?? '') ? collect() : $p->components)->map(fn ($c) => [
                'id' => (int) $c->component_id,
                'name' => $c->component_name,
            ])->values()->all(),
        ])->values()->all();

        return view('admin.pos.index', [
            'catalog' => $catalog,
            'token' => (string) \Illuminate\Support\Str::uuid(),
            'recent' => Order::query()->where('order_type', 'Walk-in')
                ->with(['payment', 'items'])
                ->latest('order_id')->limit(5)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pos_token' => ['required', 'uuid'],
            'items_json' => ['required', 'string', 'max:25000'],
            'payment_method' => ['required', Rule::in(['Cash', 'GCash'])],
            'cash_received' => ['nullable', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'gcash_reference' => ['nullable', 'string', 'max:100'],
            'payment_confirmed' => ['required', 'accepted'],
        ], [
            'payment_confirmed.accepted' => 'Confirm the money has actually been received or the GCash transfer verified.',
        ]);

        // Allow idempotent form retries, including if a browser resends a completed POST.
        $existing = DB::table('walk_in_sales')->where('pos_token', $data['pos_token'])->first();
        if ($existing) {
            return redirect()->route('admin.pos.show', $existing->order_id);
        }

        try {
            $rawLines = json_decode($data['items_json'], true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw ValidationException::withMessages(['items_json' => 'Invalid cart details. Rebuild the walk-in basket.']);
        }
        if (!is_array($rawLines) || !array_is_list($rawLines) || count($rawLines) < 1 || count($rawLines) > 25) {
            throw ValidationException::withMessages(['items_json' => 'Select between 1 and 25 menu items.']);
        }

        $validatedLines = [];
        $subtotal = 0;
        foreach ($rawLines as $index => $raw) {
            if (!is_array($raw)) {
                throw ValidationException::withMessages(['items_json' => 'Invalid item at position ' . ($index + 1) . '.']);
            }
            $id = filter_var($raw['product_id'] ?? null, FILTER_VALIDATE_INT);
            $qty = filter_var($raw['quantity'] ?? null, FILTER_VALIDATE_INT);
            if (!$id || !$qty || $qty < 1 || $qty > 20) {
                throw ValidationException::withMessages(['items_json' => 'Invalid product or quantity on line ' . ($index + 1) . '.']);
            }

            $product = Product::with([
                'category',
                'addOns' => fn ($q) => $q->where('is_available', true),
                'components' => fn ($q) => $q->where('is_removable', true),
            ])->where('is_available', true)->find($id);
            if (!$product) {
                throw ValidationException::withMessages(['items_json' => 'A product is unavailable. Refresh the POS before trying again.']);
            }

            // Drinks are standalone products, NEVER add-ons inside a meal.
            // Reject a stale or altered POS payload attempting to customize a beverage.
            if (MenuSections::isBeverageCategory($product->category?->name ?? '') && (
                !empty($raw['add_ons']) || !empty($raw['remove_components']) ||
                !empty($raw['sauce_choice'])
            )) {
                throw ValidationException::withMessages([
                    'items_json' => 'Beverages are separate products and cannot have food extras or ingredient removals.',
                ]);
            }

            $addonIds = $this->validateIds($raw['add_ons'] ?? [], 'add-on');
            $removalIds = $this->validateIds($raw['remove_components'] ?? [], 'removable component');
            $addons = $product->addOns->whereIn('addon_id', $addonIds)
                ->reject(fn ($addon) => MenuSections::isDrinkAddOnName($addon->name));
            $removals = $product->components->whereIn('component_id', $removalIds);
            if ($addons->count() !== count($addonIds) || $removals->count() !== count($removalIds)) {
                throw ValidationException::withMessages(['items_json' => 'An add-on or removal is not available for ' . $product->name . '.']);
            }

            $sauce = $raw['sauce_choice'] ?? null;
            if ($sauce === '') {
                $sauce = null;
            }
            if ($sauce !== null && !in_array($sauce, ['Honey Mustard', 'Smoky BBQ', 'Spicy Garlic Mayo'], true)) {
                throw ValidationException::withMessages(['items_json' => 'Invalid sauce selection.']);
            }
            $hasSauce = $product->components->contains(fn ($c) => str_contains(mb_strtolower($c->component_name), 'sauce'));
            $sauceRemoved = $removals->contains(fn ($c) => str_contains(mb_strtolower($c->component_name), 'sauce'));
            if ($sauce && (!$hasSauce || $sauceRemoved)) {
                throw ValidationException::withMessages(['items_json' => 'Sauce selection conflicts with the product customization.']);
            }
            if ($hasSauce && !$sauceRemoved && !$sauce) {
                $sauce = 'Honey Mustard';
            }

            $notes = trim((string) ($raw['item_notes'] ?? ''));
            if (mb_strlen($notes) > 500) {
                throw ValidationException::withMessages(['items_json' => 'Special instructions must be 500 characters or fewer.']);
            }
            $base = self::toCents($product->base_price);
            $unit = $base + $addons->sum(fn ($a) => self::toCents($a->price));
            $subtotal += $unit * $qty;
            if ($subtotal > 99999999) {
                throw ValidationException::withMessages(['items_json' => 'Sale total exceeds the supported limit.']);
            }
            $validatedLines[] = compact('product', 'qty', 'base', 'unit', 'addons', 'removals', 'sauce', 'notes');
        }

        if ($data['payment_method'] === 'Cash') {
            if (!array_key_exists('cash_received', $data) || $data['cash_received'] === null) {
                throw ValidationException::withMessages(['cash_received' => 'Enter the actual cash received.']);
            }
            $received = self::toCents($data['cash_received']);
            if ($received < $subtotal) {
                throw ValidationException::withMessages(['cash_received' => 'Cash received must cover the total.']);
            }
            $change = $received - $subtotal;
        } else {
            if (mb_strlen(trim((string) ($data['gcash_reference'] ?? ''))) < 4) {
                throw ValidationException::withMessages(['gcash_reference' => 'Enter the verified GCash reference (at least 4 characters).']);
            }
            $received = null;
            $change = null;
        }

        $adminId = Auth::guard('admin')->id();
        $savedOrder = DB::transaction(function () use ($validatedLines, $subtotal, $data, $adminId, $received, $change) {
            // Inside transaction, a second same-token POST cannot create a second receipt.
            $duplicate = DB::table('walk_in_sales')->where('pos_token', $data['pos_token'])->first();
            if ($duplicate) {
                return (int) $duplicate->order_id;
            }

            $order = Order::create([
                'customer_id' => null,
                'order_date' => now(),
                'order_status' => 'Preparing',
                'order_type' => 'Walk-in',
                'total_amount' => self::decimal($subtotal),
            ]);

            foreach ($validatedLines as $line) {
                $instructions = $line['notes'];
                if ($line['sauce']) {
                    $instructions = 'Sauce: ' . $line['sauce'] . ($instructions !== '' ? "\n" . $instructions : '');
                }
                $item = OrderItem::create([
                    'order_id' => $order->order_id,
                    'product_id' => $line['product']->product_id,
                    'quantity' => $line['qty'],
                    'unit_price' => self::decimal($line['base']),
                    'subtotal' => self::decimal($line['unit'] * $line['qty']),
                    'item_notes' => $instructions ?: null,
                ]);
                foreach ($line['addons'] as $addon) {
                    OrderItemCustomization::create([
                        'order_item_id' => $item->order_item_id,
                        'addon_id' => $addon->addon_id,
                        'addon_quantity' => 1,
                        'addon_price' => self::decimal(self::toCents($addon->price)),
                    ]);
                }
                foreach ($line['removals'] as $component) {
                    OrderItemRemoval::create([
                        'order_item_id' => $item->order_item_id,
                        'component_id' => $component->component_id,
                    ]);
                }
            }

            Payment::create([
                'order_id' => $order->order_id,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'Paid',
                'payment_date' => now(),
                'reference_number' => $data['payment_method'] === 'GCash'
                    ? trim($data['gcash_reference']) : null,
            ]);

            DB::table('walk_in_sales')->insert([
                'order_id' => $order->order_id,
                'admin_user_id' => $adminId,
                'pos_token' => $data['pos_token'],
                'cash_received' => $received === null ? null : self::decimal($received),
                'change_amount' => $change === null ? null : self::decimal($change),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return $order->order_id;
        }, 3);

        return redirect()->route('admin.pos.show', $savedOrder)
            ->with('success', 'Walk-in sale recorded and paid. Mark Fulfilled when the food is handed over.');
    }

    public function history(Request $request): View
    {
        $status = $request->query('status', 'all');
        if (!in_array($status, ['all', 'Preparing', 'Delivered'], true)) {
            $status = 'all';
        }
        $orders = Order::query()->where('order_type', 'Walk-in')
            ->with(['payment', 'items'])
            ->when($status !== 'all', fn ($q) => $q->where('order_status', $status))
            ->latest('order_id')->paginate(15)->withQueryString();
        return view('admin.pos.history', compact('orders', 'status'));
    }

    public function show(int $order): View
    {
        $record = Order::query()->where('order_type', 'Walk-in')
            ->with([
                'items.product', 'items.customizations.addOn', 'items.removals.component', 'payment',
            ])->findOrFail($order);
        $sale = DB::table('walk_in_sales as s')
            ->leftJoin('admin_users as a', 's.admin_user_id', '=', 'a.user_id')
            ->where('s.order_id', $order)
            ->select('s.*', 'a.full_name as cashier_name')->first();
        abort_unless($sale !== null, 404);
        return view('admin.pos.show', ['order' => $record, 'sale' => $sale]);
    }

    public function complete(int $order): RedirectResponse
    {
        DB::transaction(function () use ($order) {
            $record = Order::query()->where('order_type', 'Walk-in')
                ->lockForUpdate()->findOrFail($order);
            if ($record->order_status !== 'Preparing' || $record->payment?->payment_status !== 'Paid') {
                throw ValidationException::withMessages(['sale' => 'This walk-in sale cannot be fulfilled again.']);
            }
            $this->inventory->consume($record); // same Step 21 service; audit + exactly once
            $record->order_status = 'Delivered'; // existing fulfilled status for reporting
            $record->save();
        }, 3);

        return redirect()->route('admin.pos.show', $order)
            ->with('success', 'Sale fulfilled. Inventory was deducted and logged.');
    }

    private function validateIds(mixed $ids, string $type): array
    {
        if (!is_array($ids) || !array_is_list($ids) || count($ids) > 30) {
            throw ValidationException::withMessages(['items_json' => 'Invalid ' . $type . ' selection.']);
        }
        $result = [];
        foreach ($ids as $id) {
            $value = filter_var($id, FILTER_VALIDATE_INT);
            if (!$value || $value < 1 || in_array($value, $result, true)) {
                throw ValidationException::withMessages(['items_json' => 'Duplicate or invalid ' . $type . '.']);
            }
            $result[] = $value;
        }
        return $result;
    }

    private static function toCents(string|float|int $price): int
    {
        return (int) round(((float) $price) * 100);
    }

    private static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
