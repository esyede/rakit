<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Database as DB;

class Database extends Driver
{
    /**
     * Nama key cache dari file konfigurasi.
     *
     * @var string
     */
    protected $key;

    /**
     * Buat instance driver database baru.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Cek apakah item ada di cache.
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
     * Ambil item dari driver cache.
     *
     * @param string $key
     *
     * @return mixed
     */
    protected function retrieve($key)
    {
        $cache = $this->table()->where('key', '=', $this->key . $key)->first();

        if (!is_null($cache)) {
            return Carbon::createFromTimestamp($cache->expiration)->lte(Carbon::now())
                ? $this->forget($key)
                : unserialize($cache->value);
        }
    }

    /**
     * Simpan item ke cache untuk beberapa menit.
     *
     * <code>
     *
     *      // Simpan sebuah item ke cache selama 15 menit.
     *      Cache::put('name', 'Budi', 15);
     *
     * </code>
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $minutes
     */
    public function put($key, $value, $minutes)
    {
        $key = $this->key . $key;
        $value = serialize($value);
        $expiration = $this->expiration($minutes);
        $record = $this->table()->where('key', $key);

        if ($record->first()) {
            $record->update(compact('value', 'expiration'));
        } else {
            $this->table()->insert(compact('key', 'value', 'expiration'));
        }
    }

    /**
     * Hapus item dari cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $this->table()->where('key', '=', $this->key . $key)->delete();
    }

    /**
     * Hapus seluruh item cache.
     */
    public function flush()
    {
        $db = Config::get('cache.database');
        $db['connection'] = (isset($db['connection']) && !empty($db['connection'])) ? $db['connection'] : null;
        $connection = DB::connection($db['connection']);
        $driver = $connection->driver();
        $connection->query((('sqlite' === $driver) ? 'DELETE FROM ' : 'TRUNCATE TABLE ') . $db['table']);
    }

    /**
     * Ambil query builder untuk tabel di database.
     *
     * @return System\Database\Query
     */
    protected function table()
    {
        $db = Config::get('cache.database');
        $db['connection'] = (isset($db['connection']) && !empty($db['connection'])) ? $db['connection'] : null;
        return DB::connection($db['connection'])->table($db['table']);
    }
}
