<?php

namespace System\Job;

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Carbon;
use System\Str;

class Batch
{
    /**
     * The ID of the batch the running job belongs to, or that its callback job ran for.
     *
     * @var string|null
     */
    public static $current;

    /**
     * Minutes a batch's state is kept in the cache.
     *
     * @var int
     */
    public static $minutes = 10080;

    /**
     * The batch ID, set on dispatch().
     *
     * @var string|null
     */
    public $id;

    /**
     * The batch name.
     *
     * @var string
     */
    public $name = '';

    /**
     * The number of jobs.
     *
     * @var int
     */
    public $total_jobs = 0;

    /**
     * The number of jobs not run yet.
     *
     * @var int
     */
    public $pending_jobs = 0;

    /**
     * The number of jobs that failed for good.
     *
     * @var int
     */
    public $failed_jobs = 0;

    /**
     * When the batch was cancelled.
     *
     * @var string|null
     */
    public $cancelled_at;

    /**
     * When every job of the batch ran.
     *
     * @var string|null
     */
    public $finished_at;

    /**
     * When the batch was dispatched.
     *
     * @var string|null
     */
    public $created_at;

    /**
     * Whether a failed job leaves the batch running.
     *
     * @var bool
     */
    public $allow_failures = false;

    /**
     * The jobs to dispatch, as Pending::detach() results.
     *
     * @var array
     */
    protected $jobs = [];

    /**
     * The callback jobs: 'then', 'failure', 'finish'.
     *
     * @var array
     */
    protected $callbacks = [];

    /**
     * Constructor.
     *
     * @param array $jobs \System\Job\Pending instances
     */
    public function __construct(array $jobs = [])
    {
        foreach ($jobs as $job) {
            $this->jobs[] = static::spec($job);
        }
    }

    /**
     * Name the batch.
     *
     * @param string $name
     *
     * @return $this
     */
    public function name($name)
    {
        $this->name = (string) $name;
        return $this;
    }

    /**
     * Queue a job when every job of the batch succeeded.
     *
     * @param Pending $job
     *
     * @return $this
     */
    public function then(Pending $job)
    {
        $this->callbacks['then'] = static::spec($job);
        return $this;
    }

    /**
     * Queue a job on the first job that fails for good.
     *
     * @param Pending $job
     *
     * @return $this
     */
    public function on_failure(Pending $job)
    {
        $this->callbacks['failure'] = static::spec($job);
        return $this;
    }

    /**
     * Queue a job when every job of the batch ran, succeeded or not.
     *
     * @param Pending $job
     *
     * @return $this
     */
    public function on_finish(Pending $job)
    {
        $this->callbacks['finish'] = static::spec($job);
        return $this;
    }

    /**
     * Keep running the batch when a job fails; by default the first failure cancels it.
     *
     * @param bool $allow
     *
     * @return $this
     */
    public function allow_failures($allow = true)
    {
        $this->allow_failures = (bool) $allow;
        return $this;
    }

    /**
     * Store the batch and queue its jobs.
     *
     * @return $this
     */
    public function dispatch()
    {
        $this->id = Str::uuid();
        $this->created_at = Carbon::now()->format('Y-m-d H:i:s');
        $this->total_jobs = $this->pending_jobs = count($this->jobs);
        $this->save(['failed_ids' => [], 'fired' => []]);

        foreach ($this->jobs as $spec) {
            $spec['payload']['rakit_batch'] = [$this->id, Str::uuid()];
            Pending::from($spec)->dispatch();
        }

        $this->jobs = [];

        if (0 === $this->total_jobs) {
            static::record($this->id, null, 'skipped');
        }

        return static::find($this->id) ?: $this;
    }

    /**
     * Find a batch by its ID.
     *
     * @param string $id
     *
     * @return Batch|null
     */
    public static function find($id)
    {
        $state = Cache::get(static::key($id));

        if (! is_array($state)) {
            return null;
        }

        $batch = new static();
        $fields = [
            'id', 'name', 'total_jobs', 'pending_jobs', 'failed_jobs',
            'cancelled_at', 'finished_at', 'created_at', 'allow_failures',
        ];

        foreach ($fields as $field) {
            $batch->{$field} = $state[$field];
        }

        $batch->callbacks = $state['callbacks'];

        return $batch;
    }

    /**
     * Get the batch the running job belongs to.
     *
     * @return Batch|null
     */
    public static function current()
    {
        return is_null(static::$current) ? null : static::find(static::$current);
    }

    /**
     * Get the percentage of jobs that ran.
     *
     * @return int
     */
    public function progress()
    {
        return ($this->total_jobs > 0)
            ? (int) round((($this->total_jobs - $this->pending_jobs) / $this->total_jobs) * 100)
            : 100;
    }

    /**
     * Get the number of jobs that ran.
     *
     * @return int
     */
    public function processed_jobs()
    {
        return $this->total_jobs - $this->pending_jobs;
    }

