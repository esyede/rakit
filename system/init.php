<?php

namespace System;

defined('DS') or exit('No direct script access.');

/*
|--------------------------------------------------------------------------
| Buat / Baca Rakit Key
|--------------------------------------------------------------------------
| Pastikan file key.php sudah ada di base path, buat jika belum ada.
*/

$dir = path('system') . 'foundation' . DS . 'oops' . DS . 'assets' . DS . 'debugger' . DS . 'key';

if (is_file($path = path('rakit_key'))) {
    $error = null;

    if (!is_readable(dirname($path))) {
        $error = 'unreadable.phtml';
    } elseif (1 !== preg_match('/^[a-f\d]{8}(-[a-f\d]{4}){4}[a-f\d]{8}$/i', require $path)) {
        $error = 'invalid.phtml';
    }

    if ($error) {
        http_response_code(500);
        require $dir . DS . $error;

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit;
    }
} else {
    $path = path('rakit_key');

    if (!is_writable(dirname((string) $path))) {
        http_response_code(500);
        require $dir . DS . 'unwritable.phtml';

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

        $key = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(openssl_random_pseudo_bytes(16)), 4));
        file_put_contents(
            path('rakit_key'),
            '<?php' . PHP_EOL
                . PHP_EOL
                . "defined('DS') or exit('No direct script access.');" . PHP_EOL
                . PHP_EOL
                . '/*' . PHP_EOL
                . '|--------------------------------------------------------------------------' . PHP_EOL
                . '| Application Key' . PHP_EOL
                . '|--------------------------------------------------------------------------' . PHP_EOL
                . '|' . PHP_EOL
                . '| File ini (key.php) dibuat otomatis oleh rakit. Salin file ini ke tempat' . PHP_EOL
                . '| yang aman karena file ini berisi kunci untuk membuka aplikasi anda.' . PHP_EOL
                . '|' . PHP_EOL
                . '*/' . PHP_EOL
                . PHP_EOL
                . sprintf("return '%s';", $key) . PHP_EOL
        );
    } catch (\Throwable $e) {
        http_response_code(500);
        require $dir . DS . 'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit;
    } catch (\Exception $e) {
        http_response_code(500);
        require $dir . DS . 'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }

        exit;
    }
}
