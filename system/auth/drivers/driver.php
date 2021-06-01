<?php

namespace System\Auth\Drivers;

defined('DS') or exit('No direct script access.');

use System\Str;
use System\Cookie;
use System\Config;
use System\Event;
use System\Session;
use System\Crypter;

abstract class Driver
{
    /**
     * Berisi user saat ini.
     *
     * @var mixed
     */
    public $user;

    /**
     * Berisi token user.
     *
     * @var string|null
     */
    public $token;

    /**
     * Buat instance auth driver baru.
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
     * Cek apakah user belum login.
     * Method ini adalah kebalikan dari method check().
     *
     * @return bool
     */
    public function guest()
    {
        return ! $this->check();
    }

    /**
     * Cek apakah user sudah login.
     *
     * @return bool
     */
    public function check()
    {
        return ! is_null($this->user());
    }

    /**
     * Ambil user saat ini.
     * Jika ia belum login, NULL akan direturn.
     *
     * @return mixed|null
     */
    public function user()
    {
        if (is_null($this->user)) {
            $this->user = $this->retrieve($this->token);
        }

        return $this->user;
    }

    /**
     * Ambil user berdasarkan ID.
     *
     * @param int $id
     *
     * @return mixed
     */
    abstract public function retrieve($id);

    /**
     * Coba loginkan user.
     *
     * @param array $arguments
     */
    abstract public function attempt($arguments = []);

    /**
     * Loginkan user berdasarkan token miliknya.
     * Token ini berupa ID numerik milik user.
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

        if ($remember) {
            $this->remember($token);
        }

        Event::fire('rakit.auth: login');

        return true;
    }

    /**
     * Logoutkan user dari aplikasi.
     */
    public function logout()
    {
        $this->user = null;

        $this->cookie($this->recaller(), null, -2628000);
        Session::forget($this->token());
        Event::fire('rakit.auth: logout');

        $this->token = null;
    }

    /**
     * Simpan token user ke session.
     *
     * @param string $token
     */
    protected function store($token)
    {
        Session::put($this->token(), $token);
    }

    /**
     * Simpan token user ke cookie selamanya (5 tahun).
     *
     * @param string $token
     */
    protected function remember($token)
    {
        $token = Crypter::encrypt($token.'|'.Str::random(40));
        $this->cookie($this->recaller(), $token, 2628000);
    }

    /**
     * Coba cari cookie "remember me" milik user.
     *
     * @return string|null
     */
    protected function recall()
    {
        $cookie = Cookie::get($this->recaller());
        return is_null($cookie) ? null : head(explode('|', Crypter::decrypt($cookie)));
    }

    /**
     * Simpan sebuah cookie otentikasi.
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
     * Ambil nama cookie token user.
     *
     * @return string
     */
    protected function token()
    {
        return $this->name().'_login';
    }

    /**
     * Ambil nama cookie remember me.
     *
     * @return string
     */
    protected function recaller()
    {
        return $this->name().'_remember';
    }

    /**
     * Ambil nama driver dalam format snake-case.
     *
     * @return string
     */
    protected function name()
    {
        $child = get_class($this);
        return Str::lower(str_replace('\\', '_', $child));
    }
}
