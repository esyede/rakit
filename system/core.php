<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Define Framework Version
|--------------------------------------------------------------------------
| The framework version currently in use.
*/

define('RAKIT_VERSION', '0.9.9');

/*
|--------------------------------------------------------------------------
| Define Framework Constants
|--------------------------------------------------------------------------
| Constants that stay available globally.
*/

define('DEFAULT_PACKAGE', 'application');
define('RAKIT_KEY', require path('rakit_key'));

/*
|--------------------------------------------------------------------------
| Load Core Classes
|--------------------------------------------------------------------------
| Used by every request, or by the configuration classes themselves,
| so requiring them is faster than going through the autoloader.
*/

require path('system').'container.php';
require path('system').'hook.php';
require path('system').'package.php';
require path('system').'config.php';
require path('system').'helpers.php';
require_once path('system').'autoloader.php';
require path('system').'request.php';
require path('system').'response.php';
require path('system').'blade.php';

/*
|--------------------------------------------------------------------------
| Register the Framework Autoloader
|--------------------------------------------------------------------------
| Put it on the SPL stack, so classes load lazily.
*/

spl_autoload_register(['\System\Autoloader', 'load']);

/*
|--------------------------------------------------------------------------
| Register the 'System' Namespace
|--------------------------------------------------------------------------
| Map the 'System' namespace to its directory, PSR-0 style.
*/

Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Build the Foundation Request
|--------------------------------------------------------------------------
| Buid the Rakit HTTP wrapper from the HTTP foundation.
*/

Request::$foundation = Foundation\Http\Request::createFromGlobals();

/*
|--------------------------------------------------------------------------
| Determine Application Environment
|--------------------------------------------------------------------------
| From "paths.php" URI mapping, or from the "--env=" option, which wins.
*/

$environments = isset($environments) ? $environments : [];

if (Request::cli()) {
    $environment = get_cli_option('env', getenv('RAKIT_ENV'));
    $environment = empty($environment)
        ? Request::detect_env($environments, gethostname())
        : $environment;
} else {
    $environment = Request::detect_env(
        $environments,
        Request::foundation()->getRootUrl()
    );
}

/*
|--------------------------------------------------------------------------
| Set the Application Environment
|--------------------------------------------------------------------------
| Kept on the foundation's server bag, where the config override reads it.
*/

if (isset($environment) && ! empty($environment)) {
    Request::set_env($environment);
}

/*
|--------------------------------------------------------------------------
| Set the CLI Options
|--------------------------------------------------------------------------
| Parse the console arguments into $_SERVER, so they are reachable anywhere.
*/

if (Request::cli()) {
    list($arguments, $options) = Console\Console::options($_SERVER['argv']);
    $_SERVER['CLI'] = array_change_key_case($options, CASE_UPPER);
}

/*
|--------------------------------------------------------------------------
| Register All Packages (Lazy Loading)
|--------------------------------------------------------------------------
| Registered only, not booted: a package boots the first time it is reached,
| which keeps the startup cost down.
*/

$packages = require path('app').'packages.php';

foreach ($packages as $package => $config) {
    Package::register($package, $config);
}
