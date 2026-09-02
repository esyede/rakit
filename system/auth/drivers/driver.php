<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Cookie;
use System\Config;
use System\Hook;
use System\Session;
use System\Crypter;
use System\Request;

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
        return ! $this->check();
    }

    /**
     * Check if the user is logged in.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Get the current user.
     * If the user is not logged in, NULL will be returned.
     *
     * @return mixed|null
     */
    public function user()
    {
        if (! $this->user) {
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

        Hook::fire('rakit.auth: login');
        return true;
    }

    /**
     * Logout the user from the application.
     */
    public function logout()
    {
        $user = $this->user();

        if (! is_null($user)) {
            $this->save_remember_token($user, Str::random(60));
        }

        $this->user = null;

        $this->cookie($this->recaller(), '', -2628000);
        Session::forget($this->token());
        Session::regenerate();
        Hook::fire('rakit.auth: logout');

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

        // Rotate the session id so an id planted earlier never ends up authenticated.
        Session::regenerate();
    }

    /**
     * Save the user token to the cookie forever (5 years).
     * The cookie carries a token that is also stored on the user, so it can be
     * revoked, and the password hash, so changing the password revokes it too.
     *
     * @param string $token
     */
    protected function remember($token)
    {
        if (is_null($this->user)) {
            return;
        }

        $value = Str::random(60);

        if (! $this->save_remember_token($this->user, $value)) {
            return;
        }

        $payload = $token.'|'.$value.'|'.$this->password($this->user);

        $this->cookie($this->recaller(), Crypter::encrypt($payload), 2628000);
    }

    /**
     * Try to find the "remember me" cookie of the user.
     *
     * @return string|null
     */
    protected function recall()
    {
        try {
            $cookie = Cookie::get($this->recaller());
        } catch (\Throwable $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        if (is_null($cookie) || '' === $cookie) {
            return;
        }

        try {
            $segments = explode('|', Crypter::decrypt($cookie), 3);
        } catch (\Throwable $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        if (3 !== count($segments)) {
            return;
        }

        list($token, $value, $password) = $segments;

        try {
            $user = $this->retrieve($token);
        } catch (\Throwable $e) {
            return;
        } catch (\Exception $e) {
            return;
        }

        if (is_null($user)) {
            return;
        }

        $stored = (string) $this->remember_token($user);

        if ('' === $stored || !Crypter::equals($stored, $value)) {
            return;
        }

        if (! Crypter::equals($this->password($user), $password)) {
            return;
        }

        $this->user = $user;

        return $token;
    }

    /**
     * Get the "remember me" token stored on a user.
     *
     * @param mixed $user
     *
     * @return string|null
     */
    protected function remember_token($user)
    {
        return isset($user->remember_token) ? $user->remember_token : null;
    }

    /**
     * Store a new "remember me" token on a user.
     * Drivers that cannot store one return FALSE, which turns the feature off
     * instead of handing out a cookie that can never be revoked.
     *
     * @param mixed  $user
     * @param string $value
     *
     * @return bool
     */
    protected function save_remember_token($user, $value)
    {
        return false;
    }

    /**
     * Get the password hash of a user.
     *
     * @param mixed $user
     *
     * @return string
     */
    protected function password($user)
    {
        return isset($user->password) ? (string) $user->password : '';
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
        $secure = Request::secure() ?: $config['secure'];

        Cookie::put(
            $name,
            $value,
            $minutes,
            $config['path'],
            $config['domain'],
            $secure,
            isset($config['samesite']) ? $config['samesite'] : 'lax'
        );
    }

    /**
     * Get the name of the user token cookie.
     *
     * @return string
     */
    protected function token()
    {
        return $this->name().'_login';
    }

    /**
     * Get the name of the user remember me cookie.
     *
     * @return string
     */
    protected function recaller()
    {
        return $this->name().'_remember';
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
