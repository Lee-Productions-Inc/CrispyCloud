@extends('layouts.customer')

@section('title', "My Orders | Chick's Pops")
@section('body_class', 'cc-orderflow-page')

@section('page_assets')
    
@endsection

@php
    $money = fn ($amount): string => '₱' . number_format((float) $amount, 2);
    $statusTones = [
        'Preparing' => 'preparing',
        'Delivered' => 'delivered',
        'Cancelled' => 'cancelled',
    ];
    $tabLabels = ['all' => 'All orders', 'active' => 'Active', 'past' => 'Past orders'];
@endphp

@section('content')
<main id="main-content" class="cco-main min-w-0">
    <div class="cco-container mx-auto">
        <nav class="cco-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.menu') }}">Menu</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">My Orders</span>
        </nav>

        <header class="cco-page-intro">
            <div>
                <span class="cco-eyebrow">YOUR CHICK'S POPS JOURNEY</span>
                <h1>My orders<span class="cco-heading-dot">.</span></h1>
                <p>Review what you've ordered and stay updated as your food is prepared.</p>
            </div>
            <a class="cco-text-action" href="{{ route('customer.menu') }}">Browse menu <span aria-hidden="true">↗</span></a>
        </header>

        @if(session('order_cancelled'))
            <div class="cco-notice cco-notice--success" role="status">{{ session('order_cancelled') }}</div>
        @endif
        @error('cancel')
            <div class="cco-notice cco-notice--danger" role="alert">{{ $message }}</div>
        @enderror
        @if(session('reorder_error'))
            <div class="cco-notice cco-notice--danger" role="alert">{{ session('reorder_error') }}</div>
        @endif

        <div class="cco-list-toolbar">
            <nav class="cco-filter-tabs" aria-label="Filter your orders">
                @foreach($tabLabels as $tab => $label)
                    <a href="{{ route('customer.orders.index', ['filter' => $tab]) }}"
                       class="cco-filter-tab {{ $filter === $tab ? 'is-active' : '' }}"
                       @if($filter === $tab) aria-current="page" @endif>{{ $label }}</a>
                @endforeach
            </nav>
            <span class="cco-result-count">{{ $orders->total() }} {{ $orders->total() === 1 ? 'order' : 'orders' }}</span>
        </div>

        <div class="cco-order-list">
            @forelse($orders as $order)
                @php
                    $tone = $statusTones[$order->order_status] ?? 'pending';
                    $date = $order->order_date ?? $order->created_at;
                    $lineNames = $order->items->map(fn ($item) => ($item->quantity . ' × ' . ($item->product?->name ?? 'Product')));
                    $preview = $lineNames->take(3)->implode(' · ');
                    $remaining = max(0, $lineNames->count() - 3);
                    $isActive = !in_array($order->order_status, ['Delivered', 'Cancelled'], true);
                @endphp
                <article class="cco-order-card" aria-labelledby="cco-order-title-{{ $order->order_id }}">
                    <div class="cco-order-card-top">
                        <div class="cco-card-identity">
                            <span class="cco-field-label">ORDER NUMBER</span>
                            <h2 id="cco-order-title-{{ $order->order_id }}">#CP-{{ $order->order_id }}</h2>
                            <p>Placed {{ $date?->format('M j, Y') ?? 'Date unavailable' }}@if($date) at {{ $date->format('g:i A') }}@endif</p>
                        </div>
                        <span class="cco-status cco-status--{{ $tone }}"><span class="cco-status-dot" aria-hidden="true"></span>{{ $order->order_status }}</span>
                    </div>

                    <div class="cco-order-card-content">
                        <div>
                            <span class="cco-field-label">ITEMS</span>
                            <p class="cco-product-summary">{{ $preview ?: 'Order items unavailable' }}@if($remaining > 0) <span class="cco-muted">+ {{ $remaining }} more</span>@endif</p>
                            <p class="cco-secondary-meta">{{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'item' : 'items' }} in this order</p>
                        </div>
                        <div class="cco-card-amount">
                            <span class="cco-field-label">ORDER TOTAL</span>
                            <strong>{{ $money($order->total_amount) }}</strong>
                        </div>
                    </div>

                    <div class="cco-order-card-footer">
                        <span class="cco-payment-hint">Payment: {{ $order->payment?->payment_status ?? 'Pending' }}</span>
                        <div class="cco-action-group">
                            <a class="cco-button cco-button--outline" href="{{ route('customer.orders.show', $order->order_id) }}">View details</a>
                            @if($isActive)
                                <a class="cco-button cco-button--primary" href="{{ route('customer.orders.track', $order->order_id) }}">Track order <span aria-hidden="true">→</span></a>
                            @endif
                            @if($order->order_status === 'Delivered' && $order->order_type === 'Delivery')
                                <form method="POST" action="{{ route('customer.orders.reorder', $order->order_id) }}">
                                    @csrf
                                    <button type="submit" class="cco-button cco-button--primary">Reorder</button>
                                </form>
                            @endif
                            @include('customer.orders.partials.cancel-form', ['order' => $order])
                        </div>
                    </div>
                </article>
            @empty
                <section class="cco-empty" aria-labelledby="cco-empty-title">
                    <span class="cco-empty-icon" aria-hidden="true">▤</span>
                    <h2 id="cco-empty-title">{{ $filter === 'all' ? 'No orders yet' : 'No ' . ($filter === 'active' ? 'active' : 'past') . ' orders' }}</h2>
                    <p>{{ $filter === 'all' ? 'Once you place an order, you can review and track it here.' : 'There are no orders in this category right now.' }}</p>
                    @if($filter !== 'all')
                        <a class="cco-button cco-button--primary" href="{{ route('customer.orders.index', ['filter' => 'all']) }}">View all orders</a>
                    @else
                        <a class="cco-button cco-button--primary" href="{{ route('customer.menu') }}">Explore the menu</a>
                    @endif
                </section>
            @endforelse
        </div>

        @if($orders->hasPages())
            <nav class="cco-pagination" aria-label="Order pages">
                @if($orders->onFirstPage())
                    <span class="cco-page-disabled" aria-disabled="true">← Previous</span>
                @else
                    <a href="{{ $orders->previousPageUrl() }}">← Previous</a>
                @endif
                <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
                @if($orders->hasMorePages())
                    <a href="{{ $orders->nextPageUrl() }}">Next →</a>
                @else
                    <span class="cco-page-disabled" aria-disabled="true">Next →</span>
                @endif
            </nav>
        @endif
    </div>
</main>
@endsection
