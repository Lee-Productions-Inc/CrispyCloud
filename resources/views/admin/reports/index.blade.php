@extends('layouts.admin')
@section('title', 'Sales Reports')
@section('content')
<div class="a-page-head ar-page-head">
    <div>
        <p class="a-eyebrow">FINANCIAL OVERVIEW</p>
        <h1>Sales &amp; Reports</h1>
    </div>
    <a href="{{ route('admin.reports.export', $exportParams) }}" class="ar-button ar-button-primary">↓ Export paid sales (CSV)</a>
</div>

<section class="a-panel ar-filter-card" aria-label="Report date filters">
    <div class="ar-filter-main">
        <div>
            <p class="ar-small-label">REPORT PERIOD</p>
            <div class="ar-segments" role="group" aria-label="Preset date ranges">
                @foreach(['7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $key => $label)
                    <a href="{{ route('admin.reports.index', ['period' => $key]) }}" class="ar-segment {{ $period === $key ? 'is-active' : '' }}" @if($period === $key) aria-current="true" @endif>{{ $label }}</a>
                @endforeach
            </div>
        </div>
        <div class="ar-date-display"><span>Showing records from</span><strong>{{ \Carbon\Carbon::parse($fromDate)->format('M d, Y') }} – {{ \Carbon\Carbon::parse($toDate)->format('M d, Y') }}</strong></div>
    </div>
    <details class="ar-custom-dates" @if($period === 'custom') open @endif>
        <summary>Choose custom dates</summary>
        <form method="GET" action="{{ route('admin.reports.index') }}" class="ar-date-form">
            <input type="hidden" name="period" value="custom">
            <label for="ar-from"><span>From</span><input type="date" name="from" id="ar-from" value="{{ old('from', $fromDate) }}" required></label>
            <label for="ar-to"><span>To</span><input type="date" name="to" id="ar-to" value="{{ old('to', $toDate) }}" required></label>
            <button type="submit" class="ar-button ar-button-primary">Apply dates</button>
        </form>
        <small>Choose up to 366 days.</small>
    </details>
    @if($errors->any())
        <div class="ar-error" role="alert">
            <strong>Check the selected dates.</strong>
            @foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
</section>

<section class="ar-stats" aria-label="Financial summary">
    <article class="ar-stat ar-stat-primary"><div class="ar-stat-label">Gross product sales <details class="cc-finance-info"><summary aria-label="Explain gross product sales">i</summary><div>Verified paid product revenue for the selected dates, excluding delivery fees.</div></details></div><strong>₱{{ number_format($grossProductSales, 2) }}</strong><p>Verified product revenue</p></article>
    <article class="ar-stat"><div class="ar-stat-label">Business capital <details class="cc-finance-info"><summary aria-label="Explain business capital">i</summary><div>Capital assigned to calendar months overlapping the date range. Not an expense or a profit figure.</div></details></div><strong>₱{{ number_format($recordedCapital, 2) }}</strong><p><a href="{{ route('admin.investments.index') }}">Record monthly capital →</a></p></article>
</section>
<section class="ar-stats" aria-label="Sales and order statistics">
    <article class="ar-stat ar-stat-primary"><div class="ar-stat-label">Received sales <span aria-hidden="true">₱</span></div><strong>₱{{ number_format($paidSales, 2) }}</strong><p>{{ number_format($paidOrders) }} payment{{ $paidOrders === 1 ? '' : 's' }} recorded as Paid</p></article>
    <article class="ar-stat"><div class="ar-stat-label">Orders placed <span aria-hidden="true">▤</span></div><strong>{{ number_format($totalOrders) }}</strong><p>{{ number_format($cancelledOrders) }} cancelled</p></article>
    <article class="ar-stat"><div class="ar-stat-label">Pending payments <span aria-hidden="true">◷</span></div><strong>{{ number_format($pendingPayments) }}</strong><p>Orders placed during this period</p></article>
    <article class="ar-stat"><div class="ar-stat-label">Average order value <span aria-hidden="true">≋</span></div><strong>₱{{ number_format($averageOrder, 2) }}</strong><p>Excludes cancelled orders</p></article>
</section>

<div class="ar-grid ar-main-grid">
    <section class="a-panel ar-panel" aria-labelledby="ar-chart-title">
        <div class="ar-section-heading"><div><h2 id="ar-chart-title">Received sales trend</h2><p>Last {{ $chartDays }} day{{ $chartDays === 1 ? '' : 's' }} of selected period • by payment date</p></div><span class="ar-pill">Verified only</span></div>
        <div class="ar-chart" role="img" aria-label="Daily received sales for the displayed days. Full figures are in the accessible table below.">
            @foreach($daily as $row)
                <div class="ar-chart-col" title="{{ $row['label'] }}: ₱{{ number_format($row['amount'], 2) }}">
                    <div class="ar-bar-track"><span class="ar-bar" style="height: {{ round($row['amount'] / $maxDaily * 100, 2) }}%"></span></div>
                    <span class="ar-bar-label">{{ $row['label'] }}</span>
                </div>
            @endforeach
        </div>
        <details class="ar-chart-data"><summary>View exact daily amounts</summary>
            <div class="a-table-wrap"><table class="a-table"><thead><tr><th scope="col">Day</th><th scope="col">Paid sales</th></tr></thead><tbody>
                @foreach($daily as $row)<tr><td>{{ $row['label'] }}</td><td>₱{{ number_format($row['amount'], 2) }}</td></tr>@endforeach
            </tbody></table></div>
        </details>
        <p class="ar-panel-note">All summary cards use the selected period. The chart displays its final {{ $chartDays }} days for readability.</p>
    </section>
    <section class="a-panel ar-panel" aria-labelledby="ar-top-title">
        <div class="ar-section-heading"><div><h2 id="ar-top-title">Top-selling products</h2><p>Units associated with Paid orders</p></div></div>
        @forelse($topProducts as $item)
            <div class="ar-product">
                <span class="ar-rank">{{ $loop->iteration }}</span>
                <div class="ar-product-main"><div class="ar-product-line"><strong>{{ $item->name }}</strong><span>{{ number_format((int)$item->units_sold) }} units</span></div><div class="ar-product-meter"><span style="width: {{ round(((int)$item->units_sold / $highestUnits) * 100, 2) }}%"></span></div><small>₱{{ number_format((float)$item->product_value, 2) }} product value</small></div>
            </div>
        @empty
            <div class="a-empty"><span aria-hidden="true">▧</span><strong>No paid product sales yet</strong><p>Products appear here after an order's payment is verified.</p></div>
        @endforelse
    </section>
</div>

<div class="ar-grid ar-secondary-grid">
    <section class="a-panel ar-panel" aria-labelledby="ar-status-title">
        <div class="ar-section-heading"><div><h2 id="ar-status-title">Order statuses</h2><p>Orders placed in the selected period</p></div></div>
        @foreach($statuses as $row)
            <div class="ar-summary-row"><span>{{ $row['name'] }}</span><strong>{{ number_format($row['count']) }}</strong></div>
        @endforeach
        <div class="ar-hint">Non-cancelled order value: <strong>₱{{ number_format($orderValue, 2) }}</strong> <span>(not necessarily paid)</span></div>
    </section>
    <section class="a-panel ar-panel" aria-labelledby="ar-method-title">
        <div class="ar-section-heading"><div><h2 id="ar-method-title">Payment methods</h2><p>Paid in-store and verified online payments</p></div></div>
        @forelse($methods as $method)
            <div class="ar-summary-row"><span>{{ $method->payment_method }} <small>({{ number_format((int)$method->transactions) }} paid)</small></span><strong>₱{{ number_format((float)$method->amount, 2) }}</strong></div>
        @empty
            <div class="a-empty"><span aria-hidden="true">₱</span><strong>No payments verified yet</strong><p>COD and GCash totals appear after manual verification.</p></div>
        @endforelse
        <p class="ar-panel-note">GCash payments are recorded only after manual verification. This site does not process payments directly.</p>
    </section>
</div>

<section class="a-panel ar-panel ar-recent" aria-labelledby="ar-recent-title">
    <div class="ar-section-heading"><div><h2 id="ar-recent-title">Recent paid transactions</h2><p>Most recent 8 payments within the selected date range</p></div><div style="display:flex;gap:12px;flex-wrap:wrap"><a class="ar-text-link" href="{{ route('admin.orders.index') }}">Online Orders →</a><a class="ar-text-link" href="{{ route('admin.pos.history') }}">Walk-in history →</a></div></div>
    @if($recentPaid->isEmpty())
        <div class="a-empty"><span aria-hidden="true">▤</span><strong>No paid transactions in this period</strong><p>You can still review placed orders from Admin Orders.</p></div>
    @else
        <div class="a-table-wrap"><table class="a-table ar-table"><thead><tr><th scope="col">Paid on</th><th scope="col">Order</th><th scope="col">Customer</th><th scope="col">Method</th><th scope="col">Amount</th><th scope="col">Details</th></tr></thead><tbody>
            @foreach($recentPaid as $payment)
                <tr><td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y · h:i A') }}</td><td><strong>#CP-{{ $payment->order_id }}</strong></td><td>{{ $payment->order_type === 'Walk-in' ? 'Walk-in customer' : ($payment->customer_name ?? 'Customer unavailable') }}</td><td>{{ $payment->payment_method }}</td><td><strong>₱{{ number_format((float)$payment->total_amount, 2) }}</strong></td><td><a class="ar-text-link" href="{{ $payment->order_type === 'Walk-in' ? route('admin.pos.show', $payment->order_id) : route('admin.orders.show', $payment->order_id) }}">View</a></td></tr>
            @endforeach
        </tbody></table></div>
    @endif
</section>
<p class="ar-footnote">Paid revenue follows verified payment dates. Capital follows the calendar months covered by the selected range. Capital is not a profit calculation. Refund adjustments are not modeled.</p>
@endsection
