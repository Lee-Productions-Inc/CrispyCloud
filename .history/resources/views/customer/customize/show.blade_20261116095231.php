@extends('layouts.customer')

@section('title', 'Customize ' . $product->name . ' | Chick\'s Pops')
@section('body_class', 'cc-product-page')

@section('page_assets')
    @vite('resources/js/app.js')
@endsection

@php
    // Product images are local assets. The Product model does not have an image column.
    $imagePath = 'images/products/' . \Illuminate\Support\Str::slug($product->name) . '.png';
    $imageAvailable = is_file(public_path($imagePath));
    $hasSauceComponent = $product->components->contains(
        fn ($component) => str_contains(strtolower($component->component_name), 'sauce')
    );
    // Extra quantities are per individual meal. 0 means not selected.
    $selectedAddons = (array) old('add_ons', []);
    $selectedRemovals = array_map('intval', (array) old('remove_components', []));
    $initialQty = max(1, min(20, (int) old('quantity', 1)));
    $initialExtras = $product->addOns->sum(fn ($addon) =>
        (float) $addon->price * max(0, min(10, (int) ($selectedAddons[$addon->addon_id] ?? 0)))
    );
    $initialUnit = (float) $product->base_price + $initialExtras;
$selectedDrink = $isBeverage ? null : $beverages->firstWhere('product_id', (int) old('beverage_id', 0));
$drinkPrice = $selectedDrink ? (float) $selectedDrink->base_price : 0;
$initialTotal = ($initialUnit + $drinkPrice) * $initialQty;
@endphp

