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
        if (!is_null($id)) {
            $this->session = $this->driver->load($id);
        }

        if (is_null($this->session) || static::expired($this->session)) {
            $this->exists = false;
            $this->session = $this->driver->fresh();
        }

        if (!$this->has(Session::TOKEN)) {
            $this->put(Session::TOKEN, Str::random(40));
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
        return (time() - $session['last_activity']) > (Config::get('session.lifetime') * 60);
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
        return !is_null($this->get($key));
    }

    /**
     * Get an item from the session.
     * The search will also be performed in flash data, not just in the session.
     *
     * <code>
     *
     *      // Get an item from the session
     *      $name = Session::get('name');
     *
     *      // Return default value if the item is not found
     *      $name = Session::get('name', 'Budi');
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get($key, $default = null)
    {
        if (!isset($this->session['data'])) {
            return value($default);
        }

        if (!is_null($value = Arr::get($this->session['data'], $key))) {
            return $value;
        } elseif (!is_null($value = Arr::get($this->session['data'][':new:'], $key))) {
            return $value;
        } elseif (!is_null($value = Arr::get($this->session['data'][':old:'], $key))) {
            return $value;
        }

        return value($default);
    }

    /**
     * Put an item into the session.
     *
     * <code>
     *
     *      // Put an item into the session
     *      Session::put('name', 'Budi');
     *
     * </code>
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
     * <code>
     *
     *      // Put an item into the flash data
     *      Session::flash('name', 'Budi');
     *
     * </code>
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
     * <code>
     *
     *      // Keep the 'name' item from expiring
     *      Session::keep('name');
     *
     *      // Keep the 'name' and 'email' items from expiring
     *      Session::keep(['name', 'email']);
     *
     *      Session::keep('name', 'email');
     *
     * </code>
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
     */
    public function regenerate()
    {
        $this->session['id'] = $this->driver->id();
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
        return $this->session['last_activity'];
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
            $config['secure']
        );
    }
}
