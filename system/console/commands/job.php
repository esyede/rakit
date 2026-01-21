<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
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
}
