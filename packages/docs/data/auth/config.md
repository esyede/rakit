# Authentication Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Authentication Driver](#authentication-driver)
-   [Default Username](#default-username)
-   [Authentication Table](#authentication-table)
-   [Authentication Model](#authentication-model)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Auth` validates user credentials and tells you who the current user is. Its options live in `application/config/auth.php`.

<a id="authentication-driver"></a>

## Authentication Driver

The driver is what loads users. Two are built in:

-   `'magic'` (default) loads users with the [Magic Query Builder](/docs/database/magic), from the [`table`](#authentication-table) option.
-   `'facile'` loads users with a [Facile Model](/docs/database/facile), from the [`model`](#authentication-model) option.

```php
'driver' => 'magic',
```

Register a custom driver with `Auth::extend()`, for example in `application/boot.php`. It should extend `System\Auth\Drivers\Driver` and implement `retrieve($id)` and `attempt(array $arguments)`:

```php
Auth::extend('custom', function () {
    return new CustomAuthDriver();
});
```

Use `Auth::driver('facile')` to call a driver other than the configured one.

<a id="default-username"></a>

## Default Username

`identifier` is the column a user is looked up by at login, usually `'email'` (the default) or `'username'`:

```php
'identifier' => 'username',
```

Logins then use that key:

```php
Auth::attempt(['username' => 'john_doe', 'password' => 'secret']);
```

<a id="authentication-table"></a>

## Authentication Table

With the `'magic'` driver, `table` names the users table (default `'users'`):

```php
'table' => 'users',
```

The table needs at least:

- an `id` primary key (integer, string or UUID); the `magic` driver looks users up by this column
- the identifier column (e.g. `email` or `username`)
- a `password` column holding the [hashed](/docs/hash) password
- a `remember_token` column, only if you use ["Remember Me"](/docs/auth/usage#remember-me)

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

<a id="authentication-model"></a>

## Authentication Model

With the `'facile'` driver, `model` names the user model class (default `'User'`). It must extend `System\Database\Facile\Model` (the `Facile` alias) and have the same columns as described above, though its primary key may have any name:

```php
'model' => 'User',
```

```php
class User extends Facile
{
    public static $table = 'users';
    public static $fillable = ['name', 'email', 'password'];
    public static $hidden = ['password'];
}
```
