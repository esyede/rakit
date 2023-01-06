<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Crypter
{
    /**
     * Enkrispsi string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function encrypt($value)
    {
        $iv = Str::bytes(16);
        $value = openssl_encrypt($value, 'aes-256-cbc', RAKIT_KEY, 0, $iv, $tag);

        if (false === $value) {
            throw new \Exception('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $tag = base64_encode($tag ? $tag : '');
        $mac = hash_hmac('sha256', $iv . $value, RAKIT_KEY);
        $value = json_encode(compact('iv', 'value', 'mac', 'tag'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Could not encrypt the data.');
        }

        return base64_encode($value);
    }

    /**
     * Dekripsi string.
     *
     * @param string $data
     *
     * @return string
     */
    public static function decrypt($data)
    {
        $data = static::payload($data);
        $iv = base64_decode($data['iv']);
        $tag = empty($data['tag']) ? null : base64_decode($data['tag']);
        $tag = $tag ? $tag : ''; // Karena base64_decode() bisa mereturn string kosong
        $data = openssl_decrypt($data['value'], 'aes-256-cbc', RAKIT_KEY, 0, $iv, $tag);

        if ($data === false) {
            throw new \Exception('Could not decrypt the data.');
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

    /**
     * Ambil data payload.
     *
     * @param string $data
     *
     * @return array
     */
    protected static function payload($data)
    {
        $data = json_decode(base64_decode($data), true);

        if (!static::valid($data)) {
            throw new \Exception('The payload is invalid.');
        }

        $mac = hash_hmac('sha256', $data['iv'] . $data['value'], RAKIT_KEY);

        if (!static::equals($mac, $data['mac'])) {
            throw new \Exception('The MAC is invalid.');
        }

        return $data;
    }

    /**
     * Validasi data payload.
     *
     * @param mixed $data
     *
     * @return bool
     */
    protected static function valid($data)
    {
        if (!is_array($data)) {
            return false;
        }

        $items = ['iv', 'value', 'mac'];

        foreach ($items as $item) {
            if (!isset($data[$item]) || !is_string($data[$item])) {
                return false;
            }
        }

        if (isset($data['tag']) && !is_string($data['tag'])) {
            return false;
        }

        return strlen(base64_decode($data['iv'], true)) === 16;
    }
}
