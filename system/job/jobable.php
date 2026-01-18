<?php

namespace System\Job;

defined('DS') or exit('No direct access.');

use System\Job;
use System\Str;

abstract class Jobable
{
    /**
     * Berisi payload data untuk job.
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
     * Handle job execution.
     * Method ini harus di-implement di child class.
     *
     * @return void
     */
    abstract public function run();

    /**
     * Get job name dari class name.
     *
     * @return string
     */
    public static function name()
    {
        $class = explode('\\', get_called_class());
        return Str::slug(end($class));
    }

    /**
     * Dispatch job ke queue.
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
     * Dispatch job dengan schedule.
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
     * Execute job dari payload.
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
     * Get data dari payload.
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
