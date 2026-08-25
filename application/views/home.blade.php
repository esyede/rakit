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
            --surface: #08080c;
            --text: #ededf2;
            --dim: #8b8b99;
            --faint: #5f5f6d;
            --primary: #5a45ff;
            --soft: #c5c0ff;
            --rule: #1c1b24;
            --rule-strong: #2e2d3a;
            --mono: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", "Courier New", monospace;

            /* Kisi blueprint. --grid-x jadi titik nol garis sekaligus sel yang
               menyala, supaya keduanya selalu segaris. */
            --cell: 64px;
            --block: 256px;
            --grid-x: calc(50% + 32px);
            --line: rgba(255, 255, 255, .05);
            --line-strong: rgba(197, 192, 255, .22);
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
            padding: 72px 24px;
            background: var(--bg);
            color: var(--text);
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        /* --- Latar --- */

        .bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            overflow: hidden;
            pointer-events: none;
        }

        .bg__glow {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(ellipse 44% 38% at 50% 40%, rgba(90, 69, 255, .2), transparent 70%),
                radial-gradient(ellipse 90% 44% at 50% 0%, rgba(197, 192, 255, .05), transparent 68%);
        }

        .bg__grid {
            position: absolute;
            inset: 0;
            background-image:
                linear-gradient(to right, var(--line) 1px, transparent 1px),
                linear-gradient(to bottom, var(--line) 1px, transparent 1px),
                linear-gradient(to right, var(--line-strong) 1px, transparent 1px),
                linear-gradient(to bottom, var(--line-strong) 1px, transparent 1px);
            background-size:
                var(--cell) var(--cell),
                var(--cell) var(--cell),
                var(--block) var(--block),
                var(--block) var(--block);
            background-position: var(--grid-x) 0;
            -webkit-mask-image: radial-gradient(ellipse 82% 80% at 50% 46%, #000 32%, rgba(0, 0, 0, .5) 66%, transparent 92%);
            mask-image: radial-gradient(ellipse 82% 80% at 50% 46%, #000 32%, rgba(0, 0, 0, .5) 66%, transparent 92%);
        }

        /* Satu langkah --c menggeser sel tepat satu kolom, --r satu baris. Sel
           ikut mask kisi supaya tak melayang di area yang garisnya sudah pudar. */
        .bg__cells {
            position: absolute;
            inset: 0;
            -webkit-mask-image: radial-gradient(ellipse 58% 62% at 50% 46%, #000 24%, rgba(0, 0, 0, .55) 62%, transparent 90%);
            mask-image: radial-gradient(ellipse 58% 62% at 50% 46%, #000 24%, rgba(0, 0, 0, .55) 62%, transparent 90%);
        }

        .bg__cell {
            position: absolute;
            width: calc(var(--cell) - 1px);
            height: calc(var(--cell) - 1px);
            left: calc(var(--grid-x) + (var(--c) * var(--cell)) + 1px);
            top: calc((var(--r) * var(--cell)) + 1px);
            background: rgba(90, 69, 255, .11);
            box-shadow: inset 0 0 0 1px rgba(197, 192, 255, .3);
            opacity: 0;
            animation: assemble 11s ease-in-out infinite;
            animation-delay: var(--d, 0s);
        }

        @keyframes assemble {

            0%,
            58%,
            100% {
                opacity: 0;
                transform: scale(.82);
            }

            10%,
            44% {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* --- Panel --- */

        .panel {
            width: 100%;
            max-width: 780px;
            text-align: center;
        }

        .eyebrow {
            display: block;
            margin-bottom: 22px;
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .wordmark {
            font-size: clamp(52px, 12vw, 92px);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.04em;
            color: var(--text);
        }

        .wordmark em {
            font-style: normal;
            color: var(--soft);
        }

        .lead {
            max-width: 46ch;
            margin: 20px auto 0;
            color: var(--dim);
            font-size: 16px;
        }

        /* --- Tiga berkas pertama --- */

        .start {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            margin-top: 44px;
            border: 1px solid var(--rule);
            text-align: left;
        }

        .start article {
            padding: 22px 20px;
            border-right: 1px solid var(--rule);
            transition: background-color .18s ease;
        }

        .start article:last-child {
            border-right: 0;
        }

        .start article:hover {
            background: var(--surface);
        }

        .start__path {
            display: block;
            margin-bottom: 10px;
            font-family: var(--mono);
            font-size: 12px;
            line-height: 1.5;
            color: var(--soft);
            overflow-wrap: break-word;
        }

        .start__dir {
            display: block;
            color: var(--faint);
        }

        .start p {
            margin: 0;
            color: var(--dim);
            font-size: 14px;
            line-height: 1.55;
        }

        .cmd {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin: 28px 0 0;
            color: var(--faint);
            font-size: 14px;
        }

        .cmd code {
            padding: 8px 14px;
            border: 1px solid var(--rule);
            background: var(--surface);
            color: var(--dim);
            font-family: var(--mono);
            font-size: 13px;
        }

        .cmd .prompt {
            color: var(--primary);
            margin-right: 8px;
        }

        .rule {
            height: 1px;
            margin: 40px auto 32px;
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
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1;
            display: flex;
            gap: 8px;
        }

        .session a {
            padding: 7px 16px;
            border: 1px solid var(--rule-strong);
            background: var(--bg);
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
            margin: 28px 0 0;
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

        @media (max-width: 720px) {
            .start {
                grid-template-columns: minmax(0, 1fr);
            }

            .start article {
                border-right: 0;
                border-bottom: 1px solid var(--rule);
            }

            .start article:last-child {
                border-bottom: 0;
            }
        }

        @media (max-width: 640px) {
            body {
                padding: 96px 16px 48px;
            }

            .session {
                top: 16px;
                right: 16px;
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition-duration: .01ms !important;
            }

            .bg__cell {
                opacity: .6;
                transform: none;
                animation: none;
            }
        }
    </style>
</head>

<body>
    <div class="bg" aria-hidden="true">
        <div class="bg__glow"></div>
        <div class="bg__grid"></div>
        <div class="bg__cells">
            <span class="bg__cell" style="--c: -10; --r: 1; --d: 0s"></span>
            <span class="bg__cell" style="--c: -7; --r: 6; --d: 2.1s"></span>
            <span class="bg__cell" style="--c: -12; --r: 4; --d: 4.2s"></span>
            <span class="bg__cell" style="--c: -8; --r: 9; --d: 6.6s"></span>
            <span class="bg__cell" style="--c: 8; --r: 3; --d: 1.1s"></span>
            <span class="bg__cell" style="--c: 10; --r: 7; --d: 3.4s"></span>
            <span class="bg__cell" style="--c: 6; --r: 0; --d: 6.3s"></span>
        </div>
    </div>

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

    <main class="panel">
        <span class="eyebrow">Rakit {{ RAKIT_VERSION }}</span>
        <div class="wordmark">ra<em>kit</em></div>
        <p class="lead">Your application is running. Three files to know before you start building.</p>

        <div class="start">
            <article>
                <span class="start__path"><span class="start__dir">application/</span>routes.php</span>
                <p>Point a URL at a closure or a controller action.</p>
            </article>
            <article>
                <span class="start__path"><span class="start__dir">application/views/</span>home.blade.php</span>
                <p>This page. Replace it with your own.</p>
            </article>
            <article>
                <span class="start__path"><span class="start__dir">application/config/</span>application.php</span>
                <p>Application name, URL, timezone and language.</p>
            </article>
        </div>

        <p class="cmd">
            <code><span class="prompt">$</span>php rakit</code>
            lists every console command.
        </p>

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
    </main>
</body>

</html>
