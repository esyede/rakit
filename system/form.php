<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Form
{
    /**
     * Berisi nama-nama label yang telah dibuat.
     *
     * @var array
     */
    public static $labels = [];

    /**
     * Berisi macro-macro kustom yang didaftarkan user.
     *
     * @var array
     */
    public static $macros = [];

    /**
     * Daftarkan macro baru.
     *
     * @param string  $name
     * @param Closure $macro
     */
    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Buka form HTML.
     *
     * <code>
     *
     *      // Buka form POST ke URI saat ini
     *      echo Form::open();
     *
     *      // Buka form POST ke URI yang diberikan
     *      echo Form::open('user/profile');
     *
     *      // Buka form PUT ke URI yang diberikan
     *      echo Form::open('user/profile', 'put');
     *
     *      // Buka form dengan tambahan atribut HTML
     *      echo Form::open('user/profile', 'post', ['class' => 'profile']);
     *
     * </code>
     *
     * @param string $action
     * @param string $method
     * @param array  $attributes
     *
     * @return string
     */
    public static function open($action = null, $method = 'POST', $attributes = [])
    {
        $method = strtoupper($method);
        $attributes['method'] = static::method($method);
        $attributes['action'] = static::action($action);

        if (! array_key_exists('accept-charset', $attributes)) {
            $attributes['accept-charset'] = Config::get('application.encoding');
        }

        $append = '';

        if ('PUT' === $method || 'DELETE' === $method) {
            $append = static::hidden(Request::SPOOFER, $method);
        }

        return '<form'.HTML::attributes($attributes).'>'.$append;
    }

    /**
     * Tentukan method yang pas untuk sebuah form.
     *
     * @param string $method
     *
     * @return string
     */
    protected static function method($method)
    {
        return ('GET' === $method) ? 'GET' : 'POST';
    }

    /**
     * Tentuukan action yang pas untuk sebuah form
     * Jika tidak ditentukan, URI saat ini akan digunakan.
     *
     * @param string $action
     * @param bool   $https
     *
     * @return string
     */
    protected static function action($action)
    {
        $uri = is_null($action) ? URI::current() : $action;

        return HTML::entities(URL::to($uri));
    }

    /**
     * Buka form HTML untuk upload.
     *
     * @param string $action
     * @param string $method
     * @param array  $attributes
     *
     * @return string
     */
    public static function open_for_files($action = null, $method = 'POST', $attributes = [])
    {
        $attributes['enctype'] = 'multipart/form-data';

        return static::open($action, $method, $attributes);
    }

    /**
     * Tutup form HTML.
     *
     * @return string
     */
    public static function close()
    {
        return '</form>';
    }

    /**
     * Buat hidden element berisi CSRF token.
     *
     * @return string
     */
    public static function token()
    {
        return static::input('hidden', Session::TOKEN, Session::token());
    }

    /**
     * Buat label.
     *
     * <code>
     *
     *      // Buat label untuk 'email'
     *      echo Form::label('email', 'E-Mail Address');
     *
     * </code>
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function label($name, $value, $attributes = [])
    {
        static::$labels[] = $name;

        $attributes = HTML::attributes($attributes);
        $value = HTML::entities($value);

        return '<label for="'.$name.'"'.$attributes.'>'.$value.'</label>';
    }

    /**
     * Buat input box.
     *
     * <code>
     *
     *      // Buat input text bernma 'email'
     *      echo Form::input('text', 'email');
     *
     *      // Buat input text bernma 'email' dengan default value
     *      echo Form::input('text', 'email', 'paijo@gmail.com');
     *
     * </code>
     *
     * @param string $type
     * @param string $name
     * @param mixed  $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function input($type, $name, $value = null, $attributes = [])
    {
        $name = isset($attributes['name']) ? $attributes['name'] : $name;
        $id = static::id($name, $attributes);
        $attributes = array_merge($attributes, compact('type', 'name', 'value', 'id'));

        return '<input'.HTML::attributes($attributes).'>';
    }

    /**
     * Buat text input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function text($name, $value = null, $attributes = [])
    {
        return static::input('text', $name, $value, $attributes);
    }

    /**
     * Buat password input box.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return string
     */
    public static function password($name, $attributes = [])
    {
        return static::input('password', $name, null, $attributes);
    }

    /**
     * Buat hidden input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function hidden($name, $value = null, $attributes = [])
    {
        return static::input('hidden', $name, $value, $attributes);
    }

    /**
     * Buat search input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function search($name, $value = null, $attributes = [])
    {
        return static::input('search', $name, $value, $attributes);
    }

    /**
     * Buat email input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function email($name, $value = null, $attributes = [])
    {
        return static::input('email', $name, $value, $attributes);
    }

    /**
     * Buat telephone input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function telephone($name, $value = null, $attributes = [])
    {
        return static::input('tel', $name, $value, $attributes);
    }

    /**
     * Buat URL input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function url($name, $value = null, $attributes = [])
    {
        return static::input('url', $name, $value, $attributes);
    }

    /**
     * Buat number input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function number($name, $value = null, $attributes = [])
    {
        return static::input('number', $name, $value, $attributes);
    }

    /**
     * Buat date input box.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function date($name, $value = null, $attributes = [])
    {
        return static::input('date', $name, $value, $attributes);
    }

    /**
     * Buat file input box.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return string
     */
    public static function file($name, $attributes = [])
    {
        return static::input('file', $name, null, $attributes);
    }

    /**
     * Buat texarea.
     *
     * @param string $name
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function textarea($name, $value = '', $attributes = [])
    {
        $attributes['name'] = $name;
        $attributes['id'] = static::id($name, $attributes);

        if (! isset($attributes['rows'])) {
            $attributes['rows'] = 10;
        }

        if (! isset($attributes['cols'])) {
            $attributes['cols'] = 50;
        }

        return '<textarea'.HTML::attributes($attributes).'>'.HTML::entities($value).'</textarea>';
    }

    /**
     * Buat select box.
     *
     * <code>
     *
     *      // Buat select box dengan options
     *      echo Form::select('sizes', ['S' => 'Small', 'L' => 'Large']);
     *
     *      // Buat select box dengan options dan default selected valuenya
     *      echo Form::select('sizes', ['S' => 'Small', 'L' => 'Large'], 'L');
     *
     * </code>
     *
     * @param string $name
     * @param array  $options
     * @param string $selected
     * @param array  $attributes
     *
     * @return string
     */
    public static function select($name, $options = [], $selected = null, $attributes = [])
    {
        $attributes['id'] = static::id($name, $attributes);
        $attributes['name'] = $name;

        $html = [];

        foreach ($options as $value => $display) {
            if (is_array($display)) {
                $html[] = static::optgroup($display, $value, $selected);
            } else {
                $html[] = static::option($value, $display, $selected);
            }
        }

        return '<select'.HTML::attributes($attributes).'>'.implode('', $html).'</select>';
    }

    /**
     * Buat select optgroup.
     *
     * @param array  $options
     * @param string $label
     * @param string $selected
     *
     * @return string
     */
    protected static function optgroup($options, $label, $selected)
    {
        $html = [];

        foreach ($options as $value => $display) {
            $html[] = static::option($value, $display, $selected);
        }

        return '<optgroup label="'.HTML::entities($label).'">'.implode('', $html).'</optgroup>';
    }

    /**
     * Buat select option.
     *
     * @param string $value
     * @param string $display
     * @param string $selected
     *
     * @return string
     */
    protected static function option($value, $display, $selected)
    {
        if (is_array($selected)) {
            $selected = in_array($value, $selected) ? 'selected' : null;
        } else {
            $selected = ((string) $value === (string) $selected) ? 'selected' : null;
        }

        $attributes = ['value' => HTML::entities($value), 'selected' => $selected];

        return '<option'.HTML::attributes($attributes).'>'.HTML::entities($display).'</option>';
    }

    /**
     * Buat checkbox.
     *
     * <code>
     *
     *      // Buat checkbox
     *      echo Form::checkbox('terms', 'yes');
     *
     *      // Buat checkbox yang secara default sudah tercentang
     *      echo Form::checkbox('terms', 'yes', true);
     *
     * </code>
     *
     * @param string $name
     * @param string $value
     * @param bool   $checked
     * @param array  $attributes
     *
     * @return string
     */
    public static function checkbox($name, $value = 1, $checked = false, $attributes = [])
    {
        return static::checkable('checkbox', $name, $value, $checked, $attributes);
    }

    /**
     * Buat radio button.
     *
     * <code>
     *
     *      // Buat radio button
     *      echo Form::radio('drinks', 'Milk');
     *
     *      // Buat radio button yang secara default sudah terpilih
     *      echo Form::radio('drinks', 'Milk', true);
     *
     * </code>
     *
     * @param string $name
     * @param string $value
     * @param bool   $checked
     * @param array  $attributes
     *
     * @return string
     */
    public static function radio($name, $value = null, $checked = false, $attributes = [])
    {
        if (is_null($value)) {
            $value = $name;
        }

        return static::checkable('radio', $name, $value, $checked, $attributes);
    }

    /**
     * Buat input box yang bisa dicentang.
     *
     * @param string $type
     * @param string $name
     * @param string $value
     * @param bool   $checked
     * @param array  $attributes
     *
     * @return string
     */
    protected static function checkable($type, $name, $value, $checked, $attributes)
    {
        if ($checked) {
            $attributes['checked'] = 'checked';
        }

        $attributes['id'] = static::id($name, $attributes);

        return static::input($type, $name, $value, $attributes);
    }

    /**
     * Buat submit button.
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function submit($value = null, $attributes = [])
    {
        return static::input('submit', null, $value, $attributes);
    }

    /**
     * Buat reset button.
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function reset($value = null, $attributes = [])
    {
        return static::input('reset', null, $value, $attributes);
    }

    /**
     * Buat image input box.
     *
     * <code>
     *
     *      // Buat image input box
     *      echo Form::image('img/submit.png');
     *
     * </code>
     *
     * @param string $url
     * @param string $name
     * @param array  $attributes
     *
     * @return string
     */
    public static function image($url, $name = null, $attributes = [])
    {
        $attributes['src'] = URL::to_asset($url);

        return static::input('image', $name, null, $attributes);
    }

    /**
     * Buat button.
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function button($value = null, $attributes = [])
    {
        return '<button'.HTML::attributes($attributes).'>'.HTML::entities($value).'</button>';
    }

    /**
     * Tentukan atribut ID untuk elemen HTML.
     *
     * @param string $name
     * @param array  $attributes
     *
     * @return mixed
     */
    protected static function id($name, $attributes)
    {
        if (array_key_exists('id', $attributes)) {
            return $attributes['id'];
        }

        if (in_array($name, static::$labels)) {
            return $name;
        }
    }

    /**
     * Tangani pemanggilan macro kustom secara dinamis.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        if (isset(static::$macros[$method])) {
            return call_user_func_array(static::$macros[$method], $parameters);
        }

        throw new \Exception(sprintf('Method does not exists: %s', $method));
    }
}
