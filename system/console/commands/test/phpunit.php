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

// Path constants
require 'paths.php';

// Boot the core
require path('system') . 'core.php';

// Boot the default package
\System\Package::boot(DEFAULT_PACKAGE);
