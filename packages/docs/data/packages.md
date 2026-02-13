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

Packages are a simple way to separate code into smaller units so that they are easier to organize and reuse in other applications.

A package can have its own controllers, views, configs, routes, migrations, commands, and more. A package can be anything, from a database library, authentication system, to a complete CMS.

In fact, the `application/` folder is also a package, namely the default package. Even this documentation page you're reading is a package.

<a id="creating-packages"></a>

## Creating Packages

The first step to create a package is to create a new folder inside the `packages/` folder. For this example, let's create a package named `admin`, which contains the application's admin pages.

The `boot.php` file in the `application/` folder provides some basic configurations that help determine how the application will run.

We can also create a `boot.php` file in our package folder for the same purpose. This file will be executed every time the package is booted (loaded).

#### Creating the package's `boot.php` file:

```php
// file: packages/admin/boot.php

Autoloader::namespaces([
    'Admin' => Package::path('admin').'libraries',
]);
```

The code above tells Rakit that classes with the `Admin` namespace should be loaded from the `libraries/` directory of our package.

You can do whatever you want in the `boot.php` file, but this file is usually only used to register classes to the autoloader.

Actually, you are **not required** to create a `boot.php` file for your package.

Next, we will learn how to register our package to Rakit!

<a id="registering-packages"></a>

## Registering Packages

After creating the admin package, we need to register it to Rakit.

Open the `application/packages.php` file. That's where we can register our package.

Let's register the admin package:

#### Registering a simple package:

```php
return ['admin'];
```

By convention, the above code tells Rakit that the `admin` package is located in the `packages/admin/` folder. However, we can also change its location if needed:

#### Registering a package with a custom location:

```php
return [

    'admin' => ['location' => 'backend/admin'],

];
```

Now Rakit will look for our package in the `packages/backend/admin` folder.

#### Registering a package with an absolute path:

```php
return [

    'admin' => ['location' => 'path: /var/www/my-packages/admin'],

];
```

By using the `path:` prefix, you can specify an absolute path for the package.

<a id="packages--autoloading"></a>

## Packages & Autoloading

Usually, the package's `boot.php` file only contains autoloading registration. So, you can define the mapping of classes belonging to the package via the configuration array in `application/packages.php`. Here's how:

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

Notice that each key in the above array corresponds to the names of methods in the [Autoloader](/docs/autoloading) class.

The values of each configuration array above will be automatically passed to the corresponding method in the `Autoloader` class.

You must have also seen the `(:package)` placeholder. For convenience, this placeholder will be automatically replaced with the path to your package.

<a id="booting-packages"></a>

## Booting Packages

So far, our package has been created and registered, but we can't use it yet. We must load it (boot) first:

#### Booting a package:

```php
Package::boot('admin');
```

This method will execute the `boot.php` file of the admin package, which registers the classes in the admin package to the autoloader.

Calling the `boot()` method also automatically loads the following files (if they exist):
- `routes.php` - Package routes
- `events.php` - Event listeners
- `middlewares.php` - Middleware definitions
- `composers.php` - View composers

> **Note:** A package will only boot once. Subsequent calls to the `boot()` method will be ignored.

If you want to use the package throughout the application, you might need to boot the package on every request. This is quite inconvenient. If so, you can command the package to always boot automatically. Do this by adding the `autoboot` configuration to the `application/packages.php` file like this:

#### Commanding a package to boot automatically:

```php
return [

    'admin' => ['autoboot' => true],

];
```

You don't always have to define `autoboot` explicitly. Rakit has a **lazy loading** mechanism - the package will boot automatically when first accessed.

For example, if you call a view, config, language, route, or middleware belonging to a package, that package will boot itself automatically.

Every time a package boots, an event is fired. You can use this event when you need to do something after the package finishes booting:

#### Listen to package booting event:

```php
Event::listen('rakit.booted: admin', function () {
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

After being frozen, the package cannot be booted even if it is registered. Useful for temporarily disabling a package without removing its registration.

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

Please refer to the [package routing](/docs/routing#routing-paket) and [package controllers](/docs/controllers#controller-paket) pages for more detailed guidance on the package routing mechanism.

<a id="using-packages"></a>

## Using Packages

As mentioned earlier, a package can have its own controllers, views, configs, routes, migrations, commands, and more, just like the structure in the `application/` folder.

Rakit uses the `::` (double colon) syntax to load these items. Let's see the examples:

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

Sometimes, you want to see more "meta-data" information about a package. Here are the available methods:

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
$classPrefix = Package::class_prefix('admin');
// Returns: 'Admin_'
```

#### Resolve package from URI:

```php
// Determine which package handles a certain URI
$package = Package::handles('/admin/users');
// Returns: 'admin' (if the admin package handles '/admin')
```

#### Expand package path:

```php
// Convert identifier to full path
$path = Package::expand('admin::controllers/home.php');
// Returns: '/path/to/packages/admin/controllers/home.php'
```

<a id="package-assets"></a>

## Package Assets

If the package you create has views, it probably has assets such as CSS, JavaScript, and images that need to be included.

Just create an `assets/` folder inside your package and put your asset files in it. So, for example, if your package is named `admin`, put your asset files in the `packages/admin/assets/` folder.

Since the `packages/` folder is not directly accessible via the web, Rakit provides a console command to publish (copy) package assets to the `assets/` directory in the root. Here's how:

#### Publishing assets of a package:

```bash
php rakit package:publish <package-name>
```

This command will create a subfolder in `assets/packages/` corresponding to the package name. For example, if the package name is `admin`, it will create the `assets/packages/admin` folder, which contains copies of the asset files from the admin package.

#### Unpublish package assets:

```bash
php rakit package:unpublish <package-name>
```

This command will delete the `assets/packages/<package-name>` folder.

#### Accessing package assets:

After publishing, you can access package assets using the `URL::to_asset()` method or the `asset()` helper:

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

Of course, you can install packages manually by downloading their archive and extracting it to the `packages/` folder. However, there is a more practical way to install packages, namely via [Rakit Console](/docs/console).

Rakit uses a simple ZIP extraction mechanism for package installation. Here's how:

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

Want to know what packages are available? Visit the [official Rakit repository](https://rakit.esyede.my.id/repositories)

<a id="upgrading-packages"></a>

## Upgrading Packages

When you upgrade a package, Rakit will:
1. Check the latest compatible version with your Rakit
2. Delete the old version package files
3. Download and install the latest version
4. Republish the package assets

#### Upgrading a package via console:

```bash
php rakit package:upgrade <package-name>
```

> **Warning:** All old package files will be deleted during upgrade. Make sure you have backed up any changes made to the package before running the upgrade.

#### Best Practice: Don't Edit Packages Directly

If you need to change package configurations, **don't edit the package files directly**. Do the changes by listening to the `rakit.booted` event in the `application/boot.php` file:

#### Listen to package booting event:

```php
// File: application/boot.php

Event::listen('rakit.booted: admin', function () {
    Config::set('admin::general.pagename', 'Admin Panel');
});
```

<a id="removing-packages"></a>

## Removing Packages

In addition to installing and upgrading packages, you can also remove packages that are no longer needed.

There are 2 ways to do this: via console (automatic) or manual. Let's try!

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

To remove a package manually, follow these steps:

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
   php rakit cache:clear
   ```

> **Tip:** Always backup the database before removing a package that has migrations.
