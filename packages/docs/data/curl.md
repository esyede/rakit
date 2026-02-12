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

Curl is a common command used in Unix-based systems. Actually,
this term is an abbreviation of "Client URL".

Its uses include checking connectivity to a URL and transferring data.
Additionally, this type of command can be used in various protocols. Curl is also equipped with
[libcurl](https://curl.haxx.se/libcurl), a client-side URL transfer library.

> Don't forget to install the [PHP Curl](https://php.net/manual/en/book.curl.php)
> extension on your server if it's not already there.

<a id="making-requests"></a>

## Making Requests

Rakit has provided several functionalities that you can use to work with Curl.
For example, when you want to fetch data from third-party API providers.

Here are some request types you can use:

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

In addition, you can also send requests following
[standard methods](https://iana.org/assignments/http-methods/http-methods.xhtml)
or custom methods as needed:

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
- And many more (see [IANA HTTP Methods](https://iana.org/assignments/http-methods/http-methods.xhtml))

Now, let's try making a simple request using this component:

```php
$headers = ['Accept' => 'application/json'];
$query = ['foo' => 'hello', 'bar' => 'world'];

$response = Curl::post('https://mockbin.com/request', $headers, $query);

$response->code;        // contains http status code
$response->headers;     // contains object request headers
$response->body;        // contains object request body
$response->raw_body;    // contains raw body string
```

<a id="json-request"></a>

### JSON Request

To make a JSON request, please use the `body_json()` method like this:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_json($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

With this method, the `'Content-Type'` header will be automatically set to `'application/json'`
and the request body will also be converted to JSON format via [json_encode](https://php.net/json_encode).

<a id="form-request"></a>

### Form Request

To make a form request, please use the `body_form()` method like this:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_form($data);
$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

With this method, the `'Content-Type'` header will be automatically set to `'application/x-www-form-urlencoded'`
and the request body will also be converted to query string format via [http_build_query](https://php.net/http_build_query).

<a id="multipart-request"></a>

### Multipart Request

To make a multipart request, please use the `body_multipart()` method like this:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];

$body = Curl::body_multipart($data);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

With this method, the `'Content-Type'` header will be automatically set to `'multipart/form-data'`
and also a `--boundary` will be added automatically.

<a id="multipart-file"></a>

### Multipart File

To make a file upload request, please use the `body_multipart()` method like this:

```php
$headers = ['Accept' => 'application/json'];
$data = ['name' => 'budi', 'age' => 28];
$files = ['bio' => '/path/to/bio.json', 'avatar' => '/path/to/avatar.jpg'];

$body = Curl::body_multipart($data, $files);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

However, if you want to further customize the properties of the uploaded file,
you can do so with the `body_file()` method like this:

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

> In the example above, we do not use the `body_multipart()` method,
> because it's not necessary when you add files manually.

<a id="custom-body"></a>

### Custom Body

You can also send a custom request body without using the `body_xxx` methods above,
for example, you can use the [serialize](https://php.net/serialize) function for the request body
and also with a custom `Content-Type` like this:

```php
$headers = ['Accept' => 'application/json', 'Content-Type' => 'application/x-php-serialized'];
$body = serialize(['foo' => 'hello', 'bar' => 'world']);

$response = Curl::post('https://mockbin.com/request', $headers, $body);
```

<a id="authentication"></a>

## Authentication

By default, this component will use Basic Auth method so you only need to
pass username and password _(optional)_ for your request authentication:

```php
// Basic auth (default)
Curl::auth('username', 'password');

// Custom auth
Curl::auth('username', 'password', CURLAUTH_DIGEST);
```

In the 3rd parameter, you can specify what authentication method you need.
Here is a list of supported authentication methods:

| Method               | Description                                                                        |
| -------------------- | ---------------------------------------------------------------------------------- |
| `CURLAUTH_BASIC`     | HTTP Basic auth (default)                                                          |
| `CURLAUTH_DIGEST`    | HTTP Digest auth ([RFC 2617](https://www.ietf.org/rfc/rfc2617.txt))                |
| `CURLAUTH_DIGEST_IE` | HTTP Digest auth IE (Internet Explorer)                                            |
| `CURLAUTH_NEGOTIATE` | HTTP Negotiate (SPNEGO) auth ([RFC 4559](https://www.ietf.org/rfc/rfc4559.txt))    |
| `CURLAUTH_NTLM`      | HTTP NTLM auth (Microsoft)                                                         |
| `CURLAUTH_NTLM_WB`   | NTLM WinBind ([documentation](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)) |
| `CURLAUTH_ANY`       | See: [documentation](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ANYSAFE`   | See: [documentation](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |
| `CURLAUTH_ONLY`      | See: [documentation](https://curl.haxx.se/libcurl/c/CURLOPT_HTTPAUTH.html)         |

> If you pass more than one authentication method (using bitmask operator for example),
> then by default, this component will first make a request to the destination URL
> to see what authentication methods it supports, then adjust to the methods you passed.
> _For some types of methods, this will cause an additional round-trip thus increasing the potential for timeout._

<a id="cookie"></a>

## Cookie

You can also add one or more cookie headers,
written separated by semicolon and space like this:

```php
$cookie = 'session=foo; logged=true';

Curl::cookie($cookie)
```

In addition to using string notation, you can also add cookie headers via file like this:

```php
$path = path('storage').'cookies.txt';

Curl::cookie_file($path)
```

Where the `cookies.txt` file content is the cookie string declaration as explained above.

<a id="response"></a>

## Response

After the request is executed, this component will always return an `\stdClass` object with properties:

-   `code` - which will contain the http status code (e.g. `200`)
-   `headers` - which will contain the http response headers
-   `body` - which will contain the response body formatted into an object or array (if possible).
-   `raw_body` - which will contain the raw response body

<a id="advanced-configuration"></a>

## Advanced Configuration

Of course, you can further configure this component to suit your needs.

<a id="json-decode"></a>

### JSON Decode

To change the default JSON decode behavior of this component, please use
the `json_options()` method like this:

```php
$associative = true; // Return as associative array
$depth = 512; // Set maximum nesting depth
$flags = JSON_NUMERIC_CHECK & JSON_FORCE_OBJECT & JSON_UNESCAPED_SLASHES; // Set decode flags

Curl::json_options($associative, $depth, $flags);
```

<a id="timeout"></a>

### Timeout

You can also set how long the request should take until it times out:

```php
Curl::timeout(5); // Request times out after 5 seconds
```

<a id="proxy"></a>

### Proxy

You can also set a proxy for the request. The proxy types that can be used include:
`CURLPROXY_HTTP`, `CURLPROXY_HTTP_1_0`, `CURLPROXY_SOCKS4`,
`CURLPROXY_SOCKS5`, `CURLPROXY_SOCKS4A`, and `CURLPROXY_SOCKS5_HOSTNAME`.

> Complete guide on proxy types can be seen on the
> [cURL documentation page](https://curl.haxx.se/libcurl/c/CURLOPT_PROXYTYPE.html)

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

You can also declare default headers that will be used for every request,
so you don't have to repeat their declaration on every request:

```php
Curl::default_header('Header1', 'Value1');
Curl::default_header('Header2', 'Value2');
```

Need to declare several default headers at once? Easy:

```php
Curl::default_headers([
    'Header1' => 'Value1',
    'Header2' => 'Value2',
]);
```

In addition, you can also clear all the default headers you declared earlier:

```php
Curl::clear_default_headers();
```

<a id="default-curl-options"></a>

### Default cURL Options

You can also declare default [cURL options](https://php.net/curl_setopt)
that will be used on every request:

```php
Curl::curl_option(CURLOPT_COOKIE, 'foo=bar');
```

Need to declare several default options at once?

```php
Curl::curl_options([
    CURLOPT_COOKIE => 'foo=bar',
]);
```

Of course, you can also clear all the default options you declared earlier:

```php
Curl::clear_curl_options();
```

<a id="ssl-validation"></a>

### SSL Validation

By default, this component disables SSL validation for compatibility
with older PHP versions. To change this, please use the following method:

```php
// Enable SSL validation
Curl::verify_peer(true);
Curl::verify_host(true);

// Disable SSL validation
Curl::verify_peer(false);
Curl::verify_host(false);
```

<a id="additional-functions"></a>

## Additional Functions

This component also provides some additional functions for advanced needs:

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
curl_setopt($handler, CURLOPT_VERBOSE, true);
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