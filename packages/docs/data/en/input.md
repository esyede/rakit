# Input & Cookies

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Input](#input)
- [JSON Input](#json-input)
- [File](#file)
- [Old Input](#old-input)
- [Redirect With Old Input](#redirect-dengan-old-input)
- [Cookies](#cookies)
- [Merging & Replacing](#merge--replace)
- [Clearing Input](#menghapus-data-input)

<!-- /MarkdownTOC -->


<a id="input"></a>
## Input

The Input component handles input that comes into your application
via `GET`, `POST`, `PUT`, or `DELETE` requests.
Here are some examples of how to access input data using it:


#### Retrieve a value from the input array:

```php
$email = Input::get('email');
```

>  This `get()` method is used for all request types (`GET`, `POST`, `PUT`, and `DELETE`),
not just GET requests.


#### Retrieve all input from the input array:

```php
$input = Input::get();
```


#### Retrieve all input including the `$_FILES` array:

```php
$input = Input::all();
```

By default, `NULL` will be returned if the input item does not exist.


However, you may pass a different default value as a second parameter to the method:


#### Returning a default value if the requested input item doesn't exist:

```php
$name = Input::get('name', 'Anonymous');
```

#### Using a Closure to return a default value:

```php
$name = Input::get('name', function () { return 'Anonymous'; });
```

#### Determining if the input contains a given item:

```php
if (Input::has('name')) {
    // ..
}
```

>  This `has()` method will return `FALSE` if the input item is an empty string.


<a id="json-input"></a>
## JSON Input

When working with JavaScript MVC frameworks, you will need to get
the JSON posted by the application. To make your life easier, you can use this method:

#### Get JSON input to the application:

```php
$data = Input::json();
```


<a id="file"></a>
## File

#### Retrieving all items from the `$_FILES` array:

```php
$files = Input::file();
```

#### Retrieving an item only:

```php
$picture = Input::file('picture');
```

#### Retrieving a specific item from a `$_FILES` array:

```php
$size = Input::file('picture.size');
```

>  In order to use this `file()` method, you will need to add `"multipart/form-data"` to your HTML foem.


<a id="old-input"></a>
## Old Input

You'll commonly need to re-populate forms after invalid form submissions.
Rakit's `Input` class was designed with this problem in mind.

Here's an example of how you can easily retrieve the input from the previous request.
First, you need to flash the input data to the session:

#### Flashing input to the session:

```php
Input::flash();
```

#### Flashing selected input to the session:

```php
Input::flash('only', ['username', 'email']);

Input::flash('except', ['password', 'credit_card']);
```

#### Retrieving a flashed input item from the previous request:

```php
$name = Input::old('name');
```

>  You must specify a session driver before using this `old()` and `flash()` method.


_Further Reading:_

- _[Session](/docs/en/session/config)_


<a id="redirect-dengan-old-input"></a>
## Redirect With Old Input

Now that you know how to flash input to the session.
Here's a shortcut that you can use when redirecting that prevents you
from having to micro-manage your old input in that way:

#### Flashing input from a Redirect instance:

```php
return Redirect::to('login')->with_input();
```

#### Flashing selected input from a Redirect instance:

```php
return Redirect::to('login')->with_input('only', ['username']);

return Redirect::to('login')->with_input('except', ['password']);
```


<a id="cookies"></a>
## Cookies

Rakit provides a nice wrapper around the `$_COOKIE` array.
However, there are a few things you should be aware of before using it.

First, all Rakit cookies contain a "signature hash".
This allows the framework to verify that the cookie has not been modified on the client.

Secondly, when setting cookies, the cookies are not immediately sent to the browser,
but are pooled until the end of the request and then sent together.

This means that you will not be able to both set a cookie
and retrieve the value that you set in the same request.

#### Retrieving a cookie value:

```php
$name = Cookie::get('name');
```

#### Returning a default value if the requested cookie doesn't exist:

```php
$name = Cookie::get('name', 'Anonymous');
```

#### Setting a cookie that lasts for 60 minutes:

```php
Cookie::put('name', 'Anonymous', 60);
```

#### Creating a "permanent" cookie that lasts five years:

```php
Cookie::forever('name', 'Anonymous');
```

#### Deleting a cookie:

```php
Cookie::forget('name');
```


<a id="merge--replace"></a>
## Merging & Replacing

Sometimes you may wish to merge or replace the current input. Here's how:

#### Merging new data into the current input:

```php
Input::merge(['name' => 'Agus']);
```

#### Replacing the entire input array with new data:

```php
Input::replace(['name' => 'Andi', 'age' => 23]);
```


<a id="menghapus-data-input"></a>
## Clearing Input

To clear all input data for the current request, you may use the `clear()` method:

```php
Input::clear();
```
