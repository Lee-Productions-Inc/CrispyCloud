@extends('layouts.auth')

@section('title', 'Login - CrispyCloud')

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
        <h1>Welcome Back!</h1>
    </div>

    @if ($errors->any())
        <div class="form-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ url('/login') }}"
        class="auth-form"
    >

        @csrf

        <div class="form-group">

            <label class="form-label">
                Email or Username
            </label>

            <div class="input-wrapper">

                
                <input
                    type="email"
                    name="email"
                    class="form-input input-with-icon"
                    placeholder="Enter your chicken handle"
                    value="{{ old('email') }}"
                    required
                >

            </div>

        </div>

        <div class="form-group">

            <label class="form-label">
                Password
            </label>

            <div class="input-wrapper">


                <input
                    type="password"
                    name="password"
                    id="login-password"
                    class="form-input input-with-icon"
                    placeholder="••••••••"
                    required
                >

                <button
                    type="button"
                    class="password-toggle"
                    onclick="togglePassword('login-password')"
                >
                    ◉
                </button>

            </div>

        </div>

        <div class="form-options">

            <label class="checkbox-label">
                <input type="checkbox" name="remember">
                <span>Remember Me</span>
            </label>

            <a href="#" class="auth-link">
                Forgot Password?
            </a>

        </div>

        <button type="submit" class="auth-button">
            SIGN IN
        </button>

    </form>

    <div class="social-section">

        <div class="social-buttons">

            <button type="button" class="social-button">
                <span style="color:#EA4335;">G</span>
                &nbsp; Google
            </button>

            <button type="button" class="social-button">
                <span style="color:#1877F2;">●</span>
                &nbsp; Facebook
            </button>

        </div>

    </div>

    <div class="auth-footer">

        Don't have an account?

        <a href="{{ url('/register') }}" class="auth-link">
            Register Now
        </a>

    </div>

</div>

<script>
function togglePassword(id) {
    const input = document.getElementById(id);

    input.type = input.type === 'password'
        ? 'text'
        : 'password';
}
</script>

@endsection