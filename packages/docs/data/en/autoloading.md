# Autoloading

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Registering Folder](#registering-folder)
- [Registering Mapping](#registering-mapping)
- [Registering Namespace](#registering-namespace)
- [Composer Autoloader](#composer-autoloader)
    - [Notes for vendor folder](#notes-for-vendor-folder)

<!-- /MarkdownTOC -->


<a id="basic-knowledge"></a>
## Basic Knowledge

Autoloading enable you to lazyloading class files (loading only needed class files)
without explicitly call `require()` or `include()`.

So, only really needed class will be loaded by your application, and you can immediately use the class you want without doing tedious work of manually loading them.

By default, `application/models/` folder and `application/libraries/` folder are autoloaded in `application/boot.php` file, so you don't need to register it manually.

Autoloader in rakit follow `class name equal file name` convention, where file name
written entirely in lowercase.

So for example, `User` class which is stored in `models/` folder should be stored in a file
called `user.php` to be able to automatically loaded.

You can also stored it in a subfolder. Just give class namespace following
folder structure you just created. So, `Entities\User` class should be stored at `entities/user.php` file in `models/` folder.


<a id="registering-folder"></a>
## Registering Folder

As described above, `models/` folder `libraries/` folder are registered to autoload by default; but, you can also register any folder you like using the same convention:

#### Registering many folders to autoloader:

```php
Autoloader::directories([
    path('app').'classes',
    path('app').'utilities',
]);
```


<a id="registering-mapping"></a>
## Registering Mapping

Sometimes you maybe want to map the class to its related file manually. This is the most efficient way because the autoloader doesn't need to scan folders to look for the location of your class file:

#### Registering mapping to autoloader:

```php
Autoloader::map([
    'Forms\Bootstrap' => path('app').'classes/forms/bootstrap.php',
    'Forms\Bulma'     => path('app').'classes/forms/bulma.php',
]);
```


<a id="registering-namespace"></a>
## Registering Namespace

Some third-party libraries use PSR-4 and PSR-0 standard to autoload their classes.
[PSR-4](https://www.php-fig.org/psr/psr-4/) and [PSR-0](https://www.php-fig.org/psr/psr-0/)
define that class name must match their file name, including its capitalization and folder structure  shown by the namespace.

If you use libraries with convention like this, just register root namespace
and folder location to the autoloader. Rakit will handle the rest.

#### Registering namespace to autoloader:

```php
Autoloader::namespaces([
    'Doctrine' => path('libraries').'Doctrine',
]);
```

Before namespace feature were available in PHP, many libraries used _underscore_ to mark their folder.

If you want to use libraries with convention like this, you can still register it to the autoloader.

For example, if you want to use old version of [SwiftMailer](https://github.com/swiftmailer/swiftmailer)
, you will be familiar that all of its classes has prefix `Swift_`.

So, we need to register the word `Swift` to the autoloader, that word is its root namespace.

#### Registering _underscore_ class to autoloader:

```php
Autoloader::underscored([
    'Swift' => path('libraries').'Swift_Mailer',
]);
```

<a id="composer-autoloader"></a>
## Composer Autoloader

Of course you've ever use [Composer](https://getcomposer.org). Composer generaly had bring its own autoloader, which by default located in `vendor/autoload.php` folder.

So, our task here is just include that file to our application so that installed library can be recognized by rakit.

The task is easy enough, just edit `application/config/aplication.php` file and fill `composer_autoload` option with the <ins>absolute path</ins> of the autoload file.

So, if your autoload file located in `<root>/vendor/autoload.php` then fill it like this:

```php
'composer_autoload' => path('base').'vendor/autoload.php',
```

>  If the autoload file failed to be loaded caused by wrong path or other causes,
   your application will keep running without diplaying errors.



<a id="notes-for-vendor-folder"></a>
### Notes for vendor folder

It's worth to remember that by default, **no protection** provided if `vendor/` folder stored in root folder, which means all files and subfolders inside can be publicly accessed.

This is quite dangerous because they will know what libraries you use.
For this purpose, we provide some options to handle this:


#### Option 1: Rewrite URL

If you use Apache or Nginx, follow the
[pretty url](/docs/install#mempercantik-url) guide because we provide rules to protect vendor folder using that feature.



#### Option 2: Store outside document root

If your hosting allow uploading files to folders outside document root, put your vendor folder there, then change your `composer_autoload` configuration as follow:

```php
'composer_autoload' => dirname(path('base')).'/vendor/autoload.php',
```

This way, your vendor folder can not be accessed publicly and you can keep using your composer-installed libraries.
