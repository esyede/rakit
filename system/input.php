<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Input
{
    /**
     * Key yang digunakan untuk menyimpan old input di session.
     *
     * @var string
     */
    const OLD = 'rakit_old_input';

    /**
     * Berisi payload JSON untuk aplikasi.
     *
     * @var object
     */
    public static $json;

    /**
     * Ambil semua data inputan, termasuk file.
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
     * Cek apakah item yang diberikan ada di input data.
     * Jika item inputannya adalah string kosong, ia akan mereturn FALSE.
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
     * Ambil item dari data inputan.
     * Method ini digunakan untuk semua request method (GET, POST, PUT, dan DELETE).
     *
     * <code>
     *
     *      // Mengambil item 'email' dari data inputan
     *      $email = Input::get('email');
     *
     *      // Return default value jika item tidak ditemukan
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
     * Ambil item dari query string.
     *
     * <code>
     *
     *      // Ambi item 'email' dari query string
     *      $email = Input::query('email');
     *
     *      // Return default value jika item tidak ditemukan
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
     * Ambil payload JSON untuk request saat ini.
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
     * Ambil sebagian item dari data input.
     *
     * <code>
     *
     *      // Ambil hanya email dari data inputan
     *      $value = Input::only('email');
     *
     *      // Ambil hanya name dan email dari data inputan
     *      $input = Input::only(['name', 'email']);
     *
     *      $input = Input::only('name', 'email');
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
     * Ambil semua data input kecuali item-item yang diberikan.
     *
     * <code>.
     *
     *      // Ambil semua data inputan kecuali name
     *      $input = Input::except('name');.
     *
     *      // Ambil semua data inputan kecuali name dan email
     *      $input = Input::except(['name', 'email']);
     *
     *      $input = Input::except('name', 'email');
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
     * Cek apakah item yang diminta ada di old input atau tidak.
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
     * Ambil data input dari request sebelumnya.
     *
     * <code>
     *
     *      // Ambil item 'email' dari old input
     *      $email = Input::old('email');
     *
     *      // Return default value jika item tidak ditemukan
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
     * Cek apakah satu atau beberapa input diisi seluruhnya.
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
     * Cek apakah satu atau beberapa input tidak diisi.
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
     * Ambil item dari data file upload.
     *
     * <code>
     *
     *      // Ambilo array informasi dari form upload bernama 'foto'
     *      $foto = Input::file('foto');
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
     * Cek apakah data yang diupload mengandung file atau tidak.
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
     * Pindahkan file terupload ke internal storage.
     * Method ini hanyalah wrapper fungsi move_uploaded_file().
     *
     * <code>
     *
     *      // Pindahkan file 'foto' ke lokasi baru di internal storage
     *      Input::upload('foto', 'path/to/folder', 'nama_file.jpg');
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
     * Flash data inputan saat ini ke session.
     *
     * <code>
     *
     *      // Flash semua data inputan saat ini ke session
     *      Input::flash();
     *
     *      // Flash hanya bebrapa data inputan saat ini ke session
     *      Input::flash('only', ['name', 'email']);
     *
     *      // Flash semua data inputan saat ini ke session kecuali yang disebutkan
     *      Input::flash('except', ['password', 'nomor_telepon']);
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
     * Bersihkan seluruh old input dari session.
     */
    public static function flush()
    {
        Session::flash(static::OLD, []);
    }

    /**
     * Merge data baru ke array data inputan saat ini.
     *
     * @param array $inputs
     */
    public static function merge(array $inputs)
    {
        Request::foundation()->request->add($inputs);
    }

    /**
     * Replace data inputan saat ini.
     *
     * @param array $inputs
     */
    public static function replace(array $inputs)
    {
        Request::foundation()->request->replace($inputs);
    }

    /**
     * Bersihkan/buang data inputan saat ini.
     */
    public static function clear()
    {
        Request::foundation()->request->replace([]);
    }
}
