<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome!</title>
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <style>
        :root {
            --bg: #000;
            --text: #ededf2;
            --dim: #8b8b99;
            --faint: #5f5f6d;
            --primary: #5a45ff;
            --soft: #c5c0ff;
            --rule: #1c1b24;
            --rule-strong: #2e2d3a;
            --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", "Courier New", monospace;
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
            justify-content: center;
            padding: 24px;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        body::before {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -1;
            background-image:
                linear-gradient(to right, var(--rule) 1px, transparent 1px),
                linear-gradient(to bottom, var(--rule) 1px, transparent 1px);
            background-size: 64px 64px;
            background-position: center center;
            -webkit-mask-image: radial-gradient(ellipse 60% 55% at 50% 50%, #000 10%, transparent 75%);
            mask-image: radial-gradient(ellipse 60% 55% at 50% 50%, #000 10%, transparent 75%);
        }

        body::after {
            content: '';
            position: absolute;
            inset: 0;
            z-index: -2;
            background: radial-gradient(ellipse 45% 40% at 50% 42%, rgba(90, 69, 255, .13), transparent 70%);
        }

        .panel {
            width: 100%;
            max-width: 620px;
            text-align: center;
        }

        .eyebrow {
            display: block;
            margin-bottom: 24px;
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .wordmark {
            font-size: clamp(56px, 13vw, 104px);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.04em;
            color: var(--text);
        }

        .wordmark em {
            font-style: normal;
            color: var(--soft);
        }

        .rule {
            height: 1px;
            margin: 36px auto;
            background: var(--rule);
        }

        .links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .links a {
            padding: 8px 18px;
            border: 1px solid transparent;
            color: var(--dim);
            font-family: var(--mono);
            font-size: 11.5px;
            font-weight: 600;
            letter-spacing: .12em;
            text-transform: uppercase;
            text-decoration: none;
            transition: color .18s ease, border-color .18s ease;
        }

        .links a:hover {
            color: var(--text);
            border-color: var(--rule-strong);
        }

        .links a:focus-visible,
        .session a:focus-visible,
        .support a:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        .session {
            position: absolute;
            top: 20px;
            right: 20px;
            display: flex;
            gap: 8px;
        }

        .session a {
            padding: 7px 16px;
            border: 1px solid var(--rule-strong);
            color: var(--text);
            font-size: 13.5px;
            text-decoration: none;
            transition: border-color .18s ease, color .18s ease;
        }

        .session a:hover {
            border-color: var(--dim);
            color: #fff;
        }

        .support {
            margin: 36px 0 0;
            font-family: var(--mono);
            font-size: 11px;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .support a {
            color: var(--faint);
            text-decoration: none;
            border-bottom: 1px solid var(--rule-strong);
            transition: color .18s ease, border-color .18s ease;
        }

        .support a:hover {
            color: var(--soft);
            border-color: currentColor;
        }

        @media (max-width: 640px) {
            .session {
                position: static;
                justify-content: center;
                margin-bottom: 28px;
            }

            body {
                flex-direction: column;
                justify-content: center;
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
    @if (Route::has('login'))
        <div class="session">
            @guest
                <a href="{{ url('/login') }}">Login</a>
                <a href="{{ url('/register') }}">Register</a>
            @else
                <a href="{{ url('/dashboard') }}">Dashboard</a>
            @endguest
        </div>
    @endif

    <div class="panel">
        <span class="eyebrow">Rakit {{ RAKIT_VERSION }}</span>
        <div class="wordmark">ra<em>kit</em></div>

        <div class="rule"></div>

        <div class="links">
            <a href="{{ url('/docs') }}">Docs</a>
            <a href="https://rakit.esyede.my.id/api/main/index.html">API</a>
            <a href="https://rakit.esyede.my.id/repositories">Packages</a>
            <a href="https://github.com/esyede/rakit/discussions">Forum</a>
            <a href="https://github.com/esyede/rakit">Github</a>
        </div>

        <p class="support">
            Support the project &mdash;
            <a href="https://ko-fi.com/A0A61UOVND" target="_blank" rel="noopener">Buy me a coffee</a>
        </p>
    </div>
</body>

</html>
