<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Support\MenuSections;

class CustomizationController extends Controller
{
    public function show(int $product)
    {
        $item = Product::with([
            'category',
            'addOns' => fn ($query) => $query->where('is_available', true),
            'components' => fn ($query) => $query->where('is_removable', true),
        ])->where('is_available', true)->findOrFail($product);

        $isBeverage = MenuSections::isBeverageCategory($item->category?->name);
        if ($isBeverage) {
            // A drink is always its own product, never customized with meal extras.
            $item->setRelation('addOns', $item->addOns->take(0));
            $item->setRelation('components', $item->components->take(0));
        } else {
            $item->setRelation('addOns', $item->addOns->reject(
                fn ($addon) => MenuSections::isDrinkAddOnName($addon->name)
            )->values());
        }

        $beverages = $isBeverage ? collect() : Product::query()
            ->with('category')->where('is_available', true)->orderBy('name')->get()
            ->filter(fn ($option) => MenuSections::isBeverageCategory($option->category?->name))
            ->take(8)->values();

        return view('customer.customize.show', [
            'product' => $item,
            'beverages' => $beverages,
            'isBeverage' => $isBeverage,
        ]);
    }
}
