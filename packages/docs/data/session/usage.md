# Using Session

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Storing Items](#storing-items)
-   [Retrieving Items](#retrieving-items)
-   [Deleting Items](#deleting-items)
-   [Flash Items](#flash-items)
-   [Regeneration](#regeneration)
-   [CSRF Token](#csrf-token)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`Session` stores data between requests through one API, whatever the
[driver](/docs/session/config). It starts automatically on every web request, except for
paths listed in the `stateless` option. Calling it when no session was started throws an
exception, so check first in code that may run on a stateless route:

```php
if (Session::started()) {
    // ...
}
```

<a id="storing-items"></a>

## Storing Items

```php
Session::put('name', 'Budi');
Session::put('cart.items', [1, 2]);   // dot notation writes nested arrays
```

<a id="retrieving-items"></a>

## Retrieving Items

`get()` returns the item (flash items included), or the default, which may be a closure, when it
is missing:

```php
$name = Session::get('name');
$name = Session::get('name', 'Andi');
$name = Session::get('name', function () {
    return 'Andi';
});

Session::has('name');      // TRUE when the item is not null
Session::all();            // every item, raw
Session::activity();       // UNIX time of the last activity
```

<a id="deleting-items"></a>

## Deleting Items

```php
Session::forget('name');
Session::forget(['name', 'age']);   // or forget('name', 'age')
Session::flush();                   // delete everything except the CSRF token
```

<a id="flash-items"></a>

## Flash Items

Flash items are only available during the next request, which suits status messages:

```php
Session::flash('status', 'Welcome back!');
```

Usually you flash through a redirect: `Redirect::to('users')->with('status', 'Saved!')`. To keep
flash items for one more request:

```php
Session::reflash();                     // all of them
Session::keep('status');
Session::keep(['status', 'other']);
```

<a id="regeneration"></a>

## Regeneration

`regenerate()` gives the session a new random ID while keeping its data and CSRF token. Do it
after login to prevent session fixation:

```php
if (Auth::attempt(Input::only('email', 'password'))) {
    Session::regenerate();

    return Redirect::to('dashboard');
}
```

`invalidate()` also gets a new ID, but discards all data and issues a new CSRF token, which
suits logout.

<a id="csrf-token"></a>

## CSRF Token

The session holds a token that protects forms against cross-site request forgery. It is
created when the session starts (like Laravel), so every session has one. Put it in forms
with `@csrf` in Blade, or the `csrf_field()` helper:

```blade
<form method="POST" action="/users">
    @csrf
    ...
</form>
```

`csrf_token()` and `Session::token()` return the value. Stateless routes have no session,
so no token. The field is named `csrf_token` (`csrf_name()`).

Protect routes with the `csrf` middleware from `application/middlewares.php`. On any method
except GET, HEAD, OPTIONS, TRACE and CONNECT it compares the `csrf_token` field (form or JSON
body), or the `X-CSRF-TOKEN` / `X-XSRF-TOKEN` header, with the session token and responds
`422` on mismatch:

```php
Route::post('users', ['before' => 'csrf', function () {
    // ...
}]);
```

Exclude URIs (e.g. webhooks) in the `csrf_except` option of `application/config/application.php`:

```php
'csrf_except' => [
    'webhook/stripe',
    'api/*',
],
```
