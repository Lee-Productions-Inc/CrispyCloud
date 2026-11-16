@php
    $operator = config('commerce_legal.operator_name');
    $address = config('commerce_legal.business_address');
    $email = config('commerce_legal.contact_email');
    $phone = config('commerce_legal.contact_phone');
@endphp
<dl class="policy-contacts">
    <div><dt>Store</dt><dd>{{ config('commerce_legal.store_name') }}</dd></div>
    <div><dt>Business operator / data controller</dt><dd>{{ $operator ?: 'To be provided by the store' }}</dd></div>
    <div><dt>Business address</dt><dd>{{ $address ?: 'To be provided by the store' }}</dd></div>
    <div><dt>Email / privacy requests</dt><dd>@if($email)<a href="mailto:{{ $email }}">{{ $email }}</a>@else To be provided by the store @endif</dd></div>
    <div><dt>Phone</dt><dd>{{ $phone ?: 'To be provided by the store' }}</dd></div>
</dl>
@if(!$operator || !$address || !$email || !$phone)
    <p class="policy-notice"><strong>Publication setup required:</strong> The store must enter its genuine operator name, business address, email and phone number before making this website publicly available.</p>
@endif
