@extends('layouts.admin')
@section('title', 'Stock History')
@section('content')
<div class="a-page-head ai-heading"><div><p class="a-eyebrow">INVENTORY / AUDIT LOG</p><h1>Stock history</h1><p>Review every recorded stock addition and deduction.</p></div><a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.index') }}">← Inventory</a></div>
@include('admin.inventory.partials.nav')
<section class="a-panel ai-panel">
    <form method="GET" action="{{ route('admin.inventory.logs') }}" class="ai-filter">
        <label class="ai-field" for="log-ingredient"><span>Ingredient</span><select id="log-ingredient" name="ingredient"><option value="">All ingredients</option>@foreach($ingredients as $item)<option value="{{ $item->ingredient_id }}" @selected((string)$ingredientId === (string)$item->ingredient_id)>{{ $item->ingredient_name }}</option>@endforeach</select></label>
        <button type="submit" class="ai-button ai-button-primary">Filter history</button>
        <a href="{{ route('admin.inventory.logs') }}" class="ai-button ai-button-subtle">Reset</a>
    </form>
    @if($logs->isEmpty())
        <div class="a-empty"><span aria-hidden="true">▤</span><strong>No stock changes recorded</strong><p>Opening balances and stock adjustments will appear here.</p></div>
    @else
        <div class="a-table-wrap"><table class="a-table ai-table">
            <thead><tr><th scope="col">Date</th><th scope="col">Ingredient</th><th scope="col">Change</th><th scope="col">Reason</th><th scope="col">Updated by</th></tr></thead>
            <tbody>@foreach($logs as $log)<tr>
                <td>{{ $log->log_date?->format('M d, Y · h:i A') }}</td>
                <td><strong>{{ $log->ingredient?->ingredient_name ?? 'Deleted ingredient' }}</strong><small>{{ $log->log_type }}</small></td>
                <td><strong class="{{ (float)$log->quantity_changed >= 0 ? 'ai-positive' : 'ai-negative' }}">{{ (float)$log->quantity_changed > 0 ? '+' : '' }}{{ number_format((float)$log->quantity_changed, 2) }} {{ $log->ingredient?->unit_of_measure }}</strong></td>
                <td class="ai-wrap-cell">{{ $log->reason ?: '—' }}</td>
                <td>{{ $log->admin?->full_name ?? 'Unknown admin' }}</td>
            </tr>@endforeach</tbody>
        </table></div>
        @if($logs->hasPages())<div class="a-pagination">@if($logs->onFirstPage())<span aria-disabled="true">← Previous</span>@else<a href="{{ $logs->previousPageUrl() }}">← Previous</a>@endif<span>Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</span>@if($logs->hasMorePages())<a href="{{ $logs->nextPageUrl() }}">Next →</a>@else<span aria-disabled="true">Next →</span>@endif</div>@endif
    @endif
</section>
@endsection
