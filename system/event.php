<?php

namespace System;

defined('DS') or exit('No direct access.');

class Event
{
    /**
     * Contains all registered events.
     *
     * @var array
     */
    public static $events = [];

    /**
     * Contains queued events waiting to be flushed.
     *
     * @var array
     */
    public static $queued = [];

    /**
     * Contains callback listeners for queued events.
     *
     * @var array
     */
    public static $flushers = [];

    /**
     * Determines if an event has listeners or not.
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
     * Registers a callback for the given event.
     *
     * <code>
     *
     *      // Register callback for event 'boot'
     *      Event::listen('boot', function() { return 'Oke, Booted!'; } );
     *
     * </code>
     *
     * @param string   $event
     * @param \Closure $handler
     */
    public static function listen($event, \Closure $handler)
    {
        static::$events[$event][] = $handler;
    }

    /**
     * Overrides all callback listeners for the given event with a new one.
     *
     * @param string   $event
     * @param \Closure $handler
     */
    public static function override($event, \Closure $handler)
    {
        static::clear($event);
        static::listen($event, $handler);
    }

    /**
     * Registers a callback queue flusher.
     *
     * @param string   $queue
     * @param \Closure $handler
     */
    public static function flusher($queue, \Closure $handler)
    {
        static::$flushers[$queue][] = $handler;
    }

    /**
     * Removes all listeners for the given event.
     *
     * @param string $event
     */
    public static function clear($event)
    {
        unset(static::$events[$event]);
    }

    /**
     * Runs the event and returns the first response.
     *
     * <code>
     *
     *      // Run the 'boot' event
     *      $response = Event::first('boot');
     *
     *      // Run the 'boot' event with custom parameters
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
     * Runs the event until a response is returned.
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
     * Flushes the event queue, running the flusher for each payload.
     *
     * @param string $queue
     */
    public static function flush($queue)
    {
        foreach (static::$flushers[$queue] as $flusher) {
            if (!isset(static::$queued[$queue])) {
                continue;
            }

            foreach (static::$queued[$queue] as $key => $payload) {
                array_unshift($payload, $key);
                call_user_func_array($flusher, $payload);
            }
        }
    }

    /**
     * Runs an event so that all listeners are called.
     *
     * <code>
     *
     *      // Run the 'boot' event
     *      $responses = Event::fire('boot');
     *
     *      // Run the 'boot' event with additional parameters
     *      $responses = Event::fire('boot', ['rakit', 'framework']);
     *
     *      // Run multiple events with the same parameters
     *      $responses = Event::fire(['boot', 'loading'], $parameters);
     *
     * </code>
     *
     * @param string|array $events
     * @param array        $parameters
     * @param bool         $halt
     *
     * @return array|null
     */
    public static function fire($events, array $parameters = [], $halt = false)
    {
        $events = (array) $events;
        $responses = [];

        foreach ($events as $event) {
            // Track event for debugger
            if (class_exists('\System\Foundation\Oops\Debugger') && class_exists('\System\Foundation\Oops\Collectors')) {
                if (!\System\Foundation\Oops\Debugger::$productionMode) {
                    \System\Foundation\Oops\Collectors::trackEvent($event, $parameters);
                }
            }

            if (!static::exists($event)) {
                continue;
            }

            foreach (static::$events[$event] as $handler) {
                $response = call_user_func_array($handler, $parameters);

                if ($halt && !is_null($response)) {
                    return $response;
                }

                $responses[] = $response;
            }
        }

        return $halt ? null : $responses;
    }
}
