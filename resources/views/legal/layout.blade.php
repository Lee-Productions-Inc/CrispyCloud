<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#b91c37">
    <title>@yield('title') | CHICK'S POPS</title>
    @vite('resources/css/cc-legal.css')
</head>
<body>
    <a class="skip-link" href="#content">Skip to content</a>
    <header class="policy-header">
        <div class="policy-header-inner flex items-center">
            <a href="{{ route('customer.menu') }}" class="policy-brand" aria-label="CHICK'S POPS menu">
                <span class="policy-symbol" aria-hidden="true">CP</span>
                <span><strong>CHICK'S POPS</strong><small>CRISPYCLOUD</small></span>
            </a>
            <nav aria-label="Main navigation">
                <a href="{{ route('customer.menu') }}">Menu</a>
                <a href="{{ route('login') }}">Sign in</a>
            </nav>
        </div>
    </header>
    <main class="policy-shell" id="content">
        <nav class="policy-breadcrumb" aria-label="Breadcrumb"><a href="{{ route('customer.menu') }}">Home</a><span aria-hidden="true">/</span><span aria-current="page">@yield('document_name')</span></nav>
        <article class="policy-paper rounded-2xl">
            <header class="policy-intro">
                <p class="policy-overline">CUSTOMER INFORMATION</p>
                <h1>@yield('document_name')</h1>
                <p class="policy-meta">Effective {{ config('commerce_legal.effective_date') }} · Version {{ config('commerce_legal.version') }}</p>
            </header>
            <div class="policy-copy">@yield('legal_content')</div>
        </article>
        <div class="policy-other"><a href="{{ route('legal.terms') }}">Terms &amp; Conditions</a><a href="{{ route('legal.privacy') }}">Privacy Policy</a></div>
    </main>
    <footer class="policy-footer">© {{ date('Y') }} CHICK'S POPS · CrispyCloud ordering system</footer>
</body>
</html>
