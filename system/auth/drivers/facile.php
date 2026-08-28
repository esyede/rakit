<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Hash;
use System\Config;

class Facile extends Driver
{
    /**
     * Get the current user.
     * If the user is not logged in, NULL will be returned.
     *
     * @param int|object $token
     *
     * @return mixed|null
     */
    public function retrieve($token)
    {
        $model = Config::get('auth.model');

        if (is_object($token)) {
            return ($model && get_class($token) === $model) ? $token : null;
        }

        if ((! is_string($token) && ! is_int($token)) || '' === (string) $token) {
            return;
        }

        if (! $model) {
            throw new \Exception('Please set the auth model in your config file.');
        }

        return (new $model())->find($token);
    }

    /**
     * Store a new "remember me" token on a user.
     *
     * @param mixed  $user
     * @param string $value
     *
     * @return bool
     */
    protected function save_remember_token($user, $value)
    {
        try {
            $user->remember_token = $value;
            $user->save();
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Try to login the user.
     *
     * @param array $arguments
     *
     * @return bool
     */
    public function attempt(array $arguments = [])
    {
        $model = Config::get('auth.model', 'User');
        $identifier = Config::get('auth.identifier', 'email');

        if (! isset($arguments[$identifier]) || ! isset($arguments['password'])) {
            return false;
        }

        $user = (new $model())->where(function ($query) use ($arguments, $identifier) {
            $query->where($identifier, '=', $arguments[$identifier]);
            $except = Arr::except($arguments, [$identifier, 'password', 'remember']);

            foreach ($except as $column => $value) {
                $query->where($column, '=', $value);
            }
        })->first();

        if (! is_null($user) && Hash::check($arguments['password'], $user->password)) {
            return $this->login($user->get_key(), Arr::get($arguments, 'remember'));
        }

        return false;
    }
}
