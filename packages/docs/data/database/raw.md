# Raw Queries

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Other Methods](#other-methods)
-   [PDO Connection](#pdo-connection)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`query()` sends SQL straight to the database connection and runs it as it is.

#### Retrieving records from the database:

```php
$users = DB::query('select * from users');
```

#### Retrieving records from the database using data binding:

```php
$users = DB::query('select * from users where name = ?', ['test']);
```

#### Inserting a record into the database:

```php
DB::query('insert into users values (?, ?)', $bindings);
```

> An `INSERT` returns the fetched rows of the statement (an empty array unless it has a `RETURNING`
> clause), not a boolean. A failing query throws a `QueryException`.

#### Updating records and returning the number of affected rows:

```php
$affected = DB::query('update users set name = ?', $bindings);
```

#### Deleting records and returning the number of affected rows:

```php
$affected = DB::query('delete from users where id = ?', [1]);
```

<a id="other-methods"></a>

## Other Methods

A few shorthands:

#### Running `SELECT` and returning the first result:

```php
$user = DB::first('select * from users where id = 1');
```

#### Running `SELECT` and returning the value of a column:

```php
$email = DB::only('select email from users where id = 1');
```

<a id="pdo-connection"></a>

## PDO Connection

For something the database classes do not cover, reach for the PDO object itself:

#### Accessing the raw PDO connection object:

```php
$pdo = DB::connection('sqlite')->pdo();
// dd($pdo); // will contain an object from the \PDO class
```

> If no connection name is provided, it will return the object for the default connection (the `default` option in `application/config/database.php`).
