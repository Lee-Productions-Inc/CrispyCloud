<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\MenuSections;
use App\Support\ChicksPopsSauces;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CartController extends Controller
{
    private const SESSION_KEY = 'crispycloud_cart';
    private const SELECTION_KEY = 'crispycloud_checkout_lines';

    public function index(Request $request): View
    {
        $items = $request->session()->get(self::SESSION_KEY, []);

        $subtotalCents = array_reduce(
            $items,
            fn (int $sum, array $item): int => $sum + $item['unit_price_cents'] * $item['quantity'],
            0
        );

        $selectedLineIds = $request->session()->get(self::SELECTION_KEY);
        if (!is_array($selectedLineIds)) {
            $selectedLineIds = array_keys($items);
        }
        $selectedLineIds = array_values(array_intersect($selectedLineIds, array_keys($items)));
        $selectedLineIds = old('selected_lines', $selectedLineIds);
        if (!is_array($selectedLineIds)) {
            $selectedLineIds = [];
        }

        return view('customer.cart.index', compact('items', 'subtotalCents', 'selectedLineIds'));
    }

    public function store(Request $request, int $product): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'between:1,20'],
            'add_ons' => ['sometimes', 'array', 'max:30'],
            'add_ons.*' => ['required', 'integer', 'between:0,10'],
            'remove_components' => ['sometimes', 'array', 'max:30'],
            'remove_components.*' => ['integer', 'distinct'],
            'sauce_choices' => ['sometimes', 'array', 'max:2'],
            'sauce_choices.*' => ['required', 'string', 'max:70'],
            'item_notes' => ['nullable', 'string', 'max:500'],
            'beverage_id' => ['nullable', 'integer', 'min:1'],
        ]);

        $item = Product::query()
            ->with('category')
            ->where('is_available', true)
            ->findOrFail($product);

        $isBeverage = MenuSections::isBeverageCategory($item->category?->name);
        if ($isBeverage && (!empty($validated['add_ons']) || !empty($validated['remove_components']) ||
            !empty($validated['sauce_choices']) || !empty($validated['beverage_id']))) {
            throw ValidationException::withMessages([
                'add_ons' => 'Beverages cannot have food extras, ingredient removals or a second drink.',
            ]);
        }

        $selectedDrink = null;
        if (!empty($validated['beverage_id'])) {
            $selectedDrink = Product::with('category')->where('is_available', true)
                ->find($validated['beverage_id']);
            if (!$selectedDrink || !MenuSections::isBeverageCategory($selectedDrink->category?->name)) {
                throw ValidationException::withMessages([
                    'beverage_id' => 'This beverage is unavailable. Please choose another drink.',
                ]);
            }
        }

        // The customization form submits add_ons[ADDON_ID] = QUANTITY PER MEAL.
        // 0 means not selected. Validate IDs instead of treating quantities as IDs.
        $addonQuantities = [];
        foreach (($validated['add_ons'] ?? []) as $rawAddonId => $quantity) {
            $addonId = filter_var($rawAddonId, FILTER_VALIDATE_INT);
            if ($addonId === false || $addonId < 1 || isset($addonQuantities[$addonId])) {
                throw ValidationException::withMessages([
                    'add_ons' => 'An invalid or duplicate food extra was selected.',
                ]);
            }
            if ((int) $quantity > 0) {
                $addonQuantities[$addonId] = (int) $quantity;
            }
        }

        $addonIds = array_keys($addonQuantities);
        $addons = $item->addOns()
            ->where('is_available', true)
            ->whereIn('add_ons.addon_id', $addonIds)
            ->get();

        $addons = $addons->reject(fn ($addon) => MenuSections::isDrinkAddOnName($addon->name));
        if ($addons->count() !== count($addonIds)) {
            throw ValidationException::withMessages([
                'add_ons' => 'One or more selected add-ons are not available for this product.',
            ]);
        }

        $componentIds = array_map('intval', $validated['remove_components'] ?? []);
        $components = $item->components()
            ->where('is_removable', true)
            ->whereIn('component_id', $componentIds)
            ->get();

        if ($components->count() !== count($componentIds)) {
            throw ValidationException::withMessages([
                'remove_components' => 'One or more components cannot be removed from this product.',
            ]);
        }

        $removedSauce = $components->contains(
            fn ($component) => str_contains(strtolower($component->component_name), 'sauce')
        );
        $sauceChoice = $isBeverage ? null : ChicksPopsSauces::validateChoices(
            $item->name, $validated['sauce_choices'] ?? [], $removedSauce
        );

        $selectedAddons = $addons->map(fn ($addon) => [
            'addon_id' => (int) $addon->addon_id,
            'name' => $addon->name,
            'price_cents' => self::toCents($addon->price),
            'quantity' => $addonQuantities[(int) $addon->addon_id],
        ])->values()->all();

        $selectedRemovals = $components->map(fn ($component) => [
            'component_id' => (int) $component->component_id,
            'name' => $component->component_name,
        ])->values()->all();

        $basePriceCents = self::toCents($item->base_price);
        $unitPriceCents = $basePriceCents + array_sum(array_map(
            fn (array $addon): int => $addon['price_cents'] * $addon['quantity'],
            $selectedAddons
        ));
        $lineId = (string) Str::uuid();
        $cart = $request->session()->get(self::SESSION_KEY, []);

        $cart[$lineId] = [
            'product_id' => (int) $item->product_id,
            'name' => $item->name,
            'quantity' => (int) $validated['quantity'],
            'base_price_cents' => $basePriceCents,
            'unit_price_cents' => $unitPriceCents,
            'add_ons' => $selectedAddons,
            'removals' => $selectedRemovals,
            'sauce_choice' => $sauceChoice,
            'item_notes' => trim($validated['item_notes'] ?? ''),
        ];

        // The optional beverage is another real product line: independent price, recipe and receipt.
        // Using one session write keeps food + beverage together if the request is valid.
        if ($selectedDrink) {
            $drinkPriceCents = self::toCents($selectedDrink->base_price);
            $cart[(string) Str::uuid()] = [
                'product_id' => (int) $selectedDrink->product_id,
                'name' => $selectedDrink->name,
                'quantity' => (int) $validated['quantity'],
                'base_price_cents' => $drinkPriceCents,
                'unit_price_cents' => $drinkPriceCents,
                'add_ons' => [],
                'removals' => [],
                'sauce_choice' => null,
                'item_notes' => '',
            ];
        }

        $request->session()->put(self::SESSION_KEY, $cart);
        // A new cart line should be included in the next checkout by default.
        $request->session()->forget(self::SELECTION_KEY);

        return redirect()->route('customer.cart.index')->with('success',
            $selectedDrink ? 'Meal and beverage added as separate items.' : 'Product added to your basket.');
    }


    /** Save the chosen cart lines before sending a guest or customer to checkout. */
    public function selectCheckout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'selected_lines' => ['required', 'array', 'min:1', 'max:50'],
            'selected_lines.*' => ['required', 'string', 'distinct'],
        ], [
            'selected_lines.required' => 'Select at least one item to checkout.',
            'selected_lines.min' => 'Select at least one item to checkout.',
        ]);

        $cart = $request->session()->get(self::SESSION_KEY, []);
        $selected = array_values($validated['selected_lines']);
        if (empty($cart) || count(array_diff($selected, array_keys($cart))) > 0) {
            throw ValidationException::withMessages([
                'selected_lines' => 'Your cart changed. Please review your items again.',
            ]);
        }

        $request->session()->put(self::SELECTION_KEY, $selected);

        return redirect()->route('customer.checkout.start');
    }

    public function update(Request $request, string $line): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'between:1,20'],
        ]);

        $cart = $request->session()->get(self::SESSION_KEY, []);
        abort_unless(array_key_exists($line, $cart), 404);

        $cart[$line]['quantity'] = (int) $validated['quantity'];
        $request->session()->put(self::SESSION_KEY, $cart);

        return redirect()->route('customer.cart.index')->with('success', 'Quantity updated.');
    }

    public function destroy(Request $request, string $line): RedirectResponse
    {
        $cart = $request->session()->get(self::SESSION_KEY, []);
        abort_unless(array_key_exists($line, $cart), 404);

        unset($cart[$line]);
        $request->session()->put(self::SESSION_KEY, $cart);
        $selected = $request->session()->get(self::SELECTION_KEY);
        if (is_array($selected)) {
            $request->session()->put(self::SELECTION_KEY,
                array_values(array_filter($selected, fn ($id) => isset($cart[$id]))));
        }

        return redirect()->route('customer.cart.index')->with('success', 'Item removed.');
    }

    private static function toCents(string|int|float $value): int
    {
        return (int) round(((float) $value) * 100);
    }
}
