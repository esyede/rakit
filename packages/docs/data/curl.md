# Curl

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Making Requests](#making-requests)
    -   [JSON Request](#json-request)
    -   [Form Request](#form-request)
    -   [Multipart Request](#multipart-request)
    -   [Multipart File](#multipart-file)
    -   [Custom Body](#custom-body)
-   [Authentication](#authentication)
-   [Cookie](#cookie)
-   [Response](#response)
-   [Advanced Configuration](#advanced-configuration)
    -   [JSON Decode](#json-decode)
    -   [Timeout](#timeout)
    -   [Proxy](#proxy)
    -   [Proxy Authentication](#proxy-authentication)
    -   [Default Headers](#default-headers)
    -   [Default cURL Options](#default-curl-options)
    -   [SSL Validation](#ssl-validation)
-   [Additional Functions](#additional-functions)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Curl` is an HTTP client built on [libcurl](https://curl.se/libcurl/), for talking to
a URL and transferring data over it.

> Don't forget to install the [PHP Curl](https://www.php.net/manual/en/book.curl.php)
> extension on your server if it's not already there.

<a id="making-requests"></a>

## Making Requests

The request types:

```php
Curl::get($url, $headers = [], $parameters = null)
Curl::post($url, $headers = [], $body = null)
Curl::put($url, $headers = [], $body = null)
Curl::patch($url, $headers = [], $body = null)
Curl::delete($url, $headers = [], $body = null)
Curl::head($url, $headers = [], $parameters = null)
Curl::options($url, $headers = [], $parameters = null)
Curl::connect($url, $headers = [], $parameters = null)
Curl::trace($url, $headers = [], $body = null)
```

Where:

-   `$url` - is the destination endpoint for sending the request.
-   `$headers` - is the request header in array format
-   `$body` - is the request body in array format (for POST, PUT, PATCH, DELETE, TRACE)
-   `$parameters` - is the query parameters in array format (for GET, HEAD, OPTIONS, CONNECT)

Any other [standard method](https://www.iana.org/assignments/http-methods), or a
custom one, goes through `request()`:

```php
// Using available constant methods
Curl::send(Curl::LINK, $url, $body, $headers);
Curl::send(Curl::UNLINK, $url, $body, $headers);

// Using custom method string
Curl::send('CHECKOUT', $url, $body, $headers);
```

Available standard HTTP methods as constants:
- `Curl::GET`, `Curl::POST`, `Curl::PUT`, `Curl::PATCH`, `Curl::DELETE`
- `Curl::HEAD`, `Curl::OPTIONS`, `Curl::CONNECT`, `Curl::TRACE`
- `Curl::LINK`, `Curl::UNLINK`, `Curl::MERGE`
- And many more (see [IANA HTTP Methods](https://www.iana.org/assignments/http-methods))

A simple request:

```php
$headers = ['Accept' => 'application/json'];
$query = ['foo' => 'hello', 'bar' => 'world'];

$response = Curl::post('https://mockbin.com/request', $headers, $query);

$response->code;        // contains http status code
$response->headers;     // contains response headers (array)
$response->body;        // contains response body (JSON-decoded if possible)
$response->raw_body;    // contains raw body string
```

<a id="json-request"></a>

### JSON Request

`body_json()` sends a JSON body:

```php
$headers = ['Accept' => 'application/json', 'Content-Type' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_json($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

The body goes through [json_encode](https://www.php.net/json_encode). The
`'Content-Type'` header is **not** set for you, so pass `'application/json'` yourself,
as above.

<a id="form-request"></a>

### Form Request

`body_form()` sends a form body:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_form($data);
$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

The body goes through [http_build_query](https://www.php.net/http_build_query), which
cURL sends as `'application/x-www-form-urlencoded'`.

<a id="multipart-request"></a>

### Multipart Request

`body_multipart()` sends a multipart body:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_multipart($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

The body stays an array, so cURL sends it as `'multipart/form-data'` and adds the
`--boundary` itself.

<a id="multipart-file"></a>

### Multipart File

A file upload goes through `body_multipart()` too:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];
$files = ['bio' => '/path/to/bio.json', 'avatar' => '/path/to/avatar.jpg'];

$body = Curl::body_multipart($data, $files);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

`body_file()` gives more control over the uploaded file:

```php
$headers = ['Accept' => 'application/json'];
$body = [
    'name' => 'budi',
    'age' => 28,
    'bio' => Curl::body_file('/path/to/bio.json'),
    'avatar' => Curl::body_file('/path/to/avatar.jpg', 'budi.jpg'),
];

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

> No `body_multipart()` here: it is unnecessary once files are added by hand.

<a id="custom-body"></a>

### Custom Body

A body can also be sent as it is, with a `Content-Type` of your own — here through
[serialize](https://www.php.net/serialize):

```php
$headers = ['Accept' => 'application/json', 'Content-Type' => 'application/x-php-serialized'];
$body = serialize(['foo' => 'hello', 'bar' => 'world']);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

<a id="authentication"></a>

## Authentication

Authentication defaults to Basic, so a username and password are enough:

```php
// Basic auth (default)
Curl::auth('username', 'password');

// Custom auth
Curl::auth('username', 'password', CURLAUTH_DIGEST);
```

> Credentials stay set until you clear them, so every later request carries
> them — including one to a different host. Call `Curl::clear_auth()`, or
> `Curl::reset()`, before talking to somewhere else.

The third parameter picks the method:

| Method               | Description                                                                        |
| -------------------- | ---------------------------------------------------------------------------------- |
| `CURLAUTH_BASIC`     | HTTP Basic auth (default)                                                          |
| `CURLAUTH_DIGEST`    | HTTP Digest auth ([RFC 2617](https://www.rfc-editor.org/rfc/rfc2617.txt))                |
| `CURLAUTH_DIGEST_IE` | HTTP Digest auth IE (Internet Explorer)                                            |
| `CURLAUTH_NEGOTIATE` | HTTP Negotiate (SPNEGO) auth ([RFC 4559](https://www.rfc-editor.org/rfc/rfc4559.txt))    |
| `CURLAUTH_NTLM`      | HTTP NTLM auth (Microsoft)                                                         |
| `CURLAUTH_NTLM_WB`   | NTLM WinBind ([documentation](https://curl.se/libcurl/c/CURLOPT_HTTPAUTH.html)) |
| `CURLAUTH_ANY`       | See: [documentation](https://curl.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ANYSAFE`   | See: [documentation](https://curl.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ONLY`      | See: [documentation](https://curl.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |

> Passing more than one method, as a bitmask, makes cURL ask the server which ones it
> supports before choosing. That is an extra round-trip, and one more chance to time out.

<a id="cookie"></a>

## Cookie

Cookie headers are written out, separated by a semicolon and a space:

```php
$cookie = 'session=foo; logged=true';

Curl::cookie($cookie);
```

Or read from a file:

```php
$path = path('storage').'cookies.txt';

Curl::cookie_file($path);
```

The file is used as both cURL's cookie file and cookie jar ([CURLOPT_COOKIEFILE](https://curl.se/libcurl/c/CURLOPT_COOKIEFILE.html) and [CURLOPT_COOKIEJAR](https://curl.se/libcurl/c/CURLOPT_COOKIEJAR.html)), so it must use the Netscape cookie file format (or plain HTTP header style), and cookies received from the server will be written back into it.

<a id="response"></a>

## Response

Every request answers with an `\stdClass` carrying:

-   `code` - which will contain the http status code (e.g. `200`)
-   `headers` - which will contain the http response headers (as an array)
-   `body` - which will contain the response body formatted into an object or array (if possible).
-   `raw_body` - which will contain the raw response body

<a id="advanced-configuration"></a>

## Advanced Configuration

<a id="json-decode"></a>

### JSON Decode

`json_options()` changes how the response body is decoded:

```php
$associative = true; // Return as associative array
$depth = 512; // Set maximum nesting depth
$flags = JSON_BIGINT_AS_STRING; // Set decode flags (combine several with |)

Curl::json_options($associative, $depth, $flags);
```

<a id="timeout"></a>

### Timeout

How long a request may take before it times out:

```php
Curl::timeout(5); // Request times out after 5 seconds
```

<a id="proxy"></a>

### Proxy

A proxy, of type `CURLPROXY_HTTP`, `CURLPROXY_HTTP_1_0`, `CURLPROXY_SOCKS4`,
`CURLPROXY_SOCKS5`, `CURLPROXY_SOCKS4A` or `CURLPROXY_SOCKS5_HOSTNAME`:

> Complete guide on proxy types can be seen on the
> [cURL documentation page](https://curl.se/libcurl/c/CURLOPT_PROXYTYPE.html)

```php
// Set proxy with default port 1080
Curl::proxy('10.10.10.1');

// Set proxy and custom port
Curl::proxy('10.10.10.1', 8080, CURLPROXY_HTTP);

// enable tunneling
Curl::proxy('10.10.10.1', 8080, CURLPROXY_HTTP, true);
```

<a id="proxy-authentication"></a>

### Proxy Authentication

Proxy authentication is the same as [request authentication](#authentication) explained above:

```php
// Proxy authentication with basic auth
Curl::proxy_auth('username', 'password');

// Proxy authentication with digest auth
Curl::proxy_auth('username', 'password', CURLAUTH_DIGEST);
```

<a id="default-headers"></a>

### Default Headers

Default headers are sent with every request, so they need writing only once:

```php
Curl::default_header('Header1', 'Value1');
Curl::default_header('Header2', 'Value2');
```

Several at once:

```php
Curl::default_headers([
    'Header1' => 'Value1',
    'Header2' => 'Value2',
]);
```

And to clear them all:

```php
Curl::clear_default_headers();
```

<a id="default-curl-options"></a>

### Default cURL Options

Default [cURL options](https://www.php.net/curl_setopt) apply to every request too:

```php
Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
```

Several at once:

```php
Curl::curl_options([
    CURLOPT_COOKIE => 'foo=bar',
]);
```

And to clear them all:

```php
Curl::clear_curl_options();

// Credentials, cookies and proxy settings stick around too
Curl::clear_auth();
Curl::clear_cookie();
Curl::clear_proxy();

// Or drop everything at once
Curl::reset();
```

<a id="ssl-validation"></a>

### SSL Validation

SSL validation, of both peer and host, is on by default:

```php
// Enable SSL validation
Curl::verify_peer(true);
Curl::verify_host(true);

// Disable SSL validation
Curl::verify_peer(false);
Curl::verify_host(false);
```

> Requests and redirects are both limited to `http` and `https`. Redirects are
> followed by libcurl itself, so without that limit a server being fetched could
> answer with a `Location` of `file:///etc/passwd` and have the contents handed
> back to you. Use this component for HTTP only.

<a id="additional-functions"></a>

## Additional Functions

A few more, for advanced needs:

> On PHP versions below 8.0 the handler is closed right after each request,
> so `info()` and `handler()` are only usable on PHP 8.0 or newer.

#### Get information about the last transfer:

```php
// Get detailed information about the last request
$info = Curl::info();

// Example available information:
// - http_code: HTTP status code
// - total_time: Total transfer time
// - namelookup_time: DNS lookup time
// - connect_time: Connection time
// - pretransfer_time: Time before transfer
// - size_upload: Size of uploaded data
// - size_download: Size of downloaded data
// - speed_download: Download speed
// - speed_upload: Upload speed
// and others (see curl_getinfo documentation)
```

#### Get internal curl handler:

```php
// Get the cURL resource handler for advanced needs
$handler = Curl::handler();

// You can use this handler with standard PHP curl_* functions
$error = curl_errno($handler);
```

#### Format headers manually:

```php
// Format array headers into the format accepted by cURL
$headers = [
    'Accept' => 'application/json',
    'Authorization' => 'Bearer token123',
];

$formatted = Curl::format_headers($headers);
// Result: ['accept: application/json', 'authorization: Bearer token123', ...]
```

> The `format_headers()` method will automatically add User-Agent if none exists,
> and also add the `Expect:` header to avoid issues with certain servers.