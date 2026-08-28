# Kesesuaian Rakit dengan Laravel

Status penyelarasan perilaku dan keluaran (*response*) Rakit terhadap Laravel.

- **Pembanding**: API Laravel 11/12.
- **Cara verifikasi**: setiap poin dicek dengan menjalankan kode di atas SQLite in-memory dan
  membaca sumbernya, bukan dari ingatan.
- **Terakhir diperbarui**: 2026-08-28.

## Ringkasan

| Bagian | Status |
|---|---|
| Bug (3 temuan) | Selesai |
| Perilaku yang beda diam-diam (6 temuan) | Selesai, kecuali yang memang keputusan desain |
| Attribute casting, `$appends`, `$visible` | Selesai |
| Query builder: method yang sering dipakai | Selesai |
| Facile: helper model yang sering dipakai | Selesai |
| Hasil query berupa `Collection` | Selesai |
| Query relasi (`has` / `where_has` / `with_count`) | Selesai |
| `Input`, `Messages`, `Redirect`, `Response`, rule validasi | Selesai |
| Row locking (`lock_for_update` / `shared_lock`) | Selesai |
| Lapisan transaksi (audit + perbaikan) | Selesai |
| Schema builder + migrasi (audit + perbaikan) | Selesai |
| Session dan auth driver (audit + perbaikan) | Selesai |
| Blade component, API Resource, Stringable, observer | Sengaja tidak dikerjakan |

Cakupan test naik dari 1921 menjadi **2064 test**, semuanya lolos, dan seluruh berkas yang
disentuh lolos `php -l` pada PHP 5.6.

## Yang tidak dihitung sebagai ketidakcocokan

Rakit memakai `snake_case` secara sengaja. Perbedaan penamaan berikut **bukan** gap:

| Rakit | Laravel |
|---|---|
| `Str::starts_with()` | `Str::startsWith()` |
| `Collection::is_empty()` | `Collection::isEmpty()` |
| `scope_active($query)` | `scopeActive($query)` |
| `get_nama($value)` / `set_nama($value)` | accessor / mutator |
| `Model::$perpage` | `Model::$perPage` |
| `@layout` | `@extends` |
| `Query::lists()` | `Query::pluck()` (keduanya kini ada) |
| `Query::only()` | `Query::value()` (keduanya kini ada) |
| `Arr::associative()` | `Arr::isAssoc()` |
| `Arr::sequential()` | `Arr::isList()` |
| `Str::integers()` | `Str::numbers()` |
| `Model::dirty()` | `Model::isDirty()` |
| `Input::arr()` | `Request::array()` (`array` kata kunci di PHP 5.4) |
| `Facile` | `Eloquent` |

---

## 1. Bug — selesai

### 1.1 `$original` tidak disinkronkan saat model dimuat — **selesai**

`Facile\Query::hydrate()` kini memanggil `sync()` setelah `fill_raw()`. Dampaknya:

- `save()` hanya menulis kolom yang benar-benar berubah, tidak lagi menulis ulang seluruh
  kolom termasuk primary key. Ini menghilangkan risiko *lost update* pada aplikasi dengan
  banyak penulis paralel.
- `changed($attribute)` berfungsi lagi untuk model hasil query.
- `dirty()` mengembalikan `false` untuk model yang belum disentuh, sehingga guard di awal
  `save()` bekerja dan query UPDATE yang sia-sia tidak dikirim.

Ditambah `was_changed()` dan `get_original()` dengan semantik Laravel.

### 1.2 `Redirect::back()` tanpa referrer — **selesai**

Signature menjadi `back($status = 302, $fallback = false)`, dan `null` tidak lagi bocor ke
`URL::to()`. Deprecation `ltrim(): Passing null` pada PHP 8.1+ hilang. Helper global `back()`
ikut menerima `$fallback`.

### 1.3 Nilai kembalian `save()` — **selesai**

Tidak lagi bergantung pada jumlah baris terpengaruh, karena MySQL melaporkan
`affected_rows = 0` ketika nilai barunya identik. `save()` kini mengembalikan `true` selama
query berhasil dieksekusi.

Sekalian: `Query::insert()` dan `insert_or_ignore()` mengembalikan `bool`, bukan array kosong
yang selalu falsy.

---

## 2. Perilaku yang berbeda diam-diam

