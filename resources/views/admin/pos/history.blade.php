@extends('layouts.admin')

@section('title', 'Walk-in History')

@section('content')
<div class="a-page-head pos-head">
    <div>
        <p class="a-eyebrow">COUNTER SALES</p>
        <h1>Walk-in History</h1>
        <p>Review past walk-in transactions and open their receipts.</p>
    </div>
    <div class="pos-head-buttons">
        <a class="pos-btn pos-btn-primary" href="{{ route('admin.pos.index') }}">+ New walk-in sale</a>
    </div>
</div>

<section class="a-panel pos-recent" aria-label="Walk-in transaction history">
    <div class="pos-titleline">
        <div>
            <h2>Receipts</h2>
            <p class="pos-help">Filter by fulfillment status.</p>
        </div>
        <div class="pos-head-buttons">
            <a class="pos-btn {{ $status === 'all' ? 'pos-btn-primary' : 'pos-btn-muted' }}" href="{{ route('admin.pos.history', ['status' => 'all']) }}">All</a>
            <a class="pos-btn {{ $status === 'Preparing' ? 'pos-btn-primary' : 'pos-btn-muted' }}" href="{{ route('admin.pos.history', ['status' => 'Preparing']) }}">Preparing</a>
            <a class="pos-btn {{ $status === 'Delivered' ? 'pos-btn-primary' : 'pos-btn-muted' }}" href="{{ route('admin.pos.history', ['status' => 'Delivered']) }}">Served</a>
        </div>
    </div>
    @if($orders->isEmpty())
        <p class="pos-help">No walk-in transactions found for this filter.</p>
    @else
        <div class="a-table-wrap">
            <table class="a-table">
                <thead>
                    <tr>
                        <th scope="col">Receipt</th>
                        <th scope="col">Date</th>
                        <th scope="col">Items</th>
                        <th scope="col">Payment</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($orders as $order)
                        <tr>
                            <td><strong>#CP-{{ $order->order_id }}</strong></td>
                            <td>{{ $order->order_date?->format('M d, Y · h:i A') }}</td>
                            <td>{{ $order->items->sum('quantity') }}</td>
                            <td>{{ $order->payment?->payment_method ?? '—' }}</td>
                            <td>₱{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td><span class="pos-status {{ $order->order_status === 'Delivered' ? 'pos-status-done' : '' }}">{{ $order->order_status === 'Delivered' ? 'Served' : 'Preparing' }}</span></td>
                            <td><a href="{{ route('admin.pos.show', $order->order_id) }}">View receipt →</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="pos-pagination">{{ $orders->links() }}</div>
    @endif
</section>
@endsection
