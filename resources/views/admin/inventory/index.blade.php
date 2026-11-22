@extends('layouts.admin')
@section('title', 'Inventory · Ingredients & Stock')
@section('content')
<div class="a-page-head ai-heading">
    <div>
        <p class="a-eyebrow">INVENTORY / STOCK</p>
        <h1>Ingredients & Stock</h1>
        <p>Know what is on hand, record restocks, and see which ingredients need attention.</p>
    </div>
    <a class="ai-button ai-button-primary" href="{{ route('admin.inventory.create') }}">+ Add ingredient</a>
</div>
@include('admin.inventory.partials.nav')

<div class="air-guide" aria-label="Simple inventory workflow">
    <div><span class="air-guide-number">1</span><div><strong>Add ingredients</strong><small>Use their real stock units: kg, g, bottles, or pieces.</small></div></div>
    <div><span class="air-guide-number">2</span><div><strong><a href="{{ route('admin.inventory.recipes.index') }}">Set recipes</a></strong><small>Map ingredients consumed by food and drinks.</small></div></div>
    <div><span class="air-guide-number">3</span><div><strong><a href="{{ route('admin.inventory.fulfillments') }}">Verify deliveries</a></strong><small>Check logged deductions after orders are fulfilled.</small></div></div>
</div>

<div class="ai-overview" aria-label="Current inventory summary">
    <div class="ai-stat"><span>Ingredients tracked</span><strong>{{ $totalIngredients }}</strong><small>Food supplies and beverage stock</small></div>
    <div class="ai-stat ai-stat-warning"><span>Low stock</span><strong>{{ $lowIngredients }}</strong><small>At or below the reorder level</small></div>
    <div class="ai-stat ai-stat-danger"><span>Out of stock</span><strong>{{ $outIngredients }}</strong><small>Zero remaining</small></div>
</div>

<section class="a-panel ai-panel" aria-label="Ingredients">
    <div class="ai-section-head">
        <div><h2>Stock balances</h2><p>Use <strong>Adjust stock</strong> to add supplies or record losses; editing details does not change stock.</p></div>
        <div class="ai-action-row"><a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.logs') }}">View stock history</a></div>
    </div>
    <form method="GET" action="{{ route('admin.inventory.index') }}" class="ai-filter" role="search">
        <label class="ai-field" for="ai-search"><span>Search ingredient</span><input id="ai-search" name="q" type="search" maxlength="80" value="{{ $search }}" placeholder="Rice, chicken, Coke bottle…"></label>
        <label class="ai-field" for="ai-status"><span>Stock status</span><select id="ai-status" name="status">
            @foreach(['all' => 'All stock', 'in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'] as $code => $label)
                <option value="{{ $code }}" @selected($status === $code)>{{ $label }}</option>
            @endforeach
        </select></label>
        <button class="ai-button ai-button-primary" type="submit">Filter</button>
        <a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.index') }}">Clear</a>
    </form>
    @if($errors->any())
        <div class="ai-error" role="alert"><strong>Stock was not updated</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    @if($ingredients->isEmpty())
        <div class="a-empty"><strong>No matching ingredients</strong><p>Add a new ingredient or clear the filters.</p></div>
    @else
        <div class="a-table-wrap"><table class="a-table ai-table">
            <thead><tr><th scope="col">Ingredient</th><th scope="col">Available</th><th scope="col">Reorder level</th><th scope="col">Status</th><th scope="col">Actions</th></tr></thead>
            <tbody>@foreach($ingredients as $item)
                @php
                    $stock = (float) $item->current_stock;
                    $level = (float) $item->reorder_level;
                    $stockStatus = $stock <= 0 ? 'out' : ($stock <= $level ? 'low' : 'in');
                @endphp
                <tr>
                    <td><strong>{{ $item->ingredient_name }}</strong><small>ID {{ $item->ingredient_id }} · {{ $item->unit_of_measure }}</small></td>
                    <td><strong>{{ number_format($stock, 2) }} {{ $item->unit_of_measure }}</strong></td>
                    <td>{{ number_format($level, 2) }} {{ $item->unit_of_measure }}</td>
                    <td><span class="ai-chip ai-chip-{{ $stockStatus }}">{{ ['out' => 'Out of stock', 'low' => 'Low stock', 'in' => 'In stock'][$stockStatus] }}</span></td>
                    <td><div class="ai-cell-actions"><a href="{{ route('admin.inventory.edit', $item->ingredient_id) }}" class="ai-small-link">Edit details</a><a href="{{ route('admin.inventory.logs', ['ingredient' => $item->ingredient_id]) }}" class="ai-small-link">History</a></div></td>
                </tr>
                <tr class="ai-adjust-row"><td colspan="5">
                    <details class="ai-adjust-details" @if($errors->any()) open @endif>
                        <summary>Adjust stock for {{ $item->ingredient_name }} <span aria-hidden="true">⌄</span></summary>
                        <form class="ai-adjust-form" method="POST" action="{{ route('admin.inventory.adjust', $item->ingredient_id) }}">
                            @csrf @method('PATCH')
                            <label class="ai-field"><span>Adjustment</span><select name="direction" required><option value="add">Stock In (+)</option><option value="remove">Stock Out (−)</option></select></label>
                            <label class="ai-field"><span>Quantity ({{ $item->unit_of_measure }})</span><input name="quantity" type="number" min="0.01" max="99999999.99" step="0.01" inputmode="decimal" placeholder="0.00" required></label>
                            <label class="ai-field ai-reason"><span>Reason for adjustment</span>
                                <select name="reason" required data-stock-reason><option value="">Select reason</option></select>
                            </label>
                            <label class="ai-field ai-reason" data-stock-other-wrap hidden><span>Specify reason</span><input name="reason_other" type="text" minlength="3" maxlength="450" placeholder="Enter reason" disabled></label>
                            <button class="ai-button ai-button-primary" type="submit">Save stock change</button>
                        </form>
                    </details>
                </td></tr>
            @endforeach</tbody>
        </table></div>
        @if($ingredients->hasPages())<div class="a-pagination">
            @if($ingredients->onFirstPage())<span aria-disabled="true">← Previous</span>@else<a href="{{ $ingredients->previousPageUrl() }}">← Previous</a>@endif
            <span>Page {{ $ingredients->currentPage() }} of {{ $ingredients->lastPage() }}</span>
            @if($ingredients->hasMorePages())<a href="{{ $ingredients->nextPageUrl() }}">Next →</a>@else<span aria-disabled="true">Next →</span>@endif
        </div>@endif
    @endif
</section>
<script src="{{ asset('js/admin-stock-reasons.js') }}" defer></script>
@endsection
