# Controller

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Introduction](#introduction)
- [Defining Controllers](#defining-controllers)
  - [Naming Conventions](#naming-conventions)
  - [Nested Controllers](#nested-controllers)
- [Routing to Controllers](#routing-to-controllers)
- [RESTful Controllers](#restful-controllers)
- [Resource Controllers](#resource-controllers)
- [Controller Middleware](#controller-middleware)
  - [Limiting Middleware](#limiting-middleware)
  - [Before & After Hooks](#before--after-hooks)
- [Layouts](#layouts)
- [Validation](#validation)
- [Dependency Injection](#dependency-injection)
  - [Controller Factory](#controller-factory)
- [Package Controllers](#package-controllers)
- [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

Controllers group related request handlers in one class instead of many route
closures. They live in `application/controllers/`, extend `Controller`, and
expose their actions as `action_*()` methods.

<a id="defining-controllers"></a>
<a id="creating-controllers"></a>
<a id="basic-controller"></a>
<a id="controller-action"></a>

## Defining Controllers

```php
// application/controllers/user.php
class User_Controller extends Controller
{
    public function action_index()
    {
        return View::make('user.index');
    }

    public function action_show($id)
    {
        return View::make('user.show', ['user' => User::find($id)]);
    }

    // Not reachable from the web: no action_ prefix
    protected function helper()
    {
        // ..
    }
}
```

Action arguments are the route parameters, in order. Calling an action that
does not exist returns a 404 error.

A shared base class goes in `application/libraries/base/controller.php` as
`Base_Controller` (the autoloader maps `Base_Controller` to `base/controller.php`,
so `application/controllers/base.php` would not be found). Other controllers then
extend `Base_Controller`.

<a id="naming-conventions"></a>
<a id="naming-convention"></a>

### Naming Conventions

| Item   | Convention                                 | Example                                  |
|--------|--------------------------------------------|------------------------------------------|
| File   | lowercase, underscores                     | `admin_user.php`                         |
| Class  | each word capitalized + `_Controller`      | `Admin_User_Controller`                  |
| Action | `action_` + lowercase, underscores         | `action_edit_profile()`                  |

<a id="nested-controllers"></a>
<a id="nested-controller"></a>

### Nested Controllers

Controllers in subfolders take the folder name in their class name and are
referenced with dots:

```php
// application/controllers/admin/user.php
class Admin_User_Controller extends Controller
{
    public function action_index()
    {
        return View::make('admin.user.index');
    }
}

// application/routes.php
Route::get('admin/users', 'admin.user@index');
```

<a id="routing-to-controllers"></a>
<a id="controller-routing"></a>
<a id="manual-routing"></a>
<a id="auto-routing"></a>
<a id="route-to-specific-action"></a>

## Routing to Controllers

Route to an action with `controller@action`, or let `Route::controller()` map
URLs as `/{controller}/{action}/{params...}`:

```php
Route::get('user/(:num)', 'user@show');
Route::get('admin/dashboard', ['uses' => 'admin@dashboard', 'as' => 'admin.dashboard']);

Route::controller('user');               // /user, /user/show/5 -> action_show(5)
Route::controller(['user', 'post']);
Route::controller(Controller::detect()); // every controller, including nested ones
```

See [Controller Routing](/docs/routing#controller-routing) for details.

<a id="restful-controllers"></a>
<a id="restful-controller"></a>
<a id="creating-restful-controller"></a>
<a id="restful-action-convention"></a>
<a id="restful-routing"></a>

## RESTful Controllers

Set `$restful = true` and prefix actions with the lowercase HTTP verb
(`get_`, `post_`, `put_`, `patch_`, `delete_`, ...) instead of `action_`:

```php
class User_Controller extends Controller
{
    public $restful = true;

    public function get_index()          // GET /user
    {
        return View::make('user.index');
    }

    public function post_index()         // POST /user
    {
        User::create(Input::all());
        return Redirect::to('user');
    }

    public function put_update($id)      // PUT /user/update/123
    {
        // ..
    }
}
```

With `Route::controller('user')` the URL picks the action and the HTTP verb
picks the prefix: `GET /user/edit/5` calls `get_edit(5)`.

<a id="resource-controllers"></a>
<a id="resource-controller"></a>
<a id="resource-actions"></a>
<a id="resource-routing"></a>

## Resource Controllers

`Route::resource()` routes the seven CRUD actions to a controller (see the
[route table](/docs/routing#resource-routes)):

```php
Route::resource('posts');                            // Posts_Controller
Route::resource('posts', ['controller' => 'post']);  // Post_Controller instead
```

```php
class Post_Controller extends Controller
{
    public function action_index() {}        // GET    /posts
    public function action_create() {}       // GET    /posts/create
    public function action_store() {}        // POST   /posts
    public function action_show($id) {}      // GET    /posts/123
    public function action_edit($id) {}      // GET    /posts/123/edit
    public function action_update($id) {}    // PUT    /posts/123
    public function action_delete($id) {}    // DELETE /posts/123
}
```

> The DELETE route calls `action_delete()`, not `action_destroy()`. The id
> segment is `(:any)`; register routes manually if you need `(:num)`.

A dotted name nests the resource: `Route::resource('posts.comments')` routes
`/posts/(:any?)/comments/...` to `posts.comments@...`
(`application/controllers/posts/comments.php`), with the parent id as the first
action argument.

<a id="controller-middleware"></a>
<a id="before-middleware"></a>
<a id="after-middleware"></a>
<a id="middleware-with-parameter"></a>

## Controller Middleware

Attach middleware in the constructor with `$this->middleware($event, $names)`.
Names work as in routes: `|` separates them and `:` adds parameters:

```php
class Admin_Controller extends Controller
{
    public function __construct()
    {
        parent::__construct();

        $this->middleware('before', 'auth|role:admin');
        $this->middleware('after', 'log_access');
    }
}
```

A third argument passes parameters to every middleware in the call, as an array
or a closure evaluated when the middleware runs:

```php
$this->middleware('before', 'role', ['admin']);
```

<a id="limiting-middleware"></a>
<a id="middleware-for-specific-action"></a>

### Limiting Middleware

`only()` and `except()` take action names (without the prefix); `on()` takes
HTTP methods. They can be chained:

```php
$this->middleware('before', 'auth')->only(['create', 'edit']);
$this->middleware('before', 'admin')->except(['index', 'show']);
$this->middleware('before', 'csrf')->on('post');
$this->middleware('before', 'cache')->on('get')->only(['show']);
```

<a id="before--after-hooks"></a>

### Before & After Hooks

Override `before()` to run code before every action (after the `before`
middleware), and `after($response)` to run code after it:

```php
public function before()
{
    View::share('section', 'admin');
}

public function after($response)
{
    $response->header('X-Section', 'admin');
}
```

<a id="layouts"></a>
<a id="layout-controller"></a>
<a id="defining-layout"></a>
<a id="using-layout"></a>
<a id="nested-layout"></a>

## Layouts

Set `$layout` to a view name and the constructor turns it into a `View`. Fill
its variables in the action and return nothing; the layout becomes the response:

```php
class Home_Controller extends Controller
{
    public $layout = 'layouts.master';

    public function action_index()
    {
        $this->layout->title = 'Home Page';
        $this->layout->content = View::make('home.index');
    }
}
```

```blade
{{-- application/views/layouts/master.blade.php --}}
<title>{{ $title }}</title>
<main>{{ $content }}</main>
```

Prefix the value with `name: ` to use a [named view](/docs/views#named-views)
(`'name: master'`), or override `layout()` to build it yourself.

> The layout is built in `Controller::__construct()`. If you define your own
> constructor, call `parent::__construct()` or `$this->layout` stays a string.

<a id="validation"></a>

## Validation

`$this->validate($rules)` validates the current input and returns a redirect
back (with input and errors) when it fails, or `null` when it passes:

```php
public function action_store()
{
    if ($redirect = $this->validate(['title' => 'required|max:255'])) {
        return $redirect;
    }

    // ..
}
```

<a id="dependency-injection"></a>

## Dependency Injection

Controllers are created with `new $class()`, so constructors get no automatic
injection. To build one with dependencies, bind it in the
[Container](/docs/container) under `controller: <name>`, where `<name>` is the
controller part of `controller@action` (`admin.user`, `admin::home`, ...):

```php
// application/boot.php
Container::singleton('controller: user', function () {
    return new User_Controller(new UserRepository(), new MailService());
});
```

Inside a controller, `$this->foo` resolves `foo` from the container when it is
registered there. Action arguments are always route parameters, never
container services.

<a id="controller-factory"></a>

### Controller Factory

For full control over instantiation, listen to `rakit.controller.factory`. It
receives the class name and must return the instance (container bindings take
precedence):

```php
Hook::listen('rakit.controller.factory', function ($class) {
    if ('Admin_User_Controller' === $class) {
        return new $class(new UserRepository());
    }

    return new $class();
});
```

<a id="package-controllers"></a>
<a id="package-controller"></a>

## Package Controllers

Package controllers live in `packages/<package>/controllers/`, carry the
package name as a class prefix, and are referenced as `package::controller`:

```php
// packages/admin/controllers/home.php
class Admin_Home_Controller extends Controller
{
    public function action_index()
    {
        return View::make('admin::home.index');
    }
}

// packages/admin/routes.php
Route::get('(:package)', 'admin::home@index');
```

See [Package Routes](/docs/routing#package-routes).

<a id="example"></a>
<a id="complete-example"></a>

## Example

```php
// application/controllers/post.php
class Post_Controller extends Controller
{
    public $layout = 'layouts.app';

    public function __construct()
    {
        parent::__construct();

        $this->middleware('before', 'auth')->except(['index', 'show']);
        $this->middleware('before', 'csrf')->on('post', 'put', 'delete');
    }

    public function action_index()
    {
        $this->layout->title = 'Posts';
        $this->layout->content = View::make('posts.index', ['posts' => Post::paginate(15)]);
    }

    public function action_store()
    {
        if ($redirect = $this->validate(['title' => 'required|max:255'])) {
            return $redirect;
        }

        $post = Post::create(Input::only(['title', 'content']));

        return Redirect::to_route('posts.show', [$post->id]);
    }
}

// application/routes.php
Route::resource('posts', ['controller' => 'post']);
```
