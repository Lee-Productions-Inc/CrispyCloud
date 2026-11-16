@extends('layouts.customer')

@section('title', "Order Details | Chick's Pops")
@section('body_class', 'cc-orderflow-page')

@section('page_assets')
    
@endsection

@php
    $money = fn ($amount): string => '₱' . number_format((float) $amount, 2);
    $statusTones = ['Pending' => 'pending', 'Preparing' => 'preparing', 'Out for Delivery' => 'delivery', 'Delivered' => 'delivered', 'Cancelled' => 'cancelled'];
    $tone = $statusTones[$order->order_status] ?? 'pending';
    $justPlaced = session()->has('success');
    $itemsSubtotal = $order->items->sum(fn ($item) => (float) $item->subtotal);
    $deliveryFee = (float) ($order->delivery?->delivery_fee ?? 0);
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
            <span aria-current="page">Order details</span>
        </nav>

        @if($justPlaced)
            <section class="cco-confirm-hero" role="status" aria-labelledby="cco-confirm-title">
                <span class="cco-confirm-icon" aria-hidden="true">✓</span>
                <div>
                    <span class="cco-eyebrow">ORDER RECEIVED</span>
                    <h1 id="cco-confirm-title">Order placed successfully<span class="cco-heading-dot">.</span></h1>
                    <p>Your order was saved. You can track its status under My Orders.</p>
                </div>
            </section>
        @else
            <header class="cco-page-intro cco-page-intro--detail">
                <div>
                    <span class="cco-eyebrow">YOUR RECEIPT</span>
                    <h1>Order details<span class="cco-heading-dot">.</span></h1>
                    <p>A summary of your order, delivery and payment details.</p>
                </div>
            </header>
        @endif

        @error('cancel')
            <div class="cco-notice cco-notice--danger" role="alert">{{ $message }}</div>
        @enderror

        <section class="cco-receipt-strip" aria-label="Order summary">
            <div><span class="cco-field-label">ORDER NUMBER</span><strong>#CP-{{ $order->order_id }}</strong></div>
            <div><span class="cco-field-label">DATE PLACED</span><strong>{{ $date?->format('M j, Y · g:i A') ?? 'Unavailable' }}</strong></div>
            <div><span class="cco-field-label">STATUS</span><span class="cco-status cco-status--{{ $tone }}"><span class="cco-status-dot" aria-hidden="true"></span>{{ $order->order_status }}</span></div>
            <div><span class="cco-field-label">TOTAL</span><strong class="cco-receipt-total">{{ $money($order->total_amount) }}</strong></div>
        </section>

        <div class="cco-detail-grid">
            <section class="cco-surface cco-detail-primary" aria-labelledby="cco-receipt-items-heading">
                <div class="cco-surface-header"><h2 id="cco-receipt-items-heading">Items ordered</h2><span class="cco-result-count">{{ $order->items->sum('quantity') }} items</span></div>
                <div class="cco-line-items cco-line-items--full">
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
                                @if($item->item_notes)
                                    <p class="cco-item-note">{{ $item->item_notes }}</p>
                                @endif
                            </div>
                            <strong>{{ $money($item->subtotal) }}</strong>
                        </div>
                    @empty
                        <p class="cco-section-explainer">Item information is unavailable.</p>
                    @endforelse
                </div>
                <div class="cco-receipt-totals">
                    <div class="cco-total-line cco-total-line--light"><span>Items subtotal</span><strong>{{ $money($itemsSubtotal) }}</strong></div>
                    <div class="cco-total-line cco-total-line--light"><span>Delivery fee</span><strong>{{ $money($deliveryFee) }}</strong></div>
                    <div class="cco-total-line"><span>Order total</span><strong>{{ $money($order->total_amount) }}</strong></div>
                </div>
            </section>

            <aside class="cco-detail-sidebar">
                <section class="cco-surface" aria-labelledby="cco-receipt-delivery-heading">
                    <div class="cco-surface-header"><h2 id="cco-receipt-delivery-heading">Delivery details</h2></div>
                    @if($order->delivery)
                        <div class="cco-info-block"><span class="cco-field-label">RECIPIENT</span><p>{{ $order->delivery->recipient_name }}</p><p>{{ $order->delivery->recipient_phone }}</p></div>
                        <div class="cco-info-block"><span class="cco-field-label">ADDRESS</span><p>{{ $order->delivery->address_line }}, {{ $order->delivery->city }}, {{ $order->delivery->postal_code }}</p></div>
                    @else
                        <p class="cco-section-explainer">Delivery information is unavailable.</p>
                    @endif
                </section>
                <section class="cco-surface" aria-labelledby="cco-receipt-payment-heading">
                    <div class="cco-surface-header"><h2 id="cco-receipt-payment-heading">Payment details</h2></div>
                    <div class="cco-info-block"><span class="cco-field-label">PAYMENT METHOD</span><p>{{ $order->payment?->payment_method ?? 'Not recorded' }}</p></div>
                    <div class="cco-info-block"><span class="cco-field-label">PAYMENT STATUS</span><p>{{ $order->payment?->payment_status ?? 'Pending' }}</p></div>
                </section>
            </aside>
        </div>

        <div class="cco-bottom-actions">
            <a class="cco-button cco-button--primary" href="{{ route('customer.orders.track', $order->order_id) }}">View order status <span aria-hidden="true">→</span></a>
            <a class="cco-button cco-button--outline" href="{{ route('customer.orders.index') }}">All my orders</a>
            @include('customer.orders.partials.cancel-form', ['order' => $order])
            <a class="cco-text-action" href="{{ route('customer.menu') }}">Back to menu</a>
        </div>
    </div>
</main>
@endsection
