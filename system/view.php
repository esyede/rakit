<?php

namespace System;

defined('DS') or exit('No direct access.');

class View implements \ArrayAccess
{
    /**
     * Name of the view loader event.
     *
     * @var string
     */
    const LOADER = 'rakit.view.loader';

    /**
     * Name of the view engine event.
     *
     * @var string
     */
    const ENGINE = 'rakit.view.engine';

    /**
     * Contains the view name.
     *
     * @var string
     */
    public $view;

    /**
     * Contains the view data.
     *
     * @var array
     */
    public $data;

    /**
     * Contains the (absolute) view path on disk.
     *
     * @var string
     */
    public $path;

    /**
     * Contains the view data that will be shared.
     *
     * @var array
     */
    public static $shared = [];

    /**
     * Contains the list of registered view names.
     *
     * @var array
     */
    public static $names = [];

    /**
     * Contains the list of cached view contents.
     *
     * @var array
     */
    public static $cache = [];

    /**
     * Contains the view that will be rendered last.
     *
     * @var string
     */
    public static $last;

    /**
     * The number of times a view has been rendered.
     *
     * @var int
     */
    public static $rendered = 0;

    /**
     * Constructor.
     *
     * <code>
     *
     *      // Create a new view instance
     *      $view = new View('home.index');
     *
     *      // Create a new view instance (package)
     *      $view = new View('admin::home.index');
     *
     *      // Create a new view instance with view data
     *      $view = new View('home.index', ['name' => 'Budi']);
     *
     * </code>
     *
     * @param string $view
     * @param array  $data
     */
    public function __construct($view, array $data = [])
    {
        $this->view = $view;
        $this->data = $data;
        $this->path = (0 === strpos($view, 'path: ')) ? substr($view, 6) : $this->path($view);

        if (!isset($this->data['errors'])) {
            $this->data['errors'] = (Session::started() && Session::has('errors'))
                ? Session::get('errors')
                : new Messages();
        }
    }

    /**
     * Check if the given view exists.
     *
     * @param string $view
     * @param bool   $return_path
     *
     * @return string|bool
     */
    public static function exists($view, $return_path = false)
    {
        if (0 === strpos($view, 'name: ') && array_key_exists($name = substr($view, 6), static::$names)) {
            $view = static::$names[$name];
        }

        list($package, $view) = Package::parse($view);
        $path = Event::until(static::LOADER, [$package, str_replace(['.', '/'], DS, $view)]);
        return is_null($path) ? false : ($return_path ? $path : true);
    }

    /**
     * Get the absolute path of the view on disk.
     *
     * @param string $view
     *
     * @return string
     */
    protected function path($view)
    {
        if ($path = $this->exists($view, true)) {
            return $path;
        }

        throw new \Exception(sprintf('View does not exist: %s', $view));
    }

    /**
     * Get the absolute path to the view using the default convention.
     *
     * @param string $package
     * @param string $view
     * @param string $directory
     *
     * @return string|null
     */
    public static function file($package, $view, $directory)
    {
        $directory = Str::finish($directory, DS);

        if (is_file($path = $directory . $view . '.php')) {
            return $path;
        }

        if (is_file($path = $directory . $view . '.blade.php')) {
            return $path;
        }

        return null;
    }

    /**
     * Create a new instance view.
     *
     * <code>
     *
     *      // Create a new view instance
     *      $view = View::make('home.index');
     *
     *      // Create a new view instance (belonging to a package)
     *      $view = View::make('admin::home.index');
     *
     *      // Create a new view instance with view data
     *      $view = View::make('home.index', ['name' => 'Budi']);
     *
     * </code>
     *
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    public static function make($view, array $data = [])
    {
        return new static($view, $data);
    }

    /**
     * Create a new instance view from a named view.
     *
     * <code>
     *
     *      // Create a new view instance from a named view
     *      $view = View::of('profile');
     *
     *      // Create a new view instance from a named view with view data
     *      $view = View::of('profile', ['name' => 'Budi']);
     *
     * </code>
     *
     * @param string $name
     * @param array  $data
     *
     * @return View
     */
    public static function of($name, array $data = [])
    {
        return new static(static::$names[$name], $data);
    }

    /**
     * Give a name to a view.
     *
     * <code>
     *
     *      // Give a name to a view
     *      View::name('partials.profile', 'profile');
     *
     *      // Resolve instance to a named view
     *      $view = View::of('profile');
     *
     * </code>
     *
     * @param string $view
     * @param string $name
     */
    public static function name($view, $name)
    {
        static::$names[$name] = $view;
    }

