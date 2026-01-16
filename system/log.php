<?php

namespace System;

defined('DS') or exit('No direct access.');

class Log
{
    /**
     * Prefix nama file log.
     *
     * @var string
     */
    protected static $channel;

    /**
     * Set nama file tempat menyimpan log.
     *
     * @param string|null $name
     *
     * @return void
     */
    public static function channel($name = null)
    {
        static::$channel = (is_string($name) && strlen($name)) ? Str::slug($name) : null;
    }

    /**
     * Tulis log emergency.
     *
     * @param string $message
     * @param array  $context
     */
    public static function emergency($message, array $context = [])
    {
        static::write('emergency', $message, $context);
    }

    /**
     * Tulis log alert.
     *
     * @param string $message
     * @param array  $context
     */
    public static function alert($message, array $context = [])
    {
        static::write('alert', $message, $context);
    }

    /**
     * Tulis log critical.
     *
     * @param string $message
     * @param array  $context
     */
    public static function critical($message, array $context = [])
    {
        static::write('critical', $message, $context);
    }

    /**
     * Tulis log error.
     *
     * @param string $message
     * @param array  $context
     */
    public static function error($message, array $context = [])
    {
        static::write('error', $message, $context);
    }

    /**
     * Tulis log warning.
     *
     * @param string $message
     * @param array  $context
     */
    public static function warning($message, array $context = [])
    {
        static::write('warning', $message, $context);
    }

    /**
     * Tulis log notice.
     *
     * @param string $message
     * @param array  $context
     */
    public static function notice($message, array $context = [])
    {
        static::write('notice', $message, $context);
    }

    /**
     * Tulis log info.
     *
     * @param string $message
     * @param array  $context
     */
    public static function info($message, array $context = [])
    {
        static::write('info', $message, $context);
    }

    /**
     * Tulis log debug.
     *
     * @param string $message
     * @param array  $context
     */
    public static function debug($message, array $context = [])
    {
        static::write('debug', $message, $context);
    }

    /**
     * Tulis pesan ke file log.
     *
     * @param string $type
     * @param string $message
     * @param array  $context
     */
    protected static function write($type, $message, array $context = [])
    {
        if (!is_string($message)) {
            throw new \Exception(sprintf('The error message should be a string. %s given.', gettype($message)));
        }

        if (Event::exists('rakit.log')) {
            Event::fire('rakit.log', [$type, $message, $context]);
        }

        try {
            $channel = static::$channel;
            $date = Carbon::now()->format('Y-m-d');
            $appname = Config::get('application.name');
            $file = ((is_string($channel) && strlen($channel)) ? $channel : ($appname ? Str::slug($appname) : 'rakit')) . '_' . $date . '.log.php';
            $path = path('storage') . 'logs' . DS . $file;
            $formatted = static::format($type, $message, $context);

            file_put_contents($path, $formatted, LOCK_EX | (is_file($path) ? FILE_APPEND : 0));
        } catch (\Exception $e) {
            $path = path('storage') . 'logs' . DS . 'rakit.log.php';
            $formatted = static::format($type, $message, $context);

            try {
                file_put_contents($path, $formatted, LOCK_EX | (is_file($path) ? FILE_APPEND : 0));
            } catch (\Exception $exception) {
                // Silent fail jika fallback juga gagal
            }
        }
    }

    /**
     * Format pesan logging.
     *
     * @param string $type
     * @param string $message
     * @param array  $context
     *
     * @return string
     */
    protected static function format($type, $message, array $context = [])
    {
        $env = Foundation\Oops\Debugger::$productionMode ? 'production' : 'local';
        $date = Carbon::now()->format('Y-m-d H:i:s');
        $level = strtoupper((string) $type);
        $output = sprintf('[%s] %s.%s: %s', $date, $env, $level, $message);

        if (!empty($context)) {
            $formatted = static::format_context($context);
            $output .= $formatted ? ' ' . $formatted : '';
        }

        return $output . PHP_EOL;
    }

    /**
     * Format context data ke JSON.
     *
     * @param array $context
     *
     * @return string
     */
    protected static function format_context(array $context)
    {
        if (empty($context)) {
            return '';
        }

        $formatted = [];

        foreach ($context as $key => $value) {
            $formatted[$key] = static::format_value($value);
        }

        return json_encode($formatted, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Format nilai untuk logging dengan aman.
     *
     * @param mixed $value
     * @param array $objects
     * @param array $arrays
     *
     * @return mixed
     */
    protected static function format_value($value, array &$objects = [], array &$arrays = [])
    {
        $exception = (PHP_VERSION_ID < 70000) ? ($value instanceof \Exception) : ($value instanceof \Throwable || $value instanceof \Exception);

        if ($exception) {
            return static::format_exception($value);
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
                $formatted[$k] = static::format_value($v, $objects, $arrays);
            }

            unset($arrays[$hash]);
            return $formatted;
        }

        return $value;
    }

    /**
     * Format exception untuk logging.
     *
     * @param \Exception|object $e
     *
     * @return string
     */
    protected static function format_exception($e)
    {
        $class = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        $output = sprintf('[object] (%s(code: %s): %s at %s:%s)', $class, $e->getCode(), $message, $file, $line);

        if ($trace) {
            $output .= PHP_EOL . $trace;
        }

        return $output;
    }
}
