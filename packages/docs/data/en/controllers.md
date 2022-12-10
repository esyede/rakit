# Controller

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Controller Routing](#controller-routing)
-   [Package Controller](#controller-paket)
-   [Action Middleware](#action-middleware)
-   [Nested Controller](#nested-controller)
-   [Controller Layout](#layout-controller)
-   [RESTful Controller](#restful-controller)
-   [Dependency Injection](#dependency-injection)
-   [Controller Factory](#controller-factory)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Controller is a class that is responsible for receiving user input and managing interactions
between models, libraries, and views. Usually, they will request data to the Model, and then return
view that represents those data to the user.

Controller is the most common method of implementing application logic in modern web development.

However, Rakit also empowers developers to implement their application logic in a
routing declaration via closure. This topic is discussed in detail at
[routing documentation](/docs/en/routing).

New users are advised to start with the controller. There's nothing closure-based application logic
can do that controller-based cannot do.

The controller class should be stored in the `controllers/` folder. We have included
`Home_Controller` class (in `application/controllers/home.php` file) as a usage example.

<a id="membuat-controller-sederhana"></a>

#### Creating a simple controller:

```php
class Admin_Controller extends Controller
{
    public function action_index()
    {
        // ..
    }
}
```

**Action** is the name of the controller method that is intended to be accessible via the web.
Method name for actions must begin with the word `action_`.

All other methods, if the name does not starts with the word `action_` then it **will not be accessible**
by visitors to your site.

<a id="controller-routing"></a>

## Controller Routing

It is important to know that all routes in Rakit must be defined explicitly,
including the route to the controller.

This means that the methods in the controller class that have not been exposed via route registration
**will not be accessible** by visitors.

It is possible to automatically expose all methods in a controller using the `Route::controller()`
registration. Routes controller registration is usually done in the `routes.php` file.

Read the [routing page](/docs/en/routing#controller-routing) for more detailed documentation on
controller routings.

<a id="controller-paket"></a>

## Package Controller

Packages are a very flexible modularization system. Packages can be easily configured to
handle requests that come to your application. We will discuss [packages in more detail](/docs/en/packages)
in another document.

Creating controller for a package is pretty much the same as creating a regular controller. Just start
controller class name with package name. So if you want to create a package named `admin`,
your controller class should look like this:

#### Creating a controller for the admin package:

```php
class Admin_Home_Controller extends Controller
{
    public function action_index()
    {
        return "Welcome to the admin packages's index page!";
    }
}
```

So, how to register this package's controller to the router? Easy. Here's how:

#### Registering a package's controller to the router:

```php
Route::controller('admin::home');
```

Excellent! Now we can access the `admin` package's home controller from the web!

> By default, the `::` (double colon) syntax is used to refer to any information belonging to a package.
> More information about packages can be found in [package documentation](/docs/en/packages).

<a id="action-middleware"></a>

## Action Middleware

Action middleware is middleware that can be executed before or after a controller's action is executed.
Not only you have control over which middleware is assigned to which actions, you
can also choose what type of request (`GET`, `POST`, `PUT`, or `DELETE`) to activate the middleware.

You can define `before()` and `after()` middleware via the controller's class **constructor**.

Let's try adding middleware to the controller above.

#### Attach middleware to all actions:

```php
class Admin_Home_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth');
    }


    public function action_index()
    {
        return "Welcome to the admin packages's index page!";
    }
}
```

In the above example the `'auth'` middleware will be called before every action
in this controller is executed.

This `'auth'` middleware is Rakit's default middleware, the implementation can be
found in the `application/middlewares.php` file. The auth middleware verifies that
the user is already logged in, and redirects them to the `'/login'` page if they not logged in.

#### Attach middleware only to a few actions:

```php
$this->middleware('before', 'auth')->only(['index', 'list']);
```

In the above example the `'auth'` middleware will only be executed before the `action_index()` method
or `action_list()` is executed. User must be logged in to be able to access those two actions.
Other actions will not be affected.

#### Melampirkan middleware ke semua action kecuali yang disebutkan:

```php
$this->middleware('before', 'auth')->except(['add', 'posts']);
```

Just like the previous example, this declaration ensures that the `'auth'` middleware only
will run on some actions only.

Instead of defining which actions need to be authenticated, we just need to declare
whichever action will not require authentication.

Sometimes it's safer to use this `except()` method because in the future you may need to
add a new action to this controller and forgot to add it to the `only()` method.

This has the potential to cause your action controller to be inadvertently accessible by
users who have not logged in.

#### Melampirkan middleware untuk dijalankan hanya pada tipe request POST:

```php
$this->middleware('before', 'csrf')->on('post');
```

This example shows how the middleware will only run on certain types of requests.
In this case we apply the middleware `'csrf'` only when the incoming request is a `POST` request.

The `'csrf'` middleware is designed to prevent sending POST data from other parties (eg spam bots).

This middleware is also provided by default. You can see the default implementation
of `'csrf'` middleware in the `middlewares.php` file.

_Further reading :_

-   _[Middleware](/docs/en/routing#middleware)_

<a id="nested-controller"></a>

## Nested Controller

Nested controllers are controllers that are placed in subfolders. Right, you can save
controllers in a number of subfolders inside the `controllers/` folder.

Try creating the following controller class and saving it as `controllers/admin/panel.php`:

```php
class Admin_Panel_Controller extends Controller
{
    public function action_index()
    {
        // ..
    }
}
```

#### Register the nested controller to the router using dot notation:

```php
Route::controller('admin.panel');
```

> When using nested controllers, always list your controllers from those in
> innermost subfolders so controller routes don't overlap each other.

#### Accessing the controller's `index` action:

```ini
mysite.com/admin/panel
```

<a id="layout-controller"></a>

## Layout Controller

Full documentation on using layouts with Controllers can be found at the
[templating page](/docs/en/views/templating).

<a id="restful-controller"></a>

## RESTful Controller

Rakit also supports RESTful controllers. This will bw useful when building a
[CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) system because
you can separate HTML form creation logic from the logic that validates and stores the results.

A RESTful controller is indicated by the presence of a public property `$restful` in a controller class,
and the value is `TRUE`.

Instead of prefixing the controller action name with the word `action_`, you can replace it
with what type of request (eg `POST`, `GET`, `PUT` or `DELETE`) it should respond.

#### Defining the `$restful` property to the controller:

```php
class Home_Controller extends Controller
{
    public $restful = true;

    // ..
}
```

#### Creating a RESTful action in the controller:

```php
class Home_Controller extends Controller
{
    public $restful = true;


    public function get_index()
    {
        // I only accept GET request
    }

    public function post_index()
    {
        // I only accept POST request
    }
}
```

<a id="dependency-injection"></a>

## Dependency Injection

If you are focused on writing _testable_ code, you may need to inject dependencies
into your controller's constructor.

No problem. Just register your controller into the [Container](/docs/en/container).
When registering a controller to a container, prefix the name with the word `controller`.

For example, in the `boot.php` file, you can register the `User` controller as follows:

```php
Container::register('controller: user', function () {
    return new User_Controller();
});
```

When a request comes to your controller, Rakit will automatically check if the controller
is registered in the container or not, and if so, then Rakit will use this data
to resolve the controller instance.

> Before diving deeper into Dependency Injection Controller, you may want to read
> the documentation about [Container](/docs/en/container).

<a id="controller-factory"></a>

## Controller Factory

If you want more control over how your controller is instantiated, like
when using third party containers, you should use this controller factory feature.

#### Register events to handle controller instantiation:

```php
Event::listen(Controller::FACTORY, function ($controller) {
    return new $controller();
});
```

The event will receive the name of the controller class that needs to be resolved.
All you need to do is return an instance of the controller class.
