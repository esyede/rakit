# Autoloading

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Registering Folders](#registering-folders)
-   [Registering Mappings](#registering-mappings)
-   [Registering Namespaces](#registering-namespaces)
-   [Registering Class Aliases](#registering-class-aliases)
-   [Composer Autoloader](#composer-autoloader)
    -   [Notes for vendor folder](#notes-for-vendor-folder)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The autoloader loads a class file the first time the class is used, so you never
`require()` your own classes.

These folders are autoloaded out of the box (registered in `application/boot.php`):
`controllers/`, `models/`, `libraries/`, `commands/` and `jobs/` under
`application/`. The convention is one class per file, named after the class in
lowercase, with subfolders following the namespace:

| Class                         | File                                       |
| ----------------------------- | ------------------------------------------ |
| `User`                        | `application/models/user.php`              |
| `Entities\User`               | `application/models/entities/user.php`     |
| `User_Observer`               | `application/observers/user.php`           |
| `Blog_Post_Transformer`       | `packages/blog/transformers/post.php`      |

Classes ending in `_Observer` or `_Transformer` are read from the `observers/` and
`transformers/` folders of the application, or of a package when the class starts
with the package prefix (`Blog_`).

<a id="registering-folders"></a>

## Registering Folders

Register any other folder that follows the same convention:

```php
Autoloader::directories([
    path('app').'classes',
    path('app').'utilities',
]);
```

<a id="registering-mappings"></a>

## Registering Mappings

Mapping a class straight to its file is the fastest route, since nothing has to be
searched:

```php
Autoloader::map([
    'Forms\Bootstrap' => path('app').'classes/forms/bootstrap.php',
    'Forms\Bulma'     => path('app').'classes/forms/bulma.php',
]);
```

<a id="registering-namespaces"></a>

## Registering Namespaces

For [PSR-4](https://www.php-fig.org/psr/psr-4/) or
[PSR-0](https://www.php-fig.org/psr/psr-0/) libraries, register the root namespace
and its folder:

```php
Autoloader::namespaces([
    'Doctrine' => path('app').'libraries/Doctrine',
]);
```

Older libraries use an underscore instead of a namespace separator (e.g. every
[SwiftMailer](https://github.com/swiftmailer/swiftmailer) class starts with
`Swift_`). Register their prefix with `underscored()`:

```php
Autoloader::underscored([
    'Swift' => path('app').'libraries/Swift_Mailer',
]);
```

<a id="registering-class-aliases"></a>

## Registering Class Aliases

Aliases let you use a namespaced class by a short, top-level name. The aliases in
`application/config/aliases.php` are registered at boot with
`Autoloader::aliases(Config::get('aliases'))`. You can add your own, e.g. from a
package's `boot.php`:

```php
// One alias: class, alias
Autoloader::alias('System\Cookie', 'Cookie');

// A batch: alias => class
Autoloader::aliases([
    'Cookie' => 'System\Cookie',
    'Url'    => 'System\URL',
]);
```

#### Conflicts with built-in PHP classes

PHP loads extension classes before any autoloader runs, so an alias with the same
name would be silently shadowed. `aliases()` (the plural form) checks for this: a
conflicting alias is **skipped** and a warning goes to STDERR on the CLI, or to
`storage/logs/rakit.log.php` (falling back to the PHP error log).

For that reason the default alias map leaves out these names:

| Name        | Conflicting PHP extension                                |
| ----------- | -------------------------------------------------------- |
| `Event`     | [event](https://pecl.php.net/package/event) (libevent)   |
| `Redis`     | [redis (phpredis)](https://github.com/phpredis/phpredis) |
| `Memcached` | [memcached](https://pecl.php.net/package/memcached)      |

The event dispatcher is aliased as `Hook` (`System\Hook`). Import Redis and
Memcached by their full name:

```php
use System\Redis;
use System\Memcached;

$redis = Redis::db();
```

> The fully-qualified classes (`\System\Hook`, `\System\Redis`,
> `\System\Memcached`) always work, whatever the alias configuration.

<a id="composer-autoloader"></a>

## Composer Autoloader

To use [Composer](https://getcomposer.org) packages, point the `composer_autoload`
option of `application/config/application.php` at Composer's autoloader, using an
<ins>absolute path</ins>:

```php
'composer_autoload' => path('base').'vendor/autoload.php',
```

> If the file cannot be loaded (wrong path, for example), the application keeps
> running without an error.

<a id="notes-for-vendor-folder"></a>

### Notes for vendor folder

A `vendor/` folder inside the document root is **public** by default, which tells
visitors exactly which libraries you run. Either:

-   use the rewrite rules under [Pretty URLs](/docs/install#pretty-urls), which
    already deny `vendor/`, or
-   move `vendor/` above the document root and update the path:

```php
'composer_autoload' => dirname(path('base')).'/vendor/autoload.php',
```
