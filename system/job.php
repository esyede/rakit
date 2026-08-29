<?php

namespace System;

defined('DS') or exit('No direct access.');

class Job
{
    /**
     * Contains job drivers.
     *
     * @var array
     */
    public static $drivers = [];

    /**
     * Contains third-party job driver registrar.
     *
     * @var array
     */
    public static $registrar = [];

    /**
     * Contains flag for auto-discovery.
     *
     * @var bool
     */
    protected static $discovered = false;

    /**
     * Dispatch job to queue.
     *
     * @param string                                   $name
     * @param array                                    $payload
     * @param string|\System\Carbon|\DateTime|int|null $dispatch_at
     *
     * @return \System\Job\Pending
     */
    public static function dispatch($name, array $payload = [], $dispatch_at = null)
    {
        static::auto_discover();

        // Normalize dispatch_at
        if ($dispatch_at instanceof \DateTime) {
            $dispatch_at = $dispatch_at->format('Y-m-d H:i:s');
        } elseif (is_numeric($dispatch_at)) {
            $dispatch_at = date('Y-m-d H:i:s', (int) $dispatch_at);
        } elseif ($dispatch_at instanceof Carbon) {
            $dispatch_at = $dispatch_at->format('Y-m-d H:i:s');
        } elseif (is_string($dispatch_at)) {
            $dispatch_at = Carbon::parse($dispatch_at)->format('Y-m-d H:i:s');
        } elseif (is_null($dispatch_at)) {
            $dispatch_at = Carbon::now()->format('Y-m-d H:i:s');
        }

        return new Job\Pending($name, $payload, $dispatch_at);
    }

    /**
     * Auto-discover and register job listeners.
     */
    protected static function auto_discover()
    {
        if (static::$discovered) {
            return;
        }

        static::$discovered = true;
        static::load_job_classes();

        Hook::listen('rakit.jobs.process', function ($data) {
            $job = is_array($data) ? (object) $data : $data;

            if (isset($job->name) && isset($job->payloads)) {
                $payloads = is_string($job->payloads) ? unserialize($job->payloads) : $job->payloads;
                Hook::fire('rakit.jobs.run: '.$job->name, $payloads);
            }
        });
    }

    /**
     * Load and register job classes.
     */
    protected static function load_job_classes()
    {
        $directories = [];

        if (is_dir($default = path('app').'jobs'.DS)) {
            $directories[DEFAULT_PACKAGE] = $default;
        }

        $packages = Package::names();

        foreach ($packages as $package) {
            if (is_dir($directory = Package::path($package).'jobs'.DS)) {
                $directories[$package] = $directory;
            }
        }

        foreach ($directories as $package => $path) {
            $files = glob($path.'*.php');

            foreach ($files as $file) {
                $prefix = Package::class_prefix($package);
                $class = $prefix.Str::classify(basename($file, '.php')).'_Job';

                // The file is read here rather than left to the autoloader,
                // whose PSR-0 convention would look for jobs/mailing/job.php
                // instead of the jobs/mailing.php the name points at.
                if (! class_exists($class, false)) {
                    require_once $file;
                }

                if (class_exists($class, false)) {
                    if ((new \ReflectionClass($class))->isSubclassOf('\System\Job\Jobable')) {
                        Hook::listen('rakit.jobs.run: '.$class::name(), function ($payload) use ($class) {
                            $class::execute($payload);
                        });
                    }
                }
            }
        }
    }

    /**
     * Get the job driver instance.
     *
     * @param string $driver
     *
     * @return \System\Job\Drivers\Driver
     */
    public static function driver($driver = null)
    {
        $driver = is_null($driver) ? Config::get('job.driver') : $driver;

        if (! isset(static::$drivers[$driver])) {
            static::$drivers[$driver] = static::factory($driver);
        }

        return static::$drivers[$driver];
    }

    /**
     * Create a new job driver instance.
     *
     * @param string $driver
     *
     * @return \System\Job\Drivers\Driver
     */
    protected static function factory($driver)
    {
        if (isset(static::$registrar[$driver])) {
            $resolver = static::$registrar[$driver];
            return $resolver();
        }

        switch ($driver) {
            case 'file':      return new Job\Drivers\File(path('storage').'jobs'.DS);
            case 'database':  return new Job\Drivers\Database();
            case 'redis':     return new Job\Drivers\Redis(Redis::db());
            case 'memcached': return new Job\Drivers\Memcached(Memcached::connection());
            default:          throw new \Exception(sprintf('Unsupported job driver: %s', $driver));
        }
    }

    /**
     * Register a new job driver instance.
     *
     * @param string   $driver
     * @param \Closure $resolver
     */
    public static function extend($driver, \Closure $resolver)
    {
        static::$registrar[$driver] = $resolver;
    }

    /**
     * Magic method for calling methods on the default job driver.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public static function __callStatic($method, array $parameters)
    {
        return call_user_func_array([static::driver(), $method], $parameters);
    }
}
