@extends('layouts.admin')
@section('title', 'Manage Order #' . $order->order_id)
@section('content')
@php
    $isPaid = $order->payment?->payment_status === 'Paid';
    $isCod = $order->payment?->payment_method === 'COD';
    $canMarkPaid = $order->payment && !$isPaid && $order->payment->payment_status === 'Pending'
        && $order->order_status !== 'Cancelled' && (!$isCod || $order->order_status === 'Delivered');
@endphp

<div class="a-page-head a-om-head">
    <div>
        <p class="a-eyebrow">ORDER MANAGEMENT / #CP-{{ $order->order_id }}</p>
        <h1>Order #CP-{{ $order->order_id }}</h1>
        <p>Placed {{ $order->order_date?->format('F j, Y · h:i A') }}</p>
    </div>
    <a class="a-btn-secondary" href="{{ route('admin.orders.index') }}">← All orders</a>
</div>

@if($errors->any())
    <div class="a-om-validation" role="alert">
        <strong>Changes were not saved.</strong>
        <ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
    </div>
@endif

<div class="a-om-summary" aria-label="Order summary">
    <div><span>Order progress</span><span class="a-status status-{{ strtolower(str_replace(' ', '-', $order->order_status)) }}">{{ $order->order_status }}</span></div>
    <div><span>Payment</span><span class="a-om-payment {{ $isPaid ? 'is-paid' : 'is-pending' }}">{{ $order->payment?->payment_status ?? 'Not recorded' }}</span></div>
    <div><span>Order total</span><strong>₱{{ number_format((float) $order->total_amount, 2) }}</strong></div>
</div>

<div class="a-detail-grid a-om-grid">
    <div class="a-detail-stack">
        <section class="a-panel">
            <div class="a-panel-header"><div><h2>Update order status</h2><p>One dropdown to set the next outcome. Updates sync with customer tracking.</p></div></div>
            @include('admin.orders.partials.status-form', ['order' => $order, 'context' => 'show'])
        </section>

        <section class="a-panel">
            <div class="a-panel-header"><div><h2>Payment verification</h2><p>Review the method and manually record collected payment.</p></div></div>
            <dl class="a-description">
                <div><dt>Method</dt><dd>{{ $order->payment?->payment_method ?? 'Not recorded' }}</dd></div>
                <div><dt>Status</dt><dd>{{ $order->payment?->payment_status ?? 'Not recorded' }}</dd></div>
                @if($order->payment?->reference_number)
                    <div><dt>Reference</dt><dd>{{ $order->payment->reference_number }} <small class="a-om-ref-note">(customer provided; not automatically verified)</small></dd></div>
                @endif
                @if($order->payment?->payment_date)
                    <div><dt>Recorded at</dt><dd>{{ $order->payment->payment_date->format('M d, Y · h:i A') }}</dd></div>
                @endif
            </dl>
            @if($isPaid)
                <div class="a-om-info a-om-info--success">Payment has been marked Paid. This demo does not implement payment refunds or reversals.</div>
            @elseif($canMarkPaid)
                <form method="POST" action="{{ route('admin.orders.payment.paid', $order->order_id) }}" class="a-om-form a-om-payment-form">
                    @csrf
                    @method('PATCH')
                    <label class="a-om-check" for="verified-payment">
                        <input type="checkbox" id="verified-payment" name="confirmed_external_payment" value="1" required>
                        <span>@if($isCod)I received the COD cash after delivery.@else I independently verified the GCash payment outside CrispyCloud.@endif</span>
                    </label>
                    <button type="submit" class="a-om-button a-om-button--primary" onclick="return confirm('Confirm actual payment has been received? This will mark the order Paid.');">Mark payment Paid</button>
                    <p class="a-om-hint">Only record actual payments. Checking this box does not contact GCash or transfer money.</p>
                </form>
            @elseif($order->payment?->payment_status === 'Pending' && $isCod && $order->order_status !== 'Cancelled')
                <p class="a-om-info">COD stays Pending until the order is marked Delivered and cash has been collected.</p>
            @else
                <p class="a-om-info">No payment action is available for this order.</p>
            @endif
        </section>

        <section class="a-panel">
            <div class="a-panel-header"><h2>Order items & customizations</h2></div>
            @forelse($order->items as $item)
                <div class="a-line-item">
                    <div>
                        <strong>{{ $item->quantity }} × {{ $item->product?->name ?? 'Product' }}</strong>
                        <small>Base ₱{{ number_format((float) $item->unit_price, 2) }} each</small>
                        @if($item->customizations->isNotEmpty())
                            <p class="a-detail-caption">Add-ons: {{ $item->customizations->map(fn($c) => $c->addon_quantity . ' × ' . ($c->addOn?->name ?? 'Extra') . ' per item')->implode(', ') }}</p>
                        @endif
                        @if($item->removals->isNotEmpty())
                            <p class="a-detail-caption">Without: {{ $item->removals->map(fn($r) => $r->component?->component_name ?? 'Component')->implode(', ') }}</p>
                        @endif
                        @if($item->item_notes)
                            <p class="a-detail-caption">Instructions: {{ $item->item_notes }}</p>
                        @endif
                    </div>
                    <strong>₱{{ number_format((float) $item->subtotal, 2) }}</strong>
                </div>
            @empty
                <p class="a-om-info">No order items found.</p>
            @endforelse
            <div class="a-order-total"><span>Order total</span><strong>₱{{ number_format((float) $order->total_amount, 2) }}</strong></div>
        </section>
    </div>

    <aside class="a-detail-stack">
        <section class="a-panel">
            <h2>Customer information</h2>
            <dl class="a-description">
                <div><dt>Name</dt><dd>{{ $order->customer?->full_name ?? 'Unavailable' }}</dd></div>
                <div><dt>Email</dt><dd>{{ $order->customer?->email ?? 'Unavailable' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $order->customer?->phone_number ?: 'Not provided' }}</dd></div>
            </dl>
        </section>
        <section class="a-panel">
            <h2>Delivery details</h2>
            <dl class="a-description">
                <div><dt>Recorded status</dt><dd>{{ $order->delivery?->tracking_status ?? 'Unavailable' }}</dd></div>
                @if($order->delivery?->actual_delivery_time)
                    <div><dt>Delivered at</dt><dd>{{ $order->delivery->actual_delivery_time->format('M d, Y · h:i A') }}</dd></div>
                @endif
                <div><dt>Recipient</dt><dd>{{ $order->delivery?->recipient_name ?? 'Not recorded' }}</dd></div>
                <div><dt>Phone</dt><dd>{{ $order->delivery?->recipient_phone ?? 'Not recorded' }}</dd></div>
                <div><dt>Address</dt><dd>{{ collect([$order->delivery?->address_line, $order->delivery?->city, $order->delivery?->postal_code])->filter()->implode(', ') ?: 'Not recorded' }}</dd></div>
            </dl>
        </section>
        <div class="a-soft-note">Customers can view changes after refreshing My Orders or the tracking page. There is no real-time courier location and no integrated online payment verification in this local version.</div>
    </aside>
</div>
@endsection
