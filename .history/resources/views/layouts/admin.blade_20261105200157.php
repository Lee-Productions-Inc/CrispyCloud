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
            <div class="a-nav-label">WORKSPACE</div>
            <nav class="a-nav" aria-label="Main navigation">
                <a href="{{ route('admin.dashboard') }}" class="a-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" {{ request()->routeIs('admin.dashboard') ? 'aria-current=page' : '' }}>
                    <span class="a-nav-symbol" aria-hidden="true">▦</span> Dashboard
                </a>
                <a href="{{ route('admin.orders.index') }}" class="a-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}" {{ request()->routeIs('admin.orders.*') ? 'aria-current=page' : '' }}>
                    <span class="a-nav-symbol" aria-hidden="true">▤</span> Orders
                </a>
                <a href="{{ route('admin.products.index') }}" class="a-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}" {{ request()->routeIs('admin.products.*') ? 'aria-current=page' : '' }}>
                    <span class="a-nav-symbol" aria-hidden="true">▧</span> Products
                </a>
                <a href="{{ route('admin.catalog.index') }}" class="a-nav-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }}" {{ request()->routeIs('admin.catalog.*') ? 'aria-current=page' : '' }}>
                    <span class="a-nav-symbol" aria-hidden="true">⊞</span> Categories & Extras
                </a>
                <a href="{{ route('admin.inventory.index') }}" class="a-nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }}" {{ request()->routeIs('admin.inventory.*') ? 'aria-current=page' : '' }}><span class="a-nav-symbol" aria-hidden="true">▥</span> Inventory</a>
                <a href="{{ route('admin.reports.index') }}" class="a-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}" {{ request()->routeIs('admin.reports.*') ? 'aria-current=page' : '' }}><span class="a-nav-symbol" aria-hidden="true">▤</span> Sales &amp; Reports</a>
            </nav>
            <div class="a-side-bottom">
                <div class="a-side-account"><strong>{{ $admin?->full_name ?? 'Administrator' }}</strong><span>Administrator</span></div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="a-logout" type="submit">Sign out <span aria-hidden="true">↗</span></button>
                </form>
            </div>
        </aside>

        <div class="a-content-area">
            <header class="a-topbar">
                <div class="a-topbar-brand"><strong>CRISPYCLOUD</strong><span>Admin workspace</span></div>
                <div class="a-topbar-right"><span class="a-live-dot" aria-hidden="true"></span> Local admin <span class="a-topbar-divider" aria-hidden="true"></span><strong>{{ $admin?->full_name ?? 'Administrator' }}</strong></div>
            </header>
            <details class="a-mobile-menu">
                <summary>☰ &nbsp; Navigate admin pages</summary>
                <nav aria-label="Mobile admin navigation">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <a href="{{ route('admin.orders.index') }}">Orders</a>
                    <a href="{{ route('admin.products.index') }}">Products</a>
                    <a href="{{ route('admin.catalog.index') }}">Categories & Extras</a>
                    <a href="{{ route('admin.inventory.index') }}">Inventory</a>
                    <a href="{{ route('admin.reports.index') }}">Sales &amp; Reports</a>
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