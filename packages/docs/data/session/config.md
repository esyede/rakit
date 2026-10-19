# Session Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Configuration Options](#configuration-options)
-   [Drivers](#drivers)
    -   [File Driver](#file-driver)
    -   [Cookie Driver](#cookie-driver)
    -   [Database Driver](#database-driver)
    -   [Memcached, Redis & APC Drivers](#memcached-redis--apc-drivers)
    -   [Memory Driver](#memory-driver)
    -   [Custom Drivers](#custom-drivers)
-   [Sweeping](#sweeping)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

HTTP is stateless. A session keeps data for a visitor across requests: the data lives in the
configured driver, and a cookie on the visitor's device holds the session ID. Sessions are
configured in `application/config/session.php`; see [Using Session](/docs/session/usage) for
the API.

Available drivers: `file` (default), `cookie`, `database`, `memcached`, `redis`, `apc` and
`memory`.

<a id="configuration-options"></a>

## Configuration Options

| Option | Default | Description |
| --- | --- | --- |
| `driver` | `'file'` | The session driver. |
| `table` | `'sessions'` | Table used by the `database` driver. |
| `lifetime` | `60` | Minutes of inactivity before the session expires. |
| `expire_on_close` | `false` | `true` makes the session cookie end when the browser closes. |
| `cookie` | `'rakit_session'` | Name of the session ID cookie. |
| `path` | `'/'` | Cookie path. |
| `domain` | `null` | Cookie domain. |
| `secure` | `false` | Send the cookie over HTTPS only. It is forced on whenever the request is HTTPS. |
| `samesite` | `'Lax'` | `Lax`, `Strict` or `None`. |
| `sweep` | `[2, 100]` | Odds of deleting expired sessions on a request, see [Sweeping](#sweeping). |
| `stateless` | `['api/*']` | Paths that never start a session (APIs, webhooks). `*` matches anything; patterns without `*` match exactly. |

<a id="drivers"></a>

## Drivers

<a id="file-driver"></a>

### File Driver

The default, and enough for most applications. Sessions are stored as files in
`storage/sessions/`, which must be writable.

```php
'driver' => 'file',
```

<a id="cookie-driver"></a>

### Cookie Driver

Stores the whole session in an encrypted (AES-256) cookie on the visitor's device. Light and
fast, but limited to about 4 KB of data.

```php
'driver' => 'cookie',
```

<a id="database-driver"></a>

### Database Driver

[Configure the database connection](/docs/database/config) first, then create the session
table (named by the `table` option) and set `'driver' => 'database'`.

<a id="console"></a>

#### With a Migration

```bash
php rakit make:migration create_sessions_table
```

```php
Schema::create('sessions', function ($table) {
    $table->string('id', 40);
    $table->integer('last_activity');
    $table->text('data');
    $table->primary('id');
});
```

```bash
php rakit migrate
```

<a id="sqlite"></a>
<a id="mysql"></a>

#### With SQL

```sql
-- MySQL
CREATE TABLE `sessions` (
    `id` VARCHAR(40) NOT NULL,
    `last_activity` INT(10) NOT NULL,
    `data` TEXT NOT NULL,
    PRIMARY KEY (`id`)
);

-- SQLite
CREATE TABLE "sessions" (
    "id" VARCHAR PRIMARY KEY NOT NULL UNIQUE,
    "last_activity" INTEGER NOT NULL,
    "data" TEXT NOT NULL
);
```

<a id="memcached-redis--apc-drivers"></a>
<a id="memcached-driver"></a>
<a id="redis-driver"></a>
<a id="apc-driver"></a>

### Memcached, Redis & APC Drivers

These store sessions through the matching cache driver, so set that up first:
[memcached server](https://github.com/memcached/memcached/wiki/ConfiguringServer),
[redis](/docs/database/redis#configuration), or the APC(u) extension. Then:

```php
'driver' => 'memcached', // or 'redis', 'apc'
```

<a id="memory-driver"></a>

### Memory Driver

`memory` keeps the session in an array for the current request only and writes nothing.

> Use it for tests only.

<a id="custom-drivers"></a>

### Custom Drivers

Register a resolver returning a `System\Session\Drivers\Driver` subclass (implementing
`load($id)`, `save(array $session, array $config, $exists)` and `delete($id)`), then use its
name as the `driver`:

```php
Session::extend('mongo', function () {
    return new MongoSessionDriver();
});
```

<a id="sweeping"></a>

## Sweeping

The `file` and `database` drivers keep data until something deletes it, so on a share of
requests Rakit deletes every session idle for longer than `lifetime`. The `sweep` option sets
the odds as `[chances, out_of]`:

```php
'sweep' => [2, 100],  // roughly 2 of every 100 requests
'sweep' => false,     // never; clean up yourself instead
```

With sweeping off, run [`php rakit session:gc`](/docs/console#sessiongc) from a scheduled job.
The other drivers are never swept: cookie sessions live on the visitor's device, and memcached,
redis and apc expire the data themselves.
