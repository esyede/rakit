# Redis

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Konfigurasi](#konfigurasi)
- [Cara Penggunaan](#cara-penggunaan)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Redis adalah perangkat lunak penyimpanan key-value bersumber terbuka dan canggih. Ini sering disebut sebagai server struktur data karena key-nya dapat berisi [string](https://redis.io/topics/data-types#strings), [hash](https://redis.io/topics/data-types#hashes), [list](https://redis.io/topics/data-types#lists), [set](https://redis.io/topics/data-types#sets), dan [sorted set](https://redis.io/topics/data-types#sorted-sets).


<a id="konfigurasi"></a>
## Konfigurasi

Konfigurasi Redis database berada di file `application/config/database.php`. Di dalam file ini, anda akan melihat array `'redis'` yang berisi server Redis yang digunakan oleh aplikasi anda:

```php
'redis' => [

	'default' => ['host' => '127.0.0.1', 'port' => 6379],

],
```

Konfigurasi `'default'` diatas biasanya sudah cukup untuk pengembangan. Namun, anda bebas mengubah array ini sesuai environment yang anda miliki. Cukup beri nama setiap konfigurasi servernya, dan tentukan host serta port yang digunakan oleh server.


<a id="cara-penggunaan"></a>
## Cara Penggunaan

Anda bisa mendapatkan instance Redis dengan memanggil method `db()` seperti ini:

```php
$redis = Redis::db();
```

Ini akan memberi anda instance dari server `'default'`. Anda juga dapat mengoper nama server lain ke method `db()` untuk mengambil instance server tersebut sesuai dengan yang telah anda tentukan di file konfigurasi:

```php
$redis = Redis::db('redis_2');
```

Mantap! sekarang anda sudah punya instance redisnya, ini berarti anda sudah bisa menjalankan [perintah redis](https://redis.io/commands) apa pun yang anda mau. Rakit menggunakan magic method untuk mengoper perintah tesebut ke Redis server:

```php
$redis->set('name', 'Budi');

$name = $redis->get('name');

$values = $redis->lrange('names', 5, 10);
```

Perhatikan bahwa argumen perintah milik redis dipaggil sebagai nama method. Tentu saja, anda tidak diharuskan menggunakan magic method ini, anda juga dapat mengirimkan perintah ke server menggunakan method `run()` seperti berikut:

```php
$values = $redis->run('lrange', [5, 10]);
```

Hanya ingin menjalankan perintah di server redis default? Cukup gunakan magic method saja:

```php
Redis::set('name', 'Budi');

$name = Redis::get('name');

$values = Redis::lrange('names', 5, 10);
```

>  Rakit juga telah menyediakan driver redis untuk [cache](/docs/cache/config#redis) dan [session](/docs/session/config#redis).
