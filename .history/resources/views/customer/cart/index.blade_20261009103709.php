@php
    $hasLogo = is_file(public_path('images/logo.png'));
    $formatPrice = fn (int $cents): string => '₱' . number_format($cents / 100, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your Basket | Chick's Pops</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/customer/customize.css', 'resources/css/customer/cart.css', 'resources/js/app.js'])
</head>
<body class="cc-product-page cc-cart-page">
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
        <div class="cc-header-search" aria-label="Search will be available with the menu page">Search for pops, sides...</div>
    </header>

    <main class="cc-cart-shell">
        <div class="cc-basket-main">
            <h1>YOUR BASKET</h1>
            @if(session('success'))
                <div class="cc-cart-feedback" role="status">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="cc-cart-error" role="alert">{{ $errors->first() }}</div>
            @endif

            @forelse($items as $lineId => $item)
                @php
                    $imageRelativePath = 'images/products/' . \Illuminate\Support\Str::slug($item['name']) . '.png';
                    $hasImage = is_file(public_path($imageRelativePath));
                    $lineTotalCents = $item['unit_price_cents'] * $item['quantity'];
                @endphp
                <article class="cc-basket-item">
                    <div class="cc-basket-photo">
                        @if($hasImage)
                            <img src="{{ asset($imageRelativePath) }}" alt="{{ $item['name'] }}">
                        @else
                            <span aria-hidden="true">🍗</span>
                        @endif
                    </div>

                    <div class="cc-basket-info">
                        <h2>{{ $item['name'] }}</h2>
                        @if($item['sauce_choice'])
                            <p>Sauce: {{ $item['sauce_choice'] }}</p>
                        @endif
                        @foreach($item['add_ons'] as $addon)
                            <p>+ {{ $addon['name'] }} ({{ $formatPrice($addon['price_cents']) }})</p>
                        @endforeach
                        @foreach($item['removals'] as $removal)
                            <p>– No {{ $removal['name'] }}</p>
                        @endforeach
                        @if($item['item_notes'])
                            <p>Note: {{ $item['item_notes'] }}</p>
                        @endif
                        <a class="cc-reorder-link" href="{{ route('customer.customize', $item['product_id']) }}">Customize another</a>
                    </div>

                    <div class="cc-basket-actions">
                        <form method="POST" action="{{ route('customer.cart.update', $lineId) }}" class="cc-basket-quantity-form">
                            @csrf
                            @method('PATCH')
                            <label for="quantity-{{ $lineId }}">Quantity</label>
                            <input id="quantity-{{ $lineId }}" name="quantity" type="number" min="1" max="20" value="{{ $item['quantity'] }}" required>
                            <button type="submit">Update</button>
                        </form>
                        <strong>{{ $formatPrice($lineTotalCents) }}</strong>
                        <form method="POST" action="{{ route('customer.cart.destroy', $lineId) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="cc-basket-remove" aria-label="Remove {{ $item['name'] }} from basket">Remove</button>
                        </form>
                    </div>
                </article>
            @empty
                <div class="cc-basket-empty">
                    <p>Your basket is empty.</p>
                    <p>Choose a product to start customizing your order.</p>
                </div>
            @endforelse
        </div>

        <aside class="cc-basket-summary" aria-label="Basket summary">
            <h2>ORDER SUMMARY</h2>
            <div class="cc-basket-total-row">
                <span>Subtotal</span>
                <strong>{{ $formatPrice($subtotalCents) }}</strong>
            </div>
            <div class="cc-basket-total-row">
                <span>Delivery fee</span>
                <span>Calculated at checkout</span>
            </div>
            <div class="cc-basket-divider"></div>
            <div class="cc-basket-grand-total">
                <span>SUBTOTAL</span>
                <strong>{{ $formatPrice($subtotalCents) }}</strong>
            </div>
            @if(count($items) > 0)
       <a href="{{ route('customer.checkout.index') }}" class="cc-cart-checkout-cta">PROCEED TO CHECKOUT</a>
     @endif

            <p class="cc-basket-next-note">The basket is kept in this browser's Laravel session. No payment or order has been submitted.</p>
        </aside>
    </main>
</body>
</html>
