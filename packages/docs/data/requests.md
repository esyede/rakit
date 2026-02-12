# Request

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Accessing Request](#accessing-request)
  - [Request URI](#request-uri)
  - [Request Method](#request-method)
  - [Request Path](#request-path)
- [Request Headers](#request-headers)
  - [Retrieve Headers](#retrieve-headers)
  - [Check Header Exists](#check-header-exists)
  - [Common Headers](#common-headers)
- [Request IP Address](#request-ip-address)
- [Request Type Detection](#request-type-detection)
  - [AJAX Request](#ajax-request)
  - [HTTPS Request](#https-request)
  - [CLI Request](#cli-request)
  - [JSON Request](#json-request)
- [Content Negotiation](#content-negotiation)
  - [Accept Header](#accept-header)
  - [Accept Specific Types](#accept-specific-types)
  - [Content Type Preferences](#content-type-preferences)
  - [Content Type](#content-type)
- [Authorization](#authorization)
  - [Authorization Header](#authorization-header)
  - [Bearer Token](#bearer-token)
- [URI Information](#uri-information)
  - [Current URI](#current-uri)
  - [Full URI](#full-uri)
  - [URI Segments](#uri-segments)
  - [URI Pattern Matching](#uri-pattern-matching)
- [Query String](#query-string)
- [Request Route](#request-route)
- [Server Variables](#server-variables)
- [User Agent](#user-agent)
- [Referrer](#referrer)
- [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The `Request` class provides an object-oriented interface for the HTTP request being handled by the application. The Request object contains information about the HTTP request such as URI, headers, method, input data, and more.

**Advantages of using Request:**
- Centralized access to all request information
- Consistent and easy-to-use API
- Type detection (AJAX, HTTPS, CLI, JSON)
- Content negotiation support
- URI manipulation and pattern matching

<a id="accessing-request"></a>
## Accessing Request

<a id="request-uri"></a>
### Request URI

**Get current URI:**

```php
$uri = Request::uri();
// Example: "user/profile"

$uri = URI::current();
// Same as Request::uri()
```

**Get full URL:**

```php
$url = URI::full();
// Example: "http://example.com/user/profile?page=2"
```

<a id="request-method"></a>
### Request Method

**Get HTTP method:**

```php
$method = Request::method();
// GET, POST, PUT, DELETE, PATCH, etc

// Check specific method
if (Request::method() === 'POST') {
    // Handle POST request
}
```

**Check method type:**

```php
if (Request::is_method('post')) {
    // POST request
}

if (Request::is_method('get')) {
    // GET request
}
```

<a id="request-path"></a>
### Request Path

**Check if request matches path:**

```php
if (Request::is('user/*')) {
    // Request path starts with "user/"
}

if (Request::is('admin/dashboard')) {
    // Exact match
}

// Multiple patterns
if (Request::is(['admin/*', 'user/*'])) {
    // Matches either pattern
}
```

<a id="request-headers"></a>
## Request Headers

<a id="retrieve-headers"></a>
### Retrieve Headers

**Get specific header:**

```php
$accept = Request::header('Accept');
$authorization = Request::header('Authorization');
$userAgent = Request::header('User-Agent');

// With default value
$token = Request::header('X-API-Token', 'default-token');
```

**Get all headers:**

```php
$headers = Request::headers();

foreach ($headers as $name => $value) {
    echo "$name: $value\n";
}
```

<a id="check-header-exists"></a>
### Check Header Exists

```php
if (isset(Request::headers()['Authorization'])) {
    $auth = Request::header('Authorization');
}
```

<a id="common-headers"></a>
### Common Headers

```php
// Accept header
$accept = Request::header('Accept');

// Authorization header
$auth = Request::header('Authorization');

// Content-Type
$contentType = Request::header('Content-Type');

// User-Agent
$userAgent = Request::header('User-Agent');

// X-Requested-With (for AJAX detection)
$requestedWith = Request::header('X-Requested-With');

// Custom headers
$apiKey = Request::header('X-API-Key');
$apiVersion = Request::header('X-API-Version');
```

<a id="request-ip-address"></a>
## Request IP Address

**Get client IP address:**

```php
$ip = Request::ip();
// 192.168.1.100

// Use in logging
Log::info('Request from IP: ' . Request::ip());

// IP-based rate limiting
$key = 'rate_limit:' . Request::ip();
```

**Check specific IP:**

```php
if (Request::ip() === '192.168.1.100') {
    // Request from specific IP
}

// IP whitelist
$whitelist = ['192.168.1.100', '10.0.0.1'];
if (in_array(Request::ip(), $whitelist)) {
    // IP allowed
}
```

<a id="request-type-detection"></a>
## Request Type Detection

<a id="ajax-request"></a>
### AJAX Request

**Check if request is AJAX:**

```php
if (Request::ajax()) {
    return Response::json(['status' => 'success']);
}

return View::make('page');
```

**Conditional response:**

```php
$users = User::all();

if (Request::ajax()) {
    return Response::json($users);
}

return View::make('users.index', compact('users'));
```

<a id="https-request"></a>
### HTTPS Request

**Check if request is secure (HTTPS):**

```php
if (Request::secure()) {
    // Request uses HTTPS
}

// Redirect to HTTPS
if (!Request::secure()) {
    return Redirect::to('https://' . $_SERVER['HTTP_HOST'] . Request::uri());
}
```

<a id="cli-request"></a>
### CLI Request

**Check if running from command line:**

```php
if (Request::cli()) {
    // Running from console
    echo "Command line interface\n";
} else {
    // Running from web
    return View::make('home');
}
```

<a id="json-request"></a>
### JSON Request

**Check if request content is JSON:**

```php
if (Request::is_json()) {
    // Request body contains JSON
    $data = Input::json();
}
```

**Check if request expects JSON response:**

```php
if (Request::expects_json()) {
    // Client expects JSON response (AJAX non-PJAX or wants JSON)
    return Response::json(['data' => $data]);
}
```

**Check if request wants JSON response:**

```php
if (Request::wants_json()) {
    // Client prefers JSON based on Accept header
    return Response::json(['data' => $data]);
}
```

**Adaptive response:**

```php
$users = User::all();

if (Request::expects_json()) {
    return Response::json($users);
}

return View::make('users.index', compact('users'));
```

<a id="content-negotiation"></a>
## Content Negotiation

<a id="accept-header"></a>
### Accept Header

**Get all acceptable content types:**

```php
$accept = Request::accept();
// ['text/html', 'application/json', '*/*']

foreach ($accept as $type) {
    echo "Accepts: $type\n";
}
```

**Check if request accepts specific types:**

```php
// Check single type
if (Request::accepts('application/json')) {
    return Response::json($data);
}

// Check multiple types
if (Request::accepts(['text/html', 'application/xhtml+xml'])) {
    return View::make('page');
}

// Check with array argument
$types = ['application/json', 'text/xml'];
if (Request::accepts($types)) {
    // Client accepts one of these types
}
```

<a id="accept-specific-types"></a>
### Accept Specific Types

**Check if accepts HTML:**

```php
if (Request::accept_html()) {
    return View::make('page');
}
```

**Check if accepts any content type:**

```php
if (Request::accept_any()) {
    // Client accepts any content type (*/* in Accept header)
    return Response::make($content);
}
```

<a id="content-type-preferences"></a>
### Content Type Preferences

**Get preferred content type:**

```php
$preferred = Request::prefers(['application/json', 'text/html', 'text/xml']);
// Returns the first matching type from the list, or null

if ($preferred === 'application/json') {
    return Response::json($data);
} elseif ($preferred === 'text/html') {
    return View::make('page');
} elseif ($preferred === 'text/xml') {
    return Response::make($xml, 200, ['Content-Type' => 'text/xml']);
}
```

**Matches type helper:**

```php
// Check if actual type matches requested type
if (Request::matches_type('text/html', 'text/*')) {
    // Match! text/html matches text/* pattern
}

if (Request::matches_type('application/json', '*/*')) {
    // Match! Any type matches */*
}
```

<a id="content-type"></a>
### Content Type

**Get request content type:**

```php
$contentType = Request::header('Content-Type');

if ($contentType === 'application/json') {
    $data = Input::json();
} elseif ($contentType === 'application/x-www-form-urlencoded') {
    $data = Input::all();
}
```

<a id="authorization"></a>
## Authorization

<a id="authorization-header"></a>
### Authorization Header

**Get Authorization header:**

```php
$auth = Request::authorization();
// Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

if ($auth) {
    // Process authorization
} else {
    return Response::json(['error' => 'Unauthorized'], 401);
}
```

<a id="bearer-token"></a>
### Bearer Token

**Extract Bearer token:**

```php
$token = Request::bearer();
// eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...

if ($token) {
    // Verify token
    $user = JWT::decode($token);
} else {
    return Response::json(['error' => 'Token required'], 401);
}
```

**API authentication example:**

```php
Route::middleware('api.auth', function () {
    $token = Request::bearer();
    
    if (!$token) {
        return Response::json(['error' => 'Token required'], 401);
    }
    
    try {
        $payload = JWT::decode($token);
        Request::$user = User::find($payload->user_id);
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token'], 401);
    }
});

Route::get('api/profile', ['before' => 'api.auth', function () {
    return Response::json(Request::$user);
}]);
```

<a id="uri-information"></a>
## URI Information

<a id="current-uri"></a>
### Current URI

```php
$uri = URI::current();
// "user/profile"

$uri = Request::uri();
// Same as URI::current()
```

<a id="full-uri"></a>
### Full URI

```php
$fullUri = URI::full();
// "http://example.com/user/profile?page=2&sort=name"
```

<a id="uri-segments"></a>
### URI Segments

**Get specific segment:**

```php
// URI: /user/profile/edit
$segment1 = URI::segment(1); // "user"
$segment2 = URI::segment(2); // "profile"
$segment3 = URI::segment(3); // "edit"

// With default value
$segment = URI::segment(4, 'default'); // "default" if not present
```

**Get all segments:**

```php
$segments = URI::segments();
// ['user', 'profile', 'edit']

foreach ($segments as $index => $segment) {
    echo "Segment $index: $segment\n";
}
```

<a id="uri-pattern-matching"></a>
### URI Pattern Matching

**Check if URI matches pattern:**

```php
// Exact match
if (URI::is('home')) {
    echo 'Home page';
}

// Wildcard match
if (URI::is('user/*')) {
    echo 'User section';
}

// Multiple patterns
if (URI::is(['admin/*', 'dashboard/*'])) {
    echo 'Admin or dashboard';
}
```

**Practical examples:**

```php
// Breadcrumb generation
if (URI::is('admin/*')) {
    $breadcrumbs[] = ['Admin', '/admin'];
    
    if (URI::is('admin/users/*')) {
        $breadcrumbs[] = ['Users', '/admin/users'];
    }
}

// Active menu
$active = URI::is('products/*') ? 'active' : '';

// Conditional rendering
if (URI::is('blog/*')) {
    View::share('sidebar', View::make('blog.sidebar'));
}
```

<a id="query-string"></a>
## Query String

**Get query parameters:**

```php
// Via Input
$page = Input::get('page');
$sort = Input::get('sort');

// Via Request
$query = Request::foundation()->query->all();

// Check query parameter exists
if (Input::has('search')) {
    $keyword = Input::get('search');
}
```

<a id="request-route"></a>
## Request Route

**Get current route:**

```php
$route = Request::route();

// Check route name
if ($route->is('user.profile')) {
    echo 'User profile page';
}

// Get route parameters
$params = $route->parameters;

// Get route action
$action = $route->action;
```

<a id="server-variables"></a>
## Server Variables

**Access $_SERVER variables:**

```php
// Get specific server variable
$host = Request::server('HTTP_HOST');
$referer = Request::server('HTTP_REFERER');
$userAgent = Request::server('HTTP_USER_AGENT');

// Get all server variables
$server = Request::servers();

// Common server variables
$method = Request::server('REQUEST_METHOD');
$protocol = Request::server('SERVER_PROTOCOL');
$port = Request::server('SERVER_PORT');
$remoteAddr = Request::server('REMOTE_ADDR');
```

**With default value:**

```php
$referer = Request::server('HTTP_REFERER', '/');
```

<a id="user-agent"></a>
## User Agent

**Get user agent:**

```php
$userAgent = Request::header('User-Agent');
// Mozilla/5.0 (Windows NT 10.0; Win64; x64)...

// Check for specific browser
if (str_contains($userAgent, 'Chrome')) {
    echo 'Chrome browser';
}

if (str_contains($userAgent, 'Mobile')) {
    echo 'Mobile device';
}
```

<a id="referrer"></a>
## Referrer

**Get referrer URL:**

```php
$referrer = Request::referrer();
// http://example.com/previous-page

// Redirect back to referrer
return Redirect::to($referrer);

// Or use shorthand
return Redirect::back();
```

**Check referrer:**

```php
if (Request::referrer()) {
    echo 'Came from: ' . Request::referrer();
} else {
    echo 'Direct access';
}
```

<a id="practical-examples"></a>
## Practical Examples

**API Endpoint with content negotiation:**

```php
Route::get('api/users', function () {
    $users = User::all();
    
    // Content negotiation with multiple checks
    if (Request::expects_json()) {
        return Response::json($users);
    }
    
    // Check preferred format
    $format = Request::prefers(['application/json', 'text/xml', 'text/html']);
    
    if ($format === 'application/json') {
        return Response::json($users);
    } elseif ($format === 'text/xml') {
        return Response::make($users->to_xml(), 200, [
            'Content-Type' => 'application/xml'
        ]);
    }
    
    return View::make('users.index', compact('users'));
});
```

**Conditional layout based on request type:**

```php
class Base_Controller extends Controller
{
    public function layout()
    {
        // No layout for AJAX
        if (Request::ajax()) {
            return null;
        }
        
        // No layout for API or JSON requests
        if (Request::is('api/*') || Request::expects_json()) {
            return null;
        }
        
        // Mobile layout
        if (str_contains(Request::header('User-Agent'), 'Mobile')) {
            return View::make('layouts.mobile');
        }
        
        // Default layout
        return View::make('layouts.master');
    }
}
```

**Logging with request info:**

```php
Route::middleware('after', function ($response) {
    Log::info('Request processed', [
        'uri' => Request::uri(),
        'method' => Request::method(),
        'ip' => Request::ip(),
        'user_agent' => Request::header('User-Agent'),
        'referrer' => Request::referrer(),
        'status' => $response->status(),
    ]);
});
```

**IP-based rate limiting:**

```php
Route::middleware('throttle', function ($limit = 60) {
    $key = 'throttle:' . Request::ip() . ':' . Request::uri();
    $attempts = Cache::get($key, 0);
    
    if ($attempts >= $limit) {
        Log::warning('Rate limit exceeded', [
            'ip' => Request::ip(),
            'uri' => Request::uri(),
        ]);
        
        return Response::make('Too many requests', 429);
    }
    
    Cache::put($key, $attempts + 1, 60);
});
```

**Secure endpoint with various checks:**

```php
Route::post('admin/action', function () {
    // Check HTTPS
    if (!Request::secure()) {
        return Response::error('403', 'HTTPS required');
    }
    
    // Check bearer token
    $token = Request::bearer();
    if (!$token) {
        return Response::json(['error' => 'Token required'], 401);
    }
    
    // Verify token
    try {
        $user = JWT::decode($token);
    } catch (\Exception $e) {
        return Response::json(['error' => 'Invalid token'], 401);
    }
    
    // Check IP whitelist
    $whitelist = ['192.168.1.100', '10.0.0.1'];
    if (!in_array(Request::ip(), $whitelist)) {
        Log::warning('Unauthorized IP access attempt', [
            'ip' => Request::ip(),
            'uri' => Request::uri(),
            'user' => $user->id,
        ]);
        return Response::error('403');
    }
    
    // Process request
    return Response::json(['status' => 'success']);
});
```

**Adaptive response based on client:**

```php
public function action_show($id)
{
    $post = Post::find($id);
    
    // Use content negotiation
    $format = Request::prefers([
        'application/json',
        'application/xml',
        'text/html'
    ]);
    
    switch ($format) {
        case 'application/json':
            return Response::json($post);
            
        case 'application/xml':
            return Response::make($post->to_xml(), 200, [
                'Content-Type' => 'application/xml'
            ]);
            
        default:
            return View::make('posts.show', compact('post'));
    }
}
```

**Automatic breadcrumb from URI:**

```php
public function generate_breadcrumbs()
{
    $segments = URI::segments();
    $breadcrumbs = [['Home', '/']];
    $path = '';
    
    foreach ($segments as $segment) {
        $path .= '/' . $segment;
        $breadcrumbs[] = [ucfirst($segment), $path];
    }
    
    return $breadcrumbs;
}
```

**Debug helper:**

```php
Route::get('debug/request', function () {
    if (!Config::get('application.debug')) {
        return Response::error('404');
    }
    
    return [
        'uri' => Request::uri(),
        'method' => Request::method(),
        'ip' => Request::ip(),
        'secure' => Request::secure(),
        'ajax' => Request::ajax(),
        'cli' => Request::cli(),
        'is_json' => Request::is_json(),
        'expects_json' => Request::expects_json(),
        'wants_json' => Request::wants_json(),
        'accept_html' => Request::accept_html(),
        'accept_any' => Request::accept_any(),
        'accept' => Request::accept(),
        'authorization' => Request::authorization(),
        'bearer' => Request::bearer(),
        'headers' => Request::headers(),
        'segments' => URI::segments(),
        'route' => [
            'name' => Request::route() ? Request::route()->action : null,
            'params' => Request::route() ? Request::route()->parameters : [],
        ],
    ];
});
```