### 2.1 `where_has()` / `where_like()` menjadi nama kolom — **selesai**

- `where_like()`, `or_where_like()`, `where_not_like()` dan `or_where_not_like()` kini nyata.
- `where_has()` dan `where_doesnt_have()` nyata di `Facile\Query` (lihat bagian 3.3).
- `Query::__call()` punya daftar nama terpesan. Method Laravel yang belum didukung
  (`where_json_contains`, `where_full_text`, `where_relation`, dan seterusnya) melempar
  exception yang jelas, bukan diam-diam dikompilasi menjadi kolom bernama sama.

### 2.2 `reload()` bukan `refresh()` — **selesai**

`refresh()` ditambahkan dengan semantik Laravel: memutasi `$this`, mengosongkan relasi yang
sudah ter-load, lalu menyinkronkan ulang `$original`. `reload()` dan `fresh()` dibiarkan apa
adanya supaya kode lama tidak rusak.

### 2.3 `changed()` semantiknya `is_dirty()` — **selesai**

`was_changed($attribute = null)` ditambahkan, membaca dari properti `$changes` yang direkam
oleh `save()`. `changed()` tetap berarti "belum disimpan" seperti `isDirty()`.

### 2.4 Hasil query berupa `array` — **selesai**

`Query::get()`, `Facile\Query::get()`, `Model::all()` dan `Paginator::results` semuanya
mengembalikan `System\Collection`.

```php
$names = User::all()->pluck('name');
$aktif = DB::table('users')->get()->filter(function ($u) { return $u->active; });
```

Karena `Collection` sudah `Countable`, `IteratorAggregate` dan `ArrayAccess`, kode lama yang
memakai `count()`, `foreach` dan `$hasil[0]` tetap jalan. **Ini tetap perubahan yang bisa
merusak** untuk kode yang memanggil `array_map()`, `array_filter()`, `is_array()` atau
`reset()` langsung di atas hasil query — pakai `->all()` untuk mendapatkan array mentahnya.

### 2.5 `delete()` mengembalikan jumlah baris — **selesai**

`$model->delete()` kini mengembalikan `bool`: `true` bila terhapus, `false` bila modelnya
belum ada di database.

### 2.6 Nomor halaman di luar jangkauan — **sengaja dipertahankan**

`Paginator::page()` tetap menjepit `?page=99` ke halaman terakhir. Keputusan desain.

---

## 3. Fitur besar

### 3.1 Attribute casting — **selesai**

```php
class Post extends Facile
{
    public static $casts = [
        'active' => 'boolean',
        'options' => 'array',
        'price' => 'decimal:2',
        'published_at' => 'datetime',
    ];
}
```

Tipe yang didukung: `int`, `integer`, `real`, `float`, `double`, `decimal:<digit>`, `string`,
`bool`, `boolean`, `object`, `array`, `json`, `collection`, `date`, `datetime`, `timestamp`.

Casting berlaku saat dibaca dan diserialisasi balik saat ditulis, jadi
`$post->options = ['a' => 1]` otomatis di-encode menjadi JSON. Ini sekaligus menormalkan
perbedaan tipe antar driver, karena MySQL mengembalikan semuanya sebagai string sedangkan
SQLite tidak. Properti `$date_format` (default `Y-m-d H:i:s`) menentukan bentuk tanggal di
`to_array()`.

### 3.2 `$appends` dan `$visible` — **selesai**

Ditambah `make_hidden()`, `make_visible()` dan `append()` untuk override per instance.
`to_array()` juga menjalankan accessor, jadi atribut hasil accessor ikut ke JSON.

### 3.3 Query relasi — **selesai**

`has()`, `or_has()`, `doesnt_have()`, `where_has()`, `where_doesnt_have()` dan `with_count()`,
semuanya dikompilasi menjadi subquery terkorelasi:

```sql
SELECT * FROM "users" WHERE EXISTS (
    SELECT 1 FROM "posts" WHERE "posts"."user_id" = "users"."id"
)
```

Didukung untuk `has_one`, `has_many`, `belongs_to`, `belongs_to_many`, `morph_one`,
`morph_many` dan `has_many_through`, lewat method `correlate()` di tiap kelas relasi.
`morph_to` dan `morph_to_many` melempar exception yang jelas karena tidak bisa dikorelasikan
dengan satu tabel.

