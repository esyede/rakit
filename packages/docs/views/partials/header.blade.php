<!-- Header start -->
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <link rel="stylesheet" href="{{ asset('packages/docs/css/docs.css?v=' . RAKIT_VERSION) }}">
    <link rel="stylesheet" href="{{ asset('packages/docs/css/dark-mode.css?v=' . RAKIT_VERSION) }}">
    <title>Rakit :: {{ trans('docs::docs.header.documentation') }} ~ {{ $title }}</title>

    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (savedTheme === null && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    </script>

    <meta property="og:type" content="website">
    <meta property="og:title" content="Rakit :: {{ trans('docs::docs.header.documentation') }}">
    <meta property="og:description" content="{{ trans('docs::docs.header.description') }}">
    <meta property="og:site_name" content="Rakit :: {{ trans('docs::docs.header.documentation') }}">
</head>
<!-- Header end -->
