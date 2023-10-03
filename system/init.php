<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Buat Application Key
|--------------------------------------------------------------------------
| Pastikan file key.php sudah ada di base path, buat jika belum ada.
*/

$dir = __DIR__ . 'foundation' . DS . 'oops' . DS . 'assets' . DS . 'debugger' . DS . 'key';

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

        file_put_contents(path('rakit_key'), str_replace(
            '00000000-0000-0000-0000-000000000000',
            vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(openssl_random_pseudo_bytes(16)), 4)),
            file_get_contents(__DIR__ . DS . 'console' . DS . 'commands' . DS . 'stubs' . DS . 'system' . DS . 'key.stub')
        ));
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

/*
|--------------------------------------------------------------------------
| Salin _ide_helper.php
|--------------------------------------------------------------------------
| Utuk mengakomodasi autocomplete IDE anda agar lebih akurat.
*/

if (!is_file($file = dirname(__DIR__) . DS . '_ide_helper.php')) {
    $stub = __DIR__ . DS . 'console' . DS . 'commands' . DS . 'stubs' . DS . 'system' . DS . '_ide_helper.stub';
    copy($stub, $file);
}
