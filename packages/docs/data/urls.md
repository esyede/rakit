# Creating URLs

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [URL to Route](#url-to-route)
-   [URL to Controller Method](#url-to-controller-method)
-   [URL to Language Switch](#url-to-language-switch)
-   [URL to Asset](#url-to-asset)
-   [Other Helpers](#other-helpers)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`URL` builds links to routes, controller actions and assets.

#### Getting the application's base URL:

```php
$url = URL::base();
```

#### Getting the application's home page URL:

```php
$url = URL::home();
```

It uses the named route `'home'` when there is one, and the base URL otherwise.

#### Creating a URL from the base URL:

```php
$url = URL::to('user/profile');
```

> **Security:** `URL::to()` blocks protocol-relative URLs (`//evil.com`) and dangerous schemes (`javascript:`, `data:`). `Redirect::to()` blocks external hosts; use `Redirect::away()` for a deliberate external redirect.

#### Getting the current URL:

```php
$url = URL::current();
```

#### Getting the current URL along with query string:

```php
$url = URL::full();
```

#### Validating a URL:

```php
// Check if the string is a valid URL
if (URL::valid('https://example.com')) {
    // URL is valid
}

if (URL::valid('not-a-url')) {
    // URL is not valid
}
```

The `valid()` method uses PHP's `filter_var()` to validate the URL format.

<a id="url-to-route"></a>

## URL to Route

#### Creating a URL to a named route:

```php
$url = URL::to_route('profile');
```

Pass the values that replace the route's URI placeholders:

#### Creating a URL to a named route with wildcard values:

```php
$url = URL::to_route('profile', [$username]);
```

_Further reading:_

-   [Named Routes](/docs/routing#named-routes)

<a id="url-to-controller-method"></a>

## URL to Controller Method

#### Creating a URL to a controller method:

```php
$url = URL::to_action('user@profile');
```

#### Creating a URL to a controller method with wildcard values:

```php
$url = URL::to_action('user@profile', [$username]);
```

<a id="url-to-language-switch"></a>

## URL to Language Switch

#### Creating a URL to the same page in another language:

```php
$url = URL::to_language('fr');
```

#### Creating a URL to the home page in another language:

```php
$url = URL::to_language('fr', true);
```

<a id="url-to-asset"></a>

## URL to Asset

URLs created for assets will not contain the value from the `application.index` configuration.

#### Creating a URL to an asset:

```php
$url = URL::to_asset('js/jquery.js');
```

<a id="other-helpers"></a>

## Other Helpers

Global helpers cover the same ground:

#### Creating a URL from the base URL:

```php
$url = url('user/profile');
```

The `url()` function is an alias for `URL::to()`.

#### Creating a URL to an asset:

```php
$url = asset('js/jquery.js');
```

The `asset()` function is an alias for `URL::to_asset()`.

#### Creating a URL to a named route:

```php
$url = route('profile');
```

The `route()` function is an alias for `URL::to_route()`.

#### Creating a URL to a named route with wildcard values:

```php
$url = route('profile', [$username]);
```

#### Creating a URL to a controller method:

```php
$url = action('user@profile');
```

The `action()` function is an alias for `URL::to_action()`.

#### Creating a URL to a controller method with wildcard values:

```php
$url = action('user@profile', [$username]);
```

> **Note:** All created URLs will automatically follow the protocol being used
> (HTTP or HTTPS) based on the `application.url` configuration or the current request.
