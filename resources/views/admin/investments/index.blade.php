@extends('layouts.admin')
@section('title', 'Product Investments')
@section('content')
<div class="a-page-head">
    <div><p class="a-eyebrow">FINANCIAL RECORDS</p><h1>Product Investments</h1></div>
    <a href="{{ route('admin.reports.index') }}" class="a-text-link">← Sales & Reports</a>
</div>
<section class="a-stats" aria-label="Product investment totals">
    <article class="a-stat"><div class="a-stat-top"><span>Recorded product capital</span></div><strong>₱{{ number_format($totalInvested, 2) }}</strong></article>
</section>
<div class="a-lower-grid">
    <section class="a-panel" style="padding:24px">
        <h2>Add investment</h2>
        @if($errors->any())<div class="a-alert a-alert-error" role="alert">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('admin.investments.store') }}" style="display:grid;gap:16px;margin-top:20px">
            @csrf
            <label>Product
                <select name="product_id" required style="width:100%;padding:12px;border:1px solid #d6d9df;border-radius:8px">
                    <option value="">Choose product</option>
                    @foreach($products as $product)<option value="{{ $product->product_id }}" @selected(old('product_id') == $product->product_id)>{{ $product->name }}</option>@endforeach
                </select>
            </label>
            <label>Amount invested (₱)
                <input name="amount" type="number" step="0.01" min="0.01" max="9999999999.99" value="{{ old('amount') }}" required style="width:100%;padding:12px;border:1px solid #d6d9df;border-radius:8px">
            </label>
            <label>Date
                <input name="invested_on" type="date" max="{{ now()->format('Y-m-d') }}" value="{{ old('invested_on', now()->format('Y-m-d')) }}" required style="width:100%;padding:12px;border:1px solid #d6d9df;border-radius:8px">
            </label>
            <label>Note (optional)
                <input name="note" maxlength="255" value="{{ old('note') }}" placeholder="e.g. ingredients for chicken pops" style="width:100%;padding:12px;border:1px solid #d6d9df;border-radius:8px">
            </label>
            <button class="a-logout" type="submit" style="background:#b91c1c;color:#fff;padding:12px 18px;border:0;border-radius:8px;cursor:pointer">Record investment</button>
        </form>
    </section>
    <section class="a-panel" style="padding:24px">
        <h2>Recent investments</h2>
        <div class="a-table-wrap"><table class="a-table"><thead><tr><th>Date</th><th>Product</th><th>Amount</th><th>Note</th></tr></thead>
            <tbody>@forelse($investments as $investment)<tr>
                <td>{{ \Carbon\Carbon::parse($investment->invested_on)->format('M d, Y') }}</td><td>{{ $investment->product_name }}</td>
                <td>₱{{ number_format((float)$investment->amount,2) }}</td><td>{{ $investment->note ?: '—' }}</td>
            </tr>@empty<tr><td colspan="4">No investments recorded yet.</td></tr>@endforelse</tbody>
        </table></div>
        {{ $investments->links() }}
    </section>
</div>
@endsection
