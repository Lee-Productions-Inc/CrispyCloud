@php
    $customerLoggedIn = auth('web')->check();
    $menuActive = request()->routeIs('customer.menu', 'customer.menu.list', 'customer.customize');
    $ordersActive = request()->routeIs('customer.orders.*');
    $cartActive = request()->routeIs('customer.cart.*', 'customer.checkout.*');
    $accountActive = request()->routeIs('customer.profile.*', 'customer.addresses.*');
    $cartCount = collect(session('crispycloud_cart', []))->sum(fn ($line) => (int) ($line['quantity'] ?? 0));
    $logoFile = is_file(public_path('images/Chicks_Pops_Logo.png'))
        ? 'images/Chicks_Pops_Logo.png'
        : (is_file(public_path('images/logo.png')) ? 'images/logo.png' : null);
@endphp

<header class="ccn-header sticky top-0">
    <div class="ccn-header-inner grid items-center">
        <a class="ccn-brand inline-flex items-center" href="{{ route('customer.menu') }}" aria-label="Chick's Pops - Menu">
            @if($logoFile)
                <img class="ccn-brand-logo" src="{{ asset($logoFile) }}" alt="" width="44" height="44">
            @else
                <span class="ccn-brand-monogram" aria-hidden="true">CP</span>
            @endif
            <span class="ccn-brand-words"><strong>CHICK'S POPS</strong><small>Made fresh. Made crispy.</small></span>
        </a>

        <nav class="ccn-desktop-nav" aria-label="Main customer navigation">
            <a class="ccn-nav-link {{ $menuActive ? 'ccn-active' : '' }}" href="{{ route('customer.menu') }}" @if($menuActive) aria-current="page" @endif>Menu</a>
            @if($customerLoggedIn)
                <a class="ccn-nav-link {{ $ordersActive ? 'ccn-active' : '' }}" href="{{ route('customer.orders.index') }}" @if($ordersActive) aria-current="page" @endif>My Orders</a>
            @endif
        </nav>

        <div class="ccn-header-actions">
            <a class="ccn-cart-action {{ $cartActive ? 'ccn-active' : '' }} inline-flex items-center" href="{{ route('customer.cart.index') }}" @if($cartActive) aria-current="page" @endif>
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1.5 12h-11L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>
                <span>Cart</span>
                @if($cartCount > 0)<span class="ccn-cart-count" aria-label="{{ $cartCount }} items">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif
            </a>
            @if($customerLoggedIn)
                <details class="ccn-account-dropdown">
                    <summary class="ccn-account-trigger {{ $accountActive ? 'ccn-active' : '' }}" @if($accountActive) aria-current="page" @endif>
                        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
                        <span>Account</span>
                        <svg class="ccn-chevron" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="m7 10 5 5 5-5"/></svg>
                    </summary>
                    <div class="ccn-account-panel">
                        <span class="ccn-account-name">{{ auth('web')->user()->full_name }}</span>
                        <a href="{{ route('customer.profile.index') }}">My Profile & Addresses</a>
                        <a href="{{ route('customer.orders.index') }}">Order History</a>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit">Sign Out</button>
                        </form>
                    </div>
                </details>
            @else
                <a class="ccn-sign-in" href="{{ route('login') }}">Sign In</a>
            @endif
        </div>
    </div>
</header>

<nav class="ccn-mobile-nav" aria-label="Mobile customer navigation">
    <a class="{{ $menuActive ? 'ccn-active' : '' }}" href="{{ route('customer.menu') }}" @if($menuActive) aria-current="page" @endif>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M4 4v7m3-7v7M4 8h3m-1.5 3v9M16 4v16m0-16c3 1 4 4 4 7h-4"/></svg>
        <span>Menu</span>
    </a>
    <a class="{{ $ordersActive ? 'ccn-active' : '' }}" href="{{ $customerLoggedIn ? route('customer.orders.index') : route('login') }}" @if($ordersActive) aria-current="page" @endif>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M7 3h10l2 2v16l-3-2-4 2-4-2-3 2V5l2-2Z"/><path d="M9 9h6M9 13h6"/></svg>
        <span>Orders</span>
    </a>
    <a class="{{ $cartActive ? 'ccn-active' : '' }}" href="{{ route('customer.cart.index') }}" @if($cartActive) aria-current="page" @endif>
        <span class="ccn-mobile-cart-icon"><svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 8h14l-1.5 12h-11L5 8Z"/><path d="M9 9V6a3 3 0 0 1 6 0v3"/></svg>@if($cartCount > 0)<span class="ccn-mobile-count">{{ $cartCount > 99 ? '99+' : $cartCount }}</span>@endif</span>
        <span>Cart</span>
    </a>
    <a class="{{ $accountActive ? 'ccn-active' : '' }}" href="{{ $customerLoggedIn ? route('customer.profile.index') : route('login') }}" @if($accountActive) aria-current="page" @endif>
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/></svg>
        <span>{{ $customerLoggedIn ? 'Account' : 'Sign In' }}</span>
    </a>
</nav>
