<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Cookie;
use System\Config;
use System\Event;
use System\Session;
use System\Crypter;

abstract class Driver
{
    /**
     * Contains the current user.
     *
     * @var mixed
     */
    public $user;

    /**
     * Contains the user token.
     *
     * @var string|null
     */
    public $token;

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (Session::started()) {
            $this->token = Session::get($this->token());
        }

        if (is_null($this->token)) {
            $this->token = $this->recall();
        }
    }

    /**
     * Check if the user is not logged in.
     * This method is the opposite of the check() method.
     *
     * @return bool
     */
    public function guest()
    {
        return !$this->check();
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public function check()
    {
        return !is_null($this->user());
    }

    /**
     * Get the current user.
     * If the user is not logged in, NULL will be returned.
     *
     * @return mixed|null
     */
    public function user()
    {
        if (!$this->user) {
            $this->user = $this->retrieve($this->token);
        }

        return $this->user;
    }

    /**
     * Get the user by ID.
     *
     * @param int $id
     *
     * @return mixed
     */
    abstract public function retrieve($id);

    /**
     * Try to login the user.
     *
     * @param array $arguments
     */
    abstract public function attempt(array $arguments = []);

    /**
     * Log in the user based on their token.
     * The token is a numeric ID of the user.
     *
     * @param string $token
     * @param bool   $remember
     *
     * @return bool
     */
    public function login($token, $remember = false)
    {
        $this->token = $token;
        $this->store($token);
        $this->user = $this->retrieve($this->token);

        if ($remember) {
            $this->remember($token);
        }

        Event::fire('rakit.auth: login');
        return true;
    }

    /**
     * Logout the user from the application.
     */
    public function logout()
    {
        $this->user = null;

        $this->cookie($this->recaller(), '', -2628000);
        Session::forget($this->token());
        Event::fire('rakit.auth: logout');

        $this->token = null;
    }

    /**
     * Save the user token to the session.
     *
     * @param string $token
     */
    protected function store($token)
    {
        Session::put($this->token(), $token);
    }

    /**
     * Save the user token to the cookie forever (5 years).
     *
     * @param string $token
     */
    protected function remember($token)
    {
        $token = Crypter::encrypt($token . '|' . Str::random(40));
        $this->cookie($this->recaller(), $token, 2628000);
    }

    /**
     * Try to find the "remember me" cookie of the user.
     *
     * @return string|null
     */
    protected function recall()
    {
        $cookie = Cookie::get($this->recaller());
        return is_null($cookie) ? null : head(explode('|', Crypter::decrypt($cookie)));
    }

    /**
     * Save an authentication cookie.
     *
     * @param string $name
     * @param string $value
     * @param int    $minutes
     */
    protected function cookie($name, $value, $minutes)
    {
        $config = Config::get('session');
        Cookie::put($name, $value, $minutes, $config['path'], $config['domain'], $config['secure']);
    }

    /**
     * Get the name of the user token cookie.
     *
     * @return string
     */
    protected function token()
    {
        return $this->name() . '_login';
    }

    /**
     * Get the name of the user remember me cookie.
     *
     * @return string
     */
    protected function recaller()
    {
        return $this->name() . '_remember';
    }

    /**
     * Get the name of the driver in snake-case format.
     *
     * @return string
     */
    protected function name()
    {
        return Str::lower(str_replace('\\', '_', get_class($this)));
    }
}
