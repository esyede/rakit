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

Autoloading loads a class file the first time the class is used, so nothing needs a
`require()` of its own and nothing unused is ever read.

By default, the `application/controllers/`, `application/models/`, `application/libraries/`,
`application/commands/` and `application/jobs/` folders are autoloaded via
the `application/boot.php` file so you don't need to register them manually.

Two more folders need no registering either: a class named with `_Observer`
behind it is read from `application/observers/`, and one named with
`_Transformer` behind it from `application/transformers/`. So `User_Observer`
lives in `observers/user.php`, and `Blog_Post_Transformer` of the `blog` package
in `packages/blog/transformers/post.php`.

The convention is one class per file, the file named after the class in lowercase:
`User` in `models/` lives in `models/user.php`.

Subfolders follow the namespace, so `Entities\User` lives in
`models/entities/user.php`.

<a id="registering-folders"></a>

## Registering Folders

Any other folder can be registered under the same convention:

#### Registering several folders to the autoloader:

```php
Autoloader::directories([
	path('app').'classes',
	path('app').'utilities',
]);
```

<a id="registering-mappings"></a>

## Registering Mappings

Mapping a class straight to its file is the fastest route, since the autoloader has
no folders to scan:

#### Registering mapping to the autoloader:

```php
Autoloader::map([
	'Forms\Bootstrap' => path('app').'classes/forms/bootstrap.php',
	'Forms\Bulma'     => path('app').'classes/forms/bulma.php',
]);
```

<a id="registering-namespaces"></a>

## Registering Namespaces

Many third-party libraries use PSR-4 and PSR-0 standards for autoloading their classes.
[PSR-4](https://www.php-fig.org/psr/psr-4/) and [PSR-0](https://www.php-fig.org/psr/psr-0/)
state that class names must match their file names, including case sensitivity
and folder structure indicated by the namespace.

Register the root namespace and its folder, and the autoloader takes care of the rest.

#### Registering namespace to the autoloader:

```php
Autoloader::namespaces([
	'Doctrine' => path('app').'libraries/Doctrine',
]);
```

Before PHP had namespaces, libraries used an _underscore_ as the folder separator.
Those register the same way, under their prefix: every class of the old
[SwiftMailer](https://github.com/swiftmailer/swiftmailer) starts with `Swift_`, so
`Swift` is the name to register.

#### Registering underscored classes to the autoloader:

```php
Autoloader::underscored([
	'Swift' => path('app').'libraries/Swift_Mailer',
]);
```

<a id="registering-class-aliases"></a>

## Registering Class Aliases

The autoloader can also register short, top-level aliases for namespaced classes
so you can refer to them without writing the full namespace each time.

By default, the aliases configured in `application/config/aliases.php` are
registered during boot via:

```php
Autoloader::aliases(Config::get('aliases'));
```

You can also call this method (or its singular sibling `Autoloader::alias()`)
yourself, for example from a package or a custom bootstrapper:

```php
// Register one alias at a time
Autoloader::alias('System\Cookie', 'Cookie');

// Or a batch
Autoloader::aliases([
    'Cookie' => 'System\Cookie',
    'Url'    => 'System\URL',
]);
```

#### Conflict detection with built-in PHP classes

Using `Autoloader::aliases()` (the plural form) automatically checks every
alias name against PHP's built-in classes. PHP loads built-in classes from
extensions **before** any userland autoloader runs, so silently shadowed
aliases produce confusing `Call to undefined method` errors at runtime.

If a conflict is detected, the offending alias is **skipped** (not registered)
and a warning is written to STDERR (on the CLI) or to `storage/logs/rakit.log.php`
(falling back to the PHP error log) so you can rename the
alias or disable the conflicting extension.

The default alias map intentionally does **not** include the following names
because they collide with widely-used PHP extensions:

| Name        | Conflicting PHP extension                                |
| ----------- | -------------------------------------------------------- |
| `Event`     | [event](https://pecl.php.net/package/event) (libevent)    |
| `Redis`     | [redis (phpredis)](https://github.com/phpredis/phpredis) |
| `Memcached` | [memcached](https://pecl.php.net/package/memcached)      |

The framework's event dispatcher class lives at `System\Hook` and is exposed
under the short alias **`Hook`**. For Redis and Memcached, use the
fully-qualified namespace or import them:

```php
use System\Redis;
use System\Memcached;

$redis = Redis::db();
```

> Tip: the fully-qualified target classes (`\System\Hook`, `\System\Redis`,
> `\System\Memcached`) **always work** regardless of alias configuration.

<a id="composer-autoloader"></a>

## Composer Autoloader

[Composer](https://getcomposer.org) brings its own autoloader, usually at
`vendor/autoload.php`. Point the `composer_autoload` option of
`application/config/application.php` at it, with an <ins>absolute path</ins>:

```php
'composer_autoload' => path('base').'vendor/autoload.php',
```

> If the autoload file fails to load due to incorrect path or other reasons,
> your application will continue to run without displaying an error.

<a id="notes-for-vendor-folder"></a>

### Notes for vendor folder

A `vendor/` folder in the document root is **not protected** by default: every file
in it is public, which tells a visitor exactly which libraries you run. Two ways to
close that:

#### Option 1: URL Rewrite

The Apache and Nginx snippets under [pretty URLs](/docs/install#pretty-urls) already
carry rules that deny the vendor folder.

#### Option 2: Place above document root

If your hosting allows it, move the vendor folder above the document root and point
`composer_autoload` at its new place:

```php
'composer_autoload' => dirname(path('base')).'/vendor/autoload.php',
```

The folder is then out of reach, and the libraries still load.
