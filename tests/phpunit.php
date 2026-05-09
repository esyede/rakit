<?php

// --------------------------------------------------------------
// Activate output buffering
// --------------------------------------------------------------
ob_start();

// --------------------------------------------------------------
// Record the start timer (for benchmark)
// --------------------------------------------------------------
define('RAKIT_START', microtime(true));

// --------------------------------------------------------------
// Define some useful constants
// --------------------------------------------------------------
define('DS', DIRECTORY_SEPARATOR);
define('CRLF', "\r\n");
define('TAB', "\t");
define('CR', "\r");
define('LF', "\n");

// --------------------------------------------------------------
// Define constant for testing environment
// --------------------------------------------------------------
define('RAKIT_PHPUNIT_RUNNING', true);

// --------------------------------------------------------------
// Mute deprecation noise from vendor (PHPUnit 4.x, Prophecy, etc.)
// --------------------------------------------------------------
// PHPUnit 4.x converts E_DEPRECATED into test errors and PHP itself
// prints any deprecations its handler skips. Vendor libraries emit
// many such warnings on PHP 8.4+ that the framework cannot fix
// without upgrading them. Framework deprecations have been resolved,
// so deprecations are filtered out here to keep results meaningful.
if (class_exists('PHPUnit_Framework_Error_Deprecated')) {
    PHPUnit_Framework_Error_Deprecated::$enabled = false;
}
error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// --------------------------------------------------------------
// Include framework's path definitions
// --------------------------------------------------------------
require dirname(__DIR__) . DS . 'paths.php';

// --------------------------------------------------------------
// Override framework paths for test system folder
// --------------------------------------------------------------
set_path('app', __DIR__ . DS . 'fixtures' . DS . 'application' . DS);
set_path('package', __DIR__ . DS . 'fixtures' . DS . 'packages' . DS);
set_path('storage', __DIR__ . DS . 'fixtures' . DS . 'storage' . DS);
set_path('rakit_key', __DIR__ . DS . 'key.php');

// --------------------------------------------------------------
// Load the framework's core file.
// --------------------------------------------------------------
require path('system') . 'core.php';

// --------------------------------------------------------------
// Boot default package (which will boot the framework as well)
// --------------------------------------------------------------
System\Package::boot(DEFAULT_PACKAGE);
