<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

use System\Crypter;
use System\Cookie as BaseCookie;

class Cookie extends Driver
{
    /**
     * The session payload cookie name.
     *
     * @var string
     */
    const PAYLOAD = 'session_payload';

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
        if (BaseCookie::has(Cookie::PAYLOAD)) {
            return unserialize(Crypter::decrypt(BaseCookie::get(Cookie::PAYLOAD)));
        }
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
        BaseCookie::put(
            Cookie::PAYLOAD,
            Crypter::encrypt(serialize($session)),
            $config['lifetime'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            $config['samesite']
        );
    }

    /**
     * Delete the session with the given ID.
     *
     * @param string $id
     */
    public function delete($id)
    {
        BaseCookie::forget(Cookie::PAYLOAD);
    }
}
