<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Panggil Init Script
|--------------------------------------------------------------------------
| Panggil init script sebelum first boot.
*/

require __DIR__ . DS . 'init.php';

/*
|--------------------------------------------------------------------------
| Muat helpers dan autoloader awal untuk debugger
|--------------------------------------------------------------------------
|
| Muat helpers dan autoloader sebelum core untuk inisialisasi debugger early.
|
*/

require path('system') . 'helpers.php';
require_once path('system') . 'autoloader.php';
spl_autoload_register(['\System\Autoloader', 'load']);
\System\Autoloader::namespaces(['System' => path('system')]);

/*
|--------------------------------------------------------------------------
| Inisialisasi debugger awal
|--------------------------------------------------------------------------
|
| Enable debugger sebelum boot package untuk tangkap error early seperti
| koneksi Redis yang gagal saat session init.
|
*/

use System\Foundation\Oops\Debugger;

if (file_exists($debugger = path('app') . 'config' . DS . 'debugger.php')) {
    $debugger = require $debugger;
    Debugger::$productionMode = (false === (bool) $debugger['activate']);
    Debugger::enable(null, path('storage') . 'logs');
    unset($debugger);
}

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

require __DIR__ . DS . 'core.php';

/*
|--------------------------------------------------------------------------
| Load Config Debugger Base
|--------------------------------------------------------------------------
|
| Load base config debugger untuk set productionMode sebelum enable,
| agar error early tidak tampil HTML debugger.
|
*/

$debugger = require path('app') . 'config' . DS . 'debugger.php';
Debugger::$productionMode = (false === (bool) $debugger['activate']);
unset($debugger);

/*
|--------------------------------------------------------------------------
| Enable Debugger Lebih Awal
|--------------------------------------------------------------------------
|
| Enable debugger sebelum boot package untuk tangkap error early seperti
| koneksi Redis yang gagal saat session init.
|
*/

Debugger::enable(null, path('storage') . 'logs');
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
| application/config/debugger.php. Config sudah dimuat awal, tapi set ulang
| untuk memastikan konsistensi setelah package boot.
|
*/

$debugger = Config::get('debugger'); // Gunakan Config::get untuk konsistensi
$template = path('app') . 'views' . DS . 'error' . DS . '500.blade.php';

// ProductionMode sudah di-set awal, tapi pastikan sesuai config
Debugger::$productionMode = (false === (bool) $debugger['activate']);
Debugger::$strictMode = (bool) $debugger['strict'];
Debugger::$scream = (bool) $debugger['scream'];

Debugger::$logSeverity = 0;
Debugger::$errorTemplate = is_file($template) ? $template : null;
Debugger::$time = RAKIT_START;



Debugger::$showBar = (bool) $debugger['debugbar'];
Debugger::$showLocation = (bool) $debugger['location'];
Debugger::$maxDepth = (int) $debugger['depth'];
Debugger::$maxLength = (int) $debugger['length'];
Debugger::$email = (string) $debugger['email'];

unset($debugger, $template, $debugger);

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

$languages = Config::get('application.languages', ['en']);
$languages[] = Config::get('application.language', 'en');
$languages = array_filter($languages, function ($lang) {
    return is_string($lang) && preg_match('/^[a-zA-Z0-9_-]+$/', $lang);
});
usort($languages, function ($a, $b) {
    return strlen($b) - strlen($a);
});

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
$uri = (!is_string($uri) || empty($uri)) ? '/' : $uri;

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

try {
    Request::$route = Routing\Router::route(Request::method(), $uri);
    $response = Request::$route->call();
} catch (\Throwable $e) {
    Log::error('Routing error: ' . $e->getMessage(), [
        'exception' => $e,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    $response = Response::error(500, []);
} catch (\Exception $e) {
    Log::error('Routing error: ' . $e->getMessage(), [
        'exception' => $e,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    $response = Response::error(500, []);
}

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
