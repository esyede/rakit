<?php

namespace System\Session;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Str;
use System\Config;
use System\Cookie;
use System\Session;

class Payload
{
    /**
     * Contains the session data.
     *
     * @var array
     */
    public $session;

    /**
     * Contains the session driver.
     *
     * @var \System\Session\Drivers\Driver
     */
    public $driver;

    /**
     * Indicates whether the session exists in storage.
     *
     * @var bool
     */
    public $exists = true;

    /**
     * Constructor.
     *
     * @param \System\Session\Drivers\Driver $driver
     */
    public function __construct($driver)
    {
        if ($driver instanceof Drivers\Driver) {
            $this->driver = $driver;
        }
    }

    /**
     * Get all session data.
     *
     * @return array
     */
    public function all()
    {
        return isset($this->session['data']) ? $this->session['data'] : [];
    }

    /**
     * Load the session payload from storage.
     *
     * @param string $id
     */
    public function load($id)
    {
        if (! is_null($id)) {
            $this->session = $this->driver->load($id);
        }

        if (! is_array($this->session) || ! isset($this->session['id'])) {
            $this->session = null;
        }

        if (is_null($this->session) || static::expired($this->session)) {
            $this->exists = false;
            $this->session = $this->driver->fresh();
        }

        if (! isset($this->session['data']) || ! is_array($this->session['data'])) {
            $this->session['data'] = [];
        }

        foreach ([':new:', ':old:'] as $bag) {
            if (! isset($this->session['data'][$bag]) || ! is_array($this->session['data'][$bag])) {
                $this->session['data'][$bag] = [];
            }
        }
    }

    /**
     * Check if the session has expired.
     * Session considers expired if last activity time + lifetime < current time.
     *
     * @param array $session
     *
     * @return bool
     */
    protected static function expired(array $session)
    {
        $lastActivity = isset($session['last_activity']) ? $session['last_activity'] : 0;
        return (time() - $lastActivity) > (Config::get('session.lifetime') * 60);
    }

    /**
     * Check if an item exists in the session.
     *
     * @param string $key
     *
     * @return bool
     */
    public function has($key)
    {
        return ! is_null($this->get($key));
    }

    /**
     * Get an item from the session.
     * The search will also be performed in flash data, not just in the session.
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (! isset($this->session['data'])) {
            return value($default);
        }

        if (! is_null($value = Arr::get($this->session['data'], $key))) {
            return $value;
        }

        foreach ([':new:', ':old:'] as $bag) {
            if (! isset($this->session['data'][$bag]) || ! is_array($this->session['data'][$bag])) {
                continue;
            }

            if (! is_null($value = Arr::get($this->session['data'][$bag], $key))) {
                return $value;
            }
        }

        return value($default);
    }

    /**
     * Put an item into the session.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function put($key, $value)
    {
        Arr::set($this->session['data'], $key, $value);
    }

    /**
     * Put an item into the flash data.
     * Flash data will only last for the next request.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function flash($key, $value)
    {
        Arr::set($this->session['data'][':new:'], $key, $value);
    }

    /**
     * Keep all flash data for the next request.
     */
    public function reflash()
    {
        $old = $this->session['data'][':old:'];
        $this->session['data'][':new:'] = array_merge($this->session['data'][':new:'], $old);
    }

    /**
     * Keep a flash data items from expiring at the end of the request.
     *
     * @param string|array $keys
     */
    public function keep($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();

        foreach ($keys as $key) {
            $this->flash($key, $this->get($key));
        }
    }

    /**
     * Delete one or more items from the session.
     *
     * @param string $keys
     */
    public function forget($keys)
    {
        $keys = is_array($keys) ? $keys : func_get_args();
        Arr::forget($this->session['data'], $keys);
    }

    /**
     * Delete all session data (except the CSRF token).
     */
    public function flush()
    {
        $session = [Session::TOKEN => $this->token(), ':new:' => [], ':old:' => []];
        $this->session['data'] = $session;
    }

    /**
     * Set new session id.
     * Deletes the old session from storage before generating a new ID,
     * but preserves the current session data (e.g. after impersonate logout).
     */
    public function regenerate()
    {
        if (isset($this->session['id'])) {
            $this->driver->delete($this->session['id']);
        }

        $this->session['id'] = $this->driver->id();
        $this->exists = false;
    }

    /**
     * Invalidate the current session.
     * Deletes the old session from storage, generates a new session ID,
     * and resets all session data with a fresh CSRF token.
     * Use this on logout instead of regenerate() to avoid orphaned sessions.
     */
    public function invalidate()
    {
        if (isset($this->session['id'])) {
            $this->driver->delete($this->session['id']);
        }

        $this->session['id'] = $this->driver->id();
        $this->session['data'] = [Session::TOKEN => Str::random(40), ':new:' => [], ':old:' => []];
        $this->exists = false;
    }

    /**
     * Get the session token.
     *
     * @return string
     */
    public function token()
    {
        return $this->get(Session::TOKEN);
    }

    /**
     * Get the last activity time.
     *
     * @return int
     */
    public function activity()
    {
        return isset($this->session['last_activity']) ? $this->session['last_activity'] : 0;
    }

    /**
     * Save the session payload to storage.
     * This method will be automatically called at the end of each request.
     */
    public function save()
    {
        $this->session['last_activity'] = time();
        $this->age();

        $config = Config::get('session');
        $this->driver->save($this->session, $config, $this->exists);
        $this->cookie($config);

        if ($this->driver instanceof Drivers\Sweeper) {
            $this->sweep($config);
        }
    }

    /**
     * Delete expired sessions from storage based on the configured odds.
     * Only applies to drivers that do not expire their own data.
     *
     * @param array $config
     */
    protected function sweep(array $config)
    {
        $sweep = isset($config['sweep']) ? $config['sweep'] : [2, 100];

        if (! is_array($sweep) || count($sweep) < 2) {
            return;
        }

        list($chances, $out_of) = array_values($sweep);
        $chances = (int) $chances;
        $out_of = (int) $out_of;

        if ($chances < 1 || $out_of < 1) {
            return;
        }

        if (mt_rand(1, $out_of) <= $chances) {
            $lifetime = isset($config['lifetime']) ? (int) $config['lifetime'] : 0;
            $this->driver->sweep(time() - ($lifetime * 60));
        }
    }

    /**
     * Empty the old flash data.
     */
    protected function age()
    {
        $this->session['data'][':old:'] = $this->session['data'][':new:'];
        $this->session['data'][':new:'] = [];
    }

    /**
     * Set the session cookie.
     *
     * @param array $config
     */
    protected function cookie(array $config)
    {
        Cookie::put(
            $config['cookie'],
            $this->session['id'],
            $config['expire_on_close'] ? 0 : (int) $config['lifetime'],
            $config['path'],
            $config['domain'],
            $config['secure'],
            isset($config['samesite']) ? $config['samesite'] : 'lax'
        );
    }
}
