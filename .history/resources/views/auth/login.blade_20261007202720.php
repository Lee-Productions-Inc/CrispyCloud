<!DOCTYPE html>
<html>
<head>
    <title>CrispyCloud - Login</title>
</head>
<body>

<h1>Customer Login</h1>

@if ($errors->any())
    <div>
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="/login">
    @csrf

    <input type="email" name="email" placeholder="Email" required>

    <input type="password" name="password" placeholder="Password" required>

    <button type="submit">Login</button>
</form>

<a href="/register">Create an account</a>

</body>
</html>