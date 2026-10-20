<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Schedule\Event;

class Schedule
{
    /**
     * Contains the scheduled events.
     *
     * @var Event[]
     */
    public static $events = [];

    /**
     * Schedule a closure.
     *
     * @param \Closure $callback
     * @param array    $parameters
     *
     * @return Event
     */
    public static function call(\Closure $callback, array $parameters = [])
    {
        return static::$events[] = new Event('call', $callback, $parameters);
    }

    /**
     * Schedule a console command, run as 'php rakit <command>' in its own process.
     *
     * @param string $command
     *
     * @return Event
     */
    public static function command($command)
    {
        return static::$events[] = new Event('command', (string) $command);
    }

    /**
     * Schedule a shell command.
     *
     * @param string $command
     *
     * @return Event
     */
    public static function exec($command)
    {
        return static::$events[] = new Event('exec', (string) $command);
    }

    /**
     * Get the events due at the given time (now by default).
     *
     * @param \DateTime|null $now
     *
     * @return Event[]
     */
    public static function due($now = null)
    {
        $now = ($now instanceof \DateTime) ? $now : new \DateTime('now');
        $due = [];

        foreach (static::$events as $event) {
            if ($event->is_due($now)) {
                $due[] = $event;
            }
        }

        return $due;
    }

    /**
     * Forget every scheduled event.
     */
    public static function flush()
    {
        static::$events = [];
    }
}
