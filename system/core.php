<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Versi Framework Saat Ini
|--------------------------------------------------------------------------
| Definisikan info versi framework yang saat ini sedang digunakan.
*/

define('RAKIT_VERSION', '0.9.9');

/*
|--------------------------------------------------------------------------
| Konstanta Framework
|--------------------------------------------------------------------------
|
| Daftarkan konstanta tambahan. Konstanta ini dibuat agar aksesnya
| lebih mudah karena tersedia secara global.
|
*/

define('CRLF', "\r\n");
define('DEFAULT_PACKAGE', 'application');
define('RAKIT_KEY', require path('rakit_key'));

/*
|--------------------------------------------------------------------------
| Muat Kelas - Kelas Inti
|--------------------------------------------------------------------------
|
| Di sini kita memuat kelas-kelas yang digunakan di setiap request, atau
| yang digunakan oleh kelas konfigurasi.
| Lebih cepat dan lebih mudah untuk memuatnya secara manual daripada
| menggunakan autoloader.
|
*/

require path('system') . 'container.php';
require path('system') . 'event.php';
require path('system') . 'package.php';
require path('system') . 'config.php';
require path('system') . 'helpers.php';
require path('system') . 'autoloader.php';

/*
|--------------------------------------------------------------------------
| Dafarkan Autoloader Framework
|--------------------------------------------------------------------------
|
| Selanjutnya kita daftarkan kelas Autoloader ke SPL autoloader stack
| agar kelas bisa di-lazyload ketika kita membutuhkannya.
|
*/

spl_autoload_register(['\System\Autoloader', 'load']);

/*
|--------------------------------------------------------------------------
| Daftarkan Namespace 'System'
|--------------------------------------------------------------------------
|
| Daftarkan namespace 'System' dan direktori mappingnya agar bisa dimuat
| oleh autoloader menggunakan konvensi PSR-0.
|
*/

Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Buat Foundation Request
|--------------------------------------------------------------------------
|
| Rakit memisahkan implementasi detail penanganan http request ke
| folder 'foundation/http/' agar file 'system/request.php' tidak terlalu
| panjang dan tetap mudah dibaca. Nah, disini kita perlu memanggilnya.
|
*/

Request::$foundation = Foundation\Http\Request::createFromGlobals();

/*
|--------------------------------------------------------------------------
| Tentukan Environment Aplikasi
|--------------------------------------------------------------------------
|
| Selanjutnya, kita siap menentukan environment aplikasi. Ini dapat
| diatur melalui CLI atau melalui mapping URI ke environment yang
| ada di file "paths.php". Saat menentukan evironment via CLI,
| opsi CLI "--env=" akan otomatis menggantikan mapping di "paths.php".
|
*/

if (Request::cli()) {
    $environment = get_cli_option('env', getenv('RAKIT_ENV'));
    $environment = empty($environment) ? Request::detect_env($environments, gethostname()) : $environment;
} else {
    $environment = Request::detect_env($environments, Request::foundation()->getRootUrl());
}

/*
|--------------------------------------------------------------------------
| Set Environment Aplikasi
|--------------------------------------------------------------------------
|
| Setelah kita menentukan lingkungan aplikasi, kita akan mengaturnya pada
| array server global dari request foundation.
| Ini akan membuatnya tersedia di seluruh aplikasi, meskipun ini hanya
| digunakan untuk menentukan konfigurasi mana yang akan ditimpa.
|
*/

if (isset($environment) && !empty($environment)) {
    Request::set_env($environment);
}

/*
|--------------------------------------------------------------------------
| Set Array Option CLI
|--------------------------------------------------------------------------
|
| Jika request saat ini datang dari rakit console, kita parse argumen dan
| optionnya, lalu kita set ke variabel global $_SERVER agar bisa
| diakses dari mana saja.
|
*/

if (Request::cli()) {
    list($arguments, $options) = Console\Console::options($_SERVER['argv']);
    $_SERVER['CLI'] = array_change_key_case($options, CASE_UPPER);
}

/*
|--------------------------------------------------------------------------
| Daftarkan Seluruh Paket
|--------------------------------------------------------------------------
|
| Akhirnya kita akan mendaftarkan seluruh paket yang telah didefinisikan.
| Disini tidak akan dilakukan auto-boot, hanya akan di-set agar bisa
| dipanggil oleh si developer ketika ia membutuhkannya saja.
|
*/

$packages = require path('app') . 'packages.php';

foreach ($packages as $package => $config) {
    Package::register($package, $config);
}
