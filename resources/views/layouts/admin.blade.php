<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#c8102e">
    <title>@yield('title', 'Admin') • CrispyCloud</title>
    @vite('resources/css/cc-admin.css')
    <script src="{{ asset('js/crispycloud-confirm-modal.js') }}" defer></script>
</head>

<body class="admin-app">
    @php
        $admin = Auth::guard('admin')->user();
        $logoPath = public_path('images/Chicks_Pops_Logo.png');
    @endphp
    <a class="a-skip" href="#admin-main">Skip to content</a>
    <div class="min-h-screen a-shell">
        <aside class="a-sidebar shrink-0" aria-label="Administrator navigation">
            <a class="a-brand" href="{{ route('admin.dashboard') }}" aria-label="CrispyCloud admin dashboard">
                @if (file_exists($logoPath))
                    <img src="{{ asset('images/Chicks_Pops_Logo.png') }}" width="62" height="62"
                        alt="Chick's Pops logo">
                @else
                    <span class="a-logo-fallback" aria-hidden="true">CP</span>
                @endif
                <span><strong>CHICK'S POPS</strong><small>Inventory & Sales</small></span>
            </a>
            <nav class="a-nav a-refined-nav" aria-label="Main navigation">
                <div class="a-nav-group">
                    <div class="a-nav-label">OVERVIEW</div>
                    <a href="{{ route('admin.dashboard') }}"
                        class="a-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7" rx="1" />
                                <rect x="14" y="3" width="7" height="7" rx="1" />
                                <rect x="3" y="14" width="7" height="7" rx="1" />
                                <rect x="14" y="14" width="7" height="7" rx="1" />
                            </svg></span> Dashboard
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">SALES & ORDERS</div>
                    <a href="{{ route('admin.pos.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.pos.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.pos.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="4" width="18" height="16" rx="2" />
                                <path d="M7 8h10M7 12h10M7 16h6" />
                            </svg></span> Walk-in POS
                    </a>
                    <a href="{{ route('admin.orders.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.orders.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 3h10l3 3v15H8z" />
                                <path d="M18 3v4h3M11 11h7M11 15h7" />
                            </svg></span> Online Orders
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">MENU & STOCK</div>
                    <a href="{{ route('admin.products.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.products.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" />
                                <path d="M3 9h18M9 9v12" />
                            </svg></span> Products
                    </a>
                    <a href="{{ route('admin.catalog.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.catalog.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.catalog.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="3" />
                                <path d="M12 7v10M7 12h10" />
                            </svg></span> Categories & Extras
                    </a>
                    <a href="{{ route('admin.inventory.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.inventory.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.inventory.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M3 7l9-4 9 4v12l-9 3-9-3zM3 7l9 4 9-4M12 11v11" />
                            </svg></span> Inventory
                    </a>
                </div>
                <div class="a-nav-group">
                    <div class="a-nav-label">INSIGHTS</div>
                    <a href="{{ route('admin.reports.index') }}"
                        class="a-nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }} flex items-center"
                        @if (request()->routeIs('admin.reports.*')) aria-current="page" @endif>
                        <span class="inline-flex justify-center items-center a-nav-symbol shrink-0"
                            aria-hidden="true"><svg width="20" height="20" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"
                                stroke-linejoin="round">
                                <path d="M8 3h10l3 3v15H8z" />
                                <path d="M18 3v4h3M11 11h7M11 15h7" />
                            </svg></span> Sales & Reports
                    </a>
                </div>
            </nav>
            <div class="a-side-bottom">
                <a class="a-preview-link" href="{{ route('customer.menu') }}" target="_blank"
                    rel="noopener noreferrer">Preview customer menu <span aria-hidden="true">↗</span></a>
                <div class="a-side-account">
                    <strong>{{ $admin?->full_name ?? 'Administrator' }}</strong><span>Administrator</span></div>
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf
                    <button class="a-logout" type="submit">Sign out <span aria-hidden="true">↗</span></button>
                </form>
            </div>
        </aside>

        <div class="min-w-0 a-content-area">
            <header class="a-topbar">
                <div class="a-topbar-brand"><strong>CHICK'S POPS · ADMIN</strong><span>
                        @if (request()->routeIs('admin.pos.*'))
                            Walk-in counter sales
                        @elseif(request()->routeIs('admin.orders.*'))
                            Online order management
                        @elseif(request()->routeIs('admin.products.*', 'admin.catalog.*'))
                            Menu management
                        @elseif(request()->routeIs('admin.inventory.*'))
                            Inventory management
                        @elseif(request()->routeIs('admin.reports.*'))
                            Sales reporting
                        @else
                            Business overview
                        @endif
                    </span></div>
                <div class="a-topbar-right"><span class="a-live-dot" aria-hidden="true"></span> Local admin <span
                        class="a-topbar-divider"
                        aria-hidden="true"></span><strong>{{ $admin?->full_name ?? 'Administrator' }}</strong></div>
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
                    <a href="{{ route('customer.menu') }}" target="_blank" rel="noopener noreferrer">Preview
                        customer menu ↗</a>
                    <form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit">Sign
                            out</button></form>
                </nav>
            </details>
            <main class="min-w-0 a-main" id="admin-main">
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
