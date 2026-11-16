@extends('layouts.admin')
@section('title', 'Products')
@section('content')
<div class="a-page-head ap-heading cp-admin-heading">
    <div>
        <p class="a-eyebrow">MENU & STOCK / PRODUCTS</p>
        <h1>Product management</h1>
        <p>Keep your customer menu accurate. Food and beverages are separate products; extras are optional food add-ons.</p>
    </div>
    <div class="ap-actions">
        <a class="a-btn-secondary" href="{{ route('admin.catalog.index') }}">Categories & extras</a>
        <a class="ap-primary" href="{{ route('admin.products.create') }}">+ Add product</a>
    </div>
</div>
<div class="cp-help-strip" role="note">
    <strong>Quick guide</strong>
    <span><b>Food:</b> meals and chicken pops. <b>Beverages:</b> drinks sold separately. <b>Extras:</b> optional rice, gravy, cheese, etc.</span>
    <a href="{{ route('admin.catalog.index') }}#extras">Manage food extras</a>
</div>
<div class="cp-metrics" aria-label="Product summary">
    <div><span>Total products</span><strong>{{ $foodCount + $drinkCount }}</strong></div>
    <div><span>Food products</span><strong>{{ $foodCount }}</strong></div>
    <div><span>Beverages</span><strong>{{ $drinkCount }}</strong></div>
    <div><span>Visible to customers</span><strong>{{ \App\Models\Product::where('is_available', true)->count() }}</strong></div>
</div>
<div class="cp-type-tabs" role="group" aria-label="Product type">
    @foreach(['all' => 'All products', 'food' => 'Food', 'beverage' => 'Beverages'] as $key => $label)
    <a class="cp-type-tab {{ $type === $key ? 'is-active' : '' }}" @if($type===$key) aria-current="page" @endif href="{{ route('admin.products.index', array_filter(['type'=>$key === 'all' ? null : $key, 'q'=>$search ?: null, 'category'=>$categoryId, 'availability'=>$availability === 'all' ? null : $availability])) }}">{{ $label }}</a>
    @endforeach
</div>
<section class="a-panel cp-list-panel" aria-label="Manage products">
    <form method="GET" action="{{ route('admin.products.index') }}" class="ap-filter cp-product-filters">
        <input type="hidden" name="type" value="{{ $type }}">
        <div class="ap-field ap-search"><label for="product-search">Search products</label><input type="search" id="product-search" name="q" value="{{ $search }}" placeholder="Search name or description" maxlength="80"></div>
        <div class="ap-field"><label for="product-category">Category</label><select id="product-category" name="category">
                <option value="">All categories</option>@foreach($categories as $category)<option value="{{ $category->category_id }}" @selected((string)$categoryId===(string)$category->category_id)>{{ $category->name }}</option>@endforeach
            </select></div>
        <div class="ap-field"><label for="product-visibility">Visibility</label><select id="product-visibility" name="availability">
                <option value="all" @selected($availability==='all' )>All</option>
                <option value="available" @selected($availability==='available' )>Visible</option>
                <option value="hidden" @selected($availability==='hidden' )>Hidden</option>
            </select></div>
        <button type="submit" class="ap-primary">Search / Filter</button><a class="ap-clear" href="{{ route('admin.products.index') }}">Reset</a>
    </form>
    <div class="cp-list-toolbar">
        <h2>Menu items <span>{{ $products->total() }} found</span></h2><span>Use Edit to change details; Hide removes a product from new orders.</span>
    </div>
    @if($products->isEmpty())
    <div class="a-empty"><strong>No matching products</strong>
        <p>Change your filters or create your first product.</p><a href="{{ route('admin.products.create') }}" class="ap-primary">Add product</a>
    </div>
    @else
    <div class="a-table-wrap">
        <table class="a-table ap-table cp-product-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Customer menu</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                @php
                $imgPath='images/products/'.\Illuminate\Support\Str::slug($product->name).'.png';
                $imageExists=is_file(public_path($imgPath));
                @endphp
                <tr>
                    <td>
                        <div class="ap-product-cell">
                            <div class="ap-thumb">@if($imageExists)<img src="{{ asset($imgPath) }}" loading="lazy" alt="">@else<span aria-hidden="true">🍗</span>@endif</div>
                            <div><strong>{{ $product->name }}</strong><small>#{{ $product->product_id }} · {{ \Illuminate\Support\Str::limit($product->description ?: 'No description provided', 55) }}</small></div>
                        </div>
                    </td>
                    <td>{{ $product->category?->name ?? 'Uncategorized' }}</td>
                    <td><strong>₱{{ number_format((float)$product->base_price, 2) }}</strong></td>
                    <td><span class="ap-chip {{ $product->is_available ? 'ap-chip-live' : 'ap-chip-hidden' }}">{{ $product->is_available ? 'Visible' : 'Hidden' }}</span></td>
                    <td>
                        <div class="ap-row-actions">
                            <a href="{{ route('admin.products.edit', $product->product_id) }}" class="ap-edit">Edit</a>
                            <form method="POST" action="{{ route('admin.products.toggle', $product->product_id) }}">@csrf @method('PATCH')<button type="submit" class="ap-muted-action">{{ $product->is_available ? 'Hide' : 'Show' }}</button></form>
                            @if($product->is_available)<a href="{{ route('customer.customize', $product->product_id) }}" target="_blank" rel="noopener noreferrer" class="ap-muted-action" aria-label="Preview {{ $product->name }} in a new tab">Preview ↗</a>@endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($products->hasPages())<nav class="a-pagination" aria-label="Product pages">
        @if($products->onFirstPage())<span>← Previous</span>@else<a href="{{ $products->previousPageUrl() }}">← Previous</a>@endif
        <span>Page {{ $products->currentPage() }} of {{ $products->lastPage() }}</span>
        @if($products->hasMorePages())<a href="{{ $products->nextPageUrl() }}">Next →</a>@else<span>Next →</span>@endif
    </nav>@endif
    @endif
</section>
<p class="cp-caption">Products are hidden, not deleted, to protect old receipts and order history. Stock levels are handled in Inventory.</p>
@endsection