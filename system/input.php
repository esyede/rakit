<?php

namespace System;

defined('DS') or exit('No direct access.');

class Input
{
    /**
     * Key used to store old input in session.
     *
     * @var string
     */
    const OLD = 'rakit_old_input';

    /**
     * Contains the JSON payload of the request.
     *
     * @var object
     */
    public static $json;

    /**
     * Get all input data, including files.
     *
     * @return array
     */
    public static function all()
    {
        $input = array_merge(static::get(), static::file());
        unset($input[Request::SPOOFER]);

        return $input;
    }

    /**
     * Check if the given item exists in the input data.
     * If the input item is an empty string, it will return FALSE.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has($key)
    {
        return static::present(static::get($key));
    }

    /**
     * Check if the given input value counts as present.
     *
     * @param mixed $value
     *
     * @return bool
     */
    protected static function present($value)
    {
        if (is_null($value)) {
            return false;
        }

        if (is_array($value)) {
            return count($value) > 0;
        }

        if (is_bool($value) || is_object($value)) {
            return true;
        }

        return '' !== trim((string) $value);
    }

    /**
     * Get an item from the input data.
     * This method is used for all request methods (GET, POST, PUT, and DELETE).
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        $input = Request::foundation()->request->all();

        // The body wins over the query string, and every reader here goes
        // through this method so they cannot disagree about which one it is.
        if (is_null($key)) {
            return array_merge(static::query(), $input);
        }

        $value = Arr::get($input, $key);
        return is_null($value) ? Arr::get(static::query(), $key, $default) : $value;
    }

    /**
     * Get one or all query parameters.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function query($key = null, $default = null)
    {
        return Arr::get(Request::foundation()->query->all(), $key, $default);
    }

    /**
     * Get all JSON payload for the current request.
     *
     * @param bool $as_object
     *
     * @return mixed
     */
    public static function json($as_object = false)
    {
        if (is_null(static::$json)) {
            static::$json = (string) Request::foundation()->getContent();
        }

        return json_decode(is_string(static::$json) ? static::$json : json_encode(static::$json), ! $as_object);
    }

    /**
     * Get every input key.
     *
     * @return array
     */
    public static function keys()
    {
        return array_keys(static::all());
    }

    /**
     * Check whether the given key is absent from the input data.
     *
     * @param array|string $key
     *
     * @return bool
     */
    public static function missing($key)
    {
        $keys = is_array($key) ? $key : func_get_args();

        foreach ($keys as $item) {
            if (static::has($item)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check whether at least one of the given keys is filled.
     *
     * @param array|string $keys
     *
     * @return bool
     */
    public static function any_filled($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            if (static::filled($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get an input item as a string.
     *
     * @param string $key
     * @param string $default
     *
     * @return string
     */
    public static function string($key, $default = '')
    {
        $value = static::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * Get an input item as an integer.
     *
     * @param string $key
     * @param int    $default
     *
     * @return int
     */
    public static function integer($key, $default = 0)
    {
        $value = static::get($key, $default);

        return is_numeric($value) ? (int) $value : (int) $default;
    }

    /**
     * Get an input item as a float.
     *
     * @param string $key
     * @param float  $default
     *
     * @return float
     */
    public static function float($key, $default = 0.0)
    {
        $value = static::get($key, $default);

        return is_numeric($value) ? (float) $value : (float) $default;
    }

    /**
     * Get an input item as a boolean.
     * The strings '1', 'true', 'on' and 'yes' all count as TRUE,
     * everything else counts as FALSE.
     *
     * @param string $key
     * @param bool   $default
     *
     * @return bool
     */
    public static function boolean($key = null, $default = false)
    {
        if (is_null($key)) {
            return (bool) $default;
        }

        $value = static::get($key);

        if (is_null($value)) {
            return (bool) $default;
        }

        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'on', 'yes'], true);
    }

    /**
     * Get an input item as a Carbon instance.
     *
     * @param string $key
     * @param string $format
     *
     * @return \System\Carbon|null
     */
    public static function date($key, $format = null)
    {
        if (! static::filled($key)) {
            return null;
        }

        $value = static::get($key);

        try {
            return is_null($format) ? Carbon::parse($value) : Carbon::createFromFormat($format, $value);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get an input item as an array.
     *
     * @param string $key
     *
     * @return array
     */
    public static function arr($key = null)
    {
        $value = is_null($key) ? static::all() : static::get($key, []);

        return is_array($value) ? $value : (array) $value;
    }

    /**
     * Get the input data as a collection.
     *
     * @param string $key
     *
     * @return \System\Collection
     */
    public static function collect($key = null)
    {
        return new Collection(static::arr($key));
    }

    /**
     * Get only specified items from the input data.
     *
     * @param array $keys
     *
     * @return array
     */
    public static function only($keys)
    {
        return Arr::only(static::get(), is_array($keys) ? $keys : func_get_args());
    }

    /**
     * Get all items except specified items.
     *
     * @param array $keys
     *
     * @return array
     */
    public static function except($keys)
    {
        return Arr::except(static::get(), is_array($keys) ? $keys : func_get_args());
    }

    /**
     * Check if item is present in old input.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function had($key)
    {
        return static::present(static::old($key));
    }

    /**
     * Get input data from the previous request.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return string
     */
    public static function old($key = null, $default = null)
    {
        return Arr::get(Session::get(static::OLD, []), $key, $default);
    }

    /**
     * Check if one or more inputs are filled.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public static function filled($key)
    {
        $key = is_array($key) ? $key : func_get_args();

        foreach ($key as $value) {
            if (! static::present(static::get($value, null))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if one or more inputs are not filled.
     *
     * @param string|array $key
     *
     * @return bool
     */
    public static function unfilled($key)
    {
        return ! static::filled(is_array($key) ? $key : func_get_args());
    }

    /**
     * Get item from uploaded file data.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return array
     */
    public static function file($key = null, $default = null)
    {
        return Arr::get($_FILES, $key, $default);
    }

    /**
     * Check if one or more inputs have uploaded files.
     *
     * @param string $key
     *
     * @return bool
     */
    public static function has_file($key)
    {
        return mb_strlen((string) static::file($key.'.tmp_name', ''), '8bit') > 0;
    }

    /**
     * Move uploaded file to internal storage.
     *
     * @param string $key
     * @param string $directory
     * @param string $name
     *
     * @return string|false Path of the moved file, or FALSE when nothing was uploaded
     */
    public static function upload($key, $directory, $name = null)
    {
        $file = Request::foundation()->files->get($key);

        if (! ($file instanceof Foundation\Http\Upload)) {
            return false;
        }

        return $file->move($directory, $name);
    }

    /**
     * Flash the current input data to session.
     *
     * @param string $filter
     * @param array  $keys
     */
    public static function flash($filter = null, array $keys = [])
    {
        Session::flash(static::OLD, is_null($filter) ? static::get() : static::{$filter}($keys));
    }

    /**
     * Clear all old input from session.
     */
    public static function flush()
    {
        Session::flash(static::OLD, []);
    }

    /**
     * Merge new data into the current input data.
     *
     * @param array $inputs
     */
    public static function merge(array $inputs)
    {
        Request::foundation()->request->add($inputs);
    }

    /**
     * Replace input data.
     *
     * @param array $inputs
     */
    public static function replace(array $inputs)
    {
        Request::foundation()->request->replace($inputs);
    }

    /**
     * Clear all input data.
     */
    public static function clear()
    {
        Request::foundation()->request->replace([]);
    }
}
