# Using Authentication

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Encrypting Passwords](#encrypting-passwords)
-   [Log In](#log-in)
-   [Remember Me](#remember-me)
-   [Protecting Routes](#protecting-routes)
-   [Retrieving User Data](#retrieving-user-data)
-   [Check Login Status](#check-login-status)
-   [Log Out](#log-out)
-   [Practical Examples](#practical-examples)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Auth provides an easy and secure authentication mechanism for your application. Before using the `Auth` class, make sure you have:

1. [Configured the session driver](/docs/id/session/config)
2. [Configured the auth driver](/docs/id/auth/config)
3. A user table/model with a hashed password column

> Rakit automatically handles sessions and cookies for authentication, so you don't need to manage them manually.

<a id="encrypting-passwords"></a>

## Encrypting Passwords

If you are using the Auth class, we strongly recommend encrypting all passwords. Web application development should be done responsibly. Encrypted passwords minimize the potential for data breaches of your users' information.

Now, let's proceed to encrypt passwords:

```php
$hash = Hash::make('admin123');
```

By default, `Hash::make()` will use the default cost of `10`, but you can also increase or decrease it if necessary:

```php
$cost = 22;

$hash = Hash::make('admin123', $cost);
```

> The cost should only be an integer between `4` and `31`. The higher the cost, the more secure the password, but the slower the hashing process.

You can compare an unencrypted value with an encrypted value using the `check()` method like this:

```php
if (Hash::check('admin123', $hash)) {
	return 'Password is correct!';
}
```

**Example of user registration with hashed password:**

```php
public function action_register()
{
    $user = new User();
    $user->name = Input::get('name');
    $user->email = Input::get('email');
    $user->password = Hash::make(Input::get('password'));
    $user->save();

    // Automatic login after registration
    Auth::login($user->id);

    return Redirect::to('dashboard');
}
```

<a id="log-in"></a>

## Log In

It is very easy to log a user into your application using the `attempt()` method. Simply pass the identifier (email/username) and password to the method. Credentials should be placed in an array, which allows maximum flexibility across drivers, as some drivers may require different numbers of arguments. The `attempt()` method will return `TRUE` if the credentials are valid and `FALSE` otherwise:

```php
$credentials = [
    'email' => 'user@example.com',
    'password' => 'secret',
];

if (Auth::attempt($credentials)) {
    return Redirect::to('user/profile');
}
```

> The identifier column (email/username) must match the `identifier` configuration in `application/config/auth.php`.

**Login with additional conditions:**

You can add additional conditions for login, for example, only active users can log in:

```php
$credentials = [
    'email' => 'user@example.com',
    'password' => 'secret',
    'active' => true,
];

if (Auth::attempt($credentials)) {
    return Redirect::to('dashboard');
} else {
    return Redirect::back()->with('error', 'Invalid credentials or account not active.');
}
```

**Example login form:**

```php
// Controller
public function action_login()
{
    if (Request::method() === 'POST') {
        $credentials = [
            'email'    => Input::get('email'),
            'password' => Input::get('password'),
        ];

        if (Auth::attempt($credentials)) {
            Session::flash('message', 'Login successful!');
            return Redirect::to('dashboard');
        }

        return Redirect::back()
            ->with_input()
            ->with('error', 'Email or password is incorrect.');
    }

    return View::make('auth.login');
}
```

If the user's credentials are valid, the user ID will be stored in the session and the user will be considered "logged in" on subsequent requests to your application.

**Login without credential validation:**

Use the `login()` method to log in a user without checking their credentials. Useful after successful registration or in impersonation processes:

```php
// Login by user ID
Auth::login($user->id);

// Login by user ID (directly with number)
Auth::login(15);

// Login by user object (for Facile driver)
Auth::login($user);
```

<a id="remember-me"></a>

## Remember Me

The "Remember Me" feature allows users to stay logged in even after closing the browser. The cookie will be stored for 5 years.

**Login with remember me:**

```php
$credentials = [
    'email'    => Input::get('email'),
    'password' => Input::get('password'),
    'remember' => true,
];

if (Auth::attempt($credentials)) {
    return Redirect::to('dashboard');
}
```

**Or using a separate parameter:**

```php
$credentials = [
    'email'    => Input::get('email'),
    'password' => Input::get('password'),
];

$remember = Input::get('remember') ? true : false;

if (Auth::attempt($credentials)) {
    if ($remember) {
        Auth::login(Auth::user()->id, true);
    }
    return Redirect::to('dashboard');
}
```

**Example form with remember me checkbox:**

```php
<form method="POST" action="<?php echo URL::to('login'); ?>">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Password" required>

    <label>
        <input type="checkbox" name="remember" value="1">
        Remember me
    </label>

    <button type="submit">Login</button>
</form>
```

<a id="protecting-routes"></a>

## Protecting Routes

It is very common to restrict access to certain routes only to logged-in users. In Rakit, this is done using the `'auth'` filter. If the user is successfully logged in, the request will be processed normally; however, if the user is not logged in, they will be redirected to the [named route](/docs/id/routing#named-route) called `'login'`.

**Protecting a single route:**

```php
Route::get('dashboard', ['before' => 'auth', function () {
    return View::make('dashboard');
}]);
```

**Protecting group routes:**

```php
Route::group(['before' => 'auth'], function () {
    Route::get('dashboard', function () {
        return View::make('dashboard');
    });

    Route::get('profile', function () {
        return View::make('profile');
    });

    Route::get('settings', function () {
        return View::make('settings');
    });
});
```

**Protection in controller:**

```php
class Dashboard_Controller extends Controller
{
    public function __construct()
    {
        $this->filter('before', 'auth');
    }

    public function action_index()
    {
        return View::make('dashboard.index');
    }
}
```

> You are free to modify the `'auth'` filter as needed. The default implementation can be found in the `application/filters.php` file.

**Custom redirect for guests:**

If you want to redirect to a different page instead of the named route `'login'`, you can modify the filter in `application/filters.php`:

```php
Filter::register('auth', function () {
    if (Auth::guest()) {
        return Redirect::to('auth/login')->with('error', 'Please log in first.');
    }
});
```

<a id="retrieving-user-data"></a>

## Retrieving User Data

After the user has successfully logged in, you can access user data through the `user()` method:

```php
// Retrieve all user data
$user = Auth::user();

// Access user properties
$name = Auth::user()->name;
$email = Auth::user()->email;
```

**Example in view:**

```php
<?php if (Auth::check()): ?>
    <p>Welcome, <?php echo Auth::user()->name; ?>!</p>
    <p>Email: <?php echo Auth::user()->email; ?></p>
<?php endif; ?>
```

**With Blade:**

```php
@if (Auth::check())
    <p>Welcome, {{ Auth::user()->name }}!</p>
    <p>Email: {{ Auth::user()->email }}</p>
@endif
```

> If the user is not logged in, the `user()` method will return `NULL`.

**Check before access:**

```php
$user = Auth::user();

if ($user) {
    echo 'User ID: ' . $user->id;
    echo 'Name: ' . $user->name;
} else {
    echo 'User not logged in';
}
```

<a id="check-login-status"></a>

## Check Login Status

Rakit provides several methods to check the user's login status.

**Check if user is logged in:**

```php
if (Auth::check()) {
    // User is logged in
    return 'Welcome back!';
}
```

**Check if user is not logged in (guest):**

```php
if (Auth::guest()) {
    // User is not logged in
    return Redirect::to('login');
}
```

**Example usage in controller:**

```php
public function action_dashboard()
{
    if (Auth::guest()) {
        return Redirect::to('login')
            ->with('error', 'You must log in first.');
    }

    $user = Auth::user();
    return View::make('dashboard', compact('user'));
}
```

**Example in view:**

```php
@if (Auth::check())
    <a href="{{ URL::to('logout') }}">Logout</a>
@else
    <a href="{{ URL::to('login') }}">Login</a>
@endif
```

<a id="log-out"></a>

## Log Out

To log out a user, simply call the `logout()` method:

```php
Auth::logout();

return Redirect::to('login')->with('message', 'You have been logged out.');
```

This method will:
- Remove the user ID from the session
- Remove the "remember me" cookie if present
- Trigger the `rakit.auth: logout` event

**Example logout route:**

```php
Route::get('logout', function () {
    Auth::logout();
    return Redirect::to('home')->with('message', 'Successfully logged out.');
});
```

**Example in controller:**

```php
public function action_logout()
{
    Auth::logout();
    Session::flash('message', 'You have logged out of the application.');
    return Redirect::to('login');
}
```

<a id="practical-examples"></a>

## Practical Examples

**Complete Auth Controller example:**

```php
class Auth_Controller extends Controller
{
    // Display login form
    public function action_login()
    {
        // Redirect if already logged in
        if (Auth::check()) {
            return Redirect::to('dashboard');
        }

        // Process login
        if (Request::method() === 'POST') {
            $credentials = [
                'email'    => Input::get('email'),
                'password' => Input::get('password'),
                'remember' => Input::get('remember') ? true : false,
            ];

            if (Auth::attempt($credentials)) {
                return Redirect::to('dashboard');
            }

            return Redirect::back()
                ->with_input(Input::only('email'))
                ->with('error', 'Email or password is incorrect.');
        }

        return View::make('auth.login');
    }

    // Display registration form
    public function action_register()
    {
        if (Request::method() === 'POST') {
            $rules = [
                'name'     => 'required|max:100',
                'email'    => 'required|email|unique:users',
                'password' => 'required|min:6|confirmed',
            ];

            $validation = Validator::make(Input::all(), $rules);

            if ($validation->fails()) {
                return Redirect::back()
                    ->with_input()
                    ->with_errors($validation);
            }

            // Create new user
            $user = new User();
            $user->name = Input::get('name');
            $user->email = Input::get('email');
            $user->password = Hash::make(Input::get('password'));
            $user->save();

            // Automatic login
            Auth::login($user->id);

            return Redirect::to('dashboard')
                ->with('message', 'Registration successful! Welcome.');
        }

        return View::make('auth.register');
    }

    // Logout
    public function action_logout()
    {
        Auth::logout();
        return Redirect::to('login')
            ->with('message', 'You have logged out.');
    }
}
```

**Example login view (Blade):**

```php
@layout('layouts.guest')

@section('content')
    <h2>Login</h2>

    @if (Session::has('error'))
        <div class="alert alert-error">
            {{ Session::get('error') }}
        </div>
    @endif

    @if (Session::has('message'))
        <div class="alert alert-success">
            {{ Session::get('message') }}
        </div>
    @endif

    <form method="POST" action="{{ URL::to('login') }}">
        <div class="form-group">
            <label>Email</label>
            <input type="email" name="email" value="{{ Input::old('email') }}" required>
        </div>

        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="remember" value="1">
                Remember me
            </label>
        </div>

        <button type="submit">Login</button>
        <a href="{{ URL::to('register') }}">Don't have an account?</a>
    </form>
@endsection
```

**Example Base Controller with auth check:**

```php
class Base_Controller extends Controller
{
    public function __construct()
    {
        // Share current user to all views
        if (Auth::check()) {
            View::share('current_user', Auth::user());
        }
    }
}

class Dashboard_Controller extends Base_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->filter('before', 'auth');
    }

    public function action_index()
    {
        $user = Auth::user();
        $posts = Post::where('user_id', '=', $user->id)->get();

        return View::make('dashboard.index', compact('posts'));
    }
}
```
