@extends('layouts.auth')

@section('title', 'Register - CrispyCloud')

@section('content')

<div class="auth-card">

    <div class="auth-brand">

        <img
            src="{{ asset('images/Chicks_Pops_Logo.png') }}"
            alt="CrispyCloud"
            class="auth-logo"
        >

        <div class="auth-brand-name">
            CRISPY CLOUD
        </div>

        <div class="auth-tagline">
            CRISPY • FRESH • TASTY
        </div>

    </div>

    <div class="auth-heading">

        <h1>Join the Flock</h1>

        <div class="auth-subtitle">
            Register for Exclusive Treats
        </div>

    </div>

    @if ($errors->any())

        <div class="form-error">

            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach

        </div>

    @endif

    <form
        method="POST"
        action="{{ url('/register') }}"
        class="auth-form"
    >

        @csrf

        <div class="form-group">

            <label class="form-label">
                Full Name
            </label>

            <div class="input-wrapper">

                <span class="input-icon">♙</span>

                <input
                    type="text"
                    name="full_name"
                    class="form-input input-with-icon"
                    placeholder="John Doe"
                    value="{{ old('full_name') }}"
                    required
                >

            </div>

        </div>

        <div class="form-row">

            <div class="form-group">

                <label class="form-label">
                    Email Address
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">✉</span>

                    <input
                        type="email"
                        name="email"
                        class="form-input input-with-icon"
                        placeholder="example@gmail.com"
                        value="{{ old('email') }}"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Phone Number
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">☎</span>

                    <input
                        type="text"
                        name="phone_number"
                        class="form-input input-with-icon"
                        placeholder="+63 900 000 0000"
                        value="{{ old('phone_number') }}"
                    >

                </div>

            </div>

        </div>

        <div class="input-wrapper"

        <div class="form-row">

            <div class="form-group">

                <label class="form-label">
                    Password
                </label>

                <div class="input-wrapper">


                    <input
                        type="password"
                        name="password"
                        id="register-password"
                        class="form-input input-with-icon"
                        placeholder="••••••••"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label class="form-label">
                    Confirm Password
                </label>

                <div class="input-wrapper">

                    <span class="input-icon">✓</span>

                    <input
                        type="password"
                        name="password_confirmation"
                        class="form-input input-with-icon"
                        placeholder="••••••••"
                        required
                    >

                </div>

            </div>

        </div>

        <div class="form-options">

            <label class="checkbox-label">

                <input
                    type="checkbox"
                    name="terms"
                    required
                >

                <span>
                    I agree to the
                    <a href="#" class="auth-link">
                        Terms and Conditions
                    </a>
                    and Privacy Policy
                </span>

            </label>

        </div>

        <button
            type="submit"
            class="auth-button"
        >
            CREATE ACCOUNT
        </button>

    </form>

    <div class="social-section">

        <div class="divider">
            OR JOIN WITH
        </div>

        <div class="social-buttons">

            <button type="button" class="social-button">
                <span style="color:#EA4335;">G</span>
                &nbsp; GOOGLE
            </button>

            <button type="button" class="social-button">
                <span style="color:#1877F2;">●</span>
                &nbsp; FACEBOOK
            </button>

        </div>

    </div>

    <div class="auth-footer">

        Already have an account?

        <a href="{{ url('/login') }}" class="auth-link">
            Sign In
        </a>

    </div>

</div>

@endsection