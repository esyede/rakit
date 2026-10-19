# JSON Web Token (JWT)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encode Data](#encode-data)
    -   [Additional Headers](#additional-headers)
    -   [Algorithms](#algorithms)
-   [Decode Data](#decode-data)
    -   [Decode Options](#decode-options)
    -   [Time Claims](#time-claims)
-   [Refresh Token](#refresh-token)
-   [Practical Example](#practical-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `JWT` component encodes and decodes [JSON Web Tokens](https://jwt.io/) following [RFC 7519](https://datatracker.ietf.org/doc/html/rfc7519). It supports `HS256`, `HS384`, `HS512` (shared secret) and `RS256`, `RS384`, `RS512` (RSA key pair, requires OpenSSL). Every failure (bad signature, expired token, malformed segments ...) throws an `\Exception`.

<a id="encode-data"></a>

## Encode Data

`encode()` takes the payload array and the key, and returns the token string:

```php
$jwt = JWT::encode([
    'iss' => 'http://example.org',
    'aud' => 'http://example.com',
    'iat' => time(),
    'exp' => time() + 3600,
], 's3cr3t');
```

<a id="additional-headers"></a>

### Additional Headers

The third argument adds extra headers. `typ` and `alg` are always set by the component:

```php
$jwt = JWT::encode($data, $secret, ['kid' => 'key-1']);
```

<a id="algorithms"></a>

### Algorithms

The default algorithm is `HS256`; pass another as the fourth argument. For the `RS*` algorithms, encode with the PEM private key and decode with the PEM public key:

```php
$jwt = JWT::encode($data, $secret, [], 'HS384');
$jwt = JWT::encode($data, $private_key, [], 'RS256');
```

<a id="decode-data"></a>

## Decode Data

`decode()` verifies the token and returns its payload as a `stdClass`:

```php
$decoded = JWT::decode($jwt, 's3cr3t');
echo $decoded->iss;
```

> **Security:** the algorithms a token may use follow the key you decode with: a PEM key accepts only the `RS*` family, any other key only the `HS*` family. This stops someone who knows your public key from signing an `HS256` token with it as the secret. Pass the `algorithm` option to pin one specific algorithm.

<a id="decode-options"></a>

### Decode Options

```php
$decoded = JWT::decode($jwt, 's3cr3t', [
    'algorithm' => 'HS256',              // allowed algorithm(s), string or array
    'aud' => 'http://example.com',       // required audience claim
    'iss' => 'http://example.org',       // required issuer claim
    'validator' => function ($payloads, $headers) {
        // extra checks; throw an exception to reject the token
    },
]);
```

<a id="time-claims"></a>

### Time Claims

The `exp`, `nbf` and `iat` claims are always verified when present. Set `JWT::$leeway` (seconds) to tolerate clock skew between servers, and `JWT::$timestamp` to override the current time (handy in tests):

```php
JWT::$leeway = 60;
```

<a id="refresh-token"></a>

## Refresh Token

`refresh()` decodes a still-valid token and re-issues it with a new `exp`, keeping the rest of the payload. By default it reuses the token's own algorithm:

```php
$new_token = JWT::refresh($token, $secret, time() + 7200);

// Custom headers and algorithm
$new_token = JWT::refresh($token, $secret, time() + 7200, ['kid' => 'key-1'], 'HS384');
```

For `RS*` tokens, the second argument is the public key used for decoding, so pass the private key as the sixth argument (`$sign_key`) to sign the new token:

```php
$new_token = JWT::refresh($token, $public_key, time() + 7200, [], null, $private_key);
```

<a id="practical-example"></a>
<a id="practical-examples"></a>

## Practical Example

Issue a token on login and protect API routes with a middleware. Keep the secret out of your code (for example `getenv('JWT_SECRET')`) and generate it with `bin2hex(Str::bytes(32))`:

```php
Route::post('api/login', function () {
    $user = User::where('email', '=', Input::get('email'))->first();

    if (! $user || ! Hash::check(Input::get('password'), $user->password)) {
        return Response::json(['error' => 'Invalid credentials'], 401);
    }

    $token = JWT::encode([
        'iss' => URL::to('/'),
        'iat' => time(),
        'exp' => time() + 3600,
        'user_id' => $user->id,
    ], getenv('JWT_SECRET'));

    return Response::json(['token' => $token, 'expires_in' => 3600]);
});

Route::middleware('jwt.auth', function () {
    $token = Request::bearer();

    try {
        $decoded = JWT::decode($token ? $token : '', getenv('JWT_SECRET'), ['algorithm' => 'HS256']);
        Request::foundation()->attributes->set('user_id', $decoded->user_id);
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token'], 401);
    }
});

Route::get('api/profile', ['before' => 'jwt.auth', function () {
    $user = User::find(Request::foundation()->attributes->get('user_id'));

    return Response::json(['id' => $user->id, 'name' => $user->name]);
}]);
```
