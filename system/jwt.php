<?php

namespace System;

defined('DS') or exit('No direct access.');

class JWT
{
    /**
     * Timestamp saat ini.
     *
     * @var int
     */
    public static $timestamp = 0;

    /**
     * Leeway time.
     *
     * @var int
     */
    public static $leeway = 0;

    /**
     * Algoritma yang didukung.
     *
     * @var array
     */
    private static $algorithms = [
        'HS256' => 'SHA256',
        'HS384' => 'SHA384',
        'HS512' => 'SHA512',
    ];

    /**
     * Encode payload.
     *
     * @param array  $payloads
     * @param string $secret
     * @param array  $headers
     * @param string $algorithm
     *
     * @return string
     */
    public static function encode(array $payloads, $secret, array $headers = [], $algorithm = 'HS256')
    {
        if (!is_string($secret) || strlen($secret) < 1) {
            throw new \Exception('Secret cannot be empty or non-string value');
        }

        if (!is_string($algorithm) || strlen($algorithm) < 1) {
            throw new \Exception('Empty or non-string algorithm');
        }

        $algorithm = strtoupper($algorithm);

        if (!isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                "Only these algorithm are supported: %s. Got '%s' (%s)",
                implode(', ', static::$algorithms),
                $algorithm,
                gettype($algorithm)
            ));
        }

        $headers = array_merge($headers, ['typ' => 'JWT', 'alg' => $algorithm]);
        $headers = static::encode_url(static::encode_json($headers));
        $payloads = static::encode_url(static::encode_json($payloads));
        $signature = static::encode_url(static::signature($headers . '.' . $payloads, $secret, $algorithm));

        return $headers . '.' . $payloads . '.' . $signature;
    }

    /**
     * Decode payload.
     *
     * @param string $token
     * @param string $secret
     *
     * @return \stdClass
     */
    public static function decode($token, $secret)
    {
        if (!is_string($secret) || strlen($secret) < 1) {
            throw new \Exception('Secret cannot be empty or non-string value');
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

        if (!isset(static::$algorithms[$headers->alg]) || !static::$algorithms[$headers->alg]) {
            throw new \Exception(sprintf(
                "Only these algorithm are supported: %s. Got '%s' (%s)",
                implode(', ', static::$algorithms),
                $headers->alg,
                gettype($headers->alg)
            ));
        }

        if (!static::verify($headers64 . '.' . $payloads64, $signature, $secret, $headers->alg)) {
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
     * @param string $secret
     * @param string $algorithm
     *
     * @return string
     */
    private static function signature($payload, $secret, $algorithm)
    {
        $algorithm = strtoupper((string) $algorithm);

        if (!isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                "Only these algorithm are supported: %s. Got '%s' (%s)",
                implode(', ', static::$algorithms),
                $algorithm,
                gettype($algorithm)
            ));
        }

        return hash_hmac(static::$algorithms[$algorithm], $payload, $secret, true);
    }

    /**
     * Verifikasi signature.
     *
     * @param string $payload
     * @param string $signature
     * @param string $secret
     * @param string $algorithm
     *
     * @return bool
     */
    private static function verify($payload, $signature, $secret, $algorithm)
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

        return Crypter::equals($signature, hash_hmac(static::$algorithms[$algorithm], $payload, $secret, true));
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
