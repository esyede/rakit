<!DOCTYPE html>
<html lang="{{ config('application.language', 'id') }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" type="image/png" href="data:;base64,iVBORw0KGgo=">
        <title>{{ trans('home.title') }}</title>
        <link rel="stylesheet" href="{{ asset('home/css/style.min.css?v='.RAKIT_VERSION) }}">
    </head>
    <body>
        <header>
            <h1>RAKIT {{ RAKIT_VERSION }}</h1>
            <h5>{{ trans('home.slogan') }}</h5>
        </header>

        <h3>{{ trans('home.about.heading') }}</h3>
        <p>{{ trans('home.about.text1') }}</p>
        <pre>application/views/home/index.blade.php</pre>

        <p>{{ trans('home.about.text2') }}</p>
        <pre>application/routes.php</pre>

        <br>

        <h3>{{ trans('home.docs.heading') }}</h3>
        <p>{!! trans('home.docs.text', ['documentation' => '<a href="'.url('docs/'.config('application.language')).'" target="_blank">'.trans('home.documentation').'</a>']) !!}</p>

        <br>

        <h3>{{ trans('home.resources.heading') }}</h3>
        <p>{{ trans('home.resources.text') }}</p>
        <ul class="none">
            <li><a href="https://rakit.esyede.my.id" target="_blank">{{ trans('home.links.site') }}</a></li>
            <li><a href="https://rakit.esyede.my.id/api/v{{ RAKIT_VERSION }}/index.html" target="_blank">{{ trans('home.links.api') }}</a></li>
            <li><a href="https://rakit.esyede.my.id/repositories" target="_blank">{{ trans('home.links.repos') }}</a></li>
            <li><a href="https://rakit.esyede.my.id/forum" target="_blank">{{ trans('home.links.forum') }}</a></li>
            <li><a href="https://github.com/esyede/rakit">{{ trans('home.links.source') }}</a></li>
        </ul>

        <br>
        <br>
        <br>
    </body>
</html>
