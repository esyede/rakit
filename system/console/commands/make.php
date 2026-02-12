<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Carbon;
use System\Storage;
use System\Package;

class Make extends Command
{
    /**
     * Make a new controller.
     *
     * <code>
     *
     *      // Make a new controller.
     *      php rakit make:controller dashboard
     *
     *      // Make a new controller in a subdirectory.
     *      php rakit make:controller admin.home
     *
     *      // Make a new controller in a package.
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
            echo $this->warning('Controller already exists: ' . $display . '   (skipped)');
        } else {
            $directory = Str::replace_last(basename($file), '', $file);
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
            ];

            Storage::put($file, $this->stub_general($class, 'controller', $replace));

            echo $this->info('Created controller: ' . $display);
        }

        return $file;
    }

    /**
     * Make a new resource controller.
     *
     * <code>
     *
     *      // Make a new resource controller.
     *      php rakit make:resource dashboard
     *
     *      // Make a new resource controller in a subdirectory.
     *      php rakit make:resource admin.home
     *
     *      // Make a new resource controller in a package.
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
            echo $this->warning('Controller already exists: ' . $display . '   (skipped)');
        } else {
            $directory = Str::replace_last(basename($file), '', $file);
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
                'stub_uri' => Str::lower((($package === DEFAULT_PACKAGE) ? '' : $package . '/') . $class),
            ];

            Storage::put($file, $this->stub_general($class, 'resource', $replace));

            echo $this->info('Created resource controller: ' . $display);
        }

        return $file;
    }

    /**
     * Make a new model.
     *
     * <code>
     *
     *      // Make a new model.
     *      php rakit make:model user
     *
     *      // Make a new model in a package.
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
            echo $this->warning('Model already exists: ' . $display . '   (skipped)');
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class),
                'stub_table' => Str::plural($class),
            ];

            Storage::put($file, $this->stub_general($class, 'model', $replace));

            echo $this->info('Created model: ' . $display);
        }

        return $file;
    }

    /**
     * Make a new migration.
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

        $prefix = Carbon::now()->format('Y_m_d_His');
        $path = Package::path($package) . 'migrations' . DS;

        if (!is_dir($path)) {
            Storage::mkdir($path);
        }

        if (class_exists('\\' . Str::classify($migration))) {
            throw new \Exception(sprintf('Migration class already exists: %s', Str::classify($migration)));
        }

        $file = $path . $prefix . '_' . $migration . '.php';
        Storage::put($file, $this->stub_migration($package, $migration));

        echo $this->info('Created migration: ' . $prefix . '_' . $migration);
        return $file;
    }

    /**
     * Make a new command.
     *
     * <code>
     *
     *      // Make a new command.
     *      php rakit make:command dashboard
     *
     *      // Make a new command in a package.
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
            echo $this->warning('Command already exists: ' . $display . '   (skipped)');
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class) . '_Command',
            ];

            Storage::put($file, $this->stub_general($class, 'command', $replace));

            echo $this->info('Created command: ' . $display);
        }

        return $file;
    }

    /**
     * Make a new job.
     *
     * <code>
     *
     *      // Make a new job.
     *      php rakit make:job dashboard
     *
     *      // Make a new job in a package.
     *      php rakit make:job admin::dashboard
     *
     * </code>
     *
     * @param array $arguments
     *
     * @return void
     */
    public function job(array $arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the file to be make.');
        }

        $arguments[0] = $this->slashes($arguments[0]);

        if (false !== strpos($arguments[0], '/')) {
            throw new \Exception('Cannot create job inside subdirectory.');
        }

        if (false !== strstr($arguments[0], '::')) {
            list($package, $class) = Package::parse($arguments[0]);
        } else {
            list($package, $class) = [DEFAULT_PACKAGE, $arguments[0]];
        }

        if (!Package::exists($package)) {
            throw new \Exception(sprintf('Targetted package is not installed: %s', $package));
        }

        if ('_job' === Str::lower($class)) {
            throw new \Exception('Please choose another name for job.');
        }

        $class = Str::replace_last('_job', '', Str::lower($class));
        $directory = Package::path($package) . 'jobs' . DS;
        $file = $directory . $class . '.php';
        $display = Str::replace_first(path('base'), '', $file);

        if (Storage::isfile($file)) {
            echo $this->warning('Job already exists: ' . $display . '   (skipped)');
        } else {
            $this->makedir($directory);

            $replace = [
                'stub_class' => Package::class_prefix($package) . Str::classify($class) . '_Job',
            ];

            Storage::put($file, $this->stub_general($class, 'job', $replace));

            echo $this->info('Created job: ' . $display);
        }

        return $file;
    }

    /**
     * Generate auth scaffolding (login, register, forgot password).
     * (NOTE: this must be run on a fresh project).
     *
     * <code>
     *
     *      // Generate auth scaffolding.
     *      php rakit make:auth
     *
     *      // Next, run the database migration.
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

        echo $this->info('Authentication scaffolding generated successfully');
        return true;
    }

    /**
     * Make a new test.
     *
     * <code>
     *
     *      // Make a new test.
     *      php rakit make:test foobar
     *
     *      // Make a new test in the 'admin' package.
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

        echo $this->info('Created test file: ' . $display);

        return $file;
    }

    /**
     * Get stub content and replace placeholders (for general files).
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
     * Get stub content and replace placeholders (for migration files).
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
     * Make a directory if it doesn't exist.
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
     * Normalize directory separator.
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
