<?php

defined('DS') or exit('No direct access.');

if (!function_exists('e')) {
    /**
     * Escape HTML characters.
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
     * Dump variable then stop script execution.
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
     * Dump variable to the debug bar without stopping script execution.
     *
     * @param mixed       $variable
     * @param string|null $title
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
     * Dump variable without stopping script execution.
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
     * Allow accessing properties/methods of a given object that could be null.
     *
     * @param mixed         $value
     * @param callable|null $callback
     *
     * @return mixed
     */
    function optional($value = null, $callback = null)
    {
        return is_null($callback) ? (new \System\Optional($value)) : $callback($value);
    }
}

if (!function_exists('trans')) {
    /**
     * Alias for trans().
     *
     * @param string $key
     * @param array  $replacements
     * @param string $language
     *
     * @return string
     */
    function trans($key, array $replacements = [], $language = null)
    {
        return Lang::line($key, $replacements, $language);
    }
}

if (!function_exists('__')) {
    /**
     * Alias for trans().
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
     * Check if the current request is coming from CLI.
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
     * Fill with data if it is still empty.
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
     * Get an item from array using 'dot' notation.
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
     * Set an item array using 'dot' notation.
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
     * Call the given closure with the given value and return the value.
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
     * Retry execution for the given number of times.
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
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
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
     * Transform Facile object to JSON string.
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
     * Return the first element of an array.
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
     * Return the last element of an array.
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
     * Create a URL.
     *
     * <code>
     *
     *      // Create URL to location within application environment
     *      $url = url('user/profile');
     *
     *      // Create URL to location within application environment (https)
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
     * Create a URL to an asset.
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
     * Create a URL to a controller action.
     *
     * <code>
     *
     *      // Create URL to the 'index' method of the 'user' controller.
     *      $url = action('user@index');
     *
     *      // Create URL to the 'profile' method of the 'user' controller with parameter.
     *      $url = action('user@profile', ['john']);
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
     * Create a URL to a named route.
     *
     * <code>
     *
     *      // Create URL to the route named 'profile'.
     *      $url = route('profile');
     *
     *      // Create URL to the route named 'profile' with parameter.
     *      $url = route('profile', ['john']);
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
     * Get or set config.
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
     * Get or set cache.
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
     * Get or set session.
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
     * Create collection from given value.
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
     * Create a faker instance.
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
     * Create a validator instance.
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
     * Create a redirect.
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
     * Create a redirect back.
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
     * Get or set old input from session.
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
     * Create a response error.
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
     * Create a response error if condition is true.
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
     * Get the CSRF token name.
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
     * Get the CSRF token value.
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
     * Add a hidden field for CSRF token.
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
     * Get the root namespace of a class.
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
     * Get the class basename of a class or object.
     * Class basename is the class name without namespace.
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
     * Return the value of an item.
     * If the item is a Closure, the result of its execution will be returned.
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
     * Return a value if the condition is true.
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
     * Create an instance of the View class.
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
     * Render a view.
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
     * Get the content of a rendered partial view.
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
     * Get the content of a section.
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
     * Stop injecting content into a section and return its content.
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
     * Start injecting content into a section.
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
     * Stop injecting content into a section.
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
     * Inject content into a section.
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
     * Encrypt a string.
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
     * Decrypt a string.
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
     * Create a hash password.
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
     * Run a job.
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
     * Determine if the given value is "blank".
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
     * Determine if the given value is "filled".
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
     * Get the current date and time.
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
     * Get the parameter passed to rakit console.
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
     * Check if the given flag is passed to rakit console.
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
     * Get the server's operating system.
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
     * Format file size (human-friendly).
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
