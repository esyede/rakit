<?php

namespace System;

defined('DS') or exit('No direct script access.');

class HTML
{
    /**
     * Berisi macro kustom yang didaftarkan user.
     *
     * @var array
     */
    public static $macros = [];

    /**
     * Cache app encoding secara lokal agar tidak perlu
     * memanggil ulang Config::get() untuk menghemat memori.
     *
     * @var string
     */
    public static $encoding = null;

    /**
     * Daftarkan macro kustom baru.
     *
     * @param string   $name
     * @param \Closure $macro
     */
    public static function macro($name, \Closure $macro)
    {
        static::$macros[$name] = $macro;
    }

    /**
     * Ubah karakter HTML ke entities.
     *
     * @param string $value
     *
     * @return string
     */
    public static function entities($value)
    {
        return htmlentities($value, ENT_QUOTES, static::encoding(), false);
    }

    /**
     * Ubah HTML entities ke karakter HTML.
     *
     * @param string $value
     *
     * @return string
     */
    public static function decode($value)
    {
        return html_entity_decode($value, ENT_QUOTES, static::encoding());
    }

    /**
     * Ubah special characters.
     *
     * @param string $value
     *
     * @return string
     */
    public static function specialchars($value)
    {
        return htmlspecialchars($value, ENT_QUOTES, static::encoding(), false);
    }

    /**
     * Buat link ke file JavaScript.
     *
     * <code>
     *
     *      // Buat link ke file JavaScript
     *      echo HTML::script('js/jquery.js');
     *
     *      // Buat link ke file JavaScript dengan atribut tambahan
     *      echo HTML::script('js/jquery.js', ['defer']);
     *
     * </code>
     *
     * @param string $url
     * @param array  $attributes
     *
     * @return string
     */
    public static function script($url, $attributes = [])
    {
        $url = URL::to_asset($url);
        $attributes = static::attributes($attributes);

        return '<script src="'.$url.'"'.$attributes.'></script>'.PHP_EOL;
    }

    /**
     * Buat link ke file CSS.
     * Jika media type tidak disebutkan, "all" akan digunakan.
     *
     * <code>
     *
     *      // Buat link ke file CSS
     *      echo HTML::style('css/common.css');
     *
     *      // Buat link ke file CSS dengan atribut tambahan
     *      echo HTML::style('css/common.css', ['media' => 'print']);
     *
     * </code>
     *
     * @param string $url
     * @param array  $attributes
     *
     * @return string
     */
    public static function style($url, $attributes = [])
    {
        $defaults = ['media' => 'all', 'type' => 'text/css', 'rel' => 'stylesheet'];
        $attributes = $attributes + $defaults;
        $attributes = static::attributes($attributes);
        $url = URL::to_asset($url);

        return '<link href="'.$url.'"'.$attributes.'>'.PHP_EOL;
    }

    /**
     * Buat HTML span.
     *
     * @param string $value
     * @param array  $attributes
     *
     * @return string
     */
    public static function span($value, $attributes = [])
    {
        $attributes = static::attributes($attributes);
        $value = static::entities($value);

        return '<span'.$attributes.'>'.$value.'</span>';
    }

