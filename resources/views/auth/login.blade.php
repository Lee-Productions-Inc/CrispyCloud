@extends('layouts.auth')
@section('title', "Sign In | Chick's Pops")

@section('content')
@php
    $logoFile = is_file(public_path('images/Chicks_Pops_Logo.png'))
        ? 'images/Chicks_Pops_Logo.png'
        : (is_file(public_path('images/logo.png')) ? 'images/logo.png' : null);
@endphp
<div class="cca-frame">
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
            <p class="cca-eyebrow">FRESHLY MADE. EASY TO ORDER.</p>
            <h2>Craving something <em>crispy?</em></h2>
            <p>Explore the menu, choose your extras, and keep all your orders in one place.</p>
        </div>
        <div class="cca-brand-footer">The easy way to order your favorites.</div>
    </aside>

    <section class="cca-form-panel" aria-labelledby="cca-title">
        <div class="cca-form-width">
            <a class="cca-top-brand" href="{{ route('customer.menu') }}" aria-label="CHICK'S POPS menu">
                <span class="cca-top-logo">
                    @if($logoFile)<img src="{{ asset($logoFile) }}" alt="CHICK'S POPS logo">@else CP @endif
                </span>
                <strong>CHICK'S POPS</strong><small>CRISPYCLOUD</small>
            </a>
            <div class="cca-form-heading">
                <p class="cca-kicker">WELCOME BACK</p>
                <h1 id="cca-title">Sign in to your account</h1>
                <p>Enter your email and password to continue.</p>
            </div>

            @if(session('status'))
                <div role="status" class="cca-reset-notice">{{ session('status') }}</div>
            @endif
            @if($errors->any())
                <div role="alert" class="cca-alert"><strong>Unable to sign in.</strong> {{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="cca-form">
                @csrf
                <div class="cca-field">
                    <label for="cca-email">Email address</label>
                    <input id="cca-email" type="email" name="email" autocomplete="email" value="{{ old('email') }}" placeholder="you@example.com" maxlength="255" required autofocus>
                </div>
                <div class="cca-field">
                    <label for="cca-password">Password</label>
                    <div class="cca-password-field">
                        <input id="cca-password" type="password" name="password" autocomplete="current-password" placeholder="Enter your password" required>
                        <button type="button" class="cca-visibility" data-password-toggle="cca-password" aria-label="Show password" aria-pressed="false">
                            <svg class="cca-eye-show" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3.6-6 10-6 10 6 10 6-3.6 6-10 6-10-6-10-6Z"/><circle cx="12" cy="12" r="3"/></svg>
                            <svg class="cca-eye-hide" aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 3l18 18"/><path d="M10.5 6.1A11 11 0 0 1 12 6c6.4 0 10 6 10 6a15 15 0 0 1-3.1 3.5"/><path d="M6.6 6.7C3.5 8.3 2 12 2 12s3.6 6 10 6c1.4 0 2.7-.3 3.9-.8"/><path d="M10 10a3 3 0 0 0 4 4"/></svg>
                            <span data-password-label>Show</span>
                        </button>
                    </div>
                </div>
                @if(config('services.brevo.api_key') && config('services.brevo.sender_email'))
                    <div class="cca-reset-link"><a href="{{ route('password.request') }}">Forgot password?</a></div>
                @endif
                <button type="submit" class="cca-primary-btn">Sign in <span aria-hidden="true">→</span></button>
            </form>

            <a class="cca-guest-btn" href="{{ route('customer.menu') }}">
                <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 9h16l-1 11H5L4 9Z"/>
                    <path d="M9 10V7a3 3 0 0 1 6 0v3"/>
                </svg>
                <span>Browse menu as guest</span>
            </a>

            <div class="cca-bottom">New to Chick's Pops? <a href="{{ route('register') }}">Create an account</a></div>
            <nav class="cca-legal-links" aria-label="Legal information"><a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a><a href="{{ route('legal.privacy') }}">Privacy Policy</a></nav>
        </div>
    </section>
</div>
@endsection