Belum ada: `with_sum()`, `with_avg()` dan `load_missing()`.

### 3.4 Blade component — **tidak dikerjakan**

Subsistem besar, sementara `@layout`/`@section`/`@include`/`@push` sudah menutupi kebutuhan
yang sama. Sengaja tidak dikerjakan.

### 3.5 `Str::of()` / Stringable — **tidak dikerjakan**

Gula sintaks murni, prioritas rendah.

### 3.6 Model event — **tidak dikerjakan**

`Hook::fire('facile.saving: NamaModel')` sudah setara secara fungsi. Belum ada `boot()`,
kelas observer, event `retrieved`/`replicating`/`restoring`, dan kemampuan membatalkan operasi
dengan mengembalikan `false` dari listener.

### 3.7 API Resource — **tidak dikerjakan**

`Model::to_array()` dengan `$hidden`/`$visible`/`$appends`/`$casts` menutupi sebagian besar
kebutuhannya.

---

## 4. Yang ditambahkan

### 4.1 `System\Database\Query`

`value` · `pluck` · `chunk` · `each` · `when` · `unless` · `tap` · `sole` ·
`where_like` (+ `or_`/`not_`) · `select_raw` · `add_select` · `order_by_raw` ·
`group_by_raw` · `having_raw` · `or_having` · `or_having_raw` · `in_random_order` ·
`re_order` · `right_join` · `cross_join` · `update_or_insert` · `insert_or_ignore` ·
`increment`/`decrement` dengan `$extra`

Grammar ikut diperluas: `random()` dan `insert_ignore()` dengan override untuk MySQL, SQLite,
Postgres dan SQL Server; `HAVING` mendukung konektor `OR` dan bentuk mentah; `CROSS JOIN`
tanpa klausa `ON`; dan ordering mentah tidak lagi kena `COLLATE NOCASE` di SQLite.

`lock_for_update` · `shared_lock` · `lock($raw)` untuk mengunci baris

**Masih belum ada**: `upsert` · `from_sub` · `join_sub` · `where_json_contains` ·
`where_json_length` · `where_full_text`

### 4.2 `System\Database\Facile\Model`

`first_or_new` · `first_or_create` · `update_or_create` · `find_many` · `destroy` ·
`where_key` · `touch` · `refresh` · `replicate` · `is` · `is_not` · `was_changed` ·
`get_original` · `increment` · `decrement` · `make_hidden` · `make_visible` · `append` ·
`new_query` · `get_key_name` · `cast_attribute` · `cast_type` · `has_cast`

**Masih belum ada**: `truncate` · `lazy` · `get_route_key` · `resolve_route_binding` ·
`has_one_through`

### 4.3 `System\Database\Facile\Query`

`has` · `or_has` · `doesnt_have` · `where_has` · `where_doesnt_have` · `with_count` ·
`chunk` · `each` · `sole` · `when` · `unless` · `tap` · `where_key` · `where_key_not`

`chunk()` dan `each()` di sini menghidrasi model, bukan `stdClass`.

### 4.4 `System\Input`

`string` · `integer` · `float` · `boolean` · `date` · `arr` · `collect` · `keys` ·
`missing` · `any_filled`

**Masih belum ada**: `when_has` · `when_filled` · `enum`

### 4.5 `System\Messages`

Kini mengimplementasi `ArrayAccess`, `Countable`, `IteratorAggregate` dan `JsonSerializable`,
plus `merge` · `forget` · `keys` · `has_any` · `is_empty` · `is_not_empty` · `to_array` ·
`to_json`. Jadi `count($errors)` dan `json_encode($errors)` memberi hasil yang diharapkan.

### 4.6 `System\Response` dan `System\Redirect`

**Response**: `no_content` · `file` (tampilkan inline, bukan unduh).
**Masih belum ada**: `stream` · `stream_download`.

**Redirect**: `away` · `secure` · `refresh` · `guest` · `intended`.
`guest()` dan `intended()` adalah pasangan alur "simpan tujuan sebelum login, kembali ke sana
setelah login", dan melempar exception yang jelas bila belum ada session driver.

### 4.7 Rule validasi

