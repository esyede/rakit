<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Request;
use System\Hook;
use System\Log;
use System\Job;
use System\Job\Batch;

abstract class Driver
{
    /**
     * The number of jobs the last run() or runall() processed, succeeded or failed.
     *
     * @var int
     */
    public $processed = 0;

    /**
     * Add a new job to the queue.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     * @param string      $queue
     * @param bool        $without_overlapping
     *
     * @return bool
     */
    abstract public function add(
        $name,
        array $payloads = [],
        $scheduled_at = null,
        $queue = 'default',
        $without_overlapping = false
    );

    /**
     * Check if there is an overlapping job.
     *
     * @param string $name
     * @param string $queue
     *
     * @return bool
     */
    abstract public function has_overlapping($name, $queue = 'default');

    /**
     * Delete a job from the queue.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    abstract public function forget($name, $queue = null);

    /**
     * Run a specific job in the database.
     *
     * @param string      $name
     * @param int         $retries
     * @param int         $sleep_ms
     * @param string|null $queue
     *
     * @return bool
     */
    abstract public function run($name, $retries = 1, $sleep_ms = 0, $queue = null);

    /**
     * Run all available jobs in the database.
     *
     * @param int        $retries
     * @param int        $sleep_ms
     * @param array|null $queues
     *
     * @return bool
     */
    abstract public function runall($retries = 1, $sleep_ms = 0, $queues = null);

    /**
     * Claim a job so that no other worker runs it. FALSE when another one has it.
     * The claim lapses after retry_after seconds, so a job whose worker died runs
     * again instead of being lost.
     *
     * Drivers that implement run() and runall() themselves never reach this.
     *
     * @param mixed $job
     *
     * @return mixed
     */
    protected function claim($job)
    {
        throw new \LogicException(sprintf('%s does not implement claim().', get_class($this)));
    }

    /**
     * Remove a claimed job that succeeded.
     *
     * @param mixed $job
     */
    protected function complete($job)
    {
        throw new \LogicException(sprintf('%s does not implement complete().', get_class($this)));
    }

    /**
     * Store a claimed job that ran out of attempts as failed, and remove it.
     *
     * @param mixed      $job
     * @param \Throwable $e
     */
    protected function bury($job, $e)
    {
        throw new \LogicException(sprintf('%s does not implement bury().', get_class($this)));
    }

    /**
     * List the failed jobs: id, name, queue, failed_at and the exception's first line.
     *
     * @return array
     */
    public function failures()
    {
        throw new \LogicException(sprintf('%s does not list failed jobs.', get_class($this)));
    }

    /**
     * Put a failed job back on its queue, or every failed job with 'all'.
     *
     * @param string|int $id
     *
     * @return int The number of jobs put back.
     */
    public function retry($id)
    {
        throw new \LogicException(sprintf('%s does not retry failed jobs.', get_class($this)));
    }

    /**
     * Get the seconds a claim lasts: longer than the slowest job.
     *
     * @return int
     */
    protected function retry_after()
    {
        return max(1, (int) Config::get('job.retry_after', 90));
    }

    /**
     * Resolve the attempts and the pause between them: the given ones, or the config.
     *
     * @param int $retries
     * @param int $sleep_ms
     *
     * @return array
     */
    protected function attempts($retries, $sleep_ms)
    {
        return [
            (int) (($retries > 1) ? $retries : Config::get('job.max_retries', 1)),
            (int) (($sleep_ms > 0) ? $sleep_ms : Config::get('job.sleep_ms', 0)),
        ];
    }

    /**
     * Claim each job and run it with its attempts.
     *
     * @param array|\Traversable $jobs
     * @param int                $retries
     * @param int                $sleep_ms
     */
    protected function work($jobs, $retries, $sleep_ms)
    {
        foreach ($jobs as $job) {
            $claimed = $this->claim($job);

            if (false !== $claimed && ! is_null($claimed)) {
                $this->process($claimed, $retries, $sleep_ms);
            }
        }
    }

