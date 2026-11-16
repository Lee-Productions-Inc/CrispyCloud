@php
    $isPendingUnpaid = $order->order_type === 'Delivery'
        && $order->order_status === 'Pending'
        && $order->payment?->payment_status === 'Pending';
    $isSupportedPayment = in_array($order->payment?->payment_method, ['COD', 'GCash'], true);
    $gcashReferenceProvided = $order->payment?->payment_method === 'GCash'
        && filled($order->payment?->reference_number);
@endphp

@if($isPendingUnpaid && $isSupportedPayment && !$gcashReferenceProvided)
    <form method="POST" action="{{ route('customer.orders.cancel', $order->order_id) }}"
          class="cco-cancel-form"
          onsubmit="return confirm('Cancel this Pending order? This cannot be undone. If you have sent a GCash payment, contact the store instead.');">
        @csrf
        @method('PATCH')
        <button type="submit" class="cco-button cco-button--danger-outline"
                aria-label="Cancel order #CP-{{ $order->order_id }}">Cancel order</button>
    </form>
@elseif($isPendingUnpaid && $gcashReferenceProvided)
    <span class="cco-cancel-help">GCash reference submitted — contact the store to request cancellation.</span>
@endif
