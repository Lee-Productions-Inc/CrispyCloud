@extends('layouts.auth')
@section('title', "Create Account | Chick's Pops")

@section('content')
@php
    $logoFile = is_file(public_path('images/Chicks_Pops_Logo.png'))
        ? 'images/Chicks_Pops_Logo.png'
        : (is_file(public_path('images/logo.png')) ? 'images/logo.png' : null);
@endphp
<div class="cca-frame cca-frame-register">
    <aside class="cca-brand-panel" aria-label="Chick's Pops introduction">
        <a class="cca-brand" href="{{ route('customer.menu') }}" aria-label="Chick's Pops - Go to menu">
            @if($logoFile)
                <img src="{{ asset($logoFile) }}" width="56" height="56" alt="">
            @else
                <span class="cca-monogram" aria-hidden="true">CP</span>
            @endif
            <span><strong>CHICK'S POPS</strong><small>CRISPYCLOUD</small></span>
        </a>
        <div class="cca-brand-message">
            <p class="cca-eyebrow">ONE ACCOUNT. EASY ORDERING.</p>
            <h2>All your favorites, <em>one place.</em></h2>
            <p>Create an account to save delivery addresses, customize your food, and review your order history.</p>
        </div>
        <div class="cca-brand-footer">Get your next order started in a few steps.</div>
    </aside>

    <section class="cca-form-panel" aria-labelledby="cca-title">
        <div class="cca-form-width">
            <a class="cca-top-brand" href="{{ route('customer.menu') }}" aria-label="CHICK'S POPS menu">
                <span class="cca-top-logo">
                    @if($logoFile)<img src="{{ asset($logoFile) }}" alt="CHICK'S POPS logo">@else CP @endif
                </span>
                <strong>CHICK'S POPS</strong><small>CRISPYCLOUD</small>
            </a>
            <div class="cca-form-heading cca-register-heading">
                <p class="cca-kicker">JOIN CHICK'S POPS</p>
                <h1 id="cca-title">Create your account</h1>
                <p>Fill in the details below to get started.</p>
            </div>

            @if($errors->any())
                <div role="alert" class="cca-alert"><strong>Please check your information.</strong><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="cca-form">
                @csrf
                <div class="cca-field"><label for="cca-full-name">Full name</label><input id="cca-full-name" type="text" name="full_name" value="{{ old('full_name') }}" autocomplete="name" maxlength="255" placeholder="Your full name" required></div>
                <div class="cca-field-row">
                    <div class="cca-field"><label for="cca-email">Email address</label><input id="cca-email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" maxlength="255" placeholder="you@example.com" required></div>
                    <div class="cca-field"><label for="cca-phone">Phone number</label><input id="cca-phone" type="tel" name="phone_number" value="{{ old('phone_number') }}" autocomplete="tel" maxlength="20" placeholder="09XXXXXXXXX" required></div>
                </div>
                <div class="cca-field-row">
                    <div class="cca-field">
                        <label for="cca-new-password">Password</label>
                        <div class="cca-password-field">
                            <input id="cca-new-password" type="password" name="password" minlength="8" autocomplete="new-password" placeholder="At least 8 characters" required>
                            <button type="button" class="cca-visibility" data-password-toggle="cca-new-password" aria-label="Show password" aria-pressed="false">
                                <svg class="cca-eye-show" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="cca-eye-hide" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.5 6.1A11 11 0 0 1 12 6c6.4 0 10 6 10 6a15 15 0 0 1-3.1 3.5"/><path d="M6.6 6.7C3.5 8.3 2 12 2 12s3.6 6 10 6c1.4 0 2.7-.3 3.9-.8"/><path d="M10 10a3 3 0 0 0 4 4"/></svg>
                                <span data-password-label>Show</span>
                            </button>
                        </div>
                    </div>
                    <div class="cca-field">
                        <label for="cca-confirm-password">Confirm password</label>
                        <div class="cca-password-field">
                            <input id="cca-confirm-password" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" placeholder="Repeat password" required>
                            <button type="button" class="cca-visibility" data-password-toggle="cca-confirm-password" aria-label="Show confirm password" aria-pressed="false">
                                <svg class="cca-eye-show" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                                <svg class="cca-eye-hide" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.5 6.1A11 11 0 0 1 12 6c6.4 0 10 6 10 6a15 15 0 0 1-3.1 3.5"/><path d="M6.6 6.7C3.5 8.3 2 12 2 12s3.6 6 10 6c1.4 0 2.7-.3 3.9-.8"/><path d="M10 10a3 3 0 0 0 4 4"/></svg>
                                <span data-password-label>Show</span>
                            </button>
                        </div>
                    </div>
                </div>
                <label class="cca-check"><input type="checkbox" name="terms" value="1" @checked(old('terms')) required><span>I agree to the <a href="{{ route('legal.terms') }}" target="_blank" rel="noopener noreferrer">Terms &amp; Conditions</a> and acknowledge the <a href="{{ route('legal.privacy') }}" target="_blank" rel="noopener noreferrer">Privacy Policy</a>.</span></label>
                <button type="submit" class="cca-primary-btn">Create account <span aria-hidden="true">→</span></button>
            </form>

            <a class="cca-guest-btn" href="{{ route('customer.menu') }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 9h16l-1 11H5L4 9Z"/>
                    <path d="M9 10V7a3 3 0 0 1 6 0v3"/>
                </svg>
                <span>Browse menu as guest</span>
            </a>

            <div class="cca-bottom">Already have an account? <a href="{{ route('login') }}">Sign in</a></div>
            <nav class="cca-legal-links" aria-label="Legal information"><a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a><a href="{{ route('legal.privacy') }}">Privacy Policy</a></nav>
        </div>
    </section>
</div>
@endsection
