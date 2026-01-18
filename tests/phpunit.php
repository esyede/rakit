<?php

// --------------------------------------------------------------
// Definisikan konstanta untuk directory separator.
// --------------------------------------------------------------
define('DS', DIRECTORY_SEPARATOR);

// --------------------------------------------------------------
// Definisikan konstanta untuk menandai PHPUnit sedang berjalan.
// --------------------------------------------------------------
define('RAKIT_PHPUNIT_RUNNING', true);

// --------------------------------------------------------------
// Include konstanta path milik framework.
// --------------------------------------------------------------
require dirname(__DIR__) . DS . 'paths.php';

// --------------------------------------------------------------
// Timpa path framework untuk test folder system.
// --------------------------------------------------------------
set_path('app', __DIR__ . DS . 'fixtures' . DS . 'application' . DS);
set_path('package', __DIR__ . DS . 'fixtures' . DS . 'packages' . DS);
set_path('storage', __DIR__ . DS . 'fixtures' . DS . 'storage' . DS);
set_path('rakit_key', __DIR__ . DS . 'key.php');

// --------------------------------------------------------------
// Muat file bootstraper system.
// --------------------------------------------------------------
require path('system') . 'core.php';

// --------------------------------------------------------------
// Boot paket default.
// --------------------------------------------------------------
System\Package::boot(DEFAULT_PACKAGE);

// --------------------------------------------------------------
// Load classmap setelah boot.
// --------------------------------------------------------------
System\Autoloader::load_classmap();
