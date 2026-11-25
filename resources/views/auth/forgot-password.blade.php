@extends('layouts.auth')
@section('title', "Forgot Password | Chick's Pops")
@section('content')
<section class="cca-reset-card" aria-labelledby="reset-title">
    <a class="cca-reset-back" href="{{ route('login') }}">← Back to sign in</a>
    <p class="cca-kicker">ACCOUNT RECOVERY</p>
    <h1 id="reset-title">Reset your password</h1>
    <p>Enter your account email. We'll send a link to choose a new password.</p>
    @if(session('status'))<div role="status" class="cca-reset-notice">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="cca-alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.email') }}" class="cca-form">
        @csrf
        <div class="cca-field"><label for="reset-email">Email address</label><input id="reset-email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" maxlength="255" placeholder="you@example.com"></div>
        <button class="cca-primary-btn" type="submit">Send reset link</button>
    </form>
</section>
@endsection
