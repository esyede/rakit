<?php

defined('DS') or exit('No direct access.');

if (!function_exists('e')) {
    /**
     * Ubah karakter HTML ke entity-nya.
     *
     * @param string $value
     *
     * @return string
     */
    function e($value)
    {
        return htmlentities((string) $value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump variable dan hentikan eksekusi script.
     *
     * @param mixed|array $variables
     *
     * @return void
     */
    function dd($variables)
    {
        $variables = func_get_args();

        if (is_cli()) {
            array_map(function ($var) {
                echo ('\\' === DS)
                    ? \System\Foundation\Oops\Dumper::toText($var)
                    : \System\Foundation\Oops\Dumper::toTerminal($var);
            }, $variables);
        } else {
            array_map('\System\Foundation\Oops\Debugger::dump', $variables);
        }

        if (!\System\Foundation\Oops\Debugger::$productionMode) {
            die;
        }
    }
}

if (!function_exists('bd')) {
    /**
     * Dump variable ke debug bar tanpa menghentikan eksekusi script.
     *
     * @param mixed  $variable
     * @param string $title
     *
     * @return void
     */
    function bd($variable, $title = null)
    {
        return \System\Foundation\Oops\Debugger::barDump($variable, $title);
    }
}

if (!function_exists('dump')) {
    /**
     * Dump variable tanpa menghentikan eksekusi script.
     *
     * @param mixed|array $variables
     *
     * @return void
     */
    function dump($variables)
    {
        $variables = is_array($variables) ? $variables : func_get_args();
        array_map('\System\Foundation\Oops\Debugger::dump', $variables);
    }
}

if (!function_exists('optional')) {
    /**
     * Izinkan akses ke objek opsional.
     *
     * @param mixed         $value
     * @param callable|null $callback
     *
     * @return mixed
     */
    function optional($value = null, callable $callback = null)
    {
        return is_null($callback) ? (new \System\Optional($value)) : $callback($value);
    }
}

if (!function_exists('trans')) {
    /**
     * Ambil sebuah baris bahasa.
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return string
     */
    function trans($key, array $replacements = [], $language = null)
    {
        return \System\Lang::has($key) ? \System\Lang::line($key, $replacements, $language) : $key;
    }
}

if (!function_exists('__')) {
    /**
     * Alias untuk trans().
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return string
     */
    function __($key, array $replacements = [], $language = null)
    {
        return trans($key, $replacements, $language);
    }
}

if (!function_exists('is_cli')) {
    /**
     * Cek apakah request saat ini datang dari CLI.
     *
     * @return bool
     */
    function is_cli()
    {
        return defined('STDIN')
            || 'cli' === php_sapi_name()
            || ('cgi' === substr((string) PHP_SAPI, 0, 3) && is_callable('getenv') && getenv('TERM'));
    }
}

if (!function_exists('data_fill')) {
    /**
     * Isi dengan data jika ia masih kosong.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     *
     * @return mixed
     */
    function data_fill(&$target, $key, $value)
    {
        return data_set($target, $key, $value, false);
    }
}

if (!function_exists('data_get')) {
    /**
     * Ambil sebuah item dari array menggunakan notasi 'dot'.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function data_get($target, $key, $default = null)
    {
        if (is_null($key)) {
            return $target;
        }

        $key = is_array($key) ? $key : explode('.', $key);

        while (!is_null($segment = array_shift($key))) {
            if ('*' === $segment) {
                if (!is_array($target)) {
                    return value($default);
                }

                $result = [];

                foreach ($target as $item) {
                    $result[] = data_get($item, $key);
                }

                return in_array('*', $key) ? \System\Arr::collapse($result) : $result;
            }

            if (\System\Arr::accessible($target) && \System\Arr::exists($target, $segment)) {
                $target = $target[$segment];
            } elseif (is_object($target) && isset($target->{$segment})) {
                $target = $target->{$segment};
            } else {
                return value($default);
            }
        }

        return $target;
    }
}

if (!function_exists('data_set')) {
    /**
     * Set sebuah item array mengunakan notasi 'dot'.
     *
     * @param mixed        $target
     * @param string|array $key
     * @param mixed        $value
     * @param bool         $overwrite
     *
     * @return mixed
     */
    function data_set(&$target, $key, $value, $overwrite = true)
    {
        $segments = is_array($key) ? $key : explode('.', $key);

        if ('*' === ($segment = array_shift($segments))) {
            if (!\System\Arr::accessible($target)) {
                $target = [];
            }

            if ($segments) {
                foreach ($target as &$inner) {
                    data_set($inner, $segments, $value, $overwrite);
                }
            } elseif ($overwrite) {
                foreach ($target as &$inner) {
                    $inner = $value;
                }
            }
        } elseif (\System\Arr::accessible($target)) {
            if ($segments) {
                if (!\System\Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || !\System\Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (!isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || !isset($target->{$segment})) {
                $target->{$segment} = $value;
            }
        } else {
            $target = [];

            if ($segments) {
                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite) {
                $target[$segment] = $value;
            }
        }

        return $target;
    }
}

if (!function_exists('tap')) {
    /**
     * Panggil closure dengan value yang diberikan lalu return hasilnya.
     *
     * @param mixed    $value
     * @param \Closure $callback
     *
     * @return mixed
     */
    function tap($value, \Closure $callback)
    {
        if (is_null($callback) || !($callback instanceof \Closure)) {
            return $value;
        }

        $callback($value);
        return $value;
    }
}

if (!function_exists('retry')) {
    /**
     * Ulangi eksekusi sebanyak jumlah yang diberikan.
     *
     * @param int           $times
     * @param callable      $callback
     * @param int           $sleep_ms
     * @param callable|null $when
     *
     * @return mixed
     */
    function retry($times, callable $callback, $sleep_ms = 0, $when = null)
    {
        $attempts = 0;
        --$times;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (\Throwable $e) {
            if (!$times || ($when && !$when($e))) {
                throw $e;
            }

            --$times;

            if ($sleep_ms) {
                usleep($sleep_ms * 1000);
            }

            goto beginning;
        } catch (\Exception $e) {
            if (!$times || ($when && !$when($e))) {
                throw $e;
            }

            --$times;

            if ($sleep_ms) {
                usleep($sleep_ms * 1000);
            }

            goto beginning;
        }
    }
}

if (!function_exists('facile_to_json')) {
    /**
     * Ubah object Facile menjadi string JSON.
     *
     * @param Facile|array $models
     *
     * @return string
     */
    function facile_to_json($models, $json_options = 0)
    {
        if ($models instanceof \System\Database\Facile\Model) {
            $models = $models->to_array();
        } else {
            $models = array_map(function ($model) {
                return $model->to_array();
            }, $models);
        }

        return json_encode($models, $json_options);
    }
}

if (!function_exists('head')) {
    /**
     * Mereturn elemen pertama milik array.
     *
     * @param array $array
     *
     * @return mixed
     */
    function head(array $array)
    {
        return reset($array);
    }
}

if (!function_exists('last')) {
    /**
     * Return elemen terakhir milik array.
     *
     * @param array $array
     *
     * @return mixed
     */
    function last(array $array)
    {
        return end($array);
    }
}

if (!function_exists('url')) {
    /**
     * Buat sebuah URL.
     *
     * <code>
     *
     *      // Buat URL ke lokasi di dalam lingkungan aplikasi
     *      $url = url('user/profile');
     *
     *      // Buat URL ke lokasi di dalam lingkungan aplikasi (https)
     *      $url = url('user/profile', true);
     *
     * </code>
     *
     * @param string $url
     *
     * @return string
     */
    function url($url = '')
    {
        return \System\URL::to($url);
    }
}

if (!function_exists('asset')) {
    /**
     * Buat URL ke sebuah aset.
     *
     * @param string $url
     *
     * @return string
     */
    function asset($url)
    {
        return \System\URL::to_asset($url);
    }
}

if (!function_exists('action')) {
    /**
     * Buat URL ke sebuah action di controller.
     *
     * <code>
     *
     *      // Buat URL ke method 'index' milik controller 'user'
     *      $url = action('user@index');
     *
     *      // Buat URL ke http://situsku.com/user/profile/budi
     *      $url = action('user@profile', ['budi']);
     *
     * </code>
     *
     * @param string $action
     * @param array  $parameters
     *
     * @return string
     */
    function action($action, array $parameters = [])
    {
        return \System\URL::to_action($action, $parameters);
    }
}

if (!function_exists('route')) {
    /**
     * Buat sebuah URL ke named route.
     *
     * <code>
     *
     *      // Buat URL ke route yang bernama 'profile'.
     *      $url = route('profile');
     *
     *      // Buat URL ke route yang bernama 'profile' dengan parameter tambahan.
     *      $url = route('profile', [$name]);
     *
     * </code>
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    function route($name, array $parameters = [])
    {
        return \System\URL::to_route($name, $parameters);
    }
}

if (!function_exists('config')) {
    /**
     * Get atau set config.
     *
     * <code>
     *
     *      // Get config
     *      $language = config('application.language');
     *
     *      // Set config
     *      config(['application.language' => 'jp']);
     *
     * </code>
     *
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function config($key, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                \System\Config::set($name, $value);
            }

            return true;
        }

        return \System\Config::get($key, $default);
    }
}

if (!function_exists('cache')) {
    /**
     * Get/set cache.
     *
     * <code>
     *
     *      // Get cache
     *      $language = cache('error');
     *
     *      // Set cache
     *      cache(['error' => 'Akun tidak ditemukan']);
     *
     * </code>
     *
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function cache($key, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                \System\Cache::set($name, $value);
            }

            return true;
        }

        return \System\Cache::get($key, $default);
    }
}

if (!function_exists('session')) {
    /**
     * Get/set session.
     *
     * <code>
     *
     *      // Get session
     *      $language = session('error');
     *
     *      // Set session
     *      session(['error' => 'Akun tidak ditemukan']);
     *
     * </code>
     *
     * @param string|array $key
     * @param mixed        $default
     *
     * @return mixed
     */
    function session($key, $default = null)
    {
        if (is_array($key)) {
            foreach ($key as $name => $value) {
                \System\Session::set($name, $value);
            }

            return true;
        }

        return \System\Session::get($key, $default);
    }
}

if (! function_exists('collect')) {
    /**
     * Buat collection dari value yang diberikan.
     *
     * @param mixed|null $value
     *
     * @return \System\Collection
     */
    function collect($value = [])
    {
        return new \System\Collection($value);
    }
}

if (!function_exists('fake')) {
    /**
     * Buat instance faker.
     *
     * <code>
     *
     *      // Buat data faker menggunakan default locale.
     *      $name = fake()->name;
     *
     *      // Buat data faker menggunakan custom locale.
     *      $name = fake('en')->name;
     *
     * </code>
     *
     * @param string|null $locale
     *
     * @return mixed
     */
    function fake($locale = null)
    {
        return \System\Foundation\Faker\Factory::create($locale ?: config('application.language'));
    }
}

if (!function_exists('validate')) {
    /**
     * Buat instance validator.
     *
     * @param array $attributes
     * @param array $rules
     * @param array $messages
     *
     * @return \System\Validator
     */
    function validate(array $attributes, array $rules, array $messages = [])
    {
        return \System\Validator::make($attributes, $rules, $messages);
    }
}

if (!function_exists('redirect')) {
    /**
     * Buat sebuah redireksi.
     *
     * <code>
     *
     *      // Buat redireksi
     *      return redirect('user/profile');
     *
     * </code>
     *
     * @param string $url
     * @param int    $status
     *
     * @return \System\Redirect|mixed
     */
    function redirect($url, $status = 302)
    {
        return \System\Redirect::to($url, $status);
    }
}

if (!function_exists('back')) {
    /**
     * Buat sebuah redireksi ke halaman sebelumnya.
     *
     * @param int $status
     *
     * @return \System\Redirect|mixed
     */
    function back($status = 302)
    {
        return \System\Redirect::back($status);
    }
}

if (!function_exists('old')) {
    /**
     * Ambil old input dari session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    function old($key, $default = null)
    {
        return \System\Input::old($key, $default);
    }
}

if (!function_exists('abort')) {
    /**
     * Buat sebuah response error.
     *
     * @param string $code
     * @param array  $headers
     *
     * @return string
     */
    function abort($code, array $headers = [])
    {
        $code = (int) $code;
        $message = \System\Foundation\Http\Response::$statusTexts;
        $message = isset($message[$code]) ? $message[$code] : 'Unknown Error';

        if (\System\Request::wants_json()) {
            $status = $code;
            $message = json_encode(compact('status', 'message'));
            $headers = array_merge($headers, ['Content-Type' => 'application/json']);
        } else {
            $view = \System\View::exists('error.' . $code) ? 'error.' . $code : 'error.unknown';
            $message = \System\View::make($view)->render();
        }

        $response = new \System\Response($message, $code, $headers);
        $response->render();

        if (\System\Config::get('session.driver')) {
            \System\Session::save();
        }

        $response->send();
        \System\Event::fire('rakit.done', [$response]);
        $response->foundation()->finish();

        exit;
    }
}

if (!function_exists('abort_if')) {
    /**
     * Buat sebuah response error jika kondisi terpenuhi.
     *
     * @param bool   $condition
     * @param string $code
     * @param array  $headers
     *
     * @return string
     */
    function abort_if($condition, $code, array $headers = [])
    {
        if ($condition) {
            return abort($code, $headers);
        }
    }
}

if (!function_exists('csrf_name')) {
    /**
     * Ambil nama field CSRF token.
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    function csrf_name()
    {
        return \System\Session::TOKEN;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Ambil token CSRF saat ini.
     *
     * @return string|null
     */
    function csrf_token()
    {
        return \System\Session::get(csrf_name());
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Tambahkan hidden field untuk CSRF token.
     *
     * @return string
     */
    function csrf_field()
    {
        return sprintf('<input type="hidden" name="%s" value="%s">' . PHP_EOL, csrf_name(), csrf_token());
    }
}

if (!function_exists('root_namespace')) {
    /**
     * Ambil root namespace milik class.
     *
     * @param string $class
     * @param string $separator
     *
     * @return string
     */
    function root_namespace($class, $separator = '\\')
    {
        return \System\Str::contains($class, $separator) ? head(explode($separator, $class)) : null;
    }
}

if (!function_exists('class_basename')) {
    /**
     * Ambil 'class basename' milik sebuah kelas atau object.
     * Class basename adalah nama kelas tanpa namespace.
     *
     * @param object|string $class
     *
     * @return string
     */
    function class_basename($class)
    {
        return basename(str_replace('\\', '/', is_object($class) ? get_class($class) : (string) $class));
    }
}

if (!function_exists('value')) {
    /**
     * Mereturn value milik sebuah item.
     * Jika item merupakan sebuah Closure, hasil eksekusinya yang akan di-return.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    function value($value)
    {
        return (is_callable($value) && !is_string($value)) ? call_user_func($value) : $value;
    }
}

if (! function_exists('when')) {
    /**
     * Return sebuah value jika kondisinya true.
     *
     * @param mixed          $condition
     * @param \Closure|mixed $value
     * @param \Closure|mixed $default
     *
     * @return mixed
     */
    function when($condition, $value, $default = null)
    {
        $condition = ($condition instanceof \Closure) ? $condition() : $condition;
        return $condition ? value($value, $condition) : value($default, $condition);
    }
}

if (!function_exists('view')) {
    /**
     * Buat instance kelas View.
     *
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    function view($view, array $data = [])
    {
        return is_null($view) ? '' : \System\View::make($view, $data);
    }
}

if (!function_exists('render')) {
    /**
     * Render view.
     *
     * @param string $view
     * @param array  $data
     *
     * @return string
     */
    function render($view, array $data = [])
    {
        return is_null($view) ? '' : \System\View::make($view, $data)->render();
    }
}

if (!function_exists('render_each')) {
    /**
     * Ambil konten hasil render view parsial.
     *
     * @param string $partial
     * @param array  $data
     * @param string $iterator
     * @param string $empty
     *
     * @return string
     */
    function render_each($partial, array $data, $iterator, $empty = 'raw|')
    {
        return \System\View::render_each($partial, $data, $iterator, $empty);
    }
}

if (!function_exists('yield_content')) {
    /**
     * Ambil konten milik sebuah section.
     *
     * @param string $section
     *
     * @return string
     */
    function yield_content($section)
    {
        return \System\Section::yield_content($section);
    }
}

if (!function_exists('yield_section')) {
    /**
     * Hentikan injeksi konten kedalam section dan return kontennya.
     *
     * @return string
     */
    function yield_section($section)
    {
        return \System\Section::yield_section($section);
    }
}

if (!function_exists('section_start')) {
    /**
     * Mulai injeksi konten ke section.
     *
     * @return string
     */
    function section_start($section, $content = '')
    {
        return \System\Section::start($section, $content);
    }
}

if (!function_exists('section_stop')) {
    /**
     * Hentikan injeksi konten kedalam section.
     *
     * @return string
     */
    function section_stop()
    {
        return \System\Section::stop();
    }
}

if (!function_exists('section_inject')) {
    /**
     * Injeksi konten kedalam section tertentu.
     * @param string $section
     * @param string $content
     *
     * @return string
     */
    function section_inject($section, $content)
    {
        return \System\Section::inject($section, $content);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Enkripsi string.
     *
     * @param string $data
     *
     * @return string
     */
    function encrypt($data)
    {
        return \System\Crypter::encrypt($data);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Enkripsi string.
     *
     * @param string $data
     *
     * @return string
     */
    function decrypt($data)
    {
        return \System\Crypter::decrypt($data);
    }
}

if (!function_exists('bcrypt')) {
    /**
     * Buat hash password.
     *
     * @param string $string
     *
     * @return string
     */
    function bcrypt($string)
    {
        return \System\Hash::make($string);
    }
}

if (!function_exists('dispatch')) {
    /**
     * Jalankan sebuah job.
     *
     * @param string|array $events
     * @param array        $parameters
     * @param bool         $halt
     *
     * @return array|null
     */
    function dispatch($events, array $parameters = [], $halt = false)
    {
        return \System\Event::fire($events, $parameters, $halt);
    }
}

if (!function_exists('blank')) {
    /**
     * Tentukan apakah value yang diberikan "kosong".
     *
     * @param mixed $value
     *
     * @return bool
     */
    function blank($value)
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return '' === trim($value);
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof \Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists('filled')) {
    /**
     * Tentukan apakah value yang diberikan "tidak kosong".
     *
     * @param mixed $value
     *
     * @return bool
     */
    function filled($value)
    {
        return !blank($value);
    }
}

if (!function_exists('now')) {
    /**
     * Ambil instance tanggal saat ini.
     *
     * @param string $tz
     *
     * @return \System\Carbon|string
     */
    function now($tz = null)
    {
        return \System\Carbon::now($tz);
    }
}

if (!function_exists('get_cli_option')) {
    /**
     * Ambil parameter yang dioper ke rakit console.
     *
     * @param string $option
     * @param mixed  $default
     *
     * @return string
     */
    function get_cli_option($option, $default = null)
    {
        $arguments = (array) \System\Request::foundation()->server->get('argv');

        foreach ($arguments as $argument) {
            $argument = (string) $argument;

            if (0 === strpos($argument, '--' . $option . '=')) {
                return substr($argument, mb_strlen($option, '8bit') + 3);
            }
        }

        return value($default);
    }
}

if (!function_exists('has_cli_flag')) {
    /**
     * Ambil parameter yang dioper ke rakit console.
     *
     * @param string $flag
     * @param mixed  $default
     *
     * @return string
     */
    function has_cli_flag($flag)
    {
        $arguments = (array) \System\Request::foundation()->server->get('argv');

        foreach ($arguments as $argument) {
            $argument = (string) $argument;

            if (false !== strpos($argument, '-' . $flag)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('system_os')) {
    /**
     * Ambil platform / sistem operasi server.
     *
     * @return string
     */
    function system_os()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return 'Windows';
        }

        $platforms = [
            'Darwin' => 'Darwin',
            'DragonFly' => 'BSD',
            'FreeBSD' => 'BSD',
            'NetBSD' => 'BSD',
            'OpenBSD' => 'BSD',
            'Linux' => 'Linux',
            'SunOS' => 'Solaris',
        ];

        return isset($platforms[PHP_OS]) ? $platforms[PHP_OS] : 'Unknown';
    }
}

if (!function_exists('human_filesize')) {
    /**
     * Format ukuran file (ramah manusia).
     *
     * @param int $bytes
     * @param int $precision
     *
     * @return string
     */
    function human_filesize($bytes, $precision = 2)
    {
        $precision = (int) $precision;
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $power = min(floor(($bytes ? log($bytes) : 0) / log(1024)), count($units) - 1);
        $bytes = round($bytes / pow(1024, $power), $precision);

        return sprintf('%.' . $precision . 'f %s', $bytes, $units[$power]);
    }
}