`after_or_equal` (+ `after_or_equals`) · `before_or_equal` (alias) · `lowercase` ·
`uppercase` · `ascii` · `decimal` · `multiple_of` · `max_digits` · `min_digits` ·
`doesnt_start_with` · `doesnt_end_with` · `contains` · `list` · `missing` · `prohibited` ·
`declined` · `mac_address` · `ulid` · `hex_color`

Semuanya lengkap dengan baris bahasa `en` dan `id`.

**Masih belum ada**: `bail` · `sometimes` · `current_password` · `enum` · `exclude*` ·
`extensions` · `in_array_keys` · `required_array_keys` · `required_if_accepted` ·
`accepted_if` · `declined_if` · `missing_if` · `missing_unless` · `prohibited_if` ·
`prohibited_unless` · `prohibits`

### 4.8 Ekor panjang yang sengaja dilewati

`Collection` (`when`, `unless`, `tap`, `sole`, `skip`, `join`, `concat`, `count_by`, dan
sekitar 30 lainnya), `Str` (`of`, `mask`, `squish`, `is_json`, `is_uuid`, `match`, dan
sekitar 25 lainnya), `Arr` (`map`, `map_with_keys`, `key_by`, dan sekitar 18 lainnya),
direktif Blade (`@switch`, `@class`, `@checked`, dan lainnya), serta helper global
(`response()`, `request()`, `auth()`, `throw_if`, dan lainnya).

Mengejar 100% API Laravel di bagian ini adalah pekerjaan tanpa ujung dengan imbalan menurun.
Tambahkan satu per satu saat benar-benar dibutuhkan.

---

## 4b. Lapisan transaksi

Diaudit setelah bagian data selesai, dan menemukan empat masalah. Semuanya sudah
diperbaiki di `System\Database\Connection`.

### 4b.1 API transaksi manual tidak pernah ada — **selesai**

**Ini temuan paling parah dari seluruh audit.** `Connection` tidak punya
`begin_transaction()`, `commit()`, maupun `rollback()`, sementara `Connection::__call()`
mengubah method apa pun yang tidak dikenal menjadi query builder untuk tabel bernama
sama. Jadi kode ini:

```php
$connection->begin_transaction();
// ..
$connection->commit();
```

diam-diam menjadi `table('begin_transaction')` dan `table('commit')` — tidak ada
transaksi yang pernah dibuka, `inTransaction()` tetap `false`, dan tidak ada satu pun
error. Contoh persis seperti itu ada di `packages/docs/data/database/magic.md`, jadi
siapa pun yang mengikuti dokumentasi menulis kode yang tampak transaksional padahal
bukan. Ketiga method itu sekarang benar-benar ada.

### 4b.2 Transaksi bersarang melempar exception — **selesai**

`transaction()` di dalam `transaction()` menghasilkan
`PDOException: There is already an active transaction`. Sekarang tingkat kedua dan
seterusnya memakai savepoint, jadi method yang membungkus pekerjaannya sendiri dalam
transaksi aman dipanggil dari dalam transaksi lain. Hanya `commit()` terluar yang
benar-benar commit.

SQL savepoint dikompilasi per driver: `SAVEPOINT` / `RELEASE SAVEPOINT` /
`ROLLBACK TO SAVEPOINT` untuk MySQL, PostgreSQL dan SQLite; `SAVE TRANSACTION` /
`ROLLBACK TRANSACTION` untuk SQL Server, yang tidak punya padanan `RELEASE`.

### 4b.3 `transaction()` membuang nilai kembalian callback — **selesai**

Dulu mengembalikan hasil `commit()` yang selalu `true`. Sekarang mengembalikan apa
pun yang dikembalikan callback, seperti Laravel.

### 4b.4 `rollback()` dari error handler — **selesai**

`commit()` dan `rollback()` kini mengembalikan `false` alih-alih melempar ketika
tidak ada transaksi yang terbuka, sehingga aman dipanggil dari blok `catch` yang
tidak tahu pasti apakah transaksi sempat dibuka.

Ditambah `transaction_level()` untuk menanyakan kedalaman saat ini.

**Masih belum ada**: percobaan ulang otomatis saat deadlock (`transaction($callback, $attempts)`
di Laravel).

## 4c. Schema builder, migrasi, session dan auth

Empat area dari daftar "belum diaudit" dikerjakan berikutnya, dengan cara yang sama:
menjalankan kode di atas SQLite in-memory, bukan membaca sekilas.

