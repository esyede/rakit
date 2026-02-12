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
        $input = array_merge(static::get(), static::query(), static::file());
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
        return '' !== trim((string) static::get($key));
    }

    /**
     * Get an item from the input data.
     * This method is used for all request methods (GET, POST, PUT, and DELETE).
     *
     * <code>
     *
     *      // Get item 'email' from input data
     *      $email = Input::get('email');
     *
     *      // Fallback to default value if item is not found
     *      $email = Input::get('name', 'Budi');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function get($key = null, $default = null)
    {
        $input = Request::foundation()->request->all();

        if (is_null($key)) {
            return array_merge($input, static::query());
        }

        $value = Arr::get($input, $key);
        return is_null($value) ? Arr::get(static::query(), $key, $default) : $value;
    }

    /**
     * Get one or all query parameters.
     *
     * <code>
     *
     *      // Get the 'email' query parameter
     *      $email = Input::query('email');
     *
     *      // Return default value if item is not found
     *      $email = Input::query('name', 'Budi');
     *
     * </code>
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
        static::$json = static::$json ?: json_decode(Request::foundation()->getContent());
        return static::$json = $as_object
            ? json_decode(json_encode(static::$json, JSON_FORCE_OBJECT), false)
            : json_decode(json_encode(static::$json), true);
    }

    /**
     * Get only specified items from the input data.
     *
     * <code>
     *
     *      // Get only email and password from input data
     *      $input = Input::only(['name', 'email']);
     *      $input = Input::only('email', 'password');
     *
     * </code>
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
     * <code>.
     *
     *      // Get all input data except name
     *      $inputs = Input::except('name');.
     *      $inputs = Input::except(['name', 'email']);
     *      $inputs = Input::except('name', 'email');
     *
     * </code>
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
        return '' !== trim((string) static::old($key));
    }

    /**
     * Get input data from the previous request.
     *
     * <code>
     *
     *      // Get item 'email' from old input
     *      $email = Input::old('email');
     *
     *      // Fallback to default value if item is not found
     *      $email = Input::old('name', 'Budi');
     *
     * </code>
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
            $value = static::get($value, null);

            if (!is_bool($value) && !is_array($value) && trim((string) $value) === '') {
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
        return !static::filled(is_array($key) ? $key : func_get_args());
    }

    /**
     * Get item from uploaded file data.
     *
     * <code>
     *
     *      // Get array information from form upload named 'avatar'
     *      $avatar = Input::file('avatar');
     *
     * </code>
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
        return mb_strlen((string) static::file($key . '.tmp_name', ''), '8bit') > 0;
    }

    /**
     * Move uploaded file to internal storage.
     *
     * <code>
     *
     *      // Move file 'avatar' to a new location in internal storage
     *      Input::upload('avatar', 'path/to/folder', 'avatar.jpg');
     *
     * </code>
     *
     * @param string $key
     * @param string $directory
     * @param string $name
     *
     * @return Foundation\Http\File
     */
    public static function upload($key, $directory, $name = null)
    {
        if (is_null(static::file($key))) {
            return false;
        }

        return Request::foundation()->files->get($key)->move($directory, $name);
    }

    /**
     * Flash the current input data to session.
     *
     * <code>
     *
     *      // Flash all input data to session
     *      Input::flash();
     *
     *      // Flash only specific input data to session
     *      Input::flash('only', ['name', 'email']);
     *
     *      // Flash all input data to session except for specific keys
     *      Input::flash('except', ['password', 'email']);
     *
     * </code>
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
