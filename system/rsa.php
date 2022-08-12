<?php

namespace System;

defined('DS') or exit('No direct script access.');

class RSA
{
    /**
     * Detail data.
     *
     * @var array
     */
    private static $details = [
        'public_key' => null,
        'private_key' => null,
        'config' => null,
        'options' => [],
    ];

    /**
     * Enkripsi string menggunakan RSA key.
     *
     * @param string $data
     *
     * @return string
     */
    public static function encrypt($data)
    {
        static::generate();
        $data = gzcompress($data);
        $pub = openssl_pkey_get_public(static::$details['public_key']);
        $length = ceil(openssl_pkey_get_details($pub)['bits'] / 8) - 11;
        $result = '';

        while ($data) {
            $chunk = substr($data, 0, $length);
            $data = substr($data, $length);
            $temp = '';

            if (! openssl_public_encrypt($chunk, $temp, $pub)) {
                throw new \Exception('Failed to encrypt data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($pub);
        }

        return $result;
    }

    /**
     * Dekripsi data menggunakan RSA key.
     *
     * @param string $encrypted
     *
     * @return string
     */
    public static function decrypt($encrypted)
    {
        static::generate();

        if (! $priv = openssl_pkey_get_private(static::$details['private_key'])) {
            throw new \Exception(sprintf('Failed to obtain private key: %s (%s)', $priv, gettype($priv)));
        }

        $key = openssl_pkey_get_details($priv);
        $length = ceil($key['bits'] / 8);
        $result = '';

        while ($encrypted) {
            $chunk = substr($encrypted, 0, $length);
            $encrypted = substr($encrypted, $length);
            $temp = '';

            if (! openssl_private_decrypt($chunk, $temp, $priv)) {
                throw new \Exception('Failed to decrypt data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($priv);
        }

        return gzuncompress($result);
    }

    /**
     * Generate private dan public key.
     *
     * @return void
     */
    private static function generate()
    {
        if (! static::$details['private_key'] || ! static::$details['public_key']) {
            $config = path('storage').'openssl.conf';
            $randfile = path('storage').'.rnd';

            static::$details['config'] = sprintf(
                "HOME=%s\nRANDFILE=%s\n[req]\ndefault_bits=%s\n[v3_ca]",
                path('storage'), $randfile, 2048
            );

            static::$details['options'] = [
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'config' => $config,
            ];

            if (is_file($config)) {
                unlink($config);
            }

            file_put_contents($config, static::$details['config'], LOCK_EX);

            if (! static::$details['private_key']) {
                $priv = openssl_pkey_new(static::$details['options']);
                openssl_pkey_export($priv, static::$details['private_key'], null, compact('config'));
            }

            if (! static::$details['public_key']) {
                static::$details['public_key'] = openssl_pkey_get_details($priv)['key'];
            }

            if ((static::$details['private_key'] || static::$details['public_key']) && PHP_VERSION_ID < 80000) {
                openssl_free_key($priv);
            }

            if (is_file($config)) {
                unlink($config);
            }

            if (is_file($randfile)) {
                unlink($randfile);
            }
        }
    }

    /**
     * Ambil detail data.
     *
     * @return array
     */
    public static function details()
    {
        return static::$details;
    }
}
