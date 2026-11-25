@extends('layouts.auth')
@section('title', "New Password | Chick's Pops")
@section('content')
<section class="cca-reset-card" aria-labelledby="reset-title">
    <a class="cca-reset-back" href="{{ route('login') }}">← Back to sign in</a>
    <p class="cca-kicker">ACCOUNT RECOVERY</p>
    <h1 id="reset-title">Choose a new password</h1>
    <p>Enter a new password for your Chick's Pops customer account.</p>
    @if($errors->any())<div role="alert" class="cca-alert">{{ $errors->first() }}</div>@endif
    <form method="POST" action="{{ route('password.update') }}" class="cca-form">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="cca-field"><label for="reset-email">Email address</label><input id="reset-email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email" maxlength="255"></div>
        <div class="cca-field"><label for="reset-password">New password</label><input id="reset-password" type="password" name="password" minlength="8" required autocomplete="new-password"></div>
        <div class="cca-field"><label for="reset-confirm">Confirm password</label><input id="reset-confirm" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password"></div>
        <button class="cca-primary-btn" type="submit">Update password</button>
    </form>
</section>
@endsection
