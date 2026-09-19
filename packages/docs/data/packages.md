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

A package is a unit of code you can organize and reuse across applications, with controllers, views, configs, routes, migrations and commands of its own — anything from a database library to a complete CMS.

The `application/` folder is itself a package, the default one, and so is this documentation.

<a id="creating-packages"></a>

## Creating Packages

Start with a folder inside `packages/`. The example here is `admin`, holding the admin pages.

A package may carry its own `boot.php`, which runs every time the package boots, the way `application/boot.php` does for the application.

#### Creating the package's `boot.php` file:

```php
// file: packages/admin/boot.php

Autoloader::namespaces([
    'Admin' => Package::path('admin').'libraries',
]);
```

That tells Rakit to load the `Admin` namespace from the package's `libraries/` folder. `boot.php` may do anything, but registering classes is what it is usually for — and it is **not required** at all.

<a id="registering-packages"></a>

## Registering Packages

Packages are registered in `application/packages.php`:

#### Registering a simple package:

```php
return ['admin'];
```

By convention that puts the package in `packages/admin/`. The location can be changed:

#### Registering a package with a custom location:

```php
return [

    'admin' => ['location' => 'backend/admin'],

];
```

Rakit now looks in `packages/backend/admin`.

#### Registering a package with an absolute path:

```php
return [

    'admin' => ['location' => 'path: /var/www/my-packages/admin'],

];
```

The `path:` prefix takes an absolute path.

<a id="packages--autoloading"></a>

## Packages & Autoloading

When `boot.php` would only register classes, the mapping can go straight into `application/packages.php`:

#### Defining autoloader mapping for a package:

```php
return [

    'admin' => [
        'autoloads' => [

            'map' => [
                'Admin' => '(:package)/admin.php',
            ],
            'namespaces' => [
                'Admin' => '(:package)/libraries',
            ],
            'directories' => [
                '(:package)/models',
            ],

        ],
    ],

];
```

Each key names a method of the [Autoloader](/docs/autoloading) class and its value is passed to that method. The `(:package)` placeholder is replaced with the path to the package.

<a id="booting-packages"></a>

## Booting Packages

A registered package still has to be booted before it can be used:

#### Booting a package:

```php
Package::boot('admin');
```

That runs the package's `boot.php`.

Calling the `boot()` method also automatically loads the package's `routes.php` file (if it exists).
When `routes.php` exists, the following files are loaded right after it (if they exist):
- `hooks.php` - Hook (event) listeners
- `middlewares.php` - Middleware definitions
- `composers.php` - View composers

> **Note:** A package will only boot once. Subsequent calls to the `boot()` method will be ignored.

A package needed on every request can boot itself, through the `autoboot` option in `application/packages.php`:

#### Commanding a package to boot automatically:

```php
return [

    'admin' => ['autoboot' => true],

];
```

`autoboot` is rarely needed: a package boots itself the first time it is reached, whether by a route, a controller or a middleware of its own.

Every boot fires an event, for whatever has to happen afterwards:

#### Listen to package booting event:

```php
Hook::listen('rakit.booted: admin', function () {
    // The 'admin' package has successfully booted!
    // You can perform additional configuration here
    Config::set('admin::general.pagename', 'My Admin Panel');
});
```

#### Freezing a package:

You can also _"freeze"_ a package so that it cannot boot:

```php
Package::freeze('admin');
```

A frozen package leaves the registered list for the current request, so `Package::boot()` throws for it. Handy for disabling one without touching `application/packages.php`.

#### Checking package status:

```php
// Check if the package is registered
if (Package::exists('admin')) {
    // The 'admin' package is registered
}

// Check if the package is booted
if (Package::booted('admin')) {
    // The 'admin' package is booted
}

// Check if the package is routed
if (Package::routed('admin')) {
    // The 'admin' package has loaded routes
}
```

<a id="routing-to-packages"></a>

## Routing to Packages

