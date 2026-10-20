# HTTP Tests

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Making Requests](#making-requests)
-   [Sessions, Cookies & CSRF](#sessions-cookies--csrf)
-   [Authentication](#authentication)
-   [Errors](#errors)
-   [Assertions](#assertions)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `System\Testing\Http` trait sends requests through the whole application, without a web
server: middleware, session, routing, controllers and views run as they would for a browser. Add
it to any test case, such as one created by `php rakit make:test user`, and run the tests with
`php rakit test:run`:

```php
class UserTest extends \PHPUnit_Framework_TestCase
{
    use \System\Testing\Http;

    public function testTheProfilePageLoads()
    {
        $this->get('users/7')
            ->assert_ok()
            ->assert_see('Budi');
    }
}
```

<a id="making-requests"></a>

## Making Requests

```php
$this->get('search?q=rakit');
$this->post('users', ['name' => 'Budi', 'email' => 'budi@example.com']);
$this->put('users/7', $data);
$this->patch('users/7', $data);
$this->delete('users/7');
$this->json('POST', 'api/users', ['name' => 'Budi']); // JSON body, Accept: application/json
$this->call('OPTIONS', 'api/users');                    // any method
```

Every method takes extra headers as its last argument. `with_headers()` sends headers with all
following requests:

```php
$this->with_headers(['Authorization' => 'Bearer '.$token])->get('api/me');
```

Each request starts from a clean framework state, as in [worker mode](/docs/workers), and the PHP
globals of the test are put back afterwards.

<a id="sessions-cookies--csrf"></a>

## Sessions, Cookies & CSRF

The test keeps cookies between requests, as a browser does, so the session carries over:

```php
$this->post('cart', ['product' => 3]);
$this->get('cart')->assert_see('Product 3');
```

Requests that change data (`POST`, `PUT`, `PATCH`, `DELETE`) send the session's CSRF token in an
`X-Csrf-Token` header, so the `csrf` middleware runs for real and passes. Send your own header to
test a wrong token. `with_cookies(['theme' => 'dark'])` adds cookies, and `forget_client()` starts
over with no cookies, no session and no user, as a fresh browser.

<a id="authentication"></a>

## Authentication

`acting_as()` logs a user in for the following requests, without a login request:

```php
$user = User::find(1);

$this->acting_as($user)->get('dashboard')->assert_ok();
```

<a id="errors"></a>

## Errors

`abort()`, `abort_if()` and `find_or_fail()` answer with their error response, which you can
assert on. Any other exception is **not** turned into a 500 page: it is thrown into the test, with
its message and trace, so a failure points at its cause.

```php
$this->get('admin')->assert_forbidden();
```

<a id="assertions"></a>

## Assertions

Each assertion returns the response, so they chain.

| Assertion | Passes when |
| --- | --- |
| `assert_status(201)` | the status is 201 |
| `assert_ok()`, `assert_created()`, `assert_no_content()` | 200, 201, 204 |
| `assert_unauthorized()`, `assert_forbidden()`, `assert_not_found()`, `assert_unprocessable()` | 401, 403, 404, 422 |
| `assert_redirect()` / `assert_redirect('login')` | it redirects / to `URL::to('login')` |
| `assert_see('Budi')` / `assert_dont_see(..)` | the body contains the HTML-escaped text; pass `false` to compare raw HTML |
| `assert_json(['user' => ['id' => 7]])` | the JSON body contains these keys with these values; other keys may exist |
| `assert_json_path('user.id', 7)` | the value at a "dot" path |
| `assert_header('X-Api', 'v1')` / `assert_header_missing(..)` | a header is present (with a value) / absent |
| `assert_cookie('remember')` | the response sets the cookie |
| `assert_session_has('status', 'saved')` / `assert_session_missing(..)` | the session (flash data included) holds the key (with a value) / not |
| `assert_session_has_errors(['email'])` | validation errors were flashed, for those fields |

The response also gives you `status()`, `header($name)`, `json($path = null)` and `content`, the
body as the client receives it.
