<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}" class="dark">

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

    <title>Rakit :: Welcome</title>
    <meta name="description" content="Your Rakit application is up and running. Start from the routes file, the controllers directory, or the bundled documentation.">

    <meta name="robots" content="noindex, follow">
    <meta name="generator" content="Rakit {{ RAKIT_VERSION }}">
    <meta name="color-scheme" content="dark light">
    <meta name="theme-color" content="#09090b" media="(prefers-color-scheme: dark)">
    <meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">

    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css2?family=Geist:wght@300..700&family=Geist+Mono:wght@400..600&display=swap">

    <style>
        @font-face {
            font-family: "Rakit Mono";
            src: url("{{ asset('packages/docs/fonts/Monaco.ttf') }}") format("truetype");
            font-weight: 400;
            font-style: normal;
            font-display: swap;
        }

        :root {
            color-scheme: light;

            --background: #ffffff;
            --card: #ffffff;
            --muted: #f4f4f5;
            --accent: #e8e8ea;

            --foreground: #09090b;
            --muted-foreground: #71717a;
            --faint: #7e7e87;

            --border: #e4e4e7;
            --border-strong: #d4d4d8;

            --solid: #09090b;
            --solid-hover: #27272a;
            --solid-fg: #fafafa;
            --focus: #2563eb;

            --hairline: rgba(9, 9, 11, 0.05);
            --nav-bg: rgba(255, 255, 255, 0.72);

            --code-key: #1d4ed8;

            --radius: 7px;
            --radius-pill: 999px;

            --gutter: 24px;
            --container: 1400px;
            --gap: 112px;
            --cell-pad: 40px;

            --font-display:
                "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                "Helvetica Neue", Arial, sans-serif;
            --font-body:
                "Geist", ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto,
                "Helvetica Neue", Arial, sans-serif;
            --font-mono:
                "Geist Mono", "Rakit Mono", ui-monospace, SFMono-Regular, Menlo,
                Consolas, monospace;
        }

        html.dark {
            color-scheme: dark;

            --background: #09090b;
            --card: #18181b;
            --muted: #18181b;
            --accent: #27272a;

            --foreground: #fafafa;
            --muted-foreground: #a1a1aa;
            --faint: #78787f;

            --border: #27272a;
            --border-strong: #3f3f46;

            --solid: #fafafa;
            --solid-hover: #e4e4e7;
            --solid-fg: #09090b;
            --focus: #3b82f6;

            --hairline: rgba(250, 250, 250, 0.045);
            --nav-bg: rgba(9, 9, 11, 0.72);

            --code-key: #93c5fd;
        }

        *,
        *::before,
        *::after {
            box-sizing: border-box;
        }

        html {
            -webkit-text-size-adjust: 100%;
            scrollbar-gutter: stable;
        }

        body {
            margin: 0;
            background: var(--background);
            color: var(--foreground);
            font-family: var(--font-body);
            font-size: 16px;
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }

        ::selection {
            background: var(--solid);
            color: var(--solid-fg);
        }

        a {
            color: inherit;
            text-decoration: none;
            transition: color 0.18s ease;
        }

        a:hover {
            color: var(--foreground);
        }

        h1,
        h2,
        h3,
        h4 {
            font-family: var(--font-display);
            color: var(--foreground);
            margin: 0;
            font-weight: 500;
            letter-spacing: -0.025em;
            text-wrap: balance;
        }

        p {
            margin: 0;
        }

        code {
            font-family: var(--font-mono);
        }

        :focus-visible {
            outline: 2px solid var(--focus);
            outline-offset: 2px;
            border-radius: var(--radius);
        }

        a:focus:not(:focus-visible),
        button:focus:not(:focus-visible) {
            outline: none;
        }

        @media (prefers-reduced-motion: reduce) {

            *,
            *::before,
            *::after {
                transition-duration: 0.01ms !important;
                animation-duration: 0.01ms !important;
            }
        }

        .shell {
            width: 100%;
            max-width: var(--container);
            margin: 0 auto;
            padding: 0 var(--gutter);
        }

        .shell--flush {
            padding: 0;
        }

        .main {
            flex: 1 0 auto;
            position: relative;
            padding-top: 64px;
        }

        .main::after {
            content: "";
            position: absolute;
            top: 0;
            bottom: 0;
            left: 50%;
            width: min(var(--container), 100%);
            transform: translateX(-50%);
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            pointer-events: none;
        }

        .band {
            border-top: 1px solid var(--border);
        }

        .band__head {
            max-width: 640px;
            margin: 0 auto;
            padding: var(--gap) var(--gutter) 56px;
            text-align: center;
        }

        .band__head h2 {
            font-size: 40px;
            line-height: 1.15;
            margin-bottom: 12px;
        }

        .band__head p {
            color: var(--muted-foreground);
            font-size: 18px;
        }

        .eyebrow {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted-foreground);
            display: block;
            margin-bottom: 16px;
        }

        .nav {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 50;
            background: var(--nav-bg);
            -webkit-backdrop-filter: saturate(180%) blur(12px);
            backdrop-filter: saturate(180%) blur(12px);
            border-bottom: 1px solid var(--border);
        }

        .nav__inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            height: 64px;
        }

        .nav__left {
            display: flex;
            align-items: center;
            flex: 0 0 auto;
        }

        .nav__brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            white-space: nowrap;
            font-family: var(--font-mono);
            font-size: 14px;
            font-weight: 500;
            color: var(--muted-foreground);
        }

        .nav__brand::before,
        .footer__brand::before {
            color: var(--foreground);
            content: "R";
            width: 28px;
            height: 28px;
            flex: 0 0 auto;
            display: grid;
            place-items: center;
            border: 1px solid var(--border-strong);
            border-radius: var(--radius);
            background: var(--card);
            font-size: 13px;
            line-height: 1;
        }

        .nav__brand:hover {
            color: var(--foreground);
        }

        .nav__links {
            display: flex;
            align-items: center;
            gap: 28px;
            margin-left: 40px;
        }

        .nav__link {
            color: var(--muted-foreground);
            font-size: 14px;
        }

        .nav__link:hover,
        .nav__link.is-current {
            color: var(--foreground);
        }

        .nav__right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 0 1 auto;
            min-width: 0;
        }

        .nav__session {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .navbar-burger {
            display: none;
            width: 40px;
            height: 40px;
            padding: 0;
            background: none;
            border: 0;
            cursor: pointer;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
        }

        .navbar-burger span {
            display: block;
            width: 18px;
            height: 1.5px;
            background: var(--muted-foreground);
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .navbar-burger.is-active span:nth-child(1) {
            transform: translateY(6.5px) rotate(45deg);
        }

        .navbar-burger.is-active span:nth-child(2) {
            opacity: 0;
        }

        .navbar-burger.is-active span:nth-child(3) {
            transform: translateY(-6.5px) rotate(-45deg);
        }

        .theme-toggle {
            width: 36px;
            height: 36px;
            flex: 0 0 auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: transparent;
            color: var(--muted-foreground);
            cursor: pointer;
            transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .theme-toggle:hover {
            background: var(--accent);
            border-color: var(--border-strong);
            color: var(--foreground);
        }

        .theme-toggle svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .theme-toggle__sun {
            display: none;
        }

        html.dark .theme-toggle__sun {
            display: block;
        }

        html.dark .theme-toggle__moon {
            display: none;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 28px;
            border-radius: var(--radius);
            border: 1px solid transparent;
            font-family: var(--font-body);
            font-size: 15px;
            line-height: 1.2;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.18s ease, border-color 0.18s ease, color 0.18s ease;
        }

        .btn--sm {
            padding: 8px 18px;
            font-size: 14px;
        }

        .btn--primary {
            background: var(--solid);
            color: var(--solid-fg);
        }

        .btn--primary:hover {
            background: var(--solid-hover);
            color: var(--solid-fg);
        }

        .btn--ghost {
            background: var(--card);
            border-color: var(--border);
            color: var(--foreground);
        }

        .btn--ghost:hover {
            background: var(--accent);
            border-color: var(--border-strong);
            color: var(--foreground);
        }

        .btn__icon {
            width: 17px;
            height: 17px;
            flex: 0 0 auto;
        }

        .hero {
            position: relative;
            isolation: isolate;
            max-width: var(--container);
            margin: 0 auto;
            padding: 112px var(--gutter) 96px;
            text-align: center;
        }

        .hero::before {
            content: "";
            position: absolute;
            inset: 0;
            z-index: -1;
            pointer-events: none;
            background-image:
                linear-gradient(to right, var(--hairline) 1px, transparent 1px),
                linear-gradient(to bottom, var(--hairline) 1px, transparent 1px);
            background-size: 64px 64px;
            background-position: center top;
            -webkit-mask-image: radial-gradient(ellipse 65% 60% at 50% 38%, #000 15%, transparent 78%);
            mask-image: radial-gradient(ellipse 65% 60% at 50% 38%, #000 15%, transparent 78%);
        }

        .hero__badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 5px 14px;
            margin-bottom: 36px;
            border: 1px solid var(--border);
            border-radius: var(--radius-pill);
            background: var(--card);
            color: var(--muted-foreground);
            font-family: var(--font-mono);
            font-size: 12px;
            line-height: 18px;
        }

        .hero__badge b {
            color: var(--foreground);
            font-weight: 500;
        }

        .hero__title {
            font-size: 76px;
            line-height: 1.05;
            letter-spacing: -0.035em;
            font-weight: 500;
            max-width: 960px;
            margin: 0 auto 28px;
        }

        .hero__title em {
            font-style: normal;
            color: var(--muted-foreground);
        }

        .hero__lead {
            font-size: 19px;
            color: var(--muted-foreground);
            max-width: 620px;
            margin: 0 auto 40px;
        }

        .hero__lead code {
            color: var(--foreground);
            font-size: 16px;
        }

        .hero__actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 12px;
        }

        .hero__cmd {
            margin-top: 36px;
            display: flex;
            justify-content: center;
        }

        .hero__cmd code {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 10px 18px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--muted);
            color: var(--muted-foreground);
            font-size: 13.5px;
        }

        .hero__cmd .prompt {
            color: var(--code-key);
        }

        .cells {
            display: grid;
            gap: 1px;
            background: var(--border);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .cells--3 {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }

        .cells--4 {
            grid-template-columns: repeat(4, minmax(0, 1fr));
        }

        .cell {
            background: var(--background);
            padding: var(--cell-pad) 32px;
            transition: background-color 0.18s ease;
        }

        .cell:hover {
            background: var(--muted);
        }

        .cell__icon {
            width: 40px;
            height: 40px;
            display: grid;
            place-items: center;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: var(--card);
            color: var(--muted-foreground);
            transition: color 0.18s ease, border-color 0.18s ease;
        }

        .cell:hover .cell__icon {
            color: var(--foreground);
            border-color: var(--border-strong);
        }

        .cell__icon svg {
            width: 19px;
            height: 19px;
        }

        .cell__title {
            font-size: 20px;
            line-height: 1.3;
            margin-bottom: 10px;
        }

        .cell__text {
            color: var(--muted-foreground);
            font-size: 15px;
        }

        .cell__text code {
            color: var(--foreground);
            font-size: 13.5px;
        }

        .cell__action {
            margin-top: 24px;
        }

        .stat {
            padding: 32px;
            background: var(--background);
            text-align: center;
        }

        .stat__num {
            font-family: var(--font-display);
            font-size: 34px;
            font-weight: 500;
            letter-spacing: -0.025em;
            color: var(--foreground);
            display: block;
            line-height: 1.1;
        }

        .stat__label {
            font-family: var(--font-mono);
            font-size: 11px;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: var(--faint);
            margin-top: 8px;
            display: block;
        }

        .cta {
            padding: var(--gap) var(--gutter);
            text-align: center;
        }

        .cta h2 {
            font-size: 44px;
            line-height: 1.15;
            margin-bottom: 14px;
        }

        .cta p {
            color: var(--muted-foreground);
            font-size: 18px;
            margin-bottom: 32px;
        }

        .footer {
            background: var(--background);
            border-top: 1px solid var(--border);
        }

        .footer__grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 1px;
            background: var(--border);
        }

        .footer__col {
            background: var(--background);
            padding: 48px 32px;
        }

        .footer .footer__brand {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
            font-family: var(--font-mono);
            font-size: 14px;
            font-weight: 500;
            color: var(--foreground);
        }

        .footer__credit {
            color: var(--muted-foreground);
            font-size: 14px;
            max-width: 300px;
        }

        .footer__credit svg {
            vertical-align: -1px;
            color: var(--muted-foreground);
        }

        .footer__col h4 {
            font-family: var(--font-mono);
            font-size: 12px;
            font-weight: 500;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            color: var(--muted-foreground);
            margin-bottom: 18px;
        }

        .footer__col a {
            display: block;
            color: var(--muted-foreground);
            font-size: 14.5px;
            margin-bottom: 11px;
        }

        .footer__col .footer__credit a {
            display: inline;
            margin: 0;
        }

        .footer__col a:hover {
            color: var(--foreground);
        }

        .footer__base {
            border-top: 1px solid var(--border);
            padding: 20px var(--gutter);
            font-family: var(--font-mono);
            font-size: 12px;
            letter-spacing: 0.06em;
            color: var(--faint);
            text-align: center;
        }

        @media screen and (max-width: 1023px) {
            :root {
                --gap: 72px;
                --cell-pad: 32px;
            }

            .cells--4 {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .hero__title {
                font-size: 56px;
            }

            .footer__grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media screen and (max-width: 767px) {
            :root {
                --gutter: 16px;
                --gap: 56px;
                --cell-pad: 28px;
            }

            .main::after {
                display: none;
            }

            .navbar-burger {
                display: flex;
            }

            .nav__links {
                display: none;
                position: absolute;
                top: 64px;
                left: 0;
                right: 0;
                flex-direction: column;
                align-items: stretch;
                gap: 0;
                margin: 0;
                padding: 6px 0;
                background: var(--background);
                border-bottom: 1px solid var(--border);
            }

            .nav__links.is-active {
                display: flex;
            }

            .nav__link {
                padding: 11px var(--gutter);
            }

            .hero {
                padding: 72px var(--gutter) 64px;
            }

            .hero__title {
                font-size: 38px;
                letter-spacing: -0.02em;
                line-height: 1.15;
            }

            .hero__lead {
                font-size: 16.5px;
            }

            .band__head h2,
            .cta h2 {
                font-size: 28px;
            }

            .cells--3,
            .cells--4 {
                grid-template-columns: 1fr;
            }

            .hero__cmd code {
                font-size: 12px;
            }

            .footer__grid {
                grid-template-columns: 1fr;
            }

            .footer__col {
                padding: 32px var(--gutter);
            }
        }
    </style>
</head>

<body>
    <header class="nav">
        <div class="shell nav__inner">
            <div class="nav__left">
                <a class="nav__brand" href="{{ url('/') }}">Rakit</a>
                <nav id="navMenuMore" class="nav__links">
                    <a class="nav__link is-current" href="{{ url('/') }}">Home</a>
                    <a class="nav__link" href="{{ url('docs') }}">Docs</a>
                    <a class="nav__link" href="https://rakit.esyede.my.id/repositories" target="_blank">Packages</a>
                    <a class="nav__link" href="https://rakit.esyede.my.id/api/main/index.html" target="_blank">API</a>
                    <a class="nav__link" href="https://github.com/esyede/rakit/discussions" target="_blank">Forum</a>
                    <a class="nav__link" href="https://github.com/esyede/rakit" target="_blank">Github</a>
                </nav>
            </div>
            <div class="nav__right">
                @if (Route::has('login'))
                    <div class="nav__session">
                        @guest
                            <a class="btn btn--ghost btn--sm" href="{{ url('/login') }}">Login</a>
                            <a class="btn btn--primary btn--sm" href="{{ url('/register') }}">Register</a>
                        @else
                            <a class="btn btn--ghost btn--sm" href="{{ url('/dashboard') }}">Dashboard</a>
                        @endguest
                    </div>
                @endif
                <button type="button" class="theme-toggle" id="themeToggle" title="Toggle theme"
                    aria-label="Toggle theme">
                    <svg class="theme-toggle__moon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z" />
                    </svg>
                    <svg class="theme-toggle__sun" viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="4" />
                        <path
                            d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                    </svg>
                </button>
                <button type="button" class="navbar-burger" data-target="navMenuMore" aria-label="Menu">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </div>
    </header>

    <main class="main">
        <section class="hero">
            <span class="hero__badge">You are running <b>Rakit {{ RAKIT_VERSION }}</b></span>
            <h1 class="hero__title">
                Your application<br>
                is <em>up and running</em>.
            </h1>
            <p class="hero__lead">
                This page comes from <code>application/views/home.blade.php</code>, served by the
                <code>home@index</code> action. Edit it, or drop it and route your own.
            </p>
            <div class="hero__actions">
                <a class="btn btn--primary" href="{{ url('docs') }}">Read the docs</a>
                <a class="btn btn--ghost" href="{{ url('docs/structure') }}">Directory structure</a>
            </div>
            <div class="hero__cmd">
                <code><span class="prompt">$</span> php rakit serve</code>
            </div>
        </section>

        <div class="band">
            <div class="shell shell--flush">
                <div class="cells cells--4">
                    <div class="stat">
                        <span class="stat__num">5.4 &rarr; 8.x</span>
                        <span class="stat__label">PHP supported</span>
                    </div>
                    <div class="stat">
                        <span class="stat__num">~ 1 MB</span>
                        <span class="stat__label">GZipped, docs included</span>
                    </div>
                    <div class="stat">
                        <span class="stat__num">0</span>
                        <span class="stat__label">Runtime dependencies</span>
                    </div>
                    <div class="stat">
                        <span class="stat__num">MIT</span>
                        <span class="stat__label">License</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="band">
            <div class="band__head">
                <span class="eyebrow">Start here</span>
                <h2>Three files to know</h2>
                <p>Everything a request touches lives under <code>application/</code>. Open one and keep going.</p>
            </div>

            <div class="shell shell--flush">
                <div class="cells cells--3">
                    <article class="cell">
                        <span class="cell__icon">
                            <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="6" cy="16" r="3" />
                                <circle cx="26" cy="7" r="3" />
                                <circle cx="26" cy="25" r="3" />
                                <path d="M9 16 L14 16 C19 16 19 7 23 7 M14 16 C19 16 19 25 23 25" />
                            </svg>
                        </span>
                        <h3 class="cell__title">Routes</h3>
                        <p class="cell__text">
                            Map a URL to a closure or a controller action in
                            <code>application/routes.php</code>. Groups, middleware and named routes included.
                        </p>
                        <p class="cell__action">
                            <a class="btn btn--ghost btn--sm" href="{{ url('docs/routing') }}">Routing</a>
                        </p>
                    </article>

                    <article class="cell">
                        <span class="cell__icon">
                            <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M4 5 L28 5 28 27 4 27 Z M4 11 L28 11 M11 11 L11 27" />
                            </svg>
                        </span>
                        <h3 class="cell__title">Controllers &amp; views</h3>
                        <p class="cell__text">
                            Actions live in <code>application/controllers/</code>, templates in
                            <code>application/views/</code> &mdash; plain PHP or Blade, your call.
                        </p>
                        <p class="cell__action">
                            <a class="btn btn--ghost btn--sm" href="{{ url('docs/controllers') }}">Controllers</a>
                        </p>
                    </article>

                    <article class="cell">
                        <span class="cell__icon">
                            <svg viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M2 4 L30 4 30 28 2 28 Z M8 12 L13 17 8 22 M17 22 L24 22" />
                            </svg>
                        </span>
                        <h3 class="cell__title">Console</h3>
                        <p class="cell__text">
                            Scaffold controllers, models and migrations with
                            <code>php rakit make:*</code>, then run them from the same CLI.
                        </p>
                        <p class="cell__action">
                            <a class="btn btn--ghost btn--sm" href="{{ url('docs/console') }}">Console</a>
                        </p>
                    </article>
                </div>
            </div>
        </div>

        <section class="band">
            <div class="cta">
                <h2>Support the project</h2>
                <p>Rakit is maintained by volunteers. Every contribution keeps it moving.</p>
                <div class="hero__actions">
                    <a class="btn btn--primary" href="https://ko-fi.com/A0A61UOVND" target="_blank" rel="noopener">
                        <svg class="btn__icon" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill="currentColor"
                                d="M11.8 1c-1.682 0-3.129 1.368-3.799 2.797-0.671-1.429-2.118-2.797-3.8-2.797-2.318 0-4.2 1.882-4.2 4.2 0 4.716 4.758 5.953 8 10.616 3.065-4.634 8-6.050 8-10.616 0-2.319-1.882-4.2-4.2-4.2z" />
                        </svg>
                        Buy me a coffee
                    </a>
                    <a class="btn btn--ghost" href="https://github.com/esyede/rakit/discussions" target="_blank">Join
                        the forum</a>
                </div>
            </div>
        </section>
    </main>

    <footer class="footer">
        <div class="shell shell--flush">
            <div class="footer__grid">
                <div class="footer__col">
                    <a class="footer__brand" href="{{ url('/') }}">Rakit</a>
                    <p class="footer__credit">
                        Made with
                        <svg width="11" height="11" viewBox="0 0 16 16" aria-hidden="true">
                            <path fill="currentColor"
                                d="M11.8 1c-1.682 0-3.129 1.368-3.799 2.797-0.671-1.429-2.118-2.797-3.8-2.797-2.318 0-4.2 1.882-4.2 4.2 0 4.716 4.758 5.953 8 10.616 3.065-4.634 8-6.050 8-10.616 0-2.319-1.882-4.2-4.2-4.2z" />
                        </svg>
                        by awesome
                        <a href="https://github.com/esyede/rakit/contributors" target="_blank">Contributors</a>.
                        Released under the
                        <a href="https://github.com/esyede/rakit/blob/main/LICENSE" target="_blank">MIT License</a>.
                    </p>
                </div>
                <div class="footer__col">
                    <h4>Resources</h4>
                    <a href="{{ url('docs') }}">Documentation</a>
                    <a href="https://rakit.esyede.my.id/repositories" target="_blank">Packages</a>
                    <a href="https://rakit.esyede.my.id/api/main/index.html" target="_blank">API Reference</a>
                </div>
                <div class="footer__col">
                    <h4>Community</h4>
                    <a href="https://github.com/esyede/rakit/discussions" target="_blank">Forum</a>
                    <a href="https://github.com/esyede/rakit" target="_blank">Github</a>
                    <a href="https://github.com/esyede/rakit/contributors" target="_blank">Contributors</a>
                </div>
                <div class="footer__col">
                    <h4>Get started</h4>
                    <a href="{{ url('docs/install') }}">Installation</a>
                    <a href="{{ url('docs/structure') }}">Directory structure</a>
                    <a href="{{ url('docs/changelog') }}">Release notes</a>
                </div>
            </div>
        </div>
        <p class="footer__base">Rakit {{ RAKIT_VERSION }} &mdash; PHP 5.4 to 8.x</p>
    </footer>

    <script>
        (function () {
            var button = document.getElementById('themeToggle');

            if (button) {
                button.addEventListener('click', function () {
                    var root = document.documentElement;
                    var dark = !root.classList.contains('dark');

                    root.classList.toggle('dark', dark);

                    try {
                        localStorage.setItem('theme', dark ? 'dark' : 'light');
                    } catch (e) {}
                });
            }

            var burger = document.querySelector('.navbar-burger');
            var menu = document.getElementById('navMenuMore');

            if (!burger || !menu) {
                return;
            }

            burger.addEventListener('click', function () {
                burger.classList.toggle('is-active');
                menu.classList.toggle('is-active');
            });

            menu.addEventListener('click', function (e) {
                if (e.target.tagName === 'A') {
                    burger.classList.remove('is-active');
                    menu.classList.remove('is-active');
                }
            });
        })();
    </script>
</body>

</html>
