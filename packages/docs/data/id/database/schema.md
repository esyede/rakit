# Schema Builder

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Membuat & Menghapus Tabel](#membuat--menghapus-tabel)
-   [Listing Tabel](#listing-tabel)
-   [Tambah Kolom](#tambah-kolom)
-   [Listing Kolom](#listing-kolom)
-   [Hapus Kolom](#hapus-kolom)
-   [Tambah Index](#tambah-index)
-   [Hapus Index](#hapus-index)
-   [Foreign Key](#foreign-key)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Schema builder menyediakan sekumpulan method untuk membuat, memodifikasi dan
menghapus tabel di database anda. Dengan sintaks yang sederhana, anda dapat mengelola skema tabel
tanpa harus susah payah menulis kueri mentah yang rentan akan kesalahan.

Selain itu, schema buider juga bersifat independen, artinya file migrasi yang anda buat
akan dapat dijalankan di banyak sistem database.

_Bacaan lebih lanjut:_

-   [Migrasi Database](/docs/id/database/migrations)

<a id="membuat--menghapus-tabel"></a>

## Membuat & Menghapus Tabel

Komponen `Schema` (yang berada di `System\Database\Schema`) digunakan untuk
membuat dan memodifikasi tabel. Mari kita langsung lihat contohnya:

#### Membuat sebuah tabel sederhana:

```php
Schema::create('users', function ($table) {
	$table->increments('id');
});
```

Mari kita bahas contoh diatas. Method `create()` memberi tahu si schema builder
bahwa ini adalah tabel baru, sehingga harus dibuat.
Di parameter kedua, kita mengoper `Closure` yang menerima instance kelas `Table` via prooperty `$table`.

Dengan menggunakan objek kelas `Table` ini, kita dapat mudah menambah dan menghapus kolom dan index pada tabel.

#### Menghapus sebuah tabel:

```php
Schema::drop('users');
```

#### Menghapus sebuah tabel milik koneksi database tertentu:

```php
Schema::drop('users', 'nama_koneksi');
```

Terkadang anda mungkin perlu menentukan di koneksi database mana operasi harus dijalankan.

#### Menentukan di koneksi database mana operasi harus dijalankan:

```php
Schema::create('users', function ($table) {
	$table->on('connection');
});
```

<a id="listing-tabel"></a>

## Listing Tabel

Anda juga dapat me-list seluruh tabel dengan komponen ini:

```php
$tables = Schema::tables();

dd($tables);
```

Dan tentunya, anda juga dapat memilih koneksi mana yang ingin di list tabelnya:

```php
$tables = Schema::tables('sqlite');

dd($tables);
```

<a id="tambah-kolom"></a>

## Tambah Kolom

Komponen `Table` menyediakan sekumpulan perintah untuk membantu anda dalam membuat kolom.
Mari kita lihat perintah - perintahya:

| Perintah                           | Keterangan                                                     |
| ---------------------------------- | -------------------------------------------------------------- |
| `$table->increments('id');`        | Tambahkan kolom auto-increment bernama `'id'`                  |
| `$table->string('email');`         | Tambahkan kolom VARCHAR                                        |
| `$table->string('name', 100);`     | Tambahkan kolom VARCHAR dengan panjang maksimal                |
| `$table->integer('votes');`        | Tambahkan kolom INTEGER                                        |
| `$table->float('amount');`         | Tambahkan kolom FLOAT                                          |
| `$table->decimal('amount', 5, 2);` | Tambahkan kolom DECIMAL dengan presisi dan skala               |
| `$table->boolean('confirmed');`    | Tambahkan kolom BOOLEAN                                        |
| `$table->date('created_at');`      | Tambahkan kolom DATE                                           |
| `$table->timestamp('added_on');`   | Tambahkan kolom TIMESTAMP                                      |
| `$table->timestamps();`            | Tambahkan kolom DATE bernama `'created_at'` dan `'updated_at'` |
| `$table->text('description');`     | Tambahkan kolom TEXT                                           |
| `$table->blob('data');`            | Tambahkan kolom BLOB                                           |
| `->nullable()`                     | Izinkan kolom diisi dengan NULL                                |
| `->default($value)`                | Beri default value untuk kolom                                 |
| `->unsigned()`                     | Buat kolom INTEGER menjadi UNSIGNED                            |

> Di semua RDBMS, setiap kolom `BOOLEAN` akan selalu diubah secara otomatis menjadi `SMALLINT`.

#### Berikut adalah contoh cara membuat tabel dan menambahkan kolom:

```php
Schema::table('users', function ($table) {
	$table->create();
	$table->increments('id');
	$table->string('username');
	$table->string('email');
	$table->string('phone')->nullable();
    $table->integer('age')->nullable();
    $table->boolean('married')->default(0);
	$table->text('about');
	$table->timestamps();
});
```

<a id="listing-kolom"></a>

## Listing Kolom

Selain listing tabel, anda juga dapat me-list seluruh kolom milik sebuah tabel:

```php
$columns = Schema::columns('users');

dd($columns);
```

<a id="hapus-kolom"></a>

## Hapus Kolom

#### Menghapus sebuah kolom dari tabel:

```php
$table->drop_column('name');
```

#### Menghapus beberapa kolom dari tabel:

```php
$table->drop_column(['name', 'email']);
```

<a id="tambah-index"></a>

## Tambah Index

Schema builder mendukung beberapa jenis index. Ada 2 cara untuk menambahkan index.
Setiap jenis index mempunyai methodnya sendiri - sendiri. Akan tetapi, anda juga dapat
mendefinisikan index secara langsung dengan menyambungkan method indexing dengan
method untuk penambahan kolom. Contohnya seperti ini:

#### Menyambung method indexing:

```php
$table->string('email')->unique();
```

Namun jika anda lebih suka menambahkan index secara terpisah, anda bisa menuliskannya dengan cara ini:

| Perintah                               | Keterangan                  |
| -------------------------------------- | --------------------------- |
| `$table->primary('id');`               | Menambahkan primary key     |
| `$table->primary(['fname', 'lname']);` | Menambahkan composite key   |
| `$table->unique('email');`             | Menambahkan unique index    |
| `$table->fulltext('description');`     | Menambahkan full-text index |
| `$table->index('city');`               | Menambahkan index standar   |

<a id="hapus-index"></a>

## Hapus Index

Untuk menghapus index, anda hanya perlu menyebutkan nama indexnya saja. Rakit memberikan
nama yang mudah diingat untuk semua index.

Cukup <ins>gabungkan nama tabel dan nama kolom dalam index, lalu tambahkan tipe index di bagian akhir</ins>.
Mari kita lihat beberapa contohnya:

| Command                                                  | Description                                |
| -------------------------------------------------------- | ------------------------------------------ |
| `$table->drop_primary('users_id_primary');`              | Hapus primary key dari tabel "users"       |
| `$table->drop_unique('users_email_unique');`             | Hapus unique index dari tabel "users"      |
| `$table->drop_fulltext('profile_description_fulltext');` | Hapus full-text index dari tabel "profile" |
| `$table->drop_index('geo_city_index');`                  | Hapus index standar dari tabel "geo"       |

<a id="aturan-penamaan-index"></a>

> Ingat! Cara penamaan index: `nama tabel` + `kolom `+ `tipe index`, gabungkan dengan garis bawah.

<a id="foreign-key"></a>

## Foreign Key

Anda juga bisa menambahkan foreign key ke tabel. Contohnya, anggap anda punya
kolom `user_id` pada tabel `posts`, dan tabel tersebut me-reference
kolom `id` pada tabel `users`. Maka, sintaksnya jadi seperti ini:

```php
$table->foreign('user_id')->references('id')->on('users');
```

Anda juga boleh menambahkan opsi `ON DELETE` dan `ON UPDATE` ke foreign key:

```php
$table->foreign('user_id')->references('id')->on('users')->on_delete('restrict');

$table->foreign('user_id')->references('id')->on('users')->on_update('cascade');
```

Untuk menghapus foreign key pun juga sangat mudah. Secara default, aturan penamaan
foreign key ini [mengikuti aturan yang sama](#aturan-penamaan-index) seperti index - index lain.
Contohnya seperti ini:

```php
$table->drop_foreign('posts_user_id_foreign');
```

> Patut diingat bahwa kolom yang di-reference dalam foreign key hampir pasti merupakan auto-increment, maka secara otomatis tipenya adalah `UNSIGNED INTEGER`. Jadi pastikan untuk membuat kolom foreign key dengan method `unsigned()` karena kedua kolomnya harus mempunyai tipe yang sama, dan juga, engine di kedua tabel harus di-set ke `InnoDB`, dan tabel yang di-reference harus dibuat **SEBELUM** si tabel foreign key.

```php
$table->engine = 'InnoDB';

$table->integer('user_id')->unsigned();
```
