# Database Configuration

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Quick Start with SQLite](#quick-start-with-sqlite)
-   [Using Other Databases](#using-other-databases)
-   [Setting the Default Connection](#setting-the-default-connection)
-   [Overriding Default PDO Options](#overriding-default-pdo-options)

<!-- /MarkdownTOC -->

Rakit supports the following databases by default:

-   MySQL
-   PostgreSQL
-   SQLite
-   SQL Server

All database configuration options are located in the `application/config/database.php` file.

<a id="quick-start-with-sqlite"></a>

## Quick Start with SQLite

[SQLite](https://sqlite.org) is a great database system, and its configuration is straightforward.
By default, Rakit is configured to use SQLite. Yes, the purpose is so you can try Rakit without having to bother setting up a database.

Rakit will automatically store all SQLite files in the `application/storage/database/` folder
with the name `'xxxxxx-application'` where `xxxxxx` is a 32-character random string automatically added
to the front of your original database name for security reasons.

Of course, you can name it something other than `'application'`, to do so,
just change the configuration option in the `application/config/database.php` file like this:

```php
'sqlite' => [
	'driver'   => 'sqlite',
	'database' => 'your_database_name',
],
```

If your application receives less than 100,000 visits per day, SQLite is sufficient to handle it.
However, if otherwise, please use MySQL or PostgreSQL.

<a id="using-other-databases"></a>

## Using Other Databases

If you are using MySQL, SQL Server, or PostgreSQL, you need to change the configuration options
in `application/config/database.php`. In that file, you can find sample
configurations for each database system.

Just change it according to your needs and don't forget to set the default connection.

<a id="setting-the-default-connection"></a>

## Setting the Default Connection

As you may have noticed, each database connection configured in
the `application/config/database.php` file has a connection name.

By default, there are four connections defined: `sqlite`, `mysql`, `sqlsrv`, and `pgsql`.
You can freely change these connection names. The default connection can be set through the `'default'` option like this:

```php
'default' => 'sqlite';
```

This default connection is what will always be used by the [Query Builder](/docs/id/database/magic).
If you need to change the default connection during request execution, use `Config::set()`.

<a id="overriding-default-pdo-options"></a>

## Overriding Default PDO Options

The database connector component (`System\Database\Connector`) has a set of default PDO attribute definitions
that can be overridden via the configuration file.

As an example, one of the default attributes forces column names to be lowercase (`PDO::CASE_LOWER`) even if they are defined in UPPERCASE or camelCase in the table.

Therefore, by default, model objects from queries can only be accessed using lowercase.

Example configuration for a MySQL system by adding default PDO attributes:

```php
'mysql' => [
	'driver'   => 'mysql',
	'host'     => 'localhost',
	'database' => 'database',
	'username' => 'root',
	'password' => '',
	'charset'  => 'utf8',
	'prefix'   => '',

	PDO::ATTR_CASE              => PDO::CASE_LOWER,
	PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
	PDO::ATTR_STRINGIFY_FETCHES => false,
	PDO::ATTR_EMULATE_PREPARES  => false,
],
```

More information about PDO connection attributes can be found in the [official documentation](http://php.net/manual/en/pdo.setattribute.php).
