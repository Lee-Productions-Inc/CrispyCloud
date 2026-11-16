<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\MenuSections;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $input = $request->validate([
            'q' => ['nullable', 'string', 'max:80'],
            'category' => ['nullable', 'integer', 'min:1'],
            'flavor' => ['nullable', 'in:all,spicy,cheesy,garlic'],
            'section' => ['nullable', 'in:food,beverages'],
        ]);

        $section = $input['section'] ?? 'food';
        $search = trim($input['q'] ?? '');
        $categoryId = $input['category'] ?? null;
        $flavor = $section === 'food' ? ($input['flavor'] ?? 'all') : 'all';

        // Only offer categories that belong to the selected section AND have visible products.
        $categories = Category::query()->withCount([
            'products' => fn ($q) => $q->where('is_available', true),
        ])->orderBy('name')->get()->filter(
            fn ($category) => $category->products_count > 0 &&
                MenuSections::isBeverageCategory($category->name) === ($section === 'beverages')
        )->values();

        $categoryIds = $categories->pluck('category_id')->all();
        if ($categoryId !== null && !in_array((int) $categoryId, array_map('intval', $categoryIds), true)) {
            $categoryId = null;
        }

        $query = Product::query()->with('category')
            ->where('is_available', true)
            ->whereIn('category_id', $categoryIds);

        if ($categoryId !== null) {
            $query->where('category_id', $categoryId);
        }
        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        // Preserve earlier flavor links for foods only.
        $flavorTerms = ['spicy' => 'spic', 'cheesy' => 'chees', 'garlic' => 'garlic'];
        if (isset($flavorTerms[$flavor])) {
            $query->where('name', 'like', '%' . $flavorTerms[$flavor] . '%');
        }

        $products = $query->orderBy('name')->paginate(12)->withQueryString();

        return view('customer.menu.index', compact(
            'products', 'categories', 'search', 'categoryId', 'flavor', 'section'
        ));
    }
}
