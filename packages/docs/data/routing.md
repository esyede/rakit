# Routing

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Basic Routing](#basic-routing)
  - [Simple Route](#simple-route)
  - [HTTP Methods](#http-methods)
  - [Route Multiple Methods](#route-multiple-methods)
- [Route Parameters](#route-parameters)
  - [Required Parameters](#required-parameters)
  - [Optional Parameters](#optional-parameters)
  - [Wildcard Constraints](#wildcard-constraints)
- [Route Shortcut](#route-shortcut)
  - [Route Redirect](#route-redirect)
  - [Route View](#route-view)
- [Named Routes](#named-routes)
  - [Defining Named Routes](#defining-named-routes)
  - [Using Named Routes](#using-named-routes)
  - [Check Route Name](#check-route-name)
- [Route Groups](#route-groups)
  - [Group With Middleware](#group-with-middleware)
  - [Group With Prefix](#group-with-prefix)
  - [Nested Groups](#nested-groups)
  - [Group With Domain](#group-with-domain)
- [Middleware](#middleware)
  - [Defining Middleware](#defining-middleware)
  - [Middleware On Route](#middleware-on-route)
  - [Middleware Before & After](#middleware-before--after)
  - [Middleware With Parameters](#middleware-with-parameters)
  - [Middleware Pattern](#middleware-pattern)
  - [Global Middleware](#global-middleware)
- [Controller Routing](#controller-routing)
  - [Basic Controller Route](#basic-controller-route)
  - [Route to Specific Action](#route-to-specific-action)
  - [RESTful Controller](#restful-controller)
  - [Resource Controller](#resource-controller)
- [Route for Package](#route-for-package)
- [Route Helpers](#route-helpers)
- [404 Handler](#404-handler)
- [Route Listing](#route-listing)
- [Route Testing via CLI](#route-testing-via-cli)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Routing is a way to direct HTTP requests to the appropriate handler. Rakit provides a powerful and expressive routing system for defining your application's routes.

Routes are defined in the `application/routes.php` file and will be evaluated according to their definition order.

**Advantages of routing in Rakit:**
- Clean and expressive syntax
- Support for closures and controllers
- Named routes for easier maintenance
- Route grouping for better organization
- Middleware support for request filtering
- Automatic dependency injection

<a id="basic-routing"></a>
## Basic Routing

<a id="simple-route"></a>
### Simple Route

The most basic route accepts a URI and a closure:

```php
Route::get('/', function () {
    return 'Hello World!';
});
```

This route will respond to `GET` requests to `/` with the string "Hello World!".

<a id="http-methods"></a>
### HTTP Methods

Rakit supports all standard HTTP methods:

```php
// GET request
Route::get('users', function () {
    return 'List of users';
});

// POST request
Route::post('users', function () {
    return 'Create new user';
});

// PUT request
Route::put('users/{id}', function ($id) {
    return 'Update user ' . $id;
});

// PATCH request
Route::patch('users/{id}', function ($id) {
    return 'Partial update user ' . $id;
});

// DELETE request
Route::delete('users/{id}', function ($id) {
    return 'Delete user ' . $id;
});

// HEAD request
Route::head('users', function () {
    return '';
});

// OPTIONS request
Route::options('users', function () {
    return '';
});
```

<a id="route-multiple-methods"></a>
### Route Multiple Methods

**Route for all HTTP methods:**

```php
Route::any('api/webhook', function () {
    return 'Webhook handler';
});
```

**Route for specific multiple methods:**

You can register the same route for several HTTP methods:

```php
// Way 1: Separate registration with shared handler
$handler = function () {
    if (Request::method() === 'GET') {
        return View::make('form');
    }

    return 'Form submitted';
};

Route::get('form', $handler);
Route::post('form', $handler);

// Way 2: Or use Route::any() for all methods
Route::any('form', function () {
    if (Request::method() === 'GET') {
        return View::make('form');
    }

    return 'Form submitted';
});
```

<a id="route-parameters"></a>
## Route Parameters

<a id="required-parameters"></a>
### Required Parameters

Capture values from URI using wildcards:

```php
Route::get('user/(:num)', function ($id) {
    return 'User ID: ' . $id;
});

Route::get('post/(:any)', function ($slug) {
    return 'Post slug: ' . $slug;
});

Route::get('user/(:num)/post/(:num)', function ($user_id, $post_id) {
    return "User $user_id, Post $post_id";
});
```

<a id="optional-parameters"></a>
### Optional Parameters

Make parameters optional with `?`:

```php
Route::get('blog/(:any?)', function ($category = 'all') {
    return 'Category: ' . $category;
});

Route::get('page/(:num?)', function ($page = 1) {
    return 'Page: ' . $page;
});
```

<a id="wildcard-constraints"></a>
### Wildcard Constraints

**Available wildcards:**

| Wildcard | Constraint | Example |
|----------|------------|---------|
| `(:num)` | Numeric only | `123`, `456` |
| `(:alpha)` | Alphabetic only | `abc`, `xyz` |
| `(:any)` | Alphanumeric + dash/underscore | `hello-world`, `hello_world` |
| `(:all)` | All characters (including `/`) | `path/to/file.txt` |
| `(:num?)` | Optional numeric | |
| `(:alpha?)` | Optional alpha | |
| `(:any?)` | Optional any | |

**Usage examples:**

```php
// Only numbers
Route::get('order/(:num)', function ($id) {
    return Order::find($id);
});

// Only letters
Route::get('category/(:alpha)', function ($name) {
    return Category::where('name', $name)->first();
});

// Alphanumeric
Route::get('product/(:any)', function ($slug) {
    return Product::where('slug', $slug)->first();
});

// Capture all (including /)
Route::get('files/(:all)', function ($path) {
    return Storage::get($path);
});
```

<a id="route-shortcut"></a>
## Route Shortcut

<a id="route-redirect"></a>
### Route Redirect

Shortcut for simple redirects:

```php
// Redirect with 302 status
Route::redirect('old-page', 'new-page');

// Redirect with custom status
Route::redirect('old-page', 'new-page', 301);

// Redirect to named route
Route::redirect('legacy', 'home');
```

<a id="route-view"></a>
### Route View

Shortcut to return view directly:

```php
// Return view without data
Route::view('/', 'home');

// Return view with data
Route::view('about', 'about', ['title' => 'About Us']);

Route::view('contact', 'contact', [
    'email' => 'contact@example.com',
    'phone' => '123-456-7890',
]);
```

<a id="named-routes"></a>
## Named Routes

<a id="defining-named-routes"></a>
### Defining Named Routes

Named routes allow you to refer to routes by name instead of URI:

```php
Route::get('/', ['as' => 'home', function () {
    return View::make('home');
}]);

Route::get('user/profile', ['as' => 'profile', function () {
    return View::make('user.profile');
}]);

Route::get('products', ['as' => 'products.index', function () {
    return Product::all();
}]);
```

**With controller:**

```php
Route::get('user/(:num)', [
    'as' => 'user.show',
    'uses' => 'user@show'
]);
```

<a id="using-named-routes"></a>
### Using Named Routes

**Generate URL:**

```php
$url = URL::to_route('profile');
// http://example.com/user/profile

$url = URL::to_route('user.show', [1]);
// http://example.com/user/1
```

**Redirect to named route:**

```php
return Redirect::to_route('home');

return Redirect::to_route('user.show', [$user->id]);
```

**In view (Blade):**

```blade
<a href="{{ route('profile') }}">Profile</a>

<a href="{{ route('user.show', [$user->id]) }}">View User</a>
```

<a id="check-route-name"></a>
### Check Route Name

```php
// Check if current route has specific name
if (Request::route()->is('home')) {
    echo 'Current route is home';
}

if (Request::route()->is('user.show')) {
    echo 'Current route is user.show';
}

// Check if route name exists
if (Route::has('profile')) {
    echo 'Route named "profile" exists';
}
```

<a id="route-groups"></a>
## Route Groups

Route groups allow you to apply attributes to multiple routes at once.

<a id="group-with-middleware"></a>
### Group With Middleware

```php
Route::group(['before' => 'auth'], function () {
    Route::get('dashboard', function () {
        return View::make('dashboard');
    });

    Route::get('settings', function () {
        return View::make('settings');
    });

    Route::get('profile', function () {
        return View::make('profile');
    });
});
```

**Multiple middleware:**

```php
Route::group(['before' => 'auth|verified'], function () {
    Route::get('admin/users', function () {
        return View::make('admin.users');
    });

    Route::get('admin/posts', function () {
        return View::make('admin.posts');
    });
});
```

<a id="group-with-prefix"></a>
### Group With Prefix

```php
// Prefix 'admin' for all routes in group
Route::group(['prefix' => 'admin'], function () {
    // URL: /admin/users
    Route::get('users', function () {
        return 'Admin Users';
    });

    // URL: /admin/posts
    Route::get('posts', function () {
        return 'Admin Posts';
    });

    // URL: /admin/settings
    Route::get('settings', function () {
        return 'Admin Settings';
    });
});
```

<a id="nested-groups"></a>
### Nested Groups

```php
Route::group(['prefix' => 'admin', 'before' => 'auth'], function () {
    Route::get('/', function () {
        return 'Admin Dashboard';
    });

    Route::group(['before' => 'role:admin'], function () {
        // URL: /admin/users
        // Middleware: auth, role:admin
        Route::get('users', function () {
            return 'Manage Users';
        });

        // URL: /admin/settings
        // Middleware: auth, role:admin
        Route::get('settings', function () {
            return 'System Settings';
        });
    });
});
```

**Group with multiple attributes:**

```php
Route::group([
    'prefix' => 'api/v1',
    'before' => 'api|throttle:60',
    'after' => 'cors',
], function () {
    Route::get('users', 'api.users@index');
    Route::post('users', 'api.users@store');
    Route::get('posts', 'api.posts@index');
});
```

<a id="group-with-domain"></a>
### Group With Domain

Domain route grouping allows you to apply routes only for specific domains. This feature is useful for multi-tenant applications or subdomain routing.

**Basic domain routing:**

```php
Route::domain('admin.example.com', function () {
    Route::get('/', function () {
        return 'Admin Dashboard';
    });

    Route::get('users', function () {
        return 'Admin Users';
    });
});
```

**Domain with wildcard parameter:**

```php
Route::domain('{account}.example.com', function () {
    Route::get('/', function ($account) {
        return 'Welcome to ' . $account;
    });

    Route::get('dashboard', function ($account) {
        return 'Dashboard for ' . $account;
    });
});
```

**Combination of domain with other attributes:**

```php
Route::domain('api.example.com', function () {
    Route::group(['prefix' => 'v1', 'before' => 'api.auth'], function () {
        Route::get('users', 'api.users@index');
        Route::get('posts', 'api.posts@index');
    });
});
```

**Multi-domain routing:**

```php
// Main domain
Route::domain('example.com', function () {
    Route::get('/', 'home@index');
    Route::get('about', 'home@about');
});

// Admin subdomain
Route::domain('admin.example.com', function () {
    Route::group(['before' => 'auth|admin'], function () {
        Route::get('/', 'admin.dashboard@index');
        Route::get('users', 'admin.users@index');
    });
});

// API subdomain
Route::domain('api.example.com', function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::get('users', 'api.v1.users@index');
        Route::post('users', 'api.v1.users@store');
    });
});
```

**Domain with named routes:**

```php
Route::domain('blog.example.com', function () {
    Route::get('/', ['as' => 'blog.home', function () {
        return View::make('blog.home');
    }]);

    Route::get('post/{id}', ['as' => 'blog.post', function ($id) {
        return View::make('blog.post', ['id' => $id]);
    }]);
});

// Generate URL for named route in domain
$url = URL::to_route('blog.home'); // http://blog.example.com/
```

<a id="middleware"></a>
## Middleware

Middleware provides a mechanism for filtering HTTP requests.

<a id="defining-middleware"></a>
### Defining Middleware

Middleware is defined in `application/middlewares.php`:

```php
Route::middleware('auth', function () {
    if (!Auth::check()) {
        return Redirect::to('login');
    }
});

Route::middleware('admin', function () {
    if (!Auth::user()->is_admin) {
        return Response::error('403');
    }
});

Route::middleware('guest', function () {
    if (Auth::check()) {
        return Redirect::to('dashboard');
    }
});
```

<a id="middleware-on-route"></a>
### Middleware On Route

**Single middleware:**

```php
Route::get('dashboard', ['before' => 'auth', function () {
    return View::make('dashboard');
}]);
```

**Multiple middleware:**

```php
Route::get('admin/settings', [
    'before' => 'auth|admin',
    function () {
        return View::make('admin.settings');
    }
]);
```

<a id="middleware-before--after"></a>
### Middleware Before & After

**Before middleware** (executed before route):

```php
Route::get('form', ['before' => 'csrf', function () {
    return View::make('form');
}]);
```

**After middleware** (executed after route):

```php
Route::get('download', [
    'after' => 'log_download',
    function () {
        return Response::download('file.pdf');
    }
]);
```

**Combination of before and after:**

```php
Route::get('api/data', [
    'before' => 'auth|throttle',
    'after' => 'log_api_call',
    function () {
        return ['data' => 'value'];
    }
]);
```

<a id="middleware-with-parameters"></a>
### Middleware With Parameters

**Defining middleware with parameters:**

```php
Route::middleware('role', function ($role) {
    if (!Auth::user()->has_role($role)) {
        return Response::error('403');
    }
});

Route::middleware('throttle', function ($limit) {
    $key = 'throttle:' . Request::ip();
    $attempts = Cache::get($key, 0);

    if ($attempts >= $limit) {
        return Response::make('Too many requests', 429);
    }

    Cache::put($key, $attempts + 1, 60);
});
```

**Using middleware with parameters:**

```php
Route::get('admin', ['before' => 'role:admin', function () {
    return 'Admin Panel';
}]);

Route::get('api/data', ['before' => 'throttle:100', function () {
    return ['data' => 'value'];
}]);

Route::get('moderator', ['before' => 'role:moderator|admin', function () {
    return 'Moderator Panel';
}]);
```

<a id="middleware-pattern"></a>
### Middleware Pattern

Apply middleware to all routes that match pattern:

```php
// All routes starting with 'admin/*'
Route::middleware('pattern: admin/*', 'auth');

// Multiple patterns
Route::middleware('pattern: admin/*', 'auth|admin');

// With closure
Route::middleware('pattern: api/*', ['name' => 'api_auth', function () {
    if (!Request::header('Authorization')) {
        return Response::json(['error' => 'Unauthorized'], 401);
    }
}]);
```

**Example:**

```php
// In middlewares.php
Route::middleware('pattern: admin/*', 'auth|admin');
Route::middleware('pattern: api/*', 'throttle:100');
Route::middleware('pattern: user/*', 'auth');

// All these routes automatically get middleware
Route::get('admin/dashboard', function () {
    // Middleware: auth, admin
});

Route::get('api/users', function () {
    // Middleware: throttle:100
});

Route::get('user/profile', function () {
    // Middleware: auth
});
```

<a id="global-middleware"></a>
### Global Middleware

Global middleware is executed for all requests.

**Defining in `application/middlewares.php`:**

```php
// Global before middleware
Route::middleware('before', function () {
    // Maintenance mode check
    if (Config::get('application.maintenance') && !Auth::user()->is_admin) {
        return Response::error('503');
    }
});

// Global after middleware
Route::middleware('after', function () {
    // Log all requests
    Log::info('Request: ' . Request::uri());
});
```

<a id="controller-routing"></a>
## Controller Routing

<a id="basic-controller-route"></a>
### Basic Controller Route

```php
// Format: controller@method
Route::get('users', 'user@index');
Route::get('users/(:num)', 'user@show');
Route::post('users', 'user@store');
Route::put('users/(:num)', 'user@update');
Route::delete('users/(:num)', 'user@destroy');
```

**With namespace:**

```php
// Controller in subfolder
Route::get('admin/users', 'admin.user@index');
Route::get('api/v1/users', 'api.v1.user@index');
```

<a id="route-to-specific-action"></a>
### Route to Specific Action

```php
Route::get('login', [
    'uses' => 'auth@login',
    'as' => 'login'
]);

Route::post('login', [
    'uses' => 'auth@authenticate',
    'before' => 'csrf'
]);

Route::get('register', [
    'uses' => 'auth@register',
    'as' => 'register',
    'before' => 'guest'
]);
```

<a id="restful-controller"></a>
### RESTful Controller

Automatically route to RESTful controller methods:

```php
Route::controller('users');
```

**Request routing:**
- `GET /users` → `user@get_index()`
- `POST /users` → `user@post_index()`
- `GET /users/123` → `user@get_show(123)`
- `PUT /users/123` → `user@put_update(123)`
- `DELETE /users/123` → `user@delete_destroy(123)`

**Multiple controllers:**

```php
Route::controller([
    'users',
    'posts',
    'comments'
]);
```

<a id="resource-controller"></a>
### Resource Controller

Generate all routes for resource CRUD:

```php
Route::resource('posts');
```

**Generated routes:**

| Method | URI | Controller Action | Route Name |
|--------|-----|-------------------|------------|
| GET | `/posts` | `post@index` | `posts.index` |
| GET | `/posts/create` | `post@create` | `posts.create` |
| POST | `/posts` | `post@store` | `posts.store` |
| GET | `/posts/(:num)` | `post@show` | `posts.show` |
| GET | `/posts/(:num)/edit` | `post@edit` | `posts.edit` |
| PUT | `/posts/(:num)` | `post@update` | `posts.update` |
| DELETE | `/posts/(:num)` | `post@destroy` | `posts.destroy` |

**With options:**

```php
// Only specific methods
Route::resource('posts', ['only' => ['index', 'show']]);

// Exclude specific methods
Route::resource('posts', ['except' => ['destroy']]);

// Custom controller
Route::resource('posts', ['controller' => 'blog.post']);
```

<a id="route-for-package"></a>
## Route for Package

Routes for packages are defined in `packages/{package}/routes.php`.

**Package configuration in `application/packages.php`:**

```php
return [
    'admin' => ['handles' => 'admin'],
    'api' => ['handles' => 'api/v1'],
];
```

**Route in `packages/admin/routes.php`:**

```php
// URL: /admin
Route::get('(:package)', function () {
    return 'Admin Dashboard';
});

// URL: /admin/users
Route::get('(:package)/users', function () {
    return 'Admin Users';
});

// URL: /admin/settings
Route::get('(:package)/settings', 'admin.settings@index');
```

The `(:package)` placeholder is automatically replaced with the value from `handles`.

<a id="route-helpers"></a>
## Route Helpers

### Route::share()

Register the same action for multiple routes:

```php
$handler = function () {
    return View::make('home');
};

Route::share([
    ['GET', '/'],
    ['GET', '/home'],
    ['GET', '/index']
], $handler);

// Or with controller
Route::share([
    ['GET', 'admin/dashboard'],
    ['POST', 'admin/dashboard']
], 'admin@dashboard');
```

### Route::forward()

Forward request to another URI internally (no redirect):

```php
Route::get('old-page', function () {
    // Forward to another route
    return Route::forward('GET', 'new-page');
});

Route::get('new-page', function () {
    return 'This is the new page';
});

// Forward with different method
Route::post('legacy/api', function () {
    return Route::forward('POST', 'api/v2/endpoint');
});
```

### Route::middleware()

Register middleware handler directly (alternative to `middlewares.php` file):

```php
Route::middleware('custom_auth', function () {
    if (!Session::has('user_id')) {
        return Redirect::to('login');
    }
});

// Use middleware in route
Route::get('dashboard', ['before' => 'custom_auth', function () {
    return View::make('dashboard');
}]);
```

### Other HTTP Methods

Besides the common GET, POST, PUT, PATCH, DELETE, Rakit also supports other HTTP methods:

**HEAD Request:**

```php
Route::head('api/status', function () {
    return Response::make('', 200);
});
```

**OPTIONS Request (for CORS):**

```php
Route::options('api/users', function () {
    return Response::make('', 200)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
});
```

**TRACE Request:**

```php
Route::trace('debug/trace', function () {
    return 'TRACE request received';
});
```

**CONNECT Request:**

```php
Route::connect('proxy', function () {
    return 'CONNECT request received';
});
```

<a id="404-handler"></a>
## 404 Handler

Handler for not found routes is defined in `application/events.php`:

```php
Event::listen('404', function () {
    return Response::error('404');
});
```

**Custom 404 handler:**

```php
Event::listen('404', function () {
    return View::make('errors.404');
});

// Or with custom logic
Event::listen('404', function () {
    $uri = Request::uri();

    // Log 404
    Log::warning('404: ' . $uri);

    // Find similar page
    $similar = Page::where('slug', 'like', "%$uri%")->first();

    if ($similar) {
        return Redirect::to($similar->slug);
    }

    return Response::error('404');
});
```

<a id="route-listing"></a>
## Route Listing

**Get all routes:**

```php
$routes = Route::lists();

foreach ($routes as $method => $routes) {
    echo "$method:\n";
    foreach ($routes as $route) {
        echo "  " . $route['uri'] . "\n";
    }
}
```

**Get routes for specific method:**

```php
$get_routes = Router::method('GET');
$post_routes = Router::method('POST');
```

**Check if named route exists:**

```php
if (Route::has('profile')) {
    echo 'Route "profile" exists';
}
```

<a id="route-testing-via-cli"></a>
## Route Testing via CLI

Test route from command line:

```bash
# Test GET request
php rakit route:call "GET /"
php rakit route:call "GET /users"
php rakit route:call "GET /user/123"

# Test POST request
php rakit route:call "POST /users"

# Test with data
php rakit route:call "POST /login" --data='{"email":"user@test.com","password":"secret"}'
```

## Complete Example

**File: `application/routes.php`**

```php
// Home
Route::get('/', ['as' => 'home', function () {
    return View::make('home');
}]);

// Public pages
Route::view('about', 'about', ['title' => 'About Us']);
Route::view('contact', 'contact');

// Auth routes
Route::group(['before' => 'guest'], function () {
    Route::get('login', ['as' => 'login', 'uses' => 'auth@login']);
    Route::post('login', ['uses' => 'auth@authenticate', 'before' => 'csrf']);
    Route::get('register', ['as' => 'register', 'uses' => 'auth@register']);
    Route::post('register', ['uses' => 'auth@store', 'before' => 'csrf']);
});

Route::get('logout', ['as' => 'logout', 'uses' => 'auth@logout', 'before' => 'auth']);

// User area
Route::group(['prefix' => 'user', 'before' => 'auth'], function () {
    Route::get('dashboard', ['as' => 'user.dashboard', function () {
        return View::make('user.dashboard');
    }]);

    Route::get('profile', ['as' => 'user.profile', 'uses' => 'user@profile']);
    Route::put('profile', ['uses' => 'user@update_profile']);

    Route::get('settings', ['as' => 'user.settings', 'uses' => 'user@settings']);
    Route::post('settings', ['uses' => 'user@update_settings']);
});

// Admin area
Route::group(['prefix' => 'admin', 'before' => 'auth|admin'], function () {
    Route::get('/', ['as' => 'admin.dashboard', function () {
        return View::make('admin.dashboard');
    }]);

    // Resource routes
    Route::resource('users', ['controller' => 'admin.user']);
    Route::resource('posts', ['controller' => 'admin.post']);
    Route::resource('categories', ['controller' => 'admin.category']);
});

// API routes
Route::group(['prefix' => 'api', 'before' => 'throttle:100'], function () {
    Route::get('users', 'api.user@index');
    Route::get('users/(:num)', 'api.user@show');
    Route::post('users', ['before' => 'auth', 'uses' => 'api.user@store']);

    Route::get('posts', 'api.post@index');
    Route::get('posts/(:num)', 'api.post@show');
});

// Catch-all for blog posts
Route::get('blog/(:any)', function ($slug) {
    $post = Post::where('slug', $slug)->first_or_fail();
    return View::make('blog.show', compact('post'));
});
```

**File: `application/middlewares.php`**

```php
// CSRF Protection
Route::middleware('csrf', function () {
    if (Request::forged()) {
        return Response::error('403');
    }
});

// Authentication
Route::middleware('auth', function () {
    if (!Auth::check()) {
        return Redirect::to_route('login');
    }
});

// Guest only
Route::middleware('guest', function () {
    if (Auth::check()) {
        return Redirect::to_route('user.dashboard');
    }
});

// Admin check
Route::middleware('admin', function () {
    if (!Auth::user() || !Auth::user()->is_admin) {
        return Response::error('403');
    }
});

// Role check with parameter
Route::middleware('role', function ($role) {
    if (!Auth::user() || !Auth::user()->has_role($role)) {
        return Response::error('403');
    }
});

// Throttle requests
Route::middleware('throttle', function ($limit = 60) {
    $key = 'throttle:' . Request::ip();
    $attempts = Cache::get($key, 0);

    if ($attempts >= $limit) {
        return Response::make('Too many requests', 429);
    }

    Cache::put($key, $attempts + 1, 60);
});

// Pattern-based middleware
Route::middleware('pattern: admin/*', 'auth|admin');
Route::middleware('pattern: api/*', 'throttle:100');

// Global before middleware
Route::middleware('before', function () {
    // Maintenance mode
    if (Config::get('application.down') && !Auth::user()->is_admin) {
        return Response::error('503');
    }
});

// Global after middleware
Route::middleware('after', function ($response) {
    // Add custom headers
    $response->header('X-Frame-Options', 'SAMEORIGIN');
});
```
