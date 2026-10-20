<?php

// Activate output buffering
ob_start();

// Start timer, for benchmarking
define('RAKIT_START', microtime(true));

// Useful constants
define('DS', DIRECTORY_SEPARATOR);
define('CRLF', "\r\n");
define('TAB', "\t");
define('CR', "\r");
define('LF', "\n");

// Testing environment
define('RAKIT_PHPUNIT_RUNNING', true);

// PHPUnit 4.x turns E_DEPRECATED into test errors, and its own code and Prophecy
// raise many on PHP 8.4+: even assertTrue(true) failed. Mute them, as the core
// test bootstrap does.
if (class_exists('PHPUnit_Framework_Error_Deprecated')) {
    PHPUnit_Framework_Error_Deprecated::$enabled = false;
}

error_reporting(error_reporting() & ~E_DEPRECATED & ~E_USER_DEPRECATED);

// Path constants
require 'paths.php';

// Boot the core
require path('system') . 'core.php';

// Boot the default package
\System\Package::boot(DEFAULT_PACKAGE);
