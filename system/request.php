<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Request
{
    /**
     * Nama keey untuk request spoofing.
     *
     * @var string
     */
    const SPOOFER = '_method';

    /**
     * Berisi seluruh instance route untuk penanganan request.
     *
     * @var array
     */
    public static $route;

    /**
     * Berisi instance miik http foundation.
     *
     * @var \System\Faundation\Http\Request
     */
    public static $foundation;

    /**
     * Ambil URI request saat ini.
     *
     * @return string
     */
    public static function uri()
    {
        return URI::current();
    }

    /**
     * Ambil request method dari request saat ini.
     *
     * @return string
     */
    public static function method()
    {
        $method = static::foundation()->getMethod();
        return ('HEAD' === $method) ? 'GET' : $method;
    }

    /**
     * Ambil request handler dari request saat ini.
     *
     * <code>
     *
     *      // Ambil request handler dari request saat ini
     *      $accept = Request::header('accept');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public static function header($key, $default = null)
    {
        return Arr::get(static::headers(), $key, $default);
    }

    /**
     * Ambil seluruh HTTP request header.
     *
     * @return array
     */
    public static function headers()
    {
        $headers = static::foundation()->headers->all();
        $all = [];

        foreach ($headers as $name => $values) {
            foreach ($values as $value) {
                $all[$name] = $value;
            }
        }

        return $all;
    }

    /**
     * Ambil sebuah item dari array global $_SERVER.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return string
     */
    public static function server($key, $default = null)
    {
        return Arr::get(static::servers(), strtoupper($key), $default);
    }

    /**
     * Ambil suluruh item dari array global $_SERVER.
     *
     * @return array
     */
    public static function servers()
    {
        return static::foundation()->server->all();
    }

    /**
     * Cek apakah request method di-spoof dengan hidden form atau tidak.
     *
     * @return bool
     */
    public static function spoofed()
    {
        return ! is_null(static::foundation()->get(Request::SPOOFER));
    }

    /**
     * Ambil IP si pengirim request.
     *
     * @param mixed $default
     *
     * @return string
     */
    public static function ip($default = '0.0.0.0')
    {
        $client_ip = static::foundation()->getClientIp();
        return is_null($client_ip) ? $default : $client_ip;
    }

    /**
     * Ambil list acceptable content-types dari request saat ini.
     *
     * @return array
     */
    public static function accept()
    {
        return static::foundation()->getAcceptableContentTypes();
    }

    /**
     * Cek apakah conten-type yang diberikan bisa diterima oleh requset saat ini.
     *
     * @param string $type
     *
     * @return bool
     */
    public static function accepts($type)
    {
        return in_array($type, static::accept());
    }

    /**
     * Ambil language list yang bisa diterima browser si klien.
     *
     * @return array
     */
    public static function languages()
    {
        return static::foundation()->getLanguages();
    }

    /**
     * Cek apakah request saat ini datang via HTTPS atau bukan.
     *
     * @return bool
     */
    public static function secure()
    {
        return static::foundation()->isSecure();
    }

    /**
     * Cek apakah request sudah dibuat atau belum,
     * Indikasi request sudah dibuat adalah token CSRF yang dikirim user sama dengan
     * token CSRF yang ada di Session.
     *
     * @return bool
     */
    public static function forged()
    {
        return Input::get(Session::TOKEN) !== Session::token();
    }

    /**
     * Cek apakah request saat ini merupakan AJAX request atau bukan.
     *
     * @return bool
     */
    public static function ajax()
    {
        return static::foundation()->isXmlHttpRequest();
    }

    /**
     * Ambil HTTP Referrer milik request.
     *
     * @return string
     */
    public static function referrer()
    {
        return static::foundation()->headers->get('referer');
    }

    /**
     * Ambil timestamp kapan sebuah request dimulai.
     *
     * @return int
     */
    public static function time()
    {
        return (int) RAKIT_START;
    }

    /**
     * Cek apakah request saat ini datang dari konsol atau bukan.
     *
     * @return bool
     */
    public static function cli()
    {
        return defined('STDIN')
            || 'cli' === php_sapi_name()
            || ('cgi' === substr(PHP_SAPI, 0, 3) && getenv('TERM'));
    }

    /**
     * Ambil routw handler utama milik request saat ini.
     *
     * @return Route
     */
    public static function route()
    {
        return static::$route;
    }

    /**
     * Ambil instance http foundation request.
     *
     * @return System\Faundation\Http\Request
     */
    public static function foundation()
    {
        return static::$foundation;
    }

    /**
     * Oper method-method lainnya ke http foundation request.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([static::foundation(), $method], $parameters);
    }
}
