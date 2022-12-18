<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Hash;
use System\Config;
use System\Database;

class Magic extends Driver
{
    /**
     * Ambil user saat ini.
     * Jika ia belum login, NULL akan direturn.
     *
     * @param int $id
     *
     * @return mixed|null
     */
    public function retrieve($id)
    {
        if (false !== filter_var($id, FILTER_VALIDATE_INT)) {
            return Database::table(Config::get('auth.table'))->find($id);
        }
    }

    /**
     * Coba loginkan user.
     *
     * @param array $arguments
     *
     * @return bool
     */
    public function attempt(array $arguments = [])
    {
        $table = Config::get('auth.table');
        $user = Database::table($table)->where(function ($query) use ($arguments) {
            $query->where('email', '=', $arguments['email']);
            $except = Arr::except($arguments, ['email', 'password', 'remember']);

            foreach ($except as $column => $value) {
                $query->where($column, '=', $value);
            }
        })->first();

        if (!is_null($user) && Hash::check($arguments['password'], $user->password)) {
            return $this->login($user->id, Arr::get($arguments, 'remember'));
        }

        return false;
    }
}
