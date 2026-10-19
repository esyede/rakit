# Helpers

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Available Helpers](#available-helpers)
-   [Debugging](#debugging)
-   [Arrays & Data](#arrays--data)
-   [Strings & Classes](#strings--classes)
-   [URLs & Redirects](#urls--redirects)
-   [Views & Sections](#views--sections)
-   [Security](#security)
-   [Services](#services)
-   [Errors](#errors)
-   [Console](#console)
-   [Miscellaneous](#miscellaneous)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

Rakit ships a set of global helper functions. The framework uses them itself, and so can your application.

<a id="available-helpers"></a>
<a id="list-of-helpers"></a>

## Available Helpers

| Group | Helpers |
| ----- | ------- |
| Debugging | [bd](#bd) [dd](#dd) [dump](#dump) [measure](#measure) |
| Arrays & Data | [collect](#collect) [data_fill](#data_fill) [data_get](#data_get) [data_set](#data_set) [facile_to_json](#facile_to_json) [head](#head) [last](#last) |
| Strings & Classes | [\_\_ & trans](#__trans) [class_basename](#class_basename) [e](#e) [human_filesize](#human_filesize) [root_namespace](#root_namespace) |
| URLs & Redirects | [action](#action) [asset](#asset) [back](#back) [old](#old) [redirect](#redirect) [route](#route) [url](#url) |
| Views & Sections | [render](#render) [render_each](#render_each) [section_inject](#section_inject) [section_start](#section_start) [section_stop](#section_stop) [view](#view) [yield_content](#yield_content) [yield_section](#yield_section) |
| Security | [bcrypt](#bcrypt) [csrf_field](#csrf_field) [csrf_name](#csrf_name) [csrf_token](#csrf_token) [decrypt](#decrypt) [encrypt](#encrypt) |
| Services | [cache](#cache) [config](#config) [dispatch](#dispatch) [fake](#fake) [now](#now) [session](#session) [validate](#validate) |
| Errors | [abort](#abort) [abort_if](#abort_if) |
| Console | [get_cli_option](#get_cli_option) [has_cli_flag](#has_cli_flag) [is_cli](#is_cli) [system_os](#system_os) |
| Miscellaneous | [blank](#blank) [filled](#filled) [optional](#optional) [retry](#retry) [tap](#tap) [value](#value) [when](#when) |

<a id="debugging"></a>

## Debugging

<a id="bd"></a>

### bd

Dumps a variable to the [debug bar](/docs/debugging#debug-bar), with an optional title. Execution continues.

```php
bd($value, 'Breakpoint 1');
```

<a id="dd"></a>

### dd

Dumps one or more variables and stops the script (outside production mode).

```php
dd($value1, $value2);
```

<a id="dump"></a>

### dump

Like `dd`, but execution continues. On a web request with the debug bar enabled (and not in production mode), the dump goes to the debug bar.

```php
dump($value1, $value2);
```

<a id="measure"></a>

### measure

Times a closure, records it on the debug bar Timeline, and returns the closure's result. Use `start_measure` / `stop_measure` with the same name for code that does not fit in one closure.

```php
$users = measure('load users', function () {
    return DB::table('users')->get();
});

start_measure('report');
// ...
stop_measure('report', 'Monthly report');
```

> Measurements are only recorded outside production mode, so the calls are safe to leave in place.

<a id="arrays--data"></a>

## Arrays & Data

<a id="collect"></a>

### collect

Creates a [Collection](/docs/collection) from the given value.

```php
collect([1, 2, 3])->map(function ($v) { return $v * 2; })->all();
// [2, 4, 6]
```

<a id="data_fill"></a>

### data_fill

Sets a value in a nested array/object using "dot" notation, only where it is missing. Supports the `*` wildcard.

```php
$data = ['products' => [['price' => 100], []]];

data_fill($data, 'products.*.price', 200);
// ['products' => [['price' => 100], ['price' => 200]]]
```

<a id="data_get"></a>

### data_get

Gets a value from a nested array/object using "dot" notation, with an optional default. Supports the `*` wildcard.

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_get($data, 'products.desk.price');       // 100
data_get($data, 'products.desk.discount', 0); // 0

data_get([['name' => 'Desk 1'], ['name' => 'Desk 2']], '*.name');
// ['Desk 1', 'Desk 2']
```

<a id="data_set"></a>

### data_set

Sets a value in a nested array/object using "dot" notation, overwriting existing values. Supports the `*` wildcard. Pass `false` as the fourth argument to keep existing values.

```php
$data = ['products' => ['desk' => ['price' => 100]]];

data_set($data, 'products.desk.price', 200);
// ['products' => ['desk' => ['price' => 200]]]

data_set($data, 'products.desk.price', 300, false);
// unchanged: price stays 200
```

<a id="facile_to_json"></a>

### facile_to_json

Converts a Facile model, or a plain array of models, to JSON. The second argument takes `json_encode()` options.

```php
facile_to_json(User::find(1));
// {"id":1,"name":"Budi"}

facile_to_json(User::all()->all(), JSON_PRETTY_PRINT); // a Collection must be turned into an array
```

<a id="head"></a>

### head

Returns the first element of an array.

```php
head([100, 200, 300]); // 100
```

<a id="last"></a>

### last

Returns the last element of an array.

```php
last([100, 200, 300]); // 300
```

<a id="strings--classes"></a>

## Strings & Classes

<a id="__trans"></a>

### \_\_ & trans

Translates a key from the language files in `application/language/`. `__` is an alias for `trans`. Optional arguments are replacements and the language. Both return the translated string (an array for a group key), not a `Lang` object.

```php
echo trans('marketing.welcome');
echo __('marketing.hello', ['name' => 'Budi']);
echo __('marketing.welcome', [], 'en');
```

<a id="class_basename"></a>

### class_basename

Returns the class name without its namespace. Accepts a string or an object.

```php
class_basename('System\Database\Facile\Model'); // 'Model'
class_basename(new User());                    // 'User'
```

<a id="e"></a>

### e

Escapes HTML entities (`htmlentities`, UTF-8, `ENT_QUOTES`). Use it on untrusted text to prevent XSS. `Htmlable` objects are returned as-is.

```php
echo e('<script>alert("XSS")</script>');
// &lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;
```

<a id="human_filesize"></a>

### human_filesize

Formats a byte count as a readable size. The second argument is the precision (default `2`).

```php
human_filesize(1024);          // '1.00 KB'
human_filesize(1234567890);    // '1.15 GB'
human_filesize(1234567890, 0); // '1 GB'
```

<a id="root_namespace"></a>

### root_namespace

Returns the first segment of a namespaced class name, or `null` when there is none.

```php
root_namespace('System\Database\Facile\Model'); // 'System'
root_namespace('User');                         // null
```

<a id="urls--redirects"></a>

## URLs & Redirects

<a id="action"></a>

### action

Generates a URL to a controller action.

```php
action('user@profile', ['budi']);
// https://example.com/index.php/user/profile/budi
```

<a id="asset"></a>

### asset

Generates a URL to a file in the `assets/` folder.

```php
asset('css/style.css');
// https://example.com/assets/css/style.css
```

<a id="back"></a>

### back

Redirects to the previous page. When the `Referer` header is missing or points to another host, it goes to the fallback URL (default `/`).

```php
return back();
return back(302, '/dashboard');
return back()->with('error', 'Data is invalid!');
```

Other redirects and responses live on the `Redirect` and `Response` classes:

```php
Redirect::away('https://example.test/page'); // external URL
Redirect::secure('/checkout');               // force https
Redirect::refresh();                         // reload the current URL
Redirect::guest('/login');                   // remember the target, then go to login
Redirect::intended('/dashboard');            // after login, go to the remembered target

return Response::no_content();                              // 204
return Response::file(path('storage') . 'invoice.pdf');     // show inline in the browser
```

`guest()` and `intended()` need a configured session driver.

<a id="old"></a>

### old

Gets old input flashed to the session, for refilling a form after a validation error.

```php
<input name="city" value="<?php echo old('city', 'Jakarta') ?>">
```

<a id="redirect"></a>

### redirect

Returns a redirect response to a local URL, with an optional status code. External URLs throw an exception, so use `Redirect::away()` for them.

```php
return redirect('/home', 301);

return redirect('/edit')
    ->with('status', 'Profile update failed!')
    ->with_input()
    ->with_errors($validation);
```

<a id="route"></a>

### route

Generates a URL to a [named route](/docs/routing#named-routes).

```php
route('post.show', ['2024', 'my-post']);
// https://example.com/index.php/posts/2024/my-post
```

<a id="url"></a>

### url

Generates a full URL to the given path.

```php
url('user/profile'); // https://example.com/index.php/user/profile
url();               // https://example.com/index.php/
```

<a id="views--sections"></a>

## Views & Sections

<a id="render"></a>

### render

Renders a [Blade](/docs/views/templating) view to an HTML string.

```php
$html = render('home', ['name' => 'Budi']);
```

<a id="render_each"></a>

### render_each

Renders a partial once per item in a plain array, passing each item under the given variable name. The optional fourth argument is a view to render when the array is empty; prefix it with `raw|` to output a plain string instead.

```php
// views/partials/user_item.blade.php: <li>{{ $user->name }}</li>

echo render_each('partials.user_item', User::all()->all(), 'user', 'raw|No users');
```

<a id="section_inject"></a>

### section_inject

Sets a section's content directly.

```php
section_inject('scripts', '<script src="app.js"></script>');
```

<a id="section_start"></a>

### section_start

Starts a section (Blade `@section`). Pass content as the second argument to fill it in one call.

```php
<?php section_start('content') ?>
    <p>Page content</p>
<?php section_stop() ?>

<?php section_start('title', 'Page Title') ?>
```

<a id="section_stop"></a>

### section_stop

Ends the current section (Blade `@endsection` / `@stop`). See [section_start](#section_start).

<a id="view"></a>

### view

Returns a `View` instance.

```php
return view('user.profile', ['name' => 'Budi'])->with('age', 25);
```

<a id="yield_content"></a>

### yield_content

Returns a section's content (Blade `@yield`).

```php
<div class="content"><?php echo yield_content('content') ?></div>
```

<a id="yield_section"></a>

### yield_section

Ends the current section and returns its content right away (Blade `@show`).

```php
<?php section_start('sidebar') ?>
    <p>Default sidebar</p>
<?php echo yield_section() ?>
```

<a id="security"></a>

## Security

<a id="bcrypt"></a>

### bcrypt

Hashes a password with bcrypt. Check it later with `Hash::check()`.

```php
$user->password = bcrypt(Input::get('password'));

Hash::check(Input::get('password'), $user->password); // true / false
```

<a id="csrf_field"></a>

### csrf_field

Returns a hidden input holding the CSRF token.

```php
<?php echo csrf_field() ?>
// <input type="hidden" name="csrf_token" value="Wz5CiADRl2ydbHfl...">
```

<a id="csrf_name"></a>

### csrf_name

Returns the name of the CSRF token field.

```php
csrf_name(); // 'csrf_token'
```

<a id="csrf_token"></a>

### csrf_token

Returns the current CSRF token, generating one if the session has none.

```php
csrf_token(); // 'Wz5CiADRl2ydbHflMEOFQdoS4bxmd11KlhLNoLmB'
```

<a id="decrypt"></a>

### decrypt

Decrypts a string made by `encrypt`. Throws `System\Exceptions\DecryptException` on failure.

```php
$api_token = decrypt($user->token);
```

<a id="encrypt"></a>

### encrypt

Encrypts a string. Encode arrays/objects (e.g. `json_encode()`) first.

```php
$user->token = encrypt($api_token);
```

<a id="services"></a>

## Services

<a id="cache"></a>

### cache

Gets a cached value (with optional default), or stores values forever when given an array.

```php
$users = cache('users', []);
cache(['users' => $users]); // Cache::forever()
```

<a id="config"></a>

### config

Gets a config value (with optional default), or sets values when given an array.

```php
config('application.timezone');          // 'UTC'
config('debugger.activate', false);
config(['application.language' => 'en']);
```

<a id="dispatch"></a>

### dispatch

Fires one or more events (alias for `Hook::fire()`). Pass `true` as the third argument to return the first non-null listener response.

```php
dispatch('user.login', [$user]);
dispatch(['user.login', 'log.activity'], [$user]);
$result = dispatch('user.verify', [$user], true);
```

<a id="fake"></a>

### fake

Returns a Faker instance. The locale defaults to the `application.language` config; only `en` and `id` are available, others throw `InvalidArgumentException`.

```php
fake()->name;      // 'Budi Santoso'
fake('en')->email; // 'john@example.com'
```

<a id="now"></a>

### now

Returns a Carbon instance for the current time, with an optional timezone.

```php
now()->format('Y-m-d'); // '2024-01-15'
now('Asia/Tokyo')->addDay();
```

<a id="session"></a>

### session

Gets a session value (with optional default), or stores values when given an array.

```php
session('role', 'guest');
session(['user_id' => 1, 'role' => 'admin']);
```

<a id="validate"></a>

### validate

Creates a Validator. The optional third argument holds custom messages.

```php
$validator = validate(Input::all(), [
    'email' => 'required|email',
], [
    'email_required' => 'Email is required!',
]);

if ($validator->fails()) {
    return back()->with_errors($validator);
}
```

<a id="errors"></a>

## Errors

<a id="abort"></a>

### abort

Sends an error response and stops execution. It renders the `error.<code>` view (or `error.unknown`), or JSON when the request wants JSON. Extra headers can be passed.

```php
abort(404);
abort(403, ['X-Custom-Header' => 'value']);
```

<a id="abort_if"></a>

### abort_if

Calls `abort` when the condition is true.

```php
abort_if(is_null($post), 404);
abort_if($user->id !== $post->user_id, 403, ['X-Reason' => 'Unauthorized']);
```

<a id="console"></a>

## Console

<a id="get_cli_option"></a>

### get_cli_option

Gets a `--name=value` option passed to the Rakit console, with an optional default.

```php
// php rakit package:install access --timeout=30

get_cli_option('timeout');        // '30'
get_cli_option('foo', 'default'); // 'default'
```

<a id="has_cli_flag"></a>

### has_cli_flag

Checks whether a `-x` or `--name` flag was passed to the Rakit console. Single-letter flags also match bundles like `-vf`.

```php
// php rakit migrate -v --force

has_cli_flag('v');      // true
has_cli_flag('force');  // true
has_cli_flag('silent'); // false
```

<a id="is_cli"></a>

### is_cli

Checks whether the script runs from the command line.

```php
if (is_cli()) {
    // ...
}
```

<a id="system_os"></a>

### system_os

Returns the server OS: `Windows`, `Darwin`, `BSD`, `Linux`, `Solaris` or `Unknown`.

```php
if (system_os() === 'Windows') {
    // ...
}
```

<a id="miscellaneous"></a>

## Miscellaneous

<a id="blank"></a>

### blank

Checks whether a value is "empty": `null`, a whitespace-only string, or an empty array/Countable. Numbers and booleans are never blank.

```php
blank('   ');     // true
blank(collect()); // true
blank(0);         // false
blank(false);     // false
```

<a id="filled"></a>

### filled

The opposite of `blank`.

```php
filled(0);    // true
filled('');   // false
```

<a id="optional"></a>

### optional

Lets you read properties or call methods on a value that may be `null`, returning `null` instead of failing. With a callback, returns the callback's result instead.

```php
optional($user)->name;                 // null when $user is null
optional(Auth::user())->name ?: 'Guest';

optional($user, function ($user) {
    return $user->profile->name;
});
```

<a id="retry"></a>

### retry

Runs a callback up to `$times` times until it stops throwing, sleeping `$sleep_ms` milliseconds between attempts. The optional fourth callback receives the exception and decides whether to retry. The attempt number is passed to the callback.

```php
return retry(3, function ($attempt) {
    return file_get_contents('https://api.example.com/data');
}, 100, function ($e) {
    return $e instanceof ConnectionException;
});
```

<a id="tap"></a>

### tap

Passes a value to a closure, then returns the value.

```php
$user = tap(new User, function ($user) {
    $user->name = 'Budi';
});
$user->save();
```

<a id="value"></a>

### value

Returns the value, or the result of calling it if it is a closure. Extra arguments are passed to the closure.

```php
value(true);                            // true
value(function () { return 'Hello'; }); // 'Hello'
```

<a id="when"></a>

### when

Returns `$value` if the condition is truthy, otherwise `$default` (`null` if omitted). The condition and both values may be closures.

```php
when(true, 'yes', 'no');  // 'yes'
when(false, 'yes');       // null

when($user->is_admin, function () {
    return 'Admin Panel';
}, 'User Panel');
```
