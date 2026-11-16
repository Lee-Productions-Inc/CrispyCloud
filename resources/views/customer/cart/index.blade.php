@extends('layouts.customer')

@section('title', "Your Cart | Chick's Pops")
@section('body_class', 'cc-product-page cc-cart-page cc-commerce-page')

@section('page_assets')
    @vite('resources/js/app.js')
@endsection

@php
    $price = fn (int $cents): string => '₱' . number_format($cents / 100, 2);
    $totalQuantity = collect($items)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
@endphp

@section('content')
<main id="main-content" class="ccc-main">
    <div class="ccc-container mx-auto">
        <nav class="ccc-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.menu') }}">Menu</a><span aria-hidden="true">/</span><span aria-current="page">Cart</span>
        </nav>

        <div class="ccc-page-heading">
            <div>
                <span class="ccc-eyebrow">YOUR ORDER</span>
                <h1>Your cart<span class="ccc-heading-period">.</span></h1>
                <p>Review your selections, update quantities, and continue when you're ready.</p>
            </div>
            <a class="ccc-secondary-link" href="{{ route('customer.menu') }}">
                <span aria-hidden="true">←</span> Continue shopping
            </a>
        </div>

        <ol class="ccc-progress flex" aria-label="Checkout progress">
            <li class="ccc-progress-current" aria-current="step"><span>1</span> Cart</li>
            <li><span>2</span> Delivery &amp; payment</li>
            <li><span>3</span> Confirmation</li>
        </ol>

        @if(session('success'))
            <div class="ccc-notice ccc-notice-success" role="status">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="ccc-notice ccc-notice-error" role="alert">{{ session('error') }}</div>
        @endif
        @if($errors->any())
            <div class="ccc-notice ccc-notice-error" role="alert">
                <strong>Please review your cart.</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if(count($items) > 0)
        <div class="ccc-two-column grid">
            <section class="ccc-card ccc-cart-list rounded-2xl" aria-labelledby="ccc-items-title">
                <div class="ccc-card-header">
                    <h2 id="ccc-items-title">Items in your cart</h2>
                    <span class="ccc-count-pill">{{ $totalQuantity }} {{ $totalQuantity === 1 ? 'item' : 'items' }}</span>
                </div>

                @foreach($items as $lineId => $item)
                    @php
                        $imagePath = 'images/products/' . \Illuminate\Support\Str::slug($item['name']) . '.png';
                        $hasImage = is_file(public_path($imagePath));
                        $lineTotalCents = (int) $item['unit_price_cents'] * (int) $item['quantity'];
                    @endphp
                    <article class="ccc-cart-row" aria-labelledby="cart-product-{{ $loop->iteration }}">
                        <div class="ccc-item-photo">
                            @if($hasImage)
                                <img src="{{ asset($imagePath) }}" alt="{{ $item['name'] }}" loading="lazy">
                            @else
                                <span class="ccc-photo-fallback" aria-hidden="true">🍗</span>
                            @endif
                        </div>

                        <div class="ccc-item-details">
                            <h3 id="cart-product-{{ $loop->iteration }}">{{ $item['name'] }}</h3>
                            <p class="ccc-unit-price">{{ $price((int) $item['unit_price_cents']) }} per item</p>

                            @if(!empty($item['sauce_choice']) || !empty($item['add_ons']) || !empty($item['removals']) || !empty($item['item_notes']))
                            <dl class="ccc-customizations">
                                @if(!empty($item['sauce_choice']))
                                    <div><dt>Sauce</dt><dd>{{ $item['sauce_choice'] }}</dd></div>
                                @endif
                                @if(!empty($item['add_ons']))
                                    <div><dt>Extras</dt><dd>
                                        @foreach($item['add_ons'] as $addon)
                                            <span>{{ (int) ($addon['quantity'] ?? 1) }} × {{ $addon['name'] }} per item (+{{ $price((int) $addon['price_cents'] * (int) ($addon['quantity'] ?? 1)) }} per item){{ !$loop->last ? ',' : '' }}</span>
                                        @endforeach
                                    </dd></div>
                                @endif
                                @if(!empty($item['removals']))
                                    <div><dt>Removed</dt><dd>
                                        @foreach($item['removals'] as $removal)
                                            <span>{{ $removal['name'] }}{{ !$loop->last ? ',' : '' }}</span>
                                        @endforeach
                                    </dd></div>
                                @endif
                                @if(!empty($item['item_notes']))
                                    <div><dt>Note</dt><dd>{{ $item['item_notes'] }}</dd></div>
                                @endif
                            </dl>
                            @endif
                            <a href="{{ route('customer.customize', $item['product_id']) }}" class="ccc-text-link">Customize another</a>
                        </div>

                        <div class="ccc-item-actions">
                            <strong class="ccc-line-total">{{ $price($lineTotalCents) }}</strong>
                            <form action="{{ route('customer.cart.update', $lineId) }}" method="POST" class="ccc-qty-form">
                                @csrf
                                @method('PATCH')
                                <label for="cart-quantity-{{ $loop->iteration }}">Quantity</label>
                                <div class="ccc-qty-controls">
                                    <input id="cart-quantity-{{ $loop->iteration }}" type="number" name="quantity" value="{{ $item['quantity'] }}" min="1" max="20" required inputmode="numeric">
                                    <button type="submit" class="ccc-small-button">Update</button>
                                </div>
                            </form>
                            <form action="{{ route('customer.cart.destroy', $lineId) }}" method="POST">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ccc-remove-button" aria-label="Remove {{ $item['name'] }} from cart">Remove item</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </section>

            <aside class="ccc-card ccc-summary-card rounded-2xl" aria-labelledby="ccc-summary-title">
                <span class="ccc-eyebrow">BEFORE CHECKOUT</span>
                <h2 id="ccc-summary-title">Order summary</h2>
                <div class="ccc-summary-rows">
                    <div><span>Subtotal ({{ $totalQuantity }} {{ $totalQuantity === 1 ? 'item' : 'items' }})</span><strong>{{ $price($subtotalCents) }}</strong></div>
                    <div><span>Delivery fee</span><span>Shown at checkout</span></div>
                </div>
                <div class="ccc-summary-total"><span>Subtotal</span><strong>{{ $price($subtotalCents) }}</strong></div>
                <a href="{{ route('customer.checkout.start') }}" class="ccc-primary-button">Proceed to checkout <span aria-hidden="true">→</span></a>
            </aside>
        </div>
        @else
            <section class="ccc-empty-state" aria-labelledby="ccc-empty-title">
                <span class="ccc-empty-icon" aria-hidden="true">🛍️</span>
                <h2 id="ccc-empty-title">Your cart is empty</h2>
                <p>Your next favorite chicken pops are waiting. Explore the menu to get started.</p>
                <a href="{{ route('customer.menu') }}" class="ccc-primary-button">Explore the menu <span aria-hidden="true">→</span></a>
            </section>
        @endif
    </div>
</main>
@endsection
