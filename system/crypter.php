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
        $hash = openssl_encrypt($data, 'aes-256-cbc', RAKIT_KEY, OPENSSL_RAW_DATA, $iv);
        $hmac = hash_hmac('sha256', $hash, RAKIT_KEY, true);

        if (false === $hash) {
            throw new \Exception('Unable to encrypt the data.');
        }

        return base64_encode($iv . $hmac . $hash);
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

        // NOTE: Harus menggunakan substr().
        // Hash mismatch jika menggunakan mb_substr() di php 5.4.0.
        $iv = substr($hash, 0, 16);
        $hmac = substr($hash, 16, 32);
        $cipher = substr($hash, 48);

        $hmac2 = hash_hmac('sha256', $cipher, RAKIT_KEY, true);

        if (!static::equals($hmac, $hmac2)) {
            throw new \Exception('Hash verification failed.');
        }

        $data = openssl_decrypt($cipher, 'aes-256-cbc', RAKIT_KEY, OPENSSL_RAW_DATA, $iv);

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
        if (!is_string($known) || !is_string($compared)) {
            return false;
        }

        $length1 = strlen($known);
        $length2 = strlen($compared);

        if ($length1 !== $length2) {
            return false;
        }

        $result = 0;

        for ($i = 0; $i < $length1; ++$i) {
            $result |= ord($known[$i]) ^ ord($compared[$i]);
        }

        return 0 === $result;
    }
}
