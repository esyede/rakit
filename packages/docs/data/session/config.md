# Konfigurasi Session

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Driver Cookie](#driver-cookie)
- [Driver File](#driver-file)
- [Driver Database](#driver-database)
    - [Console](#console)
    - [SQLite](#sqlite)
    - [MySQL](#mysql)
- [Driver Memcached](#driver-memcached)
- [Driver Redis](#driver-redis)
- [Driver Memori](#driver-memori)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Web adalah lingkungan yang bersifat _state-less_. Ini berarti bahwa setiap request ke aplikasi anda dianggap tidak terkait dengan request sebelumnya. Namun, session memungkinkan anda menyimpan data secara statis untuk setiap pengunjung aplikasi anda. Data session untuk setiap pengunjung disimpan di server web anda, sedangkan cookie yang berisi "Session ID" disimpan di perangkat pengunjung. Cookie ini memungkinkan aplikasi anda untuk "mengingat" sesi untuk pengguna tersebut dan mengambil data sesi mereka pada request berikutnya ke aplikasi anda.

>  Sebelum menggunakan session, anda harus terlebih dahulu mengisi "application key" di file `application/config/application.php`.

Secara default, telah disediakan enam buah driver untuk session, yaitu:

- Cookie
- Filesystem
- Database
- Memcached
- Redis
- Memori (Array)


<a id="driver-cookie"></a>
## Driver Cookie

Session berbasis cookie menyediakan mekanisme yang ringan dan cepat untuk menyimpan data session. Mereka juga aman. Setiap cookie dienkripsi menggunakan enkripsi AES-256 yang kuat. Namun, cookie memiliki batas penyimpanan `4 kilobyte`, jadi anda mungkin akanperlu menggunakan driver lain jika anda ingin menyimpan banyak data dalam sesi.

Untuk mulai driver cookie ini, cukup ubah opsi drivernya di file `application/config/session.php` seperti berikut:

```php
'driver' => 'cookie'
```


<a id="driver-file"></a>
## Driver File

Kemungkinan besar, aplikasi anda akan bekerja dengan cukup baik hanya dengan menggunakan driver file ini. Namun, jika aplikasi anda menerima lalu lintas yang sangat padat, gunakan driver database atau  memcache.

Untuk mulai driver file ini, cukup ubah opsi drivernya di file `application/config/session.php` seperti berikut:

```php
'driver' => 'file'
```

Dan, session sudah siap digunakan!

>  Ketika menggunakan driver ini, data session akan disimpan di folder `storage/sessions/` sebagai file, jadi pastikan direktori tersebut dapat ditulisi.


<a id="driver-database"></a>
## Driver Database

Untuk menggunakan driver database, anda harus [mengkonfigurasi koneksi database](/docs/database/config) terlebih dahulu.

Selanjutnya, anda perlu membuat sebuah tabel sesi. Berikut adalah beberapa kueri SQL untuk membantu anda memulai. Namun, anda juga dapat menggunakan [console](/docs/console) untuk membuat tabel ini secara otomatis!


<a id="console"></a>
### Console

```bash
php rakit session:table
```


<a id="sqlite"></a>
### SQLite

```sql
CREATE TABLE "sessions" (
	"id" VARCHAR PRIMARY KEY NOT NULL UNIQUE,
	"last_activity" INTEGER NOT NULL,
	"data" TEXT NOT NULL
);
```


<a id="mysql"></a>
### MySQL
```sql
CREATE TABLE `sessions` (
	`id` VARCHAR(40) NOT NULL,
	`last_activity` INT(10) NOT NULL,
	`data` TEXT NOT NULL,
	PRIMARY KEY (`id`)
);
```

Jika anda ingin menggunakan nama tabel lain, cukup ubah opsi `'table'` di file `application/config/session.php` seperti berikut:

```php
'table' => 'sessions'
```

Dan yang terakhir, anda hanya tinggal mengubah opsi driver di file `application/config/session.php` seperti berikut:

```php
'driver' => 'database'
```


<a id="driver-memcached"></a>
## Driver Memcached

Sebelum menggunakan driver memcached, anda harus [mengkonfigurasi server memcached anda](https://github.com/memcached/memcached/wiki/ConfiguringServer) terlebih dahulu.

Setelah itu, anda hanya tinggal mengubah opsi driver di file `application/config/session.php` seperti berikut:

```php
'driver' => 'memcached'
```


<a id="driver-redis"></a>
## Driver Redis

Sebelum menggunakan driver redis, anda harus [mengkonfigurasi server redis anda](/docs/database/redis#config) terlebih dahulu.

Setelah itu, anda hanya tinggal mengubah opsi driver di file `application/config/session.php` seperti berikut:

```php
'driver' => 'redis'
```


<a id="driver-memori"></a>
## Driver Memori

Driver `'memory'` hanya menggunakan array sederhana untuk menyimpan data sesi anda pada request saat ini. Driver ini baik digunakan untuk unit-testing aplikasi anda karena tidak ada data apapun yang ditulis ke disk.

>  Driver ini tidak boleh digunakan untuk keperluan selain testing!

