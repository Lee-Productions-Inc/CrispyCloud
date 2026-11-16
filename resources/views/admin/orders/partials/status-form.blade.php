@php
    // Present only valid destinations; a single action can skip intermediate stages.
    $statusStages = ['Pending', 'Preparing', 'Out for Delivery', 'Delivered'];
    $position = array_search($order->order_status, $statusStages, true);
    $availableStatuses = ($position === false || $order->order_status === 'Delivered')
        ? [] : array_slice($statusStages, $position + 1);

    if ($position !== false && $order->order_status !== 'Delivered' && $order->order_status !== 'Out for Delivery' && $order->payment?->payment_status !== 'Paid') {
        $availableStatuses[] = 'Cancelled';
    }

    $context = $context ?? 'show';
    $selectId = 'order-status-' . $order->order_id . '-' . $context;
@endphp

@if(count($availableStatuses) > 0)
    <form method="POST" action="{{ route('admin.orders.status', $order->order_id) }}"
          class="a-os-status-form {{ $context === 'index' ? 'a-os-inline' : 'a-os-details-form' }}"
          onsubmit="return !['Delivered', 'Cancelled'].includes(this.elements['order_status'].value) || confirm('Confirm this final order status? It cannot be undone.');">
        @csrf
        @method('PATCH')
        <input type="hidden" name="current_status" value="{{ $order->order_status }}">
        <input type="hidden" name="return_to" value="{{ $context }}">
        @if($context === 'index')
            <input type="hidden" name="list_filters[status]" value="{{ $filter }}">
            <input type="hidden" name="list_filters[payment]" value="{{ $paymentFilter }}">
            <input type="hidden" name="list_filters[q]" value="{{ $search }}">
            <input type="hidden" name="list_filters[page]" value="{{ $orders->currentPage() }}">
        @endif
        <label for="{{ $selectId }}" class="{{ $context === 'index' ? 'a-os-visually-hidden' : 'a-os-select-label' }}">
            New status for order #CP-{{ $order->order_id }}
        </label>
        <div class="a-os-controls">
            <select id="{{ $selectId }}" name="order_status" required aria-label="Select new status for order #CP-{{ $order->order_id }}">
                <option value="" selected disabled>Select status…</option>
                @foreach($availableStatuses as $status)
                    <option value="{{ $status }}">{{ $status }}</option>
                @endforeach
            </select>
            <button class="a-om-button a-om-button--primary" type="submit">Update</button>
        </div>
        @if($context === 'show')
            <p class="a-om-hint">Pick a later stage directly. <strong>Delivered automatically deducts inventory</strong> using configured product, add-on, and removal recipes. Missing recipes or insufficient stock prevent delivery. Delivered and Cancelled are final; payments are recorded separately.</p>
        @endif
    </form>
@else
    <div class="a-os-locked" aria-label="No further status changes allowed">Final status · {{ $order->order_status }}</div>
@endif