    /**
     * Register view composer using the Event class.
     *
     * <code>
     *
     *      // Register view composer for 'home.index'
     *      View::composer('home.index', function ($view) {
     *          $view['title'] = 'Homepage';
     *      });
     *
     * </code>
     *
     * @param string|array $views
     * @param \Closure     $composer
     */
    public static function composer($views, \Closure $composer)
    {
        $views = (array) $views;

        foreach ($views as $view) {
            Event::listen('rakit.composing: ' . $view, $composer);
        }
    }

    /**
     * Get the rendered content of a partial view from a loop.
     *
     * @param string $view
     * @param array  $data
     * @param string $iterator
     * @param string $empty
     *
     * @return string
     */
    public static function render_each($view, array $data, $iterator, $empty = 'raw|')
    {
        $result = '';

        if (count($data) > 0) {
            foreach ($data as $key => $value) {
                $result .= render($view, ['key' => $key, $iterator => $value]);
            }
        } else {
            $result = (0 === strpos($empty, 'raw|')) ? substr($empty, 4) : render($empty);
        }

        return $result;
    }

    /**
     * Render a view.
     *
     * @return string
     */
    public function render()
    {
        ++static::$rendered;

        Event::fire('rakit.composing: ' . $this->view, [$this]);

        $contents = null;

        if (Event::exists(static::ENGINE)) {
            $result = Event::until(static::ENGINE, [$this]);
            $contents = $result ?: $contents;
        }

        $contents = $contents ?: $this->get();
        --static::$rendered;

        if (0 === static::$rendered) {
            Section::$sections = [];
            Section::$stacks = [];
        }

        // Track view rendering for debugger
        if (class_exists('\System\Foundation\Oops\Debugger') && class_exists('\System\Foundation\Oops\Collectors')) {
            if (!\System\Foundation\Oops\Debugger::$productionMode) {
                \System\Foundation\Oops\Collectors::trackView(
                    $this->view,
                    $this->path,
                    $this->data,
                    0,
                    strlen($contents)
                );
            }
        }

        return $contents;
    }

    /**
     * Get the rendered content of a view instance.
     *
     * @return string
     */
    public function get()
    {
        ob_start();

        try {
            static::$last = ['name' => $this->view, 'path' => $this->path];
            extract($this->data());

            if (!isset(static::$cache[$this->path])) {
                static::$cache[$this->path] = $this->path;
            }

            require static::$cache[$this->path];

            $content = ob_get_clean();

            if (Event::exists('view.middleware')) {
                return Event::first('view.middleware', [$content, $this->path]);
            }

            return $content;
        } catch (\Throwable $e) {
            ob_get_clean();
            throw $e;
        } catch (\Exception $e) {
            ob_get_clean();
            throw $e;
        }
    }

    /**
     * Get the view instance data.
     * Shared view data will be merged.
     *
     * @return array
     */
    public function data()
    {
        $data = array_merge($this->data, static::$shared);

        foreach ($data as $key => $value) {
            if (($value instanceof View) || ($value instanceof Response)) {
                $data[$key] = $value->render();
            }
        }

        return $data;
    }

    /**
     * Add a view instance to the view data.
     *
     * <code>
     *
     *      // Add a view instance to the view data (method 1)
     *      $view = View::make('foo')->nest('footer', 'partials.footer');
     *
     *      // Add a view instance to the view data (method 2)
     *      $view = View::make('foo')->with('footer', View::make('partials.footer'));
     *
     * </code>
     *
     * @param string $key
     * @param string $view
     * @param array  $data
     *
     * @return View
     */
    public function nest($key, $view, array $data = [])
    {
        return $this->with($key, static::make($view, $data));
    }

    /**
     * Bind a key-value data into the view,
     * This data can be accessed in the view as a variable.
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return View
     */
    public function with($key, $value = null)
    {
        if (is_array($key)) {
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $value;
        }

        return $this;
    }

    /**
     * Alias for share().
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return View
     */
    public function shares($key, $value)
    {
        static::share($key, $value);
        return $this;
    }

    /**
     * Add a data into shared view data,
     * Shared view data can be accessed by all views in the application scope.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function share($key, $value)
    {
        static::$shared[$key] = $value;
    }

    /**
     * Clear all compiled blade files.
     */
    public static function flush()
    {
        $files = glob(path('storage') . 'views' . DS . '*.php');

        if (is_array($files) && count($files) > 0) {
            foreach ($files as $file) {
                Storage::delete($file);
            }
        }
    }

    /**
     * ArrayAccess implementation
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * ArrayAccess implementation
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess implementation
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess implementation
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Magic method implementation.
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Magic method implementation.
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic method implementation.
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Returns the rendered view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Handle dynamic method calls.
     *
     * @return $this
     */
    public function __call($method, array $parameters)
    {
        if (0 === strpos($method, 'with_')) {
            return $this->with(substr($method, 5), $parameters[0]);
        }

        throw new \Exception(sprintf('Method does not exists: %s', $method));
    }
}
