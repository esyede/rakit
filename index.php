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
// Detect Worker Mode (FrankenPHP / RoadRunner)
// --------------------------------------------------------------
// Swoole leaves no trace to detect, so its server script defines
// RAKIT_WORKER_MODE as 'swoole' before requiring this file.
if (! defined('RAKIT_WORKER_MODE')) {
    if (
        function_exists('frankenphp_handle_request')
        && ! empty($_SERVER['FRANKENPHP_WORKER'])
    ) {
        define('RAKIT_WORKER_MODE', 'frankenphp');
    } elseif (
        function_exists('getenv') 
        && 'http' === getenv('RR_MODE')
    ) {
        define('RAKIT_WORKER_MODE', 'roadrunner');
    }
}

// --------------------------------------------------------------
// Run the framework
// --------------------------------------------------------------
require path('system') . 'boot.php';

// --------------------------------------------------------------
// Worker Loop
// --------------------------------------------------------------
// Swoole runs its own event loop, started by the server script.
if (
    defined('RAKIT_WORKER_MODE') 
    && 'swoole' !== RAKIT_WORKER_MODE
) {
    \System\Worker\Worker::create(RAKIT_WORKER_MODE)->run();
}
