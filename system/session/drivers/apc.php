<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Cache\Drivers\APC as CacheAPC;

class APC extends Driver
{
    /**
     * Contains the APC cache driver instance.
     *
     * @var System\Cache\Drivers\APC
     */
    private $apc;

    /**
     * Constructor.
     *
     * @param System\Cache\Drivers\APC $apc
     */
    public function __construct(CacheAPC $apc)
    {
        $this->apc = $apc;
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
        return $this->apc->get($id);
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
        $this->apc->put($session['id'], $session, $config['lifetime']);
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        /** @disregard */
        $this->apc->forget($id);
    }
}