### 4c.1 `Schema::drop()` mengabaikan argumen koneksi — **selesai**

**Temuan paling berbahaya di ronde ini.** `Schema::drop($table, $connection)` memanggil
`$table->on($connection)`, padahal `Table::on()` itu klausa `ON` untuk foreign key, bukan
pemilih koneksi (`Table::connection()`). Saat dipanggil, `$table->commands` masih kosong,
jadi method itu tidak melakukan apa pun dan argumennya hilang begitu saja:

```php
Schema::drop('users', 'replika');   // tabel di koneksi default yang hilang
```

Tabel dihapus dari koneksi **default**, bukan dari koneksi yang diminta, tanpa satu pun
error. `Schema::drop_if_exists()` lebih parah lagi: pengecekannya benar-benar melihat
koneksi yang diminta, lalu penghapusannya jatuh ke koneksi default.

Sekalian, `table()`, `create()`, `create_if_not_exists()` dan `rename()` kini menerima
argumen `$connection` di posisi terakhir, seperti `drop()`. Sebelumnya satu-satunya cara
mengarahkan koneksi adalah `$table->connection()` dari dalam closure, sementara dokumentasi
justru mencontohkan `$table->on('mysql')` yang tidak pernah bekerja.

### 4c.2 Perintah schema yang tidak didukung hilang diam-diam — **selesai**

`Schema::execute()` membungkus pemanggilan grammar dengan `if (method_exists(...))`. Kalau
grammar tidak punya method untuk suatu perintah, perintahnya dilewati tanpa jejak. Di SQLite
itu berarti `drop_column()`, `drop_primary()`, `drop_foreign()`, `drop_fulltext()` dan
`primary()` semuanya **tidak melakukan apa-apa dan tetap melaporkan sukses**:

```php
Schema::table('users', function ($table) {
    $table->drop_column('umur');    // kolomnya masih ada setelah ini
});
```

Sekarang melempar exception yang menyebut nama driver dan nama perintahnya.

### 4c.3 Grammar SQLite — **selesai**

- `drop_column()` benar-benar dikerjakan lewat `ALTER TABLE .. DROP COLUMN` (SQLite 3.35+).
- `rename_column()` dikerjakan lewat `ALTER TABLE .. RENAME COLUMN` (SQLite 3.25+).
  Sebelumnya selalu melempar "not supported", padahal SQLite mendukungnya sejak 2018.
- `drop_column_if_exists()` menyaring kolom lewat `PRAGMA table_info` lebih dulu, jadi
  kolom yang tidak ada dilewati alih-alih menggagalkan migrasi.
- Versi SQLite dicek saat runtime, jadi library lama dapat pesan yang jelas, bukan error SQL.
- `foreign()` sebelumnya menghasilkan `ALTER TABLE .. ADD CONSTRAINT` yang **bukan sintaks
  SQLite** — setiap `Schema::create()` dengan foreign key gagal dengan *syntax error*.
  Sekarang foreign key ditulis inline ke dalam `CREATE TABLE`, sama seperti primary key, dan
  benar-benar ditegakkan. Menambah atau menghapus foreign key di tabel yang sudah ada melempar
  exception yang jelas, karena SQLite memang tidak bisa.

### 4c.4 Grammar MySQL, Postgres dan SQL Server — **selesai**

- MySQL tidak punya `IF EXISTS` untuk kolom maupun indeks di dalam `ALTER TABLE`, jadi
  seluruh method `*_if_exists` di sana **mengompilasi perintah biasa** dan tetap gagal
  ketika objeknya tidak ada — persis kebalikan dari yang dijanjikan namanya. Sekarang nama
  kolom, indeks dan foreign key dicari lebih dulu di `information_schema`, dan perintahnya
  dilewati kalau tidak ketemu. Diverifikasi terhadap MariaDB 10.11 yang berjalan lokal.
- Postgres `drop_primary()` menyusun nama constraint dari `$table->name` tanpa prefix tabel,
  dan mengabaikan nama yang dioper pemanggil. Keduanya diperbaiki.
- SQL Server `drop_column_if_exists()` mengompilasi `DROP COLUMN` biasa tanpa `IF EXISTS`.

