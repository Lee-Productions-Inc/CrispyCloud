@extends('layouts.admin')
@section('title', 'Sales Reports')
@section('content')
    <div class="a-page-head ar-page-head">
        <div>
            <p class="a-eyebrow">FINANCIAL OVERVIEW</p>
            <h1>Sales &amp; Reports</h1>
        </div>
        <div class="ar-head-actions">
            <a class="ar-button-outline ar-button" href="#ar-spending">Record spending</a>
            <a href="{{ route('admin.reports.export', $exportParams) }}" class="ar-button ar-button-primary">Export CSV</a>
        </div>
    </div>

    <section class="ar-filter-card a-panel" aria-label="Report date filters">
        <div class="ar-filter-main">
            <div>
                <p class="ar-small-label">REPORT PERIOD</p>
                <div class="ar-segments" role="group" aria-label="Preset date ranges">
                    @foreach (['month' => 'This month', '7' => 'Last 7 days', '30' => 'Last 30 days', '90' => 'Last 90 days'] as $key => $label)
                        <a href="{{ route('admin.reports.index', ['period' => $key]) }}"
                            class="ar-segment {{ $period === $key ? 'is-active' : '' }}"
                            @if ($period === $key) aria-current="true" @endif>{{ $label }}</a>
                    @endforeach
                </div>
            </div>
            <div class="ar-date-display"><span>Showing records
                    from</span><strong>{{ \Carbon\Carbon::parse($fromDate)->format('M d, Y') }} –
                    {{ \Carbon\Carbon::parse($toDate)->format('M d, Y') }}</strong></div>
        </div>
        <details class="ar-custom-dates" @if ($period === 'custom') open @endif>
            <summary>Custom dates @if ($period === 'custom')
                    <span class="ar-custom-active">Active</span>
                @endif
            </summary>
            <form method="GET" action="{{ route('admin.reports.index') }}" class="ar-date-form">
                <input type="hidden" name="period" value="custom">
                <label for="ar-from"><span>From</span><input type="date" name="from" id="ar-from"
                        value="{{ old('from', $fromDate) }}" required></label>
                <label for="ar-to"><span>To</span><input type="date" name="to" id="ar-to"
                        value="{{ old('to', $toDate) }}" required></label>
                <button type="submit" class="ar-button ar-button-primary">Apply dates</button>
            </form>
            <small>Choose up to 366 days.</small>
        </details>
        @if ($errors->has('period') || $errors->has('from') || $errors->has('to'))
            <div class="ar-error" role="alert">
                @foreach (['period', 'from', 'to'] as $field)
                    @error($field)
                        <p>{{ $message }}</p>
                    @enderror
                @endforeach
            </div>
        @endif
    </section>

    <section class="a-panel ar-panel ar-expenses ar-spending-panel" id="ar-spending" aria-labelledby="ar-spending-title">
        <div class="ar-section-heading ar-spending-heading">
            <div>
                <h2 id="ar-spending-title">Business spending</h2>
                <p>Record purchases using their actual date, including past dates.</p>
            </div>
            <span class="ar-spending-period-total">₱{{ number_format($recordedExpenses, 2) }} in selected period</span>
        </div>
        <form method="POST" action="{{ route('admin.reports.expenses.store') }}" class="ar-expense-form">
            @csrf
            @foreach ($exportParams as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <label>Purchase date <input name="expense_date" type="date"
                    value="{{ old('expense_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}"
                    required></label>
            <label>Amount (₱) <input name="amount" type="number" step="0.01" min="0.01" max="99999999.99"
                    value="{{ old('amount') }}" required></label>


            @php
                $spendingTypes = [
                    'Ingredients',
                ];

                $previous = old('description', '');
                $isOther = $previous !== '' && !in_array($previous, $spendingTypes, true);
            @endphp

            <label>
                Description
                <select id="spending-type" name="description" required>
                    <option value="">Select spending type</option>

                    @foreach ($spendingTypes as $type)
                        <option value="{{ $type }}" @selected($previous === $type)>
                            {{ $type }}
                        </option>
                    @endforeach

                    <option value="Other" @selected($isOther)>
                        Other (specify)
                    </option>
                </select>

                <input type="text" id="spending-other" placeholder="Enter custom description" maxlength="180"
                    value="{{ $isOther && $previous !== 'Other' ? $previous : '' }}" class="mt-2"
                    aria-label="Specify other spending type"
                    @if (!$isOther) hidden @endif @disabled(!$isOther)>
            </label>

            <button class="ar-button ar-button-primary" type="submit">Save Entry</button>
        </form>
        @if ($errors->has('expense_date') || $errors->has('amount') || $errors->has('description'))
            <div class="ar-error" role="alert">
                @foreach (['expense_date', 'amount', 'description'] as $field)
                    @error($field)
                        <p>{{ $message }}</p>
                    @enderror
                @endforeach
            </div>
        @endif
        <p class="ar-spending-help">Spending affects only reports that include its purchase date. Earlier purchases are not
            automatically spread across later days.</p>
        <details class="ar-spending-history" @if ($expenseHistory->isNotEmpty() || $errors->any()) open @endif>
            <summary>Spending history <span>{{ number_format($expenseHistory->total()) }} recorded · all dates</span>
            </summary>
            @if ($expenseHistory->isEmpty())
                <p class="ar-panel-note">No spending has been recorded yet.</p>
            @else
                <div class="a-table-wrap">
                    <table class="a-table ar-spending-table">
                        <thead>
                            <tr>
                                <th scope="col">Purchase date</th>
                                <th scope="col">Description</th>
                                <th scope="col">Amount</th>
                                <th scope="col">Selected report</th>
                                <th scope="col">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($expenseHistory as $expense)
                                @php $inPeriod = $expense->expense_date >= $fromDate && $expense->expense_date <= $toDate; @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('M d, Y') }}</td>
                                    <td>{{ $expense->description }}</td>
                                    <td><strong>₱{{ number_format((float) $expense->amount, 2) }}</strong></td>
                                    <td><span
                                            class="ar-spending-state {{ $inPeriod ? 'is-included' : '' }}">{{ $inPeriod ? 'Included' : 'Outside period' }}</span>
                                    </td>
                                    <td>
                                        <form method="POST"
                                            action="{{ route('admin.reports.expenses.void', $expense->id) }}"
                                            class="ar-expense-void-form">
                                            @csrf
                                            @foreach ($exportParams as $key => $value)
                                                <input type="hidden" name="{{ $key }}"
                                                    value="{{ $value }}">
                                            @endforeach
                                            <button class="ar-expense-void" type="submit">Void</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($expenseHistory->hasPages())
                    <nav class="ar-spending-pages" aria-label="Spending history pages">
                        @if ($expenseHistory->onFirstPage())
                        <span>Previous</span>@else<a
                                href="{{ $expenseHistory->previousPageUrl() }}#ar-spending">Previous</a>
                        @endif
                        <span>Page {{ $expenseHistory->currentPage() }} of {{ $expenseHistory->lastPage() }}</span>
                        @if ($expenseHistory->hasMorePages())
                        <a href="{{ $expenseHistory->nextPageUrl() }}#ar-spending">Next</a>@else<span>Next</span>
                        @endif
                    </nav>
                @endif
            @endif
        </details>
    </section>

    <section class="ar-stats ar-stats-finance" aria-label="Financial summary">
        <article class="ar-stat">
            <div class="ar-stat-label">Paid revenue</div><strong>₱{{ number_format($paidSales, 2) }}</strong>
            <p>Verified payments, including delivery fees</p>
        </article>
        <article class="ar-stat">
            <div class="ar-stat-label">Estimated profit <details class="cc-finance-info">
                    <summary aria-label="Explain estimated profit">i</summary>
                    <div>Paid revenue minus spending whose purchase dates fall in the selected period. A purchase on one
                        date can make a short period negative. This is not accounting net profit.</div>
                </details>
            </div><strong>{{ $estimatedProfit < 0 ? '−' : '' }}₱{{ number_format(abs($estimatedProfit), 2) }}</strong>
            <p>After ₱{{ number_format($recordedExpenses, 2) }} spending recorded*</p>
        </article>
        <article class="ar-stat">
            <div class="ar-stat-label">Product sales</div><strong>₱{{ number_format($grossProductSales, 2) }}</strong>
            <p>Paid items only; excludes delivery fees</p>
        </article>
        <article class="ar-stat">
            <div class="ar-stat-label">Recorded spending</div><strong>₱{{ number_format($recordedExpenses, 2) }}</strong>
            <p>Manually entered business purchases and costs</p>
        </article>
    </section>
    <div class="ar-activity-line" aria-label="Order statistics">
        <span><strong>{{ number_format($totalOrders) }}</strong> orders placed</span>
        <span><strong>{{ number_format($cancelledOrders) }}</strong> cancelled</span>
        <span><strong>{{ number_format($pendingPayments) }}</strong> pending payments</span>
        <span><strong>₱{{ number_format($averageOrder, 2) }}</strong> average order value</span>
    </div>
    <p class="ar-finance-note">* Estimated result for this date range only. It uses paid revenue minus spending recorded on
        dates within that range; it is not accounting net profit.</p>

    <div class="ar-grid ar-main-grid">
        <section class="a-panel ar-panel" aria-labelledby="ar-chart-title">
            <div class="ar-section-heading">
                <div>
                    <h2 id="ar-chart-title">Received sales trend</h2>
                    <p>Last {{ $chartDays }} day{{ $chartDays === 1 ? '' : 's' }} of selected period • by payment date
                    </p>
                </div><span class="ar-pill">Verified only</span>
            </div>
            @if (!$chartHasSales)
                <p class="ar-chart-empty">No paid sales recorded in the last {{ $chartDays }} days shown. Check an
                    earlier date range if needed.</p>
            @else
                <div class="ar-chart" role="img"
                    aria-label="Daily received sales for the displayed days. Full figures are in the accessible table below.">
                    @foreach ($daily as $row)
                        <div class="ar-chart-col" title="{{ $row['label'] }}: ₱{{ number_format($row['amount'], 2) }}">
                            <div class="ar-bar-track"><span class="ar-bar" aria-hidden="true"
                                    data-report-bar-height data-percent="{{ round(($row['amount'] / $maxDaily) * 100, 2) }}"></span></div>
                            <span class="ar-bar-label">{{ $row['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
            <details class="ar-chart-data">
                <summary>View exact daily amounts</summary>
                <div class="a-table-wrap">
                    <table class="a-table">
                        <thead>
                            <tr>
                                <th scope="col">Day</th>
                                <th scope="col">Paid sales</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daily as $row)
                                <tr>
                                    <td>{{ $row['label'] }}</td>
                                    <td>₱{{ number_format($row['amount'], 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
            <p class="ar-panel-note">All summary cards use the selected period. The chart displays its final
                {{ $chartDays }} days for readability.</p>
        </section>
        <section class="a-panel ar-panel" aria-labelledby="ar-top-title">
            <div class="ar-section-heading">
                <div>
                    <h2 id="ar-top-title">Top-selling products</h2>
                    <p>Units associated with Paid orders</p>
                </div>
            </div>
            @forelse($topProducts as $item)
                <div class="ar-product">
                    <span class="ar-rank">{{ $loop->iteration }}</span>
                    <div class="ar-product-main">
                        <div class="ar-product-line">
                            <strong>{{ $item->name }}</strong><span>{{ number_format((int) $item->units_sold) }}
                                units</span></div>
                        <div class="ar-product-meter"><span aria-hidden="true"
                                data-report-bar-width data-percent="{{ round(((int) $item->units_sold / $highestUnits) * 100, 2) }}"></span>
                        </div><small>₱{{ number_format((float) $item->product_value, 2) }} product value</small>
                    </div>
                </div>
            @empty
                <div class="a-empty"><span aria-hidden="true">▧</span><strong>No paid product sales in this
                        period</strong>
                    <p>Choose another range or check payment verification.</p>
                </div>
            @endforelse
        </section>
    </div>

    <div class="ar-grid ar-secondary-grid">
        <section class="a-panel ar-panel" aria-labelledby="ar-status-title">
            <div class="ar-section-heading">
                <div>
                    <h2 id="ar-status-title">Order statuses</h2>
                    <p>Orders placed in the selected period</p>
                </div>
            </div>
            @foreach ($statuses as $row)
                <div class="ar-summary-row">
                    <span>{{ $row['name'] }}</span><strong>{{ number_format($row['count']) }}</strong></div>
            @endforeach
            <div class="ar-hint">Non-cancelled order value: <strong>₱{{ number_format($orderValue, 2) }}</strong>
                <span>(not necessarily paid)</span></div>
        </section>
        <section class="a-panel ar-panel" aria-labelledby="ar-method-title">
            <div class="ar-section-heading">
                <div>
                    <h2 id="ar-method-title">Payment methods</h2>
                    <p>Paid in-store and verified online payments</p>
                </div>
            </div>
            @forelse($methods as $method)
                <div class="ar-summary-row"><span>{{ $method->payment_method }}
                        <small>({{ number_format((int) $method->transactions) }}
                            paid)</small></span><strong>₱{{ number_format((float) $method->amount, 2) }}</strong></div>
            @empty
                <div class="a-empty"><span aria-hidden="true">₱</span><strong>No verified payments in this period</strong>
                    <p>Choose another range or check payment verification.</p>
                </div>
            @endforelse
            <p class="ar-panel-note">GCash payments are recorded only after manual verification. This site does not process
                payments directly.</p>
        </section>
    </div>

    <section class="a-panel ar-panel ar-recent" aria-labelledby="ar-recent-title">
        <div class="ar-section-heading">
            <div>
                <h2 id="ar-recent-title">Recent paid transactions</h2>
                <p>Latest 8 in selected period · all paid records are available in the CSV export.</p>
            </div>
        </div>
        <div class="ar-channel-stats" aria-label="Sales by channel">
            <article><span>Online sales</span><strong>₱{{ number_format($onlinePaidSales, 2) }}</strong><small>Verified
                    delivery orders</small></article>
            <article><span>Walk-in sales</span><strong>₱{{ number_format($walkinPaidSales, 2) }}</strong><small>Recorded
                    POS transactions</small></article>
        </div>
        @if ($recentPaid->isEmpty())
            <div class="a-empty"><strong>No paid transactions in this period</strong>
                <p>Transactions appear after their payments have been recorded as Paid.</p>
            </div>
        @else
            <div class="a-table-wrap">
                <table class="a-table ar-table">
                    <thead>
                        <tr>
                            <th scope="col">Paid on</th>
                            <th scope="col">Order</th>
                            <th scope="col">Customer</th>
                            <th scope="col">Method</th>
                            <th scope="col">Amount</th>
                            <th scope="col">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentPaid as $payment)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y · h:i A') }}</td>
                                <td><strong>#CP-{{ $payment->order_id }}</strong></td>
                                <td>{{ $payment->order_type === 'Walk-in' ? 'Walk-in customer' : $payment->customer_name ?? 'Customer unavailable' }}
                                </td>
                                <td>{{ $payment->payment_method }}</td>
                                <td><strong>₱{{ number_format((float) $payment->total_amount, 2) }}</strong></td>
                                <td><a class="ar-text-link"
                                        href="{{ $payment->order_type === 'Walk-in' ? route('admin.pos.show', $payment->order_id) : route('admin.orders.show', $payment->order_id) }}">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
    <p class="ar-footnote">Sales follow payment dates. Spending follows purchase dates. Missing expenses, unused supplies
        and refunds are not automatically accounted for.</p>
    <script src="{{ asset('js/admin-reports.js') }}" defer></script>
@endsection
