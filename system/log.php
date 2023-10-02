<?php

namespace System;

defined('DS') or exit('No direct access.');

class Log
{
    /**
     * Nama file log.
     *
     * @var string
     */
    protected static $channel;

    /**
     * Set nama file tempat menyimpan log.
     *
     * @param string|null $file
     *
     * @return void
     */
    public static function channel($name = null)
    {
        $name = (is_string($name) && strlen($name)) ? Str::slug($name) : date('Y-m-d');
        static::$channel = Str::replace_last('.log', '', Str::replace_last('.php', '', $name));
    }

    /**
     * Tulis log info.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function info($message, $data = null)
    {
        static::write('info', $message, $data);
    }

    /**
     * Tulis log warning.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function warning($message, $data = null)
    {
        static::write('warning', $message, $data);
    }

    /**
     * Tulis log error.
     *
     * @param string     $message
     * @param mixed|null $data
     */
    public static function error($message, $data = null)
    {
        static::write('error', $message, $data);
    }

    /**
     * Tulis pesan ke file log.
     *
     * @param string     $type
     * @param string     $message
     * @param mixed|null $data
     */
    protected static function write($type, $message, $data = null)
    {
        if (!is_string($message)) {
            throw new \Exception(sprintf('The error message should be a string. %s given.', gettype($message)));
        }

        $message .= $data ? Foundation\Oops\Dumper::toText($data, ['truncate' => PHP_INT_MAX]) : '';

        if (Event::exists('rakit.log')) {
            Event::fire('rakit.log', [$type, $message]);
        }

        $channel = static::$channel;
        $channel = (is_string($channel) && strlen($channel)) ? Str::slug($channel) : date('Y-m-d');
        $path = path('storage') . 'logs' . DS . $channel . '.log.php';
        $message = static::format($type, $message);

        file_put_contents($path, $message, LOCK_EX | (is_file($path) ? FILE_APPEND : 0));
    }

    /**
     * Format pesan logging.
     *
     * @param string $type
     * @param string $message
     *
     * @return string
     */
    protected static function format($type, $message)
    {
        $env = Foundation\Oops\Debugger::$productionMode ? 'production' : 'local';
        return sprintf('[%s] %s.%s: %s' . PHP_EOL, date('Y-m-d H:i:s'), $env, strtoupper((string) $type), $message);
    }
}