    /**
     * Check whether every job ran.
     *
     * @return bool
     */
    public function finished()
    {
        return ! is_null($this->finished_at);
    }

    /**
     * Check whether the batch was cancelled.
     *
     * @return bool
     */
    public function cancelled()
    {
        return ! is_null($this->cancelled_at);
    }

    /**
     * Check whether a job failed for good.
     *
     * @return bool
     */
    public function has_failures()
    {
        return $this->failed_jobs > 0;
    }

    /**
     * Cancel the batch: its jobs not run yet are dropped by the worker.
     *
     * @return $this
     */
    public function cancel()
    {
        static::update($this->id, function (array $state) {
            $state['cancelled_at'] = $state['cancelled_at'] ?: Carbon::now()->format('Y-m-d H:i:s');
            return [$state, []];
        });

        return static::find($this->id) ?: $this;
    }

    /**
     * Forget the batch's state.
     */
    public function delete()
    {
        Cache::forget(static::key($this->id));
    }

    /**
     * Record how a job of a batch ended, and queue the callback jobs it unlocks.
     * Called by the worker.
     *
     * @param string      $id
     * @param string|null $job     the job's ID in the batch
     * @param string      $outcome 'succeeded', 'failed' or 'skipped'
     */
    public static function record($id, $job, $outcome)
    {
        static::update($id, function (array $state) use ($job, $outcome) {
            $fire = [];
            $now = Carbon::now()->format('Y-m-d H:i:s');

            if ('succeeded' === $outcome && in_array($job, $state['failed_ids'], true)) {
                $state['failed_ids'] = array_values(array_diff($state['failed_ids'], [$job]));
                $state['failed_jobs'] = max(0, $state['failed_jobs'] - 1);
            } else {
                $state['pending_jobs'] = max(0, $state['pending_jobs'] - (is_null($job) ? 0 : 1));

                if ('failed' === $outcome) {
                    ++$state['failed_jobs'];
                    $state['failed_ids'][] = $job;
                    $fire[] = 'failure';

                    if (! $state['allow_failures']) {
                        $state['cancelled_at'] = $state['cancelled_at'] ?: $now;
                    }
                }
            }

            if (0 === $state['pending_jobs']) {
                $state['finished_at'] = $state['finished_at'] ?: $now;
                $fire[] = 'finish';

                if (0 === $state['failed_jobs'] && is_null($state['cancelled_at'])) {
                    $fire[] = 'then';
                }
            }

            $fire = array_values(array_diff(array_unique($fire), $state['fired']));
            $state['fired'] = array_merge($state['fired'], $fire);

            return [$state, $fire];
        });
    }

    /**
     * Change a batch's state under a lock, so workers running its jobs at once do not
     * overwrite each other, then queue the callback jobs the change asks for.
     *
     * @param string   $id
     * @param \Closure $change function (array $state) returning [$state, $callbacks]
     */
    protected static function update($id, \Closure $change)
    {
        $key = static::key($id);

        $result = Cache::lock($key.'.lock', 10)->block(10, function () use ($key, $change) {
            $state = Cache::get($key);

            if (! is_array($state)) {
                return [null, []];
            }

            $result = $change($state);
            Cache::put($key, $result[0], static::$minutes);
            return $result;
        });

        foreach ($result[1] as $callback) {
            if (isset($result[0]['callbacks'][$callback])) {
                $spec = $result[0]['callbacks'][$callback];
                $spec['payload']['rakit_batch_of'] = $id;
                Pending::from($spec)->dispatch();
            }
        }
    }

    /**
     * Store the batch's state.
     *
     * @param array $extra
     */
    protected function save(array $extra)
    {
        Cache::put(static::key($this->id), array_merge([
            'id' => $this->id,
            'name' => $this->name,
            'total_jobs' => $this->total_jobs,
            'pending_jobs' => $this->pending_jobs,
            'failed_jobs' => $this->failed_jobs,
            'cancelled_at' => $this->cancelled_at,
            'finished_at' => $this->finished_at,
            'created_at' => $this->created_at,
            'allow_failures' => $this->allow_failures,
            'callbacks' => $this->callbacks,
        ], $extra), static::$minutes);
    }

    /**
     * Get the cache key of a batch.
     *
     * @param string $id
     *
     * @return string
     */
    protected static function key($id)
    {
        return 'rakit.job.batch.'.preg_replace('/[^a-zA-Z0-9\-]/', '', (string) $id);
    }

    /**
     * Detach a pending job.
     *
     * @param mixed $job
     *
     * @return array
     */
    protected static function spec($job)
    {
        if (! ($job instanceof Pending)) {
            throw new \InvalidArgumentException('A batch takes jobs from Job::dispatch() or Jobable::dispatch().');
        }

        return $job->detach();
    }
}
