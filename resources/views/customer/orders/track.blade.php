@extends('layouts.customer')

@section('title', "Track Order #CP-{$order->order_id} | Chick's Pops")
@section('body_class', 'cc-orderflow-page')

@section('page_assets')
    
@endsection

@php
    $money = fn ($amount): string => '₱' . number_format((float) $amount, 2);
    $statusTones = ['Pending' => 'pending', 'Preparing' => 'preparing', 'Out for Delivery' => 'delivery', 'Delivered' => 'delivered', 'Cancelled' => 'cancelled'];
    $tone = $statusTones[$order->order_status] ?? 'pending';
    $isCancelled = $order->order_status === 'Cancelled';
    $isDelivered = $order->order_status === 'Delivered';
    $statusStages = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered'];
    $stageIndex = array_search($order->order_status, $statusStages, true);
    $stageIndex = $stageIndex === false ? 0 : $stageIndex;
    $steps = [
        ['name' => 'Order received', 'description' => 'Your order has been recorded.'],
        ['name' => 'Preparing', 'description' => 'Our team is getting your order ready.'],
        ['name' => 'Out for delivery', 'description' => 'The order has been marked as out for delivery.'],
        ['name' => 'Delivered', 'description' => 'The order has been marked as delivered.'],
    ];
    $date = $order->order_date ?? $order->created_at;
@endphp

@section('content')
<main id="main-content" class="cco-main min-w-0">
    <div class="cco-container mx-auto">
        <nav class="cco-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.menu') }}">Menu</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('customer.orders.index') }}">My Orders</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Tracking</span>
        </nav>

        <header class="cco-page-intro cco-page-intro--detail">
            <div>
                <span class="cco-eyebrow">ORDER STATUS</span>
                <h1>{{ $isCancelled ? 'Order cancelled' : 'Track your order' }}<span class="cco-heading-dot">.</span></h1>
                <p>#CP-{{ $order->order_id }} · {{ $order->items->sum('quantity') }} {{ $order->items->sum('quantity') === 1 ? 'item' : 'items' }}@if($date) · Placed {{ $date->format('M j, Y') }}@endif</p>
            </div>
            <span class="cco-status cco-status--{{ $tone }}"><span class="cco-status-dot" aria-hidden="true"></span>{{ $order->order_status }}</span>
        </header>

        @error('cancel')
            <div class="cco-notice cco-notice--danger" role="alert">{{ $message }}</div>
        @enderror

        @if($isCancelled)
            <div class="cco-notice cco-notice--danger" role="status">
                <strong>This order was cancelled.</strong> Its delivery progress is no longer active. You can still view the order details below.
            </div>
        @endif

        <div class="cco-detail-grid">
            <div class="cco-detail-primary">
                <section class="cco-surface" aria-labelledby="cco-progress-heading">
                    <div class="cco-surface-header">
                        <div>
                            <span class="cco-eyebrow">AT A GLANCE</span>
                            <h2 id="cco-progress-heading">Order progress</h2>
                        </div>
                    </div>
                    <p class="cco-section-explainer">Updates appear here when the business changes your order's recorded status. Refresh this page to check again.</p>
                    <ol class="cco-timeline">
                        @foreach($steps as $index => $step)
                            @php
                                $completed = !$isCancelled && ($index < $stageIndex || ($isDelivered && $index === $stageIndex));
                                $current = !$isCancelled && !$isDelivered && $index === $stageIndex;
                                $done = $completed || $current;
                                $cancelledFirst = $isCancelled && $index === 0;
                            @endphp
                            <li class="cco-timeline-item {{ $completed || $cancelledFirst ? 'is-done' : '' }} {{ $current ? 'is-current' : '' }} {{ !$done && !$cancelledFirst ? 'is-upcoming' : '' }}" @if($current) aria-current="step" @endif>
                                <span class="cco-timeline-marker" aria-hidden="true">{{ $completed || $cancelledFirst ? '✓' : ($current ? '●' : ($isCancelled ? '–' : '○')) }}</span>
                                <div>
                                    <strong>{{ $step['name'] }}</strong>
                                    <p>{{ $isCancelled && $index > 0 ? 'Not active — order cancelled' : ($done || $cancelledFirst ? $step['description'] : 'Not reached yet') }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <div class="cco-surface-actions">
                        <a class="cco-button cco-button--primary" href="{{ route('customer.orders.track', $order->order_id) }}">Refresh status <span aria-hidden="true">↻</span></a>
                        <a class="cco-button cco-button--outline" href="{{ route('customer.orders.index') }}">Back to orders</a>
                        @include('customer.orders.partials.cancel-form', ['order' => $order])
                    </div>
                    <p class="cco-helper-note">Tracking reflects database updates only. No live driver location or delivery-time estimate is available.</p>
                </section>
            </div>

            <aside class="cco-detail-sidebar">
                <section class="cco-surface" aria-labelledby="cco-tracking-order-heading">
                    <div class="cco-surface-header"><h2 id="cco-tracking-order-heading">Your order</h2></div>
                    <div class="cco-line-items">
                        @forelse($order->items as $item)
                            <div class="cco-line-item">
                                <div>
                                    <strong>{{ $item->quantity }} × {{ $item->product?->name ?? 'Product' }}</strong>
                                    @foreach($item->customizations as $extra)
                                        <p>+ {{ $extra->addon_quantity }} × {{ $extra->addOn?->name ?? 'Extra' }} per item{{ $item->quantity > 1 ? ' (' . ($item->quantity * $extra->addon_quantity) . ' total)' : '' }}</p>
                                    @endforeach
                                    @foreach($item->removals as $removed)
                                        <p>Without {{ $removed->component?->component_name ?? 'component' }}</p>
                                    @endforeach
                                    @if($item->item_notes)<p class="cco-item-note">{{ $item->item_notes }}</p>@endif
                                </div>
                                <strong>{{ $money($item->subtotal) }}</strong>
                            </div>
                        @empty
                            <p class="cco-section-explainer">Item details are unavailable.</p>
                        @endforelse
                    </div>
                    <div class="cco-total-line cco-total-line--light"><span>Delivery fee</span><strong>{{ $money($order->delivery?->delivery_fee ?? 0) }}</strong></div>
                    <div class="cco-total-line"><span>Total</span><strong>{{ $money($order->total_amount) }}</strong></div>
                    <a class="cco-text-action cco-sidebar-link" href="{{ route('customer.orders.show', $order->order_id) }}">View full details <span aria-hidden="true">→</span></a>
                </section>
                <section class="cco-surface" aria-labelledby="cco-tracking-delivery-heading">
                    <div class="cco-surface-header"><h2 id="cco-tracking-delivery-heading">Delivery &amp; payment</h2></div>
                    @if($order->delivery)
                        <div class="cco-info-block">
                            <span class="cco-field-label">DELIVERY ADDRESS</span>
                            <p>{{ $order->delivery->recipient_name }}@if($order->delivery->recipient_phone) · {{ $order->delivery->recipient_phone }}@endif</p>
                            <p>{{ $order->delivery->address_line }}, {{ $order->delivery->city }}, {{ $order->delivery->postal_code }}</p>
                        </div>
                    @else
                        <p class="cco-section-explainer">Delivery information is unavailable.</p>
                    @endif
                    <div class="cco-info-block">
                        <span class="cco-field-label">PAYMENT</span>
                        <p>{{ $order->payment?->payment_method ?? 'Not recorded' }} · {{ $order->payment?->payment_status ?? 'Pending' }}</p>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</main>
@endsection
