<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#c8102e">
    <title>@yield('title', 'Admin') • CrispyCloud</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-products.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-orders.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-inventory.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-reports.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-pos.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-ui-refine.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-pos-refine.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin-catalog-polish.css') }}">
</head>

<body class="admin-app">
    @php
    $admin = Auth::guard('admin')->user();
    $logoPath = public_path('images/logo.png');
    @endphp
    <a class="a-skip" href="#admin-main">Skip to content</a>
    <div class="a-shell">
        <aside class="a-sidebar" aria-label="Administrator navigation">
            <a class="a-brand" href="{{ route('admin.dashboard') }}" aria-label="CrispyCloud admin dashboard">
                @if (file_exists($logoPath))
                <img src="{{ asset('images/logo.png') }}" width="62" height="62" alt="Chick's Pops logo">
                @else
                <span class="a-logo-fallback" aria-hidden="true">CP</span>
                @endif
                <span><strong>CHICK'S POPS</strong><small>Inventory & Sales</small></span>
            </a>
            <nav class="a-nav a-refined-nav" aria-label="Main navigation">
                <div class="a-nav-group">
                    <div class="a-nav-label">OVERVIEW</div>
                    <a href="{{ route('admin.dashboard') }}" class="a-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" @if(request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▦</span> Dashboard
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">SALES & ORDERS</div>
                    <a href="{{ route('admin.pos.index') }}" class="a-nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }}" @if(request()->routeIs('admin.pos.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▣</span> Walk-in POS
                    </a>
                    <a href="{{ route('admin.orders.index') }}" class="a-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" @if(request()->routeIs('admin.orders.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▤</span> Online Orders
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">MENU & STOCK</div>
                    <a href="{{ route('admin.products.index') }}" class="a-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" @if(request()->routeIs('admin.products.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▧</span> Products
                    </a>
                    <a href="{{ route('admin.catalog.index') }}" class="a-nav-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}" @if(request()->routeIs('admin.catalog.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">⊞</span> Categories & Extras
                    </a>
                    <a href="{{ route('admin.inventory.index') }}" class="a-nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" @if(request()->routeIs('admin.inventory.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▥</span> Inventory
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">INSIGHTS</div>
                    <a href="{{ route('admin.reports.index') }}" class="a-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" @if(request()->routeIs('admin.reports.*')) aria-current="page" @endif>
                        <span class="a-nav-symbol" aria-hidden="true">▤</span> Sales & Reports
                    </a>
                </div>
            </nav>
            <div class="a-side-bottom">
                <a class="a-preview-link" href="{{ route('customer.menu') }}" target="_blank" rel="noopener noreferrer">Preview customer menu <span aria-hidden="true">↗</span></a>
                <div class="a-side-account"><strong>{{ $admin?->full_name ?? 'Administrator' }}</strong><span>Administrator</span></div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="a-logout" type="submit">Sign out <span aria-hidden="true">↗</span></button>
                </form>
            </div>
        </aside>

        <div class="a-content-area">
            <header class="a-topbar">
                <div class="a-topbar-brand"><strong>CHICK'S POPS · ADMIN</strong><span>@if(request()->routeIs('admin.pos.*')) Walk-in counter sales @elseif(request()->routeIs('admin.orders.*')) Online order management @elseif(request()->routeIs('admin.products.*', 'admin.catalog.*')) Menu management @elseif(request()->routeIs('admin.inventory.*')) Inventory management @elseif(request()->routeIs('admin.reports.*')) Sales reporting @else Business overview @endif</span></div>
                <div class="a-topbar-right"><span class="a-live-dot" aria-hidden="true"></span> Local admin <span class="a-topbar-divider" aria-hidden="true"></span><strong>{{ $admin?->full_name ?? 'Administrator' }}</strong></div>
            </header>
            <details class="a-mobile-menu">
                <summary>☰ &nbsp; Navigate admin pages</summary>
                <nav aria-label="Mobile admin navigation">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <a href="{{ route('admin.pos.index') }}">Walk-in POS</a>
                    <a href="{{ route('admin.orders.index') }}">Online Orders</a>
                    <a href="{{ route('admin.products.index') }}">Products</a>
                    <a href="{{ route('admin.catalog.index') }}">Categories & Extras</a>
                    <a href="{{ route('admin.inventory.index') }}">Inventory</a>
                    <a href="{{ route('admin.reports.index') }}">Sales &amp; Reports</a>
                    <a href="{{ route('customer.menu') }}" target="_blank" rel="noopener noreferrer">Preview customer menu ↗</a>
                    <form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit">Sign out</button></form>
                </nav>
            </details>
            <main class="a-main" id="admin-main">
                @if (session('success'))
                <div class="a-alert a-alert-success" role="status">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                <div class="a-alert a-alert-error" role="alert">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>