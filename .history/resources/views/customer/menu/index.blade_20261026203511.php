@extends('layouts.customer')

@section('title', "Order Menu | Chick's Pops")
@section('body_class', 'cc-menu-page')

@section('page_assets')
    @vite(['resources/css/customer/menu.css', 'resources/js/app.js'])
@endsection

@php
    // These query parameters are already supported by the existing MenuController.
    $menuLink = fn (array $overrides = []) => route('customer.menu', array_filter(
        array_merge([
            'q' => $search,
            'category' => $categoryId,
            'flavor' => $flavor === 'all' ? null : $flavor,
        ], $overrides),
        fn ($value) => $value !== null && $value !== '' && $value !== 'all'
    ));

    // Change filters without losing the other active selections.
    $categoryLink = fn ($id) => $menuLink(['category' => $id]);
    $flavorLink = fn ($value) => $menuLink(['flavor' => $value]);

    $activeCategory = $categories->firstWhere('category_id', (int) $categoryId);
    $heroImage = 'images/hero/chicks-pops-banner.png';
    $hasHeroImage = is_file(public_path($heroImage));
    $hasFilters = $search !== '' || $categoryId !== null || $flavor !== 'all';
@endphp

@section('content')
<main id="main-content" class="ccm-main">
    <div class="ccm-container">
        <div class="ccm-breadcrumb" aria-label="Current page">
            <span>CHICK'S POPS</span>
            <span class="ccm-breadcrumb-divider" aria-hidden="true">/</span>
            <span aria-current="page">Menu</span>
        </div>

        <section class="ccm-hero" aria-labelledby="ccm-hero-heading">
            <div class="ccm-hero-body">
                <div class="ccm-eyebrow"><span class="ccm-eyebrow-dot" aria-hidden="true"></span> YOUR CRAVINGS, YOUR WAY</div>
                <h1 id="ccm-hero-heading">A little crunch.<br><span>A lot to love.</span></h1>
                <p>Explore the Chick's Pops menu. Pick your favorite, choose your add-ons, and make every order yours.</p>
                <a class="ccm-hero-button" href="#browse-menu">Explore our menu <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></a>
            </div>
            <div class="ccm-hero-art" aria-hidden="true">
                <div class="ccm-hero-blob ccm-hero-blob-one"></div>
                <div class="ccm-hero-blob ccm-hero-blob-two"></div>
                @if($hasHeroImage)
                    <img src="{{ asset($heroImage) }}" alt="" class="ccm-hero-food">
                @else
                    <div class="ccm-hero-art-fallback">
                        <svg viewBox="0 0 110 110" fill="none" aria-hidden="true"><circle cx="55" cy="55" r="43" stroke="currentColor" stroke-width="3"/><circle cx="55" cy="55" r="34" stroke="currentColor" stroke-width="2" stroke-dasharray="5 8"/><path d="M33 53c0-11 10-19 22-19s22 8 22 19-10 23-22 23-22-12-22-23Z" fill="currentColor" opacity=".20"/><path d="M43 45c8-8 18-8 24 0M41 61c6 7 21 8 29 0" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                        <span>Made to be yours.</span>
                    </div>
                @endif
            </div>
        </section>

        <section id="browse-menu" class="ccm-explore" aria-labelledby="ccm-menu-title">
            <div class="ccm-intro-row">
                <div class="ccm-intro-copy">
                    <span class="ccm-section-eyebrow">MENU</span>
                    <h2 id="ccm-menu-title">Find your favorite.</h2>
                    <p>Browse by category, search for a product, or explore different flavors.</p>
                </div>
                <form action="{{ route('customer.menu') }}#browse-menu" method="GET" role="search" class="ccm-search-form">
                    @if($categoryId !== null)
                        <input type="hidden" name="category" value="{{ $categoryId }}">
                    @endif
                    @if($flavor !== 'all')
                        <input type="hidden" name="flavor" value="{{ $flavor }}">
                    @endif
                    <label for="ccm-search" class="ccm-visually-hidden">Search food and drinks</label>
                    <svg class="ccm-search-icon" aria-hidden="true" viewBox="0 0 24 24" fill="none"><circle cx="10.8" cy="10.8" r="6.4" stroke="currentColor" stroke-width="1.8"/><path d="m16 16 4 4" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/></svg>
                    <input id="ccm-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="Search meals, sides, drinks…">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="ccm-filter-bar">
                <div class="ccm-filter-label" id="ccm-category-label">Categories</div>
                <nav class="ccm-category-list" aria-labelledby="ccm-category-label">
                    <a href="{{ $categoryLink(null) }}" class="ccm-category {{ $categoryId === null ? 'ccm-category-active' : '' }}" @if($categoryId===null) aria-current="page" @endif>All items</a>
                    @foreach($categories as $category)
                        <a href="{{ $categoryLink($category->category_id) }}" class="ccm-category {{ (int)$categoryId === (int)$category->category_id ? 'ccm-category-active' : '' }}" @if((int)$categoryId === (int)$category->category_id) aria-current="page" @endif>{{ $category->name }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="ccm-flavor-bar">
                <span class="ccm-flavor-label" id="ccm-flavor-label">Explore flavors:</span>
                <nav aria-labelledby="ccm-flavor-label" class="ccm-flavor-list">
                    @foreach(['all' => 'All flavors', 'spicy' => 'Spicy', 'cheesy' => 'Cheesy', 'garlic' => 'Garlic & herb'] as $key => $label)
                        <a href="{{ $flavorLink($key) }}" class="ccm-flavor {{ $flavor === $key ? 'ccm-flavor-active' : '' }}" @if($flavor === $key) aria-current="page" @endif>{{ $label }}</a>
                    @endforeach
                </nav>
            </div>

            <div class="ccm-results-heading">
                <div>
                    <h3>{{ $activeCategory ? $activeCategory->name : 'All favorites' }}</h3>
                    <p>
                        @if($search !== '')
                            Results for “{{ $search }}” ·
                        @endif
                        {{ $products->total() }} {{ $products->total() === 1 ? 'product' : 'products' }} available
                    </p>
                </div>
                @if($hasFilters)
                    <a href="{{ route('customer.menu') }}#browse-menu" class="ccm-clear-filters">Clear filters <svg aria-hidden="true" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6 6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></a>
                @endif
            </div>

            <div class="ccm-product-grid" aria-live="polite">
                @forelse($products as $product)
                    <x-customer.product-card :product="$product" />
                @empty
                    <div class="ccm-empty">
                        <div class="ccm-empty-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="1.5"/><path d="m16 16 5 5M9 9l4 4m0-4-4 4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                        </div>
                        <h4>No matching products</h4>
                        <p>Try another search or remove a filter to see more of the menu.</p>
                        <a href="{{ route('customer.menu') }}#browse-menu">Show all products</a>
                    </div>
                @endforelse
            </div>

            @if($products->hasPages())
                <div class="ccm-pagination" aria-label="More products">
                    {{ $products->links() }}
                </div>
            @endif
        </section>

        <aside class="ccm-assurance" aria-label="How ordering works">
            <div><span class="ccm-assurance-step">01</span><strong>Choose a favorite</strong><small>Pick from the menu above</small></div>
            <div><span class="ccm-assurance-step">02</span><strong>Make it yours</strong><small>Select extras and preferences</small></div>
            <div><span class="ccm-assurance-step">03</span><strong>Checkout with ease</strong><small>Review before placing your order</small></div>
        </aside>
    </div>
</main>
@endsection
