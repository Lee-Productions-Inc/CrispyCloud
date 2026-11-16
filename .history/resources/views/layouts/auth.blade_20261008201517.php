<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'CrispyCloud')</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="auth-page">

    <main class="auth-container">
        @yield('content')
    </main>

</body>
</html>