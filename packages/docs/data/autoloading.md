# Autoloading

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Registering Folders](#registering-folders)
-   [Registering Mappings](#registering-mappings)
-   [Registering Namespaces](#registering-namespaces)
-   [Composer Autoloader](#composer-autoloader)
    -   [Notes for vendor folder](#notes-for-vendor-folder)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Autoloading allows you to lazy-load class files (load class files only when needed)
without explicitly calling `require()` or `include()`.

So, only the classes you actually need will be loaded in your application, and you
can directly use the classes you want without having to manually load them.

By default, the `application/models/` and `application/libraries/` folders are autoloaded via
the `application/boot.php` file so you don't need to register them manually.

The autoloader in Rakit follows the `class name same as file name` convention, where the file name
is written in all lowercase letters.

So for example, the `User` class placed in the `models/` folder must be placed in a file
named `user.php` to be automatically loaded.

You can also place it in subfolders. Just give the class namespace following
the folder structure you create. So, the `Entities\User` class should be placed in
the `entities/user.php` file inside the `models/` folder.

<a id="registering-folders"></a>

## Registering Folders

As explained above, the `models/` and `libraries/` folders are by default
registered to autoload; but, you can also register any folder you
like using the same convention:

#### Registering several folders to the autoloader:

```php
Autoloader::directories([
	path('app').'classes',
	path('app').'utilities',
]);
```

<a id="registering-mappings"></a>

## Registering Mappings

Sometimes you may want to map classes manually to their related files. This is the way
to load classes most efficiently because the autoloader doesn't have to scan folders
to find where your class locations are:

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

If you use libraries with such conventions, just register the root namespace
and its folder location to the autoloader. Rakit will handle the rest.

#### Registering namespace to the autoloader:

```php
Autoloader::namespaces([
	'Doctrine' => path('libraries').'Doctrine',
]);
```

Before namespaces existed in PHP, many libraries used _underscore_ as
their folder indicators.

If you want to use libraries with such conventions, you can still
register them to the autoloader.

For example, if you want to use the old version of [SwiftMailer](https://github.com/swiftmailer/swiftmailer),
you might notice that all their class names start with `Swift_`.

So, what we need to register to the autoloader is the word `Swift`, where that word
is their root namespace.

#### Registering underscored classes to the autoloader:

```php
Autoloader::underscored([
	'Swift' => path('libraries').'Swift_Mailer',
]);
```

<a id="composer-autoloader"></a>

## Composer Autoloader

Of course you've used [Composer](https://getcomposer.org). Composer generally
comes with its own autoloader, which by default is located at `vendor/autoload.php`.

So, our task here is just to include that file into our application so that libraries installed by it can be recognized by Rakit.

It's quite easy, just edit the `application/config/application.php` file and fill
the `composer_autoload` option with the <ins>absolute path</ins> where that autoload file is located.

So if your autoload file is located at `<root>/vendor/autoload.php` then fill it like this:

```php
'composer_autoload' => path('base').'vendor/autoload.php',
```

> If the autoload file fails to load due to incorrect path or other reasons,
> your application will continue to run without displaying an error.

<a id="notes-for-vendor-folder"></a>

### Notes for vendor folder

It should be noted that by default, **there is no protection** provided if
the `vendor/` folder is placed in the root folder, which means all files and subfolders inside it
can be accessed by the public.

This is certainly very dangerous because they will know what libraries you are using.
For this, we provide several options to handle this:

#### Option 1: URL Rewrite

If you are using Apache or Nginx, follow the guide
[pretty URLs](/docs/id/install#pretty-urls) because in that feature, we have
also provided rules to protect the vendor folder.

#### Option 2: Place above document root

If your hosting allows uploading files to folders above the document root, place your
vendor folder there, then change your `composer_autoload` configuration to something like this:

```php
'composer_autoload' => dirname(path('base')).'/vendor/autoload.php',
```

With that, your vendor folder will not be accessible by the public and you can still
use the libraries you install via composer.
