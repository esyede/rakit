<?php

namespace System;

defined('DS') or exit('No direct script access.');

class JWT
{
    public static $expiration = 60;
    public static $timestamp = 0;
    public static $leeway = 0;

    private static $algorithms = [
        'HS256' => 'SHA256',
        'HS512' => 'SHA512',
        'HS384' => 'SHA384',
    ];

    public static function encode(array $payloads, $secret, $algorithm = 'HS256', array $headers = [])
    {
        $timestamp = static::$timestamp ? static::$timestamp : time();
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;

        $payloads['exp'] = time() + static::$expiration;
        $payloads['jti'] = uniqid(time());
        $payloads['iat'] = time();

        $headers = array_merge($headers, ['typ' => 'JWT', 'alg' => $algorithm]);
        $headers = static::encode_url(static::encode_json($headers));
        $payloads = static::encode_url(static::encode_json($payloads));
        $message = $headers.'.'.$payloads;
        $signature = static::encode_url(static::signature($message, $secret, $algorithm));

        return $headers.'.'.$payloads.'.'.$signature;
    }

    public static function decode($token, $secret)
    {
        if (empty($secret)) {
            throw new \Exception('Secret cannot be empty');
        }

        $timestamp = static::$timestamp ? static::$timestamp : time();
        $jwt = explode('.', $token);

        if (count($jwt) !== 3) {
            throw new \Exception('Wrong number of segments');
        }

        list($headers64, $payloads64, $signature64) = $jwt;

        if (null === ($headers = static::decode_json(static::decode_url($headers64)))) {
            throw new \Exception('Invalid header encoding');
        }

        if (null === ($payloads = static::decode_json(static::decode_url($payloads64)))) {
            throw new \Exception('Invalid claims encoding');
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

    private static function signature($message, $secret, $algorithm)
    {
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;

        if (! array_key_exists($algorithm, static::$algorithms)) {
            throw new \Exception('Only these algorithm are supported: '.implode(', ', static::$algorithms));
        }

        return hash_hmac(static::$algorithms[$algorithm], $message, $secret, true);
    }

    private static function verify($message, $signature, $secret, $algorithm)
    {
        $algorithm = is_string($algorithm) ? strtoupper($algorithm) : $algorithm;

        if (empty(static::$algorithms[$algorithm])) {
            throw new \Exception('Only these algorithm are supported: '.implode(', ', static::$algorithms));
        }

        $hash = hash_hmac(static::$algorithms[$algorithm], $message, $secret, true);
        return Crypter::equals($signature, $hash);
    }

    private static function encode_url($data)
    {
        return str_replace('=', '', strtr(base64_encode($data), '+/', '-_'));
    }

    private static function decode_url($data)
    {
        $remainder  = strlen($data) % 4;
        $data .= $remainder ? str_repeat('=', 4 - $remainder) : '';

        return base64_decode(strtr($data, '-_', '+/'));
    }

    private static function encode_json($data)
    {
        $json = json_encode($data);

        if (JSON_ERROR_NONE !== json_last_error() && $errno = json_last_error()) {
            static::json_error($errno);
        } elseif ($json === 'null' && $data !== null) {
            throw new \Exception('Null result with non-null input');
        }

        return $json;
    }

    private static function decode_json($data)
    {
        $object = json_decode($data, false, 512, JSON_BIGINT_AS_STRING);

        if (JSON_ERROR_NONE !== json_last_error() && $errno = json_last_error()) {
            static::json_error($errno);
        } elseif ($object === null && $data !== 'null') {
            throw new \Exception('Null result with non-null input');
        }

        return $object;
    }

    private static function json_error($errno)
    {
        $messages = [
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Invalid or malformed JSON',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters',
        ];

        $message = isset_or($messages[$errno], sprintf('Unknown JSON error: %s', $errno));
        throw new \Exception($message);
    }
}