@section('content')
<main id="main-content" class="ccx-main" data-customizer data-base-price="{{ $product->base_price }}">
    <div class="ccx-container"> 
        <nav class="ccx-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.menu') }}">Menu</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Customize your order</span>
        </nav>

        @if($errors->any())
            <div class="ccx-form-errors" role="alert" tabindex="-1">
                <strong>Please check your selections.</strong>
                <ul>
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form class="ccx-form" method="POST" action="{{ route('customer.cart.store', $product->product_id) }}">
            @csrf

            <section class="ccx-intro" aria-labelledby="ccx-product-name">
                <div class="ccx-photo-wrap">
                    @if($imageAvailable)
                        <img class="ccx-product-img" src="{{ asset($imagePath) }}" alt="{{ $product->name }}" fetchpriority="high">
                    @else
                        <div class="ccx-photo-fallback" role="img" aria-label="Product photo unavailable">
                            <span class="ccx-fallback-symbol" aria-hidden="true">{{ $isBeverage ? '🥤' : '🍗' }}</span>
                            <span>Product photo coming soon</span>
                        </div>
                    @endif
                </div>

                <div class="ccx-intro-copy">
                    <span class="ccx-eyebrow">MADE JUST FOR YOU</span>
                    <h1 id="ccx-product-name">{{ $product->name }}</h1>
                    <p class="ccx-intro-description">{{ $product->description ?: 'Freshly prepared and ready to make your own.' }}</p>
                    <div class="ccx-intro-price">
                        <span>Starting at</span>
                        <strong>₱{{ number_format((float) $product->base_price, 2) }}</strong>
                    </div>
                    <p class="ccx-intro-guidance">{{ $isBeverage ? 'Beverages are priced and added separately from food extras.' : 'Choose your preferences below, then review your total before adding to cart.' }}</p>
                </div>
            </section>

            <div class="ccx-section-heading">
                <div>
                    <span class="ccx-eyebrow">YOUR PREFERENCES</span>
                    <h2>{{ $isBeverage ? 'Choose your drink.' : 'Make it your way.' }}</h2>
                    <p>{{ $isBeverage ? 'Review your beverage quantity and add it to your basket.' : 'Choose only what you like. Optional choices can be left blank.' }}</p>
                </div>
                <a href="{{ route('customer.menu') }}" class="ccx-continue-link">← Back to menu</a>
            </div>

            <div class="ccx-content-grid">
                <div class="ccx-choice-column">
                    @unless($isBeverage)
                    @if($hasSauceComponent)
                        <fieldset class="ccx-choice-panel">
                            <legend class="ccx-panel-legend"><span class="ccx-step">01</span> Choose your sauce <span class="ccx-optional">Choose one</span></legend>
                            <p class="ccx-panel-help">Pick the sauce you'd like with this order. Included in the base price.</p>
                            <div class="ccx-choice-list" id="cc-sauce-options">
                                @foreach(['Honey Mustard', 'Smoky BBQ', 'Spicy Garlic Mayo'] as $sauce)
                                    <label class="ccx-select-option">
                                        <input type="radio" name="sauce_choice" value="{{ $sauce }}" @checked(old('sauce_choice', 'Honey Mustard') === $sauce)>
                                        <span class="ccx-choice-name">{{ $sauce }}</span>
                                        <span class="ccx-choice-extra">Included</span>
                                    </label>
                                @endforeach
                            </div>
                            <p class="ccx-context-hint">Selecting “No Sauce” below will turn off the sauce choices.</p>
                        </fieldset>
                    @endif

                    <fieldset class="ccx-choice-panel">
                        <legend class="ccx-panel-legend"><span class="ccx-step">{{ $hasSauceComponent ? '02' : '01' }}</span> Add extras <span class="ccx-optional">Optional</span></legend>
                        <p class="ccx-panel-help">Enter how many extras you want <strong>per meal</strong>. Use 0 for none (maximum 10 of each).</p>
                        <div class="ccx-choice-list">
                            @forelse($product->addOns as $addon)
                                <div class="ccx-select-option ccx-extra-row">
                                    <label class="ccx-choice-name" for="ccx-extra-{{ $addon->addon_id }}">{{ $addon->name }}</label>
                                    <span class="ccx-choice-price">+₱{{ number_format((float) $addon->price, 2) }} each</span>
                                    <div class="ccx-extra-count">
                                        <label class="ccx-visually-hidden" for="ccx-extra-{{ $addon->addon_id }}">{{ $addon->name }} quantity per meal</label>
                                        <input type="number" id="ccx-extra-{{ $addon->addon_id }}" name="add_ons[{{ $addon->addon_id }}]"
                                            min="0" max="10" step="1" inputmode="numeric" required
                                            data-addon-price="{{ $addon->price }}" data-addon-name="{{ $addon->name }}"
                                            value="{{ max(0, min(10, (int) ($selectedAddons[$addon->addon_id] ?? 0))) }}">
                                        <small>per meal</small>
                                    </div>
                                </div>
                            @empty
                                <p class="ccx-empty">No food extras available for this product.</p>
                            @endforelse
                        </div>
                    </fieldset>

                    <fieldset class="ccx-choice-panel">
                        <legend class="ccx-panel-legend"><span class="ccx-step">{{ $hasSauceComponent ? '03' : '02' }}</span> Remove ingredients <span class="ccx-optional">Optional</span></legend>
                        <p class="ccx-panel-help">Don't want something in your order? Select it to leave it out.</p>
                        <div class="ccx-choice-list">
                            @forelse($product->components as $component)
                                @php $isSauce = str_contains(strtolower($component->component_name), 'sauce'); @endphp
                                <label class="ccx-select-option">
                                    <input type="checkbox" name="remove_components[]" value="{{ $component->component_id }}" @checked(in_array((int) $component->component_id, $selectedRemovals, true)) @if($isSauce) data-remove-sauce @endif>
                                    <span class="ccx-choice-name">No {{ $component->component_name }}</span>
                                    <span class="ccx-choice-extra">No price change</span>
                                </label>
                            @empty
                                <p class="ccx-empty">No removable ingredients for this product.</p>
                            @endforelse
                        </div>
                    </fieldset>

                    <fieldset class="ccx-choice-panel ccx-drink-panel" aria-describedby="ccx-drink-help">
                        <legend class="ccx-panel-legend"><span class="ccx-step">{{ $hasSauceComponent ? '04' : '03' }}</span> 🥤 Complete your meal <span class="ccx-optional">Optional</span></legend>
                        <p class="ccx-panel-help" id="ccx-drink-help">A drink is a separate item, not a food extra. If selected, we'll add one drink for each meal in this selection.</p>
                        <div class="ccx-choice-list ccx-drink-options">
                            <label class="ccx-select-option">
                                <input type="radio" name="beverage_id" value="" data-beverage-price="0" data-beverage-name="No drink" @checked(!old('beverage_id'))>
                                <span class="ccx-choice-name">No drink</span>
                                <span class="ccx-choice-extra">Optional</span>
                            </label>
                            @foreach($beverages as $drink)
                                <label class="ccx-select-option">
                                    <input type="radio" name="beverage_id" value="{{ $drink->product_id }}"
                                        data-beverage-price="{{ $drink->base_price }}"
                                        data-beverage-name="{{ $drink->name }}"
                                        @checked((int) old('beverage_id', 0) === (int) $drink->product_id)>
                                    <span class="ccx-choice-name">{{ $drink->name }}</span>
                                    <strong class="ccx-choice-price">+₱{{ number_format((float) $drink->base_price, 2) }} / meal</strong>
                                </label>
                            @endforeach
                        </div>
                        @if($beverages->isEmpty())
                            <p class="ccx-context-hint">No drinks currently available. You can still order food by itself.</p>
                        @endif
                        <a class="ccx-drink-browse" href="{{ route('customer.menu', ['section' => 'beverages']) }}#browse-menu">Browse the full beverages menu →</a>
                    </fieldset>
                    @endunless

                    <section class="ccx-choice-panel" aria-labelledby="ccx-notes-label">
                        <h3 class="ccx-panel-heading" id="ccx-notes-label"><span class="ccx-step">{{ $isBeverage ? '01' : ($hasSauceComponent ? '05' : '04') }}</span> Special instructions <span class="ccx-optional">Optional</span></h3>
                        <p class="ccx-panel-help">Tell us any preparation preferences. We'll pass the note along with your order.</p>
                        <label for="ccx-notes" class="ccx-visually-hidden">Special instructions</label>
                        <textarea class="ccx-notes" id="ccx-notes" name="item_notes" maxlength="500" rows="3" placeholder="Example: Extra crispy, sauce on the side...">{{ old('item_notes') }}</textarea>
                        <p class="ccx-context-hint">Maximum 500 characters.</p>
                    </section>
                </div>

                <aside class="ccx-summary-column" aria-label="Your order summary">
                    <div class="ccx-summary-card">
                        <div class="ccx-summary-heading">
                            <span class="ccx-eyebrow">REVIEW YOUR SELECTION</span>
                            <h2>Your order</h2>
                            <p>Review the price and quantity before adding to cart.</p>
                        </div>

                        <div class="ccx-order-name">{{ $product->name }}</div>
                        <dl class="ccx-price-rows">
                            <div><dt>Base price</dt><dd>₱{{ number_format((float) $product->base_price, 2) }}</dd></div>
                            @unless($isBeverage)
                                <div><dt>Food extras</dt><dd id="ccx-extras-price">₱{{ number_format($initialExtras, 2) }}</dd></div>
                            @endunless
                            @unless($isBeverage)
                                <div><dt>Drink per meal</dt><dd id="ccx-drink-price">₱{{ number_format($drinkPrice, 2) }}</dd></div>
                            @endunless
                            @if($hasSauceComponent)
                                <div><dt>Sauce</dt><dd id="ccx-sauce-selected">{{ old('sauce_choice', 'Honey Mustard') }}</dd></div>
                            @endif
                        </dl>
                        @unless($isBeverage)
                            <p id="ccx-extras-description" class="ccx-extra-description" aria-live="polite">
                                {{ $initialExtras > 0 ? 'Selected extras included in the total.' : 'No extras selected.' }}
                            </p>
                        @endunless
                        <div class="ccx-summary-divider"></div>
                        <div class="ccx-unit-total"><span>{{ $isBeverage ? 'Item price' : 'Meal price' }}</span><strong id="ccx-unit-price">₱{{ number_format($initialUnit, 2) }}</strong></div>
                        @unless($isBeverage)
                            <p class="ccx-drink-summary" id="ccx-drink-selected" aria-live="polite">{{ $selectedDrink ? 'With ' . $selectedDrink->name . ' (separate cart item)' : 'No drink selected' }}</p>
                        @endunless

                        <div class="ccx-quantity-block">
                            <label for="item-quantity">Quantity</label>
                            <div class="ccx-quantity-group">
                                <button type="button" data-quantity-step="-1" aria-label="Decrease quantity">−</button>
                                <input id="item-quantity" type="number" name="quantity" min="1" max="20" step="1" value="{{ $initialQty }}" required inputmode="numeric" aria-describedby="ccx-qty-hint">
                                <button type="button" data-quantity-step="1" aria-label="Increase quantity">+</button>
                            </div>
                            <small id="ccx-qty-hint">1–20 items per selection</small>
                        </div>

                        <div class="ccx-final-total" aria-live="polite"><span>Total</span><strong id="item-total">₱{{ number_format($initialTotal, 2) }}</strong></div>
                        <button type="submit" class="ccx-add-to-cart">Add to cart <span aria-hidden="true">→</span><span class="ccx-visually-hidden">for <span id="cart-button-total">₱{{ number_format($initialTotal, 2) }}</span></span></button>
                        <p class="ccx-summary-notice">You can review your basket before checkout. Adding to cart does not place an order.</p>
                    </div>
                </aside>
            </div>
        </form>
    </div>
</main>
@endsection
