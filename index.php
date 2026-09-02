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
// Include the framework's path definitions
// --------------------------------------------------------------
require __DIR__ . DS . 'paths.php';

// --------------------------------------------------------------
// Detect Worker Mode (FrankenPHP / RoadRunner / Swoole)
// --------------------------------------------------------------
$worker = null;

if (function_exists('frankenphp_handle_request')) {
    define('RAKIT_WORKER_MODE', 'frankenphp');
    $worker = 'frankenphp';
} elseif (getenv('RR_MODE') !== false) {
    define('RAKIT_WORKER_MODE', 'roadrunner');
    $worker = 'roadrunner';
} elseif (extension_loaded('swoole') && isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'swoole') !== false) {
    define('RAKIT_WORKER_MODE', 'swoole');
    $worker = 'swoole';
}

// --------------------------------------------------------------
// Run the framework
// --------------------------------------------------------------
require path('system') . 'boot.php';

// --------------------------------------------------------------
// Worker Loop
// --------------------------------------------------------------
if ($worker !== null) {
    $runner = \System\Bridges\Worker::create($worker);
    $runner->run();
}
