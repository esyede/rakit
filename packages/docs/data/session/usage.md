# Using Session

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Storing Items](#storing-items)
-   [Retrieving Items](#retrieving-items)
-   [Deleting Items](#deleting-items)
-   [Flash Items](#flash-items)
-   [Regeneration](#regeneration)
-   [CSRF Token](#csrf-token)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

A session stores data between HTTP requests, behind one API whatever the backend driver. With a driver configured it starts by itself on every web request, apart from the routes listed in the `'stateless'` session option.

**Check if session has started:**

```php
if (Session::started()) {
    // Session is active
}
```

<a id="storing-items"></a>

## Storing Items

`put()` stores an item:

```php
Session::put('name', 'Budi');
```

The parameters are the key the item is read back by, and its value.

**Storing multiple items:**

```php
Session::put('name', 'Budi');
Session::put('age', 25);
Session::put('city', 'Jakarta');
```

<a id="retrieving-items"></a>

## Retrieving Items

`get()` reads an item back by its key, flash data included:

```php
$name = Session::get('name');
```

A missing item gives `NULL`, unless a default is passed as the second argument:

```php
$name = Session::get('name', 'Andi');

$name = Session::get('name', function () { return 'Andi'; });
```

**Check if an item exists:**

`has()` answers whether an item is there:

```php
if (Session::has('name')) {
	$name = Session::get('name');
}
```

<a id="deleting-items"></a>

## Deleting Items

To delete an item from the session, use the `forget()` method:

```php
Session::forget('name');
```

**Deleting all items:**

`flush()` deletes every item but the CSRF token:

```php
Session::flush();
```

**Deleting several items at once:**

```php
Session::forget('name');
Session::forget('age');
Session::forget('city');
```

<a id="flash-items"></a>

## Flash Items

`flash()` stores an item that expires after the next request, which suits status messages and validation errors:

```php
Session::flash('status', 'Welcome Back!');
```

**Example usage in controller:**

```php
public function action_store()
{
    // Save data
    $user = User::create(Input::all());

    // Flash success message
    Session::flash('message', 'User created successfully!');

    return Redirect::to('users');
}
```

**In view:**

```php
<?php if (Session::has('message')): ?>
    <div class="alert alert-success">
        <?php echo Session::get('message'); ?>
    </div>
<?php endif; ?>
```

**Retain flash data:**

`reflash()` and `keep()` hold flash items for one more request:

**Retain all flash items for another request:**

```php
Session::reflash();
```

**Retain a flash item for another request:**

```php
Session::keep('status');
```

**Retain several flash items for another request:**

```php
Session::keep(['status', 'other_item']);
```

<a id="regeneration"></a>

## Regeneration

Regenerating replaces the session ID with a fresh random one, which is worth doing after a login.

```php
Session::regenerate();
```

**Regenerate ID after login:**

```php
public function action_login()
{
    $credentials = Input::only('email', 'password');

    if (Auth::attempt($credentials)) {
        // Regenerate session ID for security
        Session::regenerate();

        return Redirect::to('dashboard');
    }

    return Redirect::back()->with('error', 'Login failed!');
}
```

<a id="csrf-token"></a>

## CSRF Token

The session carries a CSRF token to protect against cross-site request forgery.

**Getting CSRF token:**

```php
$token = Session::token();
```

**Using in form:**

```php
<form method="POST" action="/users">
    <input type="hidden" name="csrf_token" value="<?php echo Session::token(); ?>">

    <!-- Form fields -->

    <button type="submit">Submit</button>
</form>
```

**Validating CSRF token:**

Attach the `csrf` middleware (defined in `application/middlewares.php`) to the routes you want to protect. It validates the token for every request method other than GET, HEAD, OPTIONS, TRACE and CONNECT, and responds with a `422` error if the token is invalid:

```php
Route::post('users', ['before' => 'csrf', function () {
    // ..
}]);
```

**Excluding routes from CSRF protection:**

If you need to exclude certain URIs from CSRF protection (e.g., for API webhooks), list them in the `'csrf_except'` option of `application/config/application.php`:

```php
'csrf_except' => [
    'webhook/stripe',
],
```

> `Session::regenerate()` keeps the current CSRF token. To also get a fresh CSRF token (e.g. on logout),
> use `Session::invalidate()`, which discards all session data as well.
