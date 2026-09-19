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

A list of the ten most popular songs does not need recomputing on every visit. Caching it for ten minutes, or an hour, takes that work off the request.

By default, 6 cache drivers have been provided:

-   File
-   Database
-   Memcached
-   APC
-   Redis
-   Memory (Array)

Rakit uses the `'file'` driver by default, storing items in `storage/cache/`. It needs no further configuration.

> Before using the `'file'` cache driver, make sure your `storage/cache/` directory is writable.

<a id="database-driver"></a>

## Database Driver

The `'database'` cache driver uses a database table as storage for cache keys and values. To get started, first specify the database table name in `application/config/cache.php`:

```php
'database' => ['table' => 'caches'],
```

Next, create that table in your database. The table must have three columns:

```php
key        - VARCHAR
value      - TEXT
expiration - VARCHAR
```

<a id="memcached-driver"></a>

## Memcached Driver

[Memcached](https://memcached.org) is a very fast, open-source distributed memory object caching system. Before using this Memcached driver, you need to install and configure Memcached and the [Memcached PHP extension](https://www.php.net/manual/en/book.memcached.php) on your server.

After Memcached is installed on the server, you must set the 'driver' in the `application/config/cache.php` file:

```php
'driver' => 'memcached'
```

Then, add your Memcached servers to the `'memcached'` array:

```php
'memcached' => [

    ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],

],
```

<a id="redis-driver"></a>

## Redis Driver

Redis is an advanced open-source key-value storage software. It is often called a data structure server because its keys can contain [strings](https://redis.io/docs/latest/develop/data-types/strings/), [hashes](https://redis.io/docs/latest/develop/data-types/hashes/), [lists](https://redis.io/docs/latest/develop/data-types/lists/), [sets](https://redis.io/docs/latest/develop/data-types/sets/), and [sorted sets](https://redis.io/docs/latest/develop/data-types/sorted-sets/).

Before using this Redis driver, you must [configure your Redis server](/docs/database/redis#configuration). After that, you just need to change the `'driver'` in the `application/config/cache.php` file to redis like this:

```php
'driver' => 'redis'
```

<a id="memory-driver"></a>

## Memory Driver

The `'memory'` driver keeps an array for the current request and writes nothing to disk, which suits unit tests. It **must not** be used in production.

<a id="cache-key"></a>

## Cache Key

APC, Redis, Memcached and a shared database table may hold other applications' data, so every item is prefixed with the _'key'_ option and a dot (`rakit.`). Change it as you like:

```php
'key' => 'rakit'
```
