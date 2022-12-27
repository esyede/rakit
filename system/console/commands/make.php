<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Storage;
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
    public function controller(array $arguments = [])
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

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_controller' === Str::lower($class)) {
            throw new \Exception('Please choose another name for controller.');
        }

        $class = Str::replace_last('_controller', '', Str::lower($class));
        $root = Package::path($package) . 'controllers' . DS;
        $file = $root . str_replace('/', DS, $this->slashes($class)) . '.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            echo 'Controller already exists: ' . $display . '   (skipped)';
        } else {
            $directory = Str::replace_last(basename($file), '', $file);
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
            ];

            Storage::put($file, $this->stub_general($class, 'controller', $replace));

            echo 'Created controller: ' . $display;
        }

        return $file;
    }

    /**
     * Buat file resource controller baru.
     *
     * <code>
     *
     *      // Buat file resource controller baru.
     *      php rakit make:resource dashboard
     *
     *      // Buat file resource controller baru didalam subdirektori.
     *      php rakit make:resource admin.home
     *
     *      // Buat file resource controller baru di paket 'admin'.
     *      php rakit make:resource admin::dashboard
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function resource(array $arguments = [])
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

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_controller' === Str::lower($class)) {
            throw new \Exception('Please choose another name for controller.');
        }

        $class = Str::replace_last('_controller', '', Str::lower($class));
        $root = Package::path($package) . 'controllers' . DS;
        $file = $root . str_replace('/', DS, $this->slashes($class)) . '.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            echo 'Controller already exists: ' . $display . '   (skipped)';
        } else {
            $directory = Str::replace_last(basename($file), '', $file);
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
                'stub_uri' => Str::lower((($package === DEFAULT_PACKAGE) ? '' : $package . '/') . $class),
            ];

            Storage::put($file, $this->stub_general($class, 'resource', $replace));

            echo 'Created resource controller: ' . $display;
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
    public function model(array $arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (false !== strpos($arguments[0], '/')) {
            throw new \Exception('Cannot create model inside subdirectory.');
        }

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        $class = Str::singular(Str::lower($class));
        $directory = Package::path($package) . 'models' . DS;
        $file = $directory . $class . '.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            echo 'Model already exists: ' . $display . '   (skipped)';
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
                'stub_table' => Str::plural($class),
            ];

            Storage::put($file, $this->stub_general($class, 'model', $replace));

            echo 'Created model: ' . $display;
        }

        return $file;
    }

    /**
     * Buat sebuah file migrasi.
     *
     * @param array $arguments
     *
     * @return string
     */
    public function migration(array $arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the migration.');
        }

        list($package, $migration) = Package::parse($arguments[0]);

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        $prefix = date('Y_m_d_His');
        $path = Package::path($package) . 'migrations' . DS;

        if (!is_dir($path)) {
            Storage::mkdir($path);
        }

        if (class_exists('\\' . Str::classify($migration))) {
            throw new \Exception(sprintf('Migration class already exists: %s', Str::classify($migration)));
        }

        $file = $path . $prefix . '_' . $migration . '.php';
        Storage::put($file, $this->stub_migration($package, $migration));

        echo 'Created migration: ' . $prefix . '_' . $migration . PHP_EOL;
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
    public function command(array $arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (false !== strpos($arguments[0], '/')) {
            throw new \Exception('Cannot create command inside subdirectory.');
        }

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_command' === Str::lower($class)) {
            throw new \Exception('Please choose another name for command.');
        }

        $class = Str::replace_last('_command', '', Str::lower($class));
        $directory = Package::path($package) . 'commands' . DS;
        $file = $directory . $class . '.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            echo 'Command already exists: ' . $display . '   (skipped)';
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class) . '_Command',
            ];

            Storage::put($file, $this->stub_general($class, 'command', $replace));

            echo 'Created command: ' . $display;
        }

        return $file;
    }

    /**
     * Buat auth scaffolding (login, register, forgot password).
     * (NOTE: harus dijalankan pada keadaan fresh project).
     *
     * <code>
     *
     *      // Buat auth scaffolding.
     *      php rakit make:auth
     *
     *      // Selanjutkan jalankan migrasi database.
     *      php rakit migrate
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function auth(array $arguments = [])
    {
        $directories = [
            'views' . DS . 'layouts',
            'views' . DS . 'auth' . DS . 'email',
            'views' . DS . 'auth' . DS . 'passwords',
            'controllers' . DS . 'auth',
        ];

        foreach ($directories as $directory) {
            if (!is_dir(path('app') . $directory)) {
                mkdir(path('app') . $directory, 0755, true);
            }
        }

        $views = [
            'auth' . DS . 'login.stub' => 'auth' . DS . 'login.blade.php',
            'auth' . DS . 'register.stub' => 'auth' . DS . 'register.blade.php',
            'auth' . DS . 'passwords' . DS . 'email.stub' => 'auth' . DS . 'passwords' . DS . 'email.blade.php',
            'auth' . DS . 'passwords' . DS . 'reset.stub' => 'auth' . DS . 'passwords' . DS . 'reset.blade.php',
            'email' . DS . 'reset.stub' => 'auth' . DS . 'email' . DS . 'reset.blade.php',
            'layouts' . DS . 'app.stub' => 'layouts' . DS . 'app.blade.php',
            'dashboard.stub' => 'dashboard.blade.php',
        ];

        foreach ($views as $key => $value) {
            copy(
                __DIR__ . DS . 'stubs' . DS . 'auth' . DS . 'views' . DS . $key,
                path('app') . 'views' . DS . $value
            );
        }

        $controllers = [
            'dashboard.stub' => 'dashboard.php',
            'login.stub' => 'auth' . DS . 'login.php',
            'register.stub' => 'auth' . DS . 'register.php',
            'password.stub' => 'auth' . DS . 'password.php',
        ];

        foreach ($controllers as $key => $value) {
            file_put_contents(
                path('app') . 'controllers' . DS . $value,
                file_get_contents(__DIR__ . DS . 'stubs' . DS . 'auth' . DS . 'controllers' . DS . $key)
            );
        }

        file_put_contents(
            path('app') . DS . 'routes.php',
            file_get_contents(__DIR__ . DS . 'stubs' . DS . 'auth' . DS . 'routes.stub'),
            LOCK_EX | FILE_APPEND
        );

        echo 'Authentication scaffolding generated successfully';
        return true;
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
    public function test(array $arguments = [])
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

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        $root = Package::path($package) . 'tests' . DS;
        $file = $root . str_replace('/', DS, $this->slashes($class)) . '.test.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            throw new \Exception(sprintf('Test file already exists: %s', $display));
        }

        $directory = Str::replace_last(basename($file), '', $file);
        $this->makedir($directory);

        $namespace = Str::studly($package);
        $replace = [
            'stub_class' => Str::studly($class) . 'Test',
            '// <namespace-declaration-placeholder>' => 'namespace ' . $namespace . '\Tests;',
            '<test-group-placeholder>' => Str::lower($package),
        ];

        Storage::put($file, $this->stub_general($class, 'test', $replace));

        echo 'Created test file: ' . $display;

        return $file;
    }

    /**
     * Ambil konten file stub dan replace placeholdernya (untuk file - file umum).
     *
     * @param string $class
     * @param string $stub
     * @param array  $replace
     *
     * @return string
     */
    protected function stub_general($class, $stub, array $replace = [])
    {
        $stub = Storage::get(path('system') . 'console' . DS . 'commands' . DS . 'stubs' . DS . $stub . '.stub');
        $class = Str::classify($class);

        foreach ($replace as $key => $value) {
            $stub = str_replace($key, $value, $stub);
        }

        return $stub;
    }

    /**
     * Ambil konten file stub dan replace placeholdernya (khusus file migrasi).
     *
     * @param string $package
     * @param string $migration
     *
     * @return string
     */
    protected function stub_migration($package, $migration)
    {
        $stub = Storage::get(path('system') . 'console' . DS . 'commands' . DS . 'stubs' . DS . 'migrate.stub');
        $prefix = Package::class_prefix($package);
        $class = $prefix . Str::classify($migration);

        return str_replace('stub_class', $class, $stub);
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
        if (!is_dir($directory)) {
            Storage::mkdir($directory, 0755);
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
