# Routing

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Introduction](#introduction)
- [Basic Routing](#basic-routing)
  - [HTTP Methods](#http-methods)
  - [Multiple Methods & URIs](#multiple-methods--uris)
  - [Redirect & View Routes](#redirect--view-routes)
- [Route Parameters](#route-parameters)
  - [Required Parameters](#required-parameters)
  - [Optional Parameters](#optional-parameters)
  - [Wildcard Constraints](#wildcard-constraints)
  - [Route Model Binding](#route-model-binding)
- [Named Routes](#named-routes)
  - [Generating URLs](#generating-urls)
  - [Inspecting the Current Route](#inspecting-the-current-route)
- [Route Groups](#route-groups)
  - [Group Middleware](#group-middleware)
  - [Group Prefix](#group-prefix)
  - [Group Name Prefix](#group-name-prefix)
  - [Nested Groups](#nested-groups)
  - [Domain Routing](#domain-routing)
- [Middleware](#middleware)
  - [Defining Middleware](#defining-middleware)
  - [Attaching Middleware](#attaching-middleware)
  - [Middleware Parameters](#middleware-parameters)
  - [Pattern Middleware](#pattern-middleware)
  - [Global Middleware](#global-middleware)
  - [CSRF Exceptions](#csrf-exceptions)
  - [Rate Limiting](#rate-limiting)
- [Controller Routing](#controller-routing)
  - [Controller Actions](#controller-actions)
  - [Auto-Routed Controllers](#auto-routed-controllers)
  - [Resource Routes](#resource-routes)
  - [API Resources & Singletons](#api-resources--singletons)
- [Package Routes](#package-routes)
- [404 Handler](#404-handler)
- [Listing & Testing Routes](#listing--testing-routes)
- [Helpers](#helpers)
  - [Route::forward()](#routeforward)
  - [Route::has() and Route::lists()](#routehas-and-routelists)
  - [Route Matched Event](#route-matched-event)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

A route maps an HTTP method and URI to a closure or a controller action.
Application routes live in `application/routes.php`; middleware lives in
`application/middlewares.php`.

When a request comes in, a route with an exact (static) URI wins first, then
wildcard routes are tried in the order they were registered.

<a id="basic-routing"></a>
<a id="simple-route"></a>

## Basic Routing

The simplest route takes a URI and a closure:

```php
Route::get('/', function () {
    return 'Hello World!';
});
```

<a id="http-methods"></a>

### HTTP Methods

There is a register method for every HTTP verb:

```php
Route::get($uri, $action);
Route::post($uri, $action);
Route::put($uri, $action);
Route::patch($uri, $action);
Route::delete($uri, $action);
Route::head($uri, $action);
Route::options($uri, $action);
Route::trace($uri, $action);
Route::connect($uri, $action);
```

A `HEAD` request falls back to the matching `GET` route when no `HEAD` route
exists. HTML forms can reach `PUT`, `PATCH` and `DELETE` routes through a hidden
`_method` field (see [Requests](/docs/requests)).

<a id="multiple-methods--uris"></a>
<a id="route-multiple-methods"></a>

### Multiple Methods & URIs

`Route::any()` answers every HTTP method:

```php
Route::any('api/webhook', function () {
    return 'Webhook handler';
});
```

To give one action to a specific list of method/URI pairs, use `Route::share()`:

```php
Route::share([
    ['GET', 'admin/dashboard'],
    ['POST', 'admin/dashboard'],
], 'admin@dashboard');
```

A route may also register several URIs at once, as an array or a
comma-separated string:

```php
Route::get(['/', 'home', 'index'], function () {
    return View::make('home');
});

Route::get('/, home, index', 'home@index'); // same thing
```

<a id="redirect--view-routes"></a>
<a id="route-shortcut"></a>
<a id="route-redirect"></a>
<a id="route-view"></a>

### Redirect & View Routes

`Route::redirect()` and `Route::view()` register `GET` routes that only redirect
or render a view:

```php
Route::redirect('old-page', 'new-page');       // 302
Route::redirect('old-page', 'new-page', 301);

Route::view('/', 'home');
Route::view('about', 'about', ['title' => 'About Us']);
```

<a id="route-parameters"></a>

## Route Parameters

<a id="required-parameters"></a>

### Required Parameters

Wildcards in the URI capture segments and pass them to the action in order:

```php
Route::get('user/(:num)', function ($id) {
    return 'User ID: ' . $id;
});

Route::get('user/(:num)/post/(:any)', function ($user_id, $slug) {
    return "User $user_id, post $slug";
});
```

<a id="optional-parameters"></a>

### Optional Parameters

Append `?` to a wildcard to make it optional, and give the closure argument a
default:

```php
Route::get('page/(:num?)', function ($page = 1) {
    return 'Page: ' . $page;
});
```

Each optional segment stands on its own, so literal segments may sit between
them. The literal segments are still required (`users/5` does not match below):

```php
// matches users/posts, users/5/posts, users/posts/7 and users/5/posts/7
Route::get('users/(:num?)/posts/(:num?)', function ($user = null, $post = null) {
    // ..
});
```

> A default only applies to a parameter nothing follows. For `users/posts/7` the
> closure receives `$user = ''` rather than `null`, so check with `empty()` when
> an optional sits in front of another segment.

<a id="wildcard-constraints"></a>

### Wildcard Constraints

| Wildcard      | Matches                                          | Example match            |
|---------------|--------------------------------------------------|--------------------------|
| `(:num)`      | digits only                                      | `42`, `1024`             |
| `(:alpha)`    | letters only (a–z, A–Z)                          | `abc`, `XYZ`             |
| `(:alnum)`    | letters or digits                                | `hello42`                |
| `(:any)`      | letters, digits, `.`, `-`, `_`, `%`, `=` (1–255) | `hello-world`, `v1.0`    |
| `(:segment)`  | any single URL segment (no `/`)                  | `posts`, `user-profile`  |
| `(:all)`      | anything, including `/`                          | `path/to/file.txt`       |

Every wildcard has an optional form: `(:num?)`, `(:any?)`, and so on.

> `(:all)` hands you a user-controlled path. Validate or allowlist it before
> using it to read files.

<a id="route-model-binding"></a>

### Route Model Binding

A binding adds a wildcard of its own that passes the record instead of its ID. Define bindings
in `application/routes.php` (or `application/boot.php`) **before** the routes that use them:

```php
Route::model('user', 'User');          // (:user) => User::find($value)
Route::model('post', 'Post', 'slug');  // (:post) => Post::where('slug', '=', $value)->first()

Route::get('users/(:user)/posts/(:post?)', function ($user, $post = null) {
    return View::make('posts.show', compact('user', 'post'));
});
```

A value that matches no record answers **404**, so the action never sees `null` for a given
segment; an optional binding that is left out stays `null`. The wildcard accepts any single
segment, and bound and plain wildcards (`(:num)`, `(:any)`) can be mixed.

For any other lookup, bind a closure. Whatever it returns is passed; `null` or `false` is a 404:

```php
Route::bind('team', function ($code) {
    return Team::where('code', '=', strtoupper($code))->where('active', '=', 1)->first();
});
```

Bindings resolve after the `before` middleware, so `auth` still turns a guest away before any
query runs. Check access to the record itself in the action, for instance with
[`Gate::authorize()`](/docs/auth/authorization). Controller actions receive bound parameters the
same way.

Named routes accept the models back, and use their key, or the column the binding looks up:

```php
URL::to_route('post.show', [$user, $post]); // .../users/7/posts/hello-world
```

A binding name may not reuse a built-in wildcard (`num`, `any` ...).

<a id="named-routes"></a>
<a id="defining-named-routes"></a>

## Named Routes

Give a route a name with the `as` key, so you can refer to it without
hard-coding its URI:

```php
Route::get('user/profile', ['as' => 'profile', function () {
    return View::make('user.profile');
}]);

Route::get('user/(:num)', ['as' => 'user.show', 'uses' => 'user@show']);
```

<a id="generating-urls"></a>
<a id="using-named-routes"></a>

### Generating URLs

Pass the wildcard values as an array, in order:

```php
$url = URL::to_route('user.show', [1]);       // http://example.com/user/1

return Redirect::to_route('user.show', [$user->id]);
```

```blade
<a href="{{ route('profile') }}">Profile</a>
```

See [URLs](/docs/urls#url-to-route) for more.

<a id="inspecting-the-current-route"></a>
<a id="check-route-name"></a>

### Inspecting the Current Route

```php
if (Request::route()->is('home')) {
    // the current route is named "home"
}

if (Route::has('profile')) {
    // a route named "profile" exists
}
```

<a id="route-groups"></a>

## Route Groups

Groups share attributes (`before`, `after`, `prefix`, `as`, `domain`) across many
routes.

<a id="group-middleware"></a>
<a id="group-with-middleware"></a>

### Group Middleware

```php
Route::group(['before' => 'auth'], function () {
    Route::get('dashboard', function () { /* .. */ });
    Route::get('settings', function () { /* .. */ });

    // Runs auth, then csrf: a route's middleware adds to the group's.
    Route::post('settings', ['before' => 'csrf', 'uses' => 'settings@update']);
});
```

A middleware group gives a list of middlewares a name of its own. Define it in
`application/middlewares.php`, then use the name anywhere a middleware goes:

```php
Route::middleware_group('web', 'csrf|throttle:60,1');
Route::middleware_group('admin', ['auth', 'can:admin', 'web']); // groups can include groups

Route::group(['before' => 'admin'], function () {
    // ..
});
```

<a id="group-prefix"></a>
<a id="group-with-prefix"></a>

### Group Prefix

```php
Route::group(['prefix' => 'admin'], function () {
    Route::get('users', function () { /* URL: /admin/users */ });
    Route::get('posts', function () { /* URL: /admin/posts */ });
});
```

<a id="group-name-prefix"></a>

### Group Name Prefix

A group's `as` is put in front of the name of each named route inside it; routes
without a name stay unnamed:

```php
Route::group(['prefix' => 'admin', 'as' => 'admin.'], function () {
    Route::get('users', ['as' => 'users', 'uses' => 'admin.user@index']); // admin.users
    Route::resource('photos');                                           // admin.photos.index, ..
});
```

<a id="nested-groups"></a>

### Nested Groups

Nested groups chain their prefixes and add their middleware to the outer
group's:

```php
Route::group(['prefix' => 'admin', 'before' => 'auth'], function () {
    Route::group(['prefix' => 'system', 'before' => 'role:admin'], function () {
        // URL: /admin/system/users, middleware: auth, role:admin
        Route::get('users', 'admin.user@index');
    });
});
```

<a id="domain-routing"></a>
<a id="group-with-domain"></a>

### Domain Routing

`Route::domain()` limits its routes to one host, which is handy for subdomains
and multi-tenant apps. It can wrap regular groups:

```php
Route::domain('api.example.com', function () {
    Route::group(['prefix' => 'v1', 'before' => 'api.auth'], function () {
        Route::get('users', 'api.users@index');
    });
});
```

A `{placeholder}` in the domain only decides whether the host matches; its value
is not passed to the action, so read it from the host yourself:

```php
Route::domain('{account}.example.com', function () {
    Route::get('/', function () {
        $parts = explode('.', Request::foundation()->getHost());
        return 'Welcome to ' . $parts[0];
    });
});
```

> `URL::to_route()` does not apply the domain: a named route inside a domain
> group still produces a URL on the application URL.

<a id="middleware"></a>

## Middleware

Middleware filters requests before or after a route runs: authentication, CSRF,
rate limiting, logging, and so on.

<a id="defining-middleware"></a>

### Defining Middleware

Register middleware in `application/middlewares.php`. A `before` middleware that
returns a value stops the request and that value becomes the response:

```php
Route::middleware('admin', function () {
    if (! Auth::user()->is_admin) {
        return Response::error(403);
    }
});
```

The application ships with these middlewares:

| Name       | Does                                                                 |
|------------|----------------------------------------------------------------------|
| `csrf`     | Returns a 422 error when a non-safe request carries no valid token   |
| `auth`     | Returns a 401 error for guests                                       |
| `can`      | 403 unless the [ability](/docs/auth/authorization) is granted, `can:<ability>[,<Model>]` |
| `throttle` | Rate limit, `throttle:<max attempts>,<minutes>` or a [named limiter](#rate-limiting) `throttle:<name>`, 429 when exceeded |
| `signed`   | 403 unless the URL carries a valid [signature](/docs/urls#signed-urls); `signed:relative` ignores scheme and host |

> Attaching a middleware that was never registered throws
> `Undefined middleware: <name>`, so a typo cannot leave a route unprotected.

<a id="attaching-middleware"></a>
<a id="middleware-on-route"></a>
<a id="middleware-before--after"></a>

### Attaching Middleware

Use `before` and `after` in the route's action array; separate several with `|`:

```php
Route::get('admin/settings', ['before' => 'auth|admin', function () {
    return View::make('admin.settings');
}]);

Route::get('api/data', [
    'before' => 'auth',
    'after' => 'log_api_call',
    'uses' => 'api@data',
]);
```

An `after` middleware receives the response as its first argument:

```php
Route::middleware('log_api_call', function ($response) {
    Log::info('API status: ' . $response->status());
});
```

<a id="middleware-parameters"></a>
<a id="middleware-with-parameters"></a>

### Middleware Parameters

Parameters follow a `:`, separated by commas, and arrive as the closure's
arguments:

```php
Route::middleware('role', function ($role) {
    if (! Auth::user()->has_role($role)) {
        return Response::error(403);
    }
});

Route::get('admin', ['before' => 'auth|role:admin|throttle:60,1', function () {
    return 'Admin Panel';
}]);
```

<a id="pattern-middleware"></a>
<a id="middleware-pattern"></a>

### Pattern Middleware

Attach middleware to every route whose URI matches a pattern (`*` is a
wildcard; separate several patterns with `, `):

```php
Route::middleware('pattern: admin/*', 'auth|admin');
Route::middleware('pattern: admin/*, user/*', 'auth');

// Or register a closure under a name in one go
Route::middleware('pattern: api/*', ['name' => 'api_auth', function () {
    if (! Request::header('Authorization')) {
        return Response::json(['error' => 'Unauthorized'], 401);
    }
}]);
```

Pattern middleware runs as `before` middleware.

<a id="global-middleware"></a>

### Global Middleware

Middlewares named `before` and `after` run on every request. Unlike other
middlewares they are optional, so nothing breaks if they are not defined. A
package can have its own, named `<package>::before` and `<package>::after`.

```php
Route::middleware('before', function () {
    // runs before every route
});

Route::middleware('after', function ($response) {
    $response->header('X-Frame-Options', 'SAMEORIGIN');
});
```

<a id="csrf-exceptions"></a>

### CSRF Exceptions

Every route that uses the session is checked for the CSRF token on `POST`, `PUT`,
`PATCH` and `DELETE`, whether or not it lists the `csrf` middleware: a form that
forgets `'before' => 'csrf'` is still protected. The token is read from the
`_token` field (`@csrf` in a form) or the `X-Csrf-Token` header, and a request
without a valid one gets a 422. Stateless routes (`session.stateless`, `api/*` by
default) have no session cookie to abuse, so they are not checked.

Turn the check off for a route, or for every route of a group, with `'csrf' => false`
in its action:

```php
Route::post('webhook/payment', ['csrf' => false, function () {
    // ..
}]);

Route::post('webhook/shipping', ['csrf' => false, 'uses' => 'webhook@shipping']);

Route::group(['csrf' => false, 'prefix' => 'webhook'], function () {
    Route::post('sms', 'webhook@sms');
});
```

Turn it off for every route with `'csrf_global' => false` in
`application/config/application.php` (the `csrf` middleware then works per route,
as before). Endpoints that cannot carry the
session token (webhooks, server-to-server callbacks) can be exempted in the same file:

```php
'csrf_except' => [
    'api/webhook', // only this path
    'api/*',       // everything below api/
],
```

Listed URIs skip the token check entirely, so only add what really needs it.

<a id="rate-limiting"></a>

### Rate Limiting

`throttle:60,1` allows 60 requests a minute, counted per route and client IP. For limits that
depend on the user, define a named limiter in `application/boot.php`. Its closure returns the
limit, or `null` for none; requests with the same `by` share a bucket, on every route using the
limiter (without `by`, the client IP):

```php
Throttle::define('api', function () {
    if (Auth::check() && Auth::user()->is_admin) {
        return null; // unlimited
    }

    return [
        'max' => 60,
        'minutes' => 1,
        'by' => Auth::check() ? 'user:'.Auth::user()->id : null,
    ];
});

Route::group(['before' => 'throttle:api'], function () {
    Route::get('api/orders', 'api.orders@index');
});
```

Outside a route, count any key yourself, for instance failed logins per email address:

```php
$key = 'login|'.strtolower(Input::get('email'));

if (Throttle::exceeded(5, 1, $key)) {
    return Throttle::error($key); // 429 with Retry-After
}

if (Auth::attempt($credentials)) {
    Throttle::clear($key);
}
```

> Before this release the middleware was `function ($limit, $minutes)`. An application created
> earlier needs the new definition from `application/middlewares.php` for `throttle:<name>`.

<a id="controller-routing"></a>

## Controller Routing

<a id="controller-actions"></a>
<a id="basic-controller-route"></a>
<a id="route-to-specific-action"></a>

### Controller Actions

Point a route at a controller with `controller@action`. Use dots for controllers
in subfolders, and the `uses` key when the route also needs other options:

```php
Route::get('users', 'user@index');                 // User_Controller::action_index()
Route::get('admin/users', 'admin.user@index');     // controllers/admin/user.php

Route::post('login', [
    'uses' => 'auth@authenticate',
    'as' => 'login.post',
    'before' => 'csrf',
]);
```

See [Controllers](/docs/controllers) for writing the controller itself.

<a id="auto-routed-controllers"></a>
<a id="restful-controller"></a>

### Auto-Routed Controllers

`Route::controller()` registers one catch-all route (any HTTP method) that maps
the next URI segment to an action of the same name and passes the rest as
parameters:

```php
Route::controller('users');
Route::controller(['users', 'posts']);
Route::controller(Controller::detect()); // every controller in application/controllers
```

| URL                  | Calls                        |
|----------------------|------------------------------|
| `/users`             | `action_index()`             |
| `/users/show/123`    | `action_show(123)`           |
| `/users/edit/42`     | `action_edit(42)`            |

Up to five segments are captured after the controller name. A `home`
controller also gets the root of its folder (`Route::controller('home')`
answers `/`). Prefer explicit routes when actions need different HTTP verbs.

<a id="resource-routes"></a>
<a id="resource-controller"></a>

### Resource Routes

`Route::resource()` registers the usual CRUD routes in one call:

```php
Route::resource('posts');
```

| Method | URI                  | Action          | Route Name      |
|--------|----------------------|-----------------|-----------------|
| GET    | `/posts`             | `posts@index`   | `posts.index`   |
| GET    | `/posts/create`      | `posts@create`  | `posts.create`  |
| POST   | `/posts`             | `posts@store`   | `posts.store`   |
| GET    | `/posts/(:any)`      | `posts@show`    | `posts.show`    |
| GET    | `/posts/(:any)/edit` | `posts@edit`    | `posts.edit`    |
| PUT    | `/posts/(:any)`      | `posts@update`  | `posts.update`  |
| DELETE | `/posts/(:any)`      | `posts@delete`  | `posts.delete`  |

```php
Route::resource('posts', ['only' => ['index', 'show']]);
Route::resource('posts', ['except' => ['delete']]);
Route::resource('posts', ['controller' => 'blog.post']); // use Blog_Post_Controller
Route::resource('posts.comments'); // nested: /posts/(:any?)/comments/...
```

> `except` takes the action names above, so it is `delete`, not `destroy`. Any
> option other than `only`, `except` and `controller` replaces the whole route
> table with what you pass.

<a id="api-resources--singletons"></a>

### API Resources & Singletons

`Route::api_resource()` registers a resource without `create` and `edit`, which only
serve HTML forms. `Route::singleton()` is for a resource there is one of, so its URIs
carry no ID:

```php
Route::api_resource('photos'); // index, store, show, update, delete
Route::singleton('profile');   // GET profile, GET profile/edit, PUT profile
```

Both take the `only`, `except` and `controller` options of `resource()`. A singleton's
actions are `show`, `edit` and `update`, named `profile.show` and so on.

<a id="package-routes"></a>
<a id="route-for-package"></a>

## Package Routes

A package defines its routes in `packages/<package>/routes.php`. The
`(:package)` placeholder is replaced by the package's `handles` value from
`application/packages.php`:

```php
// application/packages.php
return [
    'admin' => ['handles' => 'admin'],
];

// packages/admin/routes.php
Route::get('(:package)', function () {          // URL: /admin
    return 'Admin Dashboard';
});

Route::get('(:package)/settings', 'admin::settings@index'); // URL: /admin/settings
```

<a id="404-handler"></a>

## 404 Handler

When no route matches, Rakit fires the `404` hook. Its handler lives in
`application/hooks.php`:

```php
Hook::listen('404', function () {
    return Response::error(404);
});
```

Return any response you like from it, for example a custom view or a redirect.

<a id="listing--testing-routes"></a>
<a id="route-listing"></a>
<a id="route-testing-via-cli"></a>

## Listing & Testing Routes

List every registered route (method, URI, action, name):

```bash
php rakit route:list
```

Run a route from the command line and dump its response. Pass the method and
URI as two arguments. Route middleware is not run unless you pass
`--with-middleware`, which runs the before/after middleware like a real request
and prints the response status before the content:

```bash
php rakit route:call GET /users
php rakit route:call POST /users
php rakit route:call GET /admin --with-middleware
```

<a id="helpers"></a>
<a id="route-helpers"></a>

## Helpers

<a id="routeforward"></a>

### Route::forward()

Run another route internally (no redirect) and return its response, or a 404
error when nothing matches:

```php
Route::get('old-page', function () {
    return Route::forward('GET', 'new-page');
});
```

<a id="routehas-and-routelists"></a>

### Route::has() and Route::lists()

```php
Route::has('profile');           // is there a route named "profile"?

$routes = Route::lists();        // ['GET' => [uri => action, ...], 'POST' => [...], ...]
$get = Router::method('GET');    // routes of one method
```

<a id="route-matched-event"></a>

### Route Matched Event

When a route claims the request, `rakit.route.matched` fires with the
`System\Routing\Route` and the request URI (without the host), before any
middleware or action runs:

```php
Hook::listen('rakit.route.matched', function ($route, $uri) {
    Log::info('Routing ' . $uri);
});
```
