<html lang="{{ config('application.language', 'en') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="generator" content="Rakit debugger">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <script>
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
    <title>404 | Not Found</title>
    <style>
        :root {
            --background: #fff;
            --foreground: #a0aec0;
        }

        .dark {
            --background: #282c34;
            --foreground: #abb2bf;
        }

        html,
        body {
            background-color: var(--background);
            font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
        }

        pre,
        code {
            font-family: Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", "Courier New", Courier, monospace;
        }

        #oops-error {
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-height: 95vh;
            color: var(--foreground);
            font-size: 1.125em;
            text-transform: uppercase;
        }
    </style>
</head>

<body>
    <div id="oops-error">
        500 | Unknown Error
    </div>
</body>

</html>
