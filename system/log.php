<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Log
{
    /**
     * Log exception ke file.
     *
     * @param object $e
     */
    public static function exception($e)
    {
        $e = null;

        if (PHP_VERSION_ID >= 70000) {
            if ($e instanceof \Throwable) {
                $text = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();
            }
        } elseif ($e instanceof \Exception) {
            $text = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();
        } else {
            $text = 'A non-catchable error has occured.';
        }

        static::write('error', $text, $e);
    }

    /**
     * Tulis pesan ke file log.
     *
     * <code>
     *
     *      // Tulis pesan 'error' ke file log
     *      Log::write('error', 'Aduhh storage penuh!');
     *
     *      // Tulis pesan 'error' ke file log (menggunakan magic method)
     *      Log::error('error', 'Aduhh storage penuh!');
     *
     *      // Log pesan dengan data
     *      Log::write('info', 'User data: ', ['name' => 'budii', 'age' => 28]);
     *
     *      // Hasil: '[2022-06-24 17:43:02] log.INFO - User data: {"name":"budii","age":28}'
     *
     * </code>
     *
     * @param string     $type
     * @param string     $message
     * @param mixed|null $data
     */
    public static function write($type, $message, $data = null)
    {
        $message .= is_null($data) ? null : json_encode($data);

        if (Event::exists('rakit.log')) {
            Event::fire('rakit.log', [$type, $message]);
        }

        $message = static::format($type, $message);
        $path = path('storage').'logs'.DS.date('Y-m-d').'.log.php';

        if (is_file($path)) {
            file_put_contents($path, $message, LOCK_EX | FILE_APPEND);
        } else {
            $guard = "<?php defined('DS') or exit('No direct script access.'); ?>".PHP_EOL;
            file_put_contents($path, $guard.$message, LOCK_EX);
        }
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
        return '['.date('Y-m-d H:i:s').'] log.'.strtoupper($type).' - '.$message.PHP_EOL;
    }

    /**
     * Tulis pesan log secara dinamis.
     *
     * <code>
     *
     *      // Tulis pesan 'error' ke file log.
     *      Log::error('Ini adalah error!');
     *
     *      // Tulis pesan 'warning' ke file log.
     *      Log::warning('Ini adalah warning!');
     *
     *      // Log pesan dengan data
     *      Log::info('User data: ', ['name' => 'budii', 'age' => 28]);
     *
     *      // Hasil: '[2022-06-24 17:43:02] log.INFO - User data: {"name":"budii","age":28}'
     *
     * </code>
     */
    public static function __callStatic($method, array $parameters)
    {
        $parameters[1] = (isset($parameters[1]) && ! is_null($parameters[1])) ? $parameters[1] : null;
        static::write($method, $parameters[0], $parameters[1]);
    }
}
