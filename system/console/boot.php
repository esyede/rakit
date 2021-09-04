<?php

namespace System\Console;

defined('DS') or exit('No direct script access.');

use System\Package;
use System\Config;

// Boot paket default agar seluruh dependensi terdaftar di Container.
Package::boot(DEFAULT_PACKAGE);

// Set database default jika user mengoper '--database'.
if (! is_null($database = get_cli_option('database'))) {
    Config::set('database.default', $database);
}

// Juga daftarkan dependensi command ke Container.
require path('system').'console'.DS.'dependencies.php';

// Bungkus error kedalam try-catch agar lebih mudah dibaca.
try {
    Console::run(array_slice($arguments, 1));
} catch (\Throwable $e) {
    echo $e->getMessage();
} catch (\Exception $e) {
    echo $e->getMessage();
}

echo PHP_EOL;
