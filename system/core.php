<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Define Framework Version
|--------------------------------------------------------------------------
| Define the framework version that is currently being used.
*/

define('RAKIT_VERSION', '0.9.9');

/*
|--------------------------------------------------------------------------
| Define Framework Constants
|--------------------------------------------------------------------------
|
| Define additional constants. These constants are created to make access
| easier because they are available globally.
|
*/

define('DEFAULT_PACKAGE', 'application');
define('RAKIT_KEY', require path('rakit_key'));

/*
|--------------------------------------------------------------------------
| Load Core Classes
|--------------------------------------------------------------------------
|
| Here we load the classes that are used in every request, or
| that are used by the configuration classes.
| Faster and easier to load manually than using an autoloader.
|
*/

require path('system') . 'container.php';
require path('system') . 'event.php';
require path('system') . 'package.php';
require path('system') . 'config.php';
require path('system') . 'helpers.php';
require_once path('system') . 'autoloader.php';
require path('system') . 'request.php';
require path('system') . 'response.php';
require path('system') . 'blade.php';

/*
|--------------------------------------------------------------------------
| Register the Framework Autoloader
|--------------------------------------------------------------------------
|
| Next we register the Framework Autoloader to the SPL autoloader stack
| so that classes can be lazyloaded when we need them.
|
*/

spl_autoload_register(['\System\Autoloader', 'load']);

/*
|--------------------------------------------------------------------------
| Register the 'System' Namespace
|--------------------------------------------------------------------------
|
| Register the 'System' namespace and its directory mapping so that it can be
| loaded by the autoloader using PSR-0 conventions.
|
*/

Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Build the Foundation Request
|--------------------------------------------------------------------------
|
| Rakit separates the implementation details of handling HTTP requests into
| the 'foundation/http/' folder to keep the 'system/request.php' file from
| being too long and still easy to read. Here, we need to call it.
|
*/

Request::$foundation = Foundation\Http\Request::createFromGlobals();

/*
|--------------------------------------------------------------------------
| Determine Application Environment
|--------------------------------------------------------------------------
|
| Next, we are ready to determine the application environment. This can
| be set via CLI or via mapping URI to the environment defined in the
| "paths.php" file. When determining the environment via CLI option,
| the "--env=" option will automatically override the mapping in "paths.php".
|
*/

if (Request::cli()) {
    $environment = get_cli_option('env', getenv('RAKIT_ENV'));
    $environment = empty($environment) ? Request::detect_env($environments, gethostname()) : $environment;
} else {
    $environment = Request::detect_env($environments, Request::foundation()->getRootUrl());
}

/*
|--------------------------------------------------------------------------
| Set the Application Environment
|--------------------------------------------------------------------------
|
| After we have determined the application environment, we will set it on
| the array server global from the request foundation.
| This will make it available throughout the application, even though it is
| only used to determine which configuration should be overridden.
|
*/

if (isset($environment) && !empty($environment)) {
    Request::set_env($environment);
}

/*
|--------------------------------------------------------------------------
| Set the CLI Options
|--------------------------------------------------------------------------
|
| When the request is coming from the Rakit console, we parse the arguments
| and options, then set them to the global $_SERVER variable so they can be
| accessed from anywhere.
|
*/

if (Request::cli()) {
    list($arguments, $options) = Console\Console::options($_SERVER['argv']);
    $_SERVER['CLI'] = array_change_key_case($options, CASE_UPPER);
}

/*
|--------------------------------------------------------------------------
| Register All Packages (Lazy Loading)
|--------------------------------------------------------------------------
|
| Finally, we will register all packages that have been defined.
| Registration is done lazily: packages are only booted when they are first
| accessed, not at the beginning of the application. This reduces startup
| overhead. Here, we will not perform auto-boot, only set it so that it can
| be called by the developer when they need it.
|
*/

$packages = require path('app') . 'packages.php';

foreach ($packages as $package => $config) {
    Package::register($package, $config);
}
