<?php

namespace System;

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Buat / Baca Rakit Key
|--------------------------------------------------------------------------
| Pastikan file key.php sudah ada di base path, buat jika belum ada.
*/

if (is_file($path = path('rakit_key'))) {
    $dir = path('system').'foundation'.DS.'oops'.DS.'assets'.DS.'debugger'.DS.'key'.DS;

    if (! is_readable(dirname((string) $path))) {
        http_response_code(500);
        require $dir.'unreadable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }

    if (1 !== preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', require $path)) {
        http_response_code(500);
        require $dir.'invalid.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
} else {
    $path = path('rakit_key');

    if (! is_writable(dirname((string) $path))) {
        http_response_code(500);
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }

    try {
        if (isset($_SERVER['HTTP_COOKIE'])) {
            $cookies = explode(';', $_SERVER['HTTP_COOKIE']);

            foreach ($cookies as $cookie) {
                $parts = explode('=', $cookie);
                setcookie(trim($parts[0]), '', time() - 2628000);
                setcookie(trim($parts[0]), '', time() - 2628000, '/');
            }
        }

        $key = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000, mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
        file_put_contents(
            path('rakit_key'),
            '<?php'.PHP_EOL.PHP_EOL
            .'defined(\'DS\') or exit(\'No direct script access.\');'.PHP_EOL.PHP_EOL
            .'/*'.PHP_EOL
            .'|--------------------------------------------------------------------------'.PHP_EOL
            .'| Application Key'.PHP_EOL
            .'|--------------------------------------------------------------------------'.PHP_EOL
            .'|'.PHP_EOL
            .'| File ini (key.php) dibuat otomatis oleh rakit. Salin file ini ke tempat'.PHP_EOL
            .'| yang aman karena file ini adalah kunci untuk membuka aplikasi anda.'.PHP_EOL
            .'|'.PHP_EOL
            .'| Jika terjadi error "Hash verification failed", silahkan muat ulang halaman.'.PHP_EOL
            .'|'.PHP_EOL
            .'*/'.PHP_EOL
            .PHP_EOL
            .sprintf('return \'%s\';', $key).PHP_EOL
        );
    } catch (\Throwable $e) {
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        require $dir.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
        exit;
    }
}
