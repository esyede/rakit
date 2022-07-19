<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Arr
{
    /**
     * Periksa apakah value yang diberikan merupakan array dan dapat diakses.
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
     * Tambahkan sebuah elemen ke array menggunakan dot-notation (jika belum ada).
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
     * Collapse sebuah array bersarang menjadi sebuah array.
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
     * Membagi array menjadi dua array.
     * Satu berdasarkan key dan satu lagi berdasarkan value.
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
     * Ratakan array asosiatif multi-dimensi dengan dot.
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
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, static::dot($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }

        return $results;
    }

    /**
     * Ubah array notasi "dot" menjadi array biasa.
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
     * Ambil semua array kecuali key yang ditentukan.
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
     * Cek apakah key yang diberikan ada di array.
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
     * Mereturn elemen pertama dalam array yang melewati tes kebenaran yang diberikan.
     *
     * @param array         $array
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public static function first($array, callable $callback = null, $default = null)
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
     * Mereturn elemen terakhir dalam array yang melewati tes kebenaran yang diberikan.
     *
     * @param array         $array
     * @param callable|null $callback
     * @param mixed         $default
     *
     * @return mixed
     */
    public static function last($array, callable $callback = null, $default = null)
    {
        return is_null($callback)
            ? (empty($array) ? value($default) : end($array))
            : static::first(array_reverse($array, true), $callback, $default);
    }

    /**
     * Ratakan array multi-dimensi menjadi satu level.
     *
     * @param array $array
     * @param int   $depth
     *
     * @return array
     */
    public static function flatten($array, $depth = INF)
    {
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
     * Hapus satu atau beberapa item array menggunakan notasi "dot".
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
     * Ambil item dari array menggunakan notasi "dot".
     *
     * @param \ArrayAccess|array $array
     * @param string|int         $key
     * @param mixed              $default
     *
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (! static::accessible($array)) {
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
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }

    /**
     * Cek apakah ada satu atau beberapa item dalam array menggunakan notasi "dot".
     *
     * @param \ArrayAccess|array $array
     * @param string|array       $keys
     *
     * @return bool
     */
    public static function has($array, $key)
    {
        if (! $array || is_null($key)) {
            return false;
        }

        if (static::exists($array, $key)) {
            return true;
        }

        $segments = explode('.', $key);

        foreach ($segments as $segment) {
            if (static::accessible($array) && static::exists($array, $segment)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Cek apakah sebuah array merupakan array asosiatif atau bukan.
     * Sebuah array dianggap asosiatif apabila ia tidak mengandung
     * key numerik urut yang dimulai dari nol.
     *
     * @param array $array
     *
     * @return bool
     */
    public static function associative($array)
    {
        if (! is_array($array)) {
            return false;
        }

        $keys = array_keys($array);
        return (array_keys($keys) !== $keys);
    }

    /**
     * Ambil subset item dari array yang diberikan.
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
     * Ambil array nilai dari array.
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
     * Taruh item ke awal array.
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
     * Ambil sebuah value dari array, dan hapus key-nya.
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
     * Ambil satu atau beberapa nilai acak dari array.
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
                $requested, $available
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
     * Set item array ke value yang diberikan menggunakan notasi "dot"
     * Jika tidak ada key yang diberikan untuk method ini, seluruh array akan di-replace.
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

            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * Acak array yang diberikan dan kembalikan hasilnya.
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
     * Urutkan array menggunakan callback atau menggunakan notasi "dot".
     *
     * @param array                $array
     * @param callable|string|null $callback
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
     * Urutkan array berdasarkan key dan value secara rekursif.
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
     * Saring array menggunakan callback.
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
     * Bungkus value kedalam array.
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
