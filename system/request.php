<?php

namespace System;

defined('DS') or exit('No direct access.');

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
     * List format request.
     *
     * @var array
     */
    public static $formats = [
        'html' => ['text/html', 'application/xhtml+xml'],
        'txt' => ['text/plain'],
        'js' => ['application/javascript', 'application/x-javascript', 'text/javascript'],
        'css' => ['text/css'],
        'json' => ['application/json', 'application/x-json'],
        'jsonld' => ['application/ld+json'],
        'xml' => ['text/xml', 'application/xml', 'application/x-xml'],
        'rdf' => ['application/rdf+xml'],
        'atom' => ['application/atom+xml'],
        'rss' => ['application/rss+xml'],
        'form' => ['application/x-www-form-urlencoded'],
    ];

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
        $method = strtoupper((string) static::foundation()->getMethod());
        return ('HEAD' === $method) ? 'GET' : $method;
    }

    /**
     * Memeriksa tipe request method.
     *
     * @param string $method
     *
     * @return bool
     */
    public static function is_method($method)
    {
        $method = strtoupper($method);
        return static::method() === (('HEAD' === $method) ? 'GET' : $method);
    }

    /**
     * Ambil request handler dari request saat ini.
     *
     * <code>
     *
     *      // Ambil request handler dari request saat ini
     *      $accept = Request::header('Accept');
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
        return Arr::get(static::servers(), strtoupper((string) $key), $default);
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
        return !is_null(static::foundation()->get(static::SPOOFER));
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
        $address = static::foundation()->getClientIp();
        return is_null($address) ? $default : $address;
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
     * Cek apakah requset saat ini bisa menerima content-type yg diberikan.
     *
     * @param string|array $types
     *
     * @return bool
     */
    public static function accepts($types)
    {
        $types = is_array($types) ? $types : func_get_args();
        $accepts = static::accept();

        if (count($accepts) === 0) {
            return true;
        }

        foreach ($accepts as $accept) {
            if ($accept === '*/*' || $accept === '*') {
                return true;
            }

            foreach ($types as $type) {
                if (static::matches_type($accept, $type) || $accept === strtok($type, '/') . '/*') {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Mereturn content-type yang paling cocok dari daftar yang tersedia.
     *
     * @param string|array $types
     *
     * @return string|null
     */
    public function prefers($types)
    {
        $types = is_array($types) ? $types : func_get_args();
        $accepts = static::accept();

        foreach ($accepts as $accept) {
            if (in_array($accept, ['*/*', '*'])) {
                return $types[0];
            }

            foreach ($types as $ctype) {
                $type = isset(static::$formats[$ctype]) ? static::$formats[$ctype] : $ctype;

                if (static::matches_type($type, $accept) || $accept === strtok($type, '/') . '/*') {
                    return $ctype;
                }
            }
        }
    }

    /**
     * Cek apakah requset saat ini bisa menerima html.
     *
     * @return bool
     */
    public function accept_html()
    {
        return $this->accepts('text/html');
    }

    /**
     * Cek apakah requset saat ini bisa menerima content-type apapun.
     *
     * @return bool
     */
    public static function accept_any()
    {
        $accept = static::accept();
        return count($accept) === 0 || (isset($accept[0]) && ($accept[0] === '*/*' || $accept[0] === '*'));
    }

    /**
     * Cek kecocokan content type.
     *
     * @param string $actual
     * @param string $type
     *
     * @return bool
     */
    public static function matches_type($actual, $type)
    {
        if ($actual === $type) {
            return true;
        }

        $split = explode('/', $actual);

        return isset($split[1]) && false !== preg_match(
            '#' . preg_quote($split[0], '#') . '/.+\+' . preg_quote($split[1], '#') . '#',
            $type
        );
    }

    /**
     * Cek apakah request saat ini mengirim json.
     *
     * @return bool
     */
    public static function is_json()
    {
        $type = static::header('Content-Type');
        return Str::contains($type ?: '', ['/json', '+json']);
    }

    /**
     * Cek apakah request saat ini mungkin mengharapkan response json atau tidak.
     *
     * @return bool
     */
    public static function expects_json()
    {
        return (static::ajax() && !static::pjax() && static::accept_any()) || static::wants_json();
    }

    /**
     * Cek apakah request saat ini meminta json.
     *
     * @return bool
     */
    public static function wants_json()
    {
        $accept = static::accept();
        return isset($accept[0]) && Str::contains($accept[0], ['/json', '+json']);
    }

    /**
     * Ambil authorization header.
     *
     * @param mixed $default
     *
     * @return string|null
     */
    public static function authorization()
    {
        return static::header('Authorization');
    }

    /**
     * Ambil bearer token header.
     *
     * @param mixed $default
     *
     * @return string|null
     */
    public static function bearer()
    {
        $auth = (string) static::authorization();
        return (0 === stripos($auth, 'Bearer ')) ? mb_substr($auth, 7, null, '8bit') : null;
    }

    /**
     * Ambil request body.
     *
     * @param bool $as_resource
     *
     * @return string|resource|null
     */
    public static function content($as_resource = false)
    {
        return static::foundation()->getContent($as_resource);
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
     * Ambil user-agent milik pengirim request saat ini.
     *
     * @return bool
     */
    public static function agent()
    {
        return static::header('User-Agent');
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
        $token = Session::token();
        $header = static::header('X-Csrf-Token');

        if (
            in_array(static::method(), ['GET', 'OPTIONS'])
            || Input::get(Session::TOKEN) === Session::token()
            || false !== stripos((string) $header, 'nocheck')
        ) {
            return false;
        }

        return $token !== $header;
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
     * Cek apakah request saat ini merupakan hasil PJAX atau bukan.
     *
     * @return bool
     */
    public function pjax()
    {
        return (bool) static::header('X-Pjax') === true;
    }

    /**
     * Cek apakah request saat ini merupakan hasil prefetch atau bukan.
     *
     * @return bool
     */
    public function prefetch()
    {
        return strcasecmp(static::server('HTTP_X_MOZ'), 'prefetch') === 0
            || strcasecmp(static::header('Purpose'), 'prefetch') === 0;
    }

    /**
     * Ambil HTTP Referrer milik request.
     *
     * @return string
     */
    public static function referrer()
    {
        return static::header('Referer');
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
            || ('cgi' === substr((string) PHP_SAPI, 0, 3) && is_callable('getenv') && getenv('TERM'));
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
     * @return \System\Foundation\Http\Request
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
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::foundation(), $method], $parameters);
    }
}
