# JSON Web Token (JWT)

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encode Data](#encode-data)
-   [Decode Data](#decode-data)
-   [Refresh Token](#refresh-token)
-   [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

As the name suggests, the `JWT` component provides a simple way to handle encoding and decoding of [JSON Web Token](https://jwt.io/). This component is compatible with the [RFC 7519](https://tools.ietf.org/html/rfc7519) standard that is widely used.

> This component only supports 3 standard algorithms, namely `HS256`, `HS384`, and `HS512`.

<a id="encode-data"></a>

## Encode Data

#### Encoding data:

To encode data, use the `encode()` method as follows:

```php
$secret = 's3cr3t';
$data = [
    'iss' => 'http://example.org',
    'aud' => 'http://example.com',
    'iat' => 1356999524,
    'nbf' => 1357000000,
];

$jwt = JWT::encode($data, $secret);
// dd($jwt);
```

### Additional Headers

In addition to specifying the desired algorithm type, you can also pass additional headers as needed. Here's how:

```php
$headers = [
    'exp' => 3900, // expires in 65 minutes
    'type' => 'bearer',
    'foo' => 'bar',
];

$jwt = JWT::encode($data, $secret, $headers);
```

### Algorithms

By default, the above process will use the `HS256` algorithm.

However, you can also change it to another one:

```php
$jwt = JWT::encode($data, $secret, $headers, 'HS384');
// dd($jwt);
```

> Only the following algorithms are supported: `HS256` `HS384` and `HS512`.

<a id="decode-data"></a>

## Decode Data

#### Decoding data:

To decode data, use the `decode()` method as follows:

```php
$decoded = JWT::decode($jwt, 's3cr3t');
// dd($decoded);
```

You can also add additional options when decoding:

```php
$options = [
    'verify_exp' => true,  // Verify expiration time
    'verify_iat' => true,  // Verify issued at time
    'verify_nbf' => true,  // Verify not before time
];

$decoded = JWT::decode($jwt, 's3cr3t', $options);
```

<a id="refresh-token"></a>

## Refresh Token

The `refresh()` method is used to update the expiration time of an existing token without needing to re-encode all the payload:

#### Refreshing a token with a new expiration time:

```php
$token = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...';
$secret = 's3cr3t';
$new_exp = time() + 7200; // Expires in 2 hours

$refreshed_token = JWT::refresh($token, $secret, $new_exp);
// dd($refreshed_token);
```

#### Refresh with custom headers and algorithm:

```php
$headers = [
    'type' => 'bearer',
    'refresh' => true,
];

$refreshed_token = JWT::refresh($token, $secret, $new_exp, $headers, 'HS384');
```

**Use case for refresh token:**

```php
// In middleware for auto-refreshing tokens that will expire
Route::middleware('jwt.refresh', function () {
    $token = Request::bearer();
    
    if (!$token) {
        return Response::json(['error' => 'Token required'], 401);
    }
    
    try {
        $decoded = JWT::decode($token, Config::get('jwt.secret'));
        
        // Check if token will expire within 5 minutes
        $exp_threshold = time() + 300;
        
        if ($decoded->exp < $exp_threshold) {
            // Refresh token with 1 hour expiration from now
            $new_exp = time() + 3600;
            $new_token = JWT::refresh($token, Config::get('jwt.secret'), $new_exp);
            
            // Send new token via header
            header('X-New-Token: ' . $new_token);
        }
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token'], 401);
    }
});
```

<a id="practical-examples"></a>

## Practical Examples

### API Authentication with JWT

**Setup JWT configuration:**

File: `application/config/jwt.php`

```php
return [
    'secret' => 'your-secret-key-here',
    'algorithm' => 'HS256',
    'expiration' => 3600, // 1 hour
    'refresh_threshold' => 300, // 5 minutes
];
```

**Login endpoint:**

```php
Route::post('api/login', function () {
    $email = Input::get('email');
    $password = Input::get('password');
    
    $user = User::where('email', $email)->first();
    
    if (!$user || !Hash::check($password, $user->password)) {
        return Response::json(['error' => 'Invalid credentials'], 401);
    }
    
    // Generate JWT
    $payload = [
        'iss' => URL::to('/'),
        'iat' => time(),
        'exp' => time() + Config::get('jwt.expiration'),
        'user_id' => $user->id,
        'email' => $user->email,
    ];
    
    $token = JWT::encode($payload, Config::get('jwt.secret'));
    
    return Response::json([
        'token' => $token,
        'expires_in' => Config::get('jwt.expiration'),
        'user' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ],
    ]);
});
```

**Protected endpoint:**

```php
Route::middleware('jwt.auth', function () {
    $token = Request::bearer();
    
    if (!$token) {
        return Response::json(['error' => 'Token required'], 401);
    }
    
    try {
        $decoded = JWT::decode($token, Config::get('jwt.secret'));
        Request::$user_id = $decoded->user_id;
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token'], 401);
    }
});

Route::get('api/profile', ['before' => 'jwt.auth', function () {
    $user = User::find(Request::$user_id);
    
    return Response::json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
    ]);
}]);
```

**Refresh token endpoint:**

```php
Route::post('api/refresh', ['before' => 'jwt.auth', function () {
    $old_token = Request::bearer();
    $decoded = JWT::decode($old_token, Config::get('jwt.secret'));
    
    // Generate new expiration time
    $new_exp = time() + Config::get('jwt.expiration');
    
    // Refresh token
    $new_token = JWT::refresh($old_token, Config::get('jwt.secret'), $new_exp);
    
    return Response::json([
        'token' => $new_token,
        'expires_in' => Config::get('jwt.expiration'),
    ]);
}]);
```

**Middleware for JWT:**

File: `application/middlewares.php`

```php
// JWT Authentication Middleware
Route::middleware('jwt.auth', function () {
    $token = Request::bearer();
    
    if (!$token) {
        return Response::json(['error' => 'Token required'], 401);
    }
    
    try {
        $decoded = JWT::decode($token, Config::get('jwt.secret'));
        
        // Check expiration
        if (isset($decoded->exp) && $decoded->exp < time()) {
            return Response::json(['error' => 'Token expired'], 401);
        }
        
        // Store user info in request
        Request::$user_id = $decoded->user_id;
        Request::$token_data = $decoded;
        
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token: ' . $e->getMessage()], 401);
    }
});
```

**Example usage in frontend (JavaScript):**

```javascript
// Login
async function login(email, password) {
    const response = await fetch('/api/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, password })
    });
    
    const data = await response.json();
    
    if (data.token) {
        localStorage.setItem('jwt_token', data.token);
        return data;
    }
    
    throw new Error(data.error);
}

// API Request with JWT
async function getProfile() {
    const token = localStorage.getItem('jwt_token');
    
    const response = await fetch('/api/profile', {
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    // Check for refresh token in response header
    const newToken = response.headers.get('X-New-Token');
    if (newToken) {
        localStorage.setItem('jwt_token', newToken);
    }
    
    return await response.json();
}

// Refresh token
async function refreshToken() {
    const token = localStorage.getItem('jwt_token');
    
    const response = await fetch('/api/refresh', {
        method: 'POST',
        headers: {
            'Authorization': `Bearer ${token}`
        }
    });
    
    const data = await response.json();
    
    if (data.token) {
        localStorage.setItem('jwt_token', data.token);
    }
}
```

### Best Practices

**1. Use a strong secret key:**

```php
// Generate secret key
$secret = bin2hex(random_bytes(32)); // 64 hex characters
```

**2. Store secret key securely:**

```php
// Do not hardcode in code, use environment variables
$secret = getenv('JWT_SECRET') ?: Config::get('jwt.secret');
```

**3. Set reasonable expiration time:**

```php
// Short-lived token for security
'expiration' => 3600, // 1 hour

// Use refresh token for extension
```

**4. Validate all important claims:**

```php
$options = [
    'verify_exp' => true,  // Always verify expiration
    'verify_iat' => true,  // Verify issued at
    'verify_nbf' => true,  // Verify not before
];

$decoded = JWT::decode($token, $secret, $options);
```

**5. Handle token expiration gracefully:**

```php
try {
    $decoded = JWT::decode($token, $secret);
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'Expired') !== false) {
        return Response::json([
            'error' => 'Token expired',
            'code' => 'TOKEN_EXPIRED'
        ], 401);
    }
    
    return Response::json(['error' => 'Invalid token'], 401);
}
```
