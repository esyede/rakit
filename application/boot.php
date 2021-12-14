<?php

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Config Loader
|--------------------------------------------------------------------------
|
| Config loader bertanggung jawab untuk mereturn array konfigurasi untuk
| paket dan file tertentu. Secara default, kami menggunakan file yang
| telah disediakan rakit; namun, anda bebas untuk menggunakan mekanisme
| penyimpanan anda sendiri untuk menangani array konfigurasi.
|
*/

\System\Event::listen(\System\Config::LOADER, function ($package, $file) {
    return \System\Config::file($package, $file);
});

/*
|--------------------------------------------------------------------------
| Class Alias
|--------------------------------------------------------------------------
|
| Class alias memungkinkan anda menggunakan kelas tanpa perlu mengimpornya.
| Di sini kami hanya mendaftarkan alias untuk kelas bawaan rakit saja,
| dan tentunya anda juga dapat menambahkan alias lain sesuai kebutuhan.
|
*/

\System\Autoloader::$aliases = \System\Config::get('aliases');

/*
|--------------------------------------------------------------------------
| Autoload Mapping
|--------------------------------------------------------------------------
|
| Untuk mendaftarkan class map, cukup oper array ke Autoloader::map()
| seperti ini. Disini kita mendaftarkan kelas Base_controller via mapping
| karena kelas tersebut belum mengikuti konvensi psr.
|
*/

Autoloader::map([
    'Base_Controller' => path('app').'controllers'.DS.'base.php',
    // Tambahkan mapping lain disini..
]);

/*
|--------------------------------------------------------------------------
| Autoload Direktori
|--------------------------------------------------------------------------
|
| Autoloader rakit juga dapat meng-autoload direktori via konvensi psr.
| Konvensi ini pada dasarnya mengelola kelas dengan menggunakan namespace
| sebagai penunjuk struktur direktori dan lokasi kelas.
|
*/

Autoloader::directories([
    path('app').'models',
    path('app').'libraries',
    // Tambahkan direktori lain disini..
]);

/*
|--------------------------------------------------------------------------
| View Loader
|--------------------------------------------------------------------------
|
| View loader bertanggung jawab mereturn path file paket dan view.
| Tentu saja, implementasi default telah disediakan sesuai konvensi rakit.
|
*/

Event::listen(View::LOADER, function ($package, $view) {
    return View::file($package, $view, Package::path($package).'views');
});

/*
|--------------------------------------------------------------------------
| Language Loader
|--------------------------------------------------------------------------
|
| Language loader bertanggung jawab mereturn array baris bahasa.
|Tentu saja, implementasi default telah disediakan sesuai konvensi rakit.
|
*/

Event::listen(Lang::LOADER, function ($package, $language, $file) {
    return Lang::file($package, $language, $file);
});

/*
|--------------------------------------------------------------------------
| Aktifkan Blade Engine
|--------------------------------------------------------------------------
|
| Kami perlu mengktifkn blade engine disini agar langsung bisa digunakan
| dari dalam kontroler anda.
|
*/

Blade::sharpen();

/*
|--------------------------------------------------------------------------
| Set Default Timezone
|--------------------------------------------------------------------------
|
| Lalu setel default timezone sesuai konfigurasi yang anda berikan.
|
*/

date_default_timezone_set(Config::get('application.timezone', 'UTC'));

/*
|--------------------------------------------------------------------------
| Load Session
|--------------------------------------------------------------------------
|
| Kami juga perlu memuat session jika anda telah menyetel driver session.
|
*/

if (! Request::cli() && '' !== Config::get('session.driver')) {
    Session::load();
}

/*
|--------------------------------------------------------------------------
| Autoload Composer
|--------------------------------------------------------------------------
|
| Dan terakhir, kita muat autoloader milik Composer agar seluruh kelas
| didalam folder vendornya dapat dikenali oleh rakit.
|
*/

if (is_file($path = Config::get('application.composer_autoload'))) {
    require_once $path;
    unset($path);
}

/*
|--------------------------------------------------------------------------
| Lain - Lain
|--------------------------------------------------------------------------
|
| Tambahkan logic lain yang harus segera berjalan setelah
| booting aplikasi selesai, seperti ekstensi untuk custom driver dll.
|
*/

// ..
