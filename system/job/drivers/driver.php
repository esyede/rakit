<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Request;
use System\Log;

abstract class Driver
{
    /**
     * Tambahkan sebuah job.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     * @param string      $queue
     * @param bool        $without_overlapping
     *
     * @return bool
     */
    abstract public function add(
        $name,
        array $payloads = [],
        $scheduled_at = null,
        $queue = 'default',
        $without_overlapping = false
    );

    /**
     * Cek apakah job sedang overlapping.
     *
     * @param string $name
     * @param string $queue
     *
     * @return bool
     */
    abstract public function has_overlapping($name, $queue = 'default');

    /**
     * Hapus job berdasarkan nama.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    abstract public function forget($name, $queue = null);

    /**
     * Jalankan antrian job di database.
     *
     * @param string      $name
     * @param int         $retries
     * @param int         $sleep_ms
     * @param string|null $queue
     *
     * @return bool
     */
    abstract public function run($name, $retries = 1, $sleep_ms = 0, $queue = null);

    /**
     * Jalankan semua job di database.
     *
     * @param int         $retries
     * @param int         $sleep_ms
     * @param array|null  $queues
     *
     * @return bool
     */
    abstract public function runall($retries = 1, $sleep_ms = 0, $queues = null);

    /**
     * Log pesan job.
     *
     * @param string $message
     * @param string $type
     */
    protected function log($message, $type = 'info')
    {
        if (Config::get('job.logging')) {
            Log::channel('jobs');
            Log::{$type}($message);
            Log::channel(null);

            if (Request::cli()) {
                $message = '[' . Carbon::now()->format('Y-m-d H:i:s') . '] ';
                $message .= '[' . strtoupper((string) $type) . '] ' . $message . PHP_EOL;
                echo $message;
            }
        }
    }
}
