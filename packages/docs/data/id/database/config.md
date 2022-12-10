# Konfigurasi Database

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Mulai Cepat Dengan SQLite](#mulai-cepat-dengan-sqlite)
-   [Menggunakan Database Lain](#menggunakan-database-lain)
-   [Mengatur Koneksi Default](#mengatur-koneksi-default)
-   [Menimpa Opsi PDO Default](#menimpa-opsi-pdo-default)

<!-- /MarkdownTOC -->

Rakit mendukung database berikut secara default:

-   MySQL
-   PostgreSQL
-   SQLite
-   SQL Server

Seluruh opsi konfigurasi database berada di file `application/config/database.php`.

<a id="mulai-cepat-dengan-sqlite"></a>

## Mulai Cepat Dengan SQLite

[SQLite](https://sqlite.org) adalah salah satu sistem database yang bagus, konfigurasinya pun tidak rumit.
Pada keadaan default, Rakit dikonfigurasikan untuk menggunakan SQLite. Benar, tujuannya agar anda
bisa langsung mencoba Rakit tanpa harus ribet setting database.

Rakit akan secara default menyimpan seluruh file sqlite kedalam folder `application/storage/database/`
dengan nama `'xxxxxx-application'` dimana `xxxxxx` adalah 32 karakter acak yang otomatis ditambahkan
ke depan nama database asli anda untuk alasan keamanan.

Tentu saja, anda boleh menamainya dengan nama selain `'application'`, untuk melakukannya,
cukup ubah opsi konfigurasi di file `application/config/database.php` seperti ini:

```php
'sqlite' => [
	'driver'   => 'sqlite',
	'database' => 'nama_database_anda',
],
```

Jika aplikasi anda menerima kurang dari 100.000 kunjungan per hari, SQLite cukup mampu untuk menanganinya.
Tetapi jika sebaliknya, silahkan gunakan MySQL atau PostgreSQL.

<a id="menggunakan-database-lain"></a>

## Menggunakan Database Lain

Jika anda menggunakan MySQL, SQL Server, atau PostgreSQL, anda perlu mengubah opsi konfigurasi
di `application/config/database.php` tadi. Di dalam file tersebut, anda dapat menemukan sampel
konfigurasi untuk tiap - tiap sistem database.

Cukup ubah sesuai kebutuhan anda dan jangan lupa untuk mengatur koneksi defaultnya.

<a id="mengatur-koneksi-default"></a>

## Mengatur Koneksi Default

Seperti yang telah anda perhatikan, setiap koneksi database yang diatur dalam
file `application/config/database.php` memiliki nama koneksi.

Secara default, ada empat buah koneksi yang didefinisikan: `sqlite`, `mysql`, `sqlsrv`, dan `pgsql`.
Anda bebas mengubah nama koneksi ini. Koneksi default dapat diatur melalui opsi `'default'` seperti berikut:

```php
'default' => 'sqlite';
```

Koneksi default inilah yang akan selalu digunakan oleh [Query Builder](/docs/id/database/magic).
Jika anda perlu mengubah koneksi default saat request berlangsung, gunakan `Config::set()`.

<a id="menimpa-opsi-pdo-default"></a>

## Menimpa Opsi PDO Default

Komponen konektor database (`System\Database\Connector`) memiliki seperangkat definisi atribut PDO
yang dapat ditimpa via file konfigurasi.

Sebagai contoh, salah satu atribut defaultnya adalah memaksa nama kolom menjadi
huruf kecil (`PDO::CASE_LOWER`) bahkan jika mereka didefinisikan dalam UPPERCASE atau camelCase di tabel.

Oleh karena itu, pada keadaan default, object model hasil kueri hanya dapat diakses menggunakan huruf kecil.

Contoh pengaturan sistem database MySQL dengan menambahkan atribut PDO default:

```php
'mysql' => [
	'driver'   => 'mysql',
	'host'     => 'localhost',
	'database' => 'database',
	'username' => 'root',
	'password' => '',
	'charset'  => 'utf8',
	'prefix'   => '',

	PDO::ATTR_CASE              => PDO::CASE_LOWER,
	PDO::ATTR_ERRMODE           => PDO::ERRMODE_EXCEPTION,
	PDO::ATTR_ORACLE_NULLS      => PDO::NULL_NATURAL,
	PDO::ATTR_STRINGIFY_FETCHES => false,
	PDO::ATTR_EMULATE_PREPARES  => false,
],
```

Info lebih lanjut tentang atribut koneksi PDO dapat ditemukan di [dokumentasi resminya](http://php.net/manual/en/pdo.setattribute.php).
