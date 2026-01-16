<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Container;
use System\Storage;
use System\Request;
use System\Log;

class Job extends Command
{
    /**
     * Jalankan satu atau beberapa job berdasarkan nama.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $config = Config::get('job');
        $arguments = is_array($arguments) ? $arguments : [$arguments];

        if (empty($arguments)) {
            if (Request::cli()) {
                $this->error('Please give at least one job name to execute!');
                exit;
            }

            if ($config['logging']) {
                Log::error('Please give at least one job name to execute!');
            }

            return false;
        }

        foreach ($arguments as $name) {
            \System\Job::run($name);
        }
    }

    /**
     * Jalankan semua job.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function runall(array $arguments = [])
    {
        $retries = 1;
        $sleep = 0;
        $queues = null;

        // Parse arguments
        foreach ($arguments as $arg) {
            if (strpos($arg, '--retries=') === 0) {
                $retries = (int) substr($arg, 10);
            } elseif (strpos($arg, '--sleep=') === 0) {
                $sleep = (int) substr($arg, 8);
            } elseif (strpos($arg, '--queue=') === 0) {
                $queueStr = substr($arg, 8);
                $queues = array_map('trim', explode(',', $queueStr));
            }
        }

        if ($queues) {
            $this->info('Running jobs from queues: ' . implode(', ', $queues));
        } else {
            $this->info('Running all jobs from all queues');
        }

        \System\Job::runall($retries, $sleep, $queues);
    }

    /**
     * Buat tabel job.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function table(array $arguments = [])
    {
        $make = Container::resolve('command: make');

        $jobs = Config::get('job.table', 'rakit_jobs');
        $failed = Config::get('job.failed_table', 'rakit_failed_jobs');

        $migration1 = $make->migration(['create_' . $jobs . '_table']);
        $migration2 = $make->migration(['create_' . $failed . '_table']);

        $stub1 = Storage::get(__DIR__ . DS . 'stubs' . DS . 'jobs.stub');
        $stub2 = Storage::get(__DIR__ . DS . 'stubs' . DS . 'failed_jobs.stub');

        Storage::put($migration1, str_replace('jobs_table_name', $jobs, $stub1));
        Storage::put($migration2, str_replace('failed_jobs_table_name', $jobs, $stub2));

        echo PHP_EOL;
    }
}
