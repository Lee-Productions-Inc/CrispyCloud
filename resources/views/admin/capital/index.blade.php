@extends('layouts.admin')
@section('title', 'Business Capital')
@section('content')
<div class="a-page-head">
    <div>
        <p class="a-eyebrow">FINANCIAL RECORDS</p>
        <h1>Business Capital</h1>
    </div>
    <a href="{{ route('admin.reports.index') }}" class="a-text-link">Sales &amp; Reports →</a>
</div>

<section class="a-panel cc-capital-filter" aria-label="Choose capital month">
    <form method="GET" action="{{ route('admin.investments.index') }}" class="cc-capital-month-form">
        <label for="capital-month-filter">Reporting month</label>
        <input id="capital-month-filter" name="month" type="month" required value="{{ $selectedMonth }}">
        <button class="cc-capital-button cc-capital-button-light" type="submit">View month</button>
    </form>
</section>

<section class="a-stats cc-capital-summary" aria-label="Monthly capital summary">
    <article class="a-stat">
        <div class="a-stat-top">
            <span>Capital invested · {{ $monthLabel }}</span>
            <details class="cc-finance-info"><summary aria-label="Explain business capital">i</summary><div>Money contributed to the business, not the same as operating expenses or profit.</div></details>
        </div>
        <strong>₱{{ number_format($monthCapital, 2) }}</strong>
        <small>Active contributions recorded for this month</small>
    </article>
</section>

<div class="cc-capital-grid">
    <section class="a-panel cc-capital-panel" aria-labelledby="capital-form-title">
        <h2 id="capital-form-title">Record a contribution</h2>
        @if($errors->any())
            <div class="a-alert a-alert-error" role="alert">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('admin.investments.store') }}" class="cc-capital-form">
            @csrf
            <label for="cc-capital-month">Month</label>
            <input id="cc-capital-month" type="month" name="month" required value="{{ old('month', $selectedMonth) }}">

            <label for="cc-capital-amount">Amount added (₱)</label>
            <input id="cc-capital-amount" type="number" name="amount" inputmode="decimal" step="0.01" min="0.01" max="9999999999.99" required value="{{ old('amount') }}" placeholder="10000.00">

            <label for="cc-capital-note">Note <span class="cc-capital-muted">(optional)</span></label>
            <input id="cc-capital-note" type="text" name="note" maxlength="180" value="{{ old('note') }}" placeholder="Initial capital, additional funds…">

            <button class="cc-capital-button" type="submit">Save capital</button>
        </form>
    </section>

    <section class="a-panel cc-capital-panel" aria-labelledby="capital-history-title">
        <h2 id="capital-history-title">{{ $monthLabel }} history</h2>
        <div class="a-table-wrap">
            <table class="a-table cc-capital-history">
                <thead><tr><th scope="col">Recorded</th><th scope="col">Amount</th><th scope="col">Note</th><th scope="col">Status / correction</th></tr></thead>
                <tbody>
                @forelse($history as $entry)
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($entry->created_at)->format('M d, Y') }}</td>
                        <td><strong>₱{{ number_format((float)$entry->amount, 2) }}</strong></td>
                        <td>{{ $entry->note ?: '—' }}</td>
                        <td>
                            @if($entry->voided_at)
                                <span class="cc-capital-voided">Voided</span>
                                <small class="cc-capital-void-reason">{{ $entry->void_reason }}</small>
                            @else
                                <details class="cc-capital-correction">
                                    <summary>Correct entry</summary>
                                    <form method="POST" action="{{ route('admin.investments.void', $entry->id) }}">
                                        @csrf
                                        <label for="void-reason-{{ $entry->id }}">Reason</label>
                                        <input id="void-reason-{{ $entry->id }}" name="void_reason" type="text" maxlength="180" minlength="3" required placeholder="Incorrect amount…">
                                        <button type="submit" class="cc-capital-button cc-capital-button-light">Void entry</button>
                                    </form>
                                </details>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4">No capital recorded for this month yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $history->links() }}
    </section>
</div>

<section class="a-panel cc-capital-panel cc-capital-comparison" aria-labelledby="capital-comparison-title">
    <div class="cc-capital-chart-head">
        <div><h2 id="capital-comparison-title">Capital and paid sales</h2><p>Last 6 calendar months</p></div>
        <div class="cc-capital-key"><span><i class="cc-capital-dot cc-capital-dot-capital"></i> Capital</span><span><i class="cc-capital-dot cc-capital-dot-sales"></i> Paid sales</span></div>
    </div>
    <div class="cc-capital-chart" aria-hidden="true">
        @foreach($comparison as $row)
            <div class="cc-capital-chart-month">
                <div class="cc-capital-chart-pair">
                    <div class="cc-capital-chart-bar cc-capital-chart-bar-capital" style="height: {{ round($row['capital'] / $maxComparison * 100, 2) }}%"></div>
                    <div class="cc-capital-chart-bar cc-capital-chart-bar-sales" style="height: {{ round($row['revenue'] / $maxComparison * 100, 2) }}%"></div>
                </div>
                <small>{{ $row['month'] }}</small>
            </div>
        @endforeach
    </div>
    <details class="cc-capital-chart-details">
        <summary>View exact monthly figures</summary>
        <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Month</th><th>Capital</th><th>Paid sales</th></tr></thead>
            <tbody>@foreach($comparison as $row)<tr><td>{{ $row['month'] }}</td><td>₱{{ number_format($row['capital'], 2) }}</td><td>₱{{ number_format($row['revenue'], 2) }}</td></tr>@endforeach</tbody>
        </table></div>
    </details>
    <p class="cc-capital-chart-note">Paid sales use verified payments. Capital is funding received, not an expense. This chart does not calculate profit.</p>
</section>
@endsection
