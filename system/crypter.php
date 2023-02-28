<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Crypter
{
    /**
     * Enkripsi string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function encrypt($value)
    {
        $iv = Str::bytes(16);
        $value = openssl_encrypt($value, 'aes-256-cbc', RAKIT_KEY, 0, $iv);

        if (false === $value) {
            throw new \Exception('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $mac = hash_hmac('sha256', $iv . $value, RAKIT_KEY);
        $value = json_encode(compact('iv', 'value', 'mac'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Could not encrypt the data.');
        }

        return base64_encode($value);
    }

    /**
     * Dekripsi string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function decrypt($value)
    {
        $value = static::payload($value);
        $value = openssl_decrypt($value['value'], 'aes-256-cbc', RAKIT_KEY, 0, base64_decode($value['iv']));

        if (false === $value) {
            throw new \Exception('Could not decrypt the data.');
        }

        return $value;
    }

    /**
     * Cek kesamaan antara 2 string.
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

    /**
     * Ambil data payload.
     *
     * @param string $value
     *
     * @return array
     */
    protected static function payload($value)
    {
        $value = json_decode(base64_decode($value), true);

        if (!static::valid($value)) {
            throw new \Exception('The payload is invalid.');
        }

        $mac = hash_hmac('sha256', $value['iv'] . $value['value'], RAKIT_KEY);

        if (!static::equals($mac, $value['mac'])) {
            throw new \Exception('The MAC is invalid.');
        }

        return $value;
    }

    /**
     * Validasi data payload.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected static function valid($value)
    {
        if (!is_array($value)) {
            return false;
        }

        $keys = ['iv', 'value', 'mac'];

        foreach ($keys as $key) {
            if (!isset($value[$key]) || !is_string($value[$key])) {
                return false;
            }
        }

        return strlen(base64_decode($value['iv'], true)) === 16;
    }
}
