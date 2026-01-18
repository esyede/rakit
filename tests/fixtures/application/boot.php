<?php

defined('DS') or exit('No direct access.');

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

System\Event::listen(System\Config::LOADER, function ($package, $file) {
    return System\Config::file($package, $file);
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

System\Autoloader::$aliases = System\Config::get('aliases');

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

System\Autoloader::directories([
    path('app') . 'controllers',
    path('app') . 'models',
    path('app') . 'libraries',
    path('app') . 'commands',
    path('app') . 'jobs',
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

System\Event::listen(System\View::LOADER, function ($package, $view) {
    return System\View::file($package, $view, System\Package::path($package) . 'views');
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

System\Event::listen(System\Lang::LOADER, function ($package, $language, $file) {
    return System\Lang::file($package, $language, $file);
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

System\Blade::sharpen();

/*
|--------------------------------------------------------------------------
| Set Default Timezone
|--------------------------------------------------------------------------
|
| Lalu setel default timezone sesuai konfigurasi yang anda berikan.
|
*/

date_default_timezone_set(System\Config::get('application.timezone', 'UTC'));

/*
|--------------------------------------------------------------------------
| Load Session
|--------------------------------------------------------------------------
|
| Kami juga perlu memuat session jika anda telah menyetel driver session.
|
*/

if (!System\Request::cli() && filled(System\Config::get('session.driver'))) {
    System\Session::load();
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

if (is_file($path = System\Config::get('application.composer_autoload'))) {
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
