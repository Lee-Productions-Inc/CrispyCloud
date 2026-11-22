@extends('layouts.customer')

@section('title', "My Account | Chick's Pops")
@section('body_class', 'cc-profile-page')
@section('page_assets')

@endsection

@php
$initial = mb_strtoupper(mb_substr(trim($customer->full_name ?: 'C'), 0, 1));
@endphp

@section('content')
<main id="main-content" class="ccp-page">
    <div class="ccp-container">
        <header class="ccp-page-heading">
            <div>
                <p class="ccp-eyebrow">YOUR ACCOUNT</p>
                <h1>Profile &amp; addresses</h1>
                <p>Keep your details and delivery addresses in one place.</p>
            </div>
            <a class="ccp-back-link" href="{{ route('customer.menu') }}">&larr; Back to menu</a>
        </header>

        @if(session('success'))
        <div class="ccp-message ccp-message-success" role="status">{{ session('success') }}</div>
        @endif
        @if($errors->any())
        <div class="ccp-message ccp-message-error" role="alert">
            <strong>Some information needs attention.</strong>
            <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
        @endif

        <div class="ccp-grid">
            <aside class="ccp-sidebar" aria-label="Account sections">
                <div class="ccp-sidebar-intro">
                    <div class="ccp-avatar" aria-hidden="true">{{ $initial }}</div>
                    <div>
                        <strong>{{ $customer->full_name }}</strong>
                        <span>Customer account</span>
                    </div>
                </div>
                <nav aria-label="Account navigation" class="ccp-side-nav">
                    <a href="#my-profile"><span aria-hidden="true">01</span> Personal information</a>
                    <a href="#addresses"><span aria-hidden="true">02</span> Delivery addresses</a>
                    <a href="#payment-info"><span aria-hidden="true">03</span> Payment information</a>
                    <a href="{{ route('customer.orders.index') }}"><span aria-hidden="true">↗</span> My orders</a>
                </nav>
                <p class="ccp-sidebar-tip">Need to track an order? Find its status in <a href="{{ route('customer.orders.index') }}">My Orders</a>.</p>
            </aside>

            <div class="ccp-content">
                <section id="my-profile" class="ccp-panel" aria-labelledby="ccp-personal-title">
                    <div class="ccp-panel-head">
                        <div>
                            <p class="ccp-kicker">ACCOUNT DETAILS</p>
                            <h2 id="ccp-personal-title">Personal information</h2>
                            <p>Used for identifying and contacting you about your orders.</p>
                        </div>
                    </div>

                    <dl class="ccp-details-grid">
                        <div>
                            <dt>Full name</dt>
                            <dd>{{ $customer->full_name }}</dd>
                        </div>
                        <div>
                            <dt>Email address</dt>
                            <dd>{{ $customer->email }}</dd>
                        </div>
                        <div>
                            <dt>Phone number</dt>
                            <dd>{{ $customer->phone_number ?: 'Not added yet' }}</dd>
                        </div>
                    </dl>

                    <details class="ccp-disclosure" @if($errors->has('full_name') || $errors->has('email') || $errors->has('phone_number')) open @endif>
                        <summary><span>Edit personal information</span><span class="ccp-disclosure-caret" aria-hidden="true"></span></summary>
                        <form method="POST" action="{{ route('customer.profile.update') }}" class="ccp-form">
                            @csrf
                            @method('PATCH')
                            <div class="ccp-field"><label for="ccp-name">Full name</label><input id="ccp-name" type="text" name="full_name" value="{{ old('full_name', $customer->full_name) }}" autocomplete="name" maxlength="255" required></div>
                            <div class="ccp-field"><label for="ccp-email">Email address</label><input id="ccp-email" type="email" name="email" value="{{ old('email', $customer->email) }}" autocomplete="email" maxlength="255" required></div>
                            <div class="ccp-field"><label for="ccp-phone">Phone number <span class="ccp-optional">(optional)</span></label><input id="ccp-phone" type="tel" name="phone_number" value="{{ old('phone_number', $customer->phone_number) }}" autocomplete="tel" maxlength="20"></div>
                            <div class="ccp-form-actions"><button class="ccp-button" type="submit">Save changes</button></div>
                        </form>
                    </details>
                </section>

                <section id="addresses" class="ccp-panel" aria-labelledby="ccp-addresses-title">
                    <div class="ccp-panel-head">
                        <div>
                            <p class="ccp-kicker">DELIVERY</p>
                            <h2 id="ccp-addresses-title">Saved addresses</h2>
                            <p>Your default address will be suggested automatically at checkout.</p>
                        </div>
                        <a class="ccp-inline-link" href="#new-address">Add address &darr;</a>
                    </div>

                    <div class="ccp-address-list">
                        @forelse($customer->addresses as $address)
                        <article class="ccp-address-card">
                            <div class="ccp-address-top">
                                <div>
                                    <div class="ccp-address-name">
                                        <h3>{{ $address->is_default ? 'Default delivery address' : 'Saved address #' . $address->address_id }}</h3>
                                        @if($address->is_default)<span class="ccp-pill">Default</span>@endif
                                    </div>
                                    <address>{{ $address->address_line }}<br>{{ $address->city }}, {{ $address->postal_code }}</address>
                                </div>
                            </div>
                            <div class="ccp-address-actions">
                                @unless($address->is_default)
                                <form method="POST" action="{{ route('customer.addresses.default', $address->address_id) }}">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="ccp-action-button">Set as default</button>
                                </form>
                                @endunless
                                <form method="POST" action="{{ route('customer.addresses.destroy', $address->address_id) }}">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="ccp-action-button ccp-danger">Remove address</button>
                                </form>
                            </div>
                            <details class="ccp-disclosure ccp-address-edit">
                                <summary><span>Edit address</span><span class="ccp-disclosure-caret" aria-hidden="true"></span></summary>
                                <form method="POST" action="{{ route('customer.addresses.update', $address->address_id) }}" class="ccp-form">
                                    @csrf @method('PATCH')
                                    <div class="ccp-field"><label for="address-line-{{ $address->address_id }}">House number, street &amp; barangay</label><input id="address-line-{{ $address->address_id }}" type="text" name="address_line" value="{{ $address->address_line }}" maxlength="255" autocomplete="street-address" required></div>
                                    <div class="ccp-form-row">
                                        <div class="ccp-field"><label for="city-{{ $address->address_id }}">City / Municipality</label><input id="city-{{ $address->address_id }}" type="text" name="city" value="{{ $address->city }}" maxlength="100" required></div>
                                        <div class="ccp-field"><label for="postal-{{ $address->address_id }}">Postal code</label><input id="postal-{{ $address->address_id }}" type="text" name="postal_code" value="{{ $address->postal_code }}" maxlength="20" inputmode="numeric" required></div>
                                    </div>
                                    <div class="ccp-form-actions"><button type="submit" class="ccp-button">Save address</button></div>
                                </form>
                            </details>
                        </article>
                        @empty
                        <div class="ccp-empty"><strong>No saved addresses yet</strong>
                            <p>Add an address to make checkout quicker next time.</p>
                        </div>
                        @endforelse
                    </div>

                    <details id="new-address" class="ccp-disclosure ccp-add-address" @if($errors->has('address_line') || $errors->has('city') || $errors->has('postal_code')) open @endif>
                        <summary><span>+ Add a new delivery address</span><span class="ccp-disclosure-caret" aria-hidden="true"></span></summary>
                        <form method="POST" action="{{ route('customer.addresses.store') }}" class="ccp-form">
                            @csrf
                            <div class="ccp-field"><label for="new-address-line">House number, street &amp; barangay</label><input id="new-address-line" type="text" name="address_line" value="{{ old('address_line') }}" maxlength="255" placeholder="House number, street, barangay" autocomplete="street-address" required></div>
                            <div class="ccp-form-row">
                                <div class="ccp-field"><label for="new-city">City / Municipality</label><input id="new-city" type="text" name="city" value="{{ old('city') }}" maxlength="100" required></div>
                                <div class="ccp-field"><label for="new-postal">Postal code</label><input id="new-postal" type="text" name="postal_code" value="{{ old('postal_code') }}" maxlength="20" inputmode="numeric" required></div>
                            </div>
                            <label class="ccp-check"><input type="checkbox" name="make_default" value="1" @checked(old('make_default'))><span>Make this my default delivery address</span></label>
                            <div class="ccp-form-actions"><button type="submit" class="ccp-button">Save new address</button></div>
                        </form>
                    </details>
                </section>

                <section id="payment-info" class="ccp-panel ccp-payment-panel" aria-labelledby="ccp-payment-title">
                    <div class="ccp-panel-head">
                        <div>
                            <p class="ccp-kicker">PAYMENT</p>
                            <h2 id="ccp-payment-title">Payment information</h2>
                            <p>Choose your preferred option when you place an order.</p>
                        </div>
                    </div>
                    <div class="ccp-payment-options"><span>Cash on Delivery (COD)</span><span>GCash (manual reference)</span></div>
                    <p class="ccp-helper">No payment card or GCash login credentials are saved here. GCash is not an automatic payment gateway in this demo.</p>
                </section>

                <form method="POST"
                    action="{{ route('logout') }}"
                    class="ccp-mobile-signout">
                    @csrf

                    <button type="submit" class="ccp-mobile-signout-btn">
                        Sign Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</main>
@endsection