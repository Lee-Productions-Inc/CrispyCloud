@extends('layouts.admin')
@section('title', 'Walk-in Point of Sale')
@section('content')
<div class="a-page-head pos-head">
    <div>
        <p class="a-eyebrow">IN-STORE ORDERS</p>
        <h1>Walk-in POS</h1>
        <p>Choose items, review the total, and create a receipt.</p>
    </div>
    <a class="pos-btn pos-btn-muted" href="{{ route('admin.pos.history') }}">Walk-in history</a>
</div>

@if($errors->any())
    <div class="pos-error" role="alert"><strong>Sale wasn't recorded.</strong>
        <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="pos-columns pos-refined-columns">
    <section class="a-panel pos-panel pos-picker-panel" aria-labelledby="pos-picker-title">
        <div class="pos-titleline">
            <div><h2 id="pos-picker-title">Choose items</h2></div>
        </div>
        @if(empty($catalog))
            <div class="a-empty"><strong>No available menu items</strong><p>Add products from Admin → Products first.</p><a class="pos-btn pos-btn-muted" href="{{ route('admin.products.create') }}">Add product</a></div>
        @else
            <div class="pos-kind-tabs" role="group" aria-label="Choose menu section">
                <button type="button" data-pos-kind="food" class="pos-kind-tab is-active" aria-pressed="true"><span aria-hidden="true">🍗</span> Food</button>
                <button type="button" data-pos-kind="beverage" class="pos-kind-tab" aria-pressed="false"><span aria-hidden="true">🥤</span> Beverages</button>
            </div>
            <div class="pos-category-wrap" aria-label="Filter products by category">
                <span class="pos-category-heading">Filter by category</span>
                <div class="pos-category-filters" id="pos-category-filters" role="group" aria-label="Filter menu categories"></div>
            </div>
            <div class="pos-field pos-search-field">
                <label for="pos-product-search">Search menu</label>
                <input id="pos-product-search" type="search" placeholder="Search food or drinks…" autocomplete="off">
            </div>
            <p class="pos-help" id="pos-catalog-count" aria-live="polite"></p>
            <div id="pos-product-grid" class="pos-product-grid" aria-label="Products to add"></div>
            <input id="pos-product" type="hidden" value="">

            <div class="pos-configuration" id="pos-configuration" hidden>
                <div class="pos-selection-head">
                    <div>
                        <h3 id="pos-selected-title">Selected item</h3>
                        <p class="pos-help" id="pos-selected-product" aria-live="polite"></p>
                    </div>
                    <button type="button" class="pos-reset-selection" id="pos-clear-selection">Change item</button>
                </div>
                <div class="pos-field pos-field-compact">
                    <label for="pos-qty">Quantity</label>
                    <input id="pos-qty" type="number" min="1" max="20" value="1" inputmode="numeric">
                </div>
                <div id="pos-food-options">
                    <fieldset class="pos-options" id="pos-addons"><legend>Food extras</legend><p class="pos-help">Select a product first.</p></fieldset>
                    <fieldset class="pos-options" id="pos-removals"><legend>Remove ingredients</legend><p class="pos-help">Select a product first.</p></fieldset>
                    <div class="pos-field" id="pos-sauce-wrap" hidden>
                        <label>Included sauce</label>
                        <div id="pos-sauce-options" role="group" aria-label="Included sauce choices"></div>
                    </div>
                </div>
                <p class="pos-beverage-note" id="pos-beverage-note" hidden>Drinks are ordered as separate items.</p>
                <div class="pos-field"><label for="pos-notes">Special instructions (optional)</label><textarea id="pos-notes" rows="2" maxlength="500" placeholder="Any special request?"></textarea></div>
                <div id="pos-picker-error" class="pos-client-error" role="alert" hidden></div>
                <button class="pos-btn pos-btn-primary pos-add" id="pos-add-line" type="button">Add to order · <span id="pos-item-preview-total">₱0.00</span></button>
            </div>
        @endif
    </section>

    <section class="a-panel pos-panel pos-checkout" aria-labelledby="pos-checkout-title">
        <div class="pos-titleline">
            <div><h2 id="pos-checkout-title">Order summary</h2></div>
        </div>
        <form id="pos-sale-form" method="POST" action="{{ route('admin.pos.store') }}" novalidate>
            @csrf
            <input type="hidden" name="pos_token" value="{{ old('pos_token', $token) }}">
            <input type="hidden" name="items_json" id="pos-items-json" value="{{ old('items_json', '[]') }}">
            <div id="pos-cart-lines" class="pos-cart-lines" aria-live="polite"><p class="pos-empty">No items yet. Choose a product to begin.</p></div>
            <div class="pos-total"><span>Amount to collect</span><strong id="pos-grand-total">₱0.00</strong></div>
            <fieldset class="pos-method"><legend>How was the customer paying?</legend>
                <label class="pos-radio"><input type="radio" name="payment_method" value="Cash" @checked(old('payment_method', 'Cash') === 'Cash')><span><strong>Cash</strong><small>Enter cash received</small></span></label>
                <label class="pos-radio"><input type="radio" name="payment_method" value="GCash" @checked(old('payment_method') === 'GCash')><span><strong>GCash</strong><small>Verify the transfer</small></span></label>
            </fieldset>
            <div class="pos-field" id="pos-cash-block">
                <label for="pos-cash-received">Amount received (₱)</label>
                <input type="number" id="pos-cash-received" name="cash_received" min="0" step="0.01" value="{{ old('cash_received') }}" inputmode="decimal" placeholder="0.00">
                <p class="pos-change"><span id="pos-change-label">Change due</span>: <strong id="pos-change">₱0.00</strong></p>
            </div>
            <div class="pos-field" id="pos-gcash-block" hidden>
                <label for="pos-gcash-ref">Verified GCash reference</label>
                <input type="text" id="pos-gcash-ref" name="gcash_reference" maxlength="100" value="{{ old('gcash_reference') }}" placeholder="Enter transaction reference">
                <p class="pos-help">Verify the transfer in your GCash merchant account before saving.</p>
            </div>
            <div class="pos-client-error" id="pos-client-error" role="alert" hidden></div>
            <button type="submit" id="pos-record" class="pos-btn pos-btn-primary pos-record">Save paid sale &amp; create receipt</button>
        </form>
        <p class="pos-help pos-after-note">Mark the order <strong>Served</strong> on its receipt after handing it to the customer.</p>
    </section>
