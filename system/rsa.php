<?php

namespace System;

defined('DS') or exit('No direct script access.');

class RSA
{
    /**
     * Enkripsi string menggunakan RSA key.
     *
     * @param string $data
     *
     * @return string
     */
    public static function encrypt($data)
    {
        static::generate_key();

        $data = gzcompress($data);
        $public_key = file_get_contents(path('storage').'rsa-public.pem');
        $public_key = openssl_pkey_get_public($public_key);
        $key = openssl_pkey_get_details($public_key);
        $length = ceil($key['bits'] / 8) - 11;
        $result = '';

        while ($data) {
            $chunk = substr($data, 0, $length);
            $data = substr($data, $length);
            $temp = '';

            if (! openssl_public_encrypt($chunk, $temp, $public_key)) {
                throw new \Exception('Failed to encrypt data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($public_key);
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
        static::generate_key();
        $private_key = path('storage').'rsa-private.pem';

        if (! $private_key = openssl_pkey_get_private(file_get_contents($private_key))) {
            throw new \Exception('Failed to obtain private key');
        }

        $key = openssl_pkey_get_details($private_key);
        $length = ceil($key['bits'] / 8);
        $result = '';

        while ($encrypted) {
            $chunk = substr($encrypted, 0, $length);
            $encrypted = substr($encrypted, $length);
            $temp = '';

            if (! openssl_private_decrypt($chunk, $temp, $private_key)) {
                throw new \Exception('Failed to decrypt data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($private_key);
        }

        return gzuncompress($result);
    }

    /**
     * Buat private dan public key (disimpan ke folder storage).
     *
     * @return void
     */
    private static function generate_key()
    {
        $storage = path('storage');
        $randfile = $storage.'.rnd';
        $config = $storage.'openssl.conf';
        $conf = 'HOME='.$storage.PHP_EOL.'RANDFILE='.$randfile.PHP_EOL.'[v3_ca]';

        $private_key = null;
        $public_key = null;

        if (is_file($config)) {
            unlink($config);
        }

        file_put_contents($config, $conf);

        if (! is_file($path = $storage.'rsa-private.pem')) {
            $private_key = openssl_pkey_new([
                'private_key_bits' => 2048,
                'private_key_type' => OPENSSL_KEYTYPE_RSA,
                'config' => $config,
            ]);

            openssl_pkey_export_to_file($private_key, $path, null, compact('config'));
        }

        if ($private_key && ! is_file($path = $storage.'rsa-public.pem')) {
            $public_key = openssl_pkey_get_details($private_key);
            file_put_contents($path, $public_key['key']);
        }

        if ((! is_null($private_key) || ! is_null($public_key)) && PHP_VERSION_ID < 80000) {
            openssl_free_key($private_key);
        }

        if (is_file($config)) {
            unlink($config);
        }

        if (is_file($randfile)) {
            unlink($randfile);
        }
    }
}
