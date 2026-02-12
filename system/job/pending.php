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
     * @var string
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
        // Auto-dispatch when not chained
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
        if (!$this->name) {
            return false;
        }

        $driver = Job::driver($this->driver ?: null);

        // Check for overlapping
        if ($this->without_overlapping && $driver->has_overlapping($this->name, $this->queue)) {
            return false;
        }

        // Add job to the queue
        $result = $driver->add(
            $this->name,
            $this->payload,
            $this->dispatch_at,
            $this->queue,
            $this->without_overlapping
        );

        // Prevent double dispatch
        $this->name = null;

        return $result;
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
