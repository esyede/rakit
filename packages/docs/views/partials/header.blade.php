<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Runs before the first paint, so a light-theme visitor never sees the
         dark default flash past. The markup ships with class="dark". --}}
    <script>
        (function () {
            try {
                var saved = localStorage.getItem('theme');
                var light = saved
                    ? saved === 'light'
                    : window.matchMedia('(prefers-color-scheme: light)').matches;

                document.documentElement.classList.toggle('dark', !light);
            } catch (e) {}
        })();
    </script>

    <title>Rakit :: Documentation ~ {{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">

    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="generator" content="Rakit {{ RAKIT_VERSION }}">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">

    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Geist:wght@300..700&family=Geist+Mono:wght@400..600&display=swap">

    <link rel="stylesheet" href="{{ asset('packages/docs/css/docs.css?v=' . RAKIT_VERSION) }}">

    <meta property="og:type" content="article">
    <meta property="og:title" content="Rakit :: Documentation ~ {{ $title }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ $canonical }}">
    <meta property="og:site_name" content="Rakit :: Documentation">
    <meta property="og:locale" content="en_US">
    <meta property="article:modified_time" content="{{ date(DATE_W3C, $modified) }}">

    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Rakit :: Documentation ~ {{ $title }}">
    <meta name="twitter:description" content="{{ $description }}">

    <script type="application/ld+json">{!! json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'WebSite',
        'name' => 'Rakit :: Documentation',
        'alternateName' => 'Rakit',
        'url' => rtrim(url('/'), '/'),
        'inLanguage' => 'en',
        'description' => 'Documentation for Rakit, a simple, lightweight and modular PHP framework.',
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
</head>
