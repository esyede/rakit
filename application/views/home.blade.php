<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}">

<head>
    <title>Welcome!</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <script>
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme === 'dark') {
            document.documentElement.classList.add('dark');
        } else if (savedTheme === null && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    </script>
    <style>
        :root {
            --background: #fff;
            --foreground: #636b6f;
            --primary: #636b6f;
        }

        .dark {
            --background: #282c34;
            --foreground: #abb2bf;
            --primary: #61afef;
        }

        html,
        body {
            background-color: var(--background);
            color: var(--foreground);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial,
                sans-serif, "Apple Color Emoji", "Segoe UI Emoji";
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .links>a {
            color: var(--primary);
            padding: 0 25px;
            font-size: 13px;
            font-weight: 600;
            letter-spacing: 0.1rem;
            text-decoration: none;
            text-transform: uppercase;
        }

        .m-b-md {
            margin-bottom: 30px;
        }
    </style>
</head>

<body>
    <div class="flex-center position-ref full-height">

        @if (Route::has('login'))
            <div class="top-right links">
                @guest
                    <a href="{{ url('/login') }}">Login</a>
                    <a href="{{ url('/register') }}">Register</a>
                @else
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                @endguest
            </div>
        @endif

        <div class="content">
            <div class="title m-b-md">rakit</div>
            <div class="links">
                <a href="{{ url('/docs') }}">Docs</a>
                <a href="https://rakit.esyede.my.id/api/main/index.html">API</a>
                <a href="https://rakit.esyede.my.id/repositories">Packages</a>
                <a href="https://github.com/esyede/rakit/discussions">Forum</a>
                <a href="https://github.com/esyede/rakit">GitHub</a>
            </div>
        </div>
    </div>
</body>

</html>
