# Input & Cookies

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Introduction](#introduction)
- [Retrieving Input](#retrieving-input)
  - [All Input](#all-input)
  - [A Single Value](#a-single-value)
  - [Only & Except](#only--except)
  - [Typed Input](#typed-input)
  - [Query String](#query-string)
  - [JSON Input](#json-input)
- [Input Presence](#input-presence)
- [File Uploads](#file-uploads)
  - [Retrieving Files](#retrieving-files)
  - [Storing Files](#storing-files)
- [Old Input](#old-input)
- [Modifying Input](#modifying-input)
- [Cookies](#cookies)
  - [Reading Cookies](#reading-cookies)
  - [Setting Cookies](#setting-cookies)
  - [Deleting Cookies](#deleting-cookies)
- [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>
## Introduction

`Input` reads everything the client sent: query string, form fields, JSON body and uploaded
files. It also handles flashed ("old") input for redirect-after-validation flows. `Cookie`
reads and writes encrypted cookies.

When the same key appears in both the body and the query string, the body wins — consistently
across every method.

> `Input` returns raw values; it does not sanitize anything. Escape on output (Blade's `{{ }}`
> does this) and bind values in queries.

<a id="retrieving-input"></a>
## Retrieving Input

<a id="all-input"></a>
<a id="get-all-input"></a>
### All Input

```php
$input = Input::all(); // query + body + files
$input = Input::get(); // query + body, without files
```

Never pass the whole request to a model; pick the fields you accept:

```php
User::create(Input::only('name', 'email', 'password'));
```

<a id="a-single-value"></a>
<a id="get-specific-input"></a>
<a id="get-with-default-value"></a>
### A Single Value

`get()` works the same for any HTTP method. Use dot notation for nested arrays, and pass a
default (or a closure returning one) for missing keys:

```php
$name  = Input::get('name');
$email = Input::get('user.email');   // user[email]
$tag   = Input::get('tags.0');
$page  = Input::get('page', 1);

$name = Input::get('name', function () {
    return 'Guest';
});
```

<a id="only--except"></a>
### Only & Except

Both accept a list of arguments or a single array:

```php
$credentials = Input::only('email', 'password');
$data = Input::except(['_token', '_method']);
```

<a id="typed-input"></a>
### Typed Input

Form values always arrive as strings. These helpers convert them, falling back to a default
when the key is absent or the value does not fit:

```php
Input::string('name');          // '' when absent
Input::string('name', 'Anon');
Input::integer('age');          // 0 when absent or not numeric
Input::integer('age', 17);
Input::float('price');          // 0.0 when absent or not numeric
Input::boolean('agree');        // TRUE only for '1', 'true', 'on', 'yes'
Input::boolean('agree', true);  // default used only when the key is absent
Input::date('born');            // Carbon instance, or NULL when empty/unparseable
Input::date('born', 'd/m/Y');   // parse with an explicit format
Input::arr('tags');             // always an array
Input::collect();               // all input as a Collection
Input::collect('tags');         // one key as a Collection
```

`boolean()` suits checkboxes: an unchecked box sends nothing, a checked one sends `on` or `1`.

```php
$user->subscribed = Input::boolean('newsletter');
```

<a id="query-string"></a>
### Query String

To read only from the query string:

```php
// /search?q=keyword&page=2
$q      = Input::query('q');        // 'keyword'
$page   = Input::query('page', 1);  // '2' (strings)
$params = Input::query();           // all query parameters
```

<a id="json-input"></a>
### JSON Input

For JSON requests the decoded body is already part of `get()`, `all()` and friends. To read the
raw payload directly:

```php
$data = Input::json();      // array
$data = Input::json(true);  // object
```

<a id="input-presence"></a>
<a id="check-input-exists"></a>
<a id="check-input-filled"></a>
<a id="check-input-empty"></a>
## Input Presence

A value counts as present when it is not `null`, not an empty/whitespace string and not an
empty array.

```php
Input::has('email');                      // present
Input::filled('name', 'email');           // all present (also takes an array)
Input::unfilled('name', 'email');         // at least one is not present
Input::any_filled(['phone', 'email']);    // at least one present
Input::missing('coupon');                 // absent or empty
Input::missing(['coupon', 'note']);       // all absent or empty
Input::keys();                            // every input key
```

<a id="file-uploads"></a>
<a id="file-upload"></a>
## File Uploads

<a id="retrieving-files"></a>
<a id="get-uploaded-file"></a>
<a id="check-file-exists"></a>
<a id="file-information"></a>
### Retrieving Files

`file()` returns the raw `$_FILES` entry; dot notation reaches its fields:

```php
if (Input::has_file('avatar')) {
    $file = Input::file('avatar');        // ['name', 'type', 'size', 'tmp_name', 'error']
    $size = Input::file('avatar.size');   // bytes
}

$files = Input::file();                   // every uploaded file
```

<a id="storing-files"></a>
<a id="upload-file"></a>
<a id="file-validation"></a>
### Storing Files

`upload()` moves the file and returns its full path, or `FALSE` when nothing was uploaded. The
third argument sets the file name:

```php
$path = Input::upload('avatar', path('storage').'avatars');
$path = Input::upload('avatar', path('storage').'avatars', 'user-1.jpg');
```

The directory is created if it does not exist, and must be inside the application's base,
storage or assets path. Files exceeding the upload size limit, or whose name contains a
script/executable extension (`php`, `phtml`, `phar`, `js`, `html`, `svg`, `sh`, `exe`, ...),
are rejected with an exception. Validate type and size first with the
[validator](/docs/validation#validating-files), e.g. `'avatar' => 'required|image|max:2048'`.

<a id="old-input"></a>
<a id="flash-input"></a>
<a id="retrieve-old-input"></a>
<a id="flash-with-filter"></a>
<a id="clear-old-input"></a>
<a id="redirect-with-old-input"></a>
## Old Input

Flash input to the session so a form can be refilled after a failed validation. Usually you do
it through the redirect:

```php
return Redirect::back()->with_input()->with_errors($validation);
return Redirect::back()->with_input('except', ['password']);
```

Or directly:

```php
Input::flash();                              // all input
Input::flash('only', ['name', 'email']);
Input::flash('except', ['password']);
Input::flush();                              // clear flashed input
```

Read it back on the next request:

```php
Input::old('name');
Input::old('country', 'ID');
Input::had('email');   // was it present in the old input?
```

```blade
<input type="text" name="name" value="{{ Input::old('name') }}">
```

<a id="modifying-input"></a>
<a id="input-manipulation"></a>
<a id="merge-input"></a>
<a id="replace-input"></a>
<a id="clear-input"></a>
## Modifying Input

```php
Input::merge(['active' => 1]);                 // add or overwrite keys
Input::replace(['name' => 'New', 'email' => 'new@example.com']);  // replace all body input
Input::clear();                                // remove all body input
```

<a id="cookies"></a>
## Cookies

Cookie values are encrypted and bound to the cookie name, so a tampered value, or one copied
from another cookie, reads as `null`.

<a id="reading-cookies"></a>
<a id="get-cookie"></a>
### Reading Cookies

```php
$theme = Cookie::get('theme');
$theme = Cookie::get('theme', 'light');

if (Cookie::has('theme')) {
    // ...
}
```

<a id="setting-cookies"></a>
<a id="set-cookie"></a>
<a id="forever-cookie"></a>
### Setting Cookies

```php
Cookie::put($name, $value, $minutes = 0, $path = '/', $domain = null, $secure = false, $samesite = 'lax');
```

`$minutes = 0` makes a session cookie. `$samesite` is `lax`, `strict` or `none` (`null` uses
`session.samesite`).

```php
Cookie::put('theme', 'dark', 60);
Cookie::put('theme', 'dark', 60, '/', '.example.com');
Cookie::put('theme', 'dark', 60, '/', null, true);            // HTTPS only
Cookie::put('theme', 'dark', 60, '/', null, false, 'strict');

Cookie::forever('remember', $token);   // 5 years (2628000 minutes)
```

> Cookies are always `HttpOnly`. The value must be a string. The name may only contain
> letters, digits, `_`, `.` and `-`; the path and domain must not contain separators, spaces
> or line breaks; and a `$secure` cookie throws an exception when set over plain HTTP.

<a id="deleting-cookies"></a>
<a id="delete-cookie"></a>
### Deleting Cookies

```php
Cookie::forget('theme');   // takes the same path/domain/secure/samesite arguments as put()
Cookie::flush();           // discard every cookie queued in this request
```

<a id="example"></a>
<a id="practical-examples"></a>
## Example

```php
Route::post('products', function () {
    $validation = Validator::make(Input::all(), [
        'name'  => 'required',
        'price' => 'required|numeric',
        'image' => 'required|image|max:2048',
    ]);

    if ($validation->fails()) {
        return Redirect::back()->with_input('except', ['image'])->with_errors($validation);
    }

    Product::create([
        'name'  => Input::string('name'),
        'price' => Input::float('price'),
        'image' => Input::upload('image', path('storage').'products'),
    ]);

    return Redirect::to('products')->with('message', 'Product created!');
});
```
