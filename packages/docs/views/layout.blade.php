<!DOCTYPE html>
<html lang="id">

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

        <div class="divider is-white"></div>
        <div class="divider is-white"></div>
        <div class="divider is-white"></div>

        @include('docs::partials.footer')

    </body>
</html>
