# Helpers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [List of Helpers](#list-of-helpers)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Rakit includes various 'helper' functions that can be accessed globally.
Many of these functions are also used within the Rakit system;
and of course, you can also use them in your application if needed.

<a id="list-of-helpers"></a>

## List of Helpers

Below is a list of built-in helpers available:

|                                 |                                   |                                   |                                   |                                   |                                   |
| ------------------------------- | --------------------------------- | --------------------------------- | --------------------------------- | --------------------------------- | --------------------------------- |
| [e](#e)                         | [dd](#dd)                         | [dump](#dump)                     | [bd](#bd)                         | [\_\_ & trans](#__trans)          | [is_cli](#is_cli)                 |
| [data_fill](#data_fill)         | [data_get](#data_get)             | [data_set](#data_set)             | [retry](#retry)                   | [facile_to_json](#facile_to_json) | [head](#head)                     |
| [last](#last)                   | [url](#url)                       | [asset](#asset)                   | [action](#action)                 | [route](#route)                   | [redirect](#redirect)             |
| [back](#back)                   | [old](#old)                       | [csrf_name](#csrf_name)           | [csrf_token](#csrf_token)         | [csrf_field](#csrf_field)         | [root_namespace](#root_namespace) |
| [class_basename](#class_basename) | [value](#value)                 | [view](#view)                     | [render](#render)                 | [render_each](#render_each)       | [yield_content](#yield_content)   |
| [yield_section](#yield_section) | [section_start](#section_start)   | [section_stop](#section_stop)     | [section_inject](#section_inject) | [get_cli_option](#get_cli_option) | [has_cli_flag](#has_cli_flag)     |
| [system_os](#system_os)         | [config](#config)                 | [cache](#cache)                   | [session](#session)               | [collect](#collect)               | [fake](#fake)                     |
| [validate](#validate)           | [abort](#abort)                   | [abort_if](#abort_if)             | [encrypt](#encrypt)               | [decrypt](#decrypt)               | [bcrypt](#bcrypt)                 |
| [dispatch](#dispatch)           | [blank](#blank)                   | [filled](#filled)                 | [now](#now)                       | [tap](#tap)                       | [optional](#optional)             |
| [when](#when)                   | [human_filesize](#human_filesize) |

<a id="e"></a>

### e

The `e` function performs HTML entity escaping using [htmlentities](https://php.net/htmlentities) with UTF-8 encoding:

```php
echo e('<html>foo</html>');
// &lt;html&gt;foo&lt;/html&gt;

echo e('<script>alert("XSS")</script>');
// &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
```

This function is very useful for preventing XSS (Cross-Site Scripting) attacks.

<a id="dd"></a>

### dd

The `dd` function will dump the contents of a variable and stop script execution:

```php
dd($value);

dd($value1, $value2, $value3, ...);
```

<a id="dump"></a>

### dump

The `dump` function will dump the contents of a variable but script execution will continue:

```php
dump($value);

dump($value1, $value2, $value3, ...);
```

<a id="bd"></a>

### bd

The `bd` function will dump the contents of a variable to the [debug bar](/docs/en/debugging#debug-bar).
Script execution will continue:

```php
bd($value);

bd($value, 'Breakpoint 1');
```

<a id="__trans"></a>

### \_\_ & trans

The `__` and `trans` functions translate strings based on data from language files in the `application/language/` folder:

```php
echo __('marketing.welcome');
echo trans('marketing.welcome');

// With replacement
echo __('marketing.hello', ['name' => 'Budi']);
echo trans('marketing.hello', ['name' => 'Budi']);

// With specific language
echo __('marketing.welcome', [], 'en');
echo trans('marketing.welcome', [], 'en');
```

> **Note:** The `__` function is an alias for `trans`.

<a id="is_cli"></a>

### is_cli

The `is_cli` function checks if the script is running from the console:

```php
if (is_cli()) {
    // Request comes from command line!
}
```

<a id="data_fill"></a>

### data_fill

The `data_fill` function fills missing values in a nested array using "dot" notation:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_fill($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 100]]]

data_fill($data, 'products.desk.discount', 10);
// ['products' => ['desk' => ['price' => 100, 'discount' => 10]]]
```

This function also accepts the `*` (asterisk) character as a wildcard:

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

The `data_get` function also accepts a default value, which will be returned if the requested key is not found:

```php
$discount = data_get($data, 'products.desk.discount', 0);
// 0
```

This function also accepts the `*` (asterisk) character as a wildcard:

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

The `data_set` function sets a value in a nested array or object using "dot" notation:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 200]]]
```

This function also accepts the `*` (asterisk) character as a wildcard:

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

By default, all existing values will be overwritten. If you only want to set the value if it doesn't exist, pass `false` to the fourth parameter:

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200, $overwrite = false);
// ['products' => ['desk' => ['price' => 100]]]
```

<a id="retry"></a>

### retry

The `retry` function attempts to execute a callback for the given number of attempts.
If the callback executes successfully without an exception, the result is returned.

If an exception occurs, the function will automatically retry until the attempts are exhausted:

```php
// Retry 5 times with 100ms delay between attempts
return retry(5, function () {
    // Operation that might fail (e.g., HTTP request)
    return file_get_contents('https://api.example.com/data');
}, 100);
```

#### Retry with specific conditions:

```php
// Only retry for specific exceptions
return retry(3, function () {
    // Your code here
}, 100, function ($exception) {
    // Only retry for specific exceptions
    return $exception instanceof ConnectionException;
});
```

Parameters:
- `$times` - Maximum number of attempts
- `$callback` - Callback to execute
- `$sleep_ms` - Delay time between attempts (milliseconds)
- `$when` - Optional callback to determine when to retry

<a id="facile_to_json"></a>

### facile_to_json

The `facile_to_json` function converts a Facile model object to a JSON string:

```php
// Single model
$json = facile_to_json(User::find(1));
// {"id":1,"name":"Budi","email":"budi@example.com"}

// Multiple models
$json = facile_to_json(User::all());
// [{"id":1,"name":"Budi"}, {"id":2,"name":"Ani"}]

// With JSON options
$json = facile_to_json(User::all(), JSON_PRETTY_PRINT);
```

<a id="head"></a>

### head

The `head` function returns the first element of an array:

```php
$array = [100, 200, 300];

$first = head($array);
// 100

$array = ['name' => 'Budi', 'age' => 25];
$first = head($array);
// 'Budi'
```

<a id="last"></a>

### last

The `last` function returns the last element of an array:

```php
$array = [100, 200, 300];

$last = last($array);
// 300

$array = ['name' => 'Budi', 'age' => 25];
$last = last($array);
// 25
```

<a id="url"></a>

### url

The `url` function generates a complete URL to the given path:

```php
$url = url('user/profile');
// https://example.com/index.php/user/profile

$url = url('/');
// https://example.com/

$url = url();
// https://example.com/index.php
```

<a id="asset"></a>

### asset

The `asset` function generates a URL to an asset:

```php
$url = asset('css/style.css');
// https://example.com/assets/css/style.css

$url = asset('js/app.js');
// https://example.com/assets/js/app.js

$url = asset('images/logo.png');
// https://example.com/assets/images/logo.png

// Assets from package
$url = asset('packages/docs/css/style.css');
// https://example.com/assets/packages/docs/css/style.css
```

> Assets can be images, CSS, JavaScript, or other files stored in the `assets/` folder in the application root.

<a id="action"></a>

### action

The `action` function generates a URL to an action owned by a controller:

```php
// URL to 'index' action owned by User_Controller
$url = action('user@index');
// https://example.com/index.php/user/index

// URL to action with parameter
$url = action('user@profile', ['budi']);
// https://example.com/index.php/user/profile/budi

// URL to action with multiple parameters
$url = action('post@show', ['2024', '01', 'my-post']);
// https://example.com/index.php/post/show/2024/01/my-post
```

<a id="route"></a>

### route

The `route` function generates a URL to a [named route](/docs/en/routing#named-route):

```php
// URL to route named 'profile'
$url = route('profile');
// https://example.com/index.php/profile

// URL with parameter
$url = route('profile', ['budi']);
// https://example.com/index.php/profile/budi

// URL with multiple parameters
$url = route('post.show', ['2024', 'my-post']);
// https://example.com/index.php/posts/2024/my-post
```

<a id="redirect"></a>

### redirect

The `redirect` function returns a redirect response:

```php
// Simple redirect
return redirect('/home');

// Redirect with status code
return redirect('/home', 301);

// Redirect to external URL
return redirect('https://google.com');

// Redirect with flash data
return redirect('/edit')
    ->with('status', 'Profile update failed!')
    ->with_input()
    ->with_errors($validation);

// Redirect with session data
return redirect('/dashboard')
    ->with('message', 'Login successful!');
```

<a id="back"></a>

### back

The `back` function creates a redirect to the previous page:

```php
// Redirect to previous page
return back();

// With status code
return back(301);

// With flash data
return back()->with('error', 'Data is invalid!');
```

<a id="old"></a>

### old

The `old` function retrieves old input from session (useful after validation error):

```php
// In form, display old input if available
<input type="text" name="name" value="<?php echo old('name') ?>">
<input type="email" name="email" value="<?php echo old('email') ?>">

// With default value
<input type="text" name="city" value="<?php echo old('city', 'Jakarta') ?>">
```

<a id="csrf_name"></a>

### csrf_name

The `csrf_name` function returns the name of the CSRF token field:

```php
$name = csrf_name();
// 'csrf_token'
```

<a id="csrf_token"></a>

### csrf_token

The `csrf_token` function returns the current CSRF token value:

```php
$token = csrf_token();
// 'Wz5CiADRl2ydbHflMEOFQdoS4bxmd11KlhLNoLmB'
```

<a id="csrf_field"></a>

### csrf_field

The `csrf_field` function generates a hidden input field containing the CSRF token:

```php
<form method="POST" action="/user/profile">
    <?php echo csrf_field() ?>
    <!-- Form fields -->
</form>

// Output:
// <input type="hidden" name="csrf_token" value="Wz5CiADRl2ydbHflMEOFQdoS4bxmd11KlhLNoLmB">
```

<a id="root_namespace"></a>

### root_namespace

The `root_namespace` function retrieves the root namespace from a class:

```php
$namespace = root_namespace('System\Database\Facile\Model');
// 'System'

$namespace = root_namespace('App\Controllers\UserController');
// 'App'

// Returns null if no namespace
$namespace = root_namespace('User');
// null
```

<a id="class_basename"></a>

### class_basename

The `class_basename` function retrieves the class name without namespace:

```php
$name = class_basename('System\Database\Facile\Model');
// 'Model'

$name = class_basename('App\Controllers\UserController');
// 'UserController'

// Can also accept object
$user = new User();
$name = class_basename($user);
// 'User'
```

<a id="value"></a>

### value

The `value` function returns the given value. If the value is a Closure,
the result of executing the closure is returned:

```php
$result = value(true);
// true

$result = value(function () {
    return 'Hello World';
});
// 'Hello World'

// Useful for expensive default values
$result = value(function () {
    return expensive_operation();
});
```

<a id="view"></a>

### view

The `view` function returns an instance of `View`:

```php
// Simple view
return view('user.profile');

// View with data
return view('user.profile', ['name' => 'Budi', 'age' => 25]);

// View with method chaining
return view('user.profile')
    ->with('name', 'Budi')
    ->with('age', 25);
```

<a id="render"></a>

### render

The `render` function compiles a [Blade](/docs/en/views/templating) view into an HTML string:

```php
// File: views/home.blade.php
// @include('partials.header')
// <p>Hello {{ $name }}</p>
// @include('partials.footer')

$html = render('home', ['name' => 'Budi']);
// '<html><head></head><body><p>Hello Budi</p></body></html>'

// Render view without data
$html = render('emails.welcome');
```

<a id="render_each"></a>

### render_each

The `render_each` function renders a partial view for each item in an array:

```php
// File: views/partials/user_item.blade.php
// <li>{{ $user->name }} - {{ $user->email }}</li>

$users = User::all();
$html = render_each('partials.user_item', $users, 'user');

// Output:
// <li>Budi - budi@example.com</li>
// <li>Ani - ani@example.com</li>
// <li>Citra - citra@example.com</li>

// With empty view
$html = render_each('partials.user_item', $users, 'user', 'partials.no_users');
```

Parameters:
- `$partial` - Name of the partial view
- `$data` - Array of data to loop through
- `$iterator` - Name of the variable for each item
- `$empty` - View to display if data is empty (optional)

<a id="yield_content"></a>

### yield_content

The `yield_content` function retrieves content from a section (equivalent to Blade syntax `@yield`):

```php
// In layout
<div class="content">
    <?php echo yield_content('content') ?>
</div>

// In view
<?php section_start('content') ?>
    <p>This is the content</p>
<?php section_stop() ?>
```

<a id="yield_section"></a>

### yield_section

The `yield_section` function stops the section and immediately displays the content (equivalent to Blade syntax `@show`):

```php
<?php section_start('sidebar') ?>
    <div class="sidebar">
        <p>Default sidebar content</p>
    </div>
<?php echo yield_section() ?>
```

<a id="section_start"></a>

### section_start

The `section_start` function starts a section (equivalent to Blade syntax `@section`):

```php
<?php section_start('content') ?>
    <h1>Page Title</h1>
    <p>Page content here</p>
<?php section_stop() ?>

// With direct content
<?php section_start('title', 'Page Title') ?>
```

<a id="section_stop"></a>

### section_stop

The `section_stop` function stops a section (equivalent to Blade syntax `@endsection` or `@stop`):

```php
<?php section_start('content') ?>
    <p>Section content</p>
<?php section_stop() ?>
```

<a id="section_inject"></a>

### section_inject

The `section_inject` function injects content into a section:

```php
// Inject content into section
section_inject('scripts', '<script src="app.js"></script>');

// In layout
<head>
    <?php echo yield_content('scripts') ?>
</head>
```

<a id="get_cli_option"></a>

### get_cli_option

The `get_cli_option` function retrieves an option from the Rakit console command:

```bash
# Command
php rakit package:install access --verbose=yes --timeout=30
```

```php
$verbose = get_cli_option('verbose');
// 'yes'

$timeout = get_cli_option('timeout');
// '30'

// With default value
$option = get_cli_option('foo', 'default');
// 'default'
```

<a id="has_cli_flag"></a>

### has_cli_flag

The `has_cli_flag` function checks if a flag is provided to the Rakit console:

```bash
# Command
php rakit migrate -v --force
```

```php
if (has_cli_flag('v')) {
    // -v flag exists
}

if (has_cli_flag('force')) {
    // --force flag exists
}

if (has_cli_flag('silent')) {
    // false - flag does not exist
}
```

<a id="system_os"></a>

### system_os

The `system_os` function returns the server operating system:

```php
echo system_os();
// Possible values:
// - 'Windows' - Windows OS
// - 'Darwin' - macOS
// - 'BSD' - FreeBSD, OpenBSD, NetBSD, DragonFly
// - 'Linux' - Linux distributions
// - 'Solaris' - Solaris/SunOS
// - 'Unknown' - Unknown OS

// Example usage
if (system_os() === 'Windows') {
    // Special logic for Windows
}
```

<a id="config"></a>

### config

The `config` function retrieves or sets configuration:

```php
// Retrieve config
$language = config('application.language');
// 'id'

$timezone = config('application.timezone');
// 'Asia/Jakarta'

// With default value
$value = config('app.debug', false);

// Set config
config(['application.language' => 'en']);
config(['app.debug' => true]);
```

<a id="cache"></a>

### cache

The `cache` function retrieves or sets cache:

```php
// Retrieve cache
$users = cache('users');

// With default value
$value = cache('settings', []);

// Set cache
cache(['users' => $users]);
cache(['settings' => $settings]);
```

<a id="session"></a>

### session

The `session` function retrieves or sets session:

```php
// Retrieve session
$userId = session('user_id');
$username = session('username');

// With default value
$role = session('role', 'guest');

// Set session
session(['user_id' => 1]);
session(['username' => 'budi', 'role' => 'admin']);
```

<a id="collect"></a>

### collect

The `collect` function creates a Collection instance from an array:

```php
$collection = collect([1, 2, 3, 4, 5]);

$filtered = $collection->filter(function ($value) {
    return $value > 2;
});
// [3, 4, 5]

$mapped = $collection->map(function ($value) {
    return $value * 2;
});
// [2, 4, 6, 8, 10]

// Collection from associative array
$users = collect([
    ['name' => 'Budi', 'age' => 25],
    ['name' => 'Ani', 'age' => 30],
]);

$names = $users->pluck('name');
// ['Budi', 'Ani']
```

<a id="fake"></a>

### fake

The `fake` function creates a Faker instance for generating fake data:

```php
// Using default locale
$name = fake()->name;
// 'Budi Santoso'

$email = fake()->email;
// 'budi@example.com'

$address = fake()->address;
// 'Jl. Sudirman No. 123, Jakarta'

// Using specific locale
$name = fake('en')->name;
// 'John Doe'

$name = fake('ja')->name;
// 'Tanaka Taro'

// Generate data for testing
$user = [
    'name' => fake()->name,
    'email' => fake()->email,
    'phone' => fake()->phoneNumber,
    'address' => fake()->address,
];
```

<a id="validate"></a>

### validate

The `validate` function creates a Validator instance:

```php
// Simple validation
$validator = validate($_POST, [
    'name' => 'required|max:100',
    'email' => 'required|email',
    'age' => 'required|numeric|min:18',
]);

if ($validator->fails()) {
    return back()->with_errors($validator);
}

// With custom error messages
$validator = validate($_POST, [
    'email' => 'required|email',
], [
    'email.required' => 'Email is required!',
    'email.email' => 'Email format is invalid!',
]);

// Direct validation
$attributes = ['email' => 'invalid'];
$rules = ['email' => 'required|email'];
$validator = validate($attributes, $rules);

if ($validator->invalid()) {
    // Validation failed
}
```

<a id="abort"></a>

### abort

The `abort` function stops execution and displays an error page:

```php
// Abort with 404
abort(404);

// Abort with 403
abort(403);

// Abort with 500
abort(500);

// Abort with custom headers
abort(403, ['X-Custom-Header' => 'value']);

// In controller
public function action_show($id)
{
    $post = Post::find($id);

    if (!$post) {
        abort(404); // Automatically display 404 error page
    }

    return View::make('post.show', compact('post'));
}
```

<a id="abort_if"></a>

### abort_if

The `abort_if` function aborts if the condition is true:

```php
// Abort if user is not admin
abort_if(!Auth::user()->is_admin, 403);

// Abort if post not found
abort_if(is_null($post), 404);

// Abort if not authorized
abort_if($user->id !== $post->user_id, 403);

// With custom headers
abort_if($condition, 403, ['X-Reason' => 'Unauthorized']);
```

<a id="encrypt"></a>

### encrypt

The `encrypt` function encrypts a string:

```php
// Encrypt data
$encrypted = encrypt('sensitive data');
// 'eyJpdiI6IjdqY...'

// Encrypt array or object
$encrypted = encrypt(['password' => 'secret123']);

// Encrypt for storing in database
$user->token = encrypt($apiToken);
$user->save();
```

<a id="decrypt"></a>

### decrypt

The `decrypt` function decrypts an encrypted string:

```php
// Decrypt data
$decrypted = decrypt($encrypted);
// 'sensitive data'

// Decrypt from database
$apiToken = decrypt($user->token);

// Handle decryption error
try {
    $data = decrypt($encrypted);
} catch (Exception $e) {
    // Decryption failed
}
```

<a id="bcrypt"></a>

### bcrypt

The `bcrypt` function creates a password hash using bcrypt:

```php
// Hash password
$hashed = bcrypt('password123');
// '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'

// Store in database
$user = new User;
$user->password = bcrypt(Input::get('password'));
$user->save();

// Verify password
if (Hash::check(Input::get('password'), $user->password)) {
    // Password is correct
}
```

<a id="dispatch"></a>

### dispatch

The `dispatch` function sends an event (alias for `Event::fire()`):

```php
// Dispatch simple event
dispatch('user.login', [$user]);

// Dispatch multiple events
dispatch(['user.login', 'log.activity'], [$user]);

// Dispatch with halt (stop after first listener)
$result = dispatch('user.verify', [$user], true);

// Example with listener
Event::listen('user.login', function ($user) {
    Log::info('User logged in: ' . $user->email);
});

dispatch('user.login', [$user]);
```

<a id="blank"></a>

### blank

The `blank` function checks if a value is "empty":

```php
blank(null); // true
blank(''); // true
blank('   '); // true (whitespace)
blank([]); // true
blank(collect()); // true

blank(0); // false
blank(false); // false
blank('hello'); // false
blank([1, 2, 3]); // false

// Example usage
if (blank($request->input('name'))) {
    return 'Name is required';
}
```

<a id="filled"></a>

### filled

The `filled` function checks if a value is "filled" (opposite of `blank`):

```php
filled('hello'); // true
filled([1, 2, 3]); // true
filled(0); // true
filled(false); // true

filled(null); // false
filled(''); // false
filled('   '); // false
filled([]); // false

// Example usage
if (filled($request->input('email'))) {
    // Process email
}
```

<a id="now"></a>

### now

The `now` function returns a Carbon instance for the current date/time:

```php
// Get current datetime
$now = now();
// Carbon instance

echo $now;
// '2024-01-15 10:30:00'

echo $now->format('Y-m-d');
// '2024-01-15'

echo $now->toDateTimeString();
// '2024-01-15 10:30:00'

// With timezone
$now = now('Asia/Tokyo');

// Date operations
$tomorrow = now()->addDay();
$nextWeek = now()->addWeek();
$yesterday = now()->subDay();
```

<a id="tap"></a>

### tap

The `tap` function calls a callback with a value and returns the value:

```php
// Tap for debugging
$user = tap(User::find(1), function ($user) {
    Log::info('User found: ' . $user->name);
});

// Tap for modifying object
$user = tap(new User, function ($user) {
    $user->name = 'Budi';
    $user->email = 'budi@example.com';
})->save();

// Useful for method chaining
$result = tap($value, function ($value) {
    // Do something with $value
})->someMethod();
```

<a id="optional"></a>

### optional

The `optional` function accesses properties/methods from an object that may be null:

```php
// Without optional (may error if null)
// $name = $user->profile->name; // Error if $user null

// With optional (safe)
$name = optional($user)->name;
// null if $user null

$email = optional($user)->email;
// null if $user null

// With callback
$value = optional($user, function ($user) {
    return $user->profile->name;
});

// Nested optional
$city = optional(optional($user)->profile)->city;

// Practical example
$userName = optional(Auth::user())->name ?: 'Guest';
```

<a id="when"></a>

### when

The `when` function returns a value if the condition is true:

```php
// Conditional value
$result = when(true, 'yes', 'no');
// 'yes'

$result = when(false, 'yes', 'no');
// 'no'

// With closure
$result = when($user->isAdmin(), function () {
    return 'Admin Panel';
}, function () {
    return 'User Panel';
});

// Without default (returns null if false)
$result = when($condition, 'value');

// Condition as closure
$result = when(function () {
    return expensive_check();
}, 'value if true', 'value if false');
```

<a id="human_filesize"></a>

### human_filesize

The `human_filesize` function formats file size into human-readable format:

```php
echo human_filesize(1024);
// '1.00 KB'

echo human_filesize(1048576);
// '1.00 MB'

echo human_filesize(1073741824);
// '1.00 GB'

echo human_filesize(1234567890);
// '1.15 GB'

// With custom precision
echo human_filesize(1234567890, 0);
// '1 GB'

echo human_filesize(1234567890, 3);
// '1.150 GB'

// Example usage
$fileSize = filesize('path/to/file.pdf');
echo 'File size: ' . human_filesize($fileSize);
// 'File size: 2.45 MB'
```
