<!DOCTYPE html>
<html lang="{{ config('application.language', 'id') }}">
@include('docs::partials.header')

<body class="has-background-white">
    @include('docs::partials.navbar')
    <section class="section">
        <div class="container">
            <div class="columns">
                @yield('sidebar')
                @yield('content')
            </div>
        </div>
    </section>
    <br>
    <br>
    <br>
    <br>
    @include('docs::partials.footer')
</body>

</html>
