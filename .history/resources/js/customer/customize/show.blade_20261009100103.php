@php
// Existing Product model has no image column. Use a local image named after the product.
$imageRelativePath = 'images/products/' . \Illuminate\Support\Str::slug($product->name) . '.png';
$hasProductImage = is_file(public_path($imageRelativePath));
$hasLogo = is_file(public_path('images/logo.png'));
$hasSauceComponent = $product->components->contains(function ($component) {
return str_contains(strtolower($component->component_name), 'sauce');
});
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Customize {{ $product->name }} | Chick's Pops</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/customer/customize.css', 'resources/js/app.js'])
</head>

<body class="cc-product-page">
    <header class="cc-site-header">
        <a class="cc-brand" href="{{ url('/') }}" aria-label="Chick's Pops home">
            @if($hasLogo)
            <img src="{{ asset('images/logo.png') }}" alt="Chick's Pops logo" class="cc-brand-logo">
            @endif
            <span>CHICK'S<br>POPS</span>
        </a>
        <nav class="cc-top-navigation" aria-label="Product categories">
            <span class="cc-nav-current">CHICKEN POPS</span>
            <span>SIDES</span>
            <span>DRINKS</span>
            <span>DESSERTS</span>
        </nav>
        <div class="cc-header-search" aria-label="Search will be available with the menu page">
            Search for pops, sides...
            <span aria-hidden="true">⌕</span>
        </div>
    </header>

    <main class="cc-product-shell" data-customizer data-base-price="{{ $product->base_price }}">
        <div class="cc-product-intro">
            <div class="cc-product-photo">
                @if($hasProductImage)
                <img src="{{ asset($imageRelativePath) }}" alt="{{ $product->name }}">
                @else
                <div class="cc-photo-placeholder">
                    <span aria-hidden="true">🍗</span>
                    <span>Export the product photo from Figma to<br><code>public/{{ $imageRelativePath }}</code></span>
                </div>
                @endif
            </div>
            <div class="cc-product-summary">
                <a href="{{ url('/') }}" class="cc-back-link">← BACK TO MENU</a>
                <h1>{{ $product->name }}</h1>
                <p class="cc-base-price">₱{{ number_format((float) $product->base_price, 2) }}</p>
                @if($product->description)
                <p class="cc-description">{{ $product->description }}</p>
                @endif
            </div>
        </div>

        <div class="cc-config-grid">
            <div class="cc-left-options">
                @if($hasSauceComponent)
                <section class="cc-option-block" aria-labelledby="cc-sauce-title">
                    <div class="cc-section-heading">
                        <h2 id="cc-sauce-title">PICK YOUR SAUCE</h2>
                        <span>(Choose One)</span>
                    </div>
                    <div class="cc-sauce-list" id="cc-sauce-options">
                        {{-- Preview choices from the Figma design. Not yet stored as menu options in SQLite. --}}
                        @foreach(['Honey Mustard', 'Smoky BBQ', 'Spicy Garlic Mayo'] as $index => $sauce)
                        <label class="cc-sauce-option">
                            <input type="radio" name="sauce_choice" value="{{ $sauce }}" @checked($index===0)>
                            <span class="cc-sauce-name">{{ $sauce }}</span>
                            <span class="cc-free">FREE</span>
                        </label>
                        @endforeach
                    </div>
                    <p class="cc-helper">Sauces are design-preview choices until we connect them to order storage.</p>
                </section>
                @endif

                <section class="cc-option-block" aria-labelledby="cc-remove-title">
                    <div class="cc-section-heading">
                        <h2 id="cc-remove-title">REMOVE COMPONENTS</h2>
                        <span>(Optional)</span>
                    </div>
                    @forelse($product->components as $component)
                    <label class="cc-removal-option">
                        <input type="checkbox"
                            name="remove_components[]"
                            value="{{ $component->component_id }}"
                            @if(strtolower($component->component_name) === 'sauce') data-remove-sauce @endif>
                        <span>No {{ $component->component_name }}</span>
                    </label>
                    @empty
                    <p class="cc-empty">No removable components for this item.</p>
                    @endforelse
                </section>
            </div>

            <div class="cc-right-options">
                <section class="cc-option-block" aria-labelledby="cc-extras-title">
                    <div class="cc-section-heading">
                        <h2 id="cc-extras-title">ADD SOME EXTRAS</h2>
                        <span>(Optional)</span>
                    </div>
                    <div class="cc-extra-list">
                        @forelse($product->addOns as $addon)
                        <label class="cc-extra-option">
                            <input type="checkbox"
                                name="add_ons[]"
                                value="{{ $addon->addon_id }}"
                                data-addon-price="{{ $addon->price }}">
                            <span>{{ $addon->name }}</span>
                            <strong>+₱{{ number_format((float) $addon->price, 2) }}</strong>
                        </label>
                        @empty
                        <p class="cc-empty">No extras available for this item.</p>
                        @endforelse
                    </div>
                </section>

                <section class="cc-option-block" aria-labelledby="cc-notes-title">
                    <div class="cc-section-heading">
                        <h2 id="cc-notes-title">SPECIAL INSTRUCTIONS</h2>
                        <span>(Optional)</span>
                    </div>
                    <textarea class="cc-notes" name="item_notes" maxlength="500" rows="3"
                        placeholder="Extra crispy, less spicy..."></textarea>
                </section>
            </div>
        </div>

        <section class="cc-bottom-bar" aria-label="Order preview controls">
            <div class="cc-quantity-group">
                <span class="cc-quantity-label">Quantity</span>
                <button type="button" data-quantity-step="-1" aria-label="Decrease quantity">−</button>
                <input id="item-quantity" type="number" min="1" max="20" value="1" aria-label="Quantity">
                <button type="button" data-quantity-step="1" aria-label="Increase quantity">+</button>
            </div>
            <div class="cc-order-total">
                <small>LIVE TOTAL</small>
                <strong id="item-total">₱{{ number_format((float) $product->base_price, 2) }}</strong>
            </div>
            <button type="button" class="cc-cart-button" disabled
                title="Cart integration is the next step">
                ADD TO CART — COMING NEXT
            </button>
        </section>
        <p class="cc-checkout-notice">This page previews customization only. It does not save an order or charge a payment.</p>
    </main>
</body>

</html>