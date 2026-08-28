<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Call Init Script
|--------------------------------------------------------------------------
| Call init script before first boot.
*/

require __DIR__.DS.'init.php';

/*
|--------------------------------------------------------------------------
| Load Helpers and Autoloader
|--------------------------------------------------------------------------
|
| Load Helpers and Autoloader before core for early debugger initialization.
|
*/

require path('system').'helpers.php';
require_once path('system').'autoloader.php';
spl_autoload_register(['\System\Autoloader', 'load']);
\System\Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Run the Core Boot
|--------------------------------------------------------------------------
|
| With the inclusion of this file, the core boot of the framework
| will be executed, which contains the autoloader and package registration.
| In essence, after including this file, the rakit framework is
| ready to be used by the developer.
|
*/

require __DIR__.DS.'core.php';

/*
|--------------------------------------------------------------------------
| Early Debugger Initialization
|--------------------------------------------------------------------------
|
| Enable debugger before boot package for early error capture like
| failed Redis/Database connection during session init.
|
*/

use System\Foundation\Oops\Debugger;

$debugger = require path('app').'config'.DS.'debugger.php';
Debugger::$productionMode = (false === (bool) $debugger['activate']);
Debugger::enable(null, path('storage').'logs');

/*
|--------------------------------------------------------------------------
| Boot the 'application' Package
|--------------------------------------------------------------------------
|
| The 'application' package is the default package in the rakit framework.
| Yes, the application/ folder is a package, the default package.
| We need to boot it first, because this package will load all the core
| configuration of the framework.
|
*/

Package::boot(DEFAULT_PACKAGE);

/*
|--------------------------------------------------------------------------
| Re-configure the Debugger
|--------------------------------------------------------------------------
|
| Re-configure the debugger based on the configuration settings in the
| application/config/debugger.php file. The config has already been loaded,
| we need to re-configure it to ensure consistency after the package boot.
|
*/

$debugger = Config::get('debugger');
$template = path('app').'views'.DS.'error'.DS.'500.blade.php';

Debugger::$productionMode = (false === (bool) $debugger['activate']);
Debugger::$strictMode = (bool) $debugger['strict'];
Debugger::$scream = (bool) $debugger['scream'];

Debugger::$logSeverity = 0;
Debugger::$errorTemplate = is_file($template) ? $template : null;
Debugger::$time = RAKIT_START;

Debugger::$showBar = (bool) $debugger['debugbar'];
Debugger::$showLocation = (bool) $debugger['location'];
Debugger::$maxDepth = (int) $debugger['depth'];
Debugger::$maxLength = (int) $debugger['length'];
Debugger::$email = (string) $debugger['email'];
Debugger::detectDebugMode();
Debugger::dispatch();

/*
|--------------------------------------------------------------------------
| Drop the Redundant Config Revalidation
|--------------------------------------------------------------------------
|
| Config::get() re-stats the owning file on every single read to notice edits.
| Its caches are plain statics though, so PHP clears them when the request
| ends and the next request re-reads the file regardless. The check therefore
| only ever catches a config file changing *during* one request, and pays two
| filesystem calls per read for it — which hurts on shared hosting, where the
| document root often lives on network storage.
|
| Development keeps the check anyway, since that is where a surprising cache
| costs the most time. Note that Blade has a similar switch, but it is left
| alone here: compiled templates live on disk and do outlive the request, so
| turning its check off silently would stop edited templates from recompiling.
|
*/

Config::$reload = ! Debugger::$productionMode;

unset($debugger, $template, $debugger);

/*
|--------------------------------------------------------------------------
| Trust the Configured Reverse Proxies
|--------------------------------------------------------------------------
|
| Headers such as X-Forwarded-For and CF-Connecting-IP are sent by the client,
| so they are only worth reading when the request actually arrives through a
| proxy we put in front of the application. Until that list is configured they
| are ignored and the peer address is used as-is.
|
*/

$proxies = Config::get('application.trusted_proxies', []);

if (is_array($proxies) && count($proxies) > 0) {
    Foundation\Http\Request::setTrustedProxies($proxies);
}

$hosts = Config::get('application.trusted_hosts', []);

if (is_array($hosts) && count($hosts) > 0) {
    Foundation\Http\Request::setTrustedHosts($hosts);
}

unset($proxies, $hosts);

/*
|--------------------------------------------------------------------------
| Timeline: Log Boot Phase
|--------------------------------------------------------------------------
|
| After the debugger is dispatched (and the collector initialized),
| record the boot phase duration for the debug bar's Timeline panel.
| This is placed *after* the dispatch so that it is not overwritten
| by `Collectors::initialize()`.
|
*/

$rakit_boot_done = microtime(true);

if (class_exists('\System\Foundation\Oops\Debugger') && ! \System\Foundation\Oops\Debugger::$productionMode) {
    Foundation\Oops\Collectors::addTimer('Booting', ($rakit_boot_done - RAKIT_START) * 1000, 0);
}

