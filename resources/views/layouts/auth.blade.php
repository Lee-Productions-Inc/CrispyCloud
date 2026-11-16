<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#C8102E">
    <title>@yield('title', "Chick's Pops | CrispyCloud")</title>
    @vite(['resources/css/cc-auth.css', 'resources/js/app.js', 'resources/js/customer/auth-visibility.js'])

</head>
<body class="auth-page cca-body">
    <a class="cca-skip" href="#main-content">Skip to main content</a>
    <main id="main-content" class="auth-container cca-shell flex min-h-screen justify-center">
        @yield('content')
    </main>
</body>
</html>
