<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Exceptions\DecryptException;

class Crypter
{
    /**
     * Derive a key for a specific purpose via HKDF-like construction.
     *
     * @param string $purpose
     * @return string Binary key
     */
    protected static function derive_key($purpose)
    {
        return hash_hmac('sha256', $purpose, RAKIT_KEY, true);
    }

    /**
     * Encrypt a string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function encrypt($value)
    {
        $iv = Str::bytes(16);
        $enc_key = static::derive_key('rakit-encryption-v1');
        $mac_key = static::derive_key('rakit-mac-v1');
        $value = openssl_encrypt((string) $value, 'aes-256-cbc', $enc_key, 0, $iv);

        if (false === $value) {
            throw new \Exception('Could not encrypt the data.');
        }

        $iv = base64_encode($iv);
        $mac = hash_hmac('sha256', $iv.$value, $mac_key);
        $v = 1;
        $value = json_encode(compact('iv', 'value', 'mac', 'v'), JSON_UNESCAPED_SLASHES);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception('Could not encrypt the data.');
        }

        return base64_encode($value);
    }

    /**
     * Decrypt a string.
     *
     * @param string $value
     *
     * @return string
     */
    public static function decrypt($value)
    {
        $value = static::payload($value);
        $is_v1 = isset($value['v']) && $value['v'] === 1;
        $enc_key = $is_v1 ? static::derive_key('rakit-encryption-v1') : RAKIT_KEY;
        $value = openssl_decrypt($value['value'], 'aes-256-cbc', $enc_key, 0, base64_decode($value['iv']));

        if (false === $value) {
            throw new DecryptException('Could not decrypt the data.');
        }

        return $value;
    }

    /**
     * Check equality of two strings.
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
     * Get the payload.
     *
     * @param string $value
     *
     * @return array
     */
    protected static function payload($value)
    {
        $value = json_decode(base64_decode($value), true);

        if (! static::valid($value)) {
            throw new DecryptException('The payload is invalid.');
        }

        $is_v1 = isset($value['v']) && $value['v'] === 1;
        $mac_key = $is_v1 ? static::derive_key('rakit-mac-v1') : RAKIT_KEY;
        $mac = hash_hmac('sha256', $value['iv'].$value['value'], $mac_key);

        if (! static::equals($mac, $value['mac'])) {
            throw new DecryptException('The MAC is invalid.');
        }

        return $value;
    }

    /**
     * Validate the payload.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected static function valid($value)
    {
        if (! is_array($value)) {
            return false;
        }

        $keys = ['iv', 'value', 'mac'];

        foreach ($keys as $key) {
            if (! isset($value[$key]) || ! is_string($value[$key])) {
                return false;
            }
        }

        if (isset($value['v']) && $value['v'] !== 1) {
            return false;
        }

        $value = base64_decode($value['iv'], true);
        return false !== $value && strlen($value) === 16;
    }
}
