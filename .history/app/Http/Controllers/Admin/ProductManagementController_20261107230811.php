<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProductManagementController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'integer', 'exists:categories,category_id'],
            'availability' => ['nullable', Rule::in(['all', 'available', 'hidden'])],
            'type' => ['nullable', Rule::in(['all', 'food', 'beverage'])],
        ]);

        $search = trim($filters['q'] ?? '');
        $categoryId = $filters['category'] ?? null;
        $availability = $filters['availability'] ?? 'all';
        $type = $filters['type'] ?? 'all';

        // Match the existing Step 23 food/beverage category convention.
        $categories = Category::orderBy('name')->get();
        $drinkCategoryIds = $categories->filter(fn ($category) =>
            (bool) preg_match('/(?:^|[^a-z])(?:drinks?|beverages?|refreshments?|juices?|milkshakes?|sodas?|coffees?|teas?|water)(?:$|[^a-z])/i', $category->name)
        )->pluck('category_id')->all();
        $drinkCount = Product::whereIn('category_id', $drinkCategoryIds)->count();
        $foodCount = Product::count() - $drinkCount;

        $products = Product::with('category')
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            }))
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->when($type === 'beverage', fn ($q) => $q->whereIn('category_id', $drinkCategoryIds))
            ->when($type === 'food', fn ($q) => $q->whereNotIn('category_id', $drinkCategoryIds))
            ->when($availability === 'available', fn ($q) => $q->where('is_available', true))
            ->when($availability === 'hidden', fn ($q) => $q->where('is_available', false))
            ->orderByDesc('product_id')
            ->paginate(12)
            ->withQueryString();

        return view('admin.products.index', compact(
            'products', 'categories', 'search', 'categoryId', 'availability', 'type', 'drinkCount', 'foodCount'
        ));
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(),
            'categories' => Category::orderBy('name')->get(),
            'addons' => AddOn::orderBy('name')->get(),
            'selectedAddonIds' => [],
            'componentNames' => '',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateProduct($request);
        $components = $this->parseComponents($validated['removable_components'] ?? '');

        $product = DB::transaction(function () use ($validated, $components) {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'is_available' => (bool) ($validated['is_available'] ?? false),
            ]);
            $this->syncOptions($product, $validated['add_ons'] ?? [], $components);
            return $product;
        });

        $this->savePhoto($request, $product);
        return redirect()->route('admin.products.index')->with('success', 'Product created. It is now ' . ($product->is_available ? 'visible' : 'hidden') . ' on the Customer Menu.');
    }

    public function edit(int $product): View
    {
        $item = Product::with(['addOns', 'components'])->findOrFail($product);
        return view('admin.products.form', [
            'product' => $item,
            'categories' => Category::orderBy('name')->get(),
            'addons' => AddOn::orderBy('name')->get(),
            'selectedAddonIds' => $item->addOns->pluck('addon_id')->map(fn ($id) => (int) $id)->all(),
            'componentNames' => $item->components->where('is_removable', true)->pluck('component_name')->implode("\n"),
        ]);
    }

    public function update(Request $request, int $product): RedirectResponse
    {
        $item = Product::findOrFail($product);
        $validated = $this->validateProduct($request, $item);
        $components = $this->parseComponents($validated['removable_components'] ?? '');
        $oldSlug = Str::slug($item->name);

        DB::transaction(function () use ($item, $validated, $components) {
            $item->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'base_price' => $validated['base_price'],
                'is_available' => (bool) ($validated['is_available'] ?? false),
            ]);
            $this->syncOptions($item, $validated['add_ons'] ?? [], $components);
        });

        $newSlug = Str::slug($item->name);
        if ($oldSlug !== $newSlug && !$request->hasFile('image')) {
            $previous = public_path('images/products/' . $oldSlug . '.png');
            $renamed = public_path('images/products/' . $newSlug . '.png');
            if (is_file($previous) && !file_exists($renamed)) {
                rename($previous, $renamed);
            }
        }
        $this->savePhoto($request, $item);
        return redirect()->route('admin.products.index')->with('success', 'Product details and customizations updated.');
    }

    public function toggle(int $product): RedirectResponse
    {
        $item = Product::findOrFail($product);
        $item->update(['is_available' => !$item->is_available]);
        return back()->with('success', $item->is_available ? 'Product is visible to customers.' : 'Product is hidden from the Customer Menu.');
    }

    private function validateProduct(Request $request, ?Product $product = null): array
    {
        $uniqueName = Rule::unique('products', 'name');
        if ($product && $product->exists) {
            $uniqueName->ignore($product->product_id, 'product_id');
        }

        $data = $request->validate([
            'category_id' => ['required', 'integer', 'exists:categories,category_id'],
            'name' => ['required', 'string', 'max:120', 'regex:/[a-zA-Z0-9]/', $uniqueName],
            'description' => ['nullable', 'string', 'max:2000'],
            'base_price' => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'decimal:0,2'],
            'is_available' => ['sometimes', 'boolean'],
            'add_ons' => ['sometimes', 'array', 'max:50'],
            'add_ons.*' => ['integer', 'distinct', 'exists:add_ons,addon_id'],
            'removable_components' => ['nullable', 'string', 'max:1600'],
            'image' => ['nullable', 'file', 'mimes:png', 'max:4096'],
        ]);

        // A beverage is a standalone menu product, not a food add-on.
        $selectedCategory = Category::findOrFail($data['category_id']);
        $isBeverage = (bool) preg_match('/(?:^|[^a-z])(?:drinks?|beverages?|refreshments?|juices?|milkshakes?|sodas?|coffees?|teas?|water)(?:$|[^a-z])/i', $selectedCategory->name);
        if ($isBeverage && (
            !empty($data['add_ons']) || trim((string) ($data['removable_components'] ?? '')) !== ''
        )) {
            throw ValidationException::withMessages([
                'category_id' => 'Beverages are separate menu products. Remove food extras and ingredient removals from this product.',
            ]);
        }

        // The Customer Menu uses the product-name slug as its photo filename.
        // Prevent two different product names from mapping to the same file.
        $slug = Str::slug($data['name']);
        if ($slug === '' || Product::query()
            ->when($product?->exists, fn ($q) => $q->where('product_id', '!=', $product->product_id))
            ->get(['name'])->contains(fn ($other) => Str::slug($other->name) === $slug)) {
            throw ValidationException::withMessages([
                'name' => 'Choose a distinct product name. Another product uses the same image filename.',
            ]);
        }
        return $data;
    }

    /** Keep historical component IDs, even when the option is no longer offered. */
    private function parseComponents(string $raw): array
    {
        $names = collect(preg_split('/\r\n|\r|\n/', $raw))
            ->map(fn ($name) => trim($name))
            ->filter()
            ->unique(fn ($name) => mb_strtolower($name))
            ->values();
        if ($names->count() > 15 || $names->contains(fn ($name) => mb_strlen($name) > 100)) {
            throw ValidationException::withMessages(['removable_components' => 'Use at most 15 lines, each up to 100 characters.']);
        }
        return $names->all();
    }

    private function syncOptions(Product $product, array $addonIds, array $components): void
    {
        $product->addOns()->sync($addonIds);
        $saved = $product->components()->get();

        foreach ($saved as $component) {
            $enabled = collect($components)->contains(fn ($name) => mb_strtolower($name) === mb_strtolower($component->component_name));
            if ((bool) $component->is_removable !== $enabled) {
                $component->update(['is_removable' => $enabled]);
            }
        }
        foreach ($components as $name) {
            $found = $saved->contains(fn ($component) => mb_strtolower($component->component_name) === mb_strtolower($name));
            if (!$found) {
                $product->components()->create(['component_name' => $name, 'is_removable' => true]);
            }
        }
    }

    /** Customer Blade templates already expect /public/images/products/{name-slug}.png. */
    private function savePhoto(Request $request, Product $product): void
    {
        if (!$request->hasFile('image')) {
            return;
        }
        $directory = public_path('images/products');
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        $request->file('image')->move($directory, Str::slug($product->name) . '.png');
    }
}