</div>

<section class="a-panel pos-recent" aria-labelledby="pos-recent-title">
    <details class="pos-recent-details">
        <summary id="pos-recent-title"><span>Recent walk-in sales</span><span class="pos-recent-indicator">Show receipts <span aria-hidden="true">⌄</span></span></summary>
        <div class="pos-recent-content">
        <a class="pos-all-history" href="{{ route('admin.pos.history') }}">View full history →</a>
    @if($recent->isEmpty())
        <p class="pos-help">Walk-in transactions will appear here after your first sale.</p>
    @else
        <div class="a-table-wrap"><table class="a-table">
            <thead><tr><th scope="col">Receipt</th><th scope="col">Time</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Status</th><th scope="col">Action</th></tr></thead>
            <tbody>@foreach($recent as $order)
                <tr>
                    <td><strong>#CP-{{ $order->order_id }}</strong></td>
                    <td>{{ $order->order_date?->format('M d · h:i A') }}</td>
                    <td>{{ $order->items->sum('quantity') }}</td>
                    <td>₱{{ number_format((float) $order->total_amount, 2) }}</td>
                    <td><span class="pos-status {{ $order->order_status === 'Delivered' ? 'pos-status-done' : '' }}">{{ $order->order_status === 'Delivered' ? 'Served' : 'Preparing' }}</span></td>
                    <td><a href="{{ route('admin.pos.show', $order->order_id) }}">View receipt →</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
    @endif
        </div>
    </details>
</section>
<script id="pos-catalog-json" type="application/json">@json($catalog)</script>
<script src="{{ asset('js/admin-pos.js') }}" defer></script>
@endsection
