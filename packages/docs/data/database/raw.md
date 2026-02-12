# Raw Queries

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Other Methods](#other-methods)
-   [PDO Connection](#pdo-connection)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Raw queries are lines of queries written directly, which will be sent to the database server and executed immediately.
The `query()` method is used to execute raw SQL queries against your database connection.

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
$success = DB::query('insert into users values (?, ?)', $bindings);
```

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

Rakit provides several other methods to make database queries simpler. Here are some examples:

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

Sometimes you may want to access the raw PDO connection object directly from Rakit's Connection Object.
For example, if the query you want to run is not supported by Rakit's database classes. Don't worry, you can do it.

#### Accessing the raw PDO connection object:

```php
$pdo = DB::connection('sqlite')->pdo();
// dd($pdo); // will contain an object from the \PDO class
```

> If no connection name is provided, it will return the object for the `'default'` connection.
