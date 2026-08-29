<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Context
{
    /** @var \WeakMap|\SplObjectStorage|null */
    private static $map;

    private static function map()
    {
        if (null === self::$map) {
            self::$map = class_exists('\WeakMap') ? new \WeakMap() : new \SplObjectStorage();
        }

        return self::$map;
    }

    /**
     * Clear the context map.
     * @param object $e
     */
    private static function entryFor($e)
    {
        $map = self::map();
        return isset($map[$e]) ? $map[$e] : [];
    }

    /**
     * @param object $e
     * @param array $data
     */
    private static function setEntry($e, array $data)
    {
        $map = self::map();
        $map[$e] = $data;
    }

    /**
     * Set the context for an exception.
     *
     * @param object $e
     * @param mixed  $context
     */
    public static function setContext($e, $context)
    {
        $entry = self::entryFor($e);
        $entry['context'] = $context;
        self::setEntry($e, $entry);
    }

    /**
     * Get the context for an exception.
     *
     * @param object $e
     *
     * @return mixed
     */
    public static function getContext($e)
    {
        $entry = self::entryFor($e);
        return (isset($entry['context']) && ! empty($entry['context'])) ? $entry['context'] : null;
    }

    /**
     * Check if an exception has context.
     *
     * @param object $e
     *
     * @return bool
     */
    public static function hasContext($e)
    {
        $entry = self::entryFor($e);
        return array_key_exists('context', $entry) && null !== $entry['context'];
    }

    /**
     * Set whether an exception is skippable.
     *
     * @param object $e
     * @param bool   $value
     */
    public static function setSkippable($e, $value)
    {
        $entry = self::entryFor($e);
        $entry['skippable'] = (bool) $value;
        self::setEntry($e, $entry);
    }

    /**
     * Check if an exception is skippable.
     *
     * @param object $e
     *
     * @return bool
     */
    public static function isSkippable($e)
    {
        $entry = self::entryFor($e);
        return isset($entry['skippable']) && $entry['skippable'];
    }

    /**
     * Set the oops action for an exception.
     *
     * @param object $e
     * @param array  $action
     */
    public static function setOopsAction($e, array $action)
    {
        $entry = self::entryFor($e);
        $entry['oopsAction'] = $action;
        self::setEntry($e, $entry);
    }

    /**
     * Get the oops action for an exception.
     *
     * @param object $e
     *
     * @return array|null
     */
    public static function getOopsAction($e)
    {
        $entry = self::entryFor($e);
        return (isset($entry['oopsAction']) && ! empty($entry['oopsAction'])) ? $entry['oopsAction'] : null;
    }

    /**
     * Check if an exception has an oops action.
     *
     * @param object $e
     *
     * @return bool
     */
    public static function hasOopsAction($e)
    {
        $entry = self::entryFor($e);
        return array_key_exists('oopsAction', $entry) && ! empty($entry['oopsAction']);
    }
}