Please refer to the [package routing](/docs/routing#route-for-package) and [package controllers](/docs/controllers#package-controller) pages for more detailed guidance on the package routing mechanism.

<a id="using-packages"></a>

## Using Packages

A package holds the same kinds of files as `application/`, and the `::` syntax reaches them:

#### Loading a view belonging to a package:

```php
return View::make('admin::dashboard');
```

#### Retrieving a config item belonging to a package:

```php
return Config::get('admin::uploads.max_size');
```

#### Retrieving a language config item of a package:

```php
return Lang::line('admin::themes.default_theme');
```

The metadata of a package is reachable too:

#### Get the installation location of a package:

```php
$location = Package::path('admin');
// Returns: '/path/to/packages/admin/'
```

#### Get the asset location of a package:

```php
$assets = Package::assets('admin');
// Returns: '/packages/admin/'
```

#### Get package meta-data:

```php
// Get all package meta-data
$metadata = Package::get('admin');

// Get a specific option from the package
$handles = Package::option('admin', 'handles');
$autoboot = Package::option('admin', 'autoboot', false);
```

#### Get list of package names:

```php
// Get all registered package names
$names = Package::names();
// Returns: ['admin', 'docs', ...]

// Get all registered package data
$all = Package::all();
```

#### Parse package identifier:

```php
// Separate package and element from identifier
list($package, $element) = Package::parse('admin::home.index');
// $package = 'admin'
// $element = 'home.index'

// Get package name from identifier
$package = Package::name('admin::home.index');
// Returns: 'admin'

// Get element from identifier
$element = Package::element('admin::home.index');
// Returns: 'home.index'

// Create identifier from package and element
$identifier = Package::identifier('admin', 'home.index');
// Returns: 'admin::home.index'
```

#### Get package prefix:

```php
// Prefix for identifier (view, config, etc.)
$prefix = Package::prefix('admin');
// Returns: 'admin::'

// Prefix for class name
$class_prefix = Package::class_prefix('admin');
// Returns: 'Admin_'
```

#### Resolve package from URI:

```php
// Determine which package handles a certain URI
$package = Package::handles('admin/users');
// Returns: 'admin' (if the admin package handles 'admin')
```

#### Expand package path:

```php
// Convert identifier to full path
$path = Package::expand('admin::controllers/home.php');
// Returns: '/path/to/packages/admin/controllers/home.php'
```

<a id="package-assets"></a>

## Package Assets

A package keeps its CSS, JavaScript and images in an `assets/` folder of its own, so `packages/admin/assets/` for the `admin` package.

`packages/` is not reachable from the web, so a console command copies them into the root `assets/` folder:

#### Publishing assets of a package:

```bash
php rakit package:publish <package-name>
```

The files land in `assets/packages/<package-name>/`.

#### Unpublish package assets:

```bash
php rakit package:unpublish <package-name>
```

This command will delete the `assets/packages/<package-name>` folder.

#### Accessing package assets:

Reach them with `URL::to_asset()` or the `asset()` helper:

```php
<link href="<?php echo URL::to_asset('packages/themable/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo URL::to_asset('packages/themable/js/app.min.js') ?>"></script>
```

Or with the `asset()` helper:

```php
<link href="<?php echo asset('packages/admin/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo asset('packages/admin/js/app.min.js') ?>"></script>
```

> **Tip:** Package assets are automatically published during installation via `package:install`.

<a id="installing-packages"></a>

## Installing Packages

A package can be installed by hand, by extracting its archive into `packages/`, but the [console](/docs/console) does the same in one command:

#### Installing a package via rakit console:

```bash
php rakit package:install themable
```

This command will:
1. Download the package from the official repository
2. Extract to the `packages/<package-name>/` folder
3. Publish assets (if any) to `assets/packages/<package-name>/`
4. Create a `meta.json` file containing package information

> **Note:** Make sure the [cURL](https://www.php.net/manual/en/book.curl.php) extension is active before running this command.

After successful installation, the next step is to [register](#registering-packages) the package to `application/packages.php`.

#### Viewing available packages:

The [official repository](https://rakit.esyede.my.id/repositories) lists what is available.

<a id="upgrading-packages"></a>

## Upgrading Packages

When you upgrade a package, Rakit will:
1. Check the latest compatible version with your Rakit
2. Delete the old version package files
3. Download and install the latest version
4. Delete the published package assets (run `package:publish` to publish them again)

#### Upgrading a package via console:

```bash
php rakit package:upgrade <package-name>
```

> **Warning:** All old package files will be deleted during upgrade. Make sure you have backed up any changes made to the package before running the upgrade.

#### Best Practice: Don't Edit Packages Directly

To change a package's configuration, **do not edit its files**. Listen for the `rakit.booted` event in `application/boot.php` instead:

#### Listen to package booting event:

```php
// File: application/boot.php

Hook::listen('rakit.booted: admin', function () {
    Config::set('admin::general.pagename', 'Admin Panel');
});
```

<a id="removing-packages"></a>

## Removing Packages

Removing a package goes either through the console or by hand.

### Method 1: Via Console (Recommended)

#### Step 1: Reset database migrations (if any)

If the package performs database migrations, delete the tables it created first:

```bash
php rakit migrate:reset <package-name>
```

> **Indication that a package has migrations:** The package has a `migrations/` folder containing migration files.

#### Step 2: Uninstall the package

```bash
php rakit package:uninstall <package-name>
```

This command will delete:
- `packages/<package-name>/` folder
- `assets/packages/<package-name>/` folder (if any)

#### Step 3: Remove package registration

Finally, remove the package registration from the `application/packages.php` file:

```php
// Before:
return [
    'admin',
    'docs',
];

// After (admin removed):
return [
    'docs',
];
```

### Method 2: Manual

1. **Reset database migrations** (if the package has migrations)
   - Run SQL to drop the tables created by the package
   - Or use: `php rakit migrate:reset <package-name>`

2. **Delete package folder**
   - Delete the `packages/<package-name>/` folder
   - Delete the `assets/packages/<package-name>/` folder (if any)

3. **Remove package registration**
   - Open the `application/packages.php` file
   - Remove the package entry from the array

4. **Clear cache** (optional)
   ```bash
   php rakit clear:cache
   ```

> **Tip:** Always backup the database before removing a package that has migrations.
