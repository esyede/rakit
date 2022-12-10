# Helper

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Helper Lists](#list-helper)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Rakit provides a variety of globally accessible helper functions.
Many of these functions are also used in inside the rakit itself;
and of course, you can also use it in your application if needed.

<a id="list-helper"></a>

## Helper Lists

Here is a list of all built-in helpers:

|                             |                                   |                                   |                                 |                                   |                                   |
| --------------------------- | --------------------------------- | --------------------------------- | ------------------------------- | --------------------------------- | --------------------------------- |
| [e](#e)                     | [dd](#dd)                         | [dump](#dump)                     | [bd](#bd)                       | [\_\_](#__)                       | [is_cli](#is_cli)                 |
| [data_fill](#data_fill)     | [data_get](#data_get)             | [data_set](#data_set)             | [retry](#retry)                 | [facile_to_json](#facile_to_json) | [head](#head)                     |
| [last](#last)               | [url](#url)                       | [asset](#asset)                   | [action](#action)               | [route](#route)                   | [redirect](#redirect)             |
| [csrf_field](#csrf_field)   | [root_namespace](#root_namespace) | [class_basename](#class_basename) | [value](#value)                 | [view](#view)                     | [render](#render)                 |
| [render_each](#render_each) | [yield_content](#yield_content)   | [yield_section](#yield_section)   | [section_start](#section_start) | [section_stop](#section_stop)     | [get_cli_option](#get_cli_option) |
| [system_os](#system_os)     |

<a id="e"></a>

### e

The `e` function executes [htmlspecialchars](https://php.net/htmlspecialchars)
function with `double_encode` option set to `TRUE` by default:

```php
echo e('<html>foo</html>');

// &lt;html&gt;foo&lt;/html&gt;
```

<a id="dd"></a>

### dd

The `dd` function dumps the given variables and ends execution of the script:

```php
dd($value);

dd($value1, $value2, $value3, ...);
```

<a id="dump"></a>

### dump

The `dump` function dumps the given variables:

```php
dump($value);

dump($value1, $value2, $value3, ...);
```

If you want to stop executing the script after dumping the variables, use the `dd` function instead.

<a id="bd"></a>

### bd

The `bd` function dumps the given variable to [debug bar](/docs/en/debugging#debug-bar).
The script execution will still run:

```php
bd($value);

bd($value, 'Breakpoint 1');
```

<a id="__"></a>

### \_\_

The `__` function translates the given translation key using your localization files in the `languages` folder:

```php
echo __('marketing.welcome');
```

<a id="is_cli"></a>

### is_cli

The `is_cli` function checks if the script is run from within the console:

```php
if (is_cli()) {
    // Request datang dari command line!
}
```

<a id="data_fill"></a>

### data_fill

The `data_fill` function sets a missing value within a nested array or object using "dot" notation:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_fill($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 100]]]

data_fill($data, 'products.desk.discount', 10);
// ['products' => ['desk' => ['price' => 100, 'discount' => 10]]]
```

This function also accepts `*` (asterisks) as wildcards and will fill the target accordingly:

```php
$data = [
    'products' => [
        ['name' => 'Desk 1', 'price' => 100],
        ['name' => 'Desk 2'],
    ],
];

data_fill($data, 'products.*.price', 200);
/*
    [
        'products' => [
            ['name' => 'Desk 1', 'price' => 100],
            ['name' => 'Desk 2', 'price' => 200],
        ],
    ]
*/
```

<a id="data_get"></a>

### data_get

The `data_get` function retrieves a value from a nested array or object using "dot" notation:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

$price = data_get($data, 'products.desk.price');
// 100
```

The `data_get` function also accepts a default value, which will be
returned if the specified key is not found:

```php
$discount = data_get($data, 'products.desk.discount', 0);
// 0
```

The function also accepts wildcards using `*` (asterisks),
which may target any key of the array or object:

```php
$data = [
    'product-one' => ['name' => 'Desk 1', 'price' => 100],
    'product-two' => ['name' => 'Desk 2', 'price' => 150],
];

data_get($data, '*.name');
// ['Desk 1', 'Desk 2'];
```

<a id="data_set"></a>

### data_set

The data_set function sets a value within a nested array using "dot" notation:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 200]]]
```

This function also accepts wildcards using `*` (asterisks)
and will set values on the target accordingly:

```php
$data = [
    'products' => [
        ['name' => 'Desk 1', 'price' => 100],
        ['name' => 'Desk 2', 'price' => 150],
    ],
];

data_set($data, 'products.*.price', 200);
/*
    [
        'products' => [
            ['name' => 'Desk 1', 'price' => 200],
            ['name' => 'Desk 2', 'price' => 200],
        ],
    ]
*/
```

By default, any existing values are overwritten. If you wish to only set a value if
it doesn't exist, you may pass `FALSE` as the fourth argument to the function:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200, $overwrite = false);
// ['products' => ['desk' => ['price' => 100]]]
```

<a id="retry"></a>

### retry

The retry function attempts to execute the given callback
until the given maximum attempt threshold is met.

If the callback does not throw an exception, its return value will be returned.
If the callback throws an exception, it will automatically be retried.

If the maximum attempt count is exceeded, the exception will be thrown:

```php
return retry(5, function () {
    // Retry 5 times with interval of 100ms on each try...

}, 100);
```

<a id="facile_to_json"></a>

### facile_to_json

The `facile_to_json` function will convert the Facile model object into a JSON string:

```php
$json = facile_to_json(User::find(1));

$json = facile_to_json(User::all());
```

<a id="head"></a>

### head

The `head` function returns the first element in the given array:

```php
$array = [100, 200, 300];

$first = head($array);
// 100
```

<a id="last"></a>

### last

The `last` function returns the last element in the given array:

```php
$array = [100, 200, 300];

$first = last($array);
// 300
```

<a id="url"></a>

### url

The `url` function generates a fully qualified URL to the given path:

```php
$url = url('user/profile');
// https://situsku.com/index.php/user/profile
```

<a id="asset"></a>

### asset

The `asset` function generates a URL for an asset using
the current scheme of the request (HTTP or HTTPS):

```php
$url = asset('css/style.css');
// https://situsku.com/assets/css/style.css

$url = asset('packages/docs/css/style.css');
// https://situsku.com/assets/packages/docs/css/style.css
```

> Assets can be an images, CSS, JavaScript or other files stored
> in the `assets/` folder in the application root.

<a id="action"></a>

### action

The `action` function generates a URL for the given controller action:

```php
// Generate URL to User_Controller's 'index' action
$url = action('user@index');
```

You can also pass parameters to the destination URL:

```php
// Create URL to Budi's profile
$url = action('user@profile', ['budi']);
```

<a id="route"></a>

### route

The `route` function generates a URL for a given [named route](/docs/en/routing#named-route):

```php
// Create a URL to the route named 'profile'.
$url = route('profile');
```

You can also pass parameters to the destination controller's method:

```php
$url = route('profile', [$username]);
```

<a id="redirect"></a>

### redirect

The `redirect` function returns an object of `Redirect` class for redirection purpose:

```php
return redirect($url, $status = 302)

return redirect('/home');
return redirect('/home', 301);
return redirect('https://google.com');

return redirect('/edit')
    ->with('status', 'Failed to update profile!');
    ->with_input()
    ->with_errors($validation);
```

<a id="csrf_field"></a>

### csrf_field

The `csrf_field` function generates an HTML hidden input field containing
the value of the CSRF token:

```php
<?php echo csrf_field() ?>
// <input type="hidden" name="csrf_token" value="Wz5CiADRl2ydbHflMEOFQdoS4bxmd11KlhLNoLmB">
```

<a id="root_namespace"></a>

### root_namespace

The `root_namespace` function takes the root namespace of a class:

```php
$data = root_namespace('System\Database\Facle\Model');
// 'System'
```

<a id="class_basename"></a>

### class_basename

The `class_basename` function returns the class name
of the given class with the class's namespace removed:

```php
$data = class_basename('System\Database\Facle\Model');
// 'Model'
```

<a id="value"></a>

### value

The `value` function returns the value it is given. However, if you pass a closure to the function,
the closure will be executed and its returned value will be returned:

```php
$result = value(true); // true

$result = value(function () { return false; }); // false
```

<a id="view"></a>

### view

The `view` function returns an instance of the `View` class:

```php
return view('user.profile');

return view('user.profile')
    ->with('name', 'Angga');
```

<a id="render"></a>

### render

The `render` function compiles [Blade](/docs/en/views/templating) views into HTML:

```php
// File: views/home.blade.php
@include('partials.header')

<p>Hello {{ $user->name }}</p>

@include('partials.footer')
```

```php
$rendered = render('home');
// <html><head></head><body><p>Hello Budi</p></body></html>
```

<a id="render_each"></a>

### render_each

The `render_each` function compiles the blade view into HTML,
but this function is specifically designed for rendering partial views:

```php
$rendered = render_each('partials.header');
// <html><head></head><body>
```

<a id="yield_content"></a>

### yield_content

The `yield_content` function is the equivalent of the Blade's `@yield` syntax:

```php
$content = yield_content('content');
```

<a id="yield_section"></a>

### yield_section

The `yield_section` function is the equivalent of the Blade's `@show` syntax:

```php
$content = yield_section('nama-section');
```

<a id="section_start"></a>

### section_start

The `yield_start` function is the equivalent of the Blade's `@section()` syntax:

```php
section_start('nama-section');
// Fill in the section content here..
```

<a id="section_stop"></a>

### section_stop

The `yield_stop` function is the equivalent of the Blade's `@endsection` syntax:

```php
section_stop();
```

<a id="get_cli_option"></a>

### get_cli_option

The `get_cli_option` function returns the options given by user on the rakit console:

```bash
# command
php rakit package:install access --verbose=yes
```

```php
$option = get_cli_option('verbose');
// 'yes'

$option = get_cli_option('foo'); // null
$option = get_cli_option('foo', 'bar'); // 'bar'
```

<a id="system_os"></a>

### system_os

The `system_os` function returns your server's operating system:

```php
echo system_os(); // Windows
echo system_os(); // Darwin
echo system_os(); // BSD
echo system_os(); // Linux
echo system_os(); // Unknown
```