/*
|--------------------------------------------------------------------------
| Boot Other Packages
|--------------------------------------------------------------------------
|
| We know, the packages used in your application can be autoboot
| so they can be used directly without having to be manually booted.
| Here we do it.
|
*/

foreach (Package::$packages as $package => $config) {
    if (isset($config['autoboot']) && $config['autoboot']) {
        Package::boot($package);
    }
}

/*
|--------------------------------------------------------------------------
| Register Catch-All Route
|--------------------------------------------------------------------------
|
| This route handles all routes that cannot be found in your application,
| and will trigger the 404 event so that developers can easily change
| how it is handled according to their needs.
|
*/

Routing\Router::register('*', '(:all)', function () {
    return Hook::first('404');
});

/*
|--------------------------------------------------------------------------
| Read URI And Locale
|--------------------------------------------------------------------------
|
| When a request is routed, we need to read the URI and supported locale
| of the destination route so that we can redirect the request to the
| appropriate location and set the appropriate language.
|
*/

$languages = Config::get('application.languages', ['en']);
$languages[] = Config::get('application.language', 'en');
$languages = array_filter($languages, function ($lang) {
    return is_string($lang) && preg_match('/^[a-zA-Z0-9_-]+$/', $lang);
});
usort($languages, function ($a, $b) {
    return strlen($b) - strlen($a);
});

/*
|--------------------------------------------------------------------------
| Set the Locale Based On Route
|--------------------------------------------------------------------------
|
| When the URI starts with one of the supported 'locale', we will set
| the default language based on the URI segment, then we set the URI
| and we tell the Router not to include the 'locale' segment.
|
*/

$uri = URI::current();
$uri = (! is_string($uri) || empty($uri)) ? '/' : $uri;

foreach ($languages as $language) {
    if (preg_match('#^'.$language.'(?:$|/)#i', $uri)) {
        Config::set('application.language', $language);
        $uri = trim(substr((string) $uri, strlen($language)), '/');
        break;
    }
}

URI::$uri = ('' === $uri) ? '/' : $uri;

/*
|--------------------------------------------------------------------------
| Direct Incoming Request
|--------------------------------------------------------------------------
|
| Finally, we can direct the request to the correct location and execute it
| to get a response. This response is an instance of the \System\Response
| class that we can send to the browser.
|
*/

$domain = Request::foundation()->getHost();

// Mark the boundaries of each phase (routing -> controller -> render).
$rakit_timeline_route_start = microtime(true);
Request::$route = Routing\Router::route(Request::method(), $uri, $domain);

$rakit_timeline_controller_start = microtime(true);
$response = Request::$route->call();

$rakit_timeline_render_start = microtime(true);

/*
|--------------------------------------------------------------------------
| Render the Response
|--------------------------------------------------------------------------
|
| This method evaluates the response content and converts it to a string.
|
*/

$response->render();
$rakit_timeline_render_done = microtime(true);

/*
|--------------------------------------------------------------------------
| Timeline: Mark the Routing / Controller / Render Phase
|--------------------------------------------------------------------------
|
| Break down the application's execution duration into phases so that
| the debug bar's Timeline panel shows where time is spent
| (routing, controller/action, and view rendering).
| The bar is positioned relative to RAKIT_START.
|
*/

if (class_exists('\System\Foundation\Oops\Debugger') && ! \System\Foundation\Oops\Debugger::$productionMode) {
    Foundation\Oops\Collectors::addTimer(
        'Routing',
        ($rakit_timeline_controller_start - $rakit_timeline_route_start) * 1000,
        ($rakit_timeline_route_start - RAKIT_START) * 1000
    );
    Foundation\Oops\Collectors::addTimer(
        'Controller',
        ($rakit_timeline_render_start - $rakit_timeline_controller_start) * 1000,
        ($rakit_timeline_controller_start - RAKIT_START) * 1000
    );
    Foundation\Oops\Collectors::addTimer(
        'Render',
        ($rakit_timeline_render_done - $rakit_timeline_render_start) * 1000,
        ($rakit_timeline_render_start - RAKIT_START) * 1000
    );
}

/*
|--------------------------------------------------------------------------
| Persist Session
|--------------------------------------------------------------------------
|
| If there is an active session, we will save it so that it can be used in
| the next request. This will also set the session cookie in the cookie
| jar to be sent to the user.
|
*/

if (Config::get('session.driver') && Session::started()) {
    Session::save();
}

/*
|--------------------------------------------------------------------------
| Send Response to Browser
|--------------------------------------------------------------------------
|
| Here we will send the response to the browser. This method will send
| all headers and content of the response to the browser.
|
*/

$response->send();

/*
|--------------------------------------------------------------------------
| Okay, Done!
|--------------------------------------------------------------------------
|
| Fire the 'done' event to allow other outputs (such as logging, error handling)
| to be added to the response.
|
*/

Hook::fire('rakit.done', [$response]);

/*
|--------------------------------------------------------------------------
| Finish the Request
|--------------------------------------------------------------------------
|
| Send the response to the browser and finish the request.
*/

$response->foundation()->finish();
