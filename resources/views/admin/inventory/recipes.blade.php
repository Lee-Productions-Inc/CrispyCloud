@extends('layouts.admin')
@section('title', 'Inventory · Recipe Setup')
@section('content')
<div class="a-page-head ai-heading">
    <div><p class="a-eyebrow">INVENTORY / RECIPE SETUP</p><h1>Recipe Setup</h1><p>Choose what stock one product, food extra, or removable ingredient uses.</p></div>
    <a href="{{ route('admin.inventory.index') }}" class="ai-button ai-button-subtle">Back to Stock</a>
</div>
@include('admin.inventory.partials.nav')

<div class="air-hint"><strong>When is stock deducted?</strong> When an online order is marked <strong>Delivered</strong> or a paid walk-in sale is marked <strong>Fulfilled</strong>. The system uses <strong>product recipe + selected food extras − removed ingredients</strong>, multiplied by item quantity. No stock is deducted just for adding to cart.</div>

<section class="a-panel ai-panel air-recipe-panel" aria-label="Configure inventory recipe">
    <div class="air-step-head"><span class="air-step-number">1</span><div><h2>Choose what you're mapping</h2><p>Beverages are products, not add-ons. Set their stock under Products → Beverages.</p></div></div>
    <nav class="air-choice-tabs" aria-label="Recipe type">
        @foreach(['product' => 'Products', 'addon' => 'Food Extras', 'component' => 'Removed Ingredients'] as $code => $label)
            <a class="{{ $type === $code ? 'active' : '' }}" href="{{ route('admin.inventory.recipes.index', ['type' => $code]) }}" @if($type === $code) aria-current="page" @endif>{{ $label }}</a>
        @endforeach
    </nav>

    @if($type === 'product')
        <nav class="air-choice-tabs air-sub-tabs" aria-label="Product type">
            @foreach(['all' => 'All Products', 'food' => 'Food', 'beverages' => 'Beverages'] as $code => $label)
                <a class="{{ $section === $code ? 'active' : '' }}" href="{{ route('admin.inventory.recipes.index', ['type' => 'product', 'section' => $code]) }}" @if($section === $code) aria-current="page" @endif>{{ $label }}</a>
            @endforeach
        </nav>
    @endif

    <div class="air-setup-progress"><strong>{{ $configuredCount }} of {{ $choiceCount }} configured</strong><span>in this view</span></div>
    @if($errors->any())<div class="ai-error" role="alert"><strong>Recipe not saved</strong><ul>@foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul></div>@endif

    @if($choices->isEmpty())
        <div class="a-empty"><strong>No items to configure in this section</strong><p>Add products under Admin → Products, or food extras and removable ingredients under Categories & Extras.</p></div>
    @else
        <form class="ai-filter air-target-filter" action="{{ route('admin.inventory.recipes.index') }}" method="GET">
            <input type="hidden" name="type" value="{{ $type }}">
            @if($type === 'product')<input type="hidden" name="section" value="{{ $section }}">@endif
            <label class="ai-field" for="air-target"><span>{{ $type === 'product' ? 'Product' : ($type === 'addon' ? 'Food extra' : 'Removable ingredient') }}</span>
                <select id="air-target" name="id" required>
                    @foreach($choices as $choice)
                        @php $label = $type === 'component' ? ($choice->product?->name ?? 'Product') . ' — ' . $choice->component_name : $choice->name; @endphp
                        <option value="{{ $choice->getKey() }}" @selected($target?->getKey() === $choice->getKey())>{{ $label }} · {{ $choice->inventory_recipe_configured ? 'Configured' : 'Needs setup' }}</option>
                    @endforeach
                </select>
            </label>
            <button class="ai-button ai-button-primary" type="submit">Open recipe</button>
        </form>

        @if($target)
            @php
                $formRoute = match($type) {
                    'addon' => route('admin.inventory.recipes.addons.update', $target->getKey()),
                    'component' => route('admin.inventory.recipes.components.update', $target->getKey()),
                    default => route('admin.inventory.recipes.update', $target->getKey()),
                };
                $emptyConfigured = (bool) $target->inventory_recipe_configured && empty($recipes);
                $title = $type === 'component' ? ($target->product?->name ?? 'Product') . ' — ' . $target->component_name : $target->name;
                $beverage = $type === 'product' && \App\Support\MenuSections::isBeverageCategory($target->category?->name);
            @endphp
            <div class="air-recipe-divider"></div>
            <div class="air-step-head"><span class="air-step-number">2</span><div><h2>{{ $title }}</h2>
                <p>@if($target->inventory_recipe_configured)<span class="air-readiness ready">✓ Recipe configured</span>@else<span class="air-readiness unready">Needs recipe setup</span>@endif
                    · Quantities apply to <strong>one</strong> {{ $type === 'component' ? 'removed component' : ($type === 'addon' ? 'extra' : 'product') }}.</p>
            </div></div>
            <div class="air-context-note">
                @if($type === 'product' && $beverage)
                    <strong>Beverage example:</strong> A bottle of Coke should consume <strong>1.00 bottle</strong> of an ingredient named “Coke 330 mL bottle” (unit: bottles). Prepared drinks may use liquid and cup ingredients instead.
                @elseif($type === 'product')
                    <strong>Food example:</strong> A chicken-and-rice meal could consume 0.20 kg chicken and 0.15 kg rice. Use your business's confirmed amounts.
                @elseif($type === 'addon')
                    <strong>Food extra example:</strong> One Extra Rice adds 0.15 kg rice. Drinks should be created as separate products under Beverages.
                @else
                    <strong>Removal example:</strong> If the base meal includes 0.15 kg rice, removing rice subtracts that 0.15 kg from the meal's consumption. It cannot exceed what the base recipe uses.
                @endif
            </div>
            <form method="POST" action="{{ $formRoute }}" id="air-recipe-form">
                @csrf @method('PUT')
                @if($ingredients->isEmpty())
                    <div class="a-empty"><strong>No ingredients yet</strong><p><a href="{{ route('admin.inventory.create') }}">Add ingredients first</a>, unless this item intentionally consumes no tracked stock.</p></div>
                @else
                    <div class="air-step-head air-step-spaced"><span class="air-step-number">3</span><div><h3>Enter ingredient usage</h3><p>Enter only the amounts this item consumes. Leave every other ingredient at 0.</p></div></div>
                    <label class="ai-field air-ingredient-search" for="air-ingredient-search"><span>Find an ingredient in this list</span><input id="air-ingredient-search" type="search" placeholder="Search chicken, rice, Coke bottle…" autocomplete="off"></label>
                    <div class="a-table-wrap"><table class="a-table ai-table ai-recipe-table" id="air-recipe-table">
                        <thead><tr><th scope="col">Ingredient</th><th scope="col">Stock on hand</th><th scope="col">Used per item</th></tr></thead>
                        <tbody>@foreach($ingredients as $ingredient)
                            <tr><td><strong>{{ $ingredient->ingredient_name }}</strong><small>Unit: {{ $ingredient->unit_of_measure }}</small></td>
                                <td>{{ number_format((float)$ingredient->current_stock, 2) }} {{ $ingredient->unit_of_measure }}</td>
                                <td><label class="ai-recipe-field"><span class="ai-sr-only">{{ $ingredient->ingredient_name }} used per item</span>
                                    <input data-stock-amount type="number" name="quantities[{{ $ingredient->ingredient_id }}]" min="0" max="9999999.99" step="0.01" inputmode="decimal" value="{{ old('quantities.'.$ingredient->ingredient_id, $recipes[$ingredient->ingredient_id] ?? '0') }}" required>
                                    <span>{{ $ingredient->unit_of_measure }}</span>
                                </label></td>
                            </tr>
                        @endforeach</tbody>
                    </table></div>
                @endif
                <div class="air-no-effect">
                    <label for="air-no-effect-checkbox"><input id="air-no-effect-checkbox" type="checkbox" name="no_stock_effect" value="1" @checked(old('no_stock_effect') || ($emptyConfigured && !old('quantities')))>
                        <span><strong>No stock effect</strong><small>Only for items that intentionally consume no tracked ingredients. This option clears the quantities above when selected.</small></span>
                    </label>
                </div>
                <label class="air-confirm"><input type="checkbox" name="confirmed_recipe" value="1" required><span>I reviewed the amounts and units for this item.</span></label>
                <div class="ai-form-actions"><button class="ai-button ai-button-primary" type="submit">Save recipe</button><a class="ai-button ai-button-subtle" href="{{ route('admin.inventory.index') }}">Cancel</a></div>
            </form>
        @endif
    @endif
</section>
<div class="air-hint"><strong>Note:</strong> Updated recipes apply to <em>future</em> fulfillments. Past deductions are not recalculated or repeated. Use <a href="{{ route('admin.inventory.fulfillments') }}">Delivered Order Audit</a> to check the historical stock entries.</div>
<script>
(function () {
    const noEffect = document.getElementById('air-no-effect-checkbox');
    const amounts = document.querySelectorAll('#air-recipe-table [data-stock-amount]');
    const search = document.getElementById('air-ingredient-search');
    if (search) search.addEventListener('input', () => {
        const term = search.value.trim().toLocaleLowerCase();
        document.querySelectorAll('#air-recipe-table tbody tr').forEach(row => {
            row.hidden = !row.textContent.toLocaleLowerCase().includes(term);
        });
    });
    if (!noEffect) return;
    function sync() {
        if (noEffect.checked) amounts.forEach(input => { input.value = '0'; input.disabled = true; });
        else amounts.forEach(input => { input.disabled = false; });
    }
    noEffect.addEventListener('change', sync);
    if (noEffect.checked) sync();
})();
</script>
@endsection
