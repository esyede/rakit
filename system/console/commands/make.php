<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\File;
use System\Package;

class Make extends Command
{
    /**
     * Buat file controller baru.
     *
     * <code>
     *
     *      // Buat file controller baru.
     *      php rakit make:controller dashboard
     *
     *      // Buat file controller baru didalam subdirektori.
     *      php rakit make:controller admin.home
     *
     *      // Buat file controller baru di paket 'admin'.
     *      php rakit make:controller admin::dashboard
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function controller($arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (! Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_controller' === Str::lower($class)) {
            throw new \Exception('Please choose another name for controller.');
        }

        $class = Str::replace_last('_controller', '', Str::lower($class));
        $root = Package::path($package).'controllers'.DS;
        $file = $root.str_replace('/', DS, $this->slashes($class)).'.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (File::exists($file)) {
            echo 'Controller already exists: '.$display.'   (skipped)';
        } else {
            $directory = Str::replace_last(basename($file), '', $file);
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package).Str::classify($class),
            ];

            File::put($file, $this->stub($class, $replace, 'controller'));

            echo 'Created controller: '.$display;
        }

        return $file;
    }

    /**
     * Buat file model baru.
     *
     * <code>
     *
     *      // Buat file model baru.
     *      php rakit make:model user
     *
     *      // Buat file model baru di paket 'admin'.
     *      php rakit make:model admin::user
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function model($arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (strpos($arguments[0], '/')) {
            throw new \Exception('Cannot create model inside subdirectory.');
        }

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (! Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        $class = Str::singular(Str::lower($class));
        $directory = Package::path($package).'models'.DS;
        $file = $directory.$class.'.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (File::exists($file)) {
            echo 'Model already exists: '.$display.'   (skipped)';
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package).Str::classify($class),
                'stub_table' => Str::plural($class),
            ];

            File::put($file, $this->stub($class, $replace, 'model'));

            echo 'Created model: '.$display;
        }

        return $file;
    }

    /**
     * Buat file command baru.
     *
     * <code>
     *
     *      // Buat file command baru.
     *      php rakit make:command dashboard
     *
     *      // Buat file command baru di paket 'admin'.
     *      php rakit make:command admin::dashboard
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function command($arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (strpos($arguments[0], '/')) {
            throw new \Exception('Cannot create command inside subdirectory.');
        }

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (! Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_command' === Str::lower($class)) {
            throw new \Exception('Please choose another name for command.');
        }

        $class = Str::replace_last('_command', '', Str::lower($class));
        $directory = Package::path($package).'commands'.DS;
        $file = $directory.$class.'.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (File::exists($file)) {
            echo 'Command already exists: '.$display.'   (skipped)';
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package).Str::classify($class).'_Command',
            ];

            File::put($file, $this->stub($class, $replace, 'command'));

            echo 'Created command: '.$display;
        }

        return $file;
    }

    /**
     * Buat file unit test.
     *
     * <code>
     *
     *      // Buat file unit test baru.
     *      php rakit make:test foobar
     *
     *      // Buat file unit test baru di paket 'admin'.
     *      php rakit make:test admin::foobar
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function test($arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes(Str::replace_last('.test', '', $arguments[0]));

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (! Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        $root = Package::path($package).'tests'.DS;
        $file = $root.str_replace('/', DS, $this->slashes($class)).'.test.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (File::exists($file)) {
            throw new \Exception(sprintf('Test file already exists: %s', $display));
        }

        $directory = Str::replace_last(basename($file), '', $file);
        $this->makedir($directory);

        $namespace = Str::studly($package);
        $replace = [
                'stub_class' => Str::studly($class).'Test',
                '// <namespace-declaration-placeholder>' => 'namespace '.$namespace.'\Tests;',
                '<test-group-placeholder>' => Str::lower($package),
            ];

        File::put($file, $this->stub($class, $replace, 'test'));

        echo 'Created test file: '.$display;

        return $file;
    }

    /**
     * Ambil konten file stub dan replace placeholdernya.
     *
     * @param string $class
     * @param array  $replace
     * @param string $stub
     *
     * @return string
     */
    protected function stub($class, array $replace = [], $stub)
    {
        $stub = File::get(path('system').'console'.DS.'commands'.DS.'stubs'.DS.$stub.'.stub');
        $class = Str::classify($class);

        foreach ($replace as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }

    /**
     * Buat drektori jika belum ada.
     *
     * @param string $directory
     *
     * @return bool
     */
    protected function makedir($directory)
    {
        if (! File::exists($directory)) {
            File::mkdir($directory, 0777);
            File::put($directory.'index.html', 'No direct script access.'.PHP_EOL);
        }

        return true;
    }

    /**
     * Normalisasi directory separator.
     *
     * @param string $path
     *
     * @return string
     */
    protected function slashes($path)
    {
        return str_replace([DS, '.'], '/', $path);
    }
}
