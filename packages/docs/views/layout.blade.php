<!DOCTYPE html>
<html lang="{{ config('application.language', 'en') }}" class="dark">

@include('docs::partials.header')

<body>
    @include('docs::partials.navbar')

    <main class="main">
        <div class="shell">
            <div class="docs">
                @yield('sidebar')
                @yield('content')
            </div>
        </div>
    </main>

    @include('docs::partials.footer')
</body>

</html>
