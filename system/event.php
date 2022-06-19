<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Event
{
    /**
     * Berisi seluruh event terdaftar.
     *
     * @var array
     */
    public static $events = [];

    /**
     * Berisi antrian event yang menunggu di-flush.
     *
     * @var array
     */
    public static $queued = [];

    /**
     * Berisi callback milik queue-flusher terdaftar.
     *
     * @var array
     */
    public static $flushers = [];

    /**
     * Tentukan apakah event punya listener atau tidak.
     *
     * @param string $event
     *
     * @return bool
     */
    public static function exists($event)
    {
        return isset(static::$events[$event]);
    }

    /**
     * Daftarkan callback untuk item yang diberikan.
     *
     * <code>
     *
     *      // Daftarkan callback untuk event 'boot'
     *      Event::listen('boot', function() { return 'Oke, Booted!'; } );
     *
     *      // Daftarkan object sebagai callback event
     *      Event::listen('event', [$object, 'nama_method']);
     *
     * </code>
     *
     * @param string $event
     * @param mixed  $callback
     */
    public static function listen($event, $callback)
    {
        static::$events[$event][] = $callback;
    }

    /**
     * Timpa seluruh callback milik event dengan callback yang baru.
     *
     * @param string $event
     * @param mixed  $callback
     */
    public static function override($event, $callback)
    {
        static::clear($event);
        static::listen($event, $callback);
    }

    /**
     * Tambahkan item ke antrian event untuk diproses.
     *
     * @param string $queue
     * @param string $key
     * @param array  $data
     */
    public static function queue($queue, $key, array $data = [])
    {
        static::$queued[$queue][$key] = $data;
    }

    /**
     * Daftarkan callback queue flusher.
     *
     * @param string   $queue
     * @param \Closure $callback
     */
    public static function flusher($queue, \Closure $callback)
    {
        static::$flushers[$queue][] = $callback;
    }

    /**
     * Hapus semua listener milik event yang diberikan.
     *
     * @param string $event
     */
    public static function clear($event)
    {
        unset(static::$events[$event]);
    }

    /**
     * Jalankan event dan return respon pertamanya.
     *
     * <code>
     *
     *      // Jalankan event 'boot'
     *      $response = Event::first('boot');
     *
     *      // Jalankan event 'boot' dengan tambahan parameter kustom
     *      $response = Event::first('boot', ['rakit', 'framework']);
     *
     * </code>
     *
     * @param string $event
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function first($event, array $parameters = [])
    {
        return head(static::fire($event, $parameters));
    }

    /**
     * Jalankan event dan return respon pertamanya.
     * Eksekusi akan dihentikan setelah respon valid pertama ditemukan.
     *
     * @param string $event
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function until($event, array $parameters = [])
    {
        return static::fire($event, $parameters, true);
    }

    /**
     * Flush antrian event, jalankan flusher untuk setiap payload.
     *
     * @param string $queue
     */
    public static function flush($queue)
    {
        foreach (static::$flushers[$queue] as $flusher) {
            if (! isset(static::$queued[$queue])) {
                continue;
            }

            foreach (static::$queued[$queue] as $key => $payload) {
                array_unshift($payload, $key);
                call_user_func_array($flusher, $payload);
            }
        }
    }

    /**
     * Jalankan sebuah event agar semua listener ikut terpanggil.
     *
     * <code>
     *
     *      // Jalankan event 'boot'
     *      $responses = Event::fire('boot');
     *
     *      // Jalankan event 'boot' dengan tambahan parameter
     *      $responses = Event::fire('boot', ['rakit', 'framework']);
     *
     *      // Jalankan beberapa event dengan parameter yang sama
     *      $responses = Event::fire(['boot', 'loading'], $parameters);
     *
     * </code>
     *
     * @param string|array $events
     * @param array        $parameters
     * @param bool         $halt
     *
     * @return array
     */
    public static function fire($events, array $parameters = [], $halt = false)
    {
        $events = (array) $events;
        $responses = [];

        foreach ($events as $event) {
            if (static::exists($event)) {
                foreach (static::$events[$event] as $callback) {
                    $response = call_user_func_array($callback, $parameters);

                    if ($halt && ! is_null($response)) {
                        return $response;
                    }

                    $responses[] = $response;
                }
            }
        }

        return $halt ? null : $responses;
    }
}
