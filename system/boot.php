<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Call Init Script
|--------------------------------------------------------------------------
| Run the init script before the first boot.
*/

require __DIR__.DS.'init.php';

/*
|--------------------------------------------------------------------------
| Load Helpers and Autoloader
|--------------------------------------------------------------------------
| Before the core, so the debugger can initialize early.
*/

require path('system').'helpers.php';
require_once path('system').'autoloader.php';
spl_autoload_register(['\System\Autoloader', 'load']);
\System\Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Run the Core Boot
|--------------------------------------------------------------------------
| Registers the autoloader and the packages. The framework is usable after this.
*/

require __DIR__.DS.'core.php';

/*
|--------------------------------------------------------------------------
| Early Debugger Initialization
|--------------------------------------------------------------------------
| Enabled before the packages boot, to catch errors raised during session init.
*/

use System\Foundation\Oops\Debugger;

$debugger = require path('app').'config'.DS.'debugger.php';
Debugger::$productionMode = (false === (bool) $debugger['activate']);
Debugger::enable(null, path('storage').'logs');

/*
|--------------------------------------------------------------------------
| Boot the 'application' Package
|--------------------------------------------------------------------------
| The default package, which loads the core configuration of the framework.
*/

Package::boot(DEFAULT_PACKAGE);

/*
|--------------------------------------------------------------------------
| Re-configure the Debugger
|--------------------------------------------------------------------------
| The config file is loaded by now, so apply it in full.
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
Debugger::dispatch();

/*
|--------------------------------------------------------------------------
| Drop the Redundant Config Revalidation
|--------------------------------------------------------------------------
| Config::get() re-stats the owning file on every read, but its caches are plain
| statics that die with the request, so the check only ever catches a config file
| edited mid request and pays two filesystem calls per read for it. Kept in
| development, where a surprising cache costs the most time.
*/

Config::$reload = ! Debugger::$productionMode;

unset($debugger, $template, $debugger);

/*
|--------------------------------------------------------------------------
| Trust the Configured Reverse Proxies
|--------------------------------------------------------------------------
| X-Forwarded-For and CF-Connecting-IP are written by the client, so they are only
| read once the proxies sitting in front of the application are named.
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
| Placed after dispatch(), or Collectors::initialize() would overwrite it.
*/

$rakit_boot_done = microtime(true);

if (
    class_exists('\System\Foundation\Oops\Debugger')
    && ! \System\Foundation\Oops\Debugger::$productionMode
) {
    Foundation\Oops\Collectors::addTimer(
        'Booting',
        ($rakit_boot_done - RAKIT_START) * 1000,
        0
    );
}

/*
|--------------------------------------------------------------------------
| Boot Other Packages
|--------------------------------------------------------------------------
| Packages marked 'autoboot' are booted here.
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
| Handles every URI no route matched, firing the 404 event.
*/

Routing\Router::register('*', '(:all)', function () {
    return Hook::first('404');
});

/*
|--------------------------------------------------------------------------
| Worker Mode: Skip Dispatch
|--------------------------------------------------------------------------
| A worker boots once; the bridge loop dispatches each request instead.
*/

if (defined('RAKIT_WORKER_MODE')) {
    return;
}

/*
|--------------------------------------------------------------------------
| Read URI And Locale
|--------------------------------------------------------------------------
| Longest first, so 'id-ID' is matched before 'id'.
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
| A leading locale segment sets the language and is taken off the URI.
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
| Route the request and call it, giving a \System\Response.
*/

$domain = Request::foundation()->getHost();

// Mark the boundaries of each phase (routing -> controller -> render).
$rakit_tl_route_start = microtime(true);
Request::$route = Routing\Router::route(Request::method(), $uri, $domain);

$rakit_tl_controller_start = microtime(true);
$response = Request::$route->call();

$rakit_tl_render_start = microtime(true);

/*
|--------------------------------------------------------------------------
| Render the Response
|--------------------------------------------------------------------------
| Evaluate the response content into a string.
*/

$response->render();
$rakit_tl_render_done = microtime(true);

/*
|--------------------------------------------------------------------------
| Timeline: Mark the Routing / Controller / Render Phase
|--------------------------------------------------------------------------
| Split the request into phases for the debug bar's Timeline, relative to RAKIT_START.
*/

if (
    class_exists('\System\Foundation\Oops\Debugger')
    && ! \System\Foundation\Oops\Debugger::$productionMode
) {
    Foundation\Oops\Collectors::addTimer(
        'Routing',
        ($rakit_tl_controller_start - $rakit_tl_route_start) * 1000,
        ($rakit_tl_route_start - RAKIT_START) * 1000
    );
    Foundation\Oops\Collectors::addTimer(
        'Controller',
        ($rakit_tl_render_start - $rakit_tl_controller_start) * 1000,
        ($rakit_tl_controller_start - RAKIT_START) * 1000
    );
    Foundation\Oops\Collectors::addTimer(
        'Render',
        ($rakit_tl_render_done - $rakit_tl_render_start) * 1000,
        ($rakit_tl_render_start - RAKIT_START) * 1000
    );
}

/*
|--------------------------------------------------------------------------
| Persist Session
|--------------------------------------------------------------------------
| Save the session and put its cookie in the jar.
*/

if (Config::get('session.driver') && Session::started()) {
    Session::save();
}

/*
|--------------------------------------------------------------------------
| Send Response to Browser
|--------------------------------------------------------------------------
| Send the headers and the content.
*/

$response->send();

/*
|--------------------------------------------------------------------------
| Okay, Done!
|--------------------------------------------------------------------------
| Fire 'rakit.done' so listeners can still add to the response.
*/

Hook::fire('rakit.done', [$response]);

/*
|--------------------------------------------------------------------------
| Finish the Request
|--------------------------------------------------------------------------
| Flush the output and close the connection.
*/

$response->foundation()->finish();
