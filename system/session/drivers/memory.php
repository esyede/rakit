<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

class Memory extends Driver
{
    /**
     * Contains the session data in memory.
     *
     * @var array
     */
    public $session;

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
        return $this->session;
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
        // ..
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        // ..
    }
}
