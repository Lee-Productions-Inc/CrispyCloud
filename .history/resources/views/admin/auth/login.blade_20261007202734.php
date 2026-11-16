<!DOCTYPE html>
<html>
<head>
    <title>CrispyCloud - Admin Login</title>
</head>
<body>

<h1>Admin Login</h1>

@if ($errors->any())
    <div>
        {{ $errors->first() }}
    </div>
@endif

<form method="POST" action="/admin/login">
    @csrf

    <input type="email" name="email" placeholder="Admin Email" required>

    <input type="password" name="password" placeholder="Password" required>

    <button type="submit">Login</button>
</form>

</body>
</html>