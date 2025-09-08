<?php

namespace System;

defined('DS') or exit('No direct access.');

class JWT
{
    protected static $algorithms = [
        'HS256' => ['hash' => 'SHA256', 'type' => 'symmetric'],
        'HS384' => ['hash' => 'SHA384', 'type' => 'symmetric'],
        'HS512' => ['hash' => 'SHA512', 'type' => 'symmetric'],
        'RS256' => ['hash' => 'SHA256', 'type' => 'asymmetric'],
        'RS384' => ['hash' => 'SHA384', 'type' => 'asymmetric'],
        'RS512' => ['hash' => 'SHA512', 'type' => 'asymmetric'],
    ];

    public static $leeway = 0;
    public static $timestamp;

    /**
     * Encode payload.
     *
     * @param array  $payloads
     * @param string $key
     * @param array  $headers
     * @param string $algorithm
     *
     * @return string
     */
    public static function encode(array $payloads, $key, array $headers = [], $algorithm = 'HS256')
    {
        if (!is_string($key) || strlen($key) < 1) {
            throw new \Exception('Key cannot be empty or non-string value');
        }

        if (!is_string($algorithm) || strlen($algorithm) < 1) {
            throw new \Exception('Empty or non-string algorithm');
        }

        $algorithm = strtoupper($algorithm);

        if (!isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                "Only these algorithms are supported: %s. Got '%s' (%s)",
                implode(', ', array_keys(static::$algorithms)),
                $algorithm,
                gettype($algorithm)
            ));
        }

        $headers = array_merge($headers, ['typ' => 'JWT', 'alg' => $algorithm]);
        $headers = static::encode_url(static::encode_json($headers));
        $payloads = static::encode_url(static::encode_json($payloads));
        $signature = static::encode_url(static::signature($headers . '.' . $payloads, $key, $algorithm));
        return $headers . '.' . $payloads . '.' . $signature;
    }

    /**
     * Decode payload.
     *
     * @param string $token
     * @param string $key
     *
     * @return \stdClass
     */
    public static function decode($token, $key)
    {
        if (!is_string($key) || strlen($key) < 1) {
            throw new \Exception('Key cannot be empty or non-string value');
        }

        $jwt = explode('.', $token);
        $timestamp = static::$timestamp ?: time();
        if (!is_array($jwt) || count($jwt) !== 3) {
            throw new \Exception('Wrong number of segments');
        }

        list($headers64, $payloads64, $signature64) = $jwt;
        $headers = static::decode_json(static::decode_url($headers64));
        if (null === $headers) {
            throw new \Exception('Invalid header encoding');
        }

        $payloads = static::decode_json(static::decode_url($payloads64));
        if (null === $payloads) {
            throw new \Exception('Invalid claims encoding');
        }

        if (is_array($payloads) && count($payloads) < 1) {
            $payloads = new \stdClass();
        }

        $signature = static::decode_url($signature64);
        if (false === $signature) {
            throw new \Exception('Invalid signature encoding');
        }

        if (!isset($headers->alg) || !is_string($headers->alg) || strlen($headers->alg) < 1) {
            throw new \Exception('Empty or non-string algorithm');
        }

        if (!isset(static::$algorithms[$headers->alg])) {
            throw new \Exception(sprintf(
                "Only these algorithms are supported: %s. Got '%s' (%s)",
                implode(', ', array_keys(static::$algorithms)),
                $headers->alg,
                gettype($headers->alg)
            ));
        }

        if (!isset($headers->typ) || $headers->typ !== 'JWT') {
            throw new \Exception('Invalid token type');
        }

        if (!static::verify($headers64 . '.' . $payloads64, $signature, $key, $headers->alg)) {
            throw new \Exception('Signature verification failed');
        }

        if (isset($payloads->nbf) && $payloads->nbf > ($timestamp + static::$leeway)) {
            throw new \Exception(sprintf('Cannot handle token prior to %s', date(\DateTime::ATOM, $payloads->nbf)));
        }

        if (isset($payloads->iat) && $payloads->iat > ($timestamp + static::$leeway)) {
            throw new \Exception(sprintf('Cannot handle token prior to %s', date(\DateTime::ATOM, $payloads->iat)));
        }

        if (isset($payloads->exp) && ($timestamp - static::$leeway) >= $payloads->exp) {
            throw new \Exception('Expired token');
        }

        return $payloads;
    }

    /**
     * Buat signature untuk encode.
     *
     * @param string $payload
     * @param string $key
     * @param string $algorithm
     *
     * @return string
     */
    private static function signature($payload, $key, $algorithm)
    {
        $algorithm = strtoupper((string) $algorithm);
        if (!isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                "Only these algorithms are supported: %s. Got '%s' (%s)",
                implode(', ', array_keys(static::$algorithms)),
                $algorithm,
                gettype($algorithm)
            ));
        }

        $info = static::$algorithms[$algorithm];
        if ($info['type'] === 'symmetric') {
            return hash_hmac($info['hash'], $payload, $key, true);
        } elseif ($info['type'] === 'asymmetric') {
            return static::rsa_sign($payload, $key, $algorithm);
        }

        throw new \Exception('Unsupported algorithm type');
    }

    /**
     * Verifikasi signature.
     *
     * @param string $payload
     * @param string $signature
     * @param string $key
     * @param string $algorithm
     *
     * @return bool
     */
    private static function verify($payload, $signature, $key, $algorithm)
    {
        $algorithm = strtoupper((string) $algorithm);
        if (!isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                'Only these algorithms are supported: %s, got: %s (%s)',
                implode(', ', array_keys(static::$algorithms)),
                $algorithm,
                gettype($algorithm)
            ));
        }

        $info = static::$algorithms[$algorithm];
        if ($info['type'] === 'symmetric') {
            $expected = hash_hmac($info['hash'], $payload, $key, true);
            return Crypter::equals($expected, $signature);
        } elseif ($info['type'] === 'asymmetric') {
            return static::rsa_verify($payload, $signature, $key, $algorithm);
        }

        throw new \Exception('Unsupported algorithm type');
    }

    /**
     * Buat signature RSA.
     *
     * @param string $payload
     * @param string $private_key
     * @param string $algorithm
     *
     * @return string
     */
    private static function rsa_sign($payload, $private_key, $algorithm)
    {
        if (!function_exists('openssl_sign')) {
            throw new \Exception('OpenSSL extension is not available');
        }

        $success = openssl_sign($payload, $signature, $private_key, static::$algorithms[$algorithm]['hash']);
        if (!$success) {
            throw new \Exception('OpenSSL unable to sign data');
        }

        return $signature;
    }

    /**
     * Verifikasi signature RSA.
     *
     * @param string $payload
     * @param string $signature
     * @param string $public_key
     * @param string $algorithm
     *
     * @return bool
     */
    private static function rsa_verify($payload, $signature, $public_key, $algorithm)
    {
        if (!function_exists('openssl_verify')) {
            throw new \Exception('OpenSSL extension is not available');
        }

        $result = openssl_verify($payload, $signature, $public_key, static::$algorithms[$algorithm]['hash']);
        if ($result === -1) {
            throw new \Exception('OpenSSL error: ' . openssl_error_string());
        }

        return $result === 1;
    }

    /**
     * Encode string ke url base64.
     *
     * @param string $data
     *
     * @return string
     */
    private static function encode_url($data)
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }

    /**
     * Decode string dari url base64.
     *
     * @param string $data
     *
     * @return string
     */
    private static function decode_url($data)
    {
        $remainder = strlen((string) $data) % 4;
        $data .= $remainder ? str_repeat('=', 4 - $remainder) : '';
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Encode data ke bentuk json.
     *
     * @param mixed $data
     *
     * @return string
     */
    private static function encode_json($data)
    {
        $json = json_encode($data);
        if (JSON_ERROR_NONE !== json_last_error()) {
            static::json_error(json_last_error());
        } elseif ($json === 'null' && $data !== null) {
            throw new \Exception('Null result with non-null input');
        }

        return $json;
    }

    /**
     * Decode json ke bentuk object.
     *
     * @param string $data
     *
     * @return \stdClass
     */
    private static function decode_json($data)
    {
        $object = json_decode($data, false);
        if (JSON_ERROR_NONE !== json_last_error()) {
            static::json_error(json_last_error());
        } elseif ($object === null && $data !== 'null') {
            throw new \Exception('Null result with non-null input');
        }

        return $object;
    }

    /**
     * Tangani error json.
     *
     * @param int $code
     *
     * @return void
     */
    private static function json_error($code)
    {
        $errors = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters',
        ];
        throw new \Exception(isset($errors[$code]) ? $errors[$code] : sprintf('Unknown JSON error: %s', $code));
    }
}
