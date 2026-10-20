# Curl

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Making Requests](#making-requests)
    -   [Other HTTP Methods](#other-http-methods)
-   [Response](#response)
-   [Request Body](#request-body)
    -   [JSON Request](#json-request)
    -   [Form Request](#form-request)
    -   [Multipart Request](#multipart-request)
    -   [Multipart File](#multipart-file)
    -   [Custom Body](#custom-body)
-   [Authentication](#authentication)
-   [Cookie](#cookie)
-   [Advanced Configuration](#advanced-configuration)
    -   [JSON Decode](#json-decode)
    -   [Timeout](#timeout)
    -   [Proxy](#proxy)
    -   [Default Headers](#default-headers)
    -   [Default cURL Options](#default-curl-options)
    -   [Resetting Settings](#resetting-settings)
    -   [SSL Validation](#ssl-validation)
-   [Faking Requests in Tests](#faking-requests-in-tests)
-   [Additional Functions](#additional-functions)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Curl` is a simple HTTP client built on [libcurl](https://curl.se/libcurl/). It requires the [PHP Curl](https://www.php.net/manual/en/book.curl.php) extension.

<a id="making-requests"></a>

## Making Requests

Each HTTP method has a static helper:

```php
Curl::get($url, $headers = [], $parameters = null)
Curl::head($url, $headers = [], $parameters = null)
Curl::options($url, $headers = [], $parameters = null)
Curl::connect($url, $headers = [], $parameters = null)
Curl::post($url, $headers = [], $body = null)
Curl::put($url, $headers = [], $body = null)
Curl::patch($url, $headers = [], $body = null)
Curl::delete($url, $headers = [], $body = null)
Curl::trace($url, $headers = [], $body = null)
```

`$headers` is an associative array. For `get()`, an array of `$parameters` is appended to the URL as a query string; for the other methods, the third argument is sent as the request body:

```php
$response = Curl::get('https://api.example.com/users', ['Accept' => 'application/json'], ['page' => 2]);

$response = Curl::post('https://api.example.com/users', [], ['name' => 'budi', 'age' => 28]);
```

A cURL error (DNS failure, timeout, refused connection ...) throws an exception. HTTP error codes such as `404` or `500` do not; check `$response->code`.

<a id="other-http-methods"></a>

### Other HTTP Methods

Any other [standard method](https://www.iana.org/assignments/http-methods), or a custom one, goes through `send()`. Note the argument order: body before headers:

```php
Curl::send(Curl::LINK, $url, $body, $headers);
Curl::send('CHECKOUT', $url, $body, $headers);
```

Constants are available for the common and WebDAV methods: `Curl::GET`, `POST`, `PUT`, `PATCH`, `DELETE`, `HEAD`, `OPTIONS`, `CONNECT`, `TRACE`, `LINK`, `UNLINK`, `MERGE`, `PROPFIND`, `MKCOL`, `COPY`, `MOVE`, `LOCK`, `UNLOCK`, `SEARCH`, and more.

<a id="response"></a>

## Response

Every request returns a `stdClass`:

```php
$response->code;     // HTTP status code, e.g. 200
$response->headers;  // response headers (array)
$response->body;     // body, JSON-decoded when possible
$response->raw_body; // raw body string
```

<a id="request-body"></a>

## Request Body

<a id="json-request"></a>

### JSON Request

`body_json()` encodes data with `json_encode()` (an optional second argument takes its flags). The `Content-Type` header is **not** set for you:

```php
$headers = ['Content-Type' => 'application/json'];
$response = Curl::post($url, $headers, Curl::body_json(['name' => 'budi']));
```

<a id="form-request"></a>

### Form Request

`body_form()` builds a query string, which cURL sends as `application/x-www-form-urlencoded`:

```php
$response = Curl::post($url, [], Curl::body_form(['name' => 'budi', 'age' => 28]));
```

<a id="multipart-request"></a>

### Multipart Request

A plain array body is sent as `multipart/form-data` (cURL adds the boundary). `body_multipart()` turns objects or scalars into such an array:

```php
$response = Curl::post($url, [], Curl::body_multipart(['name' => 'budi', 'age' => 28]));
```

<a id="multipart-file"></a>

### Multipart File

Pass files as the second argument of `body_multipart()`:

```php
$body = Curl::body_multipart(['name' => 'budi'], [
    'bio' => '/path/to/bio.json',
    'avatar' => '/path/to/avatar.jpg',
]);

$response = Curl::post($url, [], $body);
```

Or build the array yourself with `body_file($path, $alias)`, which also lets you set the uploaded file name. It throws if the file does not exist:

```php
$response = Curl::post($url, [], [
    'name' => 'budi',
    'avatar' => Curl::body_file('/path/to/avatar.jpg', 'budi.jpg'),
]);
```

<a id="custom-body"></a>

### Custom Body

A string body is sent as-is, with whatever `Content-Type` you set:

```php
$headers = ['Content-Type' => 'application/x-php-serialized'];
$response = Curl::post($url, $headers, serialize(['foo' => 'bar']));
```

<a id="authentication"></a>

## Authentication

`auth()` sets credentials for every following request. The method defaults to `CURLAUTH_BASIC`:

```php
Curl::auth('username', 'password');
Curl::auth('username', 'password', CURLAUTH_DIGEST);
```

> **Security:** credentials stay set until cleared, so they are also sent to any other host you call later. Call `Curl::clear_auth()` or `Curl::reset()` before talking to somewhere else.

Supported methods: `CURLAUTH_BASIC`, `CURLAUTH_DIGEST`, `CURLAUTH_DIGEST_IE`, `CURLAUTH_NEGOTIATE`, `CURLAUTH_NTLM`, `CURLAUTH_NTLM_WB`, `CURLAUTH_ANY`, `CURLAUTH_ANYSAFE` and `CURLAUTH_ONLY` (see [CURLOPT_HTTPAUTH](https://curl.se/libcurl/c/CURLOPT_HTTPAUTH.html)). Combining several as a bitmask makes cURL ask the server which ones it supports first, which costs an extra round-trip.

<a id="cookie"></a>

## Cookie

Send a cookie header string, or use a cookie file:

```php
Curl::cookie('session=foo; logged=true');

Curl::cookie_file(path('storage').'cookies.txt');
```

The file is used as both cookie file and cookie jar ([CURLOPT_COOKIEFILE](https://curl.se/libcurl/c/CURLOPT_COOKIEFILE.html) / [CURLOPT_COOKIEJAR](https://curl.se/libcurl/c/CURLOPT_COOKIEJAR.html)), so it must be in Netscape or HTTP-header format, and cookies received from the server are written back into it.

<a id="advanced-configuration"></a>

## Advanced Configuration

All settings below are static and apply to every following request.

<a id="json-decode"></a>

### JSON Decode

`json_options()` sets the `json_decode()` arguments used for the response body:

```php
Curl::json_options(true, 512, JSON_BIGINT_AS_STRING); // $associative, $depth, $flags
```

<a id="timeout"></a>

### Timeout

```php
Curl::timeout(5); // seconds; 0 or less means no limit. Unset, it is 30 seconds
```

<a id="proxy"></a>
<a id="proxy-authentication"></a>

### Proxy

`proxy($address, $port = 1080, $type = CURLPROXY_HTTP, $tunnel = false)` sets a proxy. `$type` can be any [proxy type](https://curl.se/libcurl/c/CURLOPT_PROXYTYPE.html) such as `CURLPROXY_HTTP`, `CURLPROXY_SOCKS5` or `CURLPROXY_SOCKS5_HOSTNAME`. `proxy_auth()` works like [`auth()`](#authentication):

```php
Curl::proxy('10.10.10.1', 8080, CURLPROXY_HTTP, true);
Curl::proxy_auth('username', 'password', CURLAUTH_DIGEST);
```

<a id="default-headers"></a>

### Default Headers

```php
Curl::default_header('X-Api-Key', 'secret');
Curl::default_headers(['X-Api-Key' => 'secret', 'Accept' => 'application/json']);
Curl::clear_default_headers();
```

<a id="default-curl-options"></a>

### Default cURL Options

Raw [cURL options](https://www.php.net/curl_setopt) override the component's own defaults:

```php
Curl::curl_option(CURLOPT_MAXREDIRS, 3);
Curl::curl_options([CURLOPT_MAXREDIRS => 3, CURLOPT_CONNECTTIMEOUT => 5]);
Curl::clear_curl_options();
```

<a id="resetting-settings"></a>

### Resetting Settings

```php
Curl::clear_auth();
Curl::clear_cookie();  // both the cookie string and the cookie file
Curl::clear_proxy();

Curl::reset(); // all of the above, plus default headers, cURL options and timeout
```

`reset()` does not touch the JSON decode or SSL settings.

<a id="ssl-validation"></a>

### SSL Validation

Peer and host validation are on by default. Only disable them for local testing:

```php
Curl::verify_peer(false);
Curl::verify_host(false);
```

> **Security:** requests and redirects (up to 10 are followed) are limited to `http` and `https`, so a server cannot redirect you to something like `file:///etc/passwd`. Use this component for HTTP only.

<a id="faking-requests-in-tests"></a>

## Faking Requests in Tests

`Curl::fake()` answers requests with fake responses instead of sending them, so tests run
without the network and without hitting real APIs (payment gateways, mail services). The keys
are URL patterns (`*` is a wildcard, the scheme may be left out); the values are a response, a
body (an array is sent back as JSON) or a closure that receives the request:

```php
Curl::fake([
    'api.github.com/users/*' => Curl::response(['login' => 'esyede'], 200, ['X-Rate' => '59']),
    'example.com/status' => 'ok',
    'payment.test/*' => function ($request) {
        return ('POST' === $request['method']) ? Curl::response(['paid' => true]) : Curl::response('', 405);
    },
]);

$user = Curl::get('https://api.github.com/users/esyede'); // $user->code 200, $user->body->login
```

`Curl::fake()` without patterns answers every request with an empty `200`. A request that no
pattern matches throws, so a test never reaches the network by mistake; add a `'*'` pattern to
answer the rest. A closure can count its calls to answer a retry differently.

Every request is recorded as an array of `method`, `url` (with the GET query), `headers`
(lower-cased names, default headers included) and `body`, for the assertions:

```php
Curl::assert_sent(function ($request) {
    return 'POST' === $request['method']
        && 'https://payment.test/charge' === $request['url']
        && 'Bearer secret' === $request['headers']['authorization'];
});

Curl::assert_not_sent(function ($request) {
    return Str::contains($request['url'], 'refund');
});

Curl::assert_sent_count(2);
Curl::assert_nothing_sent();
$posts = Curl::recorded(function ($request) { return 'POST' === $request['method']; });
```

The assertions fail the running PHPUnit test (or throw outside PHPUnit). Call `Curl::fake(false)`
in `tearDown()` to send for real again.

<a id="additional-functions"></a>

## Additional Functions

| Method                       | Description                                                                                                    |
| ---------------------------- | -------------------------------------------------------------------------------------------------------------- |
| `info()`                     | `curl_getinfo()` of the last transfer (`http_code`, `total_time`, `size_download` ...)                         |
| `handler()`                  | The underlying cURL handle, for use with PHP's `curl_*` functions                                              |
| `format_headers($headers)`   | Merge with default headers and format as `name: value` lines; adds a `user-agent` and an empty `expect:` if missing |
| `fake_user_agent()`          | The browser-like User-Agent string used when none is given                                                     |
| `build_curl_query($data)`    | Flatten nested arrays/objects into `key[sub]` form fields                                                      |

> On PHP below 8.0 the handle is closed right after each request, so `info()` and `handler()` are only usable on PHP 8.0 or newer.
