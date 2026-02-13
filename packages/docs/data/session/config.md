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

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The web is a stateless environment. This means that each request to your application is considered unrelated to the previous request.
However, sessions allow you to store data statically for each visitor to your application.
Session data for each visitor is stored on your web server, while a cookie containing the "Session ID" is stored on the visitor's device.
This cookie allows your application to "remember" the session for that user and retrieve their session data on subsequent requests to your application.

By default, six drivers have been provided for sessions, namely:

-   Cookie
-   File
-   Database
-   Memcached
-   Redis
-   Memory (Array)

<a id="cookie-driver"></a>

## Cookie Driver

Cookie-based sessions provide a lightweight and fast mechanism for storing session data.
They are also secure. Each cookie is encrypted using strong AES-256 encryption.

However, cookies have a storage limit of `4 kilobytes`, so you may need to use
another driver if you want to store a lot of data in the session.

To start using this cookie driver, simply change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'cookie'
```

<a id="file-driver"></a>

## File Driver

Most likely, your application will work well enough just using this file driver.
However, if your application receives very heavy traffic, use the database or memcache driver.

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

Next, you need to create a session table. Here are some SQL queries to help you get started.

However, you can also use the [console](/docs/console) to create this table automatically!

<a id="console"></a>

### Console

```bash
php rakit session:table
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

Before using the redis driver, you must first [configure your redis server](/docs/database/redis#config).

After that, you just need to change the driver option in the `application/config/session.php` file as follows:

```php
'driver' => 'redis'
```

<a id="memory-driver"></a>

## Memory Driver

The `'memory'` driver only uses a simple array to store your session data for the current request.
This driver is good for unit-testing your application as no data is written to disk.

> This driver should not be used for purposes other than testing!