    /**
     * Buat HTML link.
     *
     * <code>
     *
     *      // Buat link ke lokasi didalam aplikasi
     *      echo HTML::link('user/profile', 'User Profile');
     *
     *      // Buat link ke lokasi diluar aplikasi
     *      echo HTML::link('http://situsku.com', 'Situsku');
     *
     * </code>
     *
     * @param string $url
     * @param string $title
     * @param array  $attributes
     *
     * @return string
     */
    public static function link($url, $title = null, $attributes = [])
    {
        $url = URL::to($url);
        $title = is_null($title) ? $url : $title;
        $title = static::entities($title);
        $attributes = static::attributes($attributes);

        return '<a href="'.$url.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Buat HTML link ke sebuah aset.
     * Halaman index aplikasi tidak akan disertakan ke link aset.
     *
     * @param string $url
     * @param string $title
     * @param array  $attributes
     *
     * @return string
     */
    public static function link_to_asset($url, $title = null, $attributes = [])
    {
        $url = URL::to_asset($url);
        $title = is_null($title) ? $url : $title;
        $title = static::entities($title);
        $attributes = static::attributes($attributes);

        return '<a href="'.$url.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Buat HTML link ke sebuah rute.
     * Boleh juga ditambahkan array berisi parameter untuk rute yang bersangkutan.
     *
     * <code>
     *
     *      // Buat link ke named route bernama 'profile'
     *      echo HTML::link_to_route('profile', 'Profil User');
     *
     *      // Buat link ke named route bernama 'profile' dengan parameter tambahan
     *      echo HTML::link_to_route('profile', 'Profil User', ['budi']);
     *
     * </code>
     *
     * @param string $name
     * @param string $title
     * @param array  $parameters
     * @param array  $attributes
     *
     * @return string
     */
    public static function link_to_route($name, $title = null, $parameters = [], $attributes = [])
    {
        return static::link(URL::to_route($name, $parameters), $title, $attributes);
    }

    /**
     * Buat link ke action milik controller.
     * Boleh juga ditambahkan array berisi parameter untuk action yang bersangkutan.
     *
     * <code>
     *
     *      // Buat link ke action 'home@index'
     *      echo HTML::link_to_action('home@index', 'Home');
     *
     *      // Buat link ke action 'user@profile' dengan parameter tambahan
     *      echo HTML::link_to_action('user@profile', 'Profile', ['budi']);
     *
     * </code>
     *
     * @param string $action
     * @param string $title
     * @param array  $parameters
     * @param array  $attributes
     *
     * @return string
     */
    public static function link_to_action($action, $title = null, $parameters = [], $attributes = [])
    {
        return static::link(URL::to_action($action, $parameters), $title, $attributes);
    }

    /**
     * Buat link ke bahasa.
     *
     * @param string $language
     * @param string $title
     * @param array  $attributes
     *
     * @return string
     */
    public static function link_to_language($language, $title = null, $attributes = [])
    {
        return static::link(URL::to_language($language), $title, $attributes);
    }

    /**
     * Buat link mailto:xxx.
     * Alamat email akan di-obfuscate agar terhindar dari bot spam.
     *
     * @param string $email
     * @param string $title
     * @param array  $attributes
     *
     * @return string
     */
    public static function mailto($email, $title = null, $attributes = [])
    {
        $email = static::email($email);
        $email = '&#109;&#097;&#105;&#108;&#116;&#111;&#058;'.$email;
        $title = is_null($title) ? $email : $title;
        $title = static::entities($title);
        $attributes = static::attributes($attributes);

        return '<a href="'.$email.'"'.$attributes.'>'.$title.'</a>';
    }

    /**
     * Obfuscate alamat email agar terhindar dari bot spam.
     *
     * @param string $email
     *
     * @return string
     */
    public static function email($email)
    {
        return str_replace('@', '&#64;', static::obfuscate($email));
    }

    /**
     * Buat elemen image source.
     *
     * @param string $url
     * @param string $alt
     * @param array  $attributes
     *
     * @return string
     */
    public static function image($url, $alt = '', $attributes = [])
    {
        $attributes['alt'] = $alt;
        $attributes = static::attributes($attributes);
        $url = URL::to_asset($url);

        return '<img src="'.$url.'"'.$attributes.'>';
    }

    /**
     * Buat ordered list.
     *
     * @param array $list
     * @param array $attributes
     *
     * @return string
     */
    public static function ol($list, $attributes = [])
    {
        return static::listing('ol', $list, $attributes);
    }

    /**
     * Buat un-ordered list.
     *
     * @param array $list
     * @param array $attributes
     *
     * @return string
     */
    public static function ul($list, $attributes = [])
    {
        return static::listing('ul', $list, $attributes);
    }

    /**
     * Buat ordered atau un-ordered list.
     *
     * @param string $type
     * @param array  $list
     * @param array  $attributes
     *
     * @return string
     */
    private static function listing($type, $list, $attributes = [])
    {
        $html = '';

        if (0 === count($list)) {
            return $html;
        }

        foreach ($list as $key => $value) {
            if (is_array($value)) {
                if (is_int($key)) {
                    $html .= static::listing($type, $value);
                } else {
                    $html .= '<li>'.$key.static::listing($type, $value).'</li>';
                }
            } else {
                $html .= '<li>'.static::entities($value).'</li>';
            }
        }

        $attributes = static::attributes($attributes);

        return '<'.$type.$attributes.'>'.$html.'</'.$type.'>';
    }

    /**
     * Buat definition list.
     *
     * @param array $list
     * @param array $attributes
     *
     * @return string
     */
    public static function dl($list, $attributes = [])
    {
        $html = '';

        if (0 === count($list)) {
            return $html;
        }

        foreach ($list as $term => $description) {
            $html .= '<dt>'.static::entities($term).'</dt>';
            $html .= '<dd>'.static::entities($description).'</dd>';
        }

        $attributes = static::attributes($attributes);

        return '<dl'.$attributes.'>'.$html.'</dl>';
    }

    /**
     * Buat listing atribut HTML dari array yang diberikan.
     *
     * @param array $attributes
     *
     * @return string
     */
    public static function attributes($attributes)
    {
        $attributes = (array) $attributes;
        $html = [];

        foreach ($attributes as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
            }

            if (! is_null($value)) {
                $html[] = $key.'="'.static::entities($value).'"';
            }
        }

        return (count($html) > 0) ? ' '.implode(' ', $html) : '';
    }

    /**
     * Obfuscate string untuk menghindari bot spam.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function obfuscate($value)
    {
        $value = str_split($value);
        $obfuscated = '';

        foreach ($value as $letter) {
            switch (rand(1, 3)) {
                case 1: $obfuscated .= '&#'.ord($letter).';'; break;
                case 2: $obfuscated .= '&#x'.dechex(ord($letter)).';'; break;
                case 3: $obfuscated .= $letter; // No break, memang disengaja.
            }
        }

        return $obfuscated;
    }

    /**
     * Ambil konfigurasi application encoding.
     *
     * @return string
     */
    protected static function encoding()
    {
        $default = Config::get('application.encoding', 'UTF-8');
        static::$encoding = static::$encoding ? static::$encoding : $default;

        return static::$encoding;
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
