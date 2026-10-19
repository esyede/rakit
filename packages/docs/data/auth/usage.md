# Using Authentication

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypting Passwords](#encrypting-passwords)
-   [Log In](#log-in)
-   [Remember Me](#remember-me)
-   [Retrieving User Data](#retrieving-user-data)
-   [Check Login Status](#check-login-status)
-   [Protecting Routes](#protecting-routes)
-   [Log Out](#log-out)
-   [Auth Events](#auth-events)
-   [Practical Example](#practical-example)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Auth` logs users in and out, tells you who the current user is, and protects routes. Before using it, make sure:

1.  The [session driver](/docs/session/config) is configured.
2.  The [auth driver](/docs/auth/config) is configured.
3.  Your user table stores a **hashed** password.

Authentication sessions and cookies are managed for you.

<a id="encrypting-passwords"></a>

## Encrypting Passwords

Never store plain-text passwords. Hash them with [`Hash::make()`](/docs/hash) and verify with `Hash::check()`:

```php
$user->password = Hash::make(Input::get('password'));

Hash::check('admin123', $user->password); // true / false
```

<a id="log-in"></a>

## Log In

`attempt()` takes the credentials as an array and returns `true` on success. The identifier key must match the `identifier` option in `application/config/auth.php`:

```php
if (Auth::attempt(['email' => $email, 'password' => $password])) {
    return Redirect::to('dashboard');
}

return Redirect::back()->with_input()->with('error', 'Email or password is incorrect.');
```

Any extra keys become additional `WHERE` conditions, for example letting only active users in:

```php
Auth::attempt(['email' => $email, 'password' => $password, 'active' => 1]);
```

**Logging in without credentials:**

`Auth::login()` logs a user in directly, which is useful right after registration. Pass the user's primary key, not the model; the optional second argument enables "remember me":

```php
Auth::login($user->id);
Auth::login($user->id, true);
```

> On login the session ID is regenerated (keeping the session data) and the CSRF token is rotated, so a session ID planted before login never becomes authenticated. You do not need to call `Session::regenerate()` yourself.

<a id="remember-me"></a>

## Remember Me

"Remember me" keeps the visitor logged in after the browser closes, through an encrypted cookie that lives five years. Pass `'remember' => true` to `attempt()` (or `true` as the second argument to `login()`):

```php
Auth::attempt([
    'email' => Input::get('email'),
    'password' => Input::get('password'),
    'remember' => (bool) Input::get('remember'),
]);
```

The user table needs a `remember_token` column; without it, login still works but the visitor is simply not remembered:

```php
Schema::table('users', function ($table) {
    $table->string('remember_token', 100)->nullable();
});
```

> The cookie carries a token that must match the user's `remember_token` column, plus the password hash. Logging out replaces the stored token and changing the password changes the hash, so either action revokes every cookie handed out earlier.

<a id="retrieving-user-data"></a>

## Retrieving User Data

`user()` returns the logged-in user, or `null` for a guest:

```php
$user = Auth::user();

if ($user) {
    echo $user->name;
}
```

<a id="check-login-status"></a>

## Check Login Status

```php
Auth::check(); // true when logged in
Auth::guest(); // true when not logged in
```

```php
@if (Auth::check())
    <p>Welcome, {{ Auth::user()->name }}!</p>
    <a href="{{ URL::to('logout') }}">Logout</a>
@else
    <a href="{{ URL::to('login') }}">Login</a>
@endif
```

<a id="protecting-routes"></a>

## Protecting Routes

The `auth` middleware restricts routes to logged-in users; guests get a `401` by default. Attach it to a route, a group, or a controller:

```php
Route::get('dashboard', ['before' => 'auth', function () {
    return View::make('dashboard');
}]);

Route::group(['before' => 'auth'], function () {
    Route::get('profile', function () { return View::make('profile'); });
});

class Dashboard_Controller extends Controller
{
    public function __construct()
    {
        $this->middleware('before', 'auth');
    }
}
```

The middleware is defined in `application/middlewares.php`, so you can change it, for example to redirect guests to a login page:

```php
Route::middleware('auth', function () {
    if (Auth::guest()) {
        return Redirect::to('login')->with('error', 'Please log in first.');
    }
});
```

The `throttle` middleware rate-limits requests and answers `429` once a client exceeds its limit. See [Middleware Pattern](/docs/routing#middleware-pattern) for attaching it.

<a id="log-out"></a>

## Log Out

```php
Auth::logout();

return Redirect::to('login')->with('message', 'You have been logged out.');
```

`logout()` replaces the user's `remember_token` (revoking "remember me" cookies), removes the cookie, and invalidates the session: it gets a new ID, all session data is cleared and a new CSRF token is issued.

Pass `false` to keep the rest of the session (e.g. a cart): only the login is forgotten, and the session gets a new ID to prevent fixation. The CSRF token is kept; call `Session::invalidate()` later if you want the full reset.

```php
Auth::logout(false);
```

<a id="auth-events"></a>

## Auth Events

`Auth` fires these [hooks](/docs/hooks):

| Event                   | Arguments                                      | Fired when                                      |
| ----------------------- | ---------------------------------------------- | ----------------------------------------------- |
| `rakit.auth: login`     | none                                           | a user logs in                                  |
| `rakit.auth: logout`    | none                                           | a user logs out                                 |
| `rakit.auth: failed`    | `$credentials` (without the `password` key)    | `attempt()` fails                               |
| `rakit.auth: lockout`   | `$key`, `$limit`, `$reset` (timestamp)         | the `throttle` middleware blocks a client       |

```php
// In application/hooks.php
Hook::listen('rakit.auth: failed', function ($credentials) {
    Log::warning('Failed login for '.$credentials['email']);
});

Hook::listen('rakit.auth: lockout', function ($key, $limit, $reset) {
    Log::warning('Locked out until '.date('H:i:s', $reset));
});
```

<a id="practical-example"></a>
<a id="practical-examples"></a>

## Practical Example

```php
class Auth_Controller extends Controller
{
    public function action_login()
    {
        if (Auth::check()) {
            return Redirect::to('dashboard');
        }

        if (Request::method() === 'POST') {
            $credentials = [
                'email' => Input::get('email'),
                'password' => Input::get('password'),
                'remember' => (bool) Input::get('remember'),
            ];

            if (Auth::attempt($credentials)) {
                return Redirect::to('dashboard');
            }

            return Redirect::back()
                ->with_input('only', ['email'])
                ->with('error', 'Email or password is incorrect.');
        }

        return View::make('auth.login');
    }

    public function action_register()
    {
        $validation = Validator::make(Input::all(), [
            'name' => 'required|max:100',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validation->fails()) {
            return Redirect::back()->with_input()->with_errors($validation);
        }

        $user = new User();
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->password = Hash::make(Input::get('password'));
        $user->save();

        Auth::login($user->id);

        return Redirect::to('dashboard');
    }

    public function action_logout()
    {
        Auth::logout();
        return Redirect::to('login');
    }
}
```
