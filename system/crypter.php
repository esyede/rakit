<?php

namespace System;

defined('DS') or die('No direct script access.');

class Crypter
{
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
        $iv = Str::bytes(16);

        $hash = openssl_encrypt($data, $method, RAKIT_KEY, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $hash, RAKIT_KEY, true);

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
        $hash = base64_decode($hash);

        $iv = mb_substr($hash, 0, 16, '8bit');
        $hmac = mb_substr($hash, 16, 32, '8bit');
        $cipher = mb_substr($hash, 48, null, '8bit');
        $hmac2 = hash_hmac('sha256', $cipher, RAKIT_KEY, true);

        if (! static::equals($hmac, $hmac2)) {
            throw new \Exception('Hash verification failed.');
        }

        $data = openssl_decrypt($cipher, $method, RAKIT_KEY, OPENSSL_RAW_DATA, $iv);

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
