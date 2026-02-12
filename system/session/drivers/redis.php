<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Cache\Drivers\Redis as CacheRedis;

class Redis extends Driver
{
    /**
     * Contains the Redis cache driver instance.
     *
     * @var System\Cache\Drivers\Redis
     */
    protected $redis;

    /**
     * Constructor.
     *
     * @param System\Cache\Drivers\Redis $redis
     */
    public function __construct(CacheRedis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * Load the session based on the given ID.
     * If the session is not found, NULL will be returned.
     *
     * @param string $id
     *
     * @return array
     */
    public function load($id)
    {
        /** @disregard */
        return $this->redis->get($id);
    }

    /**
     * Save the session data.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    public function save(array $session, array $config, $exists)
    {
        /** @disregard */
        $this->redis->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        /** @disregard */
        $this->redis->forget($id);
    }
}
