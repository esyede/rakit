# Konfigurasi Cache

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Driver Database](#driver-database)
- [Driver Memcached](#driver-memcached)
- [Driver Redis](#driver-redis)
- [Driver Memori](#driver-memori)
- [Cache Key](#cache-key)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Bayangkan aplikasi anda menampilkan sepuluh lagu paling populer yang dipilih oleh user. Apakah anda benar-benar perlu mencari sepuluh lagu ini setiap kali seseorang mengunjungi situs anda? Bagaimana jika anda dapat menyimpannya selama 10 menit, atau bahkan satu jam, memungkinkan anda mempercepat aplikasi anda secara dramatis? library caching ini bisa melakukannya.

Secara default, telah disediakan 5 buah cache driver:

- File
- Database
- Memcached
- APC
- Redis
- Memory (Array)

Secara default, Rakit dikonfigurasi untuk menggunakan driver cache `'file'`. Inni membuatnya siap digunakan tanpa konfigurasi tambahan. Driver ini menyimpan item yang di-cache sebagai file di direktori `storage/cache/`. Jika anda sudah merasa cukup dengan driver ini, tidak diperlukan konfigurasi lain. Anda langsung siap untuk mulai menggunakannya.

>  Sebelum menggunakan driver cache `'file'`, pastikan direktori `storage/cache/` anda dapat ditulisi.


<a id="driver-database"></a>
## Driver Database

Driver cache `'database'` menggunakan tabel database yang sebagai penyimpanan key dan value cache. Untuk memulai, pertama-tama tentukanlah nama tabel database di `application/config/cache.php`:

```php
'database' => ['table' => 'rakit_cache']),
```

Selanjutnya, buatlah tabel tersebut di database anda. Tabel tersebut harus memiliki tiga kolom:

```php
key        - VARCHAR
value      - TEXT
expiration - INTEGER
```

Mantap! Setelah config dan tabel anda telah dikonfigurasi, anda siap untuk mulai melakukan caching!


<a id="driver-memcached"></a>
## Driver Memcached

[Memcached](http://memcached.org) adalah sistem cache objek memori terdistribusi yang sangat cepat dan bersumber terbuka. Sebelum menggunakan driver Memcached ini, anda perlu menginstal dan mengkonfigurasi Memcached dan ekstensi Memcache PHP di server anda.

Setelah Memcache terinstal di server, anda harus mengatur 'driver' di file `application/config/cache.php`:

```php
'driver' => 'memcached'
```

Kemudian, tambahkan Memcached server anda ke array `'servers'`:

```php
'servers' => [

	['host' => '127.0.0.1', 'port' => 11211, 'weight' => 100],

],
```


<a id="driver-redis"></a>
## Driver Redis

Redis adalah perangkat lunak penyimpanan key-value bersumber terbuka dan canggih. Ini sering disebut sebagai server struktur data karena key-nya dapat berisi [string](http://redis.io/topics/data-types#strings), [hash](http://redis.io/topics/data-types#hashes), [list](http://redis.io/topics/data-types#lists), [set](http://redis.io/topics/data-types#sets), dan [sorted set](http://redis.io/topics/data-types#sorted-sets).

Sebelum menggunakan driver Redis ini, anda harus [mengkonfigurasi Redis server anda](/docs/database/redis#config). Setelah itu, anda cukup megubah `'driver'` di file `application/config/cache.php` menjadi redis seperti ini:

```php
'driver' => 'redis'
```


<a id="driver-memori"></a>
## Driver Memori

Driver cache `'memory'` sebenarnya tidak menyimpan apa pun ke disk. Ia hanya mempertahankan array internal dari data cache untuk request saat ini. Ini membuatnya berguna ketika anda melakukan unit-testing aplikasi anda dalam isolasi dari mekanisme penyimpanan apa pun. Driver ini **tidak boleh** digunakan di server produksi!


<a id="cache-key"></a>
## Cache Key

Untuk menghindari benturan penamaan dengan aplikasi lain yang menggunakan APC, Redis, atau Memcached, Rakit menambahkan akhiran _'key'_ ke setiap item yang disimpan dalam cache menggunakan driver ini. Jangan ragu untuk mengubah ini:

```php
'key' => 'rakit'
```
