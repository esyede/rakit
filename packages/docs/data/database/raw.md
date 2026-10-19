# Raw Queries

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Running Raw Queries](#running-raw-queries)
-   [Other Methods](#other-methods)
-   [Query Log](#query-log)
-   [PDO Connection](#pdo-connection)

<!-- /MarkdownTOC -->

<a id="running-raw-queries"></a>
<a id="basic-knowledge"></a>

## Running Raw Queries

`DB::query()` sends SQL straight to the default connection. Always pass user values as `?`
bindings rather than concatenating them into the SQL:

```php
$users = DB::query('select * from users');
$users = DB::query('select * from users where name = ?', ['test']);

DB::query('insert into users values (?, ?)', $bindings);

$affected = DB::query('update users set name = ?', $bindings);
$affected = DB::query('delete from users where id = ?', [1]);
```

What comes back depends on the statement:

| Statement                                     | Returns                                                   |
| --------------------------------------------- | --------------------------------------------------------- |
| `SELECT`, `SHOW`                              | array of rows                                             |
| `UPDATE`, `DELETE`                            | number of affected rows                                   |
| `INSERT`, or anything with `RETURNING`        | fetched rows (an empty array unless there is `RETURNING`) |
| anything else (`CREATE TABLE`, ...)           | boolean                                                   |

A failing query throws a `QueryException`. To run on another connection, use
`DB::connection('mysql')->query(...)`.

<a id="other-methods"></a>

## Other Methods

```php
$user  = DB::first('select * from users where id = 1');     // first row, or NULL
$email = DB::only('select email from users where id = 1');  // first column of first row
```

<a id="query-log"></a>

## Query Log

While the `database` option in `application/config/debugger.php` is enabled, every query is
logged with its bindings, time in milliseconds and the file:line that issued it:

```php
$queries = DB::profile();    // all logged queries
$last    = DB::last_query(); // the last one, or FALSE when there is none
```

Each logged query also fires the `rakit.query` [hook](/docs/hooks).

<a id="pdo-connection"></a>

## PDO Connection

For anything the database classes do not cover, use the underlying `PDO` object:

```php
$pdo = DB::connection('sqlite')->pdo();
```

Without a connection name, `DB::connection()` returns the default connection.
