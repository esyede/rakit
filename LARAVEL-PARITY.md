# Kesesuaian Rakit dengan Laravel

Status penyelarasan perilaku dan keluaran (*response*) Rakit terhadap Laravel.

- **Pembanding**: API Laravel 11/12.
- **Cara verifikasi**: setiap poin dicek dengan menjalankan kode di atas SQLite in-memory dan
  membaca sumbernya, bukan dari ingatan.
- **Terakhir diperbarui**: 2026-08-27.

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
| Blade component, API Resource, Stringable, observer | Sengaja tidak dikerjakan |

Cakupan test naik dari 1921 menjadi **2010 test**, semuanya lolos, dan seluruh berkas yang
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

**Masih belum ada**: `upsert` · `lock_for_update` · `shared_lock` · `from_sub` · `join_sub` ·
`where_json_contains` · `where_json_length` · `where_full_text`

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

Halaman pagination sebelumnya mendokumentasikan `$orders->per_page`, `$orders->from` dan
`$orders->to` sebagai properti — ketiganya tidak pernah ada — serta `previous()`/`next()` yang
sudah dihapus saat markup dipindah ke Blade.

---

## 6. Yang belum diaudit

- Routing, middleware, dan model binding
- Antrian (`Job`), penjadwalan, dan console command
- Session, Auth, dan Cache pada tingkat driver
- Migrasi dan Schema builder
- Mail, notifikasi, dan event broadcasting
- `Carbon` bawaan Rakit dibandingkan `nesbot/carbon`
- Testing helper (`assertDatabaseHas`, HTTP test, dan sejenisnya)
- Perilaku transaksi dan connection pooling
