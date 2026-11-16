<?php

namespace Database\Seeders;

use App\Models\AddOn;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductComponent;
use Illuminate\Database\Seeder;

class MenuDemoSeeder extends Seeder
{
    public function run(): void
    {
        // These are sample Figma entries for LOCAL TESTING, not the confirmed shop menu.
        $meals = Category::firstOrCreate(
            ['name' => 'Meals'],
            ['description' => 'Chicken Pops rice meals']
        );

        foreach (['Sides', 'Drinks', 'Desserts'] as $categoryName) {
            Category::firstOrCreate(['name' => $categoryName]);
        }

        $menu = [
            ['Garlic Mayo Chick Pops', 'Golden chicken pops with garlic mayo sauce.'],
            ['Teriyaki Chick Pops', 'Chicken pops with a savory teriyaki glaze.'],
            ['Cheese Chick Pops', 'Chicken pops topped with a creamy cheese sauce.'],
            ['Buffalo Chick Pops', 'Chicken pops finished with a spicy buffalo sauce.'],
        ];

        $addOnDefinitions = [
            ['name' => 'Extra Rice', 'price' => 20],
            ['name' => 'Extra Gravy', 'price' => 10],
            ['name' => 'Extra Chicken', 'price' => 50],
        ];
        $addOnIds = [];

        foreach ($addOnDefinitions as $definition) {
            $addOn = AddOn::firstOrCreate(
                ['name' => $definition['name']],
                ['price' => $definition['price'], 'is_available' => true]
            );
            $addOnIds[] = $addOn->addon_id;
        }

        foreach ($menu as [$name, $description]) {
            $product = Product::firstOrCreate(
                ['name' => $name, 'category_id' => $meals->category_id],
                [
                    'description' => $description,
                    'base_price' => 85, // Figma sample price; verify with client.
                    'is_available' => true,
                ]
            );

            foreach (['Rice', 'Sauce'] as $component) {
                ProductComponent::firstOrCreate(
                    ['product_id' => $product->product_id, 'component_name' => $component],
                    ['is_removable' => true]
                );
            }

            $product->addOns()->syncWithoutDetaching($addOnIds);
        }
    }
}
