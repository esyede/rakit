<?php

namespace System;

defined('DS') or exit('No direct script access.');

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
     * Algoritma yang didukung
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
     * @param string $algorithm
     * @param array  $headers
     *
     * @return string
     */
    public static function encode(array $payloads, $secret, $algorithm = 'HS256', array $headers = [])
    {
        $timestamp = static::$timestamp ? static::$timestamp : time();
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;
        $headers = $headers + ['typ' => 'JWT', 'alg' => $algorithm];

        $headers = static::encode_url(static::encode_json($headers));
        $payloads = static::encode_url(static::encode_json($payloads));
        $message = $headers.'.'.$payloads;
        $signature = static::encode_url(static::signature($message, $secret, $algorithm));

        return $headers.'.'.$payloads.'.'.$signature;
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
        if (empty($secret)) {
            throw new \Exception('Secret cannot be empty');
        }

        $timestamp = static::$timestamp ? static::$timestamp : time();
        $jwt = explode('.', $token);

        if (! is_array($jwt) || count($jwt) !== 3) {
            throw new \Exception('Wrong number of segments');
        }

        list($headers64, $payloads64, $signature64) = $jwt;

        if (null === ($headers = static::decode_json(static::decode_url($headers64)))) {
            throw new \Exception('Invalid header encoding');
        }

        if (null === ($payloads = static::decode_json(static::decode_url($payloads64)))) {
            throw new \Exception('Invalid claims encoding');
        }


        if (is_array($payloads) && count($payloads) < 1) {
            $payloads = new \stdClass;
        }

        if (false === ($signature = static::decode_url($signature64))) {
            throw new \Exception('Invalid signature encoding');
        }

        if (empty($headers->alg)) {
            throw new \Exception('Empty algorithm');
        }

        if (empty(static::$algorithms[$headers->alg])) {
            throw new \Exception('Only these algorithm are supported: '.implode(', ', static::$algorithms));
        }

        if (! static::verify($headers64.'.'.$payloads64, $signature, $secret, $headers->alg)) {
            throw new \Exception('Signature verification failed');
        }

        if (isset($payloads->nbf) && $payloads->nbf > ($timestamp + static::$leeway)) {
            throw new \Exception('Cannot handle token prior to '.date(\DateTime::ISO8601, $payloads->nbf));
        }

        if (isset($payloads->iat) && $payloads->iat > ($timestamp + static::$leeway)) {
            throw new \Exception('Cannot handle token prior to '.date(\DateTime::ISO8601, $payloads->iat));
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
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;

        if (! isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                'Only these algorithms are supported: %s, got: %s (%s)',
                implode(', ', array_keys(static::$algorithms)), $algorithm, gettype($algorithm)
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
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;

        if (! isset(static::$algorithms[$algorithm])) {
            throw new \Exception(sprintf(
                'Only these algorithms are supported: %s, got: %s (%s)',
                implode(', ', array_keys(static::$algorithms)), $algorithm, gettype($algorithm)
            ));
        }

        $hash = hash_hmac(static::$algorithms[$algorithm], $payload, $secret, true);
        return Crypter::equals($signature, $hash);
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
        $remainder = strlen($data) % 4;
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
        $json = (PHP_VERSION_ID >= 50500) ? json_encode($data, 0, 512) : json_encode($data);

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
     * @return string
     */
    private static function decode_json($data)
    {
        $object = json_decode($data, false, 512, JSON_BIGINT_AS_STRING);

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
     * @param int $errno
     *
     * @return void
     */
    private static function json_error($errno)
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters',
        ];

        $message = isset($messages[$errno])
            ? $messages[$errno]
            : sprintf('Unknown JSON error: %s', $errno);

        throw new \Exception($message);
    }
}
