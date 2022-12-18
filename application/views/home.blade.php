<!DOCTYPE html>
<html lang="{{ config('application.language' . 'en') }}">

<head>
    <title>Welcome!</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
    <style>
        html,
        body {
            background-color: #fff;
            color: #636b6f;
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
            color: #636b6f;
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

        @if (System\Routing\Route::has('login'))
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
                <a href="{{ url('/docs/' . config('application.language')) }}">Docs</a>
                <a href="https://rakit.esyede.my.id/api/v{{ RAKIT_VERSION }}">API</a>
                <a href="https://rakit.esyede.my.id/repositories">Packages</a>
                <a href="https://github.com/esyede/rakit/discussions">Forum</a>
                <a href="https://github.com/esyede/rakit">GitHub</a>
            </div>
        </div>
    </div>
</body>

</html>