    /**
     * Run a claimed job: retry it up to its attempts, then bury it. A job class may
     * set its own public static $tries and $backoff (seconds between attempts).
     *
     * @param array|object $job
     * @param int          $retries
     * @param int          $sleep_ms
     *
     * @return bool
     */
    protected function process($job, $retries, $sleep_ms)
    {
        $name = static::field($job, 'name');
        $id = static::field($job, 'id');
        $class = static::job_class(static::field($job, 'payloads'));

        if ($class && ! is_null($class::$tries)) {
            $retries = (int) $class::$tries;
        }

        if ($class && ! is_null($class::$backoff)) {
            $sleep_ms = (int) $class::$backoff * 1000;
        }

        $retries = max(1, (int) $retries);
        $attempts = 0;
        ++$this->processed;

        $payloads = static::field($job, 'payloads');
        $payloads = is_string($payloads) ? @unserialize($payloads) : $payloads;
        $payloads = is_array($payloads) ? $payloads : [];
        $batch = isset($payloads['rakit_batch']) ? (array) $payloads['rakit_batch'] : null;

        if ($batch && ($found = Batch::find($batch[0])) && $found->cancelled()) {
            $this->complete($job);
            $this->log(sprintf('Job skipped: %s - #%s (batch cancelled)', $name, $id));
            Batch::record($batch[0], $batch[1], 'skipped');

            return true;
        }

        Batch::$current = $batch ? $batch[0] : (isset($payloads['rakit_batch_of']) ? $payloads['rakit_batch_of'] : null);

        while (true) {
            ++$attempts;

            try {
                Hook::fire('rakit.jobs.process', [$job]);
                $this->log(sprintf('Job executed: %s - #%s (attempt %d)', $name, $id, $attempts));
                $this->complete($job);
                Batch::$current = null;
                Job::advance($payloads);
                $batch && Batch::record($batch[0], $batch[1], 'succeeded');
                return true;
            } catch (\Throwable $e) {
                // Handled below.
            } catch (\Exception $e) {
                // Handled below.
            }

            if ($attempts >= $retries) {
                $this->bury($job, $e);
                $this->failed($name, $id, static::field($job, 'payloads'), $e, $attempts);
                Batch::$current = null;

                $batch && Batch::record($batch[0], $batch[1], 'failed');

                return false;
            }

            $this->log(sprintf('Job retry: %s - #%s (attempt %d)', $name, $id, $attempts));

            if ($sleep_ms > 0) {
                usleep($sleep_ms * 1000);
            }
        }
    }

    /**
     * Get the Jobable class a payload names, or NULL.
     *
     * @param mixed $payloads
     *
     * @return string|null
     */
    protected static function job_class($payloads)
    {
        $payloads = is_string($payloads) ? @unserialize($payloads) : $payloads;

        if (
            is_array($payloads)
            && isset($payloads['class'])
            && is_string($payloads['class'])
            && class_exists($payloads['class'])
            && is_subclass_of($payloads['class'], 'System\Job\Jobable')
        ) {
            return $payloads['class'];
        }

        return null;
    }

    /**
     * Read a field of a job, an array or an object depending on the driver.
     *
     * @param array|object $job
     * @param string       $key
     *
     * @return mixed
     */
    protected static function field($job, $key)
    {
        if (is_array($job)) {
            return isset($job[$key]) ? $job[$key] : null;
        }

        return isset($job->{$key}) ? $job->{$key} : null;
    }

    /**
     * Format an exception for the failed jobs store.
     *
     * @param \Throwable $e
     * @param bool       $trace
     *
     * @return string
     */
    protected static function describe($e, $trace = false)
    {
        return get_class($e)
            .(('' === $e->getMessage()) ? '' : ': '.$e->getMessage())
            .' in '.$e->getFile().':'.$e->getLine()
            .($trace ? LF.'Stack trace:'.LF.$e->getTraceAsString() : '');
    }

    /**
     * Log a job that ran out of attempts, and announce its terminal failure.
     *
     * @param string       $name
     * @param string       $id
     * @param array|object $payloads
     * @param \Throwable   $e
     * @param int          $attempts
     */
    protected function failed($name, $id, $payloads, $e, $attempts)
    {
        if (is_string($payloads)) {
            $payloads = unserialize($payloads);
        }

        $this->log(sprintf(
            'Job failed: %s - #%s ::: %s (after %d attempts)',
            $name,
            $id,
            $e->getMessage(),
            $attempts
        ), 'error');

        Hook::fire('rakit.jobs.failed', [$name, $payloads, $e, $attempts]);
    }

    /**
     * Log the job message.
     *
     * @param string $message
     * @param string $type
     */
    protected function log($message, $type = 'info')
    {
        if (Config::get('job.logging')) {
            Log::channel('jobs');
            Log::{$type}($message);
            Log::channel(null);

            if (Request::cli()) {
                echo '['.Carbon::now()->format('Y-m-d H:i:s').'] ['.strtoupper((string) $type).'] '.$message.PHP_EOL;
            }
        }
    }
}
