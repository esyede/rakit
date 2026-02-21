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
