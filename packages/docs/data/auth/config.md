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

Most interactive applications have the ability for users to log in and log out. Rakit provides a simple class to help you validate user credentials and retrieve information about your application's current user.

To get started, let's look at the `application/config/auth.php` file. This configuration file contains several basic options to help you get started with authentication.

<a id="authentication-driver"></a>

## Authentication Driver

Rakit's authentication mechanism is driver-based, meaning the responsibility for retrieving users during authentication is delegated to various "drivers".

By default, we have included two drivers:

-   The `'facile'` driver that uses the [Facile Model](/docs/id/database/facile) to load your application users, and is the default driver.
-   The `'magic'` driver that uses the [Magic Query Builder](/docs/id/database/magic) to load your users.

**Selecting a driver:**

```php
// In application/config/auth.php
'driver' => 'facile',
```

You are also free to create and register your own custom drivers if needed:

```php
// In application/start.php
Auth::extend('custom', function() {
    return new CustomAuthDriver();
});
```

<a id="default-username"></a>

## Default Username

The `identifier` option in the configuration file specifies the column used to identify the user when logging in. This usually matches the database column in the users table, and will typically be `'email'` or `'username'`.

```php
'identifier' => 'email',
```

> By default, Rakit is configured to use `email` as the identifier, but of course you are free to change it as needed.

**Example using username:**

```php
// In application/config/auth.php
'identifier' => 'username',
```

With this configuration, when logging in, Rakit will search for the user based on the `username` column in the database:

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

If you are using a model with a different name, change the configuration:

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
- Primary key column (usually `id`)
- Identifier column (matching the `identifier` configuration, e.g., `email` or `username`)
- `password` column for storing hashed passwords

**Example table structure:**

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

**Custom table:**

If your user table has a different name, change the configuration:

```php
// In application/config/auth.php
'table' => 'members',
```
