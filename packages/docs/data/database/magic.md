# Magic Query Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Retrieving Records](#retrieving-records)
- [Select Columns](#select-columns)
- [Building Where Clauses](#building-where-clauses)
  - [where and or_where](#where-and-or_where)
  - [where_id and or_where_id](#where_id-and-or_where_id)
  - [where_in, where_not_in, or_where_in, and or_where_not_in](#where_in-where_not_in-or_where_in-and-or_where_not_in)
  - [where_integer_in_raw and where_integer_not_in_raw](#where_integer_in_raw-and-where_integer_not_in_raw)
  - [where_null, where_not_null, or_where_null, and or_where_not_null](#where_null-where_not_null-or_where_null-and-or_where_not_null)
  - [where_between, where_not_between, or_where_between, and or_where_not_between](#where_between-where_not_between-or_where_between-and-or_where_not_between)
  - [where_date, where_month, where_day, where_year, where_time](#where_date-where_month-where_day-where_year-where_time)
  - [where_any, where_all, and where_none](#where_any-where_all-and-where_none)
  - [where_exists and where_not_exists](#where_exists-and-where_not_exists)
  - [where_in_sub and where_not_in_sub](#where_in_sub-and-where_not_in_sub)
- [Nested Where](#nested-where)
- [Dynamic Where](#dynamic-where)
- [Raw Where](#raw-where)
- [Table Join](#table-join)
- [Order By](#order-by)
- [Group By & Having](#group-by--having)
- [Skip & Take](#skip--take)
- [Union & Union All](#union--union-all)
- [Aggregates](#aggregates)
- [Raw SQL Expressions](#raw-sql-expressions)
- [Single Values & Lists](#single-values--lists)
- [Chunking & Lazy Iteration](#chunking--lazy-iteration)
- [Insert Record](#insert-record)
- [Update Record](#update-record)
- [Increment & Decrement](#increment--decrement)
- [Delete Record](#delete-record)
- [Pagination](#pagination)
- [Conditional Clauses](#conditional-clauses)
- [Copy & Reset Query](#copy--reset-query)
- [Debug Query](#debug-query)
- [Transaction](#transaction)
- [Row Locking](#row-locking)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The Magic Query Builder builds and runs SQL with a fluent API. Every query is sent as a
[prepared statement](https://www.php.net/manual/en/pdo.prepared-statements.php), so values are
safe from SQL injection. Start a query with `DB::table()`:

```php
$query = DB::table('users');
```

Use `DB::table('users', 'mysql')` or `DB::connection('mysql')->table('users')` for a
non-default connection.

<a id="retrieving-records"></a>
<a id="find-or-fail"></a>
## Retrieving Records

```php
$users = DB::table('users')->get();       // all rows
$user  = DB::table('users')->first();     // first row, or NULL
$user  = DB::table('users')->find($id);   // by the `id` column, or NULL
```

`get()` returns a `Collection` of `stdClass` objects (empty when nothing matches). It is
countable, iterable and array accessible, and has the usual collection methods:

```php
$names = DB::table('users')->get()->pluck('name');
```

`find_or_fail()` and `first_or_fail()` stop the request with a 404 response (via `abort(404)`)
when no record is found:

```php
$user = DB::table('users')->find_or_fail($id);
$user = DB::table('users')->where('email', '=', $email)->first_or_fail();
```

> On the query builder these do not throw a catchable exception. Only a
> [Facile model](/docs/database/facile) query throws `ModelNotFoundException`.

<a id="select-columns"></a>
<a id="distinct"></a>
## Select Columns

```php
$users = DB::table('users')->get(['id', 'email as user_email']);

$users = DB::table('users')->select(['id', 'name'])->get();
$users = DB::table('users')->select('id', 'name')->add_select('email')->get();
$users = DB::table('products')->select_raw('name, price * ? as price_with_tax', [1.1])->get();

$countries = DB::table('users')->distinct()->get(['country']);
```

`add_select()` and `select_raw()` append to the select list; `select()` replaces it.

<a id="building-where-clauses"></a>
## Building Where Clauses

<a id="where-and-or_where"></a>
### where and or_where

```php
$users = DB::table('users')
    ->where('votes', '>', 100)
    ->where('active', 1)                    // operator omitted means '='
    ->or_where('email', '=', 'admin@mail.com')
    ->get();
```

Supported operators:

```php
'=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
'like', 'like binary', 'not like', 'ilike',
'&', '|', '^', '<<', '>>', '&~',
'rlike', 'not rlike', 'regexp', 'not regexp',
'~', '~*', '!~', '!~*',
'similar to', 'not similar to', 'not ilike', '~~*', '!~~*'
```

Any other operator throws, so never pass one taken from the request. Two shortcuts exist for
common cases:

```php
DB::table('users')->where_like('name', '%john%');   // also or_where_like, where_not_like, or_where_not_like
DB::table('orders')->where_column('paid_total', '!=', 'total');  // compare two columns
```

<a id="where_id-and-or_where_id"></a>
### where_id and or_where_id

Shortcuts for a condition on the `id` column:

```php
$user = DB::table('users')->where_id(1)->first();   // where('id', '=', 1)
$users = DB::table('users')->where('role', '=', 'admin')->or_where_id(5)->get();
```

<a id="where_in-where_not_in-or_where_in-and-or_where_not_in"></a>
### where_in, where_not_in, or_where_in, and or_where_not_in

```php
$users = DB::table('users')->where_in('id', [1, 2, 3])->get();
$users = DB::table('users')->where_not_in('id', [1, 2, 3])->get();

$users = DB::table('users')
    ->where('votes', '>', 100)
    ->or_where_in('name', ['John', 'Jane'])
    ->get();
```

<a id="where_integer_in_raw-and-where_integer_not_in_raw"></a>
### where_integer_in_raw and where_integer_not_in_raw

Like `where_in()`, but the values are written into the SQL instead of bound. Use them for very
long ID lists that would exceed the driver's bound parameter limit (2100 on SQL Server, 999 on
SQLite older than 3.32, 65535 on MySQL and PostgreSQL):

```php
$users = DB::table('users')->where_integer_in_raw('id', $ids)->get();
// SELECT * FROM "users" WHERE "id" IN (1, 2, 3)
```

`where_integer_not_in_raw()`, `or_where_integer_in_raw()` and `or_where_integer_not_in_raw()` are
available too.

> Only integers (an `int` or a digit string such as `'42'`) are accepted. Anything else
> (`'1 OR 1 = 1'`, `1.5`, `null`, ...) throws an `InvalidArgumentException`.

<a id="where_null-where_not_null-or_where_null-and-or_where_not_null"></a>
### where_null, where_not_null, or_where_null, and or_where_not_null

```php
$users = DB::table('users')->where_null('deleted_at')->get();
$users = DB::table('users')->where_not_null('email_verified_at')->get();
$users = DB::table('users')->where('active', '=', 1)->or_where_null('deleted_at')->get();
```

<a id="where_between-where_not_between-or_where_between-and-or_where_not_between"></a>
### where_between, where_not_between, or_where_between, and or_where_not_between

```php
$users  = DB::table('users')->where_between('votes', 1, 100)->get();
$users  = DB::table('users')->where_not_between('age', 18, 30)->get();
$orders = DB::table('orders')
    ->where('status', '=', 'paid')
    ->or_where_between('created_at', '2024-01-01', '2024-12-31')
    ->get();
```

<a id="where_date-where_month-where_day-where_year-where_time"></a>
### where_date, where_month, where_day, where_year, where_time

Compare only part of a date/time column:

```php
DB::table('orders')->where_date('created_at', '=', '2024-01-15');
DB::table('orders')->where_month('created_at', '=', 1);     // 1-12
DB::table('orders')->where_day('created_at', '=', 15);      // 1-31
DB::table('orders')->where_year('created_at', '=', 2024);
DB::table('orders')->where_time('created_at', '>=', '17:00:00');
```

A `DateTime` or `Carbon` value is reduced to the compared part, so
`where_date('created_at', '=', Carbon::now())` matches the whole day. The SQL depends on the
driver:

| Method        | MySQL        | PostgreSQL                | SQLite                                 | SQL Server          |
| ------------- | ------------ | ------------------------- | -------------------------------------- | ------------------- |
| `where_date`  | `DATE(col)`  | `CAST(col AS DATE)`       | `strftime('%Y-%m-%d', col)`            | `CAST(col AS DATE)` |
| `where_time`  | `TIME(col)`  | `CAST(col AS TIME)`       | `strftime('%H:%M:%S', col)`            | `CAST(col AS TIME)` |
| `where_day`   | `DAY(col)`   | `EXTRACT(DAY FROM col)`   | `CAST(strftime('%d', col) AS INTEGER)` | `DAY(col)`          |
| `where_month` | `MONTH(col)` | `EXTRACT(MONTH FROM col)` | `CAST(strftime('%m', col) AS INTEGER)` | `MONTH(col)`        |
| `where_year`  | `YEAR(col)`  | `EXTRACT(YEAR FROM col)`  | `CAST(strftime('%Y', col) AS INTEGER)` | `YEAR(col)`         |

> On SQLite the column must hold text such as `2024-01-15 08:30:00` (the format Rakit writes);
> `strftime()` cannot read an integer unix timestamp. The column name is validated, so an
> unchecked `Input::get('col')` is rejected with `Invalid column identifier` instead of being
> interpolated.

<a id="where_any-where_all-and-where_none"></a>
### where_any, where_all, and where_none

Apply one condition to several columns, grouped in parentheses:

```php
DB::table('users')->where('active', '=', 1)->where_any(['name', 'email'], 'like', '%budi%');
// WHERE "active" = ? AND ("name" like ? OR "email" like ?)

DB::table('posts')->where_all(['title', 'body'], 'like', '%rakit%');
// WHERE ("title" like ? AND "body" like ?)

DB::table('posts')->where_none(['title', 'body'], 'like', '%spam%');
// WHERE NOT ("title" like ? OR "body" like ?)
```

The operator may be omitted for equality, and each has an `or_` variant (`or_where_any()`,
`or_where_all()`, `or_where_none()`).

> A `NULL` column makes the condition unknown, so `where_none()` does not match that row either.
> An empty column list throws an `InvalidArgumentException`.

<a id="where_exists-and-where_not_exists"></a>
### where_exists and where_not_exists

Pass a closure that builds the subquery (or a ready `Query`):

```php
$users = DB::table('users')
    ->where_exists(function ($query) {
        $query->from('orders')->where_raw('orders.user_id = users.id');
    })
    ->get();
```

`where_not_exists()` works the same way.

<a id="where_in_sub-and-where_not_in_sub"></a>
### where_in_sub and where_not_in_sub

```php
$users = DB::table('users')
    ->where_in_sub('id', DB::table('orders')->select('user_id')->where('status', '=', 'completed'))
    ->get();

$users = DB::table('users')
    ->where_not_in_sub('id', DB::table('banned_users')->select('user_id'))
    ->get();
```

<a id="nested-where"></a>
## Nested Where

Group conditions in parentheses with `where_nested()` / `or_where_nested()` (passing a closure to
`where()` does the same):

```php
$users = DB::table('users')
    ->where('name', '=', 'John')
    ->where_nested(function ($query) {
        $query->where('votes', '>', 100)->or_where('title', '=', 'Admin');
    })
    ->get();

// WHERE name = ? AND (votes > ? OR title = ?)
```

<a id="dynamic-where"></a>
## Dynamic Where

Any `where_{column}` method becomes an equality condition on that column, and `_and_` / `_or_`
combine several:

```php
$user  = DB::table('users')->where_email($email)->first();
$user  = DB::table('users')->where_email_and_password($email, $password)->first();
$users = DB::table('users')->where_email_or_username($email, $username)->get();
```

Some names are reserved and throw instead of becoming a column condition:

-   `where_has()`, `where_doesnt_have()`, `where_relation()`, `where_belongs_to()`, `where_key()`
    and `where_key_not()` only exist on a [Facile model](/docs/database/facile#relationship-queries)
    query.
-   `where_json_contains()`, `where_json_length()`, `where_full_text()` and
    `where_morph_relation()` are not supported yet.

For a column with one of these names (e.g. `key`), use `where('key', '=', $value)`.

<a id="raw-where"></a>
## Raw Where

```php
$users = DB::table('users')->raw_where('age > ? AND city = ?', [18, 'Jakarta'])->get();

$users = DB::table('users')
    ->where('active', '=', 1)
    ->raw_or_where('(votes > 100 OR role = ?)', ['admin'])
    ->get();
```

`where_raw()` and `or_where_raw()` are aliases of `raw_where()` and `raw_or_where()`.

<a id="table-join"></a>
<a id="left-join"></a>
## Table Join

```php
$users = DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->get(['users.*', 'contacts.phone']);
```

`left_join()`, `right_join()` and `cross_join()` take the same arguments. For several join
conditions pass a closure, which receives a join with `on()` / `or_on()` (column to column) and
`where()` / `or_where()` (column to a bound value):

```php
$users = DB::table('users')
    ->left_join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')
             ->or_on('users.email', '=', 'contacts.email')
             ->where('contacts.type', 'phone'); // two arguments mean '='
    })
    ->get();
```

> Join operators are `=`, `<`, `>`, `<=`, `>=`, `<>`, `!=` and `<=>`; `where()` also accepts
> `like` and `not like`. A `null` value in `where()` compiles to `IS NULL` / `IS NOT NULL`.

<a id="order-by"></a>
## Order By

```php
$users = DB::table('users')->order_by('name')->get();                  // asc by default
$users = DB::table('users')->order_by('country')->order_by('created_at', 'desc')->get();

DB::table('articles')->latest()->take(10)->get();   // order by created_at desc (oldest() for asc)
DB::table('users')->in_random_order()->first();
DB::table('users')->order_by_raw('FIELD(status, ?, ?)', ['active', 'banned']);
$query->re_order('name');                           // drop previous orderings, optionally add a new one
```

> The direction must be `asc` or `desc`, anything else throws. Use `order_by_raw()` when you
> really mean raw SQL, and never pass request input to it.

<a id="group-by--having"></a>
## Group By & Having

```php
$users = DB::table('orders')
    ->select(['user_id', DB::raw('COUNT(*) as order_count')])
    ->group_by('user_id')
    ->having('order_count', '>', 5)
    ->get();
```

`or_having()`, `having_raw($sql, $bindings)`, `or_having_raw()` and `group_by_raw()` are available
for OR and raw variants.

<a id="skip--take"></a>
<a id="for-page"></a>
## Skip & Take

```php
$users = DB::table('users')->skip(20)->take(10)->get();   // OFFSET 20 LIMIT 10
$users = DB::table('users')->for_page(3, 20)->get();     // same as skip(40)->take(20)
```

<a id="union--union-all"></a>
## Union & Union All

```php
$admins = DB::table('users')->where('role', '=', 'admin');

$users = DB::table('users')->where('active', '=', 1)->union($admins)->get();
$users = DB::table('users')->where('active', '=', 1)->union_all($admins)->get();
```

<a id="aggregates"></a>
## Aggregates

```php
$count = DB::table('users')->where('active', '=', 1)->count();
$max   = DB::table('users')->max('votes');
$min   = DB::table('users')->min('age');
$avg   = DB::table('products')->avg('price');
$sum   = DB::table('orders')->sum('amount');
$value = DB::table('orders')->aggregate('MAX', ['amount']);   // any aggregate function

if (DB::table('users')->where('email', '=', $email)->exists()) { /* .. */ }   // or doesnt_exist()
```

<a id="raw-sql-expressions"></a>
## Raw SQL Expressions

`DB::raw()` inserts SQL as-is into a select list, condition or ordering:

```php
$users = DB::table('users')
    ->select(['*', DB::raw('DATE(created_at) as date')])
    ->where(DB::raw('YEAR(created_at)'), '=', 2024)
    ->order_by(DB::raw('votes * 2'), 'desc')
    ->get();
```

Raw expressions are not escaped. If you must build one from a value, quote it with
`DB::escape($value)`.

<a id="single-values--lists"></a>
<a id="only"></a>
<a id="lists"></a>
## Single Values & Lists

```php
$email = DB::table('users')->where('id', '=', 1)->only('email');   // one value, or NULL
$email = DB::table('users')->where('id', '=', 1)->value('email');  // alias of only()

$emails = DB::table('users')->lists('email');        // ['a@test.com', 'b@test.com', ...]
$names  = DB::table('users')->lists('name', 'id');   // [1 => 'John', 2 => 'Jane', ...]
$names  = DB::table('users')->pluck('name', 'id');   // same, as a Collection

$user = DB::table('users')->where('email', '=', $email)->sole();   // exactly one row
```

`sole()` throws when there is no matching row or more than one.

<a id="chunking--lazy-iteration"></a>
<a id="chunking--cursor"></a>
<a id="cursor"></a>
## Chunking & Lazy Iteration

To process a large table without loading it all, use `chunk()`, which passes each chunk (and the
page number) to the callback. Return `false` to stop. Like Laravel, `chunk()`, `each()` and `lazy()`
require an `order_by()` (otherwise a `RuntimeException` is thrown); Facile models order by their
key automatically:

```php
DB::table('users')->order_by('id')->chunk(500, function ($users, $page) {
    foreach ($users as $user) {
        // ..
    }
});
```

`chunk_by_id($count, $callback, $column = null, $alias = null)` pages by the ID (`WHERE id > last
ORDER BY id LIMIT n`) instead of an offset, which is faster on big tables and safe when the
callback updates the rows. `chunk_by_id_desc()` walks the IDs downwards. `$column` defaults to
`id`; pass `$alias` when the column comes back under another name. `each($callback, $count = 1000)`
and `each_by_id($callback, $count = 1000, $column = null, $alias = null)` call the callback once
per row, with `($row, $key)`; return `false` to stop.

`lazy($chunk_size = 1000)` does the same chunked fetching, but hands back a generator to
`foreach` over. It needs an `order_by()`, and throws without one. `lazy_by_id()` and
`lazy_by_id_desc()` (same arguments as `chunk_by_id()`, chunk size first) page by the ID:

```php
foreach (DB::table('orders')->order_by('id')->lazy(500) as $order) {
    // ..
}

foreach (DB::table('orders')->where('paid', '=', 0)->lazy_by_id() as $order) {
    DB::table('orders')->where('id', '=', $order->id)->update(['paid' => 1]);
}
```

`cursor()` runs **one** query and streams its rows one at a time from the PDO statement. The
columns come from `select()`:

```php
foreach (DB::table('orders')->select(['id', 'total'])->cursor() as $order) {
    // ..
}
```

| Method | Queries | Use it when |
| --- | --- | --- |
| `chunk()`, `each()`, `lazy()` | one per chunk | you need batches, or eager loading with Facile |
| `chunk_by_id()`, `each_by_id()`, `lazy_by_id()` (and `_desc`) | one per chunk | you update or delete the rows while iterating, or the table is big |
| `cursor()` | one | you want the least memory per row and no eager loading |

> `cursor()` keeps only one row in PHP at a time, but `pdo_mysql` buffers the whole result set by default (`PDO::MYSQL_ATTR_USE_BUFFERED_QUERY`), so memory can still grow with the result. Chunk sizes below 1 make `lazy()` and `lazy_by_id()` throw an `InvalidArgumentException`.

> Generators need PHP 5.5+. On PHP 5.4 `lazy()` and `lazy_by_id()` return a `System\Database\Query\Lazy` and `cursor()` a `System\Database\Query\Cursor` instead: plain `Iterator`s that work in `foreach`. They are not arrays and not `Countable`; use `iterator_to_array()` if you need one. Like a generator, a cursor can not be rewound once it has moved past its first row.

<a id="insert-record"></a>
## Insert Record

```php
DB::table('users')->insert(['name' => 'John', 'email' => 'john@example.com']);

DB::table('users')->insert([            // several rows at once
    ['name' => 'User 1', 'email' => 'user1@test.com'],
    ['name' => 'User 2', 'email' => 'user2@test.com'],
]);

$id = DB::table('users')->insert_get_id(['name' => 'Jane']);
$id = DB::table('users')->insert_get_id(['name' => 'Bob'], 'user_id');   // custom id column

DB::table('users')->insert_or_ignore($rows);   // silently skip rows that clash with a unique key

DB::table('users')->upsert($rows, 'email', ['name', 'active']); // insert, or update on a clash
```

`upsert($values, $unique_by, $update = null)` inserts the rows and, for those whose `unique_by`
columns match an existing row, updates the `update` columns instead (all inserted columns when
`null`, nothing when `[]`). The columns need a unique index or the primary key (on MySQL, any
unique key decides). It runs as one query: `ON DUPLICATE KEY UPDATE` on MySQL, `ON CONFLICT` on
PostgreSQL and SQLite 3.24+, `MERGE` on SQL Server.

`insert()` returns `true` (a failure throws). `insert_get_id()` returns an `int` when the driver
gives digits, the raw string otherwise (so UUIDs survive), and `null` when the driver has none
(e.g. an application-generated key). On PostgreSQL pass the sequence name as the third argument:
`insert_get_id($values, 'id', 'users_id_seq')`.

<a id="update-record"></a>
## Update Record

`update()` returns the number of affected rows:

```php
$affected = DB::table('users')
    ->where('id', '=', 1)
    ->update(['name' => 'New Name', 'email' => 'new@example.com']);
```

`update_or_insert($attributes, $values)` updates the row matching `$attributes`, or inserts
`$attributes + $values` when there is none:

```php
DB::table('settings')->update_or_insert(['key' => 'theme'], ['value' => 'dark']);
```

<a id="increment--decrement"></a>
## Increment & Decrement

```php
DB::table('users')->where('id', '=', 1)->increment('votes');        // +1
DB::table('users')->where('id', '=', 1)->increment('votes', 5);
DB::table('products')->where('id', '=', 1)->decrement('stock', 10, ['updated_at' => $now]);
```

The amount must be numeric; the optional third argument updates other columns at the same time.

<a id="delete-record"></a>
## Delete Record

`delete()` returns the number of deleted rows. Without a `where()` it deletes every row:

```php
DB::table('users')->where('active', '=', 0)->delete();
```

`update()`, `increment()` / `decrement()` and `delete()` honour joins, so the join can filter the
affected rows:

```php
DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->where('contacts.blocked', '=', 1)
    ->update(['users.active' => 0]);

DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->where('contacts.blocked', '=', 1)
    ->delete();
```

| Driver | Generated SQL |
|--------|---------------|
| MySQL | `UPDATE t INNER JOIN ... SET ... WHERE ...` / `DELETE t FROM t INNER JOIN ... WHERE ...` |
| SQL Server | `UPDATE t SET ... FROM t INNER JOIN ... WHERE ...` / `DELETE t FROM t INNER JOIN ... WHERE ...` |
| PostgreSQL | `UPDATE t SET ... WHERE ctid IN (SELECT t.ctid FROM t INNER JOIN ... WHERE ...)`, same for `DELETE` |
| SQLite | Like PostgreSQL, with `rowid` instead of `ctid` |

> Only the base table is updated or deleted from. On PostgreSQL and SQLite the table prefix of a
> `SET` column (`users.active`) is dropped, as they don't allow it there.

<a id="pagination"></a>
## Pagination

```php
$users = DB::table('users')->where('active', '=', 1)->paginate(15);

foreach ($users as $user) {
    echo $user->name;
}

echo $users->links();
```

`paginate()` defaults to 20 per page. See [Pagination](/docs/views/pagination) for the full API.

<a id="conditional-clauses"></a>
<a id="other-methods"></a>
## Conditional Clauses

`when()` applies a callback only when the value is truthy (`unless()` when it is falsy). The
callback receives the query and the value; an optional third callback runs otherwise:

```php
$users = DB::table('users')
    ->when(Input::get('role'), function ($query, $role) {
        return $query->where('role', '=', $role);
    })
    ->get();
```

`tap($callback)` hands the query to a callback and keeps the chain going.

<a id="copy--reset-query"></a>
<a id="copy-query"></a>
<a id="reset-query"></a>
## Copy & Reset Query

`copy()` clones a query so a shared base can be reused without being modified:

```php
$base = DB::table('users')->where('active', '=', 1);

$admins = $base->copy()->where('role', '=', 'admin')->get();
$users  = $base->copy()->where('role', '=', 'user')->get();
```

`reset()` clears every clause (wheres, selects, orderings, groupings, havings, unions, limit,
offset, locks). `reset_where()` clears only the conditions and `reset_limit_offset()` only the
limit and offset.

<a id="debug-query"></a>
## Debug Query

```php
$query = DB::table('users')->where('active', '=', 1)->where('votes', '>', 100);

echo $query->to_sql();   // SELECT * FROM "users" WHERE "active" = ? AND "votes" > ?
echo $query->debug();    // SELECT * FROM "users" WHERE "active" = 1 AND "votes" > 100

$query->dd();            // dump debug() and stop
$query->bd();            // dump debug() to the debug bar and continue
```

<a id="transaction"></a>
## Transaction

`transaction()` runs a closure inside a transaction. It commits when the closure returns, and
rolls back and rethrows when it throws. The closure receives the connection, and its return value
is returned:

```php
$user = DB::connection()->transaction(function ($connection) {
    $connection->table('users')->where('id', '=', 1)->update(['balance' => DB::raw('balance - 100')]);
    $connection->table('logs')->insert(['note' => 'debited']);

    return $connection->table('users')->find(1);
});
```

For manual control use `begin_transaction()`, `commit()` and `rollback()`:

```php
$connection = DB::connection();

try {
    $connection->begin_transaction();
    DB::table('users')->insert(['name' => 'John']);
    $connection->commit();
} catch (Exception $e) {
    $connection->rollback();
    throw $e;
}
```

`commit()` and `rollback()` just return `false` when no transaction is open, so `rollback()` is
safe in any error handler.

> A `return` inside the `try` block skips `commit()` and leaves the transaction open for the rest
> of the request. Commit first, or use the closure form.

**Nested transactions.** Opening a transaction while one is open creates a savepoint, so code that
wraps its own work in a transaction can safely run inside another. If the inner one fails, only
its work is undone; only the outermost `commit()` really commits. `transaction_level()` returns
the current depth.

```php
DB::connection()->transaction(function ($connection) {
    $connection->table('orders')->insert(['total' => 1000]);

    try {
        Billing::charge($order);   // its own transaction, a savepoint here
    } catch (Exception $e) {
        // only the inner work was undone
    }
});
```

**Transaction events.** `rakit.db.commit` and `rakit.db.rollback` fire when the outermost
transaction of a connection ends (not for savepoints), with the connection name as payload:

```php
Hook::listen('rakit.db.commit', function ($connection) {
    // e.g. dispatch jobs only once the data is really saved
});
```

<a id="row-locking"></a>
## Row Locking

A transaction alone does not stop two requests from reading the same row and both acting on it,
for example both passing a "balance is enough" check. `lock_for_update()` holds the selected rows
until the transaction ends:

```php
DB::connection()->transaction(function () use ($id, $price) {
    $user = User::where_key($id)->lock_for_update()->first();

    if ($user->balance < $price) {
        throw new Exception('balance is not enough');
    }

    User::where_key($id)->decrement('balance', $price);
});
```

`shared_lock()` is softer: others may still read the rows, but none may change them until your
transaction ends:

```php
$rates = DB::table('rates')->where('active', 1)->shared_lock()->get();
```

Locks only have effect inside a transaction.

By default a lock waits for rows another transaction holds. `no_wait()` fails immediately
instead, and `skip_locked()` leaves those rows out, which lets several workers take jobs from one
table without colliding:

```php
DB::connection()->transaction(function () {
    $job = DB::table('jobs')->where('reserved', 0)->lock_for_update()->skip_locked()->first();
    // ..
});
```

Both lock exclusively when no lock was requested yet, and need MySQL 8.0+, PostgreSQL 9.5+
(`NOWAIT` alone works on older PostgreSQL) or SQL Server. `lock()` takes a raw clause for
driver-specific locks:

```php
DB::table('jobs')->lock('FOR NO KEY UPDATE')->get();
```

What each driver compiles:

| Driver | `lock_for_update()` | `shared_lock()` |
|---|---|---|
| MySQL | `FOR UPDATE` | `LOCK IN SHARE MODE` |
| PostgreSQL | `FOR UPDATE` | `FOR SHARE` |
| SQL Server | `WITH (ROWLOCK, UPDLOCK, HOLDLOCK)` | `WITH (ROWLOCK, HOLDLOCK)` |
| SQLite | *(nothing)* | *(nothing)* |

| Driver | `no_wait()` | `skip_locked()` |
|---|---|---|
| MySQL, PostgreSQL | `FOR UPDATE NOWAIT` | `FOR UPDATE SKIP LOCKED` |
| SQL Server | `WITH (ROWLOCK, UPDLOCK, HOLDLOCK, NOWAIT)` | `WITH (ROWLOCK, UPDLOCK, READPAST)` |
| SQLite | *(nothing)* | *(nothing)* |

Combined with `shared_lock()` they compile to `FOR SHARE ...` (MySQL included), or on SQL Server
`WITH (ROWLOCK, HOLDLOCK, NOWAIT)` / `WITH (ROWLOCK, REPEATABLEREAD, READPAST)`. SQLite locks the
whole database file during a transaction, so it has no row lock and the clause is omitted.
