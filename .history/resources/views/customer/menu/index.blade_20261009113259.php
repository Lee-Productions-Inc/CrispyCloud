@php
    $hasLogo = is_file(public_path('images/logo.png'));
    $heroPath = 'images/hero/chicks-pops-banner.png';
    $hasHero = is_file(public_path($heroPath));
    $menuUrl = fn (array $options = []) => route('customer.menu', array_filter($options, fn ($v) => $v !== null && $v !== ''));
    $menuFilter = ['q' => $search, 'category' => $categoryId, 'flavor' => $flavor];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Menu | Chick's Pops — CrispyCloud</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/css/customer/menu.css', 'resources/js/app.js'])
</head>
<body class="cc-menu-page">
    <header class="cc-menu-header">
        <a class="cc-menu-brand" href="{{ route('customer.menu') }}" aria-label="Chick's Pops home">
            @if($hasLogo)<img src="{{ asset('images/logo.png') }}" alt="Chick's Pops logo">@endif
            <span>CHICK'S<br>POPS</span>
        </a>
        <nav class="cc-menu-topnav" aria-label="Browse categories">
            <a href="{{ $menuUrl() }}" @if(!$categoryId) aria-current="page" @endif>ALL</a>
            @foreach($categories->take(4) as $category)
                <a href="{{ $menuUrl(['category' => $category->category_id]) }}" @if((int)$categoryId === (int)$category->category_id) aria-current="page" @endif>{{ strtoupper($category->name) }}</a>
            @endforeach
        </nav>
        <form action="{{ route('customer.menu') }}" method="GET" role="search" class="cc-menu-search">
            @if($categoryId)<input type="hidden" name="category" value="{{ $categoryId }}">@endif
            <label class="cc-sr-only" for="menu-search">Search menu</label>
            <input id="menu-search" type="search" name="q" value="{{ $search }}" maxlength="80" placeholder="Search for pops, sides...">
            <button type="submit" aria-label="Search">⌕</button>
        </form>
        <div class="cc-menu-actions">
            <a href="{{ route('customer.cart.index') }}" class="cc-menu-basket">Basket</a>
            @if(auth('web')->check())
                <a href="{{ route('customer.orders.index') }}">My Orders</a>
                <span class="cc-menu-welcome">Hi, {{ strtok(auth('web')->user()->full_name, ' ') }}!</span>
            @else
                <a href="{{ url('/login') }}">Sign In</a>
            @endif
        </div>
    </header>

    <main class="cc-menu-main">
        <section class="cc-menu-hero" aria-label="Chick's Pops special offer">
            <div class="cc-menu-hero-copy">
                <span class="cc-menu-deal-pill">CRISPY DEALS</span>
                <h1>GET THE <em>ULTIMATE</em> POP!</h1>
                <p>Experience the crunch like never before. 100% premium chicken, seasoned to perfection.</p>
                <a href="#menu-products" class="cc-menu-hero-cta">ORDER NOW <span aria-hidden="true">→</span></a>
            </div>
            <div class="cc-menu-hero-visual">
                @if($hasHero)
                    <img src="{{ asset($heroPath) }}" alt="Chick's Pops featured chicken meal">
                @else
                    <span role="img" aria-label="Chicken meal">🍗</span>
                @endif
            </div>
        </section>

        <section class="cc-menu-filters" aria-label="Flavor filters">
            @foreach([
                'all' => 'All Flavors',
                'spicy' => '🌶️ Spicy Pop',
                'cheesy' => '🧀 Cheesy Pop',
                'garlic' => '🌿 Garlic & Herb',
            ] as $value => $label)
                <a class="cc-menu-chip {{ $flavor === $value ? 'is-active' : '' }}" href="{{ $menuUrl(['q' => $search, 'category' => $categoryId, 'flavor' => $value === 'all' ? null : $value]) }}" @if($flavor === $value) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </section>

        <section id="menu-products" class="cc-menu-products" aria-labelledby="cc-menu-products-title">
            <div class="cc-menu-section-header">
                <div>
                    <h2 id="cc-menu-products-title">{{ $categoryId ? ($categories->firstWhere('category_id', (int)$categoryId)?->name ?? 'Menu') : 'Meals' }}</h2>
                    <p>{{ $products->total() }} available {{ $products->total() === 1 ? 'item' : 'items' }}</p>
                </div>
                @if($search || $categoryId || $flavor !== 'all')
                    <a href="{{ route('customer.menu') }}">Clear filters →</a>
                @endif
            </div>

            <div class="cc-menu-product-grid">
                @forelse($products as $product)
                    @php
                        $imagePath = 'images/products/' . \Illuminate\Support\Str::slug($product->name) . '.png';
                        $productImageAvailable = is_file(public_path($imagePath));
                    @endphp
                    <article class="cc-menu-product-card">
                        <a href="{{ route('customer.customize', $product->product_id) }}" class="cc-menu-product-picture" aria-label="Customize {{ $product->name }}">
                            @if($productImageAvailable)
                                <img src="{{ asset($imagePath) }}" loading="lazy" alt="{{ $product->name }}">
                            @else
                                <span role="img" aria-label="Food illustration">🍗</span>
                            @endif
                        </a>
                        <div class="cc-menu-product-details">
                            <h3>{{ $product->name }}</h3>
                            <p>{{ $product->description ?: 'Freshly prepared Chicken Pops.' }}</p>
                            <div class="cc-menu-product-foot">
                                <strong>₱{{ number_format((float) $product->base_price, 2) }}</strong>
                                <a href="{{ route('customer.customize', $product->product_id) }}" class="cc-menu-add-button" aria-label="Customize {{ $product->name }}">+</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="cc-menu-empty">
                        <h3>No products found</h3>
                        <p>Try another search or category. Products must be marked available to appear here.</p>
                        <a href="{{ route('customer.menu') }}">View all products</a>
                    </div>
                @endforelse
            </div>
            @if($products->hasPages())
                <nav aria-label="Menu pages" class="cc-menu-pagination">
                    {{ $products->links() }}
                </nav>
            @endif
        </section>
    </main>
</body>
</html>
