<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administrator sign in • CrispyCloud</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <script src="{{ asset('js/admin-login.js') }}" defer></script>
</head>
<body class="a-login-body">
    <main class="a-login-card">
        <aside class="a-login-brand">
            <div class="a-login-logo">CHICK'S POPS</div>
            <span class="a-login-kicker">CRISPYCLOUD ADMIN</span>
            <h1>Manage the business.<br><span>Keep it crispy.</span></h1>
            <p>Orders, products, inventory, and sales in one organized workspace.</p>
            <small>Authorized administrators only</small>
        </aside>
        <section class="a-login-form-area" aria-labelledby="login-heading">
            <span class="a-eyebrow">ADMINISTRATOR ACCESS</span>
            <h2 id="login-heading">Sign in</h2>
            <p>Enter your administrator credentials to continue.</p>
            @if($errors->any()) <div class="a-alert a-alert-error" role="alert">{{ $errors->first() }}</div> @endif
            <form action="{{ route('admin.login') }}" method="POST" class="a-login-form">
                @csrf
                <label for="admin-email">Email address</label>
                <input type="email" id="admin-email" name="email" value="{{ old('email') }}" required autocomplete="username" placeholder="admin@example.com" autofocus>
                <label for="admin-password">Password</label>
                <div class="a-password-wrap">
                    <input type="password" id="admin-password" name="password" required autocomplete="current-password" placeholder="Enter your password">
                    <button id="a-toggle-password" type="button" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
                <button class="a-login-submit" type="submit">Sign in to dashboard <span aria-hidden="true">→</span></button>
            </form>
            <p class="a-login-footer">Customer ordering? <a href="{{ route('customer.menu') }}">View the customer menu</a></p>
        </section>
    </main>
</body>
</html>
