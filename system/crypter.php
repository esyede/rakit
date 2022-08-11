<?php

namespace System;

defined('DS') or die('No direct script access.');

class Crypter
{
    /**
     * Berisi encryption key.
     *
     * @var string
     */
    protected static $key;

    /**
     * Berisi cipher method.
     *
     * @var string
     */
    protected static $method;

    /**
     * Enkrispsi string.
     *
     * @param string $data
     *
     * @return string
     */
    public static function encrypt($data)
    {
        $method = static::method();
        $key = static::key();
        $iv = Str::bytes(16);

        $hash = openssl_encrypt($data, $method, $key, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $hash, $key, true);

        if (false === $hash) {
            throw new \Exception('Unable to encrypt the data.');
        }

        return base64_encode($iv.$hmac.$hash);
    }

    /**
     * Dekripsi string.
     *
     * @param string $hash
     *
     * @return string
     */
    public static function decrypt($hash)
    {
        $method = static::method();
        $key = static::key();
        $hash = base64_decode($hash);

        $iv = mb_substr($hash, 0, 16, '8bit');
        $hmac = mb_substr($hash, 16, 32, '8bit');
        $cipher = mb_substr($hash, 48, null, '8bit');
        $hmac2 = hash_hmac('sha256', $cipher, $key, true);

        if (! static::equals($hmac, $hmac2)) {
            throw new \Exception('Hash verification failed.');
        }

        $data = openssl_decrypt($cipher, $method, $key, OPENSSL_RAW_DATA, $iv);

        if (false === $data) {
            throw new \Exception('Unable to decrypt the data.');
        }

        return $data;
    }

    /**
     * Cek kesamaan antara 2 hash.
     *
     * @param string $known
     * @param string $compared
     *
     * @return bool
     */
    public static function equals($known, $compared)
    {
        if (! is_string($known) || ! is_string($compared)) {
            return false;
        }

        $length1 = mb_strlen($known, '8bit');
        $length2 = mb_strlen($compared, '8bit');

        if ($length1 !== $length2) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $length1; ++$i) {
            $result |= ord($known[$i]) ^ ord($compared[$i]);
        }

        return 0 === $result;
    }

    /**
     * Ambil encryption key (app key).
     *
     * @return string
     */
    protected static function key()
    {
        if (static::$key && mb_strlen((string) static::$key, '8bit') >= 32) {
            return static::$key;
        }

        static::$key = (string) Config::get('application.key', '');

        if (mb_strlen(trim(static::$key), '8bit') < 32) {
            $message = 'Generate your app key with rakit console '.
                'or obtain it from here: https://rakit.esyede.my.id/key';

            if (Request::cli()) {
                throw new \Exception($message);
            } elseif (Request::wants_json()) {
                Response::json([
                    'status' => 500,
                    'success' => false,
                    'message' => $message,
                ]);
            } else {
                http_response_code(500);
                require path('system').'foundation'.DS.'oops'.DS.'assets'.DS.'debugger'.DS.'key.phtml';

                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }

                exit;
            }
        }

        return static::$key;
    }

    /**
     * Ambil method untuk cipher.
     * OpenSSL 1.1.1+ mereturn nama method dalam bentuk lowercase.
     *
     * Lihat: https://php.net/manual/en/function.openssl-get-cipher-methods.php#123319
     * Lihat: https://github.com/oerdnj/deb.sury.org/issues/990
     *
     * @return string
     */
    protected static function method()
    {
        if (static::$method) {
            return static::$method;
        }

        $methods = openssl_get_cipher_methods();
        $methods = is_array($methods) ? $methods : [$methods];

        if (in_array('AES-128-CBC', $methods)) {
            static::$method = 'AES-128-CBC';
        } elseif (in_array('aes-128-cbc', $methods)) {
            static::$method = 'aes-128-cbc';
        } else {
            throw new \Exception('Required AES cipher method is not present on your system.');
        }

        return static::$method;
    }
}
