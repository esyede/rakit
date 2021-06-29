<?php

namespace System\Console\Commands\Migrate;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\Storage;
use System\Package;
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
    public function run($arguments = [])
    {
        // Buat otomatis tabel migrasi jika belum ada.
        if (! Schema::has_table('rakit_migrations')) {
            $this->install();
        }

        $package = (0 === count($arguments)) ? null : Arr::get($arguments, 0);
        $this->migrate($package);
    }

    /**
     * Jalankan migrasi milik sebuah paket.
     *
     * @param string $package
     * @param int    $version
     *
     * @return void
     */
    public function migrate($package = null, $version = null)
    {
        $migrations = $this->resolver->outstanding($package);
        $total = count($migrations);

        if (0 === $total) {
            echo 'No outstanding migrations.';
            return;
        }

        $batch = $this->database->batch() + 1;

        echo 'Processing '.$total.' migrations...'.PHP_EOL;

        foreach ($migrations as $migration) {
            $file = $this->display($migration);

            echo 'Migrating: '.$file.PHP_EOL;
            $migration['migration']->up();
            echo 'Migrated:  '.$file.PHP_EOL;

            $this->database->log($migration['package'], $migration['name'], $batch);
        }
    }

    /**
     * Rollback perintah migrasi terbaru.
     *
     * @param array $packages
     *
     * @return bool
     */
    public function rollback($packages = [])
    {
        $packages = is_array($packages) ? $packages : [$packages];
        $migrations = $this->resolver->last();

        if (is_array($packages) && count($packages) > 0) {
            $migrations = array_filter($migrations, function ($migration) use ($packages) {
                return in_array($migration['package'], $packages);
            });
        }

        if (0 === count($migrations)) {
            echo 'Nothing to rollback.'.PHP_EOL;
            return false;
        }

        $migrations = array_reverse($migrations);

        foreach ($migrations as $migration) {
            $file = $this->display($migration);

            echo 'Rolling back: '.$file.PHP_EOL;
            $migration['migration']->down();
            echo 'Rolled back:  '.$file.PHP_EOL;

            $this->database->delete($migration['package'], $migration['name']);
        }

        return true;
    }

    /**
     * Rollback seluruh migrasi yang pernah dijalankan.
     *
     * @param array $packages
     *
     * @return void
     */
    public function reset($packages = [])
    {
        while ($this->rollback($packages)) {
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
    public function rebuild()
    {
        $this->reset();
        echo PHP_EOL;
        $this->migrate();
        echo 'The database was successfully rebuilt'.PHP_EOL;
    }

    /**
     * Buat tabel untuk pencatatan migrasi database.
     *
     * @return void
     */
    public function install()
    {
        Schema::create('rakit_migrations', function ($table) {
            $table->string('package', 50);
            $table->string('name', 200);
            $table->integer('batch');
            $table->primary(['package', 'name']);
        });

        echo 'Migration table created successfully.'.PHP_EOL;
    }

    /**
     * Buat sebuah file migrasi.
     *
     * @param array $arguments
     *
     * @return string
     */
    public function make($arguments = [])
    {
        if (0 === count($arguments)) {
            throw new \Exception('I need to know what to name the migration.');
        }

        list($package, $migration) = Package::parse($arguments[0]);

        $prefix = date('Y_m_d_His');
        $path = Package::path($package).'migrations'.DS;

        if (! is_dir($path)) {
            Storage::mkdir($path);
        }

        $file = $path.$prefix.'_'.$migration.'.php';
        Storage::put($file, $this->stub($package, $migration));

        echo 'Created migration: '.$prefix.'_'.$migration;

        return $file;
    }

    /**
     * Ambil stub migrasi database dan sesuaikan nama kelasnya.
     *
     * @param string $package
     * @param string $migration
     *
     * @return string
     */
    protected function stub($package, $migration)
    {
        $stub = Storage::get(path('system').'console'.DS.'commands'.DS.'stubs'.DS.'migrate.stub');
        $prefix = Package::class_prefix($package);
        $class = $prefix.Str::classify($migration);

        return str_replace('stub_class', $class, $stub);
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
        return $migration['package'].'/'.$migration['name'];
    }
}
