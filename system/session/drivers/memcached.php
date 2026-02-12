<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Cache\Drivers\Memcached as CacheMemcached;

class Memcached extends Driver
{
    /**
     * Contains the Memcached cache driver instance.
     *
     * @var System\Cache\Drivers\Memcached
     */
    private $memcached;

    /**
     * Constructor.
     *
     * @param System\Cache\Drivers\Memcached $memcached
     */
    public function __construct(CacheMemcached $memcached)
    {
        $this->memcached = $memcached;
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
        return $this->memcached->get($id);
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
        $this->memcached->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        /** @disregard */
        $this->memcached->forget($id);
    }
}
