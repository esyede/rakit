# Packages

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Creating Packages](#creating-packages)
-   [Registering Packages](#registering-packages)
-   [Packages & Autoloading](#packages--autoloading)
-   [Booting Packages](#booting-packages)
-   [Routing to Packages](#routing-to-packages)
-   [Using Packages](#using-packages)
-   [Package Assets](#package-assets)
-   [Installing Packages](#installing-packages)
-   [Upgrading Packages](#upgrading-packages)
-   [Removing Packages](#removing-packages)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

A package is a reusable unit of code with its own controllers, views, configs,
language files, routes, migrations and commands — anything from a small library to
a complete CMS. The `application/` folder is itself a package (the default one),
and so is this documentation.

<a id="creating-packages"></a>

## Creating Packages

Create a folder in `packages/`, e.g. `packages/admin/`, laid out like
`application/`. It may contain a `boot.php`, which runs every time the package
boots. It is optional and usually just registers classes:

```php
// File: packages/admin/boot.php

Autoloader::namespaces([
    'Admin' => Package::path('admin').'libraries',
]);
```

<a id="registering-packages"></a>

## Registering Packages

List the package in `application/packages.php`:

```php
return ['admin'];
```

That loads it from `packages/admin/`. Options are given as an array:

```php
return [
    'admin' => [
        'location' => 'backend/admin',  // packages/backend/admin
        'handles'  => 'admin',          // URIs starting with admin/ go to this package
        'autoboot' => false,
    ],
];
```

| Option      | Description                                                                                  |
| ----------- | -------------------------------------------------------------------------------------------- |
| `location`  | Folder relative to `packages/`, or an absolute path with the `path: ` prefix, e.g. `'path: /var/www/my-packages/admin'`. |
| `handles`   | URI prefix routed to the package, see [package routing](/docs/routing#route-for-package).     |
| `autoboot`  | Boot on every request, see [Booting Packages](#booting-packages).                            |
| `autoloads` | Autoloader registrations, see below.                                                          |
| `boot`      | A closure run on boot instead of the package's `boot.php`.                                    |

<a id="packages--autoloading"></a>

## Packages & Autoloading

If `boot.php` would only register classes, put the registrations in
`application/packages.php` instead:

```php
return [
    'admin' => [
        'autoloads' => [
            'map'         => ['Admin' => '(:package)/admin.php'],
            'namespaces'  => ['Admin' => '(:package)/libraries'],
            'directories' => ['(:package)/models'],
        ],
    ],
];
```

Each key is an [Autoloader](/docs/autoloading) method that receives the value.
`(:package)` is replaced with the package path.

<a id="booting-packages"></a>

## Booting Packages

A registered package is booted the first time it is reached — by a route, a
controller or a middleware of its own. You can also boot it yourself:

```php
Package::boot('admin');
```

Booting runs `boot.php` and then `routes.php`. When `routes.php` exists, the
package's `hooks.php`, `middlewares.php` and `composers.php` are loaded right after
it (if present). A package boots only once; later calls are ignored.

Set `'autoboot' => true` to boot a package on every request. That is rarely needed.

Each boot fires a `rakit.booted: <package>` event. Use it to configure a package
from your application instead of editing its files, e.g. in `application/boot.php`:

```php
Hook::listen('rakit.booted: admin', function () {
    Config::set('admin::general.pagename', 'My Admin Panel');
});
```

`Package::freeze()` removes a package from the registered list for the current
request, so `Package::boot()` throws for it — handy for disabling one without
touching `application/packages.php`:

```php
Package::freeze('admin');
```

Checking package status:

```php
Package::exists('admin'); // registered?
Package::booted('admin'); // booted?
Package::routed('admin'); // routes.php loaded?
```

<a id="routing-to-packages"></a>

## Routing to Packages

See [package routing](/docs/routing#route-for-package) and
[package controllers](/docs/controllers#package-controller).

<a id="using-packages"></a>

## Using Packages

Use the `package::` prefix to reach a package's views, config and language lines:

```php
return View::make('admin::dashboard');

Config::get('admin::uploads.max_size');

Lang::line('admin::themes.default_theme');
```

The `Package` class also exposes package metadata and identifier helpers:

| Method                                   | Returns (for the `admin` package)                       |
| ---------------------------------------- | ------------------------------------------------------- |
| `Package::path('admin')`                 | `'/path/to/packages/admin/'`                            |
| `Package::assets('admin')`               | `'/packages/admin/'`                                    |
| `Package::get('admin')`                  | All options of the package                              |
| `Package::option('admin', 'autoboot', false)` | One option, with a default                         |
| `Package::names()`                       | Registered package names, e.g. `['admin', 'docs']`      |
| `Package::all()`                         | All registered packages with their options              |
| `Package::parse('admin::home.index')`    | `['admin', 'home.index']`                               |
| `Package::name('admin::home.index')`     | `'admin'`                                               |
| `Package::element('admin::home.index')`  | `'home.index'`                                          |
| `Package::identifier('admin', 'home.index')` | `'admin::home.index'`                               |
| `Package::prefix('admin')`               | `'admin::'`                                             |
| `Package::class_prefix('admin')`         | `'Admin_'`                                              |
| `Package::handles('admin/users')`        | The package handling that URI (`'admin'`), or `'application'` |
| `Package::resolve('admin')`              | `'admin'` if registered, otherwise `'application'`      |
| `Package::expand('admin::controllers/home.php')` | `'/path/to/packages/admin/controllers/home.php'` |

<a id="package-assets"></a>

## Package Assets

A package keeps its CSS, JavaScript and images in its own `assets/` folder. Since
`packages/` is not public, publish them into `assets/packages/<package-name>/`:

```bash
php rakit package:publish <package-name>
php rakit package:unpublish <package-name>   # deletes assets/packages/<package-name>
```

Link to them with `asset()` (or `URL::to_asset()`):

```php
<link href="<?php echo asset('packages/admin/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo URL::to_asset('packages/admin/js/app.min.js') ?>"></script>
```

`package:install` publishes assets automatically.

<a id="installing-packages"></a>

## Installing Packages

Extract a package archive into `packages/` by hand, or let the
[console](/docs/console) do it (requires the
[cURL](https://www.php.net/manual/en/book.curl.php) extension):

```bash
php rakit package:install themable
```

This downloads the package from the
[official repository](https://rakit.esyede.my.id/repositories) into
`packages/<package-name>/`, publishes its assets and writes a `meta.json` with the
package information. Then [register](#registering-packages) it in
`application/packages.php`.

<a id="upgrading-packages"></a>

## Upgrading Packages

```bash
php rakit package:upgrade <package-name>
```

This finds the latest version compatible with your Rakit version, deletes the old
package files and its published assets, and installs the new version. Run
`package:publish` afterwards to publish the assets again.

> All package files are deleted during an upgrade, so never edit a package
> directly. Configure it through the `rakit.booted` event
> ([see above](#booting-packages)) instead.

<a id="removing-packages"></a>

## Removing Packages

1.  Back up your database, then run `php rakit package:uninstall <package-name>`. It rolls
    back the migrations the package has run (dropping its tables; only a warning is
    printed when the database or migrations table is unavailable), then removes
    `packages/<package-name>/` and `assets/packages/<package-name>/`. If you delete
    those folders by hand instead, run `php rakit migrate:reset <package-name>` first.
2.  Remove its entry from `application/packages.php`.
3.  Optionally clear the cache: `php rakit clear:cache`.
