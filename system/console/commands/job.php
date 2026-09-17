<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Request;
use System\Log;

class Job extends Command
{
    /**
     * Run one or more jobs based on name.
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
                echo $this->error('Please give at least one job name to execute!');
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
     * Run all jobs.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function runall(array $arguments = [])
    {
        // Options never reach $arguments: the console takes every --option
        // out of the command line before the command is called.
        $retries = (int) get_cli_option('retries', 1);
        $sleep = (int) get_cli_option('sleep', 0);
        $queues = get_cli_option('queue');
        $queues = $queues ? array_map('trim', explode(',', $queues)) : null;

        if ($queues) {
            echo $this->info('Running jobs from queues: '.implode(', ', $queues));
        } else {
            echo $this->info('Running all jobs from all queues');
        }

        \System\Job::runall($retries, $sleep, $queues);
    }
}
