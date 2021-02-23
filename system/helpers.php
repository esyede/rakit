<?php

defined('DS') or exit('No direct script access.');

if (! function_exists('e')) {
    /**
     * Ubah karakter HTML ke entity-nya.
     *
     * @param string $value
     *
     * @return string
     */
    function e($value)
    {
        return HTML::entities($value);
    }
}

if (! function_exists('dd')) {
    /**
     * Dump variable dan hentikan eksekusi script.
     *
     * @param mixed ...$variables
     *
     * @return void
     */
    function dd(/* ...$variables */)
    {
        $variables = func_get_args();
        if (is_cli()) {
            array_map(function ($var) {
                if ('\\' === DIRECTORY_SEPARATOR) {
                    echo \System\Foundation\Oops\Dumper::toText($var);
                } else {
                    echo \System\Foundation\Oops\Dumper::toTerminal($var);
                }
            }, $variables);
        } else {
            array_map('\System\Foundation\Oops\Debugger::dump', $variables);
        }

        if (! \System\Foundation\Oops\Debugger::$productionMode) {
            die();
        }
    }
}

if (! function_exists('bd')) {
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

if (! function_exists('dump')) {
    /**
     * Dump variable tanpa menghentikan eksekusi script.
     *
     * @param mixed ...$variables
     *
     * @return void
     */
    function dump(/* ...$variables */)
    {
        return array_map('\System\Foundation\Oops\Debugger::dump', func_get_args());
    }
}

if (! function_exists('__')) {
    /**
     * Ambil sebuah baris bahasa.
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return string
     */
    function __($key, $replacements = [], $language = null)
    {
        return Lang::line($key, $replacements, $language);
    }
}

if (! function_exists('is_cli')) {
    /**
     * Cek apakah request saat ini datang dari CLI.
     *
     * @return bool
     */
    function is_cli()
    {
        return defined('STDIN')
            || 'cli' === php_sapi_name()
            || ('cgi' === substr(PHP_SAPI, 0, 3) && getenv('TERM'));
    }
}

if (! function_exists('data_fill')) {
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

if (! function_exists('data_get')) {
    /**
     * Ambil sebuah item dari array atau object menggunakan notasi 'dot'.
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

        while (! is_null($segment = array_shift($key))) {
            if ('*' === $segment) {
                if (! is_array($target)) {
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

if (! function_exists('data_set')) {
    /**
     * Set sebuah item array atau object mengunakan notasi 'dot'.
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
            if (! \System\Arr::accessible($target)) {
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
                if (! \System\Arr::exists($target, $segment)) {
                    $target[$segment] = [];
                }

                data_set($target[$segment], $segments, $value, $overwrite);
            } elseif ($overwrite || ! \System\Arr::exists($target, $segment)) {
                $target[$segment] = $value;
            }
        } elseif (is_object($target)) {
            if ($segments) {
                if (! isset($target->{$segment})) {
                    $target->{$segment} = [];
                }

                data_set($target->{$segment}, $segments, $value, $overwrite);
            } elseif ($overwrite || ! isset($target->{$segment})) {
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

if (! function_exists('retry')) {
    /**
     * Ulangi eksekusi sebanyak jumlah yang diberikan.
     *
     * @param int      $times
     * @param callable $callback
     * @param int      $sleep
     *
     * @throws \Exception
     *
     * @return mixed
     */
    function retry($times, callable $callback, $sleep = 0, $when = null)
    {
        $attempts = 0;
        --$times;

        beginning:
        $attempts++;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if (! $times || ($when && ! $when($e))) {
                throw $e;
            }

            --$times;

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }
}

if (! function_exists('tap')) {
    /**
     * Eksekusi closure yang diberikan lalu return valuenya.
     *
     * @param mixed    $value
     * @param callable $callback
     *
     * @return mixed
     */
    function tap($value, callable $callback)
    {
        $callback($value);

        return $value;
    }
}

if (! function_exists('trait_uses_recursive')) {
    /**
     * Mereturn seluruh trait yang digunakan oleh sebuah trait dan traitnya.
     *
     * @param string $trait
     *
     * @return array
     */
    function trait_uses_recursive($trait)
    {
        $traits = class_uses($trait);

        foreach ($traits as $trait) {
            $traits += trait_uses_recursive($trait);
        }

        return $traits;
    }
}

if (! function_exists('facile_to_json')) {
    /**
     * Ubah object Model menjadi string JSON.
     *
     * @param Facile|array $models
     *
     * @return object
     */
    function facile_to_json($models)
    {
        if ($models instanceof \System\Database\Facile\Model) {
            return json_encode($models->to_array(), JSON_BIGINT_AS_STRING | JSON_PRETTY_PRINT);
        }

        return json_encode(array_map(function ($model) {
            return $model->to_array();
        }, $models), JSON_BIGINT_AS_STRING | JSON_PRETTY_PRINT);
    }
}

if (! function_exists('head')) {
    /**
     * Mereturn elemen pertama milik array.
     *
     * @param array $array
     *
     * @return mixed
     */
    function head($array)
    {
        return reset($array);
    }
}

if (! function_exists('last')) {
    /**
     * Return elemen terakhir milik array.
     *
     * @param array $array
     *
     * @return mixed
     */
    function last($array)
    {
        return end($array);
    }
}

if (! function_exists('url')) {
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

if (! function_exists('asset')) {
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

if (! function_exists('action')) {
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
    function action($action, $parameters = [])
    {
        return \System\URL::to_action($action, $parameters);
    }
}

if (! function_exists('route')) {
    /**
     * Buat sebuah URL ke named route.
     *
     * <code>
     *
     *      // Buat URL ke route yang bernama 'profile'.
     *      $url = route('profile');
     *
     *      // Buat URL ke route yang bernama 'profile' dengan parameter tambahan.
     *      $url = route('profile', [$username]);
     *
     * </code>
     *
     * @param string $name
     * @param array  $parameters
     *
     * @return string
     */
    function route($name, $parameters = [])
    {
        return \System\URL::to_route($name, $parameters);
    }
}

if (! function_exists('root_namespace')) {
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
        if (\System\Str::contains($class, $separator)) {
            return head(explode($separator, $class));
        }
    }
}

if (! function_exists('class_basename')) {
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
        if (is_object($class)) {
            $class = get_class($class);
        }

        return basename(str_replace('\\', '/', $class));
    }
}

if (! function_exists('value')) {
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
        return (is_callable($value) && ! is_string($value)) ? call_user_func($value) : $value;
    }
}

if (! function_exists('view')) {
    /**
     * Buat instance kelas View.
     *
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    function view($view, $data = [])
    {
        if (is_null($view)) {
            return '';
        }

        return \System\View::make($view, $data);
    }
}

if (! function_exists('render')) {
    /**
     * Render view.
     *
     * @param string $view
     * @param array  $data
     *
     * @return string
     */
    function render($view, $data = [])
    {
        if (is_null($view)) {
            return '';
        }

        return \System\View::make($view, $data)->render();
    }
}

if (! function_exists('render_each')) {
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

if (! function_exists('yield_content')) {
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

if (! function_exists('yield_section')) {
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

if (! function_exists('section_start')) {
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

if (! function_exists('section_stop')) {
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

if (! function_exists('get_cli_option')) {
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
        $arguments = \System\Request::foundation()->server->get('argv');

        foreach ($arguments as $argument) {
            if (Str::starts_with($argument, '--'.$option.'=')) {
                return substr($argument, strlen($option) + 3);
            }
        }

        return value($default);
    }
}
