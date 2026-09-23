<?php

namespace System\Log;

defined('DS') or exit('No direct access.');

class Formatter
{
    /**
     * Maximum nesting depth normalized into a JSON entry.
     *
     * @var int
     */
    const DEPTH = 9;

    /**
     * Format the log record as a human-readable line.
     *
     * @param array $record
     * @param bool  $timestamp
     *
     * @return string
     */
    public static function line(array $record, $timestamp = true)
    {
        $output = sprintf('%s.%s: %s', $record['env'], strtoupper((string) $record['level']), $record['message']);

        if ($timestamp) {
            $output = '['.$record['datetime']->format('Y-m-d H:i:s').'] '.$output;
        }

        if (! empty($record['context'])) {
            $context = static::context($record['context']);
            $output .= $context ? ' '.$context : '';
        }

        return $output;
    }

    /**
     * Format the log record as a single-line JSON object.
     *
     * @param array $record
     *
     * @return string
     */
    public static function json(array $record)
    {
        $data = [
            'datetime' => $record['datetime']->format('Y-m-d\TH:i:s.uP'),
            'env' => $record['env'],
            'channel' => static::normalize($record['channel']),
            'level' => strtoupper((string) $record['level']),
            'message' => static::normalize($record['message']),
            'context' => empty($record['context']) ? new \stdClass() : static::normalize($record['context']),
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        if (! is_string($json)) {
            // Normalization should prevent this, but never lose the message itself.
            $data['context'] = ['[unencodable]' => sprintf('json_encode() error code: %s', json_last_error())];
            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $json;
    }

    /**
     * Format the context data into JSON.
     *
     * @param array $context
     *
     * @return string
     */
    public static function context(array $context)
    {
        if (empty($context)) {
            return '';
        }

        $formatted = [];

        foreach ($context as $key => $value) {
            $formatted[$key] = static::value($value);
        }

        return json_encode($formatted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format the log value for the line format.
     *
     * @param mixed $value
     * @param array $objects
     * @param array $arrays
     *
     * @return mixed
     */
    public static function value($value, array &$objects = [], array &$arrays = [])
    {
        $exception = (PHP_VERSION_ID < 70000)
            ? ($value instanceof \Exception)
            : ($value instanceof \Throwable || $value instanceof \Exception);

        if ($exception) {
            return static::exception($value);
        }

        if (is_object($value)) {
            $id = function_exists('spl_object_id') ? spl_object_id($value) : spl_object_hash($value);

            if (isset($objects[$id])) {
                return sprintf('[object] (%s) [circular]', get_class($value));
            }

            $objects[$id] = true;
            $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            unset($objects[$id]);
            return (json_last_error() === JSON_ERROR_NONE) ? $json : sprintf('[object] (%s)', get_class($value));
        }

        if (is_resource($value)) {
            return sprintf('[resource] (%s)', get_resource_type($value));
        }

        if (is_array($value)) {
            $hash = md5(serialize($value));

            if (isset($arrays[$hash])) {
                return '[array] [circular]';
            }

            $arrays[$hash] = true;
            $formatted = [];

            foreach ($value as $k => $v) {
                $formatted[$k] = static::value($v, $objects, $arrays);
            }

            unset($arrays[$hash]);
            return $formatted;
        }

        return $value;
    }

    /**
     * Format the exception for the line format.
     *
     * @param \Exception|object $e
     *
     * @return string
     */
    public static function exception($e)
    {
        return vsprintf(
            '[object] (%s(code: %s): %s at %s:%s)',
            [get_class($e), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()]
        ).($e->getTraceAsString() ? PHP_EOL.$e->getTraceAsString() : '');
    }

    /**
     * Normalize a value into something json_encode() can always handle.
     *
     * @param mixed $value
     * @param int   $depth
     *
     * @return mixed
     */
    public static function normalize($value, $depth = 0)
    {
        if ($depth > static::DEPTH) {
            return '[max depth reached]';
        }

        if (is_string($value)) {
            return static::utf8($value);
        }

        if (is_null($value) || is_bool($value) || is_int($value)) {
            return $value;
        }

        if (is_float($value)) {
            // INF and NAN make json_encode() fail.
            return is_finite($value) ? $value : (string) $value;
        }

        if (is_array($value)) {
            $normalized = [];

            foreach ($value as $key => $item) {
                $normalized[is_string($key) ? static::utf8($key) : $key] = static::normalize($item, $depth + 1);
            }

            return $normalized;
        }

        if (($value instanceof \Exception) || ($value instanceof \Throwable)) {
            return static::throwable($value, $depth);
        }

        if (is_object($value)) {
            if (($value instanceof \DateTime) || ($value instanceof \DateTimeInterface)) {
                return $value->format('Y-m-d\TH:i:s.uP');
            }

            if ($value instanceof \JsonSerializable) {
                return static::normalize($value->jsonSerialize(), $depth + 1);
            }

            if (method_exists($value, '__toString')) {
                return static::utf8((string) $value);
            }

            return [get_class($value) => static::normalize(get_object_vars($value), $depth + 1)];
        }

        if (is_resource($value)) {
            return sprintf('[resource] (%s)', get_resource_type($value));
        }

        return sprintf('[%s]', gettype($value));
    }

    /**
     * Normalize an exception into an array.
     *
     * @param \Throwable|\Exception $e
     * @param int                   $depth
     *
     * @return array
     */
    protected static function throwable($e, $depth)
    {
        $data = [
            'class' => get_class($e),
            'message' => static::utf8((string) $e->getMessage()),
            'code' => static::normalize($e->getCode(), $depth + 1),
            'file' => static::utf8($e->getFile().':'.$e->getLine()),
            'trace' => static::normalize(explode("\n", $e->getTraceAsString()), $depth + 1),
        ];

        if ($e->getPrevious()) {
            $data['previous'] = static::normalize($e->getPrevious(), $depth + 1);
        }

        return $data;
    }

    /**
     * Replace invalid UTF-8 sequences, which json_encode() refuses to encode.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function utf8($value)
    {
        return ('' === $value || preg_match('//u', $value))
            ? $value
            : mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
