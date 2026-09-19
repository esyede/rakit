# Session Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Cookie Driver](#cookie-driver)
-   [File Driver](#file-driver)
-   [Database Driver](#database-driver)
    -   [Console](#console)
    -   [SQLite](#sqlite)
    -   [MySQL](#mysql)
-   [Memcached Driver](#memcached-driver)
-   [Redis Driver](#redis-driver)
-   [Memory Driver](#memory-driver)
-   [Sweeping](#sweeping)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

HTTP is stateless: every request stands on its own. A session gives each visitor a
store on the server, and a cookie holding the session ID on their device, so the next
request finds the same data again.

By default, seven drivers have been provided for sessions, namely:

-   Cookie
-   File
-   Database
-   Memcached
-   Redis
-   APC
-   Memory (Array)

<a id="cookie-driver"></a>

## Cookie Driver

Cookie sessions are light and fast, and each cookie is encrypted with AES-256.

However, cookies have a storage limit of `4 kilobytes`, so you may need to use
another driver if you want to store a lot of data in the session.

To start using this cookie driver, simply change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'cookie'
```

<a id="file-driver"></a>

## File Driver

The file driver is enough for most applications. Under heavy traffic, move to the
database or memcached driver.

To start using this file driver, simply change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'file'
```

By default, Rakit is already configured to use this driver.

> When using this driver, session data will be stored in the `storage/sessions/` folder as files,
> so make sure that directory is writable.

<a id="database-driver"></a>

## Database Driver

To use the database driver, you must first [configure the database connection](/docs/database/config).

Then create the session table, either through a [console](/docs/console) migration or
with the SQL below.

<a id="console"></a>

### Console

```bash
php rakit make:migration create_sessions_table
```

Fill the `up()` method of the generated migration with the table definition:

```php
Schema::create('sessions', function ($table) {
    $table->string('id', 40);
    $table->integer('last_activity');
    $table->text('data');
    $table->primary('id');
});
```

Then:

```bash
php rakit migrate
```

<a id="sqlite"></a>

### SQLite

```sql
CREATE TABLE "sessions" (
    "id" VARCHAR PRIMARY KEY NOT NULL UNIQUE,
    "last_activity" INTEGER NOT NULL,
    "data" TEXT NOT NULL
);
```

<a id="mysql"></a>

### MySQL

```sql
CREATE TABLE `sessions` (
    `id` VARCHAR(40) NOT NULL,
    `last_activity` INT(10) NOT NULL,
    `data` TEXT NOT NULL,
    PRIMARY KEY (`id`)
);
```

If you want to use a different table name, simply change the `'table'` option in
the `application/config/session.php` file as follows:

```php
'table' => 'sessions'
```

And finally, you just need to change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'database'
```

<a id="memcached-driver"></a>

## Memcached Driver

Before using the memcached driver, you must first [configure your memcached server](https://github.com/memcached/memcached/wiki/ConfiguringServer).

After that, you just need to change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'memcached'
```

<a id="redis-driver"></a>

## Redis Driver

Before using the redis driver, you must first [configure your redis server](/docs/database/redis#configuration).

After that, you just need to change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'redis'
```

<a id="memory-driver"></a>

## Memory Driver

The `'memory'` driver keeps session data in an array for the current request and
writes nothing to disk, which suits unit tests.

> This driver should not be used for purposes other than testing!

<a id="sweeping"></a>

## Sweeping

The `file` and `database` drivers keep writing session data until something
deletes it. Rakit takes care of that itself: on a small share of requests it
deletes every session whose last activity is older than `'lifetime'`.

How often that happens is set by the `'sweep'` option in the
`application/config/session.php` file, written as `[chances, out_of]`:

```php
'sweep' => [2, 100],
```

The default runs the cleanup on roughly 2 out of every 100 requests. Set it to
`false` to turn it off, for example when you would rather clean the table with a
scheduled job:

```php
'sweep' => false,
```

The other drivers are never swept, because their own storage already expires the
data: cookie sessions live on the visitor's device, while memcached, redis and
apc are given the session lifetime as their expiration.
