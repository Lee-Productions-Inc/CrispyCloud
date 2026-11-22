<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ingredient;
use App\Models\Product;
use App\Support\MenuSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecipeManagementController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        // Previously bookmarked extra/removal recipe pages now open product setup.
        if (in_array($request->query('type'), ['addon', 'component'], true)) {
            return redirect()->route('admin.inventory.recipes.index');
        }

        $data = $request->validate([
            'type' => ['nullable', Rule::in(['product'])],
            'section' => ['nullable', Rule::in(['all', 'food', 'beverages'])],
            'id' => ['nullable', 'integer', 'min:1'],
            'product' => ['nullable', 'integer', 'min:1'], // Older bookmarked product links
        ]);

        $type = 'product';
        $section = $data['section'] ?? 'all';
        $choices = Product::with('category')->orderBy('name')->get();
        if ($section !== 'all') {
            $choices = $choices->filter(function (Product $product) use ($section): bool {
                $beverage = MenuSections::isBeverageCategory($product->category?->name);
                return $section === 'beverages' ? $beverage : !$beverage;
            })->values();
        }

        $id = $data['id'] ?? ($data['product'] ?? null);
        $target = $id ? $choices->firstWhere('product_id', (int) $id) : $choices->first();
        if ($id !== null && !$target) {
            abort(404);
        }

        $ingredients = Ingredient::orderBy('ingredient_name')->get();
        $recipes = $target ? DB::table('ingredient_recipes')
            ->where('product_id', $target->product_id)
            ->pluck('quantity_required', 'ingredient_id')->all() : [];
        $configuredCount = $choices->filter(fn ($item) => (bool) $item->inventory_recipe_configured)->count();
        $choiceCount = $choices->count();

        return view('admin.inventory.recipes', compact(
            'type', 'section', 'choices', 'target', 'ingredients', 'recipes', 'configuredCount', 'choiceCount'
        ));
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $productModel = Product::with('category')->findOrFail($product);
        $data = $request->validate([
            'quantities' => ['sometimes', 'array', 'max:1000'],
            'quantities.*' => ['nullable', 'numeric', 'min:0', 'max:9999999.99', 'decimal:0,2'],
            'no_stock_effect' => ['sometimes', 'boolean'],
            'confirmed_recipe' => ['required', 'accepted'],
        ]);

        $entries = [];
        $quantities = $data['quantities'] ?? [];
        foreach ($quantities as $ingredientId => $quantity) {
            if (!ctype_digit((string) $ingredientId) || (int) $ingredientId < 1) {
                throw ValidationException::withMessages(['quantities' => 'Invalid ingredient ID.']);
            }
            if ($quantity !== null && (float) $quantity > 0) {
                $entries[(int) $ingredientId] = number_format((float) $quantity, 2, '.', '');
            }
        }
        if ($quantities && Ingredient::whereIn('ingredient_id', array_keys($quantities))->count() !== count($quantities)) {
            throw ValidationException::withMessages(['quantities' => 'A recipe ingredient no longer exists.']);
        }
        $noEffect = $request->boolean('no_stock_effect');
        if ($noEffect && $entries) {
            throw ValidationException::withMessages(['no_stock_effect' => 'Choose either ingredient quantities or No stock effect.']);
        }
        if (!$noEffect && !$entries) {
            throw ValidationException::withMessages(['quantities' => 'Enter an ingredient amount or select No stock effect.']);
        }

        DB::transaction(function () use ($product, $entries): void {
            DB::table('ingredient_recipes')->where('product_id', $product)->delete();
            foreach ($entries as $ingredientId => $amount) {
                DB::table('ingredient_recipes')->insert([
                    'product_id' => $product,
                    'ingredient_id' => $ingredientId,
                    'quantity_required' => $amount,
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            DB::table('products')->where('product_id', $product)
                ->update(['inventory_recipe_configured' => true]);
        });

        $section = MenuSections::isBeverageCategory($productModel->category?->name) ? 'beverages' : 'food';
        return redirect()->route('admin.inventory.recipes.index', ['section' => $section, 'id' => $product])
            ->with('success', 'Product recipe saved. Future fulfillments will use this mapping.');
    }
}
