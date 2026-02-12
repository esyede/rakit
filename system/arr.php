<?php

namespace System;

defined('DS') or exit('No direct access.');

class Arr
{
    /**
     * Check if the given value is accessible.
     *
     * @param mixed $value
     *
     * @return bool
     */
    public static function accessible($value)
    {
        return is_array($value) || ($value instanceof \ArrayAccess);
    }

    /**
     * Add an element to an array using dot-notation (if it doesn't exist).
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    public static function add($array, $key, $value)
    {
        if (is_null(static::get($array, $key))) {
            static::set($array, $key, $value);
        }

        return $array;
    }

    /**
     * Collapse a nested array into a single array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function collapse($array)
    {
        $results = [];

        foreach ($array as $values) {
            if (is_array($values)) {
                $results[] = $values;
            }
        }

        return call_user_func_array('array_merge', $results);
    }

    /**
     * Cross join multiple arrays.
     *
     * @param array ...$arrays
     *
     * @return array
     */
    public static function cross_join(/* ...$arrays */)
    {
        $arrays = func_get_args();
        $results = [[]];

        foreach ($arrays as $index => $array) {
            $append = [];

            foreach ($results as $product) {
                foreach ($array as $item) {
                    $product[$index] = $item;
                    $append[] = $product;
                }
            }

            $results = $append;
        }

        return $results;
    }

    /**
     * Divide array into two arrays.
     * One based on key and one based on value.
     *
     * @param array $array
     *
     * @return array
     */
    public static function divide($array)
    {
        return [array_keys($array), array_values($array)];
    }

