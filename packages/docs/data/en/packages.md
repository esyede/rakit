# Package

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#pengetahuan-dasar)
-   [Creating Package](#membuat-paket)
-   [Registering Package](#mendaftarkan-paket)
-   [Package & Autoloading](#paket--autoloading)
-   [Booting Packages](#booting-paket)
-   [Routing To Packages](#routing-ke-paket)
-   [Using Packages](#menggunakan-paket)
-   [Package Assets](#aset-paket)
-   [Installing Packages](#menginstall-paket)
-   [Upgrading Packages](#mengupgrade-paket)
-   [Deleting Packages](#menghapus-paket)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Basic Knowledge

Packages are a simple way to separate code into smaller units so that
easier to organize and reuse in other applications.

A package can have it's own controllers, views, configs, routes, migrations, commands, etc.
A package could be everything from a database ORM to a robust authentication system.

In fact, the `application/` folder is also a package, the "default package".
Even the documentation you are reading is also a package.

<a id="membuat-paket"></a>

## Creating Package

The first step in creating a package is to create a folder for
the package within your packages directory.

For this example, let's create an `'admin'` package,
which could house the administrator back-end to our application.

The `application/boot.php` file provides some basic configuration that helps
to define how our application will run.

Likewise we'll create a `boot.php` file within our new package folder for the same purpose.
It is run every time the package is loaded. Let's create it:

#### Creating a package `boot.php` file:

```php
// file: packages/admin/boot.php

Autoloader::namespaces([
    'Admin' => Package::path('admin').'libraries',
]);
```

In this boot file we've told the autoloader that classes that are
namespaced to `Admin` should be loaded out of our package's `libraries` directory.

You can do anything you want in your boot file,
but typically it is used for registering classes with the autoloader.

In fact, you aren't required to create a boot file for your package..

Next, we'll look at how to register this package with our application!

<a id="mendaftarkan-paket"></a>

## Registering Package

Now that we have our admin package, we need to register it with Rakit.

Pull open your `application/packages.php` file.
This is where you register all packages used by your application.
Let's add ours:

#### Mendaftarkan paket sederhana:

```php
return [
    'admin',
];
```

By convention, Rakit will assume that the `Admin` package is located
at the root level of the `packages/` directory,
but we can specify another location if we wish:

#### Registering a package with a custom location:

```php
return [

    'admin' => [
        'location' => 'backend/admin',
    ],

];
```

Now rakit will look for our package in `packages/backend/admin` folder.

<a id="paket--autoloading"></a>

## Package & Autoloading

Typically, a package's `boot.php` file only contains autoloader registrations.
So, you may want to just skip `boot.php` and declare your package's mappings
right in its registration array. Here's how:

#### Defining autoloader mappings in a package registration:

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

Notice that each of these options corresponds to a method on the [autoloader](/docs/en/autoloading).

Yes, the value of the option will automatically be passed
to the corresponding method on the `Autoloader` class.

You may have also noticed the `(:package)` place-holder.
For convenience, this will automatically be replaced with the path to the package.

<a id="booting-paket"></a>

## Booting Packages

So our package is created and registered, but we can't use it yet. First, we need to boot it:

#### Booting a package:

```php
Package::boot('admin');
```

This tells rakit to run the `boot.php` file for the package, which will register its classes in the autoloader.
The boot method will also load the `routes.php` file for the package if it is present.

> The package will only be booted once. Subsequent calls to the `boot()` method will be ignored.

If you use a package throughout your application, you may want it to boot on every request.
If this is the case, you can configure the package to auto-boot in your `application/packages.php` file:

#### Configuration a package to auto-boot:

```php
return [

    'admin' => ['autoboot' => true],

];
```

Anda tidak selalu harus mendefinisikan `'autoboot'` secara eksplisit supaya paket anda
bisa booting secara otomatis. Bahkan, anda bisa membuat _seolah-olah_ paket tersebut
booting secara otomatis.

You do not always need to explicitly boot a package.
In fact, you can usually code as if the package was auto-booted and rakit will take care of the rest.

For example, if you attempt to use a package's views, configurations, languages, routes or filters,
the package will automatically be booted!

Each time a package is booted, it fires an event. You can listen for the starting of packages like so:

<a href="#listen-event-pengaktifan-paket"></a>

#### Listen for a package's boot event:

```php
Event::listen('rakit.booted: admin', function () {
    // Booting paket 'admin' berhasil!
});
```

It is also possible to _"freeze"_ a package so that it will never be booted.

#### Freezing a package so it can't be booted:

```php
Package::freeze('admin');
```

<a id="routing-ke-paket"></a>

## Routing To Packages

Refer to the documentation on [package routing](/docs/en/routing#routing-paket) and
[package controllers](/docs/en/controllers#controller-paket) for more information
on routing and packages.

<a id="menggunakan-paket"></a>

## Using Packages

As mentioned previously, packages can have views, configuration, language files and more.
Rakit uses a `::` (double-colon) syntax for loading these items.
So, let's look at some examples:

#### Loading a package view:

```php
return View::make('admin::dashboard');
```

#### Loading a package configuration item:

```php
return Config::get('admin::uploads.max_size');
```

#### Loading a package language line:

```php
return Lang::line('admin::themes.default_theme');
```

Sometimes you may need to gather more _"meta-data"_ information about a package,
such as whether it exists, its location, or perhaps its entire configuration array.
Here's how:

#### Determine whether a package exists:

```php
if (Package::exists('admin')) {
    // The 'admin' package exists!
}
```

#### Retrieving the installation location of a package:

```php
$location = Package::path('admin');
// dd($location);
```

#### Retrieving the meta-data of a package:

```php
$metadata = Package::get('admin');
// dd($metadata);
```

#### Retrieving the names of all installed packages:

```php
$names = Package::names();
// dd($names);
```

<a id="aset-paket"></a>

## Package Assets

If your package contains views, it is likely you have assets such as JavaScript and images
that need to be available in the root's assets directory of the application.

No problem. Just create an `assets/` folder within your package
and place all of your assets in this folder.

Great! But, how do they get into the root `assets/` folder.
The rakit console provides a simple command to copy all of your package's assets
to the root's `assets/` directory. Here it is:

#### Publish package assets into the root's assets directory:

```bash
php rakit package:publish <package-name>
```

Perintah ini akan membuat sebuah subfolder di `assets/packages/` sesuai nama paket yang diinstall.
Misalnya, jika paket yang diinstall bernama `admin`, maka akan dibuat
folder `assets/packages/admin`, yang akan berisi file-file salinan dari file aset bawaan paket admin.

This command will create a folder for the package's assets within the `assets/packages` directory.

For example, if your package is named _"admin"_, a `assets/packages/admin` folder will be created,
which will contain all of the files of the `packages/admin/assets` folder.

Then how to get the path to package assets after publishing?
You can use the `URL::to_asset()` method or the `asset()` helper as follows:

```php
<link href="<?php echo URL::to_asset('packages/themable/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo URL::to_asset('packages/themable/js/app.min.js') ?>"></script>
```

or,

```php
<link href="<?php echo asset('packages/themable/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo asset('packages/themable/js/app.min.js') ?>"></script>
```

<a id="menginstall-paket"></a>

## Installing Packages

Tentu saja, anda boleh menginstall paket secara manual dengan mendownload arsipnya dan mengekstraknya
ke folder `packages/`. Akan tetapi, ada cara yang lebih asyik untuk menginstall paket, yaitu
via [rakit console](/docs/en/console).

Of course, you may always install packages manually; however, the [rakit console](/docs/en/consi[ole])
provides an awesome method of installing and upgrading your package.

The framework uses simple Zip extraction to install the package. Here's how it works.

#### Installing a package via rakit console:

```bash
php rakit package:install themable
```

> Make sure you have enabled the [PHP cURL](https://www.php.net/manual/en/book.curl.php)
> extension before running this command.

Great! Now that you're package is installed, you're ready to [register it](#mendaftarkan-paket).

Need a list of available packages?
Check out the [official repository](https://rakit.esyede.my.id/repositories)

<a id="mengupgrade-paket"></a>

## Upgrading Packages

When you upgrade a package, rakit will automatically remove the old package and install a fresh copy.

#### Mengupgrade paket via console:

```bash
php rakit package:upgrade <package-name>
```

> Since the package is totally removed on an upgrade, you must be aware of
> any changes you have made to the package code before upgrading.

> You may need to change some configuration options in a package.
> Instead of modifying the package code directly,
> use the package boot events to set them.
> Place something like this in your `application/boot.php` file.

<a href="rakit.booted"></a>

#### Listening for a package's boot event:

```php
// File: application/boot.php

Event::listen('rakit.booted: admin', function () {
    Config::set('admin::general.pagename', 'Admin Panel');
});
```

<a id="menghapus-paket"></a>

## Deleting Packages

Apart from installing and upgrading packages, of course you can also remove packages.

There are 2 ways to do this, automatically and manually. Let's try!

#### Menghapus paket secara otomatis:

First, if the package performing database migrations, you need to remove
the database tables that it had created before:

```bash
php rakit migrate:reset <package-name>
```

> An indication of a package performing database migrations is the package
> has a `migrations/` folder which contains the migration files.

Next, we need to clean up the package's asset files:

```bash
php rakit package:uninstall <package-name>
```

This command will delete `assets/packages/<package-name>/` folder from your application.

Finally, just delete the package registry from the `application/packages.php` file.

#### Manually deleting packages:

To remove packages manually, you need to repeat the above commands manually:

1. If the package performing database migrations, you need to remove those database tables.
2. Delete the `assets/packages/<nama-paket>/` if any.
3. Finally, delete the package registry from the `application/packages.php` file.
