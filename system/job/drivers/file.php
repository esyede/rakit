<?php

namespace System\Job\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Hook;
use System\Carbon;
use System\Storage;
use System\Config;

class File extends Driver
{
    /**
     * Contains the path to the job files.
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = rtrim($path, DS).DS;

        if (! is_dir($this->path)) {
            Storage::mkdir($this->path, 0755);
        }
    }

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
    public function add(
        $name,
        array $payloads = [],
        $scheduled_at = null,
        $queue = 'default',
        $without_overlapping = false
    ) {
        $name = Str::slug($name);
        $id = Str::ulid();
        $now = Carbon::now()->format('Y-m-d H:i:s');
        $data = [
            'id' => $id,
            'name' => $name,
            'queue' => $queue,
            'without_overlapping' => $without_overlapping,
            'payloads' => serialize($payloads),
            'scheduled_at' => Carbon::parse($scheduled_at)->format('Y-m-d H:i:s'),
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $file = $this->path.$name.'__'.$id.'.job.php';
        Storage::put($file, static::guard(serialize($data)), LOCK_EX);

        $this->log(sprintf('Job added: %s - #%s (queue: %s)', $name, $id, $queue));
        return true;
    }

    /**
     * Check if there is an overlapping job.
     *
     * @param string $name
     * @param string $queue
     *
     * @return bool
     */
    public function has_overlapping($name, $queue = 'default')
    {
        $name = Str::slug($name);
        $files = glob($this->path.$name.'__*.job.php*');

        if (empty($files)) {
            return false;
        }

        foreach ($files as $file) {
            if (is_file($file)) {
                $data = unserialize(static::unguard(Storage::get($file)));

                if (
                    (isset($data['queue']) && $data['queue'] === $queue)
                    && (isset($data['without_overlapping']) && $data['without_overlapping'])
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Delete a job from the queue.
     *
     * @param string      $name
     * @param string|null $queue
     *
     * @return bool
     */
    public function forget($name, $queue = null)
    {
        $name = Str::slug($name);
        $pattern = $this->path.$name.'__*'.'.job.php*';
        $files = glob($pattern);

        foreach ($files as $file) {
            if ($queue) {
                $data = unserialize(static::unguard(Storage::get($file)));

                if (isset($data['queue']) && $data['queue'] === $queue) {
                    Storage::delete($file);
                }
            } else {
                Storage::delete($file);
            }
        }

        $this->log(sprintf('Jobs deleted: %s (queue: %s)', $name, $queue ?: 'all'));
        return true;
    }

    /**
     * Run a specific job in the file.
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
        $name = Str::slug($name);
        $this->processed = 0;
        $this->recover();

        $files = glob($this->path.$name.'__*'.'.job.php');

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($this->due($files, $queue ? [$queue] : null), $retries, $sleep_ms);

        return true;
    }

    /**
     * Run all available jobs in the file.
     *
     * @param int        $retries
     * @param int        $sleep_ms
     * @param array|null $queues
     *
     * @return bool
     */
    public function runall($retries = 1, $sleep_ms = 0, $queues = null)
    {
        $this->processed = 0;
        $this->recover();

        $files = array_filter((array) glob($this->path.'*.job.php'), function ($file) {
            return strpos(basename($file), 'failed__') !== 0;
        });

        if (empty($files)) {
            $this->log('No job files found');
            return false;
        }

        list($retries, $sleep_ms) = $this->attempts($retries, $sleep_ms);
        $this->work($this->due($files, (is_array($queues) && ! empty($queues)) ? $queues : null), $retries, $sleep_ms);

        return true;
    }

    /**
     * Get the jobs that are due, on the given queues, up to the configured maximum.
     *
     * @param array      $files
     * @param array|null $queues
     *
     * @return array
     */
    protected function due(array $files, $queues)
    {
        $max = (int) Config::get('job.max_job');
        $jobs = [];

        foreach ($files as $file) {
            if (count($jobs) >= $max) {
                break;
            }

            $content = @file_get_contents($file);

            if (false === $content) {
                continue; // Claimed by another worker meanwhile.
            }

            $data = unserialize(static::unguard($content));

            if (! is_array($data)) {
                continue;
            }

            if ($queues && (! isset($data['queue']) || ! in_array($data['queue'], $queues))) {
                continue;
            }

            if (Carbon::parse($data['scheduled_at'])->lte(Carbon::now())) {
                $data['file'] = $file;
                $jobs[] = $data;
            }
        }

        return $jobs;
    }

    /**
     * Claim a job by renaming its file: rename() is atomic, so only one worker
     * succeeds. The name carries the claim's expiry, for recover().
     *
     * @param array $job
     *
     * @return array|bool
     */
    protected function claim($job)
    {
        $reserved = $job['file'].'.'.(time() + $this->retry_after()).'.reserved.php';

        if (! @rename($job['file'], $reserved)) {
            return false;
        }

        $job['file'] = $reserved;
        return $job;
    }

    /**
     * Remove a job that succeeded.
     *
     * @param array $job
     */
    protected function complete($job)
    {
        Storage::delete($job['file']);
    }

    /**
     * Store a job that ran out of attempts as failed, and remove it.
     *
     * @param array      $job
     * @param \Throwable $e
     */
    protected function bury($job, $e)
    {
        $file = $job['file'];
        unset($job['file']);

        $this->move_to_failed($job, $e);
        Storage::delete($file);
    }

    /**
     * Put back the jobs whose worker died before finishing them.
     */
    protected function recover()
    {
        foreach ((array) glob($this->path.'*.reserved.php') as $reserved) {
            if (
                preg_match('/^(.+\.job\.php)\.(\d+)\.reserved\.php$/', $reserved, $matches)
                && (int) $matches[2] < time()
            ) {
                @rename($reserved, $matches[1]);
            }
        }
    }

    /**
     * List the failed jobs.
     *
     * @return array
     */
    public function failures()
    {
        $failures = [];

        foreach ((array) glob($this->path.'failed__*.job.php') as $file) {
            $data = @unserialize(static::unguard((string) @file_get_contents($file)));

            if (is_array($data)) {
                $failures[] = [
                    'id' => $data['id'],
                    'name' => $data['name'],
                    'queue' => $data['queue'],
                    'failed_at' => isset($data['failed_at']) ? $data['failed_at'] : '',
                    'exception' => isset($data['exception']) ? strtok((string) $data['exception'], LF) : '',
                ];
            }
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
        $pattern = ('all' === $id) ? 'failed__*.job.php' : 'failed__'.basename((string) $id).'.job.php';
        $count = 0;

        foreach ((array) glob($this->path.$pattern) as $file) {
            $data = @unserialize(static::unguard((string) @file_get_contents($file)));

            if (! is_array($data)) {
                continue;
            }

            $payloads = is_string($data['payloads']) ? @unserialize($data['payloads']) : $data['payloads'];
            $this->add($data['name'], is_array($payloads) ? $payloads : [], null, $data['queue']);
            Storage::delete($file);
            ++$count;
        }

        return $count;
    }

    /**
     * Protect the job file from direct access via browser.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function guard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return $guard.$value;
    }

    /**
     * Remove the protection from the job file.
     *
     * @param string $value
     *
     * @return string
     */
    protected static function unguard($value)
    {
        $guard = "<?php defined('DS') or exit('No direct access.');?>";
        return str_replace($guard, '', $value);
    }

    /**
     * Move the failed job to a separate file.
     *
     * @param array      $data
     * @param \Throwable $exception
     */
    protected function move_to_failed($data, $exception)
    {
        $error = get_class($exception)
            .(('' === $exception->getMessage()) ? '' : ': '.$exception->getMessage())
            .' in '.$exception->getFile().':'.$exception->getLine();
        $data = array_merge($data, [
            'exception' => $error,
            'failed_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ]);

        $file = $this->path.'failed__'.$data['id'].'.job.php';
        Storage::put($file, static::guard(serialize($data)), LOCK_EX);
    }
}
