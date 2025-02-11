<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct access.');

use System\Console\Commands\Command;
use System\Database\Schema;

class Migrator extends Command
{
    /**
     * Berisi instance migration resolver.
     *
     * @var Resolver
     */
    protected $resolver;

    /**
     * Berisi instance database migrasi.
     *
     * @var Database
     */
    protected $database;

    /**
     * Buat instance migrator baru.
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
     * Jalankan command migrasi database.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        // Buat otomatis tabel migrasi jika belum ada.
        if (!Schema::has_table('rakit_migrations')) {
            $this->install();
        }

        $arguments = empty($arguments) ? [] : $arguments[0];
        $this->migrate($arguments);
    }

    /**
     * Jalankan migrasi milik sebuah paket.
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
     * Rollback perintah migrasi terbaru.
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
     * Rollback seluruh migrasi yang pernah dijalankan.
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
     * Reset dan jalankan ulang seluruh migrasi database.
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
     * Buat tabel untuk pencatatan migrasi database.
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
     * Ambil paket dan nama migrasi (untuk tampilan saja).
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
