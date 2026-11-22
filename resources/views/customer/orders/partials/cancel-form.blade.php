@php
    $isPreparingUnpaid = $order->order_type === 'Delivery'
        && $order->order_status === 'Preparing'
        && $order->payment?->payment_status === 'Pending';
    $isSupportedPayment = in_array($order->payment?->payment_method, ['COD', 'GCash'], true);
    $gcashReferenceProvided = $order->payment?->payment_method === 'GCash'
        && filled($order->payment?->reference_number);
@endphp

@if($isPreparingUnpaid && $isSupportedPayment && !$gcashReferenceProvided)
    <form method="POST" action="{{ route('customer.orders.cancel', $order->order_id) }}"
          class="cco-cancel-form">
        @csrf
        @method('PATCH')
        <button type="submit" class="cco-button cco-button--danger-outline"
                aria-label="Cancel order #CP-{{ $order->order_id }}">Cancel order</button>
    </form>
@elseif($isPreparingUnpaid && $gcashReferenceProvided)
    <span class="cco-cancel-help">GCash reference submitted — contact the store to request cancellation.</span>
@endif
