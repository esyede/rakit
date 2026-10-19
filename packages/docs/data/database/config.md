# Database Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Quick Start with SQLite](#quick-start-with-sqlite)
- [Using Other Databases](#using-other-databases)
- [Setting the Default Connection](#setting-the-default-connection)
- [Overriding Default PDO Options](#overriding-default-pdo-options)
- [Managing Connections](#managing-connections)

<!-- /MarkdownTOC -->

Rakit supports MySQL, PostgreSQL, SQLite and SQL Server out of the box. All database
configuration lives in `application/config/database.php`.

<a id="quick-start-with-sqlite"></a>

## Quick Start with SQLite

Rakit is configured for [SQLite](https://sqlite.org) by default, so no database server is
needed. SQLite files are stored in `storage/database/` with a `.sqlite` extension, so the
default database is `storage/database/application.sqlite`. To rename it:

```php
'sqlite' => [
	'driver'   => 'sqlite',
	'database' => 'your_database_name', // or ':memory:' for an in-memory database
],
```

SQLite comfortably handles up to roughly 100,000 visits a day; beyond that, move to MySQL or
PostgreSQL.

<a id="using-other-databases"></a>

## Using Other Databases

The config file ships sample connections for MySQL (`mysql`), PostgreSQL (`pgsql`) and
SQL Server (`sqlsrv`). Fill in the one you use, then make it the default connection.

<a id="setting-the-default-connection"></a>

## Setting the Default Connection

The `'default'` option names the connection used by the [Query Builder](/docs/database/magic)
whenever no other connection is given:

```php
'default' => 'sqlite',
```

To switch it during a request, use `Config::set('database.default', 'mysql')`.

The `'fetch'` option sets the PDO fetch style of query results. The default,
`PDO::FETCH_CLASS`, returns `stdClass` objects; use `PDO::FETCH_ASSOC` for arrays.

<a id="overriding-default-pdo-options"></a>

## Overriding Default PDO Options

Every connection is opened with these PDO attributes, which you can override under the
connection's `'options'` key (attributes placed directly on the connection array are ignored):

```php
'mysql' => [
	'driver'   => 'mysql',
	'host'     => 'localhost',
	'database' => 'database',
	'username' => 'root',
	'password' => '',
	'charset'  => 'utf8',
	'prefix'   => '',

	'options'  => [
		PDO::ATTR_CASE              => PDO::CASE_LOWER,
		PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
		PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
		PDO::ATTR_STRINGIFY_FETCHES => false,
		PDO::ATTR_EMULATE_PREPARES  => false,
		PDO::ATTR_PERSISTENT        => true, // optional: reuse the connection between requests
	],
],
```

Note that `PDO::CASE_LOWER` lowercases every column name in query results, whatever the table
declares. See the [PDO documentation](https://www.php.net/manual/en/pdo.setattribute.php) for
all attributes.

<a id="managing-connections"></a>

## Managing Connections

A connection opens on first use and stays open for the rest of the request. Long-running
processes (the WebSocket server, a `job:runall` worker) may need to release or refresh it:

```php
DB::disconnect();         // close the default connection
DB::disconnect('mysql');  // close a named one; it reopens by itself on next use

DB::reconnect();          // close and reopen, returning the same Connection instance

Config::set('database.connections.mysql.database', 'another');
DB::purge('mysql');       // close and forget it; the next use rebuilds it from config
```

Because `reconnect()` keeps the same instance, a variable already holding the connection keeps
working. Use `purge()` after changing the configuration at runtime.

> Closing a connection discards any uncommitted transaction, and an in-memory SQLite database
> is lost entirely. A persistent connection is returned to PDO's pool rather than closed, so
> `disconnect()` and `reconnect()` give you a fresh PDO instance but the same server session.
