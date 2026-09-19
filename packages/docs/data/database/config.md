# Database Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Quick Start with SQLite](#quick-start-with-sqlite)
- [Using Other Databases](#using-other-databases)
- [Setting the Default Connection](#setting-the-default-connection)
- [Overriding Default PDO Options](#overriding-default-pdo-options)
- [Managing Connections](#managing-connections)

<!-- /MarkdownTOC -->

Rakit supports the following databases by default:

- MySQL
- PostgreSQL
- SQLite
- SQL Server

All database configuration options are located in the `application/config/database.php` file.

<a id="quick-start-with-sqlite"></a>

## Quick Start with SQLite

Rakit is configured for [SQLite](https://sqlite.org) out of the box, so you can start
without setting up a database server.

Rakit will automatically store all SQLite files in the `storage/database/` folder
with the `.sqlite` extension, so the default database lives in `storage/database/application.sqlite`.

To name it something other than `'application'`, change the option in
`application/config/database.php`:

```php
'sqlite' => [
	'driver'   => 'sqlite',
	'database' => 'your_database_name',
],
```

SQLite handles up to roughly 100,000 visits a day. Past that, move to MySQL or
PostgreSQL.

<a id="using-other-databases"></a>

## Using Other Databases

`application/config/database.php` ships a sample configuration for MySQL, SQL Server
and PostgreSQL. Fill in the one you use, then set it as the default connection.

<a id="setting-the-default-connection"></a>

## Setting the Default Connection

Every connection in `application/config/database.php` has a name, and four are
defined by default: `sqlite`, `mysql`, `sqlsrv` and `pgsql`. The `'default'` option
picks the one to use:

```php
'default' => 'sqlite',
```

This default connection is what will always be used by the [Query Builder](/docs/database/magic).
If you need to change the default connection during request execution, use `Config::set()`.

<a id="overriding-default-pdo-options"></a>

## Overriding Default PDO Options

`System\Database\Connectors\Connector` sets a few PDO attributes by default, which the
configuration file can override. One of them, `PDO::CASE_LOWER`, lowercases every
column name, so query results are read in lowercase whatever the table declares.

For MySQL:

The attributes go under the `'options'` key of the connection. Attributes written
directly on the connection array are not read.

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
	],
],
```

The same key is where a persistent connection is asked for, which keeps the
connection open between requests instead of opening a new one every time:

```php
'options' => [
	PDO::ATTR_PERSISTENT => true,
],
```

More information about PDO connection attributes can be found in the [official documentation](https://www.php.net/manual/en/pdo.setattribute.php).

<a id="managing-connections"></a>

## Managing Connections

A connection is opened the first time it is used and then kept for the rest of the
request, which is what you want for a request that ends in milliseconds. A process
that stays alive — the WebSocket server, or a `job:runall` left running — may need
to let one go.

Close a connection with `disconnect()`. The connection stays registered, so the next
time it is asked for it opens again by itself:

```php
DB::disconnect();         // the default connection
DB::disconnect('mysql');  // a named one
```

`reconnect()` closes it and opens it again in one step. It returns the same connection
instance as before, so a variable that is already holding it keeps working:

```php
$connection = DB::connection();

DB::reconnect();

$connection->table('users')->get(); // fine, it is the same instance
```

`purge()` closes the connection and forgets it entirely. The next call to
`connection()` builds a new one from the configuration file, so this is the one to
use after changing the configuration at runtime:

```php
Config::set('database.connections.mysql.database', 'another');

DB::purge('mysql');
```

> Whatever an open transaction had done so far is lost when the connection closes,
> and an in-memory SQLite database is gone entirely, since its contents live in the
> connection itself.

A persistent connection is the exception. PDO hands it back to its pool rather than
closing it, and the connection opened after that is the same one, still carrying
whatever session state it had. `disconnect()` and `reconnect()` still give you a
fresh PDO instance, they just do not give you a fresh connection to the server.
