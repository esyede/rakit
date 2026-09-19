# Authentication Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Authentication Driver](#authentication-driver)
-   [Default Username](#default-username)
-   [Authentication Model](#authentication-model)
-   [Authentication Table](#authentication-table)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Auth` validates user credentials and tells you who the current user is. Its options live in `application/config/auth.php`.

<a id="authentication-driver"></a>

## Authentication Driver

Authentication is driver-based: the driver is what loads the user.

By default, we have included two drivers:

-   The `'magic'` driver that uses the [Magic Query Builder](/docs/database/magic) to load your users, and is the default driver.
-   The `'facile'` driver that uses the [Facile Model](/docs/database/facile) to load your application users.

**Selecting a driver:**

```php
// In application/config/auth.php
'driver' => 'magic',
```

Custom drivers can be registered too:

```php
// In application/boot.php
Auth::extend('custom', function() {
    return new CustomAuthDriver();
});
```

<a id="default-username"></a>

## Default Username

The `identifier` option names the column a user is looked up by at login, usually `'email'` or `'username'`.

```php
'identifier' => 'email',
```

> The default is `email`.

**Example using username:**

```php
// In application/config/auth.php
'identifier' => 'username',
```

Logins are then looked up by the `username` column:

```php
Auth::attempt([
    'username' => 'john_doe',
    'password' => 'secret',
]);
```

<a id="authentication-model"></a>

## Authentication Model

When using the `'facile'` driver, the `model` option specifies which model to use when loading user data.

```php
'model' => 'User',
```

This model must extend `System\Database\Facile\Model` and have columns that match the identifier configuration.

**Example User model:**

```php
class User extends Facile
{
    public static $table = 'users';

    // Fillable columns
    public static $fillable = [
        'name',
        'email',
        'password',
    ];

    // Hidden columns during serialization
    public static $hidden = [
        'password',
    ];
}
```

**Custom model:**

For a model under another name:

```php
// In application/config/auth.php
'model' => 'Account',
```

<a id="authentication-table"></a>

## Authentication Table

When using the `'magic'` driver, the `table` option specifies which table to use for loading user data.

```php
'table' => 'users',
```

This table must have at least the following columns:
- Primary key column (usually `id`), which may hold an integer, a string or a UUID
- Identifier column (matching the `identifier` configuration, e.g., `email` or `username`)
- `password` column for storing hashed passwords
- `remember_token` column, needed only if you use ["Remember Me"](/docs/auth/usage#remember-me)

**Example table structure:**

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

**Custom table:**

For a table under another name:

```php
// In application/config/auth.php
'table' => 'members',
```
