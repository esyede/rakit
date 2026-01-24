<?php

namespace System;

defined('DS') or exit('No direct access.');

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
     * @param int    $padding
     *
     * @return string
     */
    public static function encrypt($data, $padding = OPENSSL_PKCS1_PADDING)
    {
        static::generate();
        $pubkey = openssl_pkey_get_public(static::$details['public_key']);
        $key_details = openssl_pkey_get_details($pubkey);
        $length = (int) ceil($key_details['bits'] / 8) - ($padding === OPENSSL_PKCS1_OAEP_PADDING ? 42 : 11);
        $data = (string) $data;
        $result = '';

        while ($data) {
            $chunk = mb_substr($data, 0, $length, '8bit');
            $data = mb_substr($data, $length, null, '8bit');
            $temp = '';

            if (!openssl_public_encrypt($chunk, $temp, $pubkey, $padding)) {
                $errors = static::get_openssl_errors();
                throw new \Exception('Failed to encrypt the data: ' . $errors);
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
            openssl_free_key($pubkey);
        }

        return $result;
    }

    /**
     * Dekripsi data menggunakan RSA key.
     *
     * @param string $encrypted
     * @param int    $padding
     *
     * @return string
     */
    public static function decrypt($encrypted, $padding = OPENSSL_PKCS1_PADDING)
    {
        static::generate();

        if (!($privkey = openssl_pkey_get_private(static::$details['private_key']))) {
            $errors = static::get_openssl_errors();
            throw new \Exception('Failed to obtain private key: ' . $errors);
        }

        $key = openssl_pkey_get_details($privkey);
        $length = ceil($key['bits'] / 8);
        $result = '';

        while ($encrypted) {
            $encrypted = (string) $encrypted;
            $chunk = mb_substr($encrypted, 0, $length, '8bit');
            $encrypted = mb_substr($encrypted, $length, null, '8bit');
            $temp = '';

            if (!openssl_private_decrypt($chunk, $temp, $privkey, $padding)) {
                $errors = static::get_openssl_errors();
                throw new \Exception('Failed to decrypt the data: ' . $errors);
            }

            $result .= $temp;
        }

        if (PHP_VERSION_ID < 80000) {
            /** @disregard */
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
                /** @disregard */
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
     * Load existing private dan public keys.
     *
     * @param string $private_key
     * @param string $public_key
     *
     * @return void
     */
    public static function load_keys($private_key, $public_key = null)
    {
        static::$details['private_key'] = $private_key;

        if ($public_key) {
            static::$details['public_key'] = $public_key;
        } else {
            $privkey = openssl_pkey_get_private($private_key);

            if (!$privkey) {
                $errors = static::get_openssl_errors();
                throw new \Exception('Invalid private key: ' . $errors);
            }

            $details = openssl_pkey_get_details($privkey);
            static::$details['public_key'] = $details['key'];

            if (PHP_VERSION_ID < 80000) {
                /** @disregard */
                openssl_free_key($privkey);
            }
        }
    }

    /**
     * Export private key.
     *
     * @return string
     */
    public static function export_private()
    {
        static::generate();
        return static::$details['private_key'];
    }

    /**
     * Export public key.
     *
     * @return string
     */
    public static function export_public()
    {
        static::generate();
        return static::$details['public_key'];
    }

    /**
     * Ambil OpenSSL errors.
     *
     * @return string
     */
    private static function get_openssl_errors()
    {
        $errors = '';

        while (false !== ($message = openssl_error_string())) {
            $errors .= $message . PHP_EOL;
        }

        return trim($errors);
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
