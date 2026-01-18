<?php

namespace System\Job;

defined('DS') or exit('No direct access.');

use System\Job;
use System\Str;

class Pending
{
    /**
     * Nama job.
     *
     * @var string
     */
    protected $name;

    /**
     * Payload job.
     *
     * @var array
     */
    protected $payload;

    /**
     * Waktu dispatch.
     *
     * @var string|null
     */
    protected $dispatch_at;

    /**
     * Nama queue.
     *
     * @var string
     */
    protected $queue = 'default';

    /**
     * Flag without overlapping.
     *
     * @var bool
     */
    protected $without_overlapping = false;

    /**
     * Driver yang digunakan.
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
        // Auto-dispatch jika tidak ada chaining
        register_shutdown_function(function () {
            if ($this->name) {
                $this->execute();
            }
        });
    }

    /**
     * Set queue name.
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
     * Set without overlapping.
     *
     * @return $this
     */
    public function without_overlapping()
    {
        $this->without_overlapping = true;
        return $this;
    }

    /**
     * Set driver.
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
     * Execute dispatch.
     *
     * @return bool
     */
    protected function execute()
    {
        if (!$this->name) {
            return false;
        }

        $driver = Job::driver($this->driver ?: null);

        // Cek overlapping
        if ($this->without_overlapping && $driver->has_overlapping($this->name, $this->queue)) {
            return false;
        }

        // Add job dengan queue
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
     * Force dispatch sekarang.
     *
     * @return bool
     */
    public function dispatch()
    {
        return $this->execute();
    }

    /**
     * Destructor - pastikan job di-dispatch.
     */
    public function __destruct()
    {
        if ($this->name) {
            $this->execute();
        }
    }
}
