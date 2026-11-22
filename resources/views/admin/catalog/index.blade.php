@extends('layouts.admin')
@section('title', 'Categories & Food Extras')
@section('content')
    <div class="a-page-head ap-heading cp-admin-heading">
        <div>
            <p class="a-eyebrow">MENU & STOCK / CATALOG</p>
            <h1>Categories & food extras</h1>
            <p>Two different jobs: categories organize products; extras are optional items added to a food order.</p>
        </div>
        <a href="{{ route('admin.products.index') }}" class="a-btn-secondary">← Back to products</a>
    </div>
    @if ($errors->any())
        <div class="ap-errors" role="alert"><strong>Please check the form:</strong>
            <ul>
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="a-panel cp-catalog-panel" id="categories">
        <div class="cp-catalog-head">
            <div>
                <p class="a-eyebrow">01 / MENU GROUPS</p>
                <h2>Product categories</h2>
                <p>Each product belongs to one category. Create a Drinks category to sell beverages separately.</p>
            </div><span class="cp-count">{{ $categories->count() }} total</span>
        </div>
        <form class="cp-create-form" action="{{ route('admin.catalog.categories.store') }}" method="POST">@csrf
            <div class="ap-field"><label for="new-category-name">Category name</label><input id="new-category-name"
                    name="name" required maxlength="120" placeholder="e.g. Drinks"></div>
            <div class="ap-field"><label for="new-category-description">Description <span
                        class="cp-optional">(optional)</span></label><input id="new-category-description" name="description"
                    maxlength="500" placeholder="What belongs in this category?"></div>
            <button class="ap-primary" type="submit">+ Add category</button>
        </form>
        <div class="cp-catalog-list">
            @forelse($categories as $category)
                <div class="cp-catalog-item">
                    <div class="cp-catalog-info">
                        <strong>{{ $category->name }}</strong><span>{{ $category->description ?: 'No description' }}</span><small>{{ $category->products_count }}
                            {{ $category->products_count === 1 ? 'product' : 'products' }}</small></div>
                    <details class="cp-edit-details">
                        <summary>Edit</summary>

                        <form action="{{ route('admin.catalog.categories.update', $category->category_id) }}" method="POST"
                            class="cp-edit-form">
                            @csrf
                            @method('PUT')
                            <div class="ap-field"><label for="category-name-{{ $category->category_id }}">Name</label><input
                                    id="category-name-{{ $category->category_id }}" name="name"
                                    value="{{ $category->name }}" maxlength="120" required></div>
                            <div class="ap-field"><label
                                    for="category-description-{{ $category->category_id }}">Description</label><input
                                    id="category-description-{{ $category->category_id }}" name="description"
                                    maxlength="500" value="{{ $category->description }}"></div>
                            <button class="ap-primary" type="submit">Save category</button>
                        </form>
                    </details>
                </div>
            @empty <div class="a-empty"><strong>No categories yet</strong>
                    <p>Add Chicken Meals or Drinks to organize your menu.</p>
                </div>
            @endforelse
        </div>
    </section>

    <section class="a-panel cp-catalog-panel" id="extras">
        <div class="cp-catalog-head">
            <div>
                <p class="a-eyebrow">02 / MEAL OPTIONS</p>
                <h2>Food extras</h2>
            </div><a class="a-btn-secondary" href="{{ route('admin.products.create') }}">Sell a drink? Add product ↗</a>
        </div>
        <form class="cp-create-form" action="{{ route('admin.catalog.addons.store') }}" method="POST">@csrf
            <div class="ap-field"><label for="extra-new-name">Extra name</label><input id="extra-new-name" name="name"
                    required maxlength="120" placeholder="e.g. Extra Rice"></div>
            <div class="ap-field"><label for="extra-new-price">Additional price (₱)</label><input id="extra-new-price"
                    name="price" required type="number" step="0.01" min="0" max="999999.99"
                    placeholder="20.00"></div>
            <label class="cp-availability-check"><input type="hidden" name="is_available" value="0"><input
                    type="checkbox" name="is_available" value="1" checked> Show as available</label>
            <button class="ap-primary" type="submit">+ Add food extra</button>
        </form>
        <div class="cp-catalog-list">
            @forelse($addons as $addon)
                <div class="cp-catalog-item cp-addon-entry">
                    <div class="cp-catalog-info">
                        <strong>{{ $addon->name }}</strong>
                        <span>₱{{ number_format((float) $addon->price, 2) }} · {{ $addon->products_count }}
                            {{ $addon->products_count === 1 ? 'product' : 'products' }} linked</span>
                        <small><span
                                class="ap-chip {{ $addon->is_available ? 'ap-chip-live' : 'ap-chip-hidden' }}">{{ $addon->is_available ? 'Available' : 'Hidden' }}</span></small>
                    </div>
                    <div class="cp-entry-actions">
                        <button type="button" class="cp-ux-button cp-ux-edit"
                            aria-expanded="{{ old('editing_addon_id') == $addon->addon_id ? 'true' : 'false' }}"
                            aria-controls="extra-edit-{{ $addon->addon_id }}"
                            data-extra-editor="extra-edit-{{ $addon->addon_id }}">Edit</button>
                        <form method="POST" action="{{ route('admin.catalog.addons.toggle', $addon->addon_id) }}">@csrf
                            @method('PATCH')<button class="cp-ux-button"
                                type="submit">{{ $addon->is_available ? 'Hide' : 'Show' }}</button></form>
                    </div>
                    <div id="extra-edit-{{ $addon->addon_id }}" class="cp-ux-editor"
                        @unless (old('editing_addon_id') == $addon->addon_id) hidden @endunless>
                        <form action="{{ route('admin.catalog.addons.update', $addon->addon_id) }}" method="POST"
                            class="cp-edit-form">@csrf @method('PUT')<input type="hidden" name="editing_addon_id"
                                value="{{ $addon->addon_id }}">
                            <div class="ap-field"><label for="extra-name-{{ $addon->addon_id }}">Extra name</label><input
                                    id="extra-name-{{ $addon->addon_id }}" name="name" value="{{ $addon->name }}"
                                    maxlength="120" required></div>
                            <div class="ap-field"><label for="extra-price-{{ $addon->addon_id }}">Additional price
                                    (₱)</label><input id="extra-price-{{ $addon->addon_id }}" name="price"
                                    type="number" step="0.01" min="0" max="999999.99"
                                    value="{{ $addon->price }}" required></div>
                            @if ($addon->customizations_count)
                                <small>This name is used in saved orders and cannot be renamed; prices can change for new
                                    orders.</small>
                            @endif
                            <button class="ap-primary" type="submit">Save changes</button>
                        </form>
                    </div>
                </div>
            @empty <div class="a-empty"><strong>No food extras yet</strong>
                    <p>Add optional rice, gravy, or cheese for your food products.</p>
                </div>
            @endforelse
        </div>
    </section>
    <script src="{{ asset('js/admin-catalog-editor.js') }}" defer></script>
@endsection
