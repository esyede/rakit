<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <meta name="generator" content="Rakit">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <title>500 | Internal Server Error</title>
    <style>
        /* Halaman error sengaja berdiri sendiri: seluruh gaya ditulis inline
           agar tetap tampil benar walaupun aset gagal dimuat. */

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

        /* Kisi blueprint, sama seperti hero situs. */
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
            background: radial-gradient(ellipse 45% 40% at 50% 45%, rgba(90, 69, 255, .13), transparent 70%);
        }

        .panel {
            width: 100%;
            max-width: 560px;
            text-align: center;
        }

        .eyebrow {
            display: block;
            margin-bottom: 28px;
            font-family: var(--mono);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: .14em;
            text-transform: uppercase;
            color: var(--faint);
        }

        .code {
            font-size: clamp(72px, 16vw, 132px);
            font-weight: 700;
            line-height: 1;
            letter-spacing: -.04em;
            color: var(--soft);
        }

        .rule {
            height: 1px;
            margin: 30px auto;
            background: var(--rule);
        }

        .message {
            font-family: var(--mono);
            font-size: 13px;
            font-weight: 600;
            letter-spacing: .16em;
            text-transform: uppercase;
            color: var(--text);
        }

        .hint {
            margin: 12px 0 0;
            font-size: 15px;
            color: var(--dim);
        }

        .back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 36px;
            padding: 10px 24px;
            border: 1px solid var(--rule-strong);
            color: var(--text);
            font-size: 14.5px;
            text-decoration: none;
            transition: border-color .18s ease, color .18s ease;
        }

        .back:hover {
            border-color: var(--dim);
            color: #fff;
        }

        .back:focus-visible {
            outline: 2px solid var(--primary);
            outline-offset: 2px;
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                transition-duration: .01ms !important;
            }
        }
    </style>
</head>

<body>
    <div class="panel">
        <span class="eyebrow">Error &mdash; 500</span>
        <div class="code">500</div>
        <div class="rule"></div>
        <div class="message">Internal Server Error</div>
        <p class="hint">Something went wrong on our end.</p>
        <a class="back" href="{{ url('/') }}">
            <svg width="15" height="15" viewBox="0 0 32 32" fill="none" stroke="currentColor" stroke-width="2"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M28 16 L4 16 M12 8 L4 16 12 24" />
            </svg>
            Back to home
        </a>
    </div>
</body>

</html>
