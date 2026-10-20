# Creating URLs

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [URL to Route](#url-to-route)
-   [URL to Controller Method](#url-to-controller-method)
-   [URL to Language Switch](#url-to-language-switch)
-   [URL to Asset](#url-to-asset)
-   [Signed URLs](#signed-urls)
-   [Other Helpers](#other-helpers)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `URL` class builds absolute links to paths, routes, controller actions and
assets. Generated URLs use HTTPS when the request is secure or `application.url`
starts with `https://`.

```php
URL::base();               // http://example.com
URL::home();               // the "home" named route, or the base URL
URL::to('user/profile');   // http://example.com/user/profile
URL::current();            // current URL, without the query string
URL::full();               // current URL, with the query string
URL::valid('https://example.com'); // true (uses filter_var())
```

`URL::to()` includes the `application.index` value and, when
`application.languages` is set, the current language segment. A full
`http(s)://` URL is returned unchanged.

> **Security:** `URL::to()` turns protocol-relative URLs (`//evil.com`) and other
> schemes (`javascript:`, `data:`) into `/`. `Redirect::to()` blocks external
> hosts; use `Redirect::away()` for a deliberate external redirect.

<a id="url-to-route"></a>

## URL to Route

`URL::to_route()` builds a URL from a [named route](/docs/routing#named-routes),
filling its wildcards in order. It throws an exception if the route does not
exist:

```php
$url = URL::to_route('profile');
$url = URL::to_route('profile', [$username]);
```

<a id="url-to-controller-method"></a>

## URL to Controller Method

`URL::to_action()` uses the URI of the route pointing at that action; without
one it falls back to the `controller/action/params` convention:

```php
$url = URL::to_action('user@profile');
$url = URL::to_action('user@profile', [$username]);
```

<a id="url-to-language-switch"></a>

## URL to Language Switch

`URL::to_language()` returns the current page in another language, or the home
page when the second argument is `true`. The language must be listed in
`application.languages`:

```php
$url = URL::to_language('fr');
$url = URL::to_language('fr', true);
```

<a id="url-to-asset"></a>

## URL to Asset

`URL::to_asset()` points into the `assets/` folder, without the
`application.index` value or language segment. Full URLs (including `//cdn...`)
are returned unchanged:

```php
$url = URL::to_asset('js/jquery.js'); // http://example.com/assets/js/jquery.js
```

<a id="signed-urls"></a>

## Signed URLs

A signed URL carries an HMAC of itself, so a link you hand out (an email confirmation, an
unsubscribe link, a download) can be trusted without a login: changing any part of it breaks the
signature. Pass minutes to make it expire:

```php
$url = URL::signed('unsubscribe?user=7');                       // never expires
$url = URL::signed('download?file=report.pdf', 30);              // valid 30 minutes
$url = URL::to_signed_route('unsubscribe', [$user->id], 60 * 24); // a named route
```

Protect the route with the `signed` middleware, which answers 403 otherwise, or check by hand:

```php
Route::get('unsubscribe', ['before' => 'signed', 'uses' => 'newsletter@unsubscribe']);

if (! URL::has_valid_signature()) {
    return Response::error(403);
}
```

The scheme and host are signed too. Behind a proxy that changes them before PHP sees the
request, sign and check relative URLs only, which covers the path and query:

```php
$url = URL::signed('download?file=report.pdf', 30, false);

Route::get('download', ['before' => 'signed:relative', 'uses' => 'files@download']);
```

The signing key is derived from `key.php`, so generating a new key invalidates every signed URL.

<a id="other-helpers"></a>

## Other Helpers

Global helper functions are shortcuts for the methods above:

| Helper                          | Same as                          |
|---------------------------------|----------------------------------|
| `url('user/profile')`           | `URL::to()`                      |
| `asset('js/jquery.js')`         | `URL::to_asset()`                |
| `route('profile', [$username])` | `URL::to_route()`                |
| `action('user@profile', [$id])` | `URL::to_action()`               |