### 4c.5 `Schema::tables()` dan `columns()` melihat database yang salah — **selesai**

Keduanya mencari nama database lewat `Config::get('database.connections.'.$driver.'.database')`
— **berdasarkan nama driver, bukan nama koneksi**. Untuk koneksi bernama `replika` dengan
driver `mysql`, yang dibaca adalah konfigurasi koneksi `mysql`, yang bisa saja menunjuk
database lain atau tidak ada sama sekali. Hasilnya `columns()` mengembalikan array kosong dan
`has_column()` selalu `false`, tanpa error.

Prefix tabel juga tidak pernah diterapkan, padahal `create()` dan `drop()` menerapkannya lewat
grammar. Jadi `Schema::has_table('users')` mengembalikan `false` untuk tabel yang barusan
dibuat sendiri oleh `Schema::create('users')` kalau koneksinya memakai prefix — yang membuat
`create_if_not_exists()` membuat ulang tabel yang sudah ada, dan `drop_if_exists()` tidak
menghapus apa pun.

Sekarang keduanya membaca `database` dan `prefix` dari konfigurasi koneksi yang benar-benar
dipakai, dan `DB::escape()` yang mengutip memakai PDO koneksi default diganti dengan quoting
lewat koneksi tujuan.

### 4c.6 `migrate:rollback` mati total — **selesai**

Regresi dari bagian 2.4. `Migrate\Database::last()` mengembalikan hasil `Query::get()`, yang
sejak `get()` diubah menjadi `Collection` bukan lagi array, sementara `Resolver::resolve()`
menuliskan type hint `array`:

```
TypeError: Resolver::resolve(): Argument #1 ($migrations) must be of type array,
System\Collection given
```

Artinya `migrate:rollback`, `migrate:reset` dan `migrate:refresh` **fatal error sejak
perubahan itu**, dan tidak ada satu pun test yang menutupi jalur tersebut. `last()` kini
mengembalikan array lagi, dan ada berkas test baru `tests/cases/migrate.test.php` yang
menjalankan migrasi sungguhan lalu me-rollback-nya.

### 4c.7 `empty()` di atas hasil query — **selesai**

Regresi lain dari bagian 2.4 dengan pola yang sama: `empty($object)` selalu `false` untuk
objek apa pun, termasuk `Collection` kosong. Di `Job\Drivers\Database` itu membuat cabang
"Job is empty" tidak pernah tercapai dan `where_in('id', [])->delete()` tetap dijalankan.
Diganti dengan `count()`, yang memang dipahami `Collection`.

### 4c.8 `where_in()` dengan array kosong — **selesai**

`where_in($column, [])` menghasilkan `IN ()`, yang syntax error di MySQL, Postgres dan SQL
Server (SQLite kebetulan memaafkannya). Sekarang mengompilasi `0 = 1`, dan `where_not_in()`
mengompilasi `1 = 1`, sama seperti Laravel.

### 4c.9 Session tidak pernah dibersihkan — **selesai**

Tidak ada mekanisme *garbage collection* sama sekali. Driver `file` menulis berkas ke
`storage/sessions/` dan driver `database` menulis baris ke tabel `sessions`, dan keduanya
tidak pernah dihapus. Driver lain aman karena penyimpanannya sendiri yang mengatur
kedaluwarsa: cookie ada di perangkat pengunjung, sedangkan memcached, redis dan apc menerima
`lifetime` sebagai TTL.

Ditambahkan interface `Session\Drivers\Sweeper`, diimplementasi oleh driver `file` dan
`database`, dan `Payload::save()` menjalankannya lewat undian. Peluangnya diatur opsi
`'sweep' => [2, 100]` di `application/config/session.php`, dan `false` untuk mematikannya.

Sekalian, `Payload::regenerate()` dan `invalidate()` tidak lagi mengasumsikan `load()` sudah
pernah dipanggil.

### 4c.10 Session fixation saat login — **selesai**

`Auth::login()` menyimpan token ke session yang sedang berjalan tanpa mengganti id-nya. Jadi
id session yang ditanam penyerang sebelum korban login akan ikut terautentikasi setelahnya.
Laravel memanggil `session()->migrate(true)` di titik ini; Rakit tidak memanggil apa pun, dan
controller stub bawaannya juga tidak.

