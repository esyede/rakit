<?php

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Config Loader
|--------------------------------------------------------------------------
|
| Config loader is responsible for returning configuration array for
| packages and specific files. By default, we use the file provided by
| rakit; however, you are free to use your own mechanism to handle
| configuration array.
|
*/

System\Event::listen(System\Config::LOADER, function ($package, $file) {
    return System\Config::file($package, $file);
});

/*
|--------------------------------------------------------------------------
| Class Alias
|--------------------------------------------------------------------------
|
| Class alias allows you to use classes without having to import them.
| Here we only register alias for rakit's built-in classes, and of course
| you can add your own alias as needed.
|
*/

System\Autoloader::$aliases = System\Config::get('aliases');

/*
|--------------------------------------------------------------------------
| Autoload Directories
|--------------------------------------------------------------------------
|
| Rakit's autoloader also supports autoloading directories via PSR-0
| convention. This convention basically manages classes using namespace
| as directory structure and class location.
|
*/

System\Autoloader::directories([
    path('app') . 'controllers',
    path('app') . 'models',
    path('app') . 'libraries',
    path('app') . 'commands',
    path('app') . 'jobs',

    // Add your own directories here..
]);

/*
|--------------------------------------------------------------------------
| View Loader
|--------------------------------------------------------------------------
|
| View loader is responsible for returning the path of the package and view.
|
*/

System\Event::listen(System\View::LOADER, function ($package, $view) {
    return System\View::file($package, $view, System\Package::path($package) . 'views');
});

/*
|--------------------------------------------------------------------------
| Language Loader
|--------------------------------------------------------------------------
|
| Language loader is responsible for returning the array of language lines.
|
*/

System\Event::listen(System\Lang::LOADER, function ($package, $language, $file) {
    return System\Lang::file($package, $language, $file);
});

/*
|--------------------------------------------------------------------------
| Enable Blade Engine
|--------------------------------------------------------------------------
|
| We need to enable the blade engine here so that it can be used
| directly from within your controllers.
|
*/

System\Blade::sharpen();

/*
|--------------------------------------------------------------------------
| Set Default Timezone
|--------------------------------------------------------------------------
|
| Here we set the default timezone according to your configuration.
|
*/

date_default_timezone_set(System\Config::get('application.timezone', 'UTC'));

/*
|--------------------------------------------------------------------------
| Load Session
|--------------------------------------------------------------------------
|
| We also need to load session if you have set the session driver.
|
*/

if (!System\Request::cli() && filled(System\Config::get('session.driver'))) {
    System\Session::load();
}

/*
|--------------------------------------------------------------------------
| Autoload Composer Dependencies
|--------------------------------------------------------------------------
|
| Then we autoload composer dependencies so that all classes
| inside vendor folder can be recognized by rakit.
|
*/

if (is_file($path = System\Config::get('application.composer_autoload'))) {
    require_once $path;
    unset($path);
}

/*
|--------------------------------------------------------------------------
| Custom Booting Logic
|--------------------------------------------------------------------------
|
| If you have any custom booting logic, you can add it here.
|
*/

// ..
