@extends('layouts.admin')
@section('title', $ingredient->exists ? 'Edit Ingredient' : 'Add Ingredient')
@section('content')
<div class="a-page-head ai-heading">
    <div><p class="a-eyebrow">INVENTORY / INGREDIENTS</p><h1>{{ $ingredient->exists ? 'Edit ingredient' : 'Add ingredient' }}</h1><p>{{ $ingredient->exists ? 'Update the item details and reorder threshold.' : 'Create an ingredient and record its opening quantity.' }}</p></div>
    <a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.index') }}">← Back to inventory</a>
</div>
@include('admin.inventory.partials.nav')
<section class="a-panel ai-form-panel">
    @if($errors->any())<div class="ai-error" role="alert"><strong>Please check these fields:</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" action="{{ $ingredient->exists ? route('admin.inventory.update', $ingredient->ingredient_id) : route('admin.inventory.store') }}" class="ai-ingredient-form">
        @csrf
        @if($ingredient->exists)@method('PUT')@endif
        <label class="ai-field ai-wide" for="ing-name"><span>Ingredient name *</span><input id="ing-name" name="ingredient_name" maxlength="120" value="{{ old('ingredient_name', $ingredient->ingredient_name) }}" placeholder="e.g. Chicken Breast or Coke 330 mL bottle" required></label>
        <label class="ai-field" for="ing-unit"><span>Unit of measure *</span><select id="ing-unit" name="unit_of_measure" required>
            <option value="">Choose a unit</option>
            @foreach($units as $unit)<option value="{{ $unit }}" @selected(old('unit_of_measure', $ingredient->unit_of_measure) === $unit)>{{ $unit }}</option>@endforeach
        </select></label>
        <label class="ai-field" for="ing-threshold"><span>Reorder threshold *</span><input id="ing-threshold" name="reorder_level" type="number" step="0.01" min="0" max="99999999.99" value="{{ old('reorder_level', $ingredient->reorder_level ?? '0') }}" required><small>An alert appears when stock reaches this amount or less.</small></label>
        @unless($ingredient->exists)
            <label class="ai-field ai-wide" for="ing-opening"><span>Opening stock *</span><input id="ing-opening" type="number" name="opening_stock" step="0.01" min="0" max="99999999.99" value="{{ old('opening_stock', '0') }}" required><small>The starting balance is recorded in Stock History if greater than zero. For a bottled drink, choose the unit bottles or pcs.</small></label>
        @else
            <div class="ai-edit-stock ai-wide"><strong>Current balance: {{ number_format((float)$ingredient->current_stock, 2) }} {{ $ingredient->unit_of_measure }}</strong><p>For accountability, stock balance is changed using the <a href="{{ route('admin.inventory.index') }}">Adjust stock</a> form rather than here.</p></div>
        @endunless
        <div class="ai-form-actions ai-wide"><button type="submit" class="ai-button ai-button-primary">{{ $ingredient->exists ? 'Save changes' : 'Add ingredient' }}</button><a href="{{ route('admin.inventory.index') }}" class="ai-button ai-button-subtle">Cancel</a></div>
    </form>
</section>
@endsection
