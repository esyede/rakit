# Magic Query Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3,4" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Mengambil Record](#mengambil-record)
- [Select Columns](#select-columns)
- [Membangun Klausa Where](#membangun-klausa-where)
  - [where dan or_where](#where-dan-or_where)
  - [where_id dan or_where_id](#where_id-dan-or_where_id)
  - [where_in, where_not_in, or_where_in, dan or_where_not_in](#where_in-where_not_in-or_where_in-dan-or_where_not_in)
  - [where_null, where_not_null, or_where_null, dan or_where_not_null](#where_null-where_not_null-or_where_null-dan-or_where_not_null)
  - [where_between, where_not_between, or_where_between, dan or_where_not_between](#where_between-where_not_between-or_where_between-dan-or_where_not_between)
  - [where_date, where_month, where_day, where_year](#where_date-where_month-where_day-where_year)
  - [where_exists dan where_not_exists](#where_exists-dan-where_not_exists)
  - [where_in_sub dan where_not_in_sub](#where_in_sub-dan-where_not_in_sub)
- [Nested Where](#nested-where)
- [Where Dinamis](#where-dinamis)
- [Raw Where](#raw-where)
- [Join Tabel](#join-tabel)
- [Left Join](#left-join)
- [Order By](#order-by)
- [Group By & Having](#group-by--having)
- [Skip & Take](#skip--take)
- [For Page](#for-page)
- [Distinct](#distinct)
- [Union & Union All](#union--union-all)
- [Agregasi](#agregasi)
- [Ekspresi SQL Mentah](#ekspresi-sql-mentah)
- [Only](#only)
- [Lists](#lists)
- [Cursor](#cursor)
- [Insert Record](#insert-record)
- [Update Record](#update-record)
- [Increment & Decrement](#increment--decrement)
- [Delete Record](#delete-record)
- [Paginasi](#paginasi)
- [Find Or Fail](#find-or-fail)
- [Copy Query](#copy-query)
- [Reset Query](#reset-query)
- [Debug Query](#debug-query)
- [Transaction](#transaction)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Magic Query Builder adalah class yang disediakan untuk memudahkan anda membangun query SQL dan bekerja dengan database. Semua query disiapkan menggunakan [prepared statement](https://www.php.net/manual/en/pdo.prepared-statements.php) sehingga otomatis terlindung dari serangan [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection).

Untuk memulai, panggil method `DB::table()` dengan nama tabel yang ingin dioperasikan:

```php
$query = DB::table('users');
```

Sekarang anda memiliki akses ke Query Builder untuk tabel "users" dan dapat melakukan operasi seperti select, insert, update, atau delete.

<a id="mengambil-record"></a>
## Mengambil Record

**Mengambil array record dari database:**

```php
$users = DB::table('users')->get();
```

Method `get()` mengembalikan array berisi object dengan property yang sesuai dengan nama kolom tabel.

**Mengambil record tunggal:**

```php
$user = DB::table('users')->first();
```

**Mengambil record berdasarkan primary key:**

```php
$user = DB::table('users')->find($id);
```

**Mengambil record berdasarkan ID dengan exception jika tidak ditemukan:**

```php
$user = DB::table('users')->find_or_fail($id);
// Throw ModelNotFoundException jika tidak ditemukan
```

> **Catatan:** Method `first()` dan `find()` mengembalikan `NULL` jika tidak ada hasil. Method `get()` mengembalikan array kosong.

<a id="select-columns"></a>
## Select Columns

**Memilih kolom tertentu:**

```php
$users = DB::table('users')->get(['id', 'email', 'name']);

// Atau dengan alias
$users = DB::table('users')->get(['id', 'email as user_email']);
```

**Menggunakan method select():**

```php
$users = DB::table('users')
    ->select(['id', 'name', 'email'])
    ->get();

// Atau multiple arguments
$users = DB::table('users')
    ->select('id', 'name', 'email')
    ->get();
```

<a id="membangun-klausa-where"></a>
## Membangun Klausa Where

<a id="where-dan-or_where"></a>
### where dan or_where

**Basic WHERE clause:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->first();

$users = DB::table('users')
    ->where('votes', '>', 100)
    ->get();
```

**Multiple WHERE dengan AND:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->where('email', '=', 'example@mail.com')
    ->first();
```

**WHERE dengan OR:**

```php
$users = DB::table('users')
    ->where('id', '=', 1)
    ->or_where('email', '=', 'admin@mail.com')
    ->get();
```

**Operator yang didukung:**

```php
'=', '<', '>', '<=', '>=', '<>', '!=', '<=>',
'like', 'like binary', 'not like', 'ilike',
'&', '|', '^', '<<', '>>',
'rlike', 'not rlike', 'regexp', 'not regexp',
'~', '~*', '!~', '!~*',
'similar to', 'not similar to', 'not ilike', '~~*', '!~~*'
```

**Contoh dengan LIKE:**

```php
$users = DB::table('users')
    ->where('name', 'like', '%john%')
    ->get();
```

<a id="where_id-dan-or_where_id"></a>
### where_id dan or_where_id

Shortcut untuk WHERE pada kolom `id`:

```php
// Sama dengan: where('id', '=', 1)
$user = DB::table('users')->where_id(1)->first();

// OR WHERE id
$users = DB::table('users')
    ->where('email', 'like', '%@gmail.com')
    ->or_where_id(5)
    ->get();
```

<a id="where_in-where_not_in-or_where_in-dan-or_where_not_in"></a>
### where_in, where_not_in, or_where_in, dan or_where_not_in

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

<a id="where_null-where_not_null-or_where_null-dan-or_where_not_null"></a>
### where_null, where_not_null, or_where_null, dan or_where_not_null

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

<a id="where_between-where_not_between-or_where_between-dan-or_where_not_between"></a>
### where_between, where_not_between, or_where_between, dan or_where_not_between

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
// Mencari berdasarkan tanggal
$orders = DB::table('orders')
    ->where_date('created_at', '=', '2024-01-15')
    ->get();
```

**WHERE MONTH:**

```php
// Mencari berdasarkan bulan (1-12)
$orders = DB::table('orders')
    ->where_month('created_at', '=', 1)
    ->get();
```

**WHERE DAY:**

```php
// Mencari berdasarkan hari (1-31)
$orders = DB::table('orders')
    ->where_day('created_at', '=', 15)
    ->get();
```

**WHERE YEAR:**

```php
// Mencari berdasarkan tahun
$orders = DB::table('orders')
    ->where_year('created_at', '=', 2024)
    ->get();
```

<a id="where_exists-dan-where_not_exists"></a>
### where_exists dan where_not_exists

**WHERE EXISTS dengan subquery:**

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

<a id="where_in_sub-dan-where_not_in_sub"></a>
### where_in_sub dan where_not_in_sub

**WHERE IN dengan subquery:**

```php
$users = DB::table('users')
    ->where_in_sub('id', DB::table('orders')
        ->select('user_id')
        ->where('status', '=', 'completed')
    )
    ->get();
```

**WHERE NOT IN dengan subquery:**

```php
$users = DB::table('users')
    ->where_not_in_sub('id', DB::table('banned_users')
        ->select('user_id')
    )
    ->get();
```

<a id="nested-where"></a>
## Nested Where

Grouping WHERE clause dengan parentheses:

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

Contoh lebih kompleks:

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

<a id="where-dinamis"></a>
## Where Dinamis

Query Builder mendukung dynamic WHERE methods berdasarkan nama kolom:

```php
// Sama dengan: where('email', '=', $email)
$user = DB::table('users')->where_email($email)->first();

// Sama dengan: where('name', '=', $name)
$users = DB::table('users')->where_name($name)->get();

// Multiple conditions dengan _and_
$user = DB::table('users')
    ->where_email_and_password($email, $password)
    ->first();
// SQL: WHERE email = ? AND password = ?

// Multiple conditions dengan _or_
$users = DB::table('users')
    ->where_email_or_username($email, $username)
    ->get();
// SQL: WHERE email = ? OR username = ?
```

<a id="raw-where"></a>
## Raw Where

Untuk query WHERE yang kompleks, gunakan raw WHERE:

```php
$users = DB::table('users')
    ->raw_where('age > ? AND city = ?', [18, 'Jakarta'])
    ->get();

// Dengan OR
$users = DB::table('users')
    ->where('active', '=', 1)
    ->raw_or_where('(votes > 100 OR role = ?)', ['admin'])
    ->get();
```

<a id="join-tabel"></a>
## Join Tabel

**Basic JOIN:**

```php
$users = DB::table('users')
    ->join('contacts', 'users.id', '=', 'contacts.user_id')
    ->get(['users.*', 'contacts.phone']);
```

**JOIN dengan closure:**

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

// Dengan closure
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

<a id="group-by--having"></a>
## Group By & Having

**GROUP BY:**

```php
$totals = DB::table('orders')
    ->select(['user_id', DB::raw('SUM(amount) as total')])
    ->group_by('user_id')
    ->get();
```

**GROUP BY dengan HAVING:**

```php
$users = DB::table('orders')
    ->select(['user_id', DB::raw('COUNT(*) as order_count')])
    ->group_by('user_id')
    ->having('order_count', '>', 5)
    ->get();
```

<a id="skip--take"></a>
## Skip & Take

**LIMIT dan OFFSET:**

```php
// Ambil 10 record
$users = DB::table('users')
    ->take(10)
    ->get();

// Skip 20, ambil 10
$users = DB::table('users')
    ->skip(20)
    ->take(10)
    ->get();
```

<a id="for-page"></a>
## For Page

Shortcut untuk pagination manual:

```php
// Halaman 1, 15 per halaman
$users = DB::table('users')
    ->for_page(1, 15)
    ->get();

// Halaman 3, 20 per halaman  
$users = DB::table('users')
    ->for_page(3, 20)
    ->get();
// Sama dengan: skip(40)->take(20)
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

<a id="agregasi"></a>
## Agregasi

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

<a id="ekspresi-sql-mentah"></a>
## Ekspresi SQL Mentah

Untuk query SQL yang kompleks, gunakan `DB::raw()`:

```php
$users = DB::table('users')
    ->select([
        '*',
        DB::raw('COUNT(*) as total'),
        DB::raw('DATE(created_at) as date')
    ])
    ->get();

// Dalam WHERE
$users = DB::table('users')
    ->where(DB::raw('YEAR(created_at)'), '=', 2024)
    ->get();

// Dalam ORDER BY
$users = DB::table('users')
    ->order_by(DB::raw('votes * 2'), 'desc')
    ->get();
```

**Escape manual:**

```php
$value = DB::escape($user_input);
```

<a id="only"></a>
## Only

Mengambil value dari satu kolom saja:

```php
$email = DB::table('users')
    ->where('id', '=', 1)
    ->only('email');

// Return: "user@example.com" (bukan object atau array)
```

<a id="lists"></a>
## Lists

Mengambil array key-value:

```php
// Array dengan value saja
$emails = DB::table('users')->lists('email');
// ['email1@test.com', 'email2@test.com', ...]

// Array dengan custom key
$users = DB::table('users')->lists('name', 'id');
// [1 => 'John', 2 => 'Jane', ...]
```

<a id="cursor"></a>
## Cursor

Untuk dataset besar, gunakan cursor untuk mengurangi memory usage:

```php
foreach (DB::table('users')->cursor() as $user) {
    echo $user->name;
}

// Dengan chunk size custom
foreach (DB::table('orders')->cursor(['*'], 500) as $order) {
    // Process order
}
```

Cursor menggunakan generator PHP dan memproses data secara streaming.

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

**Insert dan dapatkan ID:**

```php
$id = DB::table('users')->insert_get_id([
    'name' => 'Jane Doe',
    'email' => 'jane@example.com'
]);

echo $id; // Auto-increment ID
```

**Insert dengan custom ID column:**

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
// Update dengan WHERE
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
// Delete dengan WHERE
DB::table('users')
    ->where('id', '=', 1)
    ->delete();

// Delete multiple
DB::table('users')
    ->where('active', '=', 0)
    ->delete();

// Delete semua (hati-hati!)
DB::table('temp_data')->delete();
```

<a id="paginasi"></a>
## Paginasi

```php
// 20 per halaman (default)
$users = DB::table('users')->paginate();

// Custom per halaman
$users = DB::table('users')->paginate(15);

// Dengan kolom tertentu
$users = DB::table('users')->paginate(10, ['id', 'name', 'email']);

// Dengan WHERE
$users = DB::table('users')
    ->where('active', '=', 1)
    ->order_by('created_at', 'desc')
    ->paginate(20);

// Di view
foreach ($users->results as $user) {
    echo $user->name;
}

echo $users->links();
```

<a id="find-or-fail"></a>
## Find Or Fail

Method yang throw exception jika record tidak ditemukan:

```php
try {
    $user = DB::table('users')->find_or_fail($id);
} catch (ModelNotFoundException $e) {
    return Response::error('404');
}

// Atau first_or_fail
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

Membuat salinan query untuk digunakan kembali:

```php
$base_query = DB::table('users')
    ->where('active', '=', 1)
    ->where('country', '=', 'US');

// Copy untuk query berbeda
$admins = $base_query->copy()
    ->where('role', '=', 'admin')
    ->get();

$users = $base_query->copy()
    ->where('role', '=', 'user')
    ->get();

// $base_query tidak terpengaruh
```

<a id="reset-query"></a>
## Reset Query

**Reset seluruh query:**

```php
$query = DB::table('users')
    ->where('active', '=', 1)
    ->take(10)
    ->skip(5);

$query->reset(); // Reset semua conditions

$query->get(); // Ambil semua data
```

**Reset WHERE saja:**

```php
$query = DB::table('users')
    ->where('role', '=', 'admin')
    ->where('country', '=', 'US');

$query->reset_where(); // Hapus semua WHERE
$query->where('active', '=', 1)->get();
```

**Reset LIMIT dan OFFSET:**

```php
$query = DB::table('users')
    ->skip(10)
    ->take(20);

$query->reset_limit_offset();
$query->get(); // Ambil semua tanpa limit
```

<a id="debug-query"></a>
## Debug Query

Untuk melihat SQL query yang dihasilkan:

```php
$query = DB::table('users')
    ->where('active', '=', 1)
    ->where('votes', '>', 100);

// Tanpa bindings
echo $query->to_sql();
// SELECT * FROM users WHERE active = ? AND votes > ?

// Dengan bindings
echo $query->debug();
// SELECT * FROM users WHERE active = 1 AND votes > 100
```

Method ini sangat berguna untuk debugging.

<a id="transaction"></a>
## Transaction

Gunakan transaction untuk memastikan integritas data:

```php
DB::connection()->transaction(function () {
    DB::table('accounts')
        ->where('id', '=', 1)
        ->update(['balance' => DB::raw('balance - 100')]);
    
    DB::table('accounts')
        ->where('id', '=', 2)
        ->update(['balance' => DB::raw('balance + 100')]);
});
```

Manual transaction control:

```php
$connection = DB::connection();

try {
    $connection->begin_transaction();
    
    // Query 1
    DB::table('users')->insert(['name' => 'John']);
    
    // Query 2
    DB::table('profiles')->insert(['user_id' => 1]);
    
    $connection->commit();
} catch (Exception $e) {
    $connection->rollback();
    throw $e;
}
```

Lihat dokumentasi [Database Transaction](/docs/database/raw#transaction) untuk detail lebih lanjut.