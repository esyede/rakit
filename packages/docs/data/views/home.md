# View & Response

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating View](#creating-view)
  - [Basic View](#basic-view)
  - [Check View Exists](#check-view-exists)
  - [View Locations](#view-locations)
- [Passing Data to View](#passing-data-to-view)
  - [With Method](#with-method)
  - [Array Data](#array-data)
  - [Compact](#compact)
  - [Magic Method](#magic-method)
  - [Constructor Data](#constructor-data)
- [Nested Views](#nested-views)
- [Named Views](#named-views)
- [View Composers](#view-composers)
  - [Defining Composer](#defining-composer)
  - [Multiple Views](#multiple-views)
  - [Use Case](#use-case)
- [Sharing Data](#sharing-data)
  - [Share to All Views](#share-to-all-views)
  - [Share in Service Provider](#share-in-service-provider)
- [View Rendering](#view-rendering)
  - [Render Each](#render-each)
  - [Get Rendered Content](#get-rendered-content)
- [Response Types](#response-types)
  - [String Response](#string-response)
  - [View Response](#view-response)
  - [JSON Response](#json-response)
  - [JSONP Response](#jsonp-response)
  - [Model Response](#model-response)
  - [Download Response](#download-response)
  - [Error Response](#error-response)
- [Response Headers](#response-headers)
  - [Set Headers](#set-headers)
  - [Content Type](#content-type)
- [Response Status Code](#response-status-code)
- [Redirect Responses](#redirect-responses)
  - [Basic Redirect](#basic-redirect)
  - [Redirect to Named Route](#redirect-to-named-route)
  - [Redirect Back](#redirect-back)
  - [Redirect with Flash Data](#redirect-with-flash-data)
  - [Redirect with Input](#redirect-with-input)
- [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Views are the presentation layer in the application that contains HTML displayed to the user. Views separate business logic from display,
making code more organized and maintainable.

**Advantages of using Views:**
- Separation of concerns
- Code reusability
- Easier maintenance
- Template inheritance (Blade)
- Easy data binding

Views are stored in the `application/views/` folder with `.php` or `.blade.php` extension (for Blade template).

<a id="creating-view"></a>
## Creating View

<a id="basic-view"></a>
### Basic View

**File: `application/views/home.php`**

```php
<!DOCTYPE html>
<html>
<head>
    <title>Home Page</title>
</head>
<body>
    <h1>Welcome to Rakit!</h1>
</body>
</html>
```

**Return view from route:**

```php
Route::get('/', function () {
    return View::make('home');
});
```

**Return view from controller:**

```php
class Home_Controller extends Controller
{
    public function action_index()
    {
        return View::make('home');
    }
}
```

<a id="check-view-exists"></a>
### Check View Exists

**Check if view exists:**

```php
if (View::exists('home')) {
    return View::make('home');
}

return View::make('default');
```

**Get view path:**

```php
$path = View::exists('home', true);
// /path/to/application/views/home.php
```

<a id="view-locations"></a>
### View Locations

**Subfolder views:**

```php
// File: application/views/admin/dashboard.php
return View::make('admin.dashboard');

// File: application/views/user/profile/edit.php
return View::make('user.profile.edit');
```

**Package views:**

```php
// File: packages/admin/views/dashboard.php
return View::make('admin::dashboard');
```

<a id="passing-data-to-view"></a>
## Passing Data to View

<a id="with-method"></a>
### With Method

**Single data:**

```php
Route::get('user/{id}', function ($id) {
    $user = User::find($id);

    return View::make('user.profile')->with('user', $user);
});
```

**Multiple data with chaining:**

```php
return View::make('user.profile')
    ->with('user', $user)
    ->with('posts', $posts)
    ->with('comments', $comments);
```

<a id="array-data"></a>
### Array Data

**Pass array to constructor:**

```php
$data = [
    'user' => $user,
    'posts' => $posts,
    'comments' => $comments,
];

return View::make('user.profile', $data);
```

<a id="compact"></a>
### Compact

**Using compact():**

```php
$user = User::find($id);
$posts = $user->posts;
$comments = $user->comments;

return View::make('user.profile', compact('user', 'posts', 'comments'));
```

<a id="magic-method"></a>
### Magic Method

**Using property magic:**

```php
$view = View::make('user.profile');
$view->user = User::find($id);
$view->posts = Post::all();

return $view;
```

<a id="constructor-data"></a>
### Constructor Data

**Pass data directly:**

```php
return View::make('home', ['title' => 'Home Page', 'active' => 'home']);
```

**In view:**

```php
<title><?php echo $title; ?></title>
<body class="<?php echo $active; ?>">
    <!-- content -->
</body>
```

<a id="nested-views"></a>
## Nested Views

**Include view inside another view:**

**File: `application/views/layouts/master.php`**

```php
<!DOCTYPE html>
<html>
<head>
    <title><?php echo $title; ?></title>
</head>
<body>
    <header>
        <?php echo View::make('partials.header'); ?>
    </header>

    <main>
        <?php echo $content; ?>
    </main>

    <footer>
        <?php echo View::make('partials.footer'); ?>
    </footer>
</body>
</html>
```

**Using nested view:**

```php
$content = View::make('home.index');

return View::make('layouts.master')
    ->with('title', 'Home Page')
    ->with('content', $content);
```

**Pass data to nested view:**

```php
$header = View::make('partials.header', ['user' => Auth::user()]);
$content = View::make('home.index', ['posts' => $posts]);

return View::make('layouts.master', compact('header', 'content'));
```

<a id="named-views"></a>
## Named Views

**Register named view:**

```php
// In boot.php or composer
View::name('layouts.master', 'layout.master');
View::name('user.profile', 'profile');
```

**Using named view:**

```php
// Instead of View::make('layouts.master')
return View::of('layout.master', $data);

// Instead of View::make('user.profile')
return View::of('profile', compact('user'));
```

<a id="view-composers"></a>
## View Composers

View composers are callbacks or class methods called when a view is rendered. Useful for injecting data into views automatically.

<a id="defining-composer"></a>
### Defining Composer

**In `application/start.php` or `application/routes.php`:**

```php
// Composer for single view
View::composer('user.profile', function ($view) {
    $view->with('posts_count', Post::count());
});

// Composer with data from model
View::composer('layouts.master', function ($view) {
    $view->with('current_user', Auth::user());
    $view->with('notifications', Notification::recent());
});
```

> View composers are defined in `application/start.php` or at the beginning of `application/routes.php`, not in a separate `composers.php` file.

<a id="multiple-views"></a>
### Multiple Views

**Composer for multiple views:**

```php
View::composer(['user.profile', 'user.settings'], function ($view) {
    $view->with('user', Auth::user());
});

// Composer for all views in folder
View::composer('admin.*', function ($view) {
    $view->with('admin_user', Auth::user());
    $view->with('menu', Menu::admin());
});
```

<a id="use-case"></a>
### Use Case

**Sidebar with dynamic data:**

```php
View::composer('partials.sidebar', function ($view) {
    $view->with('categories', Category::all());
    $view->with('popular_posts', Post::order_by('views', 'desc')->take(5)->get());
    $view->with('tags', Tag::all());
});
```

**Navigation with active state:**

```php
View::composer('partials.navigation', function ($view) {
    $current_route = URI::current();
    $view->with('current_route', $current_route);
    $view->with('menu_items', Menu::all());
});
```

<a id="sharing-data"></a>
## Sharing Data

<a id="share-to-all-views"></a>
### Share to All Views

**Share data available in all views:**

```php
// In application/start.php
View::share('app_name', Config::get('application.name'));
View::share('app_url', Config::get('application.url'));
```

**Using in view:**

```php
<title><?php echo $app_name; ?></title>
<link rel="canonical" href="<?php echo $app_url; ?>">
```

<a id="share-in-service-provider"></a>
### Share in Service Provider

**In base controller:**

```php
class Base_Controller extends Controller
{
    public function __construct()
    {
        // Share to all views
        View::share('current_user', Auth::user());
        View::share('csrf_token', Session::token());
        View::share('flash_message', Session::get('message'));
    }
}
```

<a id="view-rendering"></a>
## View Rendering

<a id="render-each"></a>
### Render Each

**Render view for each item in array:**

```php
$users = User::all();

$html = View::render_each('user.item', $users, 'user');
```

**File: `application/views/user/item.php`**

```php
<div class="user-item">
    <h3><?php echo $user->name; ?></h3>
    <p><?php echo $user->email; ?></p>
</div>
```

**With empty view:**

```php
$html = View::render_each('user.item', $users, 'user', 'user.empty');
```

**File: `application/views/user/empty.php`**

```php
<p>No users found.</p>
```

<a id="get-rendered-content"></a>
### Get Rendered Content

**Get view as string:**

```php
$view = View::make('email.welcome', ['user' => $user]);
$html = $view->render();

// Send via email
Email::send($user->email, 'Welcome!', $html);
```

<a id="response-types"></a>
## Response Types

<a id="string-response"></a>
### String Response

**Basic string response:**

```php
Route::get('/', function () {
    return 'Hello World!';
});

// With Response::make()
Route::get('/', function () {
    return Response::make('Hello World!');
});
```

<a id="view-response"></a>
### View Response

**Response with view:**

```php
Route::get('/', function () {
    return Response::view('home', ['title' => 'Home']);
});

// Same as
Route::get('/', function () {
    return View::make('home', ['title' => 'Home']);
});
```

**With status code and headers:**

```php
return Response::view('home', $data, 200, [
    'Cache-Control' => 'max-age=3600',
]);
```

<a id="json-response"></a>
### JSON Response

**JSON response:**

```php
Route::get('api/users', function () {
    $users = User::all();
    return Response::json($users);
});

// With status code
Route::post('api/users', function () {
    $user = User::create(Input::all());
    return Response::json($user, 201);
});

// With custom headers
return Response::json($data, 200, [
    'X-Custom-Header' => 'value',
]);
```

**JSON options:**

```php
// Pretty print
return Response::json($data, 200, [], JSON_PRETTY_PRINT);

// Unescaped unicode
return Response::json($data, 200, [], JSON_UNESCAPED_UNICODE);
```

<a id="jsonp-response"></a>
### JSONP Response

**JSONP response for cross-domain requests:**

```php
Route::get('api/users', function () {
    $users = User::all();
    $callback = Input::get('callback', 'callback');

    return Response::jsonp($callback, $users);
});

// Output: callback([{"id":1,"name":"Budi"},...])
```

**With status code and headers:**

```php
return Response::jsonp('myCallback', $data, 200, [
    'X-Custom-Header' => 'value',
]);
```

<a id="model-response"></a>
### Model Response

**Response model as JSON:**

```php
Route::get('api/users/{id}', function ($id) {
    $user = User::find($id);

    if (!$user) {
        return Response::json(['error' => 'User not found'], 404);
    }

    return Response::facile($user);
});

// Multiple models
Route::get('api/users', function () {
    $users = User::all();
    return Response::facile($users);
});
```

<a id="download-response"></a>
### Download Response

**Force download file:**

```php
Route::get('download/{file}', function ($file) {
    $path = path('storage') . 'downloads/' . $file;

    return Response::download($path);
});

// With custom filename
Route::get('invoice/{id}', function ($id) {
    $invoice = Invoice::find($id);
    $path = $invoice->pdf_path;

    return Response::download($path, 'invoice-' . $id . '.pdf');
});

// With headers
return Response::download($path, $name, [
    'Content-Type' => 'application/pdf',
]);
```

<a id="error-response"></a>
### Error Response

**Response error page:**

```php
// 404 Not Found
Route::get('missing', function () {
    return Response::error(404);
});

// 403 Forbidden
Route::get('forbidden', function () {
    return Response::error(403);
});

// 500 Internal Server Error
Route::get('error', function () {
    return Response::error(500);
});
```

**With custom headers:**

```php
// 429 Too Many Requests with Retry-After header
return Response::error(429, ['Retry-After' => 3600]);
```

**Custom error view:**

Create view in `application/views/error/404.php`:

```php
<!DOCTYPE html>
<html>
<head>
    <title>404 - Page Not Found</title>
</head>
<body>
    <h1>Page Not Found</h1>
    <p>The page you are looking for could not be found.</p>
</body>
</html>
```

**Response for JSON request:**

If the request expects JSON (with `Accept: application/json` header), Response::error() will automatically return JSON:

```php
// Automatically returns JSON if request wants JSON
return Response::error(404);

// Output: {"status": 404, "message": "Not Found"}
```

<a id="response-headers"></a>
## Response Headers

<a id="set-headers"></a>
### Set Headers

**Set response headers:**

```php
$response = Response::make('content', 200, [
    'Cache-Control' => 'no-cache, no-store, must-revalidate',
    'Pragma' => 'no-cache',
    'Expires' => '0',
]);

return $response;
```

**Add header after create:**

```php
$response = Response::make('content');
$response->header('X-Custom-Header', 'value');
$response->header('X-Request-ID', uniqid());

return $response;
```

<a id="content-type"></a>
### Content Type

**Set content type:**

```php
// XML
return Response::make($xml, 200, [
    'Content-Type' => 'application/xml',
]);

// Plain text
return Response::make($text, 200, [
    'Content-Type' => 'text/plain',
]);

// CSV
return Response::make($csv, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="export.csv"',
]);
```

<a id="response-status-code"></a>
## Response Status Code

**Common status codes:**

```php
// 200 OK
return Response::make('Success', 200);

// 201 Created
return Response::json($user, 201);

// 204 No Content
return Response::make('', 204);

// 301 Moved Permanently
return Response::make('', 301, ['Location' => $new_url]);

// 302 Found (Redirect)
return Redirect::to('home');

// 400 Bad Request
return Response::json(['error' => 'Bad Request'], 400);

// 401 Unauthorized
return Response::json(['error' => 'Unauthorized'], 401);

// 403 Forbidden
return Response::error('403');

// 404 Not Found
return Response::error('404');

// 422 Unprocessable Entity
return Response::json(['errors' => $validation->errors], 422);

// 500 Internal Server Error
return Response::error('500');
```

<a id="redirect-responses"></a>
## Redirect Responses

<a id="basic-redirect"></a>
### Basic Redirect

**Redirect to URL:**

```php
return Redirect::to('home');
return Redirect::to('user/profile');
return Redirect::to('https://example.com');
```

**Redirect with status code:**

```php
// 301 Permanent
return Redirect::to('new-url', 301);

// 302 Temporary (default)
return Redirect::to('new-url', 302);
```

<a id="redirect-to-named-route"></a>
### Redirect to Named Route

**Redirect to named route:**

```php
return Redirect::to_route('home');
return Redirect::to_route('user.profile', [$user_id]);
```

<a id="redirect-back"></a>
### Redirect Back

**Redirect to previous page:**

```php
return Redirect::back();

// With status code
return Redirect::back(301);
```

<a id="redirect-with-flash-data"></a>
### Redirect with Flash Data

**Flash message to session:**

```php
return Redirect::to('home')
    ->with('message', 'Login successful!');

// Multiple flash data
return Redirect::to('dashboard')
    ->with('message', 'Welcome back!')
    ->with('user', $user);
```

**In view:**

```php
<?php if (Session::has('message')): ?>
    <div class="alert alert-success">
        <?php echo Session::get('message'); ?>
    </div>
<?php endif; ?>
```

<a id="redirect-with-input"></a>
### Redirect with Input

**Flash input to repopulate form:**

```php
// Validation failed, flash input
return Redirect::back()
    ->with_input()
    ->with_errors($validation);

// Flash only certain fields
return Redirect::back()
    ->with_input('only', ['name', 'email']);

// Flash except certain fields
return Redirect::back()
    ->with_input('except', ['password']);
```

<a id="practical-examples"></a>
## Practical Examples

**Controller with various response types:**

```php
class Post_Controller extends Controller
{
    // HTML View
    public function action_index()
    {
        $posts = Post::with('author')->paginate(15);

        return View::make('posts.index', compact('posts'));
    }

    // JSON API
    public function action_api_index()
    {
        $posts = Post::with('author')->get();

        return Response::json($posts);
    }

    // Single post
    public function action_show($id)
    {
        $post = Post::with('author', 'comments')->find($id);

        if (!$post) {
            return Response::error(404);
        }

        return View::make('posts.show', compact('post'));
    }

    // Create post
    public function action_create()
    {
        return View::make('posts.create');
    }

    // Store post
    public function action_store()
    {
        $validation = Validator::make(Input::all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validation->fails()) {
            return Redirect::back()
                ->with_input()
                ->with_errors($validation);
        }

        $post = Post::create(Input::all());

        return Redirect::to_route('posts.show', [$post->id])
            ->with('message', 'Post created successfully!');
    }

    // Download attachment
    public function action_download($id)
    {
        $post = Post::find($id);
        $path = $post->attachment_path;

        return Response::download($path, $post->attachment_name);
    }
}
```
