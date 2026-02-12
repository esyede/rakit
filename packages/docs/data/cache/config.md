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

Imagine your application displays the ten most popular songs selected by users. Do you really need to search for these ten songs every time someone visits your site? What if you could store them for 10 minutes, or even an hour, allowing you to dramatically speed up your application? This caching library can do that.

By default, 5 cache drivers have been provided:

-   File
-   Database
-   Memcached
-   APC
-   Redis
-   Memory (Array)

By default, Rakit is configured to use the `'file'` cache driver. This makes it ready to use without additional configuration. This driver stores cached items as files in the `storage/cache/` directory. If you are satisfied with this driver, no other configuration is required. You are ready to start using it.

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

Great! After your config and table are configured, you are ready to start caching!

<a id="memcached-driver"></a>

## Memcached Driver

[Memcached](http://memcached.org) is a very fast, open-source distributed memory object caching system. Before using this Memcached driver, you need to install and configure Memcached and the Memcache PHP extension on your server.

After Memcache is installed on the server, you must set the 'driver' in the `application/config/cache.php` file:

```php
'driver' => 'memcached'
```

Then, add your Memcached servers to the `'servers'` array:

```php
'servers' => [

    ['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],

],
```

<a id="redis-driver"></a>

## Redis Driver

Redis is an advanced open-source key-value storage software. It is often called a data structure server because its keys can contain [strings](http://redis.io/topics/data-types#strings), [hashes](http://redis.io/topics/data-types#hashes), [lists](http://redis.io/topics/data-types#lists), [sets](http://redis.io/topics/data-types#sets), and [sorted sets](http://redis.io/topics/data-types#sorted-sets).

Before using this Redis driver, you must [configure your Redis server](/docs/id/database/redis#config). After that, you just need to change the `'driver'` in the `application/config/cache.php` file to redis like this:

```php
'driver' => 'redis'
```

<a id="memory-driver"></a>

## Memory Driver

The `'memory'` cache driver doesn't actually store anything to disk. It only maintains an internal array of cache data for the current request. This makes it useful when you are unit-testing your application in isolation from any storage mechanism. This driver **should not** be used on production servers!

<a id="cache-key"></a>

## Cache Key

To avoid naming collisions with other applications using APC, Redis, or Memcached, Rakit appends a _'key'_ suffix to every item stored in the cache using these drivers. Feel free to change this:

```php
'key' => 'rakit'
```
