# View & Response

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating View](#creating-view)
  - [View Locations](#view-locations)
  - [Check View Exists](#check-view-exists)
- [Passing Data to View](#passing-data-to-view)
- [Nested Views](#nested-views)
- [Sharing Data](#sharing-data)
- [View Composers](#view-composers)
- [Named Views](#named-views)
- [View Rendering](#view-rendering)
  - [Get Rendered Content](#get-rendered-content)
  - [Render Each](#render-each)
- [Response Types](#response-types)
  - [String & View Response](#string--view-response)
  - [JSON Response](#json-response)
  - [JSONP Response](#jsonp-response)
  - [Model Response](#model-response)
  - [File & Download Response](#file--download-response)
  - [Error Response](#error-response)
- [Response Headers & Status](#response-headers--status)
- [Redirect Responses](#redirect-responses)
  - [Redirect with Flash Data](#redirect-with-flash-data)
  - [Redirect with Input](#redirect-with-input)
- [Practical Example](#practical-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Views hold the HTML sent back to the client, separate from your routes and
controllers. They live in `application/views/` as either plain PHP (`.php`) or
[Blade](/docs/views/templating) (`.blade.php`) files. This page also covers the
`Response` and `Redirect` classes used to return them.

<a id="basic-view"></a>
<a id="creating-view"></a>
## Creating View

Create `application/views/home.php`:

```php
<html>
<body>
    <h1>Welcome to Rakit!</h1>
</body>
</html>
```

Return it from a route or a controller with `View::make()`:

```php
Route::get('/', function () {
    return View::make('home');
});
```

<a id="view-locations"></a>
### View Locations

Use dots for subfolders and `package::` for views in a [package](/docs/packages):

```php
View::make('user.profile.edit'); // application/views/user/profile/edit.php
View::make('admin::dashboard');  // packages/admin/views/dashboard.php
```

A `path: ` prefix renders a `.php` file by its path. The file must sit inside
the application, system, storage or a package directory:

```php
View::make('path: ' . path('app') . 'views/home.php');
```

<a id="check-view-exists"></a>
### Check View Exists

```php
if (View::exists('home')) {
    // ...
}

$path = View::exists('home', true); // full path, or false
```

<a id="with-method"></a>
<a id="array-data"></a>
<a id="compact"></a>
<a id="magic-method"></a>
<a id="constructor-data"></a>
<a id="passing-data-to-view"></a>
## Passing Data to View

Pass an array as the second argument, or use `with()`. Each key becomes a
variable in the view:

```php
return View::make('user.profile', compact('user', 'posts'));

return View::make('user.profile')
    ->with('user', $user)
    ->with(['posts' => $posts, 'title' => 'Profile']);
```

Dynamic `with_*` methods, properties and array access work too:

```php
$view = View::make('user.profile')->with_user($user);
$view->posts = $posts;
$view['title'] = 'Profile';
```

In the view, simply use the variables:

```php
<title><?php echo $title; ?></title>
```

> Every view also receives an `$errors` variable: the validation errors flashed
> to the session, or an empty `Messages` object.

<a id="nested-views"></a>
## Nested Views

A view can be passed as data to another view; it is rendered automatically.
`nest()` is a shortcut for that:

```php
return View::make('layouts.master')
    ->with('title', 'Home Page')
    ->nest('content', 'home.index', ['posts' => $posts]);
```

You can also render a partial directly inside a view:

```php
<header><?php echo View::make('partials.header'); ?></header>
<main><?php echo $content; ?></main>
```

<a id="share-to-all-views"></a>
<a id="share-from-base-controller"></a>
<a id="sharing-data"></a>
## Sharing Data

`View::share()` makes a value available to every view. Call it in
`application/composers.php` or a base controller constructor:

```php
View::share('app_name', Config::get('application.name'));

class Base_Controller extends Controller
{
    public function __construct()
    {
        View::share('current_user', Auth::user());
    }
}
```

On a view instance, `shares($key, $value)` does the same and returns the view.

<a id="defining-composer"></a>
<a id="multiple-views"></a>
<a id="use-case"></a>
<a id="view-composers"></a>
## View Composers

A composer is a closure that runs every time a view is rendered, useful for data
a partial always needs. Register them in `application/composers.php`:

```php
View::composer('partials.sidebar', function ($view) {
    $view->with('categories', Category::all());
    $view->with('popular_posts', Post::order_by('views', 'desc')->take(5)->get());
});

View::composer(['user.profile', 'user.settings'], function ($view) {
    $view->with('user', Auth::user());
});
```

> View names must match exactly; wildcards such as `admin.*` are not supported.
> `composers.php` is loaded right after `routes.php`. A [package](/docs/packages)
> can have its own `composers.php` as long as it also has a `routes.php`.

<a id="named-views"></a>
## Named Views

Give a view a short name with `View::name()`, then create it with `View::of()`:

```php
View::name('layouts.master', 'layout');

return View::of('layout', $data);
```

<a id="view-rendering"></a>
## View Rendering

<a id="get-rendered-content"></a>
### Get Rendered Content

`render()` returns the view as a string. Casting a view to string does the same:

```php
$html = View::make('email.welcome', ['user' => $user])->render();
```

<a id="render-each"></a>
### Render Each

`View::render_each()` renders a partial once per item of a plain array. Each
partial receives the item under the given name, plus `$key`:

```php
$users = User::all()->all(); // plain array

$html = View::render_each('user.item', $users, 'user');
```

The fourth argument is shown when the array is empty: a view name, or literal
text prefixed with `raw|`:

```php
View::render_each('user.item', $users, 'user', 'user.empty');
View::render_each('user.item', $users, 'user', 'raw|<p>No users found.</p>');
```

<a id="response-types"></a>
## Response Types

<a id="string-response"></a>
<a id="view-response"></a>
<a id="string--view-response"></a>
### String & View Response

Routes can return a string or a view directly. Use `Response::make()` or
`Response::view()` when you need a status code or headers:

```php
return 'Hello World!';
return Response::make('Hello World!', 200, ['Content-Type' => 'text/plain']);
return Response::view('home', ['title' => 'Home'], 200, ['Cache-Control' => 'max-age=3600']);
return Response::no_content(); // empty 204 response
```

<a id="json-response"></a>
### JSON Response

`Response::json($data, $status, $headers, $json_options)` encodes the data and
sets `Content-Type: application/json`:

```php
return Response::json($users);
return Response::json($user, 201);
return Response::json($data, 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
```

<a id="jsonp-response"></a>
### JSONP Response

```php
return Response::jsonp(Input::get('callback', 'callback'), $users);
// callback([{"id":1,"name":"Budi"}]);
```

The callback name must be a valid JavaScript identifier, otherwise an exception
is thrown. Status and headers are the 3rd and 4th arguments.

<a id="model-response"></a>
### Model Response

`Response::facile()` converts a Facile model (or an array of them) to JSON:

```php
return Response::facile(User::find($id));
return Response::facile(User::all());
```

<a id="download-response"></a>
<a id="file--download-response"></a>
### File & Download Response

`download()` forces a download, `file()` displays the file inline (e.g. a PDF
or image). Both take extra headers:

```php
return Response::download($path);
return Response::download($path, 'invoice-' . $id . '.pdf', ['Content-Type' => 'application/pdf']);
return Response::file($path);
```

> Paths are confined to the base, application and storage directories (plus
> any listed in `application.download_roots`); `..` and stream wrappers throw an
> exception. Still, never build a path from raw user input: use `basename()`.

<a id="error-response"></a>
### Error Response

`Response::error()` renders `application/views/error/{code}.php` (falling back
to `error/unknown.php`) with `$code` and `$message` variables:

```php
return Response::error(404);
return Response::error(429, ['Retry-After' => 3600]);
```

When the client prefers JSON (`Accept: application/json`), it returns JSON
instead:

```json
{"status": 404, "message": "Not Found"}
```

<a id="response-headers"></a>
<a id="set-headers"></a>
<a id="content-type"></a>
<a id="response-status-code"></a>
<a id="response-headers--status"></a>
## Response Headers & Status

Pass headers as the third argument to `make()`, or set them later. All setters
return the response, so they chain:

```php
return Response::make($csv, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="export.csv"',
]);

return Response::make('content')
    ->header('X-Request-ID', uniqid())
    ->with_headers(['Cache-Control' => 'no-cache'])
    ->with_cookie('last_seen', time(), 60)
    ->with_status_code(201);
```

| Method                                                                     | Description                                    |
| -------------------------------------------------------------------------- | ---------------------------------------------- |
| `header($name, $value)`                                                    | Set one header                                 |
| `with_headers(array $headers)`                                             | Set several headers                            |
| `with_cookie($name, $value, $minutes, $path, $domain, $secure, $samesite)` | Attach a cookie                                |
| `with_status_code($code)`                                                  | Change the status code                         |
| `status($status = null)`                                                   | Get the status code, or set it                 |
| `headers()`                                                                | The header bag (`->get()`, `->has()`, `->all()`) |
| `render()`                                                                 | The content as a string                        |

<a id="basic-redirect"></a>
<a id="redirect-to-named-route"></a>
<a id="redirect-back"></a>
<a id="redirect-responses"></a>
## Redirect Responses

All redirect methods take an optional status code (default `302`) as the last
argument:

```php
return Redirect::to('user/profile');
return Redirect::to('new-url', 301);
return Redirect::to_route('user.profile', [$user_id]);
return Redirect::back();
return Redirect::away('https://example.com');
```

> `Redirect::to()` only accepts local URLs and throws for external ones; use
> `away()` for those. `back()` falls back to `/` (or the `$fallback` second
> argument) when the referrer is missing or points to another host.

| Method                                         | Redirects to                                                 |
| ---------------------------------------------- | ------------------------------------------------------------ |
| `home()`                                       | The application home URL                                     |
| `back($status, $fallback)`                     | The previous page                                            |
| `to_action('home@index', $params)`             | A controller action                                          |
| `secure($url)`                                 | The local URL over HTTPS                                     |
| `refresh()`                                    | The current URI                                              |
| `guest($url)`                                  | `$url`, remembering the current URL in the session           |
| `intended($default = '/')`                     | The URL remembered by `guest()`, or `$default`               |

<a id="redirect-with-flash-data"></a>
### Redirect with Flash Data

`with()` flashes a value to the session for the next request (a session driver
must be configured):

```php
return Redirect::to('dashboard')->with('message', 'Welcome back!');
```

```php
<?php if (Session::has('message')): ?>
    <div class="alert"><?php echo Session::get('message'); ?></div>
<?php endif; ?>
```

<a id="redirect-with-input"></a>
### Redirect with Input

`with_input()` flashes the input so forms can be refilled with `Input::old()`.
`with_errors()` flashes a validator's errors (or a `Messages` object) as
`errors`:

```php
return Redirect::back()->with_input()->with_errors($validation);
return Redirect::back()->with_input('only', ['name', 'email']);
return Redirect::back()->with_input('except', ['password']);
```

<a id="practical-examples"></a>
<a id="practical-example"></a>
## Practical Example

```php
class Post_Controller extends Controller
{
    public function action_show($id)
    {
        $post = Post::find($id);

        if (! $post) {
            return Response::error(404);
        }

        return Request::expects_json()
            ? Response::facile($post)
            : View::make('posts.show', compact('post'));
    }

    public function action_store()
    {
        $validation = Validator::make(Input::all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validation->fails()) {
            return Redirect::back()->with_input()->with_errors($validation);
        }

        // Post needs a $fillable list: Model::$guarded defaults to ['*']
        $post = Post::create(Input::only('title', 'content'));

        return Redirect::to_route('posts.show', [$post->id])
            ->with('message', 'Post created successfully!');
    }
}
```
