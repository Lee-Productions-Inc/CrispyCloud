@extends('layouts.admin')
@php
    $editing = $product->exists;
    $selected = array_map('intval', (array) old('add_ons', $selectedAddonIds));
    $image = $editing ? 'images/products/' . \Illuminate\Support\Str::slug($product->name) . '.png' : null;
@endphp
@section('title', $editing ? 'Edit Product' : 'Add Product')
@section('content')
<div class="a-page-head ap-heading"><div><p class="a-eyebrow">PRODUCT MANAGEMENT</p><h1>{{ $editing ? 'Edit product' : 'Add a new product' }}</h1><p>Customers will see these products in the menu. Put beverages in the Drinks category, not in food Extras.</p></div><a class="a-btn-secondary" href="{{ route('admin.products.index') }}">← All products</a></div>
@if($errors->any())
<div class="ap-errors" role="alert"><strong>Please review the following:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif
@if($categories->isEmpty())
<div class="a-panel"><h2>Create a category first</h2><p>Every product requires a category. Add one before creating a product.</p><a class="ap-primary" href="{{ route('admin.catalog.index') }}">Manage categories</a></div>
@else
<form class="ap-product-editor" action="{{ $editing ? route('admin.products.update', $product->product_id) : route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
    @csrf @if($editing) @method('PUT') @endif
    <div class="ap-form-main">
        <section class="a-panel ap-panel-group">
            <h2>Basic information</h2><p class="ap-helper">Use the actual item name and the price approved by CHICK'S POPS.</p>
            <div class="ap-form-grid">
                <div class="ap-field ap-span"><label for="p-name">Product name <span aria-hidden="true">*</span></label><input id="p-name" name="name" maxlength="120" value="{{ old('name', $product->name) }}" required placeholder="e.g. Garlic Mayo Chick Pops">@error('name')<small class="ap-invalid">{{ $message }}</small>@enderror</div>
                <div class="ap-field"><label for="p-cat">Category <span aria-hidden="true">*</span></label><select name="category_id" id="p-cat" required><option value="">Select category</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" data-is-beverage="{{ preg_match('/(?:^|[^a-z])(?:drinks?|beverages?|refreshments?|juices?|milkshakes?|sodas?|coffees?|teas?|water)(?:$|[^a-z])/i', $category->name) ? '1' : '0' }}" @selected((string)old('category_id', $product->category_id) === (string)$category->category_id)>{{ $category->name }}</option>@endforeach</select></div>
                <div class="ap-field"><label for="p-price">Base price (₱) <span aria-hidden="true">*</span></label><input id="p-price" type="number" step="0.01" min="0.01" max="999999.99" name="base_price" value="{{ old('base_price', $product->base_price) }}" required placeholder="85.00"></div>
                <div class="ap-field ap-span"><label for="p-description">Description</label><textarea id="p-description" name="description" rows="4" maxlength="2000" placeholder="Describe the meal in a few helpful sentences">{{ old('description', $product->description) }}</textarea></div>
            </div>
        </section>
        <section class="a-panel ap-panel-group" id="ap-food-customizations">
            <h2>Customization options · food only</h2><p class="ap-helper">Assign food extras and removable ingredients to meals. Beverages are always ordered as separate products.</p>
            <div class="ap-check-grid">
                @forelse($addons as $addon)
                    <label class="ap-check-option"><input type="checkbox" name="add_ons[]" value="{{ $addon->addon_id }}" @checked(in_array((int)$addon->addon_id, $selected, true))><span><strong>{{ $addon->name }}</strong><small>₱{{ number_format((float)$addon->price, 2) }} · {{ $addon->is_available ? 'Available' : 'Hidden in customer menu' }}</small></span></label>
                @empty
                    <p class="ap-helper">No extras yet. <a href="{{ route('admin.catalog.index') }}">Create extras</a>, then return here.</p>
                @endforelse
            </div>
            <div class="ap-field"><label for="p-components">Removable ingredients or components</label><textarea id="p-components" name="removable_components" rows="5" placeholder="Rice&#10;Gravy&#10;Sauce">{{ old('removable_components', $componentNames) }}</textarea><small class="ap-helper">One name per line. Customers can choose to remove these. Including a component named “Sauce” enables the existing sauce selector. Removing a line here disables that option without deleting historical orders.</small></div>
        </section>
        <div class="a-soft-note" id="ap-drink-note" hidden>Beverage selected: this product will be listed under Drinks and ordered separately. Food extras and removable components are disabled for beverages.</div>
    </div>
    <aside class="ap-form-side">
        <section class="a-panel ap-panel-group"><h2>Product image</h2>
            <div class="ap-image-preview">@if($image && is_file(public_path($image)))<img src="{{ asset($image) }}" alt="Current photo for {{ $product->name }}">@else<span aria-hidden="true">🍗</span><small>No photo yet</small>@endif</div>
            <div class="ap-field"><label for="p-image">Upload a Photo</label><input type="file" id="p-image" name="image" accept=".png,.jpg,.jpeg,image/png,image/jpeg" ><small class="ap-helper">PNG only, up to 4 MB. Automatically appears on customer cards; no database migration needed.</small></div>
        </section>
        <section class="a-panel ap-panel-group"><h2>Customer visibility</h2><input type="hidden" name="is_available" value="0"><label class="ap-check-option"><input type="checkbox" name="is_available" value="1" @checked(old('is_available', $editing ? $product->is_available : true))><span><strong>Show on customer menu</strong><small>Uncheck to save as hidden. Existing orders remain unchanged.</small></span></label>
            <div class="ap-submit"><button class="ap-primary" type="submit">{{ $editing ? 'Save changes' : 'Create product' }}</button><a class="ap-clear" href="{{ route('admin.products.index') }}">Cancel</a></div>
        </section>
    </aside>
</form>
<script src="{{ asset('js/admin-product-refine.js') }}" defer></script>
@endif
@endsection
