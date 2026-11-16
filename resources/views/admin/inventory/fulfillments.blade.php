@extends('layouts.admin')
@section('title', 'Inventory · Delivered Order Audit')
@section('content')
<div class="a-page-head ai-heading">
    <div><p class="a-eyebrow">INVENTORY / VERIFICATION</p><h1>Delivered Order Stock Audit</h1>
        <p>Confirm which fulfilled orders recorded inventory deductions, and view the actual ingredient movements.</p>
    </div>
    <a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.recipes.index') }}">Review recipes</a>
</div>
@include('admin.inventory.partials.nav')
<div class="air-hint"><strong>How to read this page:</strong> “Recorded” means the order has a one-time inventory deduction receipt. Expand it to see logged stock movements. “No record” usually means the order was delivered before automatic inventory tracking was installed. <strong>Do not deduct historical orders again.</strong></div>
<section class="a-panel ai-panel" aria-label="Delivered orders with stock records">
    <div class="ai-section-head"><div><h2>Fulfilled order records</h2><p>Includes online deliveries and completed walk-in sales. No changes are made from this page.</p></div></div>
    <form action="{{ route('admin.inventory.fulfillments') }}" class="ai-filter air-audit-filter" method="GET">
        <label class="ai-field" for="air-order-id"><span>Order ID (optional)</span><input id="air-order-id" name="order_id" type="number" min="1" step="1" value="{{ $orderId }}" placeholder="e.g. 1024"></label>
        <label class="ai-field" for="air-source"><span>Order type</span><select id="air-source" name="source">
            @foreach(['all' => 'All types', 'Delivery' => 'Online delivery', 'Walk-in' => 'Walk-in POS'] as $code => $label)
                <option value="{{ $code }}" @selected($source === $code)>{{ $label }}</option>
            @endforeach
        </select></label>
        <label class="ai-field" for="air-record"><span>Deduction record</span><select id="air-record" name="record">
            @foreach(['all' => 'All', 'recorded' => 'Recorded', 'unrecorded' => 'No record (older orders)'] as $code => $label)
                <option value="{{ $code }}" @selected($record === $code)>{{ $label }}</option>
            @endforeach
        </select></label>
        <button type="submit" class="ai-button ai-button-primary">Filter</button>
        <a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.fulfillments') }}">Clear</a>
    </form>
    @if($orders->isEmpty())
        <div class="a-empty"><strong>No delivered orders found</strong><p>Try another filter, or fulfill a new order after configuring its recipe.</p></div>
    @else
        <div class="a-table-wrap"><table class="a-table ai-table air-audit-table">
            <thead><tr><th scope="col">Order</th><th scope="col">Fulfillment</th><th scope="col">Deduction record</th><th scope="col">Verification</th></tr></thead>
            <tbody>
            @foreach($orders as $order)
                @php
                    $receipt = $receipts->get($order->order_id);
                    $reason = 'Automatic stock deduction for delivered order #CP-' . $order->order_id;
                    $lines = $movements->get($reason, collect());
                    $orderUrl = $order->order_type === 'Walk-in' ? route('admin.pos.show', $order->order_id) : route('admin.orders.show', $order->order_id);
                @endphp
                <tr>
                    <td><strong>#CP-{{ $order->order_id }}</strong><small>{{ $order->order_type === 'Walk-in' ? 'Walk-in POS' : 'Online delivery' }} · {{ $order->items_count }} item(s)</small></td>
                    <td>{{ $order->order_date?->format('M d, Y · h:i A') ?? '—' }}<small>Order creation time</small></td>
                    <td>
                        @if($receipt)
                            <span class="air-audit-badge is-recorded">✓ Recorded</span>
                            <small>{{ \Illuminate\Support\Carbon::parse($receipt->deducted_at)->format('M d, Y · h:i A') }}</small>
                        @else
                            <span class="air-audit-badge is-legacy">No deduction record</span>
                            <small>Older order / review history</small>
                        @endif
                    </td>
                    <td><div class="ai-cell-actions"><a class="ai-small-link" href="{{ $orderUrl }}">View order</a></div></td>
                </tr>
                <tr class="air-audit-details-row"><td colspan="4">
                    <details class="air-audit-details"><summary>Stock movements for #CP-{{ $order->order_id }} <span aria-hidden="true">⌄</span></summary>
                        @if($receipt)
                            <p class="air-detail-note">Deduction performed by {{ $adminNames[$receipt->admin_user_id] ?? ('Admin #' . $receipt->admin_user_id) }}. The quantities below are the actual saved stock-log entries.</p>
                            @if($lines->isEmpty())
                                <p class="air-detail-note"><strong>No tracked ingredient movements.</strong> This receipt may represent an explicitly configured “No stock effect” order. Check the order and recipes if unexpected.</p>
                            @else
                                <div class="air-movement-list">
                                    @foreach($lines as $movement)
                                        <div><span>{{ $movement->ingredient?->ingredient_name ?? 'Ingredient no longer present' }}</span><strong>{{ number_format((float)$movement->quantity_changed, 2) }} {{ $movement->ingredient?->unit_of_measure }}</strong></div>
                                    @endforeach
                                </div>
                            @endif
                        @else
                            <p class="air-detail-note">No receipt exists for this historical order. Check prior inventory logs manually if necessary. This page will not apply stock deductions retroactively.</p>
                        @endif
                    </details>
                </td></tr>
            @endforeach
            </tbody>
        </table></div>
        @if($orders->hasPages())<div class="a-pagination">
            @if($orders->onFirstPage())<span aria-disabled="true">← Previous</span>@else<a href="{{ $orders->previousPageUrl() }}">← Previous</a>@endif
            <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
            @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Next →</a>@else<span aria-disabled="true">Next →</span>@endif
        </div>@endif
    @endif
</section>
<div class="air-hint"><strong>Important:</strong> Recipes can change later. This page verifies that a deduction receipt and ingredient logs were saved; it cannot reconstruct the exact recipe configuration used on a historical order. It does not modify inventory.</div>
@endsection
