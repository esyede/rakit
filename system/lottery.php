<?php

namespace System;

defined('DS') or exit('No direct access.');

class Lottery
{
    /**
     * Contains the chances of winning.
     *
     * @var int|float
     */
    protected $chances;

    /**
     * Contains the total number of chances.
     *
     * @var int|null
     */
    protected $out_of;

    /**
     * Callback when winning.
     *
     * @var null|callable
     */
    protected $winner;

    /**
     * Callback when losing.
     *
     * @var null|callable
     */
    protected $loser;

    /**
     * Result generator.
     *
     * @var callable|null
     */
    protected static $factory;

    /**
     * Constructor.
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
     * Create a new instance.
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
     * Set callback when winning.
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
     * Set callback when losing.
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
     * Invoke the lottery.
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
     * Run the lottery.
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
     * Set callback when winning or losing, randomly.
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
     * Check if the result is a win.
     *
     * @return bool
     */
    protected function wins()
    {
        $factory = static::factory();
        return $factory($this->chances, $this->out_of);
    }

    /**
     * Result generator.
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
     * Force the result to always win.
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
     * Force the result to always lose.
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
     * Alias for sequence.
     *
     * @param array         $sequence
     * @param callable|null $when_missing
     *
     * @return void
     */
    public static function fix($sequence, $when_missing = null)
    {
        return static::sequence($sequence, $when_missing);
    }

    /**
     * Set the sequence to be used for determining the result.
     *
     * @param  array  $sequence
     * @param  callable|null  $when_missing
     * @return void
     */
    public static function sequence($sequence, $when_missing = null)
    {
        $next = 0;
        $when_missing = $when_missing ?: function ($chances, $out_of) use (&$next) {
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
     * Alias for normal.
     *
     * @return void
     */
    public static function normally()
    {
        return static::normal();
    }

    /**
     * Indicates that the result should be determined normally.
     *
     * @return void
     */
    public static function normal()
    {
        static::$factory = null;
    }

    /**
     * Set the result generator.
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
