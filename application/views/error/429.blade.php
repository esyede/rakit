<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}" class="dark">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Honours the theme the visitor picked on the rest of the site. --}}
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

    <meta name="robots" content="noindex">
    <meta name="generator" content="Rakit">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <title>429 | Too Many Requests</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Geist:wght@300..700&family=Geist+Mono:wght@400..600&display=swap">

    <style>
        :root {
            color-scheme: light;

            --background: #ffffff;
            --card: #ffffff;
            --accent: #e8e8ea;
            --foreground: #09090b;
            --muted-foreground: #71717a;
            --border: #e4e4e7;
            --border-strong: #d4d4d8;
            --focus: #2563eb;
            --hairline: rgba(9, 9, 11, 0.05);
            --stripe: rgba(228, 228, 231, 0.56);

            --col: 768px;
            --font-sans:
                "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI",
                Roboto, "Helvetica Neue", Arial, sans-serif;
            --font-mono:
                "Geist Mono", ui-monospace, SFMono-Regular, Menlo, Consolas,
                "Liberation Mono", "Courier New", monospace;
        }

        html.dark {
            color-scheme: dark;

            --background: #09090b;
            --card: #18181b;
            --accent: #27272a;
            --foreground: #fafafa;
            --muted-foreground: #a1a1aa;
            --border: #27272a;
            --border-strong: #3f3f46;
            --focus: #3b82f6;
            --hairline: rgba(250, 250, 250, 0.045);
            --stripe: rgba(39, 39, 42, 0.56);
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
        }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            background: var(--background);
            color: var(--foreground);
            font-family: var(--font-sans);
            font-size: 16px;
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        h1 {
            margin: 0;
            font-weight: 500;
            letter-spacing: -0.025em;
        }

        /* Clips the 100vw bleeds without creating a scroll container. */
        .stage {
            width: 100%;
            overflow-x: clip;
        }

        /* The column, and the two vertical rules that run its whole height —
           drawn above every bleeding child so the lines stay unbroken. */
        .frame {
            position: relative;
            max-width: var(--col);
            margin: 0 auto;
            padding: 0 1px;
        }

        .frame::before,
        .frame::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            width: 1px;
            background: var(--border);
            z-index: 3;
            pointer-events: none;
        }

        .frame::before {
            left: 0;
        }

        .frame::after {
            right: 0;
        }

        /* Horizontal rules escape the column and run to both screen edges. */
        .rule,
        .sep {
            width: 100vw;
            margin-left: 50%;
            transform: translateX(-50%);
        }

        .rule {
            height: 1px;
            background: var(--border);
        }

        .sep {
            height: 32px;
            background-image: repeating-linear-gradient(-45deg,
                    var(--stripe) 0 1px,
                    transparent 1px 7.07px);
        }

        /* The numeral sits on drafting paper: construction lines bleeding out
           of the column, over a 32px grid confined to it. Both stay inside
           this band — run across a whole viewport they read as stray streaks
           rather than as a drawing. */
        .hero {
            position: relative;
            height: 216px;
        }

        .hero::before {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100vw;
            background-image:
                repeating-linear-gradient(45deg, var(--hairline) 0 1px, transparent 1px 118px),
                repeating-linear-gradient(-45deg, var(--hairline) 0 1px, transparent 1px 118px);
        }

        .hero-inner {
            position: relative;
            height: 100%;
            display: grid;
            place-items: center;
            background-image:
                repeating-linear-gradient(to right, var(--hairline) 0 1px, transparent 1px 32px),
                repeating-linear-gradient(to bottom, var(--hairline) 0 1px, transparent 1px 32px);
        }

        .code {
            font-size: clamp(72px, 17vw, 116px);
            line-height: 1;
            letter-spacing: -0.045em;
            font-variant-numeric: tabular-nums;
        }

        .fig {
            position: absolute;
            right: 17px;
            bottom: 9px;
            font-family: var(--font-mono);
            font-size: 12px;
            line-height: 16px;
            color: var(--muted-foreground);
        }

        /* A spec-sheet label rather than a headline. */
        .label {
            padding: 9px 17px;
            font-family: var(--font-mono);
            font-size: 13px;
            line-height: 18px;
            font-weight: 500;
            letter-spacing: 0.2em;
            text-transform: uppercase;
            border-bottom: 1px solid var(--border);
        }

        .body {
            padding: 14px 17px 16px;
            font-size: 15px;
            line-height: 24px;
            color: var(--muted-foreground);
        }

        .body strong {
            color: var(--foreground);
            font-weight: 500;
        }

        .actions {
            padding: 14px 17px 16px;
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border: 1px solid var(--border);
            border-radius: 7px;
            background: var(--card);
            color: var(--foreground);
            font-size: 14px;
            text-decoration: none;
            transition: background-color .18s ease, border-color .18s ease;
        }

        .back:hover {
            background: var(--accent);
            border-color: var(--border-strong);
        }

        .back:focus-visible {
            outline: 2px solid var(--focus);
            outline-offset: 3px;
        }

        @media (max-width: 720px) {
            .hero {
                height: 168px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="stage">
        <div class="frame">
            <div class="rule"></div>

            <div class="hero">
                <div class="hero-inner">
                    <h1 class="code">429</h1>
                </div>
                <span class="fig">fig. 429</span>
            </div>

            <div class="rule"></div>

            <p class="label">Error &mdash; 429</p>

            <div class="body">
                <strong>Too Many Requests</strong> &mdash; that is more requests than the server will take right now. Wait a moment, then retry.
            </div>

            <div class="sep"></div>

            <div class="actions">
                <a class="back" href="{{ url('/') }}">
                    <svg width="15" height="15" viewBox="0 0 32 32" fill="none" stroke="currentColor"
                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M28 16 L4 16 M12 8 L4 16 12 24" />
                    </svg>
                    Back to home
                </a>
            </div>

            <div class="rule"></div>
        </div>
    </div>
</body>

</html>
