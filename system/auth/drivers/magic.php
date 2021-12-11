<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Hash;
use System\Config;
use System\Database as DB;

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
            return DB::table(Config::get('auth.table'))->find($id);
        }
    }

    /**
     * Coba loginkan user.
     *
     * @param array $arguments
     *
     * @return bool
     */
    public function attempt($arguments = [])
    {
        $user = $this->get_user($arguments);
        $password = $arguments['password'];
        $fieldname = Config::get('auth.password', 'password');

        if (! is_null($user) && Hash::check($password, $user->{$fieldname})) {
            return $this->login($user->id, Arr::get($arguments, 'remember'));
        }

        return false;
    }

    /**
     * Ambil user dari tabel di database.
     *
     * @param array $arguments
     *
     * @return mixed
     */
    protected function get_user($arguments)
    {
        $table = Config::get('auth.table');

        return DB::table($table)->where(function ($query) use ($arguments) {
            $identifier = Config::get('auth.identifier');
            $query->where($identifier, '=', $arguments['identifier']);
            $except = Arr::except($arguments, ['identifier', 'password', 'remember']);

            foreach ($except as $column => $val) {
                $query->where($column, '=', $val);
            }
        })->first();
    }
}
