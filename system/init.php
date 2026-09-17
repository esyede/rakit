<?php

namespace System;

defined('DS') or exit('No direct access.');

/*
|--------------------------------------------------------------------------
| Auto-generate App Key
|--------------------------------------------------------------------------
| Ensure the key.php file exists in the base path, create it if it doesn't.
*/

$dir = __DIR__.DS.'foundation'.DS.'oops'.DS.'assets'.DS.'debugger';

if (is_file($path = path('rakit_key'))) {
    $ptrn = '/^(?:[a-f\d]{64}|[a-f\d]{8}(?:-[a-f\d]{4}){4}[a-f\d]{8})$/i';
    $error = null;

    if (! is_readable(dirname($path))) {
        $error = 'unreadable.phtml';
    } elseif (1 !== preg_match($ptrn, require $path)) {
        $error = 'invalid.phtml';
    }

    if ($error) {
        http_response_code(500);
        require $dir.DS.$error;

        if (function_exists('fastcgi_finish_request')) {
            /* @disregard */
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            /* @disregard */
            litespeed_finish_request();
        }

        exit(255);
    }
} else {
    $path = path('rakit_key');

    if (! is_writable(dirname((string) $path))) {
        http_response_code(500);
        require $dir.DS.'unwritable.phtml';

        if (function_exists('fastcgi_finish_request')) {
            /* @disregard */
            fastcgi_finish_request();
        } elseif (function_exists('litespeed_finish_request')) {
            /* @disregard */
            litespeed_finish_request();
        }

        exit(255);
    }

    if (isset($_SERVER['HTTP_COOKIE'])) {
        $cookies = explode(';', $_SERVER['HTTP_COOKIE']);

        foreach ($cookies as $cookie) {
            $parts = explode('=', $cookie);
            $name = trim($parts[0]);
            $ttl = time() - 2628000;

            if (PHP_VERSION_ID < 70300) {
                setcookie($name, '', $ttl, '/; samesite=Lax');
                setcookie($name, '', $ttl);
            } else {
                setcookie($name, '', [
                    'expires' => $ttl,
                    'path' => '/',
                    'samesite' => 'Lax',
                ]);
                setcookie($name, '', [
                    'expires' => $ttl,
                    'samesite' => 'Lax',
                ]);
            }
        }
    }

    $stub = __DIR__.DS.'console'.DS.'commands'.DS.'stubs'.DS.'system';
    file_put_contents(path('rakit_key'), str_replace(
        '00000000-0000-0000-0000-000000000000',
        bin2hex(openssl_random_pseudo_bytes(32)),
        file_get_contents($stub.DS.'key.stub')
    ));
}

if (
    ! is_file($file = dirname(__DIR__).DS.'_ide_helper.php')
    && is_writable(dirname($file))
) {
    @copy($stub.DS.'_ide_helper.stub', $file);
}
