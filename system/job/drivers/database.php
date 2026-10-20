<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Database as DB;
use System\Hook;
use System\Str;

class Database extends Driver
{
    /**
     * Add a new job to the database.
     *
     * @param string      $name
     * @param array       $payloads
     * @param string|null $scheduled_at
     * @param string      $queue
     * @param bool        $without_overlapping
     *
     * @return bool
     */
    public function add(
        $name,
        array $payloads = [],
        $scheduled_at = null,
        $queue = 'default',
        $without_overlapping = false
    ) {
        $config = Config::get('job');
        $name = Str::slug($name);
        $id = DB::table($config['table'])->insert_get_id([
            'name' => $name,
            'payloads' => serialize($payloads),
            'queue' => $queue,
            'without_overlapping' => $without_overlapping ? 1 : 0,
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => Carbon::now()->format('Y-m-d H:i:s'),
            'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $this->log(sprintf('Job added: %s - #%s (queue: %s)', $name, $id, $queue));

        return true;
    }

    /**
     * Check if there is an overlapping job in the database.
     *
     * @param string $name
     * @param string $queue
     *
     * @return bool
     */
    public function has_overlapping($name, $queue = 'default')
    {
        $count = DB::table(Config::get('job.table'))
            ->where('name', '=', Str::slug($name))
            ->where('queue', '=', $queue)
            ->where('without_overlapping', '=', 1)
            ->count();

        return $count > 0;
    }

    /**
     * Delete a job from the database.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    public function forget($name, $queue = null)
    {
        $config = Config::get('job');
        $name = Str::slug($name);

        Hook::fire('rakit.jobs.forget: '.$name);

        $query = DB::table($config['table'])->where('name', $name);

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query->get();

        if (count($jobs) > 0) {
            $ids = [];

            foreach ($jobs as $job) {
                $ids[] = $job->id;
            }

            DB::table($config['table'])->where_in('id', $ids)->delete();
        }

        $fails = DB::table($config['failed_table'])->where('name', $name);

        if ($queue) {
            $fails->where('queue', $queue);
        }

        $jobs = $fails->get();

        if (count($jobs) > 0) {
            $ids = [];

            foreach ($jobs as $job) {
                $ids[] = $job->id;
            }

            DB::table($config['failed_table'])->where_in('id', $ids)->delete();
        }

        $this->log(sprintf('Jobs deleted: %s (queue: %s)', $name, $queue ?: 'all'));

        return true;
    }

    /**
     * Run a specific job from the database.
     *
     * @param string      $name
     * @param int         $retries
     * @param int         $sleep_ms
     * @param string|null $queue
     *
     * @return bool
     */
    public function run($name, $retries = 1, $sleep_ms = 0, $queue = null)
    {
        $config = Config::get('job');
        $name = Str::slug($name);
        $this->processed = 0;

        if (empty($name)) {
            $this->log('Job is empty');
            return false;
        }

        $query = DB::table($config['table'])
            ->where('name', $name)
            ->where('scheduled_at', '<=', Carbon::now()->format('Y-m-d H:i:s'));

        if ($queue) {
            $query->where('queue', $queue);
        }

        $jobs = $query->order_by('created_at', 'ASC')->take($config['max_job'])->get();

        if (count($jobs) < 1) {
            $this->log('Job is empty');
        } else {
            list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
            $this->work($jobs, $retries, $sleep_ms);
        }
    }

    /**
     * Run all available jobs from the database.
     *
     * @param int        $retries
     * @param int        $sleep_ms
     * @param array|null $queues
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0, $queues = null)
    {
        $config = Config::get('job');
        $this->processed = 0;
        $query = DB::table($config['table'])
            ->where('scheduled_at', '<=', Carbon::now()->format('Y-m-d H:i:s'));

        if (is_array($queues) && ! empty($queues)) {
            $query->where_in('queue', $queues);
        }

        $jobs = $query->order_by('created_at', 'ASC')->take($config['max_job'])->get();

        if (count($jobs) < 1) {
            $this->log('Job is empty');
        } else {
            list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
            $this->work($jobs, $retries, $sleep_ms);
        }
    }

    /**
     * Claim a job by pushing its scheduled time past the claim: only the worker whose
     * UPDATE still finds the time it read gets it, and the job comes back on its own
     * if that worker dies.
     *
     * @param object $job
     *
     * @return object|bool
     */
    protected function claim($job)
    {
        $claimed = DB::table(Config::get('job.table'))
            ->where('id', '=', $job->id)
            ->where('scheduled_at', '=', $job->scheduled_at)
            ->update([
                'scheduled_at' => date('Y-m-d H:i:s', time() + $this->retry_after()),
                'updated_at' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

        return (1 === (int) $claimed) ? $job : false;
    }

    /**
     * Remove a job that succeeded.
     *
     * @param object $job
     */
    protected function complete($job)
    {
        DB::table(Config::get('job.table'))->where('id', '=', $job->id)->delete();
    }

    /**
     * Store a job that ran out of attempts in the failed table, and remove it.
     *
     * @param object     $job
     * @param \Throwable $e
     */
    protected function bury($job, $e)
    {
        $config = Config::get('job');

        DB::table($config['failed_table'])->insert([
            'job_id' => $job->id,
            'name' => $job->name,
            'queue' => $job->queue,
            'payloads' => $job->payloads,
            'exception' => static::describe($e, true),
            'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $this->complete($job);
    }

    /**
     * List the failed jobs.
     *
     * @return array
     */
    public function failures()
    {
        $failures = [];

        foreach (DB::table(Config::get('job.failed_table'))->order_by('id', 'ASC')->get() as $row) {
            $failures[] = [
                'id' => $row->id,
                'name' => $row->name,
                'queue' => $row->queue,
                'failed_at' => $row->failed_at,
                'exception' => strtok((string) $row->exception, LF),
            ];
        }

        return $failures;
    }

    /**
     * Put a failed job back on its queue, or every failed job with 'all'.
     *
     * @param string|int $id
     *
     * @return int
     */
    public function retry($id)
    {
        $table = Config::get('job.failed_table');
        $query = DB::table($table);

        if ('all' !== $id) {
            $query->where('id', '=', (int) $id);
        }

        $count = 0;

        foreach ($query->get() as $row) {
            $payloads = @unserialize($row->payloads);
            $this->add($row->name, is_array($payloads) ? $payloads : [], null, $row->queue);
            DB::table($table)->where('id', '=', $row->id)->delete();
            ++$count;
        }

        return $count;
    }
}
