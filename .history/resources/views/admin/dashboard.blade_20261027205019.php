@extends('layouts.admin')
@section('title', 'Dashboard')
@section('content')
<div class="a-page-head">
    <div>
        <p class="a-eyebrow">BUSINESS OVERVIEW</p>
        <h1>Welcome, {{ explode(' ', trim(Auth::guard('admin')->user()?->full_name ?? 'Admin'))[0] }}!</h1>
        <p>Your Chick's Pops operations at a glance. Figures come directly from the database.</p>
    </div>
    <span class="a-date-label">{{ now()->format('M d, Y') }}</span>
</div>

<section class="a-stats" aria-label="Business statistics">
    <article class="a-stat">
        <div class="a-stat-top"><span>Paid sales</span><span class="a-stat-icon a-red" aria-hidden="true">₱</span></div>
        <strong>₱{{ number_format($paidSales, 2) }}</strong>
        <small>Payments marked Paid (not pending)</small>
    </article>
    <article class="a-stat">
        <div class="a-stat-top"><span>All orders</span><span class="a-stat-icon a-amber" aria-hidden="true">▤</span></div>
        <strong>{{ number_format($totalOrders) }}</strong>
        <small>{{ number_format($pendingOrders) }} awaiting review</small>
    </article>
    <article class="a-stat">
        <div class="a-stat-top"><span>Customers</span><span class="a-stat-icon a-green" aria-hidden="true">♙</span></div>
        <strong>{{ number_format($totalCustomers) }}</strong>
        <small>Registered customer accounts</small>
    </article>
    <article class="a-stat">
        <div class="a-stat-top"><span>Average order value</span><span class="a-stat-icon a-purple" aria-hidden="true">≋</span></div>
        <strong>₱{{ number_format($averageOrderValue, 2) }}</strong>
        <small>All non-cancelled orders</small>
    </article>
</section>

<section class="a-dashboard-panels" aria-label="Performance overview">
    <article class="a-panel a-chart-panel">
        <div class="a-panel-header">
            <div>
                <h2>Order value</h2>
                <p>Last 7 days · excluding cancelled orders</p>
            </div>
            <span class="a-small-tag">7-day view</span>
        </div>
        <div class="a-chart-wrap">
            <svg viewBox="0 0 640 245" class="a-chart" role="img" aria-label="Seven-day chart of placed order value, with labelled days below">
                <line x1="30" y1="49" x2="606" y2="49" class="a-gridline" />
                <line x1="30" y1="121" x2="606" y2="121" class="a-gridline" />
                <line x1="30" y1="194" x2="606" y2="194" class="a-gridline" />
                <polyline points="{{ $chartPolyline }}" class="a-graph-line" />
                @foreach ($chartPoints as $p)
                <circle cx="{{ $p['x'] }}" cy="{{ $p['y'] }}" r="4.8" class="a-graph-point">
                    <title>₱{{ number_format($p['value'], 2) }}</title>
                </circle>
                @endforeach
                @foreach ($dailyValues as $i => $d)
                <text x="{{ 30 + $i * 96 }}" y="226" text-anchor="middle" class="a-chart-label">{{ $d['label'] }}</text>
                @endforeach
            </svg>
        </div>
        <p class="a-chart-caption">These are placed order amounts, not proof of collected payment. Hover a point for its value.</p>
        <details class="a-chart-details">
            <summary>Show daily figures</summary>
            <div class="a-daily-table">
                @foreach ($dailyValues as $d)
                <span>{{ $d['date'] }}</span><strong>₱{{ number_format($d['value'], 2) }}</strong>
                @endforeach
            </div>
        </details>
    </article>
    <article class="a-panel">
        <div class="a-panel-header">
            <div>
                <h2>Top-selling products</h2>
                <p>Based on delivered orders</p>
            </div>
        </div>
        @forelse ($topProducts as $product)
        <div class="a-product-row"><span class="a-product-rank">{{ $loop->iteration }}</span>
            <div><strong>{{ $product->product_name }}</strong><small>{{ number_format($product->units_sold) }} units delivered</small></div>
        </div>
        @empty
        <div class="a-empty"><span aria-hidden="true">▧</span><strong>No delivered sales yet</strong>
            <p>Popular products will appear when orders are marked Delivered.</p>
        </div>
        @endforelse
    </article>
</section>

<section class="a-lower-grid" aria-label="Current operations">
    <article class="a-panel a-recent-panel">
        <div class="a-panel-header">
            <div>
                <h2>Recent orders</h2>
                <p>{{ $activeOrders }} currently active</p>
            </div><a class="a-text-link" href="{{ route('admin.orders.index') }}">View all orders →</a>
        </div>
        @if($recentOrders->isEmpty())
        <div class="a-empty"><span aria-hidden="true">▤</span><strong>No orders yet</strong>
            <p>New customer orders will appear here automatically.</p>
        </div>
        @else
        <div class="a-table-wrap">
            <table class="a-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($recentOrders as $order)
                    @php $s = strtolower(str_replace(' ', '-', $order->order_status)); @endphp
                    <tr>
                        <td><strong>#CP-{{ $order->order_id }}</strong><small>{{ $order->order_date?->format('M d · h:i A') }}</small></td>
                        <td>{{ $order->customer?->full_name ?? 'Customer account unavailable' }}</td>
                        <td>{{ $order->items->sum('quantity') }}</td>
                        <td>₱{{ number_format((float)$order->total_amount, 2) }}</td>
                        <td><span class="a-status status-{{ $s }}">{{ $order->order_status }}</span></td>
                        <td><a class="a-view" href="{{ route('admin.orders.show', $order->order_id) }}">View</a></td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </article>
    <article class="a-panel a-alert-panel">
        <div class="a-panel-header">
            <div>
                <h2>Low stock alerts</h2>
                <p>Ingredients at or below reorder level</p>
            </div><span class="a-small-tag">{{ $lowStock->count() }} shown</span>
        </div>
        @forelse($lowStock as $ingredient)
        <div class="a-stock-row"><span>{{ $ingredient->ingredient_name }}</span><strong>{{ number_format((float)$ingredient->current_stock, 2) }} {{ $ingredient->unit_of_measure }}</strong></div>
        @empty
        <div class="a-empty"><span aria-hidden="true">✓</span><strong>No low-stock records</strong>
            <p>Once ingredients are added, stock alerts will appear automatically.</p>
        </div>
        @endforelse
        <div class="a-soft-note">Inventory editing and automatic stock deductions will be implemented in the Inventory module.</div>
    </article>
</section>
@endsection