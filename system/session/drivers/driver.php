<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;

abstract class Driver
{
    /**
     * Load the session based on the given ID.
     * If the session is not found, NULL will be returned.
     *
     * @param string $id
     *
     * @return array
     */
    abstract public function load($id);

    /**
     * Save the session data.
     *
     * @param array $session
     * @param array $config
     * @param bool  $exists
     */
    abstract public function save(array $session, array $config, $exists);

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    abstract public function delete($id);

    /**
     * Generate a fresh session array.
     *
     * @return array
     */
    public function fresh()
    {
        return ['id' => $this->id(), 'data' => [':new:' => [], ':old:' => []]];
    }

    /**
     * Get a unique session ID.
     *
     * @return string
     */
    public function id()
    {
        if ($this instanceof Cookie) {
            return Str::random(40);
        }

        $session = null;
        $id = null;

        do {
            $id = Str::random(40);
            $session = $this->load($id);
        } while (!is_null($session));

        return $id;
    }
}
