<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Context
{
    /** @var \WeakMap|\SplObjectStorage|null */
    private static $map = null;

    private static function map()
    {
        if (null === self::$map) {
            self::$map = class_exists('\WeakMap') ? new \WeakMap() : new \SplObjectStorage();
        }

        return self::$map;
    }

    private static function entryFor($e)
    {
        $map = self::map();
        return isset($map[$e]) ? $map[$e] : [];
    }

    private static function setEntry($e, array $data)
    {
        $map = self::map();
        $map[$e] = $data;
    }

    public static function setContext($e, $context)
    {
        $entry = self::entryFor($e);
        $entry['context'] = $context;
        self::setEntry($e, $entry);
    }

    public static function getContext($e)
    {
        $entry = self::entryFor($e);
        return (isset($entry['context']) && !empty($entry['context'])) ? $entry['context'] : null;
    }

    public static function hasContext($e)
    {
        $entry = self::entryFor($e);
        return array_key_exists('context', $entry) && null !== $entry['context'];
    }

    public static function setSkippable($e, $value)
    {
        $entry = self::entryFor($e);
        $entry['skippable'] = (bool) $value;
        self::setEntry($e, $entry);
    }

    public static function isSkippable($e)
    {
        $entry = self::entryFor($e);
        return isset($entry['skippable']) && $entry['skippable'];
    }

    public static function setOopsAction($e, array $action)
    {
        $entry = self::entryFor($e);
        $entry['oopsAction'] = $action;
        self::setEntry($e, $entry);
    }

    public static function getOopsAction($e)
    {
        $entry = self::entryFor($e);
        return (isset($entry['oopsAction']) && !empty($entry['oopsAction'])) ? $entry['oopsAction'] : null;
    }

    public static function hasOopsAction($e): bool
    {
        $entry = self::entryFor($e);
        return array_key_exists('oopsAction', $entry) && !empty($entry['oopsAction']);
    }
}
