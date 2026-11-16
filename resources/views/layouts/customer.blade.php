<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#C8102E">
    <title>@yield('title', "Chick's Pops | CrispyCloud")</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite('resources/css/cc-customer.css')

    @yield('page_assets')
    @stack('head')

    <script src="{{ asset('js/crispycloud-confirm-modal.js') }}" defer></script>
</head>
<body class="cc-customer-page @yield('body_class')">
    <a class="ccn-skip" href="#main-content">Skip to main content</a>
    @include('components.customer.navbar')
    @yield('content')
</body>
</html>
