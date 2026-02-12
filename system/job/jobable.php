<?php

namespace System\Job;

defined('DS') or exit('No direct access.');

use System\Job;
use System\Str;

abstract class Jobable
{
    /**
     * Contains the job data payload.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * Handle the job logic.
     * This method must be implemented by the child class.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Get the job name from class name.
     *
     * @return string
     */
    public static function name()
    {
        $class = explode('\\', get_called_class());
        return Str::slug(end($class));
    }

    /**
     * Dispatch the job to the job queue.
     *
     * @param array       $data
     * @param string|null $dispatch_at
     *
     * @return Pending
     */
    public static function dispatch(array $data = [], $dispatch_at = null)
    {
        return Job::dispatch(static::name(), ['class' => get_called_class(), 'data' => $data], $dispatch_at);
    }

    /**
     * Dispatch the job to be executed at a specific time.
     *
     * @param string $dispatch_at
     * @param array  $data
     *
     * @return Pending
     */
    public static function dispatch_at($dispatch_at, array $data = [])
    {
        return static::dispatch($data, $dispatch_at);
    }

    /**
     * Execute the job from the given payload.
     *
     * @param array $payload
     *
     * @return void
     */
    public static function execute(array $payload)
    {
        if (isset($payload['class']) && isset($payload['data'])) {
            $class = $payload['class'];

            if (class_exists($class)) {
                $class = new $class($payload['data']);

                if ($class instanceof Jobable) {
                    $class->run();
                }
            }
        }
    }

    /**
     * Get a data from the job data payload.
     *
     * @param string     $key
     * @param mixed|null $default
     *
     * @return mixed
     */
    protected function get($key, $default = null)
    {
        return isset($this->data[$key]) ? $this->data[$key] : $default;
    }

    /**
     * Get all data.
     *
     * @return array
     */
    protected function data()
    {
        return $this->data;
    }
}
