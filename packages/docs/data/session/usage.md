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

Session provides a way to store user data between HTTP requests. Rakit provides a clean and consistent API for accessing various session backend drivers.

Sessions in Rakit will automatically start when the application is first run, so you don't need to worry about starting sessions manually.

**Check if session has started:**

```php
if (Session::started()) {
    // Session is active
}
```

<a id="storing-items"></a>

## Storing Items

Storing items in the session is very simple. Just call the `put()` method like this:

```php
Session::put('name', 'Budi');
```

The first parameter is the key of the session item. You will use this key to retrieve the item from the session. The second parameter is its value.

**Storing multiple items:**

```php
Session::put('name', 'Budi');
Session::put('age', 25);
Session::put('city', 'Jakarta');
```

<a id="retrieving-items"></a>

## Retrieving Items

You can use the `get()` method to retrieve items from the session, including flash data. Just specify which item's key you want to retrieve:

```php
$name = Session::get('name');
```

By default, it will return `NULL` if the session item does not exist. However, you can provide a default value as the second parameter if needed:

```php
$name = Session::get('name', 'Andi');

$name = Session::get('name', function () { return 'Andi'; });
```

Now, it will return `'Andi'` if the `'name'` item does not exist in the session.

**Check if an item exists:**

Rakit gives you a simple way to determine if an item exists in the session using the `has()` method:

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

You can delete all items from the session using the `flush()` method:

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

The `flash()` method stores an item in the session that will expire after the next request. This is useful for storing temporary data such as status or validation error messages:

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

Flash items that expire in the next request can be retained for another request using the `reflash()` or `keep()` methods:

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

Sometimes you may want to regenerate the session ID. This means the old session ID will be replaced with a new random session ID. This is useful for security, especially after a user logs in.

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

Session provides CSRF (Cross-Site Request Forgery) tokens to protect your application from CSRF attacks.

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

Rakit automatically validates CSRF tokens for POST, PUT, PATCH, and DELETE requests. If the token is invalid, the application will throw an exception.

**Excluding routes from CSRF protection:**

If you need to exclude certain routes from CSRF protection (e.g., for API webhooks), you can do so in the filter:

```php
// In application/routes.php
Route::post('webhook/stripe', ['before' => 'no_csrf', function() {
    // Handle webhook
}]);
```

> CSRF tokens are automatically regenerated every time the session is regenerated with `Session::regenerate()`.
