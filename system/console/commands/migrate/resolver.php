<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct access.');

use System\Package;
use System\Str;

class Resolver
{
    /**
     * Contains instance of database migration.
     *
     * @var Database
     */
    protected $database;

    /**
     * Create new migration resolver instance.
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
     * Resolve all migrations belonging to a package.
     *
     * @param array $arguments
     *
     * @return array
     */
    public function outstanding(array $arguments = [])
    {
        $arguments = empty($arguments) ? array_merge(Package::names(), ['application']) : $arguments;
        $migrations = [];

        foreach ($arguments as $package) {
            $ran = $this->database->ran($package);
            $files = $this->migrations($package);

            foreach ($files as $key => $name) {
                if (!in_array($name, $ran)) {
                    $migrations[] = compact('package', 'name');
                }
            }
        }

        return $this->resolve($migrations);
    }

    /**
     * Resolve array of last batch migrations.
     *
     * @return array
     */
    public function last()
    {
        return $this->resolve($this->database->last());
    }

    /**
     * Resolve array of instance migrations.
     *
     * @param array $migrations
     *
     * @return array
     */
    protected function resolve(array $migrations)
    {
        $instances = [];

        foreach ($migrations as $migration) {
            $migration = (array) $migration;
            $package = (string) $migration['package'];
            $name = (string) $migration['name'];
            $path = Package::path($package) . 'migrations' . DS;

            require_once $path . $name . '.php';

            $class = Package::class_prefix($package) . Str::classify(substr($name, 18));
            $migration = new $class();
            $instances[] = compact('package', 'name', 'migration');
        }

        usort($instances, function ($left, $right) {
            return strcmp($left['name'], $right['name']);
        });

        return $instances;
    }

    /**
     * Get array of migration files belonging to a package.
     *
     * @param string $package
     *
     * @return array
     */
    protected function migrations($package)
    {
        $files = glob(Package::path($package) . 'migrations' . DS . '*_*.php');

        if (false === $files) {
            return [];
        }

        foreach ($files as &$file) {
            $file = Str::replace_last('.php', '', basename((string) $file));
        }

        sort($files);

        return $files;
    }
}
