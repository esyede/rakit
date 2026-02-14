# Controller

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Creating Controllers](#creating-controllers)
  - [Basic Controller](#basic-controller)
  - [Controller Action](#controller-action)
  - [Naming Convention](#naming-convention)
- [Controller Routing](#controller-routing)
  - [Manual Routing](#manual-routing)
  - [Auto Routing](#auto-routing)
  - [Route to Specific Action](#route-to-specific-action)
- [RESTful Controller](#restful-controller)
  - [Creating RESTful Controller](#creating-restful-controller)
  - [RESTful Action Convention](#restful-action-convention)
  - [RESTful Routing](#restful-routing)
- [Resource Controller](#resource-controller)
  - [Resource Actions](#resource-actions)
  - [Resource Routing](#resource-routing)
- [Controller Middleware](#controller-middleware)
  - [Before Middleware](#before-middleware)
  - [After Middleware](#after-middleware)
  - [Middleware for Specific Action](#middleware-for-specific-action)
  - [Middleware with Parameter](#middleware-with-parameter)
- [Nested Controller](#nested-controller)
- [Layout Controller](#layout-controller)
  - [Defining Layout](#defining-layout)
  - [Using Layout](#using-layout)
  - [Nested Layout](#nested-layout)
- [Dependency Injection](#dependency-injection)
- [Controller Factory](#controller-factory)
- [Package Controller](#package-controller)
- [Complete Example](#complete-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

Controllers are classes responsible for receiving HTTP requests and managing interactions between Models, Libraries, and Views. Controllers provide a more organized structure compared to using Closures in routes.

**Advantages of using Controllers:**
- Better code organization
- Reusable logic
- Dependency injection support
- Middleware support per action
- Easier to test

Controllers are stored in the `application/controllers/` folder and must extend the `Controller` class.

<a id="creating-controllers"></a>
## Creating Controllers

<a id="basic-controller"></a>
### Basic Controller

The simplest controller:

```php
class Home_Controller extends Controller
{
    public function action_index()
    {
        return View::make('home.index');
    }
}
```

**File location:** `application/controllers/home.php`

<a id="controller-action"></a>
### Controller Action

Actions are methods in the controller that can be accessed from the web. Actions must be prefixed with `action_`:

```php
class User_Controller extends Controller
{
    // URL: /user
    public function action_index()
    {
        return 'User list';
    }

    // URL: /user/profile
    public function action_profile()
    {
        return 'User profile';
    }

    // URL: /user/settings
    public function action_settings()
    {
        return View::make('user.settings');
    }

    // This method CANNOT be accessed from the web (no action_ prefix)
    protected function helper_method()
    {
        return 'This is a helper method';
    }
}
```

**Action with parameters:**

```php
class Post_Controller extends Controller
{
    // URL: /post/show/123
    public function action_show($id)
    {
        $post = Post::find($id);
        return View::make('post.show', compact('post'));
    }

    // URL: /post/edit/123
    public function action_edit($id)
    {
        $post = Post::find($id);
        return View::make('post.edit', compact('post'));
    }

    // URL: /post/category/technology/5
    public function action_category($category, $page = 1)
    {
        $posts = Post::where('category', $category)
            ->paginate(10);

        return View::make('post.category', compact('posts', 'category'));
    }
}
```

<a id="naming-convention"></a>
### Naming Convention

**Filename:** lowercase with underscores
- `home.php`
- `user.php`
- `admin_user.php`

**Classname:** PascalCase with `_Controller` suffix
- `Home_Controller`
- `User_Controller`
- `Admin_User_Controller`

**Action:** `action_` prefix with lowercase and underscores
- `action_index()`
- `action_show()`
- `action_edit_profile()`

<a id="controller-routing"></a>
## Controller Routing

<a id="manual-routing"></a>
### Manual Routing

The most explicit way to route controllers:

```php
// Format: controller@action
Route::get('/', 'home@index');
Route::get('about', 'home@about');
Route::get('contact', 'home@contact');

Route::get('user/profile', 'user@profile');
Route::get('user/settings', 'user@settings');
Route::post('user/update', 'user@update');

Route::get('post/(:num)', 'post@show');
Route::get('post/(:any)', 'post@slug');
```

**With array syntax:**

```php
Route::get('admin/dashboard', [
    'uses' => 'admin@dashboard',
    'as' => 'admin.dashboard',
    'before' => 'auth|admin'
]);
```

<a id="auto-routing"></a>
### Auto Routing

Register all actions in a controller:

```php
// Register single controller
Route::controller('user');

// Register multiple controllers
Route::controller(['user', 'post', 'comment']);

// Auto-detect and register all controllers
Route::controller(Controller::detect());
```

**URL Convention with auto routing:**
```bash
/{controller}/{action}/{param1}/{param2}/...
```

**Examples:**
- `/user` → `User_Controller::action_index()`
- `/user/profile` → `User_Controller::action_profile()`
- `/user/edit/123` → `User_Controller::action_edit(123)`
- `/post/show/5` → `Post_Controller::action_show(5)`

<a id="route-to-specific-action"></a>
### Route to Specific Action

```php
Route::get('login', 'auth@login');
Route::post('login', 'auth@authenticate');
Route::get('logout', 'auth@logout');

Route::get('register', 'auth@register');
Route::post('register', 'auth@store');

Route::get('password/reset', 'password@reset');
Route::post('password/email', 'password@email');
```

<a id="restful-controller"></a>
## RESTful Controller

<a id="creating-restful-controller"></a>
### Creating RESTful Controller

Set the `$restful = true` property to use RESTful convention:

```php
class User_Controller extends Controller
{
    public $restful = true;

    // GET /user
    public function get_index()
    {
        $users = User::all();
        return View::make('user.index', compact('users'));
    }

    // POST /user
    public function post_index()
    {
        $user = User::create(Input::all());
        return Redirect::to('user');
    }

    // GET /user/123
    public function get_show($id)
    {
        $user = User::find($id);
        return View::make('user.show', compact('user'));
    }

    // PUT /user/123
    public function put_update($id)
    {
        $user = User::find($id);
        $user->update(Input::all());
        return Redirect::to('user/' . $id);
    }

    // DELETE /user/123
    public function delete_destroy($id)
    {
        User::delete($id);
        return Redirect::to('user');
    }
}
```

<a id="restful-action-convention"></a>
### RESTful Action Convention

Prefix methods with HTTP verbs:

| HTTP Method | Action Format | Example |
|-------------|---------------|---------|
| GET | `get_*()` | `get_index()`, `get_show()` |
| POST | `post_*()` | `post_create()`, `post_store()` |
| PUT | `put_*()` | `put_update()` |
| PATCH | `patch_*()` | `patch_update()` |
| DELETE | `delete_*()` | `delete_destroy()` |

<a id="restful-routing"></a>
### RESTful Routing

```php
// Register RESTful controller
Route::controller('user');
```

Request routing automatically:
- `GET /user` → `get_index()`
- `GET /user/create` → `get_create()`
- `POST /user` → `post_index()`
- `GET /user/123` → `get_show(123)`
- `GET /user/123/edit` → `get_edit(123)`
- `PUT /user/123` → `put_update(123)`
- `DELETE /user/123` → `delete_destroy(123)`

<a id="resource-controller"></a>
## Resource Controller

<a id="resource-actions"></a>
### Resource Actions

Resource controller for standard CRUD operations:

```php
class Post_Controller extends Controller
{
    // GET /posts
    public function action_index()
    {
        $posts = Post::paginate(15);
        return View::make('posts.index', compact('posts'));
    }

    // GET /posts/create
    public function action_create()
    {
        return View::make('posts.create');
    }

    // POST /posts
    public function action_store()
    {
        $post = Post::create(Input::all());
        return Redirect::to('posts/' . $post->id);
    }

    // GET /posts/123
    public function action_show($id)
    {
        $post = Post::find($id);
        return View::make('posts.show', compact('post'));
    }

    // GET /posts/123/edit
    public function action_edit($id)
    {
        $post = Post::find($id);
        return View::make('posts.edit', compact('post'));
    }

    // PUT /posts/123
    public function action_update($id)
    {
        $post = Post::find($id);
        $post->update(Input::all());
        return Redirect::to('posts/' . $id);
    }

    // DELETE /posts/123
    public function action_destroy($id)
    {
        Post::delete($id);
        return Redirect::to('posts');
    }
}
```

<a id="resource-routing"></a>
### Resource Routing

Register all resource routes:

```php
Route::resource('posts');
```

Generated routes:

| Method | URI | Action | Route Name |
|--------|-----|--------|------------|
| GET | `/posts` | `index` | `posts.index` |
| GET | `/posts/create` | `create` | `posts.create` |
| POST | `/posts` | `store` | `posts.store` |
| GET | `/posts/(:num)` | `show` | `posts.show` |
| GET | `/posts/(:num)/edit` | `edit` | `posts.edit` |
| PUT | `/posts/(:num)` | `update` | `posts.update` |
| DELETE | `/posts/(:num)` | `destroy` | `posts.destroy` |

**Resource with options:**

```php
// Only specific actions
Route::resource('posts', ['only' => ['index', 'show']]);

// Exclude specific actions
Route::resource('posts', ['except' => ['destroy']]);

// Custom controller location
Route::resource('posts', ['controller' => 'blog.post']);
```

<a id="controller-middleware"></a>
## Controller Middleware

<a id="before-middleware"></a>
### Before Middleware

Middleware executed before action:

```php
class Admin_Controller extends Controller
{
    public function __construct()
    {
        // Apply to all actions
        $this->middleware('before', 'auth');
    }

    public function action_dashboard()
    {
        return View::make('admin.dashboard');
    }

    public function action_users()
    {
        return View::make('admin.users');
    }
}
```

<a id="after-middleware"></a>
### After Middleware

Middleware executed after action:

```php
class Api_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'api_auth');
        $this->middleware('after', 'api_log');
    }

    public function action_index()
    {
        return ['status' => 'success'];
    }
}
```

<a id="middleware-for-specific-action"></a>
### Middleware for Specific Action

**Apply middleware only to specific actions:**

```php
class Post_Controller extends Controller
{
    public function __construct()
    {
        // Only for create and edit
        $this->middleware('before', 'auth')->only(['create', 'edit']);

        // Exclude show and index
        $this->middleware('before', 'admin')->except(['show', 'index']);
    }

    public function action_index()
    {
        return 'Public access';
    }

    public function action_create()
    {
        // Middleware: auth
        return View::make('post.create');
    }

    public function action_edit($id)
    {
        // Middleware: auth, admin
        return View::make('post.edit');
    }
}
```

**Specify HTTP method:**

```php
class Form_Controller extends Controller
{
    public function __construct()
    {
        // Only for POST requests
        $this->middleware('before', 'csrf')->on('post');

        // Only for GET requests on 'show' action
        $this->middleware('before', 'cache')->on('get', ['show']);
    }

    public function action_create()
    {
        // GET: no csrf
        // POST: with csrf
        return View::make('form.create');
    }

    public function action_show($id)
    {
        // GET: with cache
        return View::make('form.show');
    }
}
```

<a id="middleware-with-parameter"></a>
### Middleware with Parameter

```php
class Admin_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth');
        $this->middleware('before', 'role:admin');
    }

    public function action_index()
    {
        return View::make('admin.index');
    }
}
```

**Multiple middleware:**

```php
class Secure_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth|verified|active');
        $this->middleware('after', 'log_access');
    }
}
```

<a id="nested-controller"></a>
## Nested Controller

Controllers in subfolders for better organization:

**File structure:**
```
application/
  controllers/
    admin/
      user.php
      post.php
      settings.php
    api/
      v1/
        user.php
        post.php
```

**File: `application/controllers/admin/user.php`**

```php
class Admin_User_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth|admin');
    }

    public function action_index()
    {
        $users = User::paginate(20);
        return View::make('admin.user.index', compact('users'));
    }
}
```

**Routing:**

```php
// Manual
Route::get('admin/users', 'admin.user@index');

// Auto
Route::controller('admin.user');

// Detect
Route::controller(Controller::detect());
```

**URL:** `/admin/user` or `/admin/user/index`

<a id="layout-controller"></a>
## Layout Controller

<a id="defining-layout"></a>
### Defining Layout

```php
class Base_Controller extends Controller
{
    public $layout = 'layouts.master';

    public function layout()
    {
        if (Request::ajax()) {
            return null; // No layout for AJAX
        }

        return View::make($this->layout);
    }
}
```

<a id="using-layout"></a>
### Using Layout

```php
class Home_Controller extends Base_Controller
{
    public $layout = 'layouts.master';

    public function action_index()
    {
        $this->layout->title = 'Home Page';
        $this->layout->content = View::make('home.index');
    }

    public function action_about()
    {
        $this->layout->title = 'About Us';
        $this->layout->content = View::make('home.about');
    }
}
```

**File: `application/views/layouts/master.blade.php`**

```blade
<!DOCTYPE html>
<html>
<head>
    <title>{{ $title }}</title>
</head>
<body>
    <header>
        @include('partials.header')
    </header>

    <main>
        {{ $content }}
    </main>

    <footer>
        @include('partials.footer')
    </footer>
</body>
</html>
```

<a id="nested-layout"></a>
### Nested Layout

```php
class User_Controller extends Controller
{
    public $layout = 'layouts.user';

    public function action_dashboard()
    {
        $this->layout->title = 'Dashboard';
        $this->layout->sidebar = View::make('user.sidebar');
        $this->layout->content = View::make('user.dashboard');
    }
}
```

<a id="dependency-injection"></a>
## Dependency Injection

Controllers support dependency injection via constructor and methods:

**Constructor injection:**

```php
use System\Database\Database;
use System\Cache\Cache;

class User_Controller extends Controller
{
    protected $db;
    protected $cache;

    public function __construct(Database $db, Cache $cache)
    {
        $this->db = $db;
        $this->cache = $cache;

        $this->middleware('before', 'auth');
    }

    public function action_index()
    {
        $users = $this->cache->remember('users', 60, function () {
            return $this->db->table('users')->get();
        });

        return View::make('user.index', compact('users'));
    }
}
```

**Method injection:**

```php
class Post_Controller extends Controller
{
    public function action_show($id, Request $request, Cache $cache)
    {
        $post = $cache->remember('post.' . $id, 3600, function () use ($id) {
            return Post::find($id);
        });

        return View::make('post.show', compact('post'));
    }
}
```

<a id="controller-factory"></a>
## Controller Factory

Custom controller instantiation:

**Register factory in `application/boot.php`:**

```php
Event::listen('rakit.controller.factory', function ($controller) {
    // Custom logic to instantiate controller
    if ($controller === 'Admin_User_Controller') {
        return new $controller(new UserRepository());
    }

    return new $controller();
});
```

**Or with Container:**

```php
Container::singleton('controller: user', function () {
    return new User_Controller(new UserRepository());
});
```

<a id="package-controller"></a>
## Package Controller

Controllers for packages with package name prefix:

**File: `packages/admin/controllers/home.php`**

```php
class Admin_Home_Controller extends Controller
{
    public function action_index()
    {
        return View::make('admin::home.index');
    }

    public function action_dashboard()
    {
        return View::make('admin::dashboard');
    }
}
```

**Routing:**

```php
// In packages/admin/routes.php
Route::get('(:package)', 'admin::home@index');
Route::get('(:package)/dashboard', 'admin::home@dashboard');
```

**Package config in `application/packages.php`:**

```php
return [
    'admin' => ['handles' => 'admin'],
];
```

<a id="complete-example"></a>
## Complete Example

**File: `application/controllers/post.php`**

```php
class Post_Controller extends Controller
{
    public $layout = 'layouts.app';

    public function __construct()
    {
        // Middleware for all actions
        $this->middleware('before', 'auth')->except(['index', 'show']);

        // CSRF protection for write operations
        $this->middleware('before', 'csrf')->on('post');

        // Log access
        $this->middleware('after', 'log_access');
    }

    /**
     * Display listing of posts
     * GET /posts
     */
    public function action_index()
    {
        $posts = Post::with('author')
            ->where('published', 1)
            ->order_by('created_at', 'desc')
            ->paginate(15);

        $this->layout->title = 'Blog Posts';
        $this->layout->content = View::make('posts.index', compact('posts'));
    }

    /**
     * Show single post
     * GET /posts/123 or /posts/my-post-slug
     */
    public function action_show($identifier)
    {
        // Try to find by ID first, then by slug
        $post = is_numeric($identifier)
            ? Post::find($identifier)
            : Post::where('slug', $identifier)->first();

        if (!$post) {
            return Response::error('404');
        }

        // Increment views
        $post->increment('views');

        // Get related posts
        $related = Post::where('category_id', $post->category_id)
            ->where('id', '!=', $post->id)
            ->take(5)
            ->get();

        $this->layout->title = $post->title;
        $this->layout->content = View::make('posts.show', compact('post', 'related'));
    }

    /**
     * Show form to create new post
     * GET /posts/create
     */
    public function action_create()
    {
        $categories = Category::lists('name', 'id');

        $this->layout->title = 'Create New Post';
        $this->layout->content = View::make('posts.create', compact('categories'));
    }

    /**
     * Store new post
     * POST /posts
     */
    public function action_store()
    {
        $rules = [
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'required|exists:categories,id',
        ];

        $validation = Validator::make(Input::all(), $rules);

        if ($validation->fails()) {
            return Redirect::back()
                ->with_input()
                ->with_errors($validation);
        }

        $post = new Post;
        $post->title = Input::get('title');
        $post->slug = Str::slug(Input::get('title'));
        $post->content = Input::get('content');
        $post->category_id = Input::get('category_id');
        $post->user_id = Auth::user()->id;
        $post->save();

        return Redirect::to('posts/' . $post->id)
            ->with('message', 'Post created successfully!');
    }

    /**
     * Show form to edit post
     * GET /posts/123/edit
     */
    public function action_edit($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return Response::error('404');
        }

        // Check ownership
        if ($post->user_id !== Auth::user()->id && !Auth::user()->is_admin) {
            return Response::error('403');
        }

        $categories = Category::lists('name', 'id');

        $this->layout->title = 'Edit Post';
        $this->layout->content = View::make('posts.edit', compact('post', 'categories'));
    }

    /**
     * Update existing post
     * PUT /posts/123
     */
    public function action_update($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return Response::error('404');
        }

        // Check ownership
        if ($post->user_id !== Auth::user()->id && !Auth::user()->is_admin) {
            return Response::error('403');
        }

        $rules = [
            'title' => 'required|max:255',
            'content' => 'required',
            'category_id' => 'required|exists:categories,id',
        ];

        $validation = Validator::make(Input::all(), $rules);

        if ($validation->fails()) {
            return Redirect::back()
                ->with_input()
                ->with_errors($validation);
        }

        $post->title = Input::get('title');
        $post->slug = Str::slug(Input::get('title'));
        $post->content = Input::get('content');
        $post->category_id = Input::get('category_id');
        $post->save();

        return Redirect::to('posts/' . $post->id)
            ->with('message', 'Post updated successfully!');
    }

    /**
     * Delete post
     * DELETE /posts/123
     */
    public function action_destroy($id)
    {
        $post = Post::find($id);

        if (!$post) {
            return Response::error('404');
        }

        // Check ownership
        if ($post->user_id !== Auth::user()->id && !Auth::user()->is_admin) {
            return Response::error('403');
        }

        $post->delete();

        return Redirect::to('posts')
            ->with('message', 'Post deleted successfully!');
    }

    /**
     * Search posts
     * GET /posts/search?q=keyword
     */
    public function action_search()
    {
        $keyword = Input::get('q');

        $posts = Post::where('title', 'like', "%$keyword%")
            ->or_where('content', 'like', "%$keyword%")
            ->paginate(15);

        $this->layout->title = 'Search: ' . $keyword;
        $this->layout->content = View::make('posts.search', compact('posts', 'keyword'));
    }
}
```

**File: `application/controllers/base.php`**

```php
abstract class Base_Controller extends Controller
{
    public $layout = 'layouts.master';

    public function __construct()
    {
        // Set global view data
        View::share('app_name', Config::get('application.name'));
        View::share('current_user', Auth::user());

        // CSRF token
        View::share('csrf_token', Session::token());
    }

    public function layout()
    {
        // No layout for AJAX requests
        if (Request::ajax()) {
            return null;
        }

        // No layout for API requests
        if (Request::is('api/*')) {
            return null;
        }

        return View::make($this->layout);
    }

    protected function validate_ownership($model, $user_id_field = 'user_id')
    {
        if ($model->{$user_id_field} !== Auth::user()->id && !Auth::user()->is_admin) {
            return Response::error('403');
        }
    }
}
```

**Routing: `application/routes.php`**

```php
// Resource controller
Route::resource('posts', ['controller' => 'post']);

// Additional routes
Route::get('posts/search', 'post@search');
Route::get('posts/category/(:any)', 'post@category');
Route::post('posts/(:num)/comment', 'post@add_comment');
```
