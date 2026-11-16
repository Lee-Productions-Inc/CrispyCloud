<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Product;

class CustomizationController extends Controller
{
    public function show(int $product)
    {
        $item = Product::with([
            'addOns' => function ($query) {
                $query->where('is_available', true);
            },
            'components' => function ($query) {
                $query->where('is_removable', true);
            }
        ])
        ->where('is_available', true)
        ->findOrFail($product);

        return view('customer.customize.show', [
            'product' => $item
        ]);
    }
}