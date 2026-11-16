<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AddOn;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function index(): View
    {
        return view('admin.catalog.index', [
            'categories' => Category::withCount('products')->orderBy('name')->get(),
            'addons' => AddOn::withCount(['products', 'customizations'])->orderBy('name')->get(),
        ]);
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        Category::create($data);
        return redirect(route('admin.catalog.index') . '#categories')->with('success', 'Category added.');
    }

    public function updateCategory(Request $request, int $category): RedirectResponse
    {
        $item = Category::findOrFail($category);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('categories', 'name')->ignore($item->category_id, 'category_id')],
            'description' => ['nullable', 'string', 'max:500'],
        ]);
        $item->update($data);
        return redirect()->route('admin.catalog.index')->with('success', 'Category updated.');
    }

    public function storeAddOn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('add_ons', 'name')],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
            'is_available' => ['sometimes', 'boolean'],
        ]);
        $this->rejectBeverageExtra($data['name']);
        AddOn::create([
            'name' => $data['name'],
            'price' => $data['price'],
            'is_available' => (bool) ($data['is_available'] ?? false),
        ]);
        return redirect()->route('admin.catalog.index')->with('success', 'Add-on added. Link it to products in the Edit Product page.');
    }

    public function updateAddOn(Request $request, int $addon): RedirectResponse
    {
        $item = AddOn::findOrFail($addon);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('add_ons', 'name')->ignore($item->addon_id, 'addon_id')],
            'price' => ['required', 'numeric', 'min:0', 'max:999999.99', 'decimal:0,2'],
        ]);
        $this->rejectBeverageExtra($data['name']);
        if ($item->customizations()->exists() && $item->name !== $data['name']) {
            throw ValidationException::withMessages(['name' => 'This extra appears in past orders. Keep its original name, or hide it and create a new extra.']);
        }
        $item->update($data);
        return redirect()->route('admin.catalog.index')->with('success', 'Add-on updated. Customers will see the new price on their next customization.');
    }

    public function toggleAddOn(int $addon): RedirectResponse
    {
        $item = AddOn::findOrFail($addon);
        $item->update(['is_available' => !$item->is_available]);
        return back()->with('success', $item->is_available ? 'Add-on is available.' : 'Add-on is hidden from new customizations.');
    }

    /** Delete only extras never used in a placed order. Linked product offerings are detached. */
    public function destroyAddOn(int $addon): RedirectResponse
    {
        try {
            DB::transaction(function () use ($addon): void {
                $item = AddOn::query()->lockForUpdate()->findOrFail($addon);

                if ($item->customizations()->exists()) {
                    throw ValidationException::withMessages([
                        'delete' => 'This extra appears in previous orders and cannot be deleted. Use Hide instead.',
                    ]);
                }

                // Recipe definitions are only removed for an otherwise unused extra.
                if (Schema::hasTable('addon_ingredient_recipes')) {
                    DB::table('addon_ingredient_recipes')->where('addon_id', $item->addon_id)->delete();
                }

                $item->products()->detach();
                $item->delete();
            });
        } catch (QueryException $e) {
            return redirect(route('admin.catalog.index') . '#extras')
                ->with('error', 'This extra is referenced by other records. Hide it instead of deleting it.');
        }

        return redirect(route('admin.catalog.index') . '#extras')
            ->with('success', 'Unused extra deleted and unlinked from products.');
    }

    /** Prevent drinks accidentally being registered as food extras. */
    private function rejectBeverageExtra(string $name): void
    {
        if (preg_match('/\b(?:coke|cola|pepsi|sprite|fanta|soda|soft[ -]?drink|juice|iced[ -]?tea|milk[ -]?tea|milkshake|coffee|latte|water|lemonade|root[ -]?beer|energy[ -]?drink)\b/i', $name)) {
            throw ValidationException::withMessages([
                'name' => 'Beverages belong under Products → Add Product → Drinks, not Food Extras.',
            ]);
        }
    }

}
