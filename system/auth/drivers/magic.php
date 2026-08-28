<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct access.');

use System\Arr;
use System\Hash;
use System\Config;
use System\Database;

class Magic extends Driver
{
    /**
     * Get the current user.
     * If the user is not logged in, NULL will be returned.
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function retrieve($id)
    {
        if ((! is_string($id) && ! is_int($id)) || '' === (string) $id) {
            return;
        }

        return Database::table(Config::get('auth.table', 'users'))->find($id);
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
        if (! isset($user->id)) {
            return false;
        }

        try {
            Database::table(Config::get('auth.table', 'users'))
                ->where('id', '=', $user->id)
                ->update(['remember_token' => $value]);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        $user->remember_token = $value;

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
        $table = Config::get('auth.table', 'users');
        $identifier = Config::get('auth.identifier', 'email');

        if (! isset($arguments[$identifier]) || ! isset($arguments['password'])) {
            return false;
        }

        $user = Database::table($table)->where(function ($query) use ($arguments, $identifier) {
            $query->where($identifier, '=', $arguments[$identifier]);
            $except = Arr::except($arguments, [$identifier, 'password', 'remember']);

            foreach ($except as $column => $value) {
                $query->where($column, '=', $value);
            }
        })->first();

        if (! is_null($user) && Hash::check($arguments['password'], $user->password)) {
            return $this->login($user->id, Arr::get($arguments, 'remember'));
        }

        return false;
    }
}
