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

    /**
     * Keep running jobs as they become due, until stopped. Run it under a process
     * monitor (supervisor, systemd) that starts it again when it exits.
     *
     * Options, as job:runall: --queue=a,b --retries= --sleep= (ms between attempts);
     * and --idle=3 (seconds to wait on an empty queue), --max-jobs=0,
     * --max-time=0 (seconds), --memory=128 (MB), where 0 means no limit.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function work(array $arguments = [])
    {
        $queues = get_cli_option('queue');
        $queues = $queues ? array_map('trim', explode(',', $queues)) : null;
        $retries = (int) get_cli_option('retries', 0);
        $sleep_ms = (int) get_cli_option('sleep', 0);
        $idle = max(1, (int) get_cli_option('idle', 3));
        $max_jobs = (int) get_cli_option('max-jobs', 0);
        $max_time = (int) get_cli_option('max-time', 0);
        $memory = (int) get_cli_option('memory', 128);
        $started = time();
        $done = 0;
        $stop = false;

        if (function_exists('pcntl_signal')) {
            $handler = function () use (&$stop) {
                $stop = true;
            };

            pcntl_signal(SIGTERM, $handler);
            pcntl_signal(SIGINT, $handler);
        }

        echo $this->info('Working jobs'.($queues ? ' from queues: '.implode(', ', $queues) : '').'. Stop with Ctrl+C.');

        while (! $stop) {
            $driver = \System\Job::driver();
            $driver->runall($retries, $sleep_ms, $queues);
            $done += $driver->processed;

            if (class_exists('System\Database\Connection', false)) {
                \System\Database\Connection::$queries = [];
            }

            if (function_exists('pcntl_signal_dispatch')) {
                pcntl_signal_dispatch();
            }

            if ($stop) {
                break;
            }

            if ($max_jobs > 0 && $done >= $max_jobs) {
                echo $this->info(sprintf('Stopping: %d jobs processed.', $done));
                break;
            }

            if ($max_time > 0 && time() - $started >= $max_time) {
                echo $this->info('Stopping: time limit reached.');
                break;
            }

            if ($memory > 0 && memory_get_usage(true) >= $memory * 1048576) {
                echo $this->warning('Stopping: memory limit reached.');
                break;
            }

            if (0 === $driver->processed) {
                sleep($idle);

                if (function_exists('pcntl_signal_dispatch')) {
                    pcntl_signal_dispatch();
                }
            }
        }
    }

    /**
     * List the failed jobs.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function failed(array $arguments = [])
    {
        $failures = \System\Job::driver()->failures();

        if (empty($failures)) {
            echo $this->info('No failed jobs.');
            return;
        }

        $lines = str_pad('ID', 28).' | '.str_pad('Job', 24).' | '.str_pad('Queue', 10).' | '.str_pad('Failed at', 19).' | Exception'.PHP_EOL;

        foreach ($failures as $job) {
            $lines .= str_pad((string) $job['id'], 28).' | '.str_pad($job['name'], 24).' | '
                .str_pad($job['queue'], 10).' | '.str_pad((string) $job['failed_at'], 19).' | '.$job['exception'].PHP_EOL;
        }

        echo $this->info($lines);
    }

    /**
     * Put failed jobs back on their queue: 'job:retry <id>' or 'job:retry all'.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function retry(array $arguments = [])
    {
        if (empty($arguments)) {
            echo $this->error("Give a failed job ID, or 'all'.");
            return;
        }

        $count = 0;

        foreach ($arguments as $id) {
            $count += \System\Job::driver()->retry($id);
        }

        echo $this->info(sprintf('%d job(s) put back on their queue.', $count));
    }
}
