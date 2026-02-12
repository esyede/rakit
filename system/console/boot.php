<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

use System\Package;
use System\Config;

// Boot the default package to register all dependencies in the Container.
Package::boot(DEFAULT_PACKAGE);

$default = Config::get('database.default');

// Set the default database if the user provides '--database'.
if (!is_null($database = get_cli_option('database'))) {
    Config::set('database.default', $database);
}

// Also register command dependencies in the Container.
require path('system') . 'console' . DS . 'dependencies.php';

// Check if console supports color
$color = function_exists('posix_isatty') && @posix_isatty(STDOUT);

if (DS === '\\') {
    $color = (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
        || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
}

// Wrap error in try-catch for easier reading.
try {
    Console::run(array_slice($arguments, 1));
    Config::set('database.default', $default);
} catch (\Throwable $e) {

    Config::set('database.default', $default);
    $err = sprintf('Error: %s', $e->getMessage());

    if (Config::get('debugger.activate')) {
        $err = sprintf(
            "Error: %s \r\n\tin %s:%s\nStack Trace:\r\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }
    echo $color ? "\033[31m{$err}\033[m" : $err;
} catch (\Exception $e) {
    Config::set('database.default', $default);
    $err = sprintf('Error: %s', $e->getMessage());

    if (Config::get('debugger.activate')) {
        $err = sprintf(
            "Error: %s \r\n\tin %s:%s\nStack Trace:\r\n%s",
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
    }

    echo $color ? "\033[31m{$err}\033[m" : $err;
}

echo PHP_EOL;
