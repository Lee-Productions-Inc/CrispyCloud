@extends('layouts.customer')

@section('title', "Checkout | Chick's Pops")
@section('body_class', 'cc-product-page cc-checkout-page cc-commerce-page')

@section('page_assets')
    @vite('resources/js/app.js')
@endsection

@php
    $price = fn (int $cents): string => '₱' . number_format($cents / 100, 2);
    $totalQuantity = collect($items)->sum(fn ($item) => (int) ($item['quantity'] ?? 0));
@endphp

@section('content')
<main id="main-content" class="ccc-main">
    <div class="ccc-container mx-auto">
        <nav class="ccc-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('customer.menu') }}">Menu</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('customer.cart.index') }}">Cart</a>
            <span aria-hidden="true">/</span>
            <span aria-current="page">Checkout</span>
        </nav>

        <div class="ccc-page-heading">
            <div>
                <span class="ccc-eyebrow">ALMOST THERE</span>
                <h1>Checkout<span class="ccc-heading-period">.</span></h1>
                <p>Confirm your delivery details and review your payment choice before ordering.</p>
            </div>
            <a class="ccc-secondary-link" href="{{ route('customer.cart.index') }}"><span aria-hidden="true">←</span> Back to cart</a>
        </div>

        <ol class="ccc-progress flex" aria-label="Checkout progress">
            <li class="ccc-progress-complete"><span aria-hidden="true">✓</span> <span class="ccc-sr-only">Completed: </span>Cart</li>
            <li class="ccc-progress-current" aria-current="step"><span>2</span> Delivery &amp; payment</li>
            <li><span>3</span> Confirmation</li>
        </ol>

        @if($errors->any())
            <div class="ccc-notice ccc-notice-error" role="alert" id="checkout-errors">
                <strong>Please check the information below.</strong>
                <ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('customer.checkout.store') }}" class="ccc-two-column grid">
            @csrf
            <div class="ccc-checkout-left">
                <section class="ccc-card ccc-form-panel rounded-2xl" aria-labelledby="ccc-delivery-title">
                    <div class="ccc-section-title-row">
                        <div class="ccc-section-number" aria-hidden="true">01</div>
                        <div>
                            <h2 id="ccc-delivery-title">Delivery details</h2>
                            <p>We'll use these details for this specific order.</p>
                        </div>
                    </div>
                    <div class="ccc-fields-grid">
                        <div class="ccc-field">
                            <label for="recipient-name">Recipient name <span aria-hidden="true">*</span></label>
                            <input id="recipient-name" type="text" name="recipient_name" maxlength="255" autocomplete="name" value="{{ old('recipient_name', $user->full_name) }}" required>
                            @error('recipient_name')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ccc-field">
                            <label for="recipient-phone">Phone number <span aria-hidden="true">*</span></label>
                            <input id="recipient-phone" type="tel" name="recipient_phone" maxlength="30" autocomplete="tel" value="{{ old('recipient_phone', $user->phone_number) }}" required>
                            <label class="ccc-save-contact"><input type="checkbox" name="save_contact_phone" value="1" @checked(old('save_contact_phone', !$user->phone_number))><span>Save this number to my account</span></label>
                            @error('recipient_phone')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ccc-field ccc-field-full">
                            <label for="delivery-address">Street / building / barangay <span aria-hidden="true">*</span></label>
                            <input id="delivery-address" type="text" name="address_line" maxlength="255" autocomplete="street-address" value="{{ old('address_line', $defaultAddress?->address_line) }}" required>
                            @error('address_line')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ccc-field">
                            <label for="delivery-city">City / municipality <span aria-hidden="true">*</span></label>
                            <input id="delivery-city" type="text" name="city" maxlength="100" autocomplete="address-level2" value="{{ old('city', $defaultAddress?->city) }}" required>
                            @error('city')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                        <div class="ccc-field">
                            <label for="delivery-postal">Postal code <span aria-hidden="true">*</span></label>
                            <input id="delivery-postal" type="text" name="postal_code" maxlength="20" autocomplete="postal-code" value="{{ old('postal_code', $defaultAddress?->postal_code) }}" required>
                            @error('postal_code')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <p class="ccc-inline-tip">{{ $defaultAddress ? 'Your default saved address is filled in. You can edit it for this order.' : 'No default address saved yet. Enter where you want your food delivered.' }} <a href="{{ route('customer.profile.index') }}#addresses">Manage saved addresses</a></p>
                </section>

                <section class="ccc-card ccc-form-panel rounded-2xl" aria-labelledby="ccc-payment-title">
                    <div class="ccc-section-title-row">
                        <div class="ccc-section-number" aria-hidden="true">02</div>
                        <div>
                            <h2 id="ccc-payment-title">Payment method</h2>
                            <p>Choose how you'd like to pay.</p>
                        </div>
                    </div>
                    <fieldset class="ccc-payment-fieldset">
                        <legend class="ccc-sr-only">Choose a payment method</legend>
                        <div class="ccc-payment-options">
                            <label class="ccc-payment-choice">
                                <input type="radio" name="payment_method" value="COD" @checked(old('payment_method', 'COD') === 'COD') required>
                                <span class="ccc-payment-symbol" aria-hidden="true">₱</span>
                                <span class="ccc-payment-description"><strong>Cash on delivery</strong><small>Pay when your order arrives.</small></span>
                            </label>
                            <label class="ccc-payment-choice">
                                <input type="radio" name="payment_method" value="GCash" @checked(old('payment_method') === 'GCash')>
                                <span class="ccc-payment-symbol" aria-hidden="true">G</span>
                                <span class="ccc-payment-description"><strong>GCash — manual verification</strong><small>No automatic online payment is processed.</small></span>
                            </label>
                        </div>
                        @error('payment_method')<p class="ccc-field-error">{{ $message }}</p>@enderror
                    </fieldset>
                    <div id="cc-gcash-reference" class="ccc-gcash-reference" hidden>
                        <div class="ccc-field">
                            <label for="gcash-reference">GCash reference number <span class="ccc-optional">(optional)</span></label>
                            <input id="gcash-reference" type="text" name="reference_number" maxlength="100" value="{{ old('reference_number') }}" placeholder="Enter your reference, if available">
                            @error('reference_number')<p class="ccc-field-error">{{ $message }}</p>@enderror
                        </div>
                        <p class="ccc-inline-tip">Payment will remain pending until the administrator verifies it. Do not enter your GCash PIN or password.</p>
                    </div>
                </section>

                <section class="ccc-card ccc-form-panel rounded-2xl" aria-labelledby="ccc-review-title">
                    <div class="ccc-section-title-row ccc-review-heading">
                        <div>
                            <h2 id="ccc-review-title">Review your items</h2>
                            <p>{{ $totalQuantity }} {{ $totalQuantity === 1 ? 'item' : 'items' }} in this order</p>
                        </div>
                        <a class="ccc-text-link" href="{{ route('customer.cart.index') }}">Edit cart</a>
                    </div>
                    <div class="ccc-review-items">
                    @foreach($items as $item)
                        @php
                            $imagePath = 'images/products/' . \Illuminate\Support\Str::slug($item['name']) . '.png';
                            $hasImage = is_file(public_path($imagePath));
                        @endphp
                        <article class="ccc-review-item">
                            <div class="ccc-review-photo">
                                @if($hasImage)
                                    <img src="{{ asset($imagePath) }}" alt="{{ $item['name'] }}" loading="lazy">
                                @else
                                    <span aria-hidden="true">🍗</span>
                                @endif
                            </div>
                            <div class="ccc-review-info">
                                <h3>{{ $item['name'] }} <span>× {{ $item['quantity'] }}</span></h3>
                                @if(!empty($item['sauce_choice']))<p>Sauce: {{ $item['sauce_choice'] }}</p>@endif
                                @foreach($item['add_ons'] as $addon)
                                    <p>+ {{ (int) ($addon['quantity'] ?? 1) }} × {{ $addon['name'] }} per item{{ (int) $item['quantity'] > 1 ? ' (' . ((int) ($addon['quantity'] ?? 1) * (int) $item['quantity']) . ' total)' : '' }}</p>
                                @endforeach
                                @foreach($item['removals'] as $removal)<p>− No {{ $removal['name'] }}</p>@endforeach
                                @if(!empty($item['item_notes']))<p>Note: {{ $item['item_notes'] }}</p>@endif
                            </div>
                            <strong class="ccc-review-price">{{ $price((int) $item['unit_price_cents'] * (int) $item['quantity']) }}</strong>
                        </article>
                    @endforeach
                    </div>
                </section>
            </div>

            <aside class="ccc-card ccc-summary-card ccc-checkout-summary rounded-2xl" aria-labelledby="ccc-summary-title">
                <span class="ccc-eyebrow">FINAL REVIEW</span>
                <h2 id="ccc-summary-title">Payment summary</h2>
                <div class="ccc-summary-rows">
                    <div><span>Subtotal</span><strong>{{ $price($subtotalCents) }}</strong></div>
                    <div><span>Delivery fee</span><strong>{{ $price($deliveryFeeCents) }}</strong></div>
                </div>
                <div class="ccc-summary-total"><span>Total due</span><strong>{{ $price($totalCents) }}</strong></div>
                <button type="submit" class="ccc-primary-button">Place order <span aria-hidden="true">→</span></button>
                <p class="ccc-small-print">Your order will start as <strong>Preparing</strong>. Payment stays pending until verified. The delivery fee is a demo value.</p>
                <div class="ccc-summary-support"><a href="{{ route('customer.cart.index') }}">← Return to cart</a></div>
            </aside>
        </form>
    </div>
</main>
@endsection
