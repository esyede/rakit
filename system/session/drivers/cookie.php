<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

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
        // Note: Cookie::get() already decrypts, and Cookie::put() already
        // encrypts. Wrapping the payload in a second Crypter layer here was pure
        // overhead, and any value that does not survive it (a stale cookie, a
        // rotated application key) has to read back as "no session" rather than
        // taking the whole request down.
        try {
            if (!BaseCookie::has(Cookie::PAYLOAD)) {
                return null;
            }

            $session = @unserialize(BaseCookie::get(Cookie::PAYLOAD));
        } catch (\Throwable $e) {
            return null;
        } catch (\Exception $e) {
            return null;
        }

        return is_array($session) ? $session : null;
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
            serialize($session),
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