    /**
     * Flatten a multi-dimensional associative array with dots.
     *
     * @param array  $array
     * @param string $prepend
     *
     * @return array
     */
    public static function dot($array, $prepend = '')
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value) && !empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend . $key . '.'));
            } else {
                $results[$prepend . $key] = $value;
            }
        }

        return $results;
    }

    /**
     * Change array dot notation to array.
     *
     * @param array $array
     *
     * @return array
     */
    public static function undot($array)
    {
        $results = [];

        foreach ($array as $key => $value) {
            static::set($results, $key, $value);
        }

        return $results;
    }

    /**
     * Get all array except key that specified.
     *
     * @param array        $array
     * @param array|string $keys
     *
     * @return array
     */
    public static function except($array, $keys)
    {
        static::forget($array, $keys);
        return $array;
    }

    /**
     * Check if key that specified exists in array.
     *
     * @param \ArrayAccess|array $array
     * @param string|int         $key
     *
     * @return bool
     */
    public static function exists($array, $key)
    {
        return ($array instanceof \ArrayAccess)
            ? $array->offsetExists($key)
            : array_key_exists($key, $array);
    }

    /**
     * Return the first element in the array that passes the given truth test.
     *
     * @param array         $array
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public static function first($array, $callback = null, $default = null)
    {
        if (is_null($callback)) {
            if (empty($array)) {
                return value($default);
            }

            foreach ($array as $item) {
                return $item;
            }
        }

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Return the last element in the array that passes the given truth test.
     *
     * @param array         $array
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public static function last($array, $callback = null, $default = null)
    {
        return is_null($callback)
            ? (empty($array) ? value($default) : end($array))
            : static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Flatten multi-dimensional array into one level.
     *
     * @param array $array
     * @param int   $depth
     *
     * @return array
     */
    public static function flatten($array, $depth = PHP_INT_MAX)
    {
        $depth = ($depth < 1) ? 1 : (int) $depth;
        $result = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                if (1 === $depth) {
                    $result = array_merge($result, $item);
                    continue;
                }

                $result = array_merge($result, static::flatten($item, $depth - 1));
                continue;
            }

            $result[] = $item;
        }

        return $result;
    }

    /**
     * Remove one or more items from array using "dot" notation.
     *
     * @param array        $array
     * @param array|string $keys
     */
    public static function forget(&$array, $keys)
    {
        $original = &$array;
        $keys = (array) $keys;

        if (0 === count($keys)) {
            return;
        }

        foreach ($keys as $key) {
            if (static::exists($array, $key)) {
                unset($array[$key]);
                continue;
            }

            $parts = explode('.', $key);
            $array = &$original;

            while (count($parts) > 1) {
                $part = array_shift($parts);

                if (isset($array[$part]) && is_array($array[$part])) {
                    $array = &$array[$part];
                } else {
                    continue 2;
                }
            }

            unset($array[array_shift($parts)]);
        }
    }

    /**
     * Get item from array using "dot" notation.
     *
     * @param \ArrayAccess|array $array
     * @param string|int         $key
     * @param mixed              $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (!static::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (static::exists($array, $key)) {
            return $array[$key];
        }

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (!static::accessible($array) || !static::exists($array, $segment)) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Check if one or more items exist in array using "dot" notation.
     *
     * @param \ArrayAccess|array $array
     * @param string|array       $keys
     *
     * @return bool
     */
    public static function has($array, $key)
    {
        if (!$array || is_null($key)) {
            return false;
        }

        if (static::exists($array, $key)) {
            return true;
        }

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (!static::accessible($array) || !static::exists($array, $segment)) {
                return false;
            }

            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Check if one or more items exist in array using "dot" notation.
     *
     * @param \ArrayAccess|array $array
     * @param string|array       $keys
     *
     * @return bool
     */
    public static function has_any($array, $keys)
    {
        if (is_null($keys)) {
            return false;
        }

        $keys = (array) $keys;

        if (!$array || $keys === []) {
            return false;
        }

        foreach ($keys as $key) {
            if (static::has($array, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if array is associative.
     * Associative arrays have keys that are not sequential integers.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function associative($array)
    {
        if (!is_array($array)) {
            return false;
        }

        $array = array_keys($array);
        return (array_keys($array) !== $array);
    }

    /**
     * Check if array is sequential.
     * Sequential arrays have keys that are sequential integers.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function sequential($array)
    {
        if (!is_array($array)) {
            return false;
        }

        if ([] === $array || $array === array_values($array)) {
            return true;
        }

        $next = -1;

        foreach ($array as $key => $value) {
            if ($key !== ++$next) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get a subset of items from the given array.
     *
     * @param array        $array
     * @param array|string $keys
     *
     * @return array
     */
    public static function only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array) $keys));
    }

    /**
     * Get array values from the given array.
     *
     * @param array             $array
     * @param string|array      $value
     * @param string|array|null $key
     *
     * @return array
     */
    public static function pluck($array, $value, $key = null)
    {
        $value = is_string($value) ? explode('.', $value) : $value;
        $key = (is_null($key) || is_array($key)) ? $key : explode('.', $key);

        $results = [];

        foreach ($array as $item) {
            $item_value = data_get($item, $value);

            if (is_null($key)) {
                $results[] = $item_value;
            } else {
                $item_key = data_get($item, $key);
                $results[$item_key] = $item_value;
            }
        }

        return $results;
    }

    /**
     * Prepend an item to the beginning of an array.
     *
     * @param array $array
     * @param mixed $value
     * @param mixed $key
     *
     * @return array
     */
    public static function prepend($array, $value, $key = null)
    {
        if (is_null($key)) {
            array_unshift($array, $value);
        } else {
            $array = [$key => $value] + $array;
        }

        return $array;
    }

    /**
     * Pull an item from the array, and remove it.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function pull(array &$array, $key, $default = null)
    {
        $value = static::get($array, $key, $default);
        static::forget($array, $key);

        return $value;
    }

    /**
     * Pick a random item from the array.
     *
     * @param array    $array
     * @param int|null $number
     *
     * @return mixed
     */
    public static function random($array, $number = null)
    {
        $requested = is_null($number) ? 1 : $number;
        $available = count($array);

        if ($requested > $available) {
            throw new \InvalidArgumentException(sprintf(
                'You requested %s items, but there are only %s items available.',
                $requested,
                $available
            ));
        }

        if (is_null($number)) {
            return $array[array_rand($array)];
        }

        if (0 === (int) $number) {
            return [];
        }

        $keys = (array) array_rand($array, $number);
        $results = [];

        foreach ($keys as $key) {
            $results[] = $array[$key];
        }

        return $results;
    }

    /**
     * Set array value using dot notation.
     * If no key is given for this method, the entire array will be replaced.
     *
     * @param array  $array
     * @param string $key
     * @param mixed  $value
     *
     * @return array
     */
    public static function set(&$array, $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Shuffle array then return the result.
     *
     * @param array    $array
     * @param int|null $seed
     *
     * @return array
     */
    public static function shuffle($array, $seed = null)
    {
        if (is_null($seed)) {
            shuffle($array);
        } else {
            mt_srand($seed);
            shuffle($array);
            mt_srand();
        }

        return $array;
    }

    /**
     * Sort array using callback or dot notatio.
     *
     * @param array         $array
     * @param callable|null $callback
     *
     * @return array
     */
    public static function sort($array, $callback = null)
    {
        $results = [];

        if (is_string($callback)) {
            $callback = function ($item) use ($callback) {
                return data_get($item, $callback);
            };
        }

        foreach ($array as $key => $value) {
            $results[$key] = call_user_func($callback, $value);
        }

        asort($results, SORT_REGULAR);

        $keys = array_keys($results);

        foreach ($keys as $key) {
            $results[$key] = $array[$key];
        }

        return $results;
    }

    /**
     * Recursively sort array using callback or dot notation.
     *
     * @param array $array
     *
     * @return array
     */
    public static function recsort($array)
    {
        foreach ($array as &$value) {
            if (is_array($value)) {
                $value = static::recsort($value);
            }
        }

        if (static::associative($array)) {
            ksort($array);
        } else {
            sort($array);
        }

        return $array;
    }

    /**
     * Filter array using callback.
     *
     * @param array    $array
     * @param callable $callback
     *
     * @return array
     */
    public static function where($array, callable $callback)
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (call_user_func($callback, $key, $value)) {
                $results[$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Wrap value into array.
     *
     * @param mixed $value
     *
     * @return array
     */
    public static function wrap($value)
    {
        return is_null($value) ? [] : (is_array($value) ? $value : [$value]);
    }
}
