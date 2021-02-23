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
        if (PHP_VERSION_ID >= 70000) {
            if ($e instanceof \Throwable) {
                $exception = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();
            }
        } elseif ($e instanceof \Exception) {
            $exception = $e->getMessage().' in '.$e->getFile().' on line '.$e->getLine();
        } else {
            $exception = 'A non-catchable error has occured.';
        }

        static::write('error', $exception);
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
     *      // Log data array
     *      Log::write('info', ['name' => 'Budi', 'id' => '4', [21, 167, 54]], true);
     *
     *      // Hasil: Array ( [name] => Budi [id] => 4 [0] => Array ( [0] => 21 [1] => 167 [2] => 54 ) )
     *      // Jika $prettify bernilai TRUE, maka hasilnya menjadi: Array
     *
     * </code>
     *
     * @param string $type
     * @param string $message
     * @param bool   $prettify
     */
    public static function write($type, $message, $prettify = false)
    {
        $message = $prettify ? print_r($message, true) : $message;

        if (Event::listeners('rakit.log')) {
            Event::fire('rakit.log', [$type, $message]);
        }

        $message = static::format($type, $message);
        $path = path('storage').'logs'.DS.date('Y-m-d').'.log.php';

        if (is_file($path)) {
            File::append($path, $message);
        } else {
            File::put($path, "<?php defined('DS') or exit('No direct script access.'); ?>".PHP_EOL.$message);
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
        return date('Y-m-d H:i:s').' '.Str::upper($type).' - '.$message.PHP_EOL;
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
     *      // Log data array
     *      Log::write('info', ['name' => 'Budi', 'id' => '4', [21, 167, 54]], true);
     *
     *      // Hasil: Array ( [name] => Budi [id] => 4 [0] => Array ( [0] => 21 [1] => 167 [2] => 54 ) )
     *      // Jika parameter ke-dua bernilai TRUE, maka hasilnya menjadi: Array
     *
     * </code>
     */
    public static function __callStatic($method, $parameters)
    {
        $parameters[1] = empty($parameters[1]) ? false : $parameters[1];
        static::write($method, $parameters[0], $parameters[1]);
    }
}
