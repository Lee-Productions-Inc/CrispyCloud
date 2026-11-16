<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Ingredient;
use App\Models\Product;
use App\Models\ProductComponent;
use App\Support\MenuSections;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RecipeManagementController extends Controller
{
    private const TYPES = [
        'product' => ['table' => 'ingredient_recipes', 'entity_table' => 'products', 'pk' => 'product_id', 'name' => 'Product'],
        'addon' => ['table' => 'addon_ingredient_recipes', 'entity_table' => 'add_ons', 'pk' => 'addon_id', 'name' => 'Add-on'],
        'component' => ['table' => 'component_ingredient_recipes', 'entity_table' => 'product_components', 'pk' => 'component_id', 'name' => 'Removable component'],
    ];

    public function index(Request $request): View
    {
        $data = $request->validate([
            'type' => ['nullable', Rule::in(array_keys(self::TYPES))],
            'section' => ['nullable', Rule::in(['all', 'food', 'beverages'])],
            'id' => ['nullable', 'integer', 'min:1'],
            'product' => ['nullable', 'integer', 'min:1'], // Existing Step 19 links
        ]);

        $type = $data['type'] ?? 'product';
        $section = $type === 'product' ? ($data['section'] ?? 'all') : 'all';

        $choices = match ($type) {
            'product' => Product::with('category')->orderBy('name')->get(),
            'addon' => AddOn::orderBy('name')->get(),
            'component' => ProductComponent::with('product')->orderBy('product_id')->orderBy('component_name')->get(),
        };

        if ($type === 'product' && $section !== 'all') {
            $choices = $choices->filter(function ($product) use ($section) {
                $isBeverage = MenuSections::isBeverageCategory($product->category?->name);
                return $section === 'beverages' ? $isBeverage : !$isBeverage;
            })->values();
        }

        $id = $data['id'] ?? ($type === 'product' ? ($data['product'] ?? null) : null);
        $target = $id ? $choices->firstWhere(self::TYPES[$type]['pk'], (int) $id) : $choices->first();
        if ($id !== null && !$target) {
            abort(404);
        }

        $ingredients = Ingredient::orderBy('ingredient_name')->get();
        $recipes = $target ? DB::table(self::TYPES[$type]['table'])
            ->where(self::TYPES[$type]['pk'], $target->getKey())
            ->pluck('quantity_required', 'ingredient_id')->all() : [];

        $configuredCount = $choices->filter(fn ($choice) => (bool) $choice->inventory_recipe_configured)->count();
        $choiceCount = $choices->count();

        return view('admin.inventory.recipes', compact(
            'type', 'section', 'choices', 'target', 'ingredients', 'recipes', 'configuredCount', 'choiceCount'
        ));
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        Product::findOrFail($product);
        return $this->saveRecipe($request, 'product', $product);
    }

    public function updateAddon(Request $request, int $addon): RedirectResponse
    {
        AddOn::findOrFail($addon);
        return $this->saveRecipe($request, 'addon', $addon);
    }

    public function updateComponent(Request $request, int $component): RedirectResponse
    {
        ProductComponent::findOrFail($component);
        return $this->saveRecipe($request, 'component', $component);
    }

    private function saveRecipe(Request $request, string $type, int $entityId): RedirectResponse
    {
        $data = $request->validate([
            'quantities' => ['sometimes', 'array', 'max:1000'],
            // Ingredient.current_stock has 2 decimals, so recipes must use the same precision.
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
            throw ValidationException::withMessages(['no_stock_effect' => 'Choose either ingredient quantities or No stock effect, not both.']);
        }
        if (!$noEffect && !$entries) {
            throw ValidationException::withMessages(['quantities' => 'Enter at least one ingredient amount or explicitly check No stock effect.']);
        }

        $config = self::TYPES[$type];
        DB::transaction(function () use ($config, $entityId, $entries): void {
            DB::table($config['table'])->where($config['pk'], $entityId)->delete();
            foreach ($entries as $ingredientId => $amount) {
                DB::table($config['table'])->insert([
                    $config['pk'] => $entityId,
                    'ingredient_id' => $ingredientId,
                    'quantity_required' => $amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            DB::table($config['entity_table'])->where($config['pk'], $entityId)
                ->update(['inventory_recipe_configured' => true]);
        });

        $params = ['type' => $type, 'id' => $entityId];
        if ($type === 'product') {
            $product = Product::with('category')->findOrFail($entityId);
            $params['section'] = MenuSections::isBeverageCategory($product->category?->name)
                ? 'beverages' : 'food';
        }

        return redirect()->route('admin.inventory.recipes.index', $params)
            ->with('success', $config['name'] . ' inventory recipe saved. Future order fulfillments will use this mapping.');
    }
}
