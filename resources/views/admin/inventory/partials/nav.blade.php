<nav class="air-nav" aria-label="Inventory sections">
    <a class="air-nav-link {{ request()->routeIs('admin.inventory.index', 'admin.inventory.create', 'admin.inventory.edit') ? 'is-current' : '' }}"
       href="{{ route('admin.inventory.index') }}" @if(request()->routeIs('admin.inventory.index', 'admin.inventory.create', 'admin.inventory.edit')) aria-current="page" @endif>Ingredients & Stock</a>
    <a class="air-nav-link {{ request()->routeIs('admin.inventory.recipes.*') ? 'is-current' : '' }}"
       href="{{ route('admin.inventory.recipes.index') }}" @if(request()->routeIs('admin.inventory.recipes.*')) aria-current="page" @endif>Recipe Setup</a>
    <a class="air-nav-link {{ request()->routeIs('admin.inventory.fulfillments') ? 'is-current' : '' }}"
       href="{{ route('admin.inventory.fulfillments') }}" @if(request()->routeIs('admin.inventory.fulfillments')) aria-current="page" @endif>Delivered Order Audit</a>
    <a class="air-nav-link {{ request()->routeIs('admin.inventory.logs') ? 'is-current' : '' }}"
       href="{{ route('admin.inventory.logs') }}" @if(request()->routeIs('admin.inventory.logs')) aria-current="page" @endif>Stock History</a>
</nav>
