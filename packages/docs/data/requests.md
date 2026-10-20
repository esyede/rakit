# Request

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Request URI](#request-uri)
  - [URI Segments](#uri-segments)
  - [URI Pattern Matching](#uri-pattern-matching)
- [Request Method](#request-method)
- [Request Headers](#request-headers)
- [Request IP Address](#request-ip-address)
- [Query String](#query-string)
- [Request Type Detection](#request-type-detection)
  - [JSON Request](#json-request)
- [Content Negotiation](#content-negotiation)
  - [Content Type Preferences](#content-type-preferences)
- [Authorization](#authorization)
- [CSRF Protection](#csrf-protection)
  - [Checking Forged Requests](#checking-forged-requests)
  - [CSRF Exceptions](#csrf-exceptions)
- [Request Route](#request-route)
- [Server Variables](#server-variables)
- [Referrer](#referrer)
- [Other Methods](#other-methods)
- [Practical Example](#practical-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Request` class gives you the current HTTP request: its URI, method,
headers, IP, server values, and what kind of request it is (AJAX, JSON, HTTPS,
CLI). For form fields, JSON bodies and uploaded files, see [Input](/docs/input).

<a id="accessing-request"></a>
<a id="uri-information"></a>
<a id="current-uri"></a>
<a id="full-uri"></a>
<a id="request-uri"></a>
## Request URI

`Request::uri()` (same as `URI::current()`) returns the path without leading or
trailing slashes, or `/` for the home page. `URI::full()` returns the full URL
including the query string:

```php
$uri = Request::uri(); // 'user/profile'
$url = URI::full();    // 'http://example.com/user/profile?page=2'
```

<a id="uri-segments"></a>
### URI Segments

`URI::segment()` counts from `1` and accepts a default:

```php
// URI: /user/profile/edit
URI::segment(1);            // 'user'
URI::segment(4, 'default'); // 'default'
```

All segments live in the zero-based `URI::$segments` property, filled in once
`URI::current()` (or `URI::segment()`) has been called:

```php
URI::current();
$segments = URI::$segments; // ['user', 'profile', 'edit']
```

<a id="request-path"></a>
<a id="uri-pattern-matching"></a>
### URI Pattern Matching

`URI::is()` checks the current URI against a pattern. `*` is a wildcard, and an
array matches if any pattern does:

```php
URI::is('admin/dashboard');        // exact match
URI::is('user/*');                 // anything under user/
URI::is(['admin/*', 'dashboard/*']);

$active = URI::is('products/*') ? 'active' : '';
```

<a id="request-method"></a>
## Request Method

`method()` returns the uppercase HTTP method, honoring method spoofing via the
`_method` field of a `POST` body (never the query string). `real_method()` returns
the method the server actually received, use it for security decisions:

```php
$method = Request::method(); // 'POST', 'PUT', ...

if (Request::is_method('post')) {
    // case-insensitive
}

$real = Request::real_method();
```

<a id="retrieve-headers"></a>
<a id="check-header-exists"></a>
<a id="common-headers"></a>
<a id="user-agent"></a>
<a id="request-headers"></a>
## Request Headers

Header names are case-insensitive. `header()` accepts a default value:

```php
$type  = Request::header('Content-Type');
$token = Request::header('X-API-Token', 'default-token');
$agent = Request::agent(); // shortcut for the User-Agent header

if (Request::foundation()->headers->has('X-API-Key')) {
    // header present
}
```

`headers()` returns every header as a `name => value` array. As in Laravel/Symfony,
names are lowercase with `_` turned into `-` (e.g. `user-agent`, `x-requested-with`).
Response headers keep the casing you set them with when sent (`Content-Type`):

```php
foreach (Request::headers() as $name => $value) {
    echo $name . ': ' . $value . "\n";
}
```

<a id="request-ip-address"></a>
## Request IP Address

`ip()` returns the client IP. If it cannot be determined or is not a valid IP,
the default (`'0.0.0.0'`) is returned:

```php
$ip = Request::ip();
$ip = Request::ip('unknown');

if (in_array(Request::ip(), ['192.168.1.100', '10.0.0.1'])) {
    // whitelisted
}
```

<a id="query-string"></a>
## Query String

Query parameters are usually read through [Input](/docs/input). The raw query
bag is also reachable from the underlying request:

```php
$page  = Input::get('page');
$query = Request::foundation()->query->all();
```

<a id="ajax-request"></a>
<a id="https-request"></a>
<a id="cli-request"></a>
<a id="request-type-detection"></a>
## Request Type Detection

```php
Request::ajax();     // X-Requested-With: XMLHttpRequest
Request::pjax();     // X-Pjax header present
Request::secure();   // over HTTPS
Request::cli();      // running from the console
Request::prefetch(); // browser is only prefetching the page
```

A common use is returning JSON to AJAX calls and a view otherwise:

```php
if (Request::ajax()) {
    return Response::json($users);
}

return View::make('users.index', compact('users'));
```

<a id="content-type"></a>
<a id="json-request"></a>
### JSON Request

| Method           | Returns `true` when                                                           |
| ---------------- | ----------------------------------------------------------------------------- |
| `is_json()`      | The `Content-Type` contains `/json` or `+json` (the body is JSON)             |
| `wants_json()`   | The most preferred `Accept` type contains `/json` or `+json`                  |
| `expects_json()` | The request is AJAX but not PJAX, or `wants_json()` is `true`                 |

```php
if (Request::is_json()) {
    $data = Input::json();
}

if (Request::expects_json()) {
    return Response::json($users);
}
```

<a id="accept-header"></a>
<a id="accept-specific-types"></a>
<a id="content-negotiation"></a>
## Content Negotiation

`accept()` lists the types from the `Accept` header, most preferred first.
`accepts()` takes one type, several arguments, or an array, and returns `true`
if any of them is acceptable (wildcards like `text/*` and `*/*` count, and an
empty `Accept` header accepts everything):

```php
$types = Request::accept(); // ['text/html', 'application/json', '*/*']

if (Request::accepts('application/json')) {
    return Response::json($data);
}

Request::accepts(['text/html', 'application/xhtml+xml']);
Request::accept_html(); // same as accepts('text/html')
Request::accept_any();  // Accept header is empty or starts with */*
```

<a id="content-type-preferences"></a>
### Content Type Preferences

`prefers()` walks the `Accept` header in order of preference and returns the
first of your types that matches, or `null`. If the client sends `*/*`, your
first type is returned:

```php
$format = Request::prefers(['application/json', 'text/html']);

if ($format === 'application/json') {
    return Response::json($data);
}

return View::make('page');
```

`matches_type($actual, $type)` is the helper behind this. It returns `true` when
the two types are equal, or when `$type` is a structured-syntax variant of
`$actual` (it does not expand `*` wildcards):

```php
Request::matches_type('application/json', 'application/json');         // true
Request::matches_type('application/json', 'application/vnd.api+json'); // true
Request::matches_type('text/html', 'text/*');                          // false
```

<a id="authorization-header"></a>
<a id="bearer-token"></a>
<a id="authorization"></a>
## Authorization

`authorization()` returns the raw `Authorization` header. `bearer()` returns
the token from a `Bearer <token>` header, or `null` when the header is missing,
uses another scheme, or the token contains invalid characters:

```php
$auth  = Request::authorization(); // 'Bearer eyJhbGciOi...'
$token = Request::bearer();        // 'eyJhbGciOi...'

if (! $token) {
    return Response::json(['error' => 'Token required'], 401);
}
```

<a id="csrf-protection"></a>
## CSRF Protection

The `csrf` middleware protects every non-safe request (anything but `GET`,
`HEAD`, `OPTIONS`, `TRACE` and `CONNECT`) against cross-site request forgery.
Render the session token into forms with `@csrf` or `csrf_field()`:

```blade
<form method="POST" action="/posts">
    @csrf
    ...
</form>
```

For AJAX calls, send the token in the `X-CSRF-Token` or `X-XSRF-Token` header,
or as `csrf_token` in a JSON body. Tokens are compared in constant time.

<a id="checking-forged-requests"></a>
### Checking Forged Requests

`Request::forged()` returns `true` when the token is missing or wrong, and
`false` for safe methods. This is exactly what the `csrf` middleware checks:

```php
if (Request::forged()) {
    return Response::error(422);
}
```

> The check uses `real_method()`, so a `POST` spoofed as `GET` via `_method`
> or `X-Http-Method-Override` is still checked.

<a id="csrf-exceptions"></a>
### CSRF Exceptions

Webhooks, callbacks and server-to-server endpoints that cannot carry a session
token can be exempted in `application/config/application.php`. Patterns support
`*` wildcards:

```php
'csrf_except' => [
    'api/webhook',
    'api/*',
],
```

<a id="request-route"></a>
## Request Route

`route()` returns the matched `Route` object (or `null` before routing):

```php
$route = Request::route();

$route->is('user.profile'); // checks the route name ('as')
$route->parameters;         // route parameters
$route->action;             // route action array
```

<a id="server-variables"></a>
## Server Variables

`server()` reads a `$_SERVER` value (the key is uppercased for you) with an
optional default. `servers()` returns them all:

```php
$host    = Request::server('HTTP_HOST');
$referer = Request::server('HTTP_REFERER', '/');
$all     = Request::servers();
```

<a id="referrer"></a>
## Referrer

`referrer()` returns the `Referer` header, or `null`. To simply go back, use
`Redirect::back()`:

```php
$from = Request::referrer();
```

<a id="other-methods"></a>
## Other Methods

| Method               | Description                                                       |
| -------------------- | ----------------------------------------------------------------- |
| `content($resource)` | Raw request body; pass `true` to get it as a stream resource      |
| `spoofed()`          | `true` when the method was spoofed through the `_method` field    |
| `languages()`        | Languages from the `Accept-Language` header, most preferred first |
| `subdomain()`        | The subdomain, or `null` when the host has no subdomain           |
| `time()`             | UNIX timestamp of when the request started                        |
| `env()`              | Current environment name                                          |
| `set_env($env)`      | Override the current environment name                             |
| `is_env($env)`       | `true` when the current environment matches `$env`                |
| `foundation()`       | The underlying `System\Foundation\Http\Request` instance          |

Any other static call is forwarded to the foundation request, e.g.
`Request::getHost()`.

<a id="practical-examples"></a>
<a id="practical-example"></a>
## Practical Example

An endpoint that serves JSON, XML or HTML depending on the client:

```php
Route::get('api/users', function () {
    $users = User::all();
    $format = Request::prefers(['application/json', 'application/xml', 'text/html']);

    if (Request::expects_json() || $format === 'application/json') {
        return Response::json($users);
    }

    if ($format === 'application/xml') {
        return Response::make(View::make('users.xml', compact('users')), 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    return View::make('users.index', compact('users'));
});
```
