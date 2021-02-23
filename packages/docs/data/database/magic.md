# Magic Query Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Mengambil Record](#mengambil-record)
- [Membangun Klausa Where](#membangun-klausa-where)
    - [where dan or_where](#where-dan-or_where)
    - [where_in, where_not_in, or_where_in, dan or_where_not_in](#where_in-where_not_in-or_where_in-dan-or_where_not_in)
    - [where_null, where_not_null, or_where_null, and or_where_not_null](#where_null-where_not_null-or_where_null-and-or_where_not_null)
    - [where_between, where_not_between, or_where_between, and or_where_not_between](#where_between-where_not_between-or_where_between-and-or_where_not_between)
- [Nested Where](#nested-where)
- [Where Dinamis](#where-dinamis)
- [Join Tabel](#join-tabel)
- [Order By](#order-by)
- [Group By](#group-by)
- [Skip & Take](#skip--take)
- [Agregasi](#agregasi)
- [Ekspresi SQL Mentah](#ekspresi-sql-mentah)
    - [Manual Escape](#manual-escape)
- [Insert Record](#insert-record)
- [Update Record](#update-record)
- [Delete Record](#delete-record)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Magic Query Builder adalah kelas yang disediakan untuk memudahkan anda membangun kueri SQL dan bekerja dengan database. Semua perintah disiapkan menggunakan [prepared statement](https://www.php.net/manual/en/pdo.prepared-statements.php) sehingga otomatis terlindung dari serangan [SQL Injection](https://en.wikipedia.org/wiki/SQL_injection).

Anda bisa memulai query builder dengan memanggil method `DB::table()`. Cukup sebutkan nama tabel yang ingin dioperasikan:

```php
$query = DB::table('users');
```

Sekarang anda telah memiliki akses Magic Query Builder untuk tabel "users". Dengan query builder ini, anda bisa melakukan operasi - opreasi umum seperti select, insert, update, atau delete record dari tabel.


<a id="mengambil-record"></a>
## Mengambil Record

#### Mengambil sebuah array record dari database:

```php
$users = DB::table('users')->get();
```

>  Method `get()` ini akan me-rturn `array berisi object` dengan nama properti yang sesuai dengan nama - nama kolom pada tabel yang sedang dioperasikan.

#### Mengambil record tunggal dari database:

```php
$user = DB::table('users')->first();
```

#### Mengambil record tunggal berdasarkan primary key:

```php
$user = DB::table('users')->find($id);
```

>  Jika tida ada hasil yang ditemukan, method `first()` akan me-return `NULL`. Sedangkan method `get()` akan me-return sebuah `Array Kosong`.

#### Mengambil value milik sebuah kolom di database:

```php
$email = DB::table('users')
	->where('id', '=', 1)
	->only('email');
```

#### Hanya mengambil kolom tertentu dari database:

```php
$user = DB::table('users')
	->get(['id', 'email as user_email']);
```

#### Mengambil record berdasarkan list kolom yang diberikan:

```php
$users = DB::table('users')
	->take(10)
	->lists('email', 'id');
```

>  Parameter ke-dua sifatnya opsional, boleh diisi boleh tidak.


#### Select distinct dari database:

```php
$user = DB::table('users')
	->distinct()
	->get();
```


<a id="membangun-klausa-where"></a>
## Membangun Klausa Where


<a id="where-dan-or_where"></a>
### where dan or_where

Tersedia beberapa method untuk membantu anda dalam pembangunan klausa where. Method paling dasar yang dapat anda coba adalah `where()` dan `or_where()`. Berikut contoh penggunaanya:

```php
return DB::table('users')
	->where('id', '=', 1)
	->or_where('email', '=', 'example@gmail.com')
	->first();
```

Tentu saja, Tidak hanya operator "sama dengan" saja, anda juga boleh menggunakan operator lain:

```php
return DB::table('users')
	->where('id', '>', 1)
	->or_where('name', 'LIKE', '%Budi%')
	->first();
```

Seperti yang bisa anda bayangkan, secara default method `where()` akan ditambahkan ke susunan kueri menggunakan kondisi `AND`, sedangkan method `or_where()` akan menggunakan kondisi `OR`.


<a id="where_in-where_not_in-or_where_in-dan-or_where_not_in"></a>
### where_in, where_not_in, or_where_in, dan or_where_not_in

Kelompok method `where_in()` memudahkan anda untuk membangun query pencarian pada data array:

```php
DB::table('users')
	->where_in('id', [1, 2, 3])
	->get();

DB::table('users')
	->where_not_in('id', [1, 2, 3])
	->get();

DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_in('id', [1, 2, 3])
	->get();

DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_not_in('id', [1, 2, 3])
	->get();
```


<a id="where_null-where_not_null-or_where_null-and-or_where_not_null"></a>
### where_null, where_not_null, or_where_null, and or_where_not_null

Kelompok method `where_null()` membuat pengecekan nilai NULL menjadi sangat mudah:

```php
return DB::table('users')
	->where_null('updated_at')
	->get();

return DB::table('users')
	->where_not_null('updated_at')
	->get();

return DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_null('updated_at')
	->get();

return DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_not_null('updated_at')
	->get();
```


<a id="where_between-where_not_between-or_where_between-and-or_where_not_between"></a>
### where_between, where_not_between, or_where_between, and or_where_not_between

Kelompok method `where_between()` membuat pengecekan `BETWEEN` antara rentang nilai menjadi sangat mudah:

```php
return DB::table('users')
	->where_between($column, $min, $max)
	->get();

return DB::table('users')
	->where_between('updated_at', '2000-10-10', '2012-10-10')
	->get();

return DB::table('users')
	->where_not_between('updated_at', '2000-10-10', '2012-01-01')
	->get();

return DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_between('updated_at', '2000-10-10', '2012-01-01')
	->get();

return DB::table('users')
	->where('email', '=', 'example@gmail.com')
	->or_where_not_between('updated_at', '2000-10-10', '2012-01-01')
	->get();
```


<a id="nested-where"></a>
## Nested Where

Dimasa mendatang, anda mungkin perlu mengelompokkan potongan - potongan klausa `WHERE` kedalam tanda kurung. Untuk melakukannya, anda hanya perlu mengoper `Closure` sebagai parameter ke method `where()` ataupun `or_where()` seperti berikut:

```php
$users = DB::table('users')
	->where('id', '=', 1)
	->or_where(function ($query) {
		$query->where('age', '>', 25);
		$query->where('votes', '>', 100);
	})
	->get();
```

Contoh diatas akan menghasilkan query sebagai berikut:

```sql
SELECT * FROM "users" WHERE "id" = ? OR ("age" > ? AND "votes" > ?)
```


<a id="where-dinamis"></a>
## Where Dinamis

Method where dinamis dapat meningkatkan kemudahan dalam membaca kodingan anda. Anda pun bisa dengan mudah melakukannya:

```php
$user = DB::table('users')
	->where_email('example@gmail.com') // WHERE `email` = 'example@gmail.com'
	->first();

$user = DB::table('users')
	->where_email_and_password('example@gmail.com', 'secret');
	// WHERE `email` = 'example@gmail.com' AND `password` = 'secret'

$user = DB::table('users')
	->where_id_or_name(1, 'Budi'); // WHERE `id` = 1 OR `name`= 'Budi'
```


<a id="join-tabel"></a>
## Join Tabel

Perlu join tabel? Silahkan gunakan method `join()` atau `left_join()` seperti berikut:

```php
DB::table('users')
	->join('phone', 'users.id', '=', 'phone.user_id')
	->get(['users.email', 'phone.number']);
```

Dimana nama tabel yang ingin anda join dioper ke parameter pertama. Sedangkan 3 parameter setelahnya digunakan untuk menambahkan klausa `ON` pada kueri join.

Setelah bisa menggunakan method `join()`, anda otomatis mampu menggunakan method `left_join()` karena urutan parameternya sama saja:

```php
DB::table('users')
	->left_join('phone', 'users.id', '=', 'phone.user_id')
	->get(['users.email', 'phone.number']);
```

Anda juga boleh memberikan lebih dari satu kondisi ke klausa `ON` dengan cara mengoper `Closure` ke parameter kedua:

```php
DB::table('users')
	->join('phone', function ($join) {
		$join->on('users.id', '=', 'phone.user_id');
		$join->or_on('users.id', '=', 'phone.contact_id');
	})
	->get(['users.email', 'phone.number']);

```


<a id="order-by"></a>
## Order By

Anda dapat dengan mudah melakukan ordering / mengurutkan data hasil kueri menggunakan method `order_by()`. Cukup taruh nama kolom di parameter pertama dan tipe pengurutannya (`'asc'` atau `'desc'`) ke parameter kedua seperti ini:

```php
return DB::table('users')
	->order_by('email', 'desc')
	->get();
```

Tentu saja, anda boleh melakukan pengurutan kolom sebanyak yang anda mau:

```php
return DB::table('users')
	->order_by('email', 'desc')
	->order_by('name', 'asc')
	->get();
```


<a id="group-by"></a>
## Group By

Anda dapat dengan mudah melakukan grouping / pengelompokan data menggunakan method `group_by()` seperti ini:

```php
return DB::table(...)
	->group_by('email')
	->get();
```


<a id="skip--take"></a>
## Skip & Take

Jika anda ingin me-`LIMIT` jumlah data hasil kueri, silahkan gunakan method `take()` seperti ini:

```php
return DB::table('users')
	->take(10)
	->get();
```

Sedangkan untuk mengatur `OFFSET`, silahkan gunakan method `skip()`:

```php
return DB::table('users')
	->skip(10)
	->get();
```


<a id="agregasi"></a>
## Agregasi

Perlu mengambil nilai `MIN`, `MAX`, `AVG`, `SUM`, atau `COUNT`? Cukup sebutkan nama kolomnya:

```php
$min = DB::table('users')->min('age');

$max = DB::table('users')->max('weight');

$avg = DB::table('users')->avg('salary');

$sum = DB::table('users')->sum('votes');

$count = DB::table('users')->count();
```

Tentu saja, anda juga bisa membatasi kuerinya terlebih dahulu menggunakan `WHERE`:

```php
$count = DB::table('users')
	->where('id', '>', 10)
	->count();
```


<a id="ekspresi-sql-mentah"></a>
## Ekspresi SQL Mentah

Terkadang, anda mungkin perlu menginsert nilai kolom menggunakan fungsi native SQL seperti `NOW()`. Tetapi, secara default, Magic Query Builder akan secara otomatis meng-quote dan meng-escape value yang anda oper padanya menggunakan parameter binding untuk mencegah sql injection). Untuk mem-bypass fitur ini, gunakan metode `raw()`, seperti ini:

```php
DB::table('users')
	->update(['updated_at' => DB::raw('NOW()')]);
```

Method `raw()` akan memerintahkan si Magic Query Builder untuk meng-inject SQL mentah anda secara langsung kedalam susunan kueri, tanpa menggunakan parameter binding. Contoh kasus penggunaan `DB::raw()` ini misalnya, anda perlu meng-increment kolom _'votes'_ seperti dibawah ini:

```php
DB::table('users')
	->update(['votes' => DB::raw('votes + 1')]);
```

>  Gunakan `DB::raw()` hanya jika anda sudah tidak punya opsi lain ketika menggunakan Magic Query Builder. Hal ini karena SQL yang diinject langsung ke database sangat rentan akan SQL Injection. Terlebih jika datanya berasal dari inputan user.


Tetapi tentu saja, juga telah disediakan method yang lebih mudah untuk melakukan operasi increment dan decrement ini:

```php
DB::table('users')->increment('votes');

DB::table('users')->decrement('votes');
```


<a id="manual-escape"></a>
### Manual Escape

Seperti yang telah disebutkan diatas, penggunaan `DB::raw()` rentan terhadap serangan SQL Injection, oleh karena itu, disediakan method bantuan untuk meng-escape value pada potongan query mentah anda:

```php
// $name = Request::post('name');

$name = DB::escape($name);

return DB::raw('SELECT * FROM users WHERE name='.$name)->get();
```

<a id="insert-record"></a>
## Insert Record

Method `insert()` mengharapkan data array. Method ini akan me-return `TRUE` atau `FALSE`, yang mengindikasikan suskses atau tidaknya operasi insert anda:

```php
DB::table('users')
	->insert(['email' => 'example@gmail.com']);
```

Perlu insert data yang ID-nya auto-increment? Gunakan method `insert_get_id()`, method ini akan meng-insert data lalu mereturn **last insert id** milik record yang baru saja anda insert tadi:

```php
$id = DB::table('users')
	->insert_get_id(['email' => 'example@gmail.com']);
```

>  Method `insert_get_id()` ini mewajibkan kolom auto-increment anda bernama `'id'`.



<a id="update-record"></a>
## Update Record

Untuk mengupdate record, cukup oper array asosiatif ke method `update()` seperti berikut:

```php
$data = [
    'email' => 'budi.baru@gmail.com'
    'name'  => 'Budi Purnomo'
];

$affected = DB::table('users')->update($data);
```

Tentu saja, jika anda hanya ingin mengupdate beberapa kolom saja, anda bisa menambahkan klausa `WHERE` sebelum memanggil method ini:

```php
$data = [
    'email' => 'budi.baru@gmail.com'
    'name'  => 'Budi Purnomo'
];

$affected = DB::table('users')
	->where('id', '=', 1)
	->update($data);
```


<a id="delete-record"></a>
## Delete Record

Sedangkan jika anda ingin menghapus record dari database, cukup panggil method `delete()` seperti ini:

```php
$affected = DB::table('users')
	->where('id', '=', 1)
	->delete();
```

Ingin cara cepat menghapus data berdasarkan ID? Bisa. Langsung saja oper ID-nya seperti ini:

```php
$affected = DB::table('users')->delete(1);
```
