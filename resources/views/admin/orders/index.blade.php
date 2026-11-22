@extends('layouts.admin')
@section('title', 'Manage Orders')
@section('content')
<div class="a-page-head a-om-head">
    <div>
        <p class="a-eyebrow">ORDER OPERATIONS</p>
        <h1>Online orders</h1>
    </div>

</div>

<section class="a-panel" aria-label="Order management">
    <nav class="a-filters" aria-label="Filter orders by status">
        @foreach(['all' => 'All Orders', 'Preparing' => 'Preparing', 'Delivered' => 'Delivered', 'Cancelled' => 'Cancelled'] as $code => $label)
            <a href="{{ route('admin.orders.index', array_filter(['status' => $code === 'all' ? null : $code, 'payment' => $paymentFilter === 'all' ? null : $paymentFilter, 'q' => $search !== '' ? $search : null])) }}"
               class="{{ $filter === $code ? 'active' : '' }}" {{ $filter === $code ? 'aria-current=page' : '' }}>{{ $label }}</a>
        @endforeach
    </nav>
    <form class="a-om-search" method="GET" action="{{ route('admin.orders.index') }}" role="search">
        @if($filter !== 'all')<input type="hidden" name="status" value="{{ $filter }}">@endif
        <div class="a-om-field">
            <label for="a-om-query">Find an order</label>
            <input id="a-om-query" name="q" type="search" maxlength="80" value="{{ $search }}" placeholder="Order ID, customer name or email">
        </div>
        <div class="a-om-field a-om-field--small">
            <label for="a-om-payment-filter">Payment</label>
            <select id="a-om-payment-filter" name="payment">
                <option value="all" @selected($paymentFilter === 'all')>All payments</option>
                <option value="Pending" @selected($paymentFilter === 'Pending')>Pending</option>
                <option value="Paid" @selected($paymentFilter === 'Paid')>Paid</option>
            </select>
        </div>
        <button class="a-om-button a-om-button--primary" type="submit">Apply</button>
        <a class="a-om-clear" href="{{ route('admin.orders.index') }}">Clear</a>
    </form>

    @if($errors->any())
        <div class="a-om-validation" role="alert">
            <strong>Status was not updated.</strong>
            <ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
        </div>
    @endif

    @if ($orders->isEmpty())
        <div class="a-empty"><span aria-hidden="true">▤</span><strong>No matching orders</strong><p>Clear your filters or try another search term.</p></div>
    @else
        <div class="a-table-wrap">
            <table class="a-table a-om-table">
                <thead><tr><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Items</th><th scope="col">Total</th><th scope="col">Progress</th><th scope="col">Payment</th><th scope="col">Details</th></tr></thead>
                <tbody>
                @foreach($orders as $order)
                    @php $tone = strtolower(str_replace(' ', '-', $order->order_status)); @endphp
                    <tr>
                        <td><strong>#CP-{{ $order->order_id }}</strong><small>{{ $order->order_date?->format('M d, Y · h:i A') }}</small></td>
                        <td><strong>{{ $order->customer?->full_name ?? 'Unavailable' }}</strong><small>{{ $order->customer?->email }}</small></td>
                        <td>{{ $order->items->sum('quantity') }}</td>
                        <td><strong>₱{{ number_format((float) $order->total_amount, 2) }}</strong></td>
                        <td><span class="a-status status-{{ $tone }}">{{ $order->order_status }}</span></td>
                        <td><span class="a-om-payment {{ $order->payment?->payment_status === 'Paid' ? 'is-paid' : 'is-pending' }}">{{ $order->payment?->payment_status ?? 'Not recorded' }}</span><small>{{ $order->payment?->payment_method }}</small></td>
                        <td class="a-os-action-cell">
                            <a class="a-os-view" href="{{ route('admin.orders.show', $order->order_id) }}" aria-label="View details for order {{ $order->order_id }}">View</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())
            <nav class="a-pagination" aria-label="Orders pagination">
                @if($orders->onFirstPage())<span aria-disabled="true">← Previous</span>@else<a href="{{ $orders->previousPageUrl() }}">← Previous</a>@endif
                <span>Page {{ $orders->currentPage() }} of {{ $orders->lastPage() }}</span>
                @if($orders->hasMorePages())<a href="{{ $orders->nextPageUrl() }}">Next →</a>@else<span aria-disabled="true">Next →</span>@endif
            </nav>
        @endif
    @endif
</section>
@endsection
