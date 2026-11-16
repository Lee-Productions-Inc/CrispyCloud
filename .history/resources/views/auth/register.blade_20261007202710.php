<!DOCTYPE html>
<html>
<head>
    <title>CrispyCloud - Register</title>
</head>
<body>

<h1>Create Customer Account</h1>

<form method="POST" action="/register">
    @csrf

    <input type="text" name="full_name" placeholder="Full Name" required>

    <input type="email" name="email" placeholder="Email" required>

    <input type="text" name="phone_number" placeholder="Phone Number">

    <input type="password" name="password" placeholder="Password" required>

    <input type="password" name="password_confirmation" placeholder="Confirm Password" required>

    <button type="submit">Create Account</button>
</form>

<a href="/login">Already have an account?</a>

</body>
</html>