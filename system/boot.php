<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Panggil Init Script
|--------------------------------------------------------------------------
| Panggil init script sebelum first boot.
*/

require 'init.php';

/*
|--------------------------------------------------------------------------
| Jalankan Core Boot
|--------------------------------------------------------------------------
|
| Dengan meng-include file ini, core boot milik framework akan dijalankan,
| yang mana didalamnya mengandung autoloader dan registrasi paket.
| Pada dasarnya, setelah file ini diinclude, rakit framework
| sudah dapat digunakan oleh si developer.
|
*/

require 'core.php';

/*
|--------------------------------------------------------------------------
| Mulai Paket 'application'
|--------------------------------------------------------------------------
|
| Paket 'application' ini adalah paket default di rakit framework.
| Ya, folder application/ itu adalah sebuah paket, tepatnya paket default.
| Kita perlu menjalankannya pertama kali, karena paket ini akan memuat
| seluruh konfigurasi inti framework.
|
*/

Package::boot(DEFAULT_PACKAGE);

/*
|--------------------------------------------------------------------------
| Konfigurasi Debugger
|--------------------------------------------------------------------------
|
| Atur konfigurasi debugger sesuai data yang diberikan user di file
| application/config/debugger.php.
|
*/

use System\Foundation\Oops\Debugger;

Debugger::enable(false, path('storage') . 'logs');

$debugger = Config::get('debugger');
$template = path('app') . 'views' . DS . 'error' . DS . '500.blade.php';

Debugger::$productionMode = (false === (bool) $debugger['activate']);
Debugger::$strictMode = (bool) $debugger['strict'];
Debugger::$scream = (bool) $debugger['scream'];

Debugger::$showFirelog = false;
Debugger::$logSeverity = 0;
Debugger::$errorTemplate = is_file($template) ? $template : null;
Debugger::$time = RAKIT_START;
Debugger::$editor = false;
Debugger::$editorMapping = [];
Debugger::$customCssFiles = [];
Debugger::$customJsFiles = [];

Debugger::$showBar = (bool) $debugger['debugbar'];
Debugger::$showLocation = (bool) $debugger['location'];
Debugger::$maxDepth = (int) $debugger['depth'];
Debugger::$maxLength = (int) $debugger['length'];
Debugger::$email = (string) $debugger['email'];

unset($debugger, $template);

/*
|--------------------------------------------------------------------------
| Jalankan Paket Lain
|--------------------------------------------------------------------------
|
| Kita tahu, paket yang digunakan di aplikasi anda bisa di-autoboot
| sehingga dia bisa langsung digunakan tanpa harus di-boot secara manual.
| Nah, disini kita melakukannya.
|
*/

foreach (Package::$packages as $package => $config) {
    if (isset($config['autoboot']) && $config['autoboot']) {
        Package::boot($package);
    }
}

/*
|--------------------------------------------------------------------------
| Daftarkan Catch-All Route
|--------------------------------------------------------------------------
|
| Catch-all route ini menangani seluruh rute yang tidak dapat ditemukan di
| aplikasi Anda, dan akan menjalankan event 404 sehingga si developer
| bisa dengan mudah mengubah cara penanganannya sesuai kebutuhan.
|
*/

Routing\Router::register('*', '(:all)', function () {
    return Event::first('404');
});

/*
|--------------------------------------------------------------------------
| Baca URI Dan Locale
|--------------------------------------------------------------------------
|
| Ketika request diarahkan, kita perlu mengambil URI dan locale (bahasa)
| yang didukung oleh rute tujuan agar kita bisa mengarahkan requestnya ke
| lokasi yang tepat serta men-set bahasa yang sesuai dengan yang diminta.
|
*/

$languages = Config::get('application.languages', []);
$languages[] = Config::get('application.language');

/*
|--------------------------------------------------------------------------
| Set Locale Berdasarkan Rute
|--------------------------------------------------------------------------
|
| Jika URI diawali dengan salah satu 'locale' yang didukung, kita akan set
| bahasa default sesuai segmen URI tersebut, lalu kita set URI-nya
| dan kita beri tahu Router agar tidak menyertakan segmen 'locale'-nya.
|
*/

$uri = URI::current();

foreach ($languages as $language) {
    if (preg_match('#^' . $language . '(?:$|/)#i', $uri)) {
        Config::set('application.language', $language);
        $uri = trim(substr((string) $uri, strlen($language)), '/');
        break;
    }
}

URI::$uri = ('' === $uri) ? '/' : $uri;

/*
|--------------------------------------------------------------------------
| Arahkan Request Yang Datang
|--------------------------------------------------------------------------
|
| Fiuh! Akhirnya kita bisa mengarahkan request ke lokasi yang tepat dan
| mengeksekusinya untuk mendapatkan respon. Si respon ini berupa instance
| dari kelas \System\Response yang bisa kita kirim ke browser.
|
*/

Request::$route = Routing\Router::route(Request::method(), $uri);
$response = Request::$route->call();

/*
|--------------------------------------------------------------------------
| Render Responnya
|--------------------------------------------------------------------------
|
| Method render() ini mengevaluasi konten respon dan mengubahnya ke string.
|
*/

$response->render();

/*
|--------------------------------------------------------------------------
| Pertahankan Session
|--------------------------------------------------------------------------
|
| Jika sudah ada session yang aktif, kita akan simpan sessionnya
| agar tetap bisa dipakai di request berikutnya. Ini juga akan men-set
| session cookie di cookie jar untuk dikirim ke user.
|
*/

if (Config::get('session.driver')) {
    Session::save();
}

/*
|--------------------------------------------------------------------------
| Kirim Responnya Ke Browser
|--------------------------------------------------------------------------
|
| Disini kita akan mengirim responnya ke browser. Method ini akan
| mengirim seluruh headers dan konten respon Anda ke browser.
|
*/

$response->send();

/*
|--------------------------------------------------------------------------
| Oke, Selesai!
|--------------------------------------------------------------------------
|
| Jalankan event 'done' agar output lainnya bisa ditambahkan ke respon.
| Output lain yang dimaksud misalnya Anda melakukan semacam logging.
|
*/

Event::fire('rakit.done', [$response]);

/*
|--------------------------------------------------------------------------
| Selesaikan Request PHP-FastCGI
|--------------------------------------------------------------------------
|
| Hentikan proses PHP untuk server pengguna FastCGI agar ekseskusi script
| bisa (agak) lebih cepat.
*/

$response->foundation()->finish();
