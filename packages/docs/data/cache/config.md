# Cache Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Database Driver](#database-driver)
- [Memcached Driver](#memcached-driver)
- [Redis Driver](#redis-driver)
- [Memory Driver](#memory-driver)
- [Cache Key](#cache-key)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Caching stores the result of expensive work (such as a "top 10 songs" query) for a while, so it doesn't have to be recomputed on every request. Cache settings live in `application/config/cache.php`.

Six drivers are available: `file`, `database`, `memcached`, `apc`, `redis` and `memory`. The default is `file`, which stores items in `storage/cache/` and needs no further setup.

> Make sure the `storage/cache/` directory is writable before using the `file` driver.

<a id="database-driver"></a>

## Database Driver

The `database` driver stores items in a database table. Set the table name (and optionally a `connection`) in the config file:

```php
'database' => ['table' => 'caches'],
```

Then create the table with these three columns:

```php
key        - VARCHAR
value      - TEXT
expiration - VARCHAR
```

<a id="memcached-driver"></a>

## Memcached Driver

The `memcached` driver requires a [Memcached](https://memcached.org) server and the [Memcached PHP extension](https://www.php.net/manual/en/book.memcached.php). Select the driver and list your servers:

```php
'driver' => 'memcached',

'memcached' => [
    ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],
],
```

<a id="redis-driver"></a>

## Redis Driver

The `redis` driver uses your default Redis connection. First [configure your Redis server](/docs/database/redis#configuration), then select the driver:

```php
'driver' => 'redis'
```

<a id="memory-driver"></a>

## Memory Driver

The `memory` driver keeps items in an array for the current request only and writes nothing to disk. It is meant for unit tests and **must not** be used in production.

<a id="cache-key"></a>

## Cache Key

APC, Redis, Memcached and a shared database may also hold other applications' data, so every item key is prefixed with the `key` option plus a dot (e.g. `rakit.`):

```php
'key' => 'rakit'
```
