# Redis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Configuration](#configuration)
-   [Usage](#usage)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

[Redis](https://redis.io) is an advanced key-value store, often called a data structure server because its keys can hold [strings](https://redis.io/docs/latest/develop/data-types/strings/), [hashes](https://redis.io/docs/latest/develop/data-types/hashes/), [lists](https://redis.io/docs/latest/develop/data-types/lists/), [sets](https://redis.io/docs/latest/develop/data-types/sets/) and [sorted sets](https://redis.io/docs/latest/develop/data-types/sorted-sets/). Rakit talks to it directly over a socket, so no PHP extension is required.

<a id="configuration"></a>

## Configuration

Redis servers are listed in the `'redis'` array of `application/config/database.php`. The `'default'` entry is enough for development; add more by name, each with a host, port and database index:

```php
'redis' => [
    'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 0],
],
```

<a id="usage"></a>

## Usage

> The class lives at `System\Redis`. No short `Redis` alias is registered, because the [phpredis extension](https://github.com/phpredis/phpredis) ships a built-in `Redis` class with the same name. Import it with `use System\Redis;` (assumed below) or write `\System\Redis::`.

`db()` returns a connection to the `'default'` server, or to another server by name:

```php
use System\Redis;

$redis = Redis::db();
$redis = Redis::db('redis_2');
```

Any [Redis command](https://redis.io/docs/latest/commands/) can be called as a method; the method name is the command and its arguments are passed through. `run()` does the same explicitly:

```php
$redis->set('name', 'Budi');
$name = $redis->get('name');
$values = $redis->lrange('names', 5, 10);

$values = $redis->run('lrange', ['names', 5, 10]);
```

Static calls go straight to the default server:

```php
Redis::set('name', 'Budi');
$name = Redis::get('name');
```

> Rakit also provides a Redis driver for [cache](/docs/cache/config#redis-driver) and [session](/docs/session/config#redis-driver).
