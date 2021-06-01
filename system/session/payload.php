<?php

namespace System\Session;

defined('DS') or exit('No direct script access.');

use System\Arr;
use System\Str;
use System\Config;
use System\Cookie;
use System\Session;
use System\Session\Drivers\Driver;
use System\Session\Drivers\Sweeper;

class Payload
{
    /**
     * Berisi array session yang disimpan di driver saat ini.
     *
     * @var array
     */
    public $session;

    /**
     * Berisi nama driver yang sedang digunakan.
     *
     * @var Driver
     */
    public $driver;

    /**
     * Indikasi bahwa session sudah ada di penyimpanan.
     *
     * @var bool
     */
    public $exists = true;

    /**
     * Buat instance payload baru.
     *
     * @param Driver $driver
     */
    public function __construct(Driver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * Muat session untuk request saat ini.
     *
     * @param string $id
     */
    public function load($id)
    {
        if (! is_null($id)) {
            $this->session = $this->driver->load($id);
        }

        if (is_null($this->session) || static::expired($this->session)) {
            $this->exists = false;
            $this->session = $this->driver->fresh();
        }

        if (! $this->has(Session::TOKEN)) {
            $this->put(Session::TOKEN, Str::random(40));
        }
    }

    /**
     * Cek apakah instance paylod session yang diberikan valid.
     * Session dianggap valid jika ia ada di penyimpanan dan belum kedaluwarsa.
     *
     * @param array $session
     *
     * @return bool
     */
    protected static function expired($session)
    {
        $lifetime = Config::get('session.lifetime');
        return (time() - $session['last_activity']) > ($lifetime * 60);
    }

    /**
     * Cek apakah ada/tidaknya item di session atau flash data.
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
     * Ambil item di session saat ini.
     * Pencarian juga akan dilakukan di flash data, tidak hanya di session saja.
     *
     * <code>
     *
     *      // Ambil sebuah item dari session
     *      $name = Session::get('name');
     *
     *      // Return default value jika itemnya tidak ketemu
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
        if (! isset($this->session['data'])) {
            return value($default);
        }

        if (! is_null($value = Arr::get($this->session['data'], $key))) {
            return $value;
        } elseif (! is_null($value = Arr::get($this->session['data'][':new:'], $key))) {
            return $value;
        } elseif (! is_null($value = Arr::get($this->session['data'][':old:'], $key))) {
            return $value;
        }

        return value($default);
    }

    /**
     * Taruh item ke session.
     *
     * <code>
     *
     *      // Taruh sebuah item ke session
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
     * Taruh sebuah item ke flash data.
     * Flash data hanya akan tersedia di request saat ini dan request berikutnya.
     *
     * <code>
     *
     *      // Taruh sebuah item ke flash data
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
     * Pertahankan item flash data agar tdak kedaluwarsa setelah request dijalankan.
     */
    public function reflash()
    {
        $old = $this->session['data'][':old:'];
        $this->session['data'][':new:'] = array_merge($this->session['data'][':new:'], $old);
    }

    /**
     * Pertahankan item flash data agar tidak kedaluwarsa di akhir request.
     *
     * <code>
     *
     *      // Pertahankan item 'name' agar tidak kedaluwarsa
     *      Session::keep('name');
     *
     *      // Pertahankan item 'name' dan 'email' agar tidak kedaluwarsa
     *      Session::keep(['name', 'email']);
     *
     * </code>
     *
     * @param string|array $keys
     */
    public function keep($keys)
    {
        $keys = (array) $keys;

        foreach ($keys as $key) {
            $this->flash($key, $this->get($key));
        }
    }

    /**
     * Hapus sebuah item dari session.
     *
     * @param string $key
     */
    public function forget($key)
    {
        Arr::forget($this->session['data'], $key);
    }

    /**
     * Hapus seluruh item dari session (kecuali token CSRF).
     */
    public function flush()
    {
        $session = [Session::TOKEN => $this->token(), ':new:' => [], ':old:' => []];
        $this->session['data'] = $session;
    }

    /**
     * Set session-id baru untuk session.
     */
    public function regenerate()
    {
        $this->session['id'] = $this->driver->id();
        $this->exists = false;
    }

    /**
     * Ambil token CSRF.
     *
     * @return string
     */
    public function token()
    {
        return $this->get(Session::TOKEN);
    }

    /**
     * Ambil info 'last actvity'.
     *
     * @return int
     */
    public function activity()
    {
        return $this->session['last_activity'];
    }

    /**
     * Simpan payload session.
     * Method ini akan otomatis terpanggil di akhir setiap request.
     */
    public function save()
    {
        $this->session['last_activity'] = time();
        $this->age();

        $config = Config::get('session');
        $this->driver->save($this->session, $config, $this->exists);
        $this->cookie($config);

        $sweepage = $config['sweepage'];

        if (mt_rand(1, $sweepage[1]) <= $sweepage[0]) {
            $this->sweep();
        }
    }

    /**
     * Bersihkan seluruh session yang telah kedaluwarsa (garbage collection).
     */
    public function sweep()
    {
        if ($this->driver instanceof Sweeper) {
            $this->driver->sweep(time() - (Config::get('session.lifetime') * 60));
        }
    }

    /**
     * Buat flash data kedaluwarsa.
     */
    protected function age()
    {
        $this->session['data'][':old:'] = $this->session['data'][':new:'];
        $this->session['data'][':new:'] = [];
    }

    /**
     * Kirim cookie session-id ke browser.
     *
     * @param array $config
     */
    protected function cookie($config)
    {
        $minutes = $config['expire_on_close'] ? 0 : $config['lifetime'];
        Cookie::put(
            $config['cookie'],
            $this->session['id'],
            $minutes,
            $config['path'],
            $config['domain'],
            $config['secure']
        );
    }
}
