<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Database\Schema;
use System\Package;
use System\Config;
use System\Log;

class Job extends Command
{
    /**
     * Jalankan satu atau beberapa job berdasarkan nama.
     *
     * @param string|array $names
     *
     * @return void
     */
    public function run($names = [])
    {
        $config = Config::get('job');
        $names = is_array($names) ? $names : func_get_args();

        if (empty($names)) {
            if (Request::cli()) {
                echo 'Please give at least one job name to execute!'.PHP_EOL;
                exit;
            } else {
                if ($config['logging']) {
                    Log::error('Please give at least one job name to execute!');
                }

                return false;
            }
        }

        foreach ($names as $name) {
            Job::run($name);
        }
    }

    /**
     * Jalankan semua job.
     *
     * @return void
     */
    public function runall()
    {
        Job::runall();
    }

    /**
     * Buat tabel job.
     *
     * @return void
     */
    public function table()
    {
        $make = Container::resolve('command: make');
        $migrator = Container::resolve('command: migrate');

        $migration = $make->migration(['create_jobs_table']);
        $stub = __DIR__.DS.'stubs'.DS.'job.stub';

        Storage::put($migration, Storage::get($stub));

        $this->driver('database');

        echo PHP_EOL;

        $migrator->run();
    }
}
