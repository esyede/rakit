# Magic Query Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#basic-knowledge)
- [Retrieving Records](#retrieving-records)
- [Select Columns](#select-columns)
- [Building Where Clauses](#building-where-clauses)
  - [where and or_where](#where-and-or_where)
  - [where_id and or_where_id](#where_id-and-or_where_id)
  - [where_in, where_not_in, or_where_in, and or_where_not_in](#where_in-where_not_in-or_where_in-and-or_where_not_in)
  - [where_null, where_not_null, or_where_null, and or_where_not_null](#where_null-where_not_null-or_where_null-and-or_where_not_null)
  - [where_between, where_not_between, or_where_between, and or_where_not_between](#where_between-where_not_between-or_where_between-and-or_where_not_between)
  - [where_date, where_month, where_day, where_year](#where_date-where_month-where_day-where_year)
  - [where_exists and where_not_exists](#where_exists-and-where_not_exists)
  - [where_in_sub and where_not_in_sub](#where_in_sub-and-where_not_in_sub)
- [Nested Where](#nested-where)
- [Dynamic Where](#dynamic-where)
- [Raw Where](#raw-where)
- [Table Join](#table-join)
- [Left Join](#left-join)
- [Order By](#order-by)
- [Group By & Having](#group-by--having)
- [Skip & Take](#skip--take)
- [For Page](#for-page)
- [Distinct](#distinct)
- [Union & Union All](#union--union-all)
- [Aggregates](#aggregates)
- [Raw SQL Expressions](#raw-sql-expressions)
- [Only](#only)
- [Lists](#lists)
- [Cursor](#cursor)
- [Insert Record](#insert-record)
- [Update Record](#update-record)
- [Increment & Decrement](#increment--decrement)
- [Delete Record](#delete-record)
- [Pagination](#pagination)
- [Find Or Fail](#find-or-fail)
- [Copy Query](#copy-query)
- [Reset Query](#reset-query)
- [Debug Query](#debug-query)
- [Transaction](#transaction)
- [Row Locking](#row-locking)
- [Other Methods](#other-methods)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>
## Basic Knowledge

The Magic Query Builder is a class that helps you build SQL queries and work with the database. Every query is prepared with a [prepared statement](https://www.php.net/manual/en/pdo.prepared-statements.php), so it is automatically protected from [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection).

To get started, call the `DB::table()` method with the name of the table you want to work with:

```php
$query = DB::table('users');
```

You now have access to the Query Builder for the "users" table and can run operations such as select, insert, update, or delete.

<a id="retrieving-records"></a>
## Retrieving Records

**Retrieve the records from the database:**

```php
$users = DB::table('users')->get();
```

The `get()` method returns a `Collection` of objects whose properties match the table column
names. Because it is a collection, you can keep chaining on the result:

```php
$names = DB::table('users')->get()->pluck('name');
$adults = DB::table('users')->get()->filter(function ($user) {
    return $user->age >= 17;
});
```

It is countable, iterable and array accessible, so `count($users)`, `foreach ($users as $user)`
and `$users[0]` all keep working as they did before.

**Retrieve a single record:**

```php
$user = DB::table('users')->first();
```

**Retrieve a record by its primary key:**

```php
$user = DB::table('users')->find($id);
```

**Retrieve a record by ID, throwing an exception when it is not found:**

```php
$user = DB::table('users')->find_or_fail($id);
// Throw ModelNotFoundException if not found
```

> **Note:** `first()` and `find()` return `NULL` when there is no result, while `get()` returns an empty collection.

<a id="select-columns"></a>
## Select Columns

**Select specific columns:**

```php
$users = DB::table('users')->get(['id', 'email', 'name']);

// Or with alias
$users = DB::table('users')->get(['id', 'email as user_email']);
```

**Using the select() method:**

```php
$users = DB::table('users')
    ->select(['id', 'name', 'email'])
    ->get();

// Atau multiple arguments
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();
```

<a id="building-where-clauses"></a>
## Building Where Clauses

<a id="where-and-or_where"></a>
### where and or_where

**Basic WHERE clause:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->first();

$users = DB::table('users')
    ->where('votes', '>', 100)
    ->get();
```

**Multiple WHERE with AND:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->where('email', '=', 'example@mail.com')
    ->first();
```

**WHERE with OR:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->or_where('email', '=', 'admin@mail.com')
    ->get();
```

**Supported operators:**

```php
'=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
'like', 'like binary', 'not like', 'ilike',
'&', '|', '^', '<<', '>>',
'rlike', 'not rlike', 'regexp', 'not regexp',
'~', '~*', '!~', '!~*',
'similar to', 'not similar to', 'not ilike', '~~*', '!~~*'
```

**Example with LIKE:**

```php
$users = DB::table('users')
    ->where('name', 'like', '%john%')
    ->get();
```

<a id="where_id-and-or_where_id"></a>
### where_id and or_where_id

Shortcut for a WHERE on the `id` column:

```php
// Equivalent to: where('id', '=', 1)
$user = DB::table('users')->where_id(1)->first();

// OR WHERE id
$users = DB::table('users')
    ->where('email', 'like', '%@gmail.com')
    ->or_where_id(5)
    ->get();
```

<a id="where_in-where_not_in-or_where_in-and-or_where_not_in"></a>
### where_in, where_not_in, or_where_in, and or_where_not_in

**WHERE IN:**

```php
$users = DB::table('users')
    ->where_in('id', [1, 2, 3])
    ->get();
```

**WHERE NOT IN:**

```php
$users = DB::table('users')
    ->where_not_in('id', [1, 2, 3])
    ->get();
```

**OR WHERE IN:**

```php
$users = DB::table('users')
    ->where('votes', '>', 100)
    ->or_where_in('name', ['John', 'Jane'])
    ->get();
```

**OR WHERE NOT IN:**

```php
$users = DB::table('users')
    ->where('active', '=', 1)
    ->or_where_not_in('role', ['admin', 'moderator'])
    ->get();
```

<a id="where_null-where_not_null-or_where_null-and-or_where_not_null"></a>
### where_null, where_not_null, or_where_null, and or_where_not_null

**WHERE NULL:**

```php
$users = DB::table('users')
    ->where_null('deleted_at')
    ->get();
```

**WHERE NOT NULL:**

```php
$users = DB::table('users')
    ->where_not_null('email_verified_at')
    ->get();
```

**OR WHERE NULL:**

```php
$users = DB::table('users')
    ->where('active', '=', 1)
    ->or_where_null('deleted_at')
    ->get();
```

**OR WHERE NOT NULL:**

```php
$users = DB::table('users')
    ->where('role', '=', 'admin')
    ->or_where_not_null('premium_until')
    ->get();
```

<a id="where_between-where_not_between-or_where_between-and-or_where_not_between"></a>
### where_between, where_not_between, or_where_between, and or_where_not_between

**WHERE BETWEEN:**

```php
$users = DB::table('users')
    ->where_between('votes', 1, 100)
    ->get();

$orders = DB::table('orders')
    ->where_between('created_at', '2024-01-01', '2024-12-31')
    ->get();
```

**WHERE NOT BETWEEN:**

```php
$users = DB::table('users')
    ->where_not_between('age', 18, 30)
    ->get();
```

**OR WHERE BETWEEN:**

```php
$users = DB::table('users')
    ->where('country', '=', 'US')
    ->or_where_between('age', 20, 30)
    ->get();
```

**OR WHERE NOT BETWEEN:**

```php
$products = DB::table('products')
    ->where('category', '=', 'electronics')
    ->or_where_not_between('price', 100, 500)
    ->get();
```

<a id="where_date-where_month-where_day-where_year"></a>
### where_date, where_month, where_day, where_year

**WHERE DATE:**

```php
// Search by date
$orders = DB::table('orders')
    ->where_date('created_at', '=', '2024-01-15')
    ->get();
```

**WHERE MONTH:**

```php
// Search by month (1-12)
$orders = DB::table('orders')
    ->where_month('created_at', '=', 1)
    ->get();
```

**WHERE DAY:**

```php
// Search by day (1-31)
$orders = DB::table('orders')
    ->where_day('created_at', '=', 15)
    ->get();
```

**WHERE YEAR:**

```php
// Search by year
$orders = DB::table('orders')
    ->where_year('created_at', '=', 2024)
    ->get();
```

> **Security (fixed):** `where_date|month|day|year|time` now wrap the column via the grammar (`DATE("col")`) and validate the identifier. Passing `Input::get('col')` directly without allowlisting is now rejected (`Invalid column identifier`) instead of interpolated as `DATE(created_at)=1 OR ...`.

<a id="where_exists-and-where_not_exists"></a>
### where_exists and where_not_exists

**WHERE EXISTS with a subquery:**

```php
$users = DB::table('users')
    ->where_exists(function ($query) {
        $query->from('orders')
              ->where_raw('orders.user_id = users.id');
    })
    ->get();
```

**WHERE NOT EXISTS:**

```php
$users = DB::table('users')
    ->where_not_exists(function ($query) {
        $query->from('orders')
              ->where_raw('orders.user_id = users.id');
    })
    ->get();
```

<a id="where_in_sub-and-where_not_in_sub"></a>
### where_in_sub and where_not_in_sub

**WHERE IN with a subquery:**

```php
$users = DB::table('users')
    ->where_in_sub('id', DB::table('orders')
        ->select('user_id')
        ->where('status', '=', 'completed')
    )
    ->get();
```

**WHERE NOT IN with a subquery:**

```php
$users = DB::table('users')
    ->where_not_in_sub('id', DB::table('banned_users')
        ->select('user_id')
    )
    ->get();
```

<a id="nested-where"></a>
## Nested Where

Group WHERE clauses with parentheses:

```php
$users = DB::table('users')
    ->where('name', '=', 'John')
    ->where_nested(function ($query) {
        $query->where('votes', '>', 100)
              ->or_where('title', '=', 'Admin');
    })
    ->get();

// SQL: SELECT * FROM users WHERE name = 'John' AND (votes > 100 OR title = 'Admin')
```

A more complex example:

```php
$users = DB::table('users')
    ->where('country', '=', 'US')
    ->where_nested(function ($query) {
        $query->where('age', '>=', 18)
              ->where('age', '<=', 65);
    })
    ->or_where_nested(function ($query) {
        $query->where('role', '=', 'admin')
              ->where('verified', '=', 1);
    })
    ->get();
```

<a id="dynamic-where"></a>
## Dynamic Where

The Query Builder supports dynamic WHERE methods based on the column name:

```php
// Equivalent to: where('email', '=', $email)
$user = DB::table('users')->where_email($email)->first();

// Equivalent to: where('name', '=', $name)
$users = DB::table('users')->where_name($name)->get();

// Multiple conditions with _and_
$user = DB::table('users')
    ->where_email_and_password($email, $password)
    ->first();
// SQL: WHERE email = ? AND password = ?

// Multiple conditions with _or_
$users = DB::table('users')
    ->where_email_or_username($email, $username)
    ->get();
// SQL: WHERE email = ? OR username = ?
```

<a id="raw-where"></a>
## Raw Where

For a complex WHERE, use a raw WHERE:

```php
$users = DB::table('users')
    ->raw_where('age > ? AND city = ?', [18, 'Jakarta'])
    ->get();

// With OR
$users = DB::table('users')
    ->where('active', '=', 1)
    ->raw_or_where('(votes > 100 OR role = ?)', ['admin'])
    ->get();
```

<a id="table-join"></a>
## Table Join

**Basic JOIN:**

```php
$users = DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->get(['users.*', 'contacts.phone']);
```

**JOIN with a closure:**

```php
$users = DB::table('users')
    ->join('contacts', function ($join) {
        $join->on('users.id', '=', 'contacts.user_id')
             ->or_on('users.email', '=', 'contacts.email');
    })
    ->get();
```

**Multiple JOIN:**

```php
$orders = DB::table('orders')
    ->join('users', 'orders.user_id', '=', 'users.id')
    ->join('products', 'orders.product_id', '=', 'products.id')
    ->get(['orders.*', 'users.name', 'products.title']);
```

<a id="left-join"></a>
## Left Join

```php
$users = DB::table('users')
    ->left_join('orders', 'users.id', '=', 'orders.user_id')
    ->get();

// With a closure
$users = DB::table('users')
    ->left_join('orders', function ($join) {
        $join->on('users.id', '=', 'orders.user_id')
             ->where('orders.status', '=', 'completed');
    })
    ->get();
```

<a id="order-by"></a>
## Order By

```php
// Ascending (default)
$users = DB::table('users')
    ->order_by('name')
    ->get();

// Descending
$users = DB::table('users')
    ->order_by('created_at', 'desc')
    ->get();

// Multiple order by
$users = DB::table('users')
    ->order_by('country', 'asc')
    ->order_by('name', 'asc')
    ->get();
```

> The direction is only ever `asc` or `desc`; anything else throws. The same
> goes for the operator of `where()` and `having()`, which has to be one the
> grammar knows. Both end up in the SQL as written, so neither is a place to
> pass something that came from a request. Use `order_by_raw()` when you really
> do mean raw SQL.

<a id="group-by--having"></a>
## Group By & Having

**GROUP BY:**

```php
$totals = DB::table('orders')
    ->select(['user_id', DB::raw('SUM(amount) as total')])
    ->group_by('user_id')
    ->get();
```

**GROUP BY with HAVING:**

```php
$users = DB::table('orders')
    ->select(['user_id', DB::raw('COUNT(*) as order_count')])
    ->group_by('user_id')
    ->having('order_count', '>', 5)
    ->get();
```

<a id="skip--take"></a>
## Skip & Take

**LIMIT and OFFSET:**

```php
// Take 10 records
$users = DB::table('users')
    ->take(10)
    ->get();

// Skip 20, take 10
$users = DB::table('users')
    ->skip(20)
    ->take(10)
    ->get();
```

<a id="for-page"></a>
## For Page

Shortcut for manual pagination:

```php
// Page 1, 15 per page
$users = DB::table('users')
    ->for_page(1, 15)
    ->get();

// Page 3, 20 per page  
$users = DB::table('users')
    ->for_page(3, 20)
    ->get();
// Equivalent to: skip(40)->take(20)
```

<a id="distinct"></a>
## Distinct

```php
$countries = DB::table('users')
    ->distinct()
    ->get(['country']);
```

<a id="union--union-all"></a>
## Union & Union All

**UNION:**

```php
$query1 = DB::table('users')->where('active', '=', 1);
$query2 = DB::table('users')->where('role', '=', 'admin');

$users = $query1->union($query2)->get();
```

**UNION ALL:**

```php
$query1 = DB::table('orders')->where('status', '=', 'pending');
$query2 = DB::table('orders')->where('status', '=', 'processing');

$orders = $query1->union_all($query2)->get();
```

<a id="aggregates"></a>
## Aggregates

**COUNT:**

```php
$count = DB::table('users')->count();

$active_count = DB::table('users')
    ->where('active', '=', 1)
    ->count();
```

**MAX:**

```php
$max_votes = DB::table('users')->max('votes');
```

**MIN:**

```php
$min_age = DB::table('users')->min('age');
```

**AVG:**

```php
$avg_price = DB::table('products')->avg('price');
```

**SUM:**

```php
$total_sales = DB::table('orders')->sum('amount');
```

<a id="raw-sql-expressions"></a>
## Raw SQL Expressions

For a complex SQL query, use `DB::raw()`:

```php
$users = DB::table('users')
    ->select([
        '*',
        DB::raw('COUNT(*) as total'),
        DB::raw('DATE(created_at) as date')
    ])
    ->get();

// In WHERE
$users = DB::table('users')
    ->where(DB::raw('YEAR(created_at)'), '=', 2024)
    ->get();

// In ORDER BY
$users = DB::table('users')
    ->order_by(DB::raw('votes * 2'), 'desc')
    ->get();
```

**Manual escaping:**

```php
$value = DB::escape($user_input);
```

<a id="only"></a>
## Only

Retrieve the value of a single column:

```php
$email = DB::table('users')
    ->where('id', '=', 1)
    ->only('email');

// Return: "user@example.com" (not an object or array)
```

<a id="lists"></a>
## Lists

Retrieve a key-value array:

```php
// Array with values only
$emails = DB::table('users')->lists('email');
// ['email1@test.com', 'email2@test.com', ...]

// Array with custom key
$users = DB::table('users')->lists('name', 'id');
// [1 => 'John', 2 => 'Jane', ...]
```

<a id="cursor"></a>
## Cursor

For a large dataset, use a cursor to keep memory usage low:

```php
foreach (DB::table('users')->cursor() as $user) {
    echo $user->name;
}

// With a custom chunk size
foreach (DB::table('orders')->cursor(['*'], 500) as $order) {
    // Process order
}
```

The cursor uses a PHP generator and streams the data row by row.

<a id="insert-record"></a>
## Insert Record

**Insert single record:**

```php
$id = DB::table('users')->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'password' => Hash::make('secret')
]);
```

**Insert and get the ID:**

```php
$id = DB::table('users')->insert_get_id([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

echo $id; // Auto-increment ID
```

**Insert with a custom ID column:**

```php
$id = DB::table('users')->insert_get_id([
    'name' => 'Bob'
], 'user_id');
```

**Batch insert:**

```php
DB::table('users')->insert([
    ['name' => 'User 1', 'email' => 'user1@test.com'],
    ['name' => 'User 2', 'email' => 'user2@test.com'],
    ['name' => 'User 3', 'email' => 'user3@test.com'],
]);
```

<a id="update-record"></a>
## Update Record

```php
// Update with WHERE
DB::table('users')
    ->where('id', '=', 1)
    ->update([
        'name' => 'New Name',
        'email' => 'newemail@example.com'
    ]);

// Update multiple conditions
DB::table('users')
    ->where('active', '=', 0)
    ->where('created_at', '<', '2020-01-01')
    ->update(['status' => 'inactive']);
```

<a id="increment--decrement"></a>
## Increment & Decrement

**Increment:**

```php
// Increment votes by 1
DB::table('users')
    ->where('id', '=', 1)
    ->increment('votes');

// Increment by custom amount
DB::table('users')
    ->where('id', '=', 1)
    ->increment('votes', 5);
```

**Decrement:**

```php
// Decrement by 1
DB::table('products')
    ->where('id', '=', 1)
    ->decrement('stock');

// Decrement by custom amount
DB::table('products')
    ->where('id', '=', 1)
    ->decrement('stock', 10);
```

<a id="delete-record"></a>
## Delete Record

```php
// Delete with WHERE
DB::table('users')
    ->where('id', '=', 1)
    ->delete();

// Delete multiple
DB::table('users')
    ->where('active', '=', 0)
    ->delete();

// Delete all (be careful!)
DB::table('temp_data')->delete();
```

<a id="pagination"></a>
## Pagination

```php
// 20 per page (default)
$users = DB::table('users')->paginate();

// Custom per page
$users = DB::table('users')->paginate(15);

// With specific columns
$users = DB::table('users')->paginate(10, ['id', 'name', 'email']);

// With WHERE
$users = DB::table('users')
    ->where('active', '=', 1)
    ->order_by('created_at', 'desc')
    ->paginate(20);

// In view
foreach ($users->results as $user) {
    echo $user->name;
}

echo $users->links();
```

<a id="find-or-fail"></a>
## Find Or Fail

Methods that throw an exception when the record is not found:

```php
try {
    $user = DB::table('users')->find_or_fail($id);
} catch (ModelNotFoundException $e) {
    return Response::error('404');
}

// Or with first_or_fail
try {
    $user = DB::table('users')
        ->where('email', '=', $email)
        ->first_or_fail();
} catch (ModelNotFoundException $e) {
    return Redirect::back()->with('error', 'User not found');
}
```

<a id="copy-query"></a>
## Copy Query

Make a copy of a query so it can be reused:

```php
$base_query = DB::table('users')
    ->where('active', '=', 1)
    ->where('country', '=', 'US');

// Copy for different query
$admins = $base_query->copy()
    ->where('role', '=', 'admin')
    ->get();

$users = $base_query->copy()
    ->where('role', '=', 'user')
    ->get();

// $base_query is not affected
```

<a id="reset-query"></a>
## Reset Query

**Reset the whole query:**

```php
$query = DB::table('users')
    ->where('active', '=', 1)
    ->take(10)
    ->skip(5);

$query->reset(); // Reset all conditions

$query->get(); // Get all rows
```

**Reset only the WHERE clauses:**

```php
$query = DB::table('users')
    ->where('role', '=', 'admin')
    ->where('country', '=', 'US');

$query->reset_where(); // Remove every WHERE
$query->where('active', '=', 1)->get();
```

**Reset LIMIT and OFFSET:**

```php
$query = DB::table('users')
    ->skip(10)
    ->take(20);

$query->reset_limit_offset();
$query->get(); // Get everything, without the limit
```

<a id="debug-query"></a>
## Debug Query

To inspect the generated SQL query:

```php
$query = DB::table('users')
    ->where('active', '=', 1)
    ->where('votes', '>', 100);

// Without bindings
echo $query->to_sql();
// SELECT * FROM users WHERE active = ? AND votes > ?

// With bindings
echo $query->debug();
// SELECT * FROM users WHERE active = 1 AND votes > 100
```

These methods are very handy while debugging.

<a id="transaction"></a>
## Transaction

Use a transaction to keep the data consistent. The closure receives the
connection, and whatever it returns becomes the return value of `transaction()`:

```php
$user = DB::connection()->transaction(function ($connection) {
    $connection->table('users')->update(['balance' => DB::raw('balance - 100')]);
    $connection->table('logs')->insert(['note' => 'debited']);

    return $connection->table('users')->find(1);
});
```

Anything the closure throws rolls the whole thing back and is rethrown, so there
is nothing to catch unless you want to handle it.

**Manual control:**

```php
$connection = DB::connection();

try {
    $connection->begin_transaction();

    DB::table('users')->insert(['name' => 'John']);
    DB::table('profiles')->insert(['user_id' => 1]);

    $connection->commit();
} catch (Exception $e) {
    $connection->rollback();
    throw $e;
}
```

`rollback()` and `commit()` are harmless when no transaction is open, they simply
return `FALSE`. That makes `rollback()` safe to call from an error handler that
is not sure whether a transaction was ever started.

> **Beware:** a `return` inside the `try` block skips the `commit()` and leaves
> the transaction open for the rest of the request. Either commit before you
> return, or use the closure form, which cannot get this wrong.

**Nested transactions:**

Opening a transaction while one is already open uses a savepoint instead of a
second real transaction, so a method that wraps its own work may safely be
called from inside another transaction:

```php
DB::connection()->transaction(function ($connection) {
    $connection->table('orders')->insert(['total' => 1000]);

    try {
        // Its own transaction, which becomes a savepoint here
        Billing::charge($order);
    } catch (Exception $e) {
        // Only the work of the inner one is undone, the outer one carries on
    }

    $connection->table('logs')->insert(['note' => 'order placed']);
});
```

Only the outermost `commit()` really commits. Use `transaction_level()` to ask
how deep you are.

<a id="row-locking"></a>
## Row Locking

A transaction on its own does not stop two requests from reading the same row at
the same time. This is the pattern that breaks:

```php
// UNSAFE: both requests may read balance = 100 and both pass the check
$user = User::find($id);

if ($user->balance < $price) {
    return 'balance is not enough';
}

User::where_key($id)->decrement('balance', $price);
```

`decrement()` is atomic, but the check before it is not, so two purchases of 100
against a balance of 100 can both go through. `lock_for_update()` closes that
window by holding the row until the transaction ends:

```php
DB::connection()->transaction(function () use ($id, $price) {
    $user = User::where_key($id)->lock_for_update()->first();

    if ($user->balance < $price) {
        throw new Exception('balance is not enough');
    }

    User::where_key($id)->decrement('balance', $price);
});
```

`shared_lock()` is the softer one, other transactions may still read the rows
but none may change them until yours ends:

```php
$rates = DB::table('rates')->where('active', 1)->shared_lock()->get();
```

Both only hold inside a transaction, outside of one they do nothing useful.
`lock()` takes a raw clause when a driver specific one is needed:

```php
DB::table('jobs')->lock('FOR UPDATE SKIP LOCKED')->get();
```

What each driver compiles:

| Driver | `lock_for_update()` | `shared_lock()` |
|---|---|---|
| MySQL | `FOR UPDATE` | `LOCK IN SHARE MODE` |
| PostgreSQL | `FOR UPDATE` | `FOR SHARE` |
| SQL Server | `WITH (ROWLOCK, UPDLOCK, HOLDLOCK)` | `WITH (ROWLOCK, HOLDLOCK)` |
| SQLite | *(nothing)* | *(nothing)* |

SQLite locks the whole database file for the duration of a transaction, so it
has no row level lock to ask for and the clause is left out entirely.

<a id="other-methods"></a>
## Other Methods

| Method                                            | Description                                                     |
| ------------------------------------------------- | --------------------------------------------------------------- |
| `latest($column)`                                 | Order descending, defaults to the `created_at` column           |
| `oldest($column)`                                 | Order ascending, defaults to the `created_at` column            |
| `exists()`                                        | `true` when at least one row matches                            |
| `doesnt_exist()`                                  | The opposite of `exists()`                                      |
| `where_column($column1, $operator, $column2)`     | Compare two columns to each other                               |
| `where_time($column, $operator, $value)`          | WHERE on the time part of a column                              |
| `aggregate($aggregator, array $columns)`          | Run any aggregate function, for example `MAX`                   |
| `chunk($count, $callback)`                        | Process rows in chunks, returning `false` stops the iteration   |
| `chunk_by_id($count, $callback, $column, $alias)` | Process rows in chunks, paging by id instead of by offset       |
| `each($callback, $count)`                         | Run the callback over every single row                          |
| `value($column)`                                  | A single column value of the first row                          |
| `pluck($column, $key)`                            | A collection holding the values of a single column              |
| `sole($columns)`                                  | Exactly one row, complains when there is none or more than one  |
| `when($value, $callback, $default)`               | Apply the callback only when the value is truthy                |
| `unless($value, $callback, $default)`             | Apply the callback only when the value is falsy                 |
| `tap($callback)`                                  | Hand the query to the callback and keep on chaining             |
| `where_like($column, $value)`                     | WHERE LIKE, also `or_where_like` and `where_not_like`           |
| `select_raw($sql, $bindings)` / `add_select()`    | Raw or extra columns in the SELECT clause                       |
| `order_by_raw()` / `group_by_raw()`               | Raw ORDER BY and GROUP BY clauses                               |
| `having_raw($sql, $bindings)` / `or_having()`     | Raw and OR variants of the HAVING clause                        |
| `in_random_order($seed)`                          | Order the rows randomly                                         |
| `re_order($column, $direction)`                   | Drop every ORDER BY added so far                                |
| `right_join()` / `cross_join()`                   | RIGHT and CROSS joins                                           |
| `update_or_insert($attributes, $values)`          | Update the matching row, or insert it when there is none        |
| `insert_or_ignore($values)`                       | Insert while silently skipping the rows that clash              |
| `dd()` / `bd()`                                   | Dump the SQL and its bindings, then stop or continue            |

```php
// The newest 10 articles
$articles = DB::table('articles')->latest()->take(10)->get();

// Rows where the two columns are not the same
$mismatch = DB::table('orders')->where_column('paid_total', '!=', 'total')->get();

// Check without pulling any row
if (DB::table('users')->where('email', 'budi@site.com')->doesnt_exist()) {
    // ..
}

// Safe for a large table, because it does not use OFFSET
DB::table('users')->chunk_by_id(500, function ($users) {
    foreach ($users as $user) {
        // ..
    }
});

// Only apply the filter when it was actually asked for
$users = DB::table('users')
    ->when($request_role, function ($query, $role) {
        return $query->where('role', '=', $role);
    })
    ->get();

// A single value instead of a whole row
$email = DB::table('users')->where('id', '=', 1)->value('email');

// Update when it exists, insert when it does not
DB::table('settings')->update_or_insert(['key' => 'theme'], ['value' => 'dark']);
```

> **Note:** methods that Laravel provides but Rakit does not support yet, such as
> `where_has()` or `where_json_contains()`, now raise a clear exception instead of being
> silently compiled into a column of that name.