`login()` dan `logout()` kini memanggil `Session::regenerate()`, yang mengganti id sambil
mempertahankan data session, jadi token login tetap ada di tempatnya.

### 4c.11 Cookie "remember me" tidak bisa dicabut — **selesai**

Isi cookie-nya `Crypter::encrypt($id.'|'.Str::random(40))`, dan bagian acaknya tidak pernah
dicocokkan dengan apa pun. Jadi cookie itu sebenarnya hanya "id user terenkripsi": tidak bisa
dicabut, tidak batal saat logout, dan tetap berlaku setelah password diganti. Siapa pun yang
memegang salinannya tetap bisa masuk selamanya.

Sekarang mengikuti Laravel. Cookie berisi tiga bagian, `id|token|hash password`, tokennya
disimpan di kolom `remember_token` pada user, dan `recall()` mencocokkan token serta hash
password memakai pembanding waktu-tetap `Crypter::equals()`. Efeknya:

- `logout()` mengganti token yang tersimpan, jadi seluruh cookie lama langsung mati.
- Mengganti password ikut mematikan seluruh cookie lama.
- Cookie format lama yang hanya dua bagian ditolak.

Kolom `remember_token` ditambahkan ke migrasi `create_users_table` bawaan. Aplikasi yang sudah
jalan perlu menambahkannya sendiri; tanpa kolom itu login tetap bekerja, hanya fitur "remember
me" yang mati alih-alih membagikan cookie yang tidak bisa dicabut. Driver pihak ketiga yang
tidak mengimplementasi `save_remember_token()` juga berperilaku begitu.

### 4c.12 Primary key string dan UUID tidak bisa login — **selesai**

`retrieve()` di driver `magic` maupun `facile` menyaring tokennya dengan
`FILTER_VALIDATE_INT`, jadi user dengan primary key berupa string atau UUID **tidak pernah
sampai ke query** dan selalu dianggap tamu. Penyaringnya diganti dengan pengecekan
string/integer yang tidak kosong, yang tetap menahan token null tanpa menyentuh database.

## 5. Dokumentasi yang ikut diperbarui

| Berkas | Perubahan |
|---|---|
| `packages/docs/data/database/magic.md` | `get()` mengembalikan `Collection`, tabel method diperluas |
| `packages/docs/data/database/facile.md` | Bagian baru: Attribute Casting, Model Helpers, Relationship Queries; `$casts`/`$visible`/`$appends`; catatan `delete()` mengembalikan bool |
| `packages/docs/data/views/pagination.md` | Ditulis ulang: API accessor, JSON, page name kustom, kustomisasi view Blade, markup baru |
| `packages/docs/data/input.md` | Bagian baru: Typed Input |
| `packages/docs/data/messages.md` | Bagian baru: Bag Helpers |
| `packages/docs/data/validation.md` | `after_or_equal`, `declined`, bagian baru: Other Rules |
| `packages/docs/data/helpers.md` | `back()` dengan fallback, redirect dan response baru |
| `packages/docs/data/database/schema.md` | `$table->on('mysql')` diganti `connection()`, argumen koneksi baru, batasan SQLite |
| `packages/docs/data/session/config.md` | Bagian baru: Sweeping |
| `packages/docs/data/auth/usage.md` | Catatan id session diganti saat login dan logout, kolom `remember_token` |
| `packages/docs/data/auth/config.md` | Kolom `remember_token`, primary key boleh string/UUID |

Halaman pagination sebelumnya mendokumentasikan `$orders->per_page`, `$orders->from` dan
`$orders->to` sebagai properti — ketiganya tidak pernah ada — serta `previous()`/`next()` yang
sudah dihapus saat markup dipindah ke Blade.

Halaman schema mencontohkan `$table->on('mysql')` untuk memilih koneksi, yang tidak pernah
bekerja karena `on()` adalah klausa foreign key (lihat bagian 4c.1).

---

## 6. Yang belum diaudit

- Routing, middleware, dan model binding
- Antrian (`Job`) di luar driver database, penjadwalan, dan console command selain migrasi
- Cache pada tingkat driver
- Mail, notifikasi, dan event broadcasting
- `Carbon` bawaan Rakit dibandingkan `nesbot/carbon`
- Testing helper (`assertDatabaseHas`, HTTP test, dan sejenisnya)
- Connection pooling
