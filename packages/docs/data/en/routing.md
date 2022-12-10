# Routing

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [The Basics](#routing-dasar)
    -   [Redirect Routes](#route-redirect)
    -   [View Routes](#route-view)
-   [Wildcards](#uri-wildcard)
-   [The 404 Event](#event-404)
-   [Middlewares](#middleware)
-   [Pattern Middlewares](#middleware-pola-uri)
-   [Global Middlewares](#middleware-global)
-   [Route Groups](#route-grouping)
-   [Named Routes](#named-route)
-   [Package Routes](#routing-paket)
-   [Controller Routing](#routing-controller)
-   [CLI Route Testing](#route-testing-via-cli)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Unlike many other frameworks with Rakit it's possible to embed application logic in two ways.

While controllers are the most common way to implement application logic it's also possible
to embed your logic directly into routes.

This is **especially** nice for small sites that contain only a few pages
as you don't have to create a bunch of controllers just to expose half a dozen methods
or put a handful of unrelated methods into the same controller and then have to
manually designate routes that point to them.

Routes are usually defined in the `routes.php` file.

In the following example the first parameter is the route that you're _"registering"_ with the router.
The second parameter is the function containing the logic for that route.

Routes are defined without a front-slash.
The only exception to this is the default route which is represented with only a front-slash.

> Routes are evaluated in the order that they are registered,
> so register any "catch-all" routes at the bottom of your routes.php file.

<a id="routing-dasar"></a>

## The Basics

The most basic route accepts both a URI and a closure, providing a very simple method and
expressive way to define routes and behavior without complicated routing configuration files:

#### Registering a route that responds to `GET`:

```php
Route::get('/', function () {
	return 'Halo dunia!';
});
```

#### Registering a route that is valid for any HTTP verb (`GET`, `POST`, `PUT`, and `DELETE`):

```php
Route::any('/', function () {
	return 'Halo dunia!';
});
```

#### Registering routes for other request methods:

```php
Route::post('user', function () {
    // ..
});

Route::put('user/(:num)', function ($id) {
    // ..
});

Route::delete('user/(:num)', function ($id) {
	// ..
});
```

#### Registering a single URI for multiple HTTP verbs:

```php
Router::register(['GET', 'POST'], $uri, $callback);
```

<a id="route-redirect"></a>

### Redirect Routes

If you need to create a redirection route to another URI, you can use the `Route::redirect()` method.
This method provides a convenient shortcut so you don't have to use Closure to do a simple redirect:

```php
Route::redirect('deleted-page', 'home');
```

By default, it will return the status code `302`. You can customize it of course:

```php
Route::redirect('deleted-page', 'home', 301);
```

<a id="route-view"></a>

### View Routes

If your route only needs to return views, you can use the `Route::view()` method.

This method accepts the URI as its first argument and the view name as its second argument.
Additionally, you can also pass an array of data to pass to the view as the third argument:

```php
Route::view('/', 'home');

Route::view('profile', 'profile', ['name' => 'Budi']);
```

<a id="uri-wildcard"></a>

## Wildcards

#### Forcing a URI segment to be any alphabets:

```php
Route::get('user/(:alpha)', function ($id) {
    // ..
});
```

#### Forcing a URI segment to be any digit:

```php
Route::get('user/(:num)', function ($id) {
    // ..
});
```

#### Allowing a URI segment to be any alpha-numeric string:

```php
Route::get('post/(:any)', function ($title) {
    // ..
});
```

#### Catching the remaining URI without limitations:

```php
Route::get('files/(:all)', function ($path) {
    // ..
});
```

#### Allowing a URI segment to be optional:

```php
Route::get('page/(:any?)', function ($page = 'index') {
    // ..
});
```

<a id="event-404"></a>

## The 404 Event

If a request enters your application but does not match any existing route, the 404 event
will be raised. You can find the default event handler in your `application/events.php` file.

#### The default 404 event handler:

```php
Event::listen('404', function () {
    return Response::error('404');
});
```

You are free to change this to fit the needs of your application!

_Further Reading:_

-   _[Events](/docs/en/events)_

<a id="middleware"></a>

## Middlewares

Route middlewares may be run before or after a route is executed.
If a `"before"` middleware returns a value, that value is considered the response to the request
and the route is not executed, which is convenient when implementing authentication middlewares, etc.

Middlewares are usually defined in the `middlewares.php` file.

#### Registering a middleware:

```php
Route::middleware('auth', function () {
    return Redirect::to('home');
});
```

#### Attaching a middleware to a route:

```php
Route::get('blocked', ['before' => 'auth', function () {
    return View::make('blocked');
}]);
```

#### Attaching an `"after"` middleware to a route:

```php
Route::get('download', ['after' => 'track', function () {
    // ..
}]);
```

#### Attaching multiple middlewares to a route:

```php
Route::get('create', ['before' => 'csrf|auth', function () {
    // ..
}]);
```

#### Passing parameters to middlewares:

```php
Route::get('panel', ['before' => 'role:admin', function () {
    // ..
}]);
```

<a id="middleware-pola-uri"></a>

## Pattern Middlewares

Sometimes you may want to attach a middleware to all requests that begin with a given URI.
For example, you may want to attach the `"auth"` middleware to all requests with URIs
that begin with `"/admin"`. Here's how to do it:

#### Defining a URI pattern based middleware:

```php
Route::middleware('pattern: admin/*', 'auth');
```

Optionally you can register middlewares directly when attaching middlewares to a given URI
by supplying an array with the name of the middleware and a callback.

#### Defining a middleware and URI pattern based middleware in one:

```php
Route::middleware('pattern: admin/*', ['name' => 'auth', function () {
    // ..
}]);
```

<a id="middleware-global"></a>

## Global Middlewares

Rakit has two "global" middlewares that run before and after every request to your application.
You can find them both in the `application/middlewares.php` file.

These middlewares make great places to start common packages or other things.

> The `"after"` middleware receives the `Response` object for the current request.

<a id="route-grouping"></a>

## Route Groups

Route groups allow you to attach a set of attributes to a group of routes,
allowing you to keep your code neat and tidy.

```php
Route::group(['before' => 'auth'], function () {

	Route::get('panel', function () {
        // ..
	});

	Route::get('dashboard', function () {
        // ..
	});
});
```

<a id="named-route"></a>

## Named Routes

Constantly generating URLs or redirects using a route's URI can cause problems
when routes are later changed. Assigning the route a name gives you a convenient way
to refer to the route throughout your application.

When a route change occurs the generated links will point to the new route with no further configuration needed.

#### Registering a named route:

```php
Route::get('/', ['as' => 'home', function () {
    return 'Welcome to our homepage!';
}]);
```

#### Generating a URL to a named route:

```php
$url = URL::to_route('home');
```

#### Redirecting to the named route:

```php
return Redirect::to_route('home');
```

Once you have named a route, you may easily check if the route handling the current request has a given name.

#### Determine if the route handling the request has a given name:

```php
if (Request::route()->is('home')) {
    return 'Current route name is: home';
}
```

<a id="routing-paket"></a>

## Package Routes

Rakit is a flexible framework, the way it works is similar to a package manager on Linux.
Packages can easily be configured to handle requests to your application.

We'll be going over [packages in more detail](/docs/en/packages) in another document.
For now, read through this section and just be aware that not only can routes be used
to expose functionality in packages, but they can also be registered from within packages.

Let's open the `application/packages.php` file and add something:

#### Registering a package to handle routes:

```php
return [

	'admin' => ['handles' => 'admin'],

];
```

Notice the new `'handles'` option in our package configuration array?

This tells rakit to load the `admin` package on any requests where the URI begins with `"/admin"`.

Now you're ready to register some routes for your package, so create a `routes.php` file
within the root directory of your package and add the following:

#### Registering a '/' (root) route for a package:

```php
Route::get('(:package)', function () {
    return 'Welcome to the admin package!';
});
```

Let's explore this example. Notice the `(:package)` placeholder?

That will be replaced with the value of the handles clause that you used to register your package.
This keeps your code [D.R.Y](https://en.wikipedia.org/wiki/Don't_repeat_yourself), and allows
those who use your package to change it's root URI without breaking your routes! Nice, right?

Of course, you can use the `(:package)` placeholder for all of your routes, not just your root route.

#### Registering package routes:

```php
Route::get('(:package)/password', function () {
    return 'Welcome to the admin >> password page!';
});
```

<a id="routing-controller"></a>

## Controller Routing

Controllers provide another way to manage your application logic. If you're unfamiliar with controllers
you may want to [read about controllers](/docs/en/controllers) and return to this section.

It is important to be aware that all routes in rakit **must be explicitly defined**,
including routes to controllers.

This means that controller methods that have not been exposed through
route registration **cannot be accessed** by your visitors.

It's possible to automatically expose all methods within a controller using controller route registration.
Controller route registrations are typically defined in `routes.php` file.

Most likely, you just want to register all of the controllers in your application's `controllers/` directory.
You can do it in one simple statement. Here's how:

#### Register all controllers for the application:

```php
Route::controller(Controller::detect());
```

The `Controller::detect()` method simply returns an array of all of the controllers defined for the application.

If you wish to automatically detect the controllers in a package, just pass the package name to the method.

If no package is specified, the application folder's controller directory will be searched.

> It is important to note that this method gives you no control over the order in which controllers are loaded.
> `Controller::detect()` should only be used to Route controllers in very small sites.
> "Manually" routing controllers gives you much more control, is more self-documenting, and is certainly advised.

#### Register all controllers for the `"admin"` package:

```php
Route::controller(Controller::detect('admin'));
```

#### Registering the `home` controller with the Router:

```php
Route::controller('home');
```

#### Registering several controllers with the router:

```php
Route::controller(['dashboard.panel', 'admin']);
```

Once a controller is registered, you may access its methods using a simple URI convention:

```ini
mysite.com/<controller_name>/<method_name>/<additional_parameters>
```

This convention is similar to that employed by CodeIgniter 3 and other popular frameworks,
where the first segment is the controller name, the second is the method,
and the remaining segments are passed to the method as arguments.

If no method segment is present, the `index` method will be used.

This routing convention may not be desirable for every situation, so you may also
explicitly route URIs to controller actions using a simple, intuitive syntax.

#### Registering a route that points to a controller action:

```php
Route::get('/', 'home@index');
```

#### Registering a route with middleware that points to a controller action:

```php
Route::get('/', ['uses' => 'home@index', 'after' => 'track']);
```

#### Registering a named route that points to a controller action:

```php
Route::get('/', ['uses' => 'home@index', 'as' => 'home.welcome']);
```

<a id="route-testing-via-cli"></a>

## CLI Route Testing

Anda dapat memanggil rute yang anda buat via [console](/docs/en/console#memanggil-rute).
Cukup sebutkan tipe request dan URI mana yang ingin anda panggil.

You may test your routes using [rakit console command](/docs/en/console#memanggil-rute).
Simply specify the request method and URI you want to use:

#### Calling a route via the rakit console:

```bash
php rakit route:call get api/user/1
```
