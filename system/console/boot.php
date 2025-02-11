<?php

namespace System\Console;

defined('DS') or exit('No direct access.');

use System\Package;
use System\Config;

// Boot paket default agar seluruh dependensi terdaftar di Container.
Package::boot(DEFAULT_PACKAGE);

$default = Config::get('database.default');

// Set database default jika user mengoper '--database'.
if (!is_null($database = get_cli_option('database'))) {
    Config::set('database.default', $database);
}

// Juga daftarkan dependensi command ke Container.
require path('system') . 'console' . DS . 'dependencies.php';

// Cek apakah konsol support warna
$color = function_exists('posix_isatty') && @posix_isatty(STDOUT);
if (DS === '\\') {
    $color = (function_exists('sapi_windows_vt100_support') && @sapi_windows_vt100_support(STDOUT))
        || (getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON');
}

// Bungkus error kedalam try-catch agar lebih mudah dibaca.
try {
    Console::run(array_slice($arguments, 1));
    Config::set('database.default', $default);
} catch (\Throwable $e) {
    Config::set('database.default', $default);
    $e = sprintf("Error: %s \r\n\tin %s:%s", $e->getMessage(), $e->getFile(), $e->getLine());
    echo $color ? "\033[31m{$e}\033[m" : $e;
} catch (\Exception $e) {
    Config::set('database.default', $default);
    $e = sprintf("Error: %s \r\n\tin %s:%s", $e->getMessage(), $e->getFile(), $e->getLine());
    echo $color ? "\033[31m{$e}\033[m" : $e;
}

echo PHP_EOL;
