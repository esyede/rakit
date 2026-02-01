<?php

namespace System;

defined('DS') or exit('No direct access.');

class View implements \ArrayAccess
{
    /**
     * Nama event view loader.
     *
     * @var string
     */
    const LOADER = 'rakit.view.loader';

    /**
     * Nama event view engine.
     *
     * @var string
     */
    const ENGINE = 'rakit.view.engine';

    /**
     * Berisi nama view.
     *
     * @var string
     */
    public $view;

    /**
     * Berisi data-data view.
     *
     * @var array
     */
    public $data;

    /**
     * Berisi path (absolut) view di disk.
     *
     * @var string
     */
    public $path;

    /**
     * Berisi data-data view yang di-share.
     *
     * @var array
     */
    public static $shared = [];

    /**
     * Berisi list nama-nama view terdaftar.
     *
     * @var array
     */
    public static $names = [];

    /**
     * Berisi list konten cache view.
     *
     * @var array
     */
    public static $cache = [];

    /**
     * Berisi view terakhir yang akan di-render.
     *
     * @var string
     */
    public static $last;

    /**
     * Counter operasi render view.
     *
     * @var int
     */
    public static $rendered = 0;

    /**
     * Buat instance view baru.
     *
     * <code>
     *
     *      // Buat sebuah instance view baru
     *      $view = new View('home.index');
     *
     *      // Buat sebuah instance view baru (milik paket)
     *      $view = new View('admin::home.index');
     *
     *      // Buat sebuah instance view baru dengan view data
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
     * Periksa apakah view yang diberikan ada atau tidak.
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
     * Ambil path (absolut) view di disk.
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
     * Ambil path ke view menggunakan kovensi default.
     *
     * @param string $package
     * @param string $view
     * @param string $directory
     *
     * @return string
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
    }

    /**
     * Buat sebuah instance view baru.
     *
     * <code>
     *
     *      // Buat sebuah instance view baru
     *      $view = View::make('home.index');
     *
     *      // Buat sebuah instance view baru (milik paket)
     *      $view = View::make('admin::home.index');
     *
     *      // Buat sebuah instance view baru dengan view data
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
     * Buat sebuah instance view baru dari sebuah named view.
     *
     * <code>
     *
     *      // Buat sebuah instance view baru dari sebuah named view
     *      $view = View::of('profile');
     *
     *      // Buat sebuah instance view baru dari sebuah named view dengan view data
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
     * Beri nama ke sebuah view.
     *
     * <code>
     *
     *      // Beri nama ke sebuah view
     *      View::name('partials.profile', 'profile');
     *
     *      // Resolve instance ke sebuah named view
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
     * Daftarkan view composer menggunakan kelas \System\Event.
     *
     * <code>
     *
     *      // Daftarkan view composer untuk view 'home.index'
     *      View::composer('home.index', function ($view) {
     *          $view['title'] = 'Beranda';
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
     * Ambil hasil render view parsial dari sebuah loop.
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
     * Ambil string konten hasil evaluasi dari sebuah view.
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

        return $contents;
    }

    /**
     * Ambil konten hasil evaluasi sebuah view.
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        } catch (\Exception $e) {
            ob_get_clean();
            throw $e;
        }
    }

    /**
     * Ambil array view data untuk instance view.
     * Shared view data akan dicampur dengan view data biasa.
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
     * Tambahkan instance view ke view data.
     *
     * <code>
     *
     *      // Tambahkan instance view ke view data (cara 1)
     *      $view = View::make('foo')->nest('footer', 'partials.footer');
     *
     *      // Tambahkan instance view ke view data (cara 2)
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
     * Tambahkan sebuah data key-value ke view data,
     * data ini bisa diakses di view sebagai variabel.
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
     * Chainable View::share().
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
     * Tambahkan sebuah data key-value ke shared view data,
     * Shared view data bisa diakses oleh semua view di lingkup aplikasi.
     *
     * @param string $key
     * @param mixed  $value
     */
    public static function share($key, $value)
    {
        static::$shared[$key] = $value;
    }

    /**
     * Bersihkan seluruh file hasil kompilasi blade.
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
     * Implementasi ArrayAccess::offsetExists().
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * Implementasi ArrayAccess::offsetGet().
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * Implementasi ArrayAccess::offsetSet().
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * Implementasi ArrayAccess::offsetUnset().
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Magic Method getter.
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Magic Method setter.
     */
    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    /**
     * Magic Method untuk data checking.
     */
    public function __isset($key)
    {
        return isset($this->data[$key]);
    }

    /**
     * Mereturn string hasil eveluasi view.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }

    /**
     * Magic Method menangani pemanggilan method secara dinamis.
     * Method ini menangai pemanggilan helper 'with_xxx'.
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
