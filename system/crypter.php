<?php

namespace System;

defined('DS') or die('No direct script access.');

class Crypter
{
    /**
     * Enkrispsi string.
     *
     * @param string $data
     *
     * @return string
     */
    public static function encrypt($data)
    {
        $iv = Str::bytes(16);
        $hash = openssl_encrypt((string) $data, static::method(), RAKIT_KEY, OPENSSL_RAW_DATA, $iv);
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
        $hash = base64_decode($hash);
        $iv = mb_substr((string) $hash, 0, 16, '8bit');
        $hmac = mb_substr((string) $hash, 16, 32, '8bit');
        $cipher = mb_substr((string) $hash, 48, null, '8bit');
        $hmac2 = hash_hmac('sha256', $cipher, RAKIT_KEY, true);

        if (! static::equals($hmac, $hmac2)) {
            throw new \Exception('Hash verification failed.');
        }

        $data = openssl_decrypt($cipher, static::method(), RAKIT_KEY, OPENSSL_RAW_DATA, $iv);

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

        $length1 = mb_strlen((string) $known, '8bit');
        $length2 = mb_strlen((string) $compared, '8bit');

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
        $methods = openssl_get_cipher_methods();
        $methods = is_array($methods) ? $methods : [$methods];

        if (in_array('AES-256-CBC', $methods)) {
            return 'AES-256-CBC';
        } elseif (in_array('aes-256-cbc', $methods)) {
            return 'aes-256-cbc';
        }

        throw new \Exception('Required cipher method is not present on your system: aes-256-cbc');
    }
}
