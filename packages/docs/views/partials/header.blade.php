<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Rakit :: Documentation ~ {{ $title }}</title>
    <meta name="description" content="{{ $description }}">
    <link rel="canonical" href="{{ $canonical }}">

    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <meta name="generator" content="Rakit {{ RAKIT_VERSION }}">
    <meta name="theme-color" content="#000000">
    <meta name="color-scheme" content="dark">

    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
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
