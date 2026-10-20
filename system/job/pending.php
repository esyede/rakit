<?php

namespace System\Job;

defined('DS') or exit('No direct access.');

use System\Job;
use System\Str;

class Pending
{
    /**
     * Contains the job name.
     *
     * @var string|null
     */
    protected $name;

    /**
     * Contains the job data payload.
     *
     * @var array
     */
    protected $payload;

    /**
     * Contains the dispatch time.
     *
     * @var string|null
     */
    protected $dispatch_at;

    /**
     * Contains the queue name.
     *
     * @var string
     */
    protected $queue = 'default';

    /**
     * Indicates whether to prevent overlapping jobs.
     *
     * @var bool
     */
    protected $without_overlapping = false;

    /**
     * Contains the driver name.
     *
     * @var string|null
     */
    protected $driver;

    /**
     * Constructor.
     *
     * @param string      $name
     * @param array       $payload
     * @param string|null $dispatch_at
     */
    public function __construct($name, array $payload = [], $dispatch_at = null)
    {
        $this->name = Str::slug($name);
        $this->payload = $payload;
        $this->dispatch_at = $dispatch_at;
        register_shutdown_function(function () {
            if ($this->name) {
                $this->execute();
            }
        });
    }

    /**
     * Get the queue name.
     *
     * @param string $queue
     *
     * @return $this
     */
    public function on_queue($queue = 'default')
    {
        $this->queue = $queue;
        return $this;
    }

    /**
     * Prevent overlapping jobs.
     *
     * @return $this
     */
    public function without_overlapping()
    {
        $this->without_overlapping = true;
        return $this;
    }

    /**
     * Set the driver to use.
     *
     * @param string $driver
     *
     * @return $this
     */
    public function via($driver)
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Execute the job dispatch.
     *
     * @return bool
     */
    protected function execute()
    {
        if (! $this->name) {
            return false;
        }

        $driver = Job::driver($this->driver ?: null);

        if ($this->without_overlapping && $driver->has_overlapping($this->name, $this->queue)) {
            return false;
        }

        $result = $driver->add(
            $this->name,
            $this->payload,
            $this->dispatch_at,
            $this->queue,
            $this->without_overlapping
        );

        $this->name = null;
        return $result;
    }

    /**
     * Take the job out of auto-dispatch and return what it would have queued, so a
     * chain or a batch can dispatch it later.
     *
     * @return array
     */
    public function detach()
    {
        $spec = [
            'name' => $this->name,
            'payload' => $this->payload,
            'dispatch_at' => $this->dispatch_at,
            'queue' => $this->queue,
            'without_overlapping' => $this->without_overlapping,
            'driver' => $this->driver,
        ];

        $this->name = null;

        return $spec;
    }

    /**
     * Make a pending job back from detach()'s result.
     *
     * @param array $spec
     *
     * @return Pending
     */
    public static function from(array $spec)
    {
        $pending = new static($spec['name'], (array) $spec['payload'], $spec['dispatch_at']);
        $pending->queue = $spec['queue'];
        $pending->without_overlapping = (bool) $spec['without_overlapping'];
        $pending->driver = $spec['driver'];

        return $pending;
    }

    /**
     * Force dispatch now.
     *
     * @return bool
     */
    public function dispatch()
    {
        return $this->execute();
    }

    /**
     * Destructor - ensure the job is dispatched.
     */
    public function __destruct()
    {
        if ($this->name) {
            $this->execute();
        }
    }
}
