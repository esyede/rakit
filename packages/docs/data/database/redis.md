# Redis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Configuration](#configuration)
-   [Usage](#usage)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Redis is an open-source, advanced key-value storage software.
It is often referred to as a data structure server because its keys can contain [strings](https://redis.io/topics/data-types#strings),
[hashes](https://redis.io/topics/data-types#hashes),
[lists](https://redis.io/topics/data-types#lists),
[sets](https://redis.io/topics/data-types#sets), and [sorted sets](https://redis.io/topics/data-types#sorted-sets).

<a id="configuration"></a>

## Configuration

Redis database configuration is located in the `application/config/database.php` file.
In this file, you will see a `'redis'` array that contains the Redis servers used by your application:

```php
'redis' => [

	'default' => ['host' => '127.0.0.1', 'port' => 6379],

],
```

The `'default'` configuration above is usually sufficient for development.
However, you are free to modify this array according to your environment.
Just give each server configuration a name, and specify the host and port used by the server.

<a id="usage"></a>

## Usage

You can get a Redis instance by calling the `db()` method like this:

```php
$redis = Redis::db();
```

This will give you an instance of the `'default'` server.
You can also pass the name of another server to the `db()` method to get an instance of that server as specified in your configuration file:

```php
$redis = Redis::db('redis_2');
```

Great! Now you have a Redis instance, which means you can run any [Redis commands](https://redis.io/commands) you want.
Rakit uses magic methods to pass these commands to the Redis server:

```php
$redis->set('name', 'Budi');

$name = $redis->get('name');

$values = $redis->lrange('names', 5, 10);
```

Note that the arguments of Redis commands are called as method names. Of course, you are not required to use these magic methods;
you can also send commands to the server using the `run()` method like this:

```php
$values = $redis->run('lrange', [5, 10]);
```

Just want to run commands on the default Redis server? Just use the magic methods:

```php
Redis::set('name', 'Budi');

$name = Redis::get('name');

$values = Redis::lrange('names', 5, 10);
```

> Rakit also provides a Redis driver for [cache](/docs/id/cache/config#redis) and [session](/docs/id/session/config#redis).
