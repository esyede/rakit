<!DOCTYPE html>
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
    <title>503 | Service Unavailable</title>
    <style>
        :root {
            --background: #fff;
            --foreground: #7a828c;
            --muted: #b3bac2;
            --primary: #2563eb;
        }

        .dark {
            --background: #0e0f13;
            --foreground: #abb2bf;
            --muted: #6b7280;
            --primary: #7aa2f7;
        }

        html,
        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: BlinkMacSystemFont, -apple-system, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Fira Sans", "Droid Sans", "Helvetica Neue", Helvetica, Arial, sans-serif;
            margin: 0;
        }

        pre,
        code {
            font-family: Consolas, "Andale Mono WT", "Andale Mono", "Lucida Console", "Lucida Sans Typewriter", "DejaVu Sans Mono", "Bitstream Vera Sans Mono", "Liberation Mono", "Nimbus Mono L", "Courier New", Courier, monospace;
        }

        #oops-error {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            min-height: 100vh;
            padding: 24px;
            box-sizing: border-box;
        }

        #oops-error .code {
            font-size: clamp(80px, 18vw, 160px);
            font-weight: 800;
            line-height: 1;
            letter-spacing: -3px;
            color: var(--primary);
        }

        #oops-error .sep {
            width: 54px;
            height: 3px;
            margin: 18px 0 16px;
            background: var(--primary);
            border-radius: 2px;
            opacity: 0.85;
        }

        #oops-error .message {
            font-size: 1rem;
            letter-spacing: 0.22em;
            text-transform: uppercase;
            color: var(--muted);
        }
    </style>
</head>

<body>
    <div id="oops-error">
        <div class="code">503</div>
        <div class="sep"></div>
        <div class="message">Service Unavailable</div>
    </div>
</body>

</html>
