<?php

namespace System\Cache;

defined('DS') or exit('No direct access.');

use System\Str;

class Lock
{
    /**
     * The cache driver holding the lock.
     *
     * @var \System\Cache\Drivers\Driver
     */
    protected $driver;

    /**
     * The lock name.
     *
     * @var string
     */
    protected $name;

    /**
     * Seconds the lock lasts, so a holder that dies does not keep it forever.
     *
     * @var int
     */
    protected $seconds;

    /**
     * The owner token: only its holder may release the lock.
     *
     * @var string
     */
    protected $owner;

    /**
     * Constructor.
     *
     * @param \System\Cache\Drivers\Driver $driver
     * @param string                       $name
     * @param int                          $seconds
     * @param string|null                  $owner
     */
    public function __construct($driver, $name, $seconds = 10, $owner = null)
    {
        $this->driver = $driver;
        $this->name = (string) $name;
        $this->seconds = max(1, (int) $seconds);
        $this->owner = is_null($owner) ? Str::random(32) : (string) $owner;
    }

    /**
     * Take the lock. With a callback, run it and release the lock afterwards, even
     * when it throws, and return its result; FALSE when the lock is taken.
     *
     * @param \Closure|null $callback
     *
     * @return mixed
     */
    public function get($callback = null)
    {
        $acquired = $this->driver->acquire_lock($this->name, $this->owner, $this->seconds);
        return (! $acquired || is_null($callback)) ? $acquired : $this->run($callback);
    }

    /**
     * Wait up to some seconds for the lock, then behave as get(). Throws on timeout,
     * so a callback that returns FALSE is not mistaken for a lock never taken.
     *
     * @param int           $seconds
     * @param \Closure|null $callback
     *
     * @return mixed
     *
     * @throws \System\Exceptions\LockTimeoutException
     */
    public function block($seconds, $callback = null)
    {
        $until = microtime(true) + max(0, (int) $seconds);

        while (! $this->driver->acquire_lock($this->name, $this->owner, $this->seconds)) {
            if (microtime(true) >= $until) {
                throw new \System\Exceptions\LockTimeoutException(sprintf('Timed out waiting for lock: %s', $this->name));
            }

            usleep(250000);
        }

        return is_null($callback) ? true : $this->run($callback);
    }

    /**
     * Run a callback while holding the lock, and release it afterwards, also when the
     * callback throws.
     *
     * @param \Closure $callback
     *
     * @return mixed
     */
    protected function run(\Closure $callback)
    {
        try {
            $result = $callback();
        } catch (\Throwable $e) {
            $this->release();
            throw $e;
        } catch (\Exception $e) {
            $this->release();
            throw $e;
        }

        $this->release();
        return $result;
    }

    /**
     * Release the lock, if this owner still holds it.
     *
     * @return bool
     */
    public function release()
    {
        return $this->driver->release_lock($this->name, $this->owner);
    }

    /**
     * Get the owner token, to release the lock from another process.
     *
     * @return string
     */
    public function owner()
    {
        return $this->owner;
    }
}
