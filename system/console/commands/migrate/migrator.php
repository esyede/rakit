<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct access.');

use System\Console\Commands\Command;
use System\Database\Schema;

class Migrator extends Command
{
    /**
     * Contains the migration resolver instance.
     *
     * @var Resolver
     */
    protected $resolver;

    /**
     * Contains the database migration instance.
     *
     * @var Database
     */
    protected $database;

    /**
     * Creates a new migrator instance.
     *
     * @param Resolver $resolver
     * @param Database $database
     *
     * @return void
     */
    public function __construct(Resolver $resolver, Database $database)
    {
        $this->resolver = $resolver;
        $this->database = $database;
    }

    /**
     * Runs the database migration command.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        // Create the migrations table automatically if it doesn't exist.
        if (!Schema::has_table('rakit_migrations')) {
            $this->install();
        }

        $arguments = empty($arguments) ? [] : $arguments[0];
        $this->migrate($arguments);
    }

    /**
     * Runs the migrations for a given package.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function migrate(array $arguments = [])
    {
        $migrations = $this->resolver->outstanding($arguments);
        $total = count($migrations);

        if (0 === $total) {
            echo $this->info('Done. No outstanding migrations.');
            return;
        }

        $batch = $this->database->batch() + 1;

        echo $this->warning('Processing ' . $total . ' migrations...');

        foreach ($migrations as $migration) {
            $file = $this->display($migration);

            echo 'Migrating : ' . $file . PHP_EOL;
            $migration['migration']->up();
            echo $this->info('Migrated  : ' . $file);

            $this->database->log($migration['package'], $migration['name'], $batch);
        }
    }

    /**
     * Rollback the last migration.
     *
     * @param array $arguments
     *
     * @return bool
     */
    public function rollback(array $arguments = [])
    {
        $migrations = $this->resolver->last();

        if (count($arguments) > 0) {
            $migrations = array_filter($migrations, function ($migration) use ($arguments) {
                return in_array($migration['package'], $arguments);
            });
        }

        if (0 === count($migrations)) {
            echo $this->info('Done. Nothing to rollback.');
            return false;
        }

        $migrations = array_reverse($migrations);

        foreach ($migrations as $migration) {
            $file = $this->display($migration);

            echo 'Rolling back : ' . $file . PHP_EOL;
            $migration['migration']->down();
            echo $this->info('Rolled back  : ' . $file);

            $this->database->delete($migration['package'], $migration['name']);
        }

        return true;
    }

    /**
     * Rollback all migrations that have been run.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function reset(array $arguments = [])
    {
        while ($this->rollback($arguments)) {
            // Rollback semuanya..
        }
    }

    /**
     * Reset then run all database migrations.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function refresh(array $arguments = [])
    {
        $this->reset();
        echo PHP_EOL;
        $this->migrate();
        echo $this->info('Done. The database was successfully refreshed.');
    }

    /**
     * Create the table for tracking database migrations.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function install(array $arguments = [])
    {
        Schema::create('rakit_migrations', function ($table) {
            $table->string('package', 50);
            $table->string('name', 200);
            $table->integer('batch');
            $table->primary(['package', 'name']);
        });

        echo $this->info('Migration table created successfully.');
    }

    /**
     * Get the package and migration name (for display purposes only).
     *
     * @param array $migration
     *
     * @return string
     */
    protected function display($migration)
    {
        return $migration['package'] . '/' . $migration['name'];
    }
}
