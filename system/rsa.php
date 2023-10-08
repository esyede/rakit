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
        $pubkey = openssl_pkey_get_public(static::$details['public_key']);
        $length = (int) ceil(openssl_pkey_get_details($pubkey)['bits'] / 8) - 11;
        $data = (string) $data;
        $result = '';

        while ($data) {
            $chunk = mb_substr($data, 0, $length, '8bit');
            $data = mb_substr($data, $length, null, '8bit');
            $temp = '';

            if (!openssl_public_encrypt($chunk, $temp, $pubkey)) {
                throw new \Exception('Failed to encrypt the data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($pubkey);
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

        if (!($privkey = openssl_pkey_get_private(static::$details['private_key']))) {
            throw new \Exception(sprintf('Failed to obtain private key: %s (%s)', $privkey, gettype($privkey)));
        }

        $key = openssl_pkey_get_details($privkey);
        $length = ceil($key['bits'] / 8);
        $result = '';

        while ($encrypted) {
            $encrypted = (string) $encrypted;
            $chunk = mb_substr($encrypted, 0, $length, '8bit');
            $encrypted = mb_substr($encrypted, $length, null, '8bit');
            $temp = '';

            if (!openssl_private_decrypt($chunk, $temp, $privkey)) {
                throw new \Exception('Failed to decrypt the data');
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            openssl_free_key($privkey);
        }

        return $result;
    }

    /**
     * Generate private dan public key.
     *
     * @return void
     */
    private static function generate()
    {
        if (!static::$details['private_key'] || !static::$details['public_key']) {
            $config = path('storage') . 'openssl.conf';
            $randfile = path('storage') . '.rnd';

            static::$details['config'] = sprintf(
                "HOME=%s\nRANDFILE=%s\n[req]\ndefault_bits=%s\n[v3_ca]\n",
                path('storage'),
                $randfile,
                2048
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

            if (!static::$details['private_key']) {
                $privkey = openssl_pkey_new(static::$details['options']);

                if (!openssl_pkey_export($privkey, static::$details['private_key'], null, compact('config'))) {
                    $errors = null;

                    while (false !== ($message = openssl_error_string())) {
                        $errors .= $message . PHP_EOL;
                    }

                    throw new \Exception(sprintf('Failed to export private key: %s', $errors));
                }
            }

            if (!static::$details['public_key']) {
                $details = openssl_pkey_get_details($privkey);

                if (!isset($details['key'])) {
                    throw new \Exception('Failed to extract public key');
                }

                static::$details['public_key'] = $details['key'];
            }

            if ((static::$details['private_key'] || static::$details['public_key']) && PHP_VERSION_ID < 80000) {
                openssl_free_key($privkey);
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
