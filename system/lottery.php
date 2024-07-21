<?php

namespace System;

defined('DS') or exit('No direct access.');

class Lottery
{
    /**
     * Jumlah ekspektasi kemenangan.
     *
     * @var int|float
     */
    protected $chances;

    /**
     * Jumlah peluang potensi kemenangan.
     *
     * @var int|null
     */
    protected $out_of;

    /**
     * Callback ketika menang.
     *
     * @var null|callable
     */
    protected $winner;

    /**
     * Callback ketika kalah.
     *
     * @var null|callable
     */
    protected $loser;

    /**
     * Generator hasil.
     *
     * @var callable|null
     */
    protected static $factory;

    /**
     * Konstruktor.
     *
     * @param int|float $chances
     * @param int|null  $out_of
     *
     * @return void
     */
    public function __construct($chances, $out_of = null)
    {
        if ($out_of === null && is_float($chances) && $chances > 1) {
            throw new \Exception('Float must not be greater than 1.');
        }

        $this->chances = $chances;
        $this->out_of = $out_of;
    }

    /**
     * Buat instance baru.
     *
     * @param int|float $chances
     * @param int|null  $out_of
     *
     * @return static
     */
    public static function odds($chances, $out_of = null)
    {
        return new static($chances, $out_of);
    }

    /**
     * Set callback ketika menang.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function winner($callback)
    {
        $this->winner = $callback;
        return $this;
    }

    /**
     * Set callback ketika kalah.
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function loser($callback)
    {
        $this->loser = $callback;
        return $this;
    }

    /**
     * Jalankan.
     *
     * @param mixed ...$args
     *
     * @return mixed
     */
    public function __invoke(/** ...$args */)
    {
        $args = func_get_args();
        return $this->run_callback($args);
    }

    /**
     * Jalankan.
     *
     * @param null|int $times
     *
     * @return mixed
     */
    public function choose($times = null)
    {
        if ($times === null) {
            return $this->run_callback();
        }

        $results = [];

        for ($i = 0; $i < $times; $i++) {
            $results[] = $this->run_callback();
        }

        return $results;
    }

    /**
     * Set callback ketika menang atau kalah, secara acak.
     *
     * @param mixed ...$args
     *
     * @return callable
     */
    protected function run_callback(/** ...$args */)
    {
        $args = func_get_args();

        if ($this->wins()) {
            return $this->winner ? call_user_func_array($this->winner, $args) : true;
        }

        return $this->loser ? call_user_func_array($this->loser, $args) : false;
    }

    /**
     * Cek apakah hasilnya menang.
     *
     * @return bool
     */
    protected function wins()
    {
        $factory = static::factory();
        return $factory($this->chances, $this->out_of);
    }

    /**
     * Generator hasil.
     *
     * @return callable
     */
    protected static function factory()
    {
        if (static::$factory) {
            return static::$factory;
        }

        return function ($chances, $out_of) {
            return ($out_of === null)
                ? (Str::integers(0, PHP_INT_MAX) / PHP_INT_MAX <= $chances)
                : (Str::integers(1, $out_of) <= $chances);
        };
    }

    /**
     * Paksa agar hasilnya menang terus.
     *
     * @param callable|null $callback
     *
     * @return void
     */
    public static function always_win($callback = null)
    {
        self::set_factory(function () {
            return true;
        });

        if ($callback === null) {
            return;
        }

        $callback();
        static::normal();
    }

    /**
     * Paksa agar hasilnya kalah terus.
     *
     * @param callable|null $callback
     *
     * @return void
     */
    public static function always_lose($callback = null)
    {
        self::set_factory(function () {
            return false;
        });

        if ($callback === null) {
            return;
        }

        $callback();
        static::normal();
    }

    /**
     * Atur urutan yang akan digunakan untuk menentukan hasil (alias).
     *
     * @param array         $sequence
     * @param callable|null $when_missing
     *
     * @return void
     */
    public static function fix($sequence, $when_missing = null)
    {
        return static::sequencify($sequence, $when_missing);
    }

    /**
     * Atur urutan yang akan digunakan untuk menentukan hasil.
     *
     * @param  array  $sequence
     * @param  callable|null  $when_missing
     * @return void
     */
    public static function sequencify($sequence, $when_missing = null)
    {
        $next = 0;
        $when_missing = $when_missing ? $when_missing : function ($chances, $out_of) use (&$next) {
            $cache = static::$factory;
            static::$factory = null;
            $factory = static::factory();
            $result = $factory($chances, $out_of);
            static::$factory = $cache;
            $next++;
            return $result;
        };

        static::set_factory(function ($chances, $out_of) use (&$next, $sequence, $when_missing) {
            return array_key_exists($next, $sequence) ? $sequence[$next++] : $when_missing($chances, $out_of);
        });
    }

    /**
     * Menunjukkan bahwa hasil harus ditentukan secara normal (alias).
     *
     * @return void
     */
    public static function normally()
    {
        return static::normal();
    }

    /**
     * Menunjukkan bahwa hasil harus ditentukan secara normal.
     *
     * @return void
     */
    public static function normal()
    {
        static::$factory = null;
    }

    /**
     * Set generator yang harus digunakan untuk hasil normal.
     *
     * @param callable $factory
     *
     * @return void
     */
    public static function set_factory($factory)
    {
        self::$factory = $factory;
    }
}
