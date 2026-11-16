@php
    $formatMoney = fn($value): string => '₱' . number_format((float) $value, 2);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Order Confirmed | Chick's Pops</title>
    @vite(['resources/css/app.css', 'resources/css/customer/customize.css', 'resources/css/customer/checkout.css'])
</head>
<body class="cc-product-page cc-checkout-page">
    <main class="cc-order-confirmation">
        <p class="cc-order-success-mark" aria-hidden="true">✓</p>
        <h1>ORDER PLACED!</h1>
        @if(session('success'))<p class="cc-confirm-message">{{ session('success') }}</p>@endif
        <p>Order <strong>#CC-{{ str_pad($order->order_id, 5, '0', STR_PAD_LEFT) }}</strong></p>
        <p>Status: <strong>{{ $order->order_status }}</strong> · Payment: <strong>{{ $order->payment?->payment_status ?? 'Pending' }}</strong></p>
        <section class="cc-confirm-panel">
            <h2>YOUR ITEMS</h2>
            @foreach($order->items as $item)
                <div class="cc-confirm-item">
                    <div>
                        <strong>{{ $item->product?->name ?? 'Product' }} × {{ $item->quantity }}</strong>
                        @foreach($item->customizations as $customization)
                            <p>+ {{ $customization->addOn?->name ?? 'Add-on' }} × {{ $customization->addon_quantity }}</p>
                        @endforeach
                        @foreach($item->removals as $removal)
                            <p>− No {{ $removal->component?->component_name ?? 'Component' }}</p>
                        @endforeach
                        @if($item->item_notes)<p>{{ $item->item_notes }}</p>@endif
                    </div>
                    <strong>{{ $formatMoney($item->subtotal) }}</strong>
                </div>
            @endforeach
            <div class="cc-confirm-item cc-confirm-amount"><strong>Total including delivery</strong><strong>{{ $formatMoney($order->total_amount) }}</strong></div>
        </section>
        <section class="cc-confirm-panel">
            <h2>DELIVERY TO</h2>
            <p>{{ $order->delivery?->recipient_name }} · {{ $order->delivery?->recipient_phone }}</p>
            <p>{{ $order->delivery?->address_line }}, {{ $order->delivery?->city }}, {{ $order->delivery?->postal_code }}</p>
            <p>Payment method: {{ $order->payment?->payment_method }}</p>
        </section>
        <a class="cc-checkout-back" href="{{ url('/') }}">← Back to Home</a>
         <p><a href="{{ route('customer.orders.index') }}">VIEW MY ORDERS</a></p>

    </main>
</body>
</html>
