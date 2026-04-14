<?php

namespace System\Cache\Drivers;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Carbon;
use System\Database as DB;

class Database extends Driver
{
    /**
     * Contains the cache key prefix from the configuration file.
     *
     * @var string
     */
    protected $key;

    /**
     * Make a new database cache driver instance.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Check if an item exists in the cache.
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
     * Retrieve an item from the cache driver.
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
     * Store an item in the cache for a given number of minutes.
     *
     * <code>
     *
     *      // Store an item in the cache for 15 minutes
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
     * Increment a numeric value in the cache (atomic).
     *
     * @param string $key
     * @param int    $minutes
     *
     * @return int
     */
    public function increment($key, $minutes = 1)
    {
        $db = Config::get('cache.database');
        $db['connection'] = (isset($db['connection']) && !empty($db['connection'])) ? $db['connection'] : null;
        $connection = DB::connection($db['connection']);
        $table = $db['table'];
        $prefixed = $this->key . $key;
        $expiration = $this->expiration($minutes);
        $new = 1;

        $connection->transaction(function () use ($connection, $table, $prefixed, $expiration, &$new) {
            $cache = $connection->table($table)->where('key', '=', $prefixed)->first();
            $expired = !is_null($cache) && Carbon::createFromTimestamp($cache->expiration)->lte(Carbon::now());

            if (is_null($cache) || $expired) {
                $connection->table($table)->where('key', '=', $prefixed)->delete();
                $connection->table($table)->insert(['key' => $prefixed, 'value' => serialize(1), 'expiration' => $expiration]);
                $new = 1;
            } else {
                $current = (int) unserialize($cache->value);
                $new = $current + 1;
                $connection->table($table)->where('key', '=', $prefixed)->update(['value' => serialize($new)]);
            }
        });

        return $new;
    }

    /**
     * Remove an item from the cache.
     *
     * @param string $key
     */
    public function forget($key)
    {
        $this->table()->where('key', '=', $this->key . $key)->delete();
    }

    /**
     * Remove all items from the cache.
     */
    public function flush()
    {
        $db = Config::get('cache.database');
        $db['connection'] = (isset($db['connection']) && !empty($db['connection'])) ? $db['connection'] : null;
        $connection = DB::connection($db['connection']);
        $connection->query((('sqlite' === $connection->driver()) ? 'DELETE FROM ' : 'TRUNCATE TABLE ') . $db['table']);
    }

    /**
     * Get a query builder for the cache table.
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
