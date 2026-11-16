<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use App\Models\AddOn;
use App\Models\ProductComponent;

class CustomizationDemoSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create sample category
        $category = Category::firstOrCreate(
            ['category_name' => 'Demo Meals'],
            ['description' => 'Sample meals for testing']
        );

        // 2. Create sample product
        $product = Product::firstOrCreate(
            [
                'name' => 'Demo Chicken Meal',
                'category_id' => $category->category_id,
            ],
            [
                'description' => 'Chicken meal for customization testing',
                'base_price' => 150.00,
                'is_available' => true,
            ]
        );

        // 3. Create sample add-ons
        $addons = [
            ['addon_name' => 'Extra Rice', 'price' => 20.00],
            ['addon_name' => 'Extra Gravy', 'price' => 10.00],
            ['addon_name' => 'Extra Chicken', 'price' => 50.00],
        ];

        foreach ($addons as $addon) {
            AddOn::firstOrCreate(
                ['addon_name' => $addon['addon_name']],
                [
                    'price' => $addon['price'],
                    'is_available' => true,
                ]
            );
        }

        // 4. Create removable components
        $components = [
            'Rice',
            'Gravy',
            'Sauce',
        ];

        foreach ($components as $component) {
            ProductComponent::firstOrCreate(
                [
                    'product_id' => $product->product_id,
                    'component_name' => $component,
                ],
                ['is_removable' => true]
            );
        }
    }
}