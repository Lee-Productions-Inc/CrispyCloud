@props(['product'])

@php
$photoPath = 'images/products/' . \Illuminate\Support\Str::slug($product->name) . '.png';
$hasPhoto = is_file(public_path($photoPath));
$productUrl = route('customer.customize', $product->product_id);
$isBeverage = \App\Support\MenuSections::isBeverageCategory($product->category?->name);
@endphp

<article class="ccm-card relative">
    <a href="{{ $productUrl }}" class="ccm-card-photo" aria-label="View {{ $product->name }}">
        @if($hasPhoto)
        <img src="{{ asset($photoPath) }}" alt="{{ $product->name }}" loading="lazy">
        @else
        <span class="ccm-photo-placeholder" aria-hidden="true">
            @if($isBeverage)
            <span class="ccm-photo-drink-icon" aria-hidden="true">🥤</span>
            @else
            <svg viewBox="0 0 80 80" fill="none">
                <circle cx="40" cy="40" r="29" stroke="currentColor" stroke-width="2" />
                <path d="M22 32h36M24 48h32" stroke="currentColor" stroke-width="2" stroke-linecap="round" opacity=".45" />
                <path d="M34 30c4-6 10-6 14 0l6 10-8 11H32l-6-11 8-10Z" fill="currentColor" opacity=".14" />
            </svg>
            @endif
        </span>
        @endif
    </a>
    <div class="ccm-card-body">
        <div class="ccm-card-heading">
            <h4><a href="{{ $productUrl }}">{{ $product->name }}</a></h4>
        </div>
        <p class="ccm-card-description">{{ $product->description ?: 'Explore this Chick\'s Pops favorite and customize it your way.' }}</p>
        <div class="ccm-card-bottom">
            <div class="ccm-card-price"><small>{{ $isBeverage ? 'Price' : 'Starts at' }}</small><strong>₱{{ number_format((float) $product->base_price, 2) }}</strong></div>
            <a class="ccm-card-cta" href="{{ $productUrl }}">{{ $isBeverage ? 'Choose drink' : 'Customize' }} <svg aria-hidden="true" viewBox="0 0 24 24" fill="none">
                    <path d="M5 12h14m-6-6 6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg></a>
        </div>
    </div>
</article>