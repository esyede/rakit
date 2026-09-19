# Redis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Configuration](#configuration)
-   [Usage](#usage)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Redis is an open-source, advanced key-value storage software.
It is often referred to as a data structure server because its keys can contain [strings](https://redis.io/docs/latest/develop/data-types/strings/),
[hashes](https://redis.io/docs/latest/develop/data-types/hashes/),
[lists](https://redis.io/docs/latest/develop/data-types/lists/),
[sets](https://redis.io/docs/latest/develop/data-types/sets/), and [sorted sets](https://redis.io/docs/latest/develop/data-types/sorted-sets/).

<a id="configuration"></a>

## Configuration

Redis database configuration is located in the `application/config/database.php` file.
In this file, you will see a `'redis'` array that contains the Redis servers used by your application:

```php
'redis' => [

	'default' => ['host' => '127.0.0.1', 'port' => 6379, 'database' => 0],

],
```

The `'default'` entry is enough for development. Add your own by name, each with a
host, a port and a database index.

<a id="usage"></a>

## Usage

> **Note about the class name:** the Rakit Redis class lives at `System\Redis`. The short alias `Redis` is **not** registered by default because PHP's [phpredis extension](https://github.com/phpredis/phpredis) exposes a built-in `Redis` class with the same name. Use either the fully-qualified namespace or import it via `use`:
>
> ```php
> use System\Redis;
> // ...now you can call Redis::db() in this file
> ```
>
> All examples below assume the `use System\Redis;` import is present at the top of your file. Alternatively, replace every `Redis::` with `\System\Redis::`.

`db()` gives you an instance:

```php
$redis = Redis::db();
```

That is the `'default'` server. Name another one from the configuration file to get
that instead:

```php
$redis = Redis::db('redis_2');
```

Any [Redis command](https://redis.io/docs/latest/commands/) can be run on the
instance; magic methods pass it through to the server:

```php
$redis->set('name', 'Budi');

$name = $redis->get('name');

$values = $redis->lrange('names', 5, 10);
```

The command is the method name and its arguments the method arguments. `run()` does
the same thing explicitly:

```php
$values = $redis->run('lrange', ['names', 5, 10]);
```

Static calls go straight to the default server:

```php
use System\Redis;

Redis::set('name', 'Budi');

$name = Redis::get('name');

$values = Redis::lrange('names', 5, 10);
```

> Rakit also provides a Redis driver for [cache](/docs/cache/config#redis-driver) and [session](/docs/session/config#redis-driver).
