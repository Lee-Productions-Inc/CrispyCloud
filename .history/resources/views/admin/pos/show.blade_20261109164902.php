@extends('layouts.admin')
@section('title', 'Walk-in Receipt')
@section('content')
<div class="a-page-head pos-head pos-no-print">
    <div><p class="a-eyebrow">COUNTER RECEIPT</p><h1>Walk-in #CP-{{ $order->order_id }}</h1><p>Payment recorded; fulfillment tracked independently of online deliveries.</p></div>
    <div class="pos-head-buttons"><a class="pos-btn pos-btn-muted" href="{{ route('admin.pos.history') }}">← Walk-in history</a><button class="pos-btn pos-btn-muted" type="button" onclick="window.print()">Print receipt</button></div>
</div>
@if($errors->any())<div class="pos-error pos-no-print" role="alert"><strong>Could not complete sale.</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
<div class="pos-receipt-grid">
    <section class="a-panel pos-receipt" aria-label="Sale receipt">
        <div class="pos-receipt-brand"><strong>CHICK'S POPS</strong><span>WALK-IN SALES RECEIPT</span></div>
        <dl class="pos-receipt-details">
            <div><dt>Receipt</dt><dd>#CP-{{ $order->order_id }}</dd></div>
            <div><dt>Date</dt><dd>{{ $order->order_date?->format('M d, Y · h:i A') }}</dd></div>
            <div><dt>Handled by</dt><dd>{{ $sale->cashier_name ?? 'Administrator' }}</dd></div>
            <div><dt>Payment</dt><dd>{{ $order->payment?->payment_method }} — {{ $order->payment?->payment_status }}</dd></div>
        </dl>
        <div class="pos-receipt-items">
            @foreach($order->items as $item)
            <div class="pos-receipt-item">
                <div><strong>{{ $item->quantity }}× {{ $item->product?->name ?? 'Product #' . $item->product_id }}</strong>
                    @if($item->customizations->isNotEmpty())
                        <small>Extras per item:
                            {{ $item->customizations->map(fn ($c) => $c->addon_quantity . ' × ' . ($c->addOn?->name ?? 'Extra'))->implode(', ') }}
                            @if($item->quantity > 1) ({{ $item->quantity }} items; quantities apply to each) @endif
                        </small>
                    @endif
                    @if($item->removals->isNotEmpty()) <small>Removed: {{ $item->removals->map(fn ($r) => $r->component?->component_name)->filter()->implode(', ') }}</small> @endif
                    @if($item->item_notes) <small>{{ $item->item_notes }}</small> @endif
                </div>
                <strong>₱{{ number_format((float) $item->subtotal, 2) }}</strong>
            </div>
            @endforeach
        </div>
        <div class="pos-receipt-total"><span>Total paid</span><strong>₱{{ number_format((float)$order->total_amount, 2) }}</strong></div>
        @if($order->payment?->payment_method === 'Cash')
            <div class="pos-receipt-details"><div><dt>Cash received</dt><dd>₱{{ number_format((float) $sale->cash_received, 2) }}</dd></div><div><dt>Change</dt><dd>₱{{ number_format((float) $sale->change_amount, 2) }}</dd></div></div>
        @else
            <div class="pos-receipt-details"><div><dt>GCash reference</dt><dd>{{ $order->payment?->reference_number ?? '—' }}</dd></div></div>
        @endif
        <p class="pos-receipt-footer">Thank you for choosing Chick's Pops!</p>
    </section>
    <aside class="a-panel pos-receipt-side pos-no-print" aria-label="Fulfillment">
        <h2>Fulfillment status</h2>
        <p><span class="pos-status {{ $order->order_status === 'Delivered' ? 'pos-status-done' : '' }}">{{ $order->order_status === 'Delivered' ? 'Fulfilled' : 'Preparing' }}</span></p>
        @if($order->order_status === 'Preparing')
            <p>When the meal has been prepared and handed to the customer, mark it fulfilled. Inventory will be deducted and logged once.</p>
            <form method="POST" action="{{ route('admin.pos.complete', $order->order_id) }}">
                @csrf
                <button class="pos-btn pos-btn-primary pos-record" type="submit">Mark Fulfilled &amp; Deduct Stock</button>
            </form>
            <p class="pos-help">If an ingredient recipe is missing or stock is insufficient, fulfillment will be blocked. No inventory or order status will be partially updated.</p>
        @else
            <p class="pos-complete-message">✓ This walk-in order is complete. Inventory deduction has already been recorded.</p>
        @endif
        <a class="pos-btn pos-btn-muted pos-record" href="{{ route('admin.pos.index') }}">+ Start another sale</a>
    </aside>
</div>
@endsection
