<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct script access.');

use System\Config;
use System\Container;
use System\Storage;
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
            }
            if ($config['logging']) {
                Log::error('Please give at least one job name to execute!');
            }

            return false;
        }

        foreach ($names as $name) {
            \System\Job::run($name);
        }
    }

    /**
     * Jalankan semua job.
     *
     * @return void
     */
    public function runall()
    {
        \System\Job::runall();
    }

    /**
     * Buat tabel job.
     *
     * @return void
     */
    public function table()
    {
        $make = Container::resolve('command: make');

        $jobs = Config::get('job.table', 'jobs');
        $failed = Config::get('job.failed_table', 'failed_jobs');

        $migration1 = $make->migration(['create_'.$jobs. '_table']);
        $migration2 = $make->migration(['create_'.$failed.'_table']);

        $stub1 = __DIR__.DS.'stubs'.DS.'jobs.stub';
        $stub2 = __DIR__.DS.'stubs'.DS.'failed_jobs.stub';

        Storage::put($migration1, Storage::get($stub1));
        Storage::put($migration2, Storage::get($stub2));

        echo PHP_EOL;
    }
}
