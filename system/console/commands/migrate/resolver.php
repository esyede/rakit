<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct script access.');

use System\Package;
use System\Str;

class Resolver
{
    /**
     * Berisi instance database migrasi.
     *
     * @var Database
     */
    protected $database;

    /**
     * Buat instance migration resolver baru.
     *
     * @param Database $database
     *
     * @return void
     */
    public function __construct(Database $database)
    {
        $this->database = $database;
    }

    /**
     * Resolve seluruh migrasi milik sebuah paket.
     *
     * @param string $package
     *
     * @return array
     */
    public function outstanding($package = null)
    {
        $packages = is_null($package) ? array_merge(Package::names(), ['application']) : [$package];
        $migrations = [];

        foreach ($packages as $package) {
            $ran = $this->database->ran($package);
            $files = $this->migrations($package);

            foreach ($files as $key => $name) {
                if (! in_array($name, $ran)) {
                    $migrations[] = compact('package', 'name');
                }
            }
        }

        return $this->resolve($migrations);
    }

    /**
     * Resolve array migrasi batch terakhir.
     *
     * @return array
     */
    public function last()
    {
        return $this->resolve($this->database->last());
    }

    /**
     * Resolve array instance migrasi.
     *
     * @param array $migrations
     *
     * @return array
     */
    protected function resolve(array $migrations)
    {
        $instances = [];

        foreach ($migrations as $migration) {
            $package = $migration['package'];
            $name = $migration['name'];
            $path = Package::path($package).'migrations'.DS;

            require_once $path.$name.'.php';

            $prefix = Package::class_prefix($package);
            $class = $prefix.Str::classify(substr($name, 18));
            $migration = new $class();
            $instances[] = compact('package', 'name', 'migration');
        }

        usort($instances, function ($left, $right) {
            return strcmp($left['name'], $right['name']);
        });

        return $instances;
    }

    /**
     * Ambil seluruh nama file migrasi milik sebuah paket.
     *
     * @param string $package
     *
     * @return array
     */
    protected function migrations($package)
    {
        $files = glob(Package::path($package).'migrations'.DS.'*_*.php');

        if (false === $files) {
            return [];
        }

        foreach ($files as &$file) {
            $file = Str::replace_last('.php', '', basename($file));
        }

        sort($files);

        return $files;
    }
}
