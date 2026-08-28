# Daftar Bug Rakit

Hasil audit menyeluruh terhadap framework. Setiap poin di sini **sudah dibuktikan dengan
menjalankan kode**, bukan dari membaca sekilas, dan menyertakan cara mereproduksinya.

- **Cara verifikasi**: SQLite in-memory, MariaDB 10.11 lokal, dan Redis 7 lokal.
- **Mulai diaudit**: 2026-08-28.
- **Aturan main**: centang hanya setelah ada perbaikan **dan** test yang menutupinya.

## Ringkasan

| Tingkat | Total | Selesai | Sisa |
|---|---|---|---|
| [Kritis — keamanan](#kritis--keamanan) | 7 | 0 | 7 |
| [Tinggi — fungsi rusak](#tinggi--fungsi-rusak) | 4 | 0 | 4 |
| [Sedang](#sedang) | 7 | 0 | 7 |
| [Rendah / pengerasan](#rendah--pengerasan) | 10 | 0 | 10 |
| **Total** | **28** | **0** | **28** |

Area yang belum disisir dicatat di [Belum diaudit](#belum-diaudit).

---

## Kritis — keamanan

### K1. CSRF bisa dilewati lewat spoofing method

- [ ] Belum diperbaiki

`Foundation\Http\Request::getMethod()` (`system/foundation/http/request.php:751`) menghormati
`_method` dari body/query dan header `X-Http-Method-Override` **tanpa syarat**. Symfony
menutup ini di balik `enableHttpMethodParameterOverride()` justru karena berbahaya; Rakit
tidak punya gerbang itu.

Akibatnya `Request::forged()` melihat method `GET` untuk request yang sebenarnya `POST`, lalu
keluar lebih awal:

```php
if (in_array(static::method(), ['GET', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'])) {
    return false;   // dianggap tidak dipalsukan
}
```

**Reproduksi** — POST lintas situs ke route yang dilindungi `csrf`, dengan `_method=GET` di
body:

```
POST /hapus-akun            _method=GET&nama=x
-> Request::method() = GET
-> forged()          = false
-> hasil             = AKUN DIHAPUS
```

Sama saja lewat header `X-Http-Method-Override: GET` tanpa mengubah body.

Kena pada route apa pun yang terdaftar untuk banyak method — `Route::any()` dan seluruh route
hasil `Route::controller()` (didaftarkan untuk `*`) — karena controller non-restful memanggil
`action_<nama>` yang sama tanpa peduli method HTTP-nya, sementara body POST tetap terbaca
`Input::get()`.

**Saran**: matikan override secara default dengan opsi konfigurasi untuk menyalakannya, dan
bagaimanapun juga jadikan `Request::forged()` memakai `REQUEST_METHOD` mentah.

### K2. Middleware yang tidak terdaftar dilewati diam-diam (fail open)

- [ ] Belum diperbaiki

`Middleware::run()` (`system/routing/middleware.php:94`):

```php
if (! isset(static::$middlewares[$middleware])) {
    continue;
}
```

Salah ketik nama middleware, atau package yang belum ter-boot saat route dijalankan, membuat
route **berjalan tanpa proteksi apa pun dan tanpa error**.

**Reproduksi**:

```php
Route::get('rahasia', ['before' => 'atuh', function () { return 'RAHASIA BOCOR'; }]);
// -> 'RAHASIA BOCOR', bukan 401
```

**Saran**: lempar exception untuk middleware yang tidak dikenal, seperti Laravel.

### K3. Middleware grup luar hilang di grup bersarang

- [ ] Belum diperbaiki

`Router::merge_groups()` (`system/routing/router.php:251`) memakai `array_merge()` di seluruh
tumpukan grup, jadi grup dalam **mengganti** atribut grup luar alih-alih menggabungkannya.

**Reproduksi**:

```php
Route::group(['prefix' => 'admin', 'before' => 'auth'], function () {
    Route::get('dashboard', ...);
    Route::group(['prefix' => 'users', 'before' => 'admin'], function () {
        Route::get('list', ...);
    });
});
```

menghasilkan:

```
'admin/dashboard'  => before='auth'
'users/list'       => before='admin'      <- seharusnya 'admin/users/list', before 'auth|admin'
```

Dua masalah sekaligus: URI-nya salah **dan** middleware grup luar hilang, jadi route dalam
kehilangan proteksi yang dikira sudah terpasang. Ini pola yang sangat umum
(`group(['before' => 'auth'])` membungkus `group(['prefix' => ..])`).

**Saran**: sambung `prefix` dan `domain` secara berjenjang, gabungkan `before`/`after` alih-alih
menimpanya.

### K4. `Input::all()` dan `Input::get()` memilih sumber yang berbeda

- [ ] Belum diperbaiki

`Input::all()` (`system/input.php:30`) memakai `array_merge($post, $query, $files)` sehingga
**query string menang**, sementara `Input::get($key)` membaca body lebih dulu dan baru jatuh ke
query, sehingga **body yang menang**. `Input::only()` ikut memakai `all()`.

Karena `Controller::validate()` dan pola umum di dokumentasi memvalidasi `Input::all()` lalu
membaca nilainya dengan `Input::get()`, keduanya bisa melihat nilai yang berbeda pada request
yang sama — **yang divalidasi bukan yang dipakai**.

**Reproduksi**:

```
POST /simpan?peran=user      body: peran=admin

Validator::make(Input::all(), ['peran' => 'required|in:user,tamu'])->passes()  => true
Input::all()['peran']                                                          => 'user'
Input::only(['peran'])['peran']                                                => 'user'
Input::get('peran')                                                            => 'admin'
```

**Saran**: satu urutan prioritas untuk semua pembaca input. Laravel memakai `body + query`
(body menang); ikuti itu di `all()`, `only()`, `get()` dan `has()`.

### K5. SQL injection lewat operator `where()`

- [ ] Belum diperbaiki

`Query\Grammars\Grammar::where_basic()` (`system/database/query/grammars/grammar.php:202`)
menyambung `$where['operator']` mentah-mentah ke SQL. Nilainya memang di-bind, operatornya
tidak divalidasi sama sekali.

**Reproduksi**:

```php
$op = '> 0 OR 1=1 AND "id" >';
DB::table('users')->where('id', $op, 999999)->get();
// SELECT * FROM "users" WHERE "id" > 0 OR 1=1 AND "id" > ?
// mengembalikan SELURUH baris, bukan nol
```

**Saran**: validasi operator terhadap daftar putih dan lempar exception untuk yang tidak
dikenal, seperti `Builder::invalidOperator()` di Laravel.

### K6. SQL injection lewat arah `order_by()`

- [ ] Belum diperbaiki

`Query\Grammars\Grammar::orderings()` (`system/database/query/grammars/grammar.php:421`) hanya
meng-`strtoupper()` arah pengurutan lalu menyambungnya ke SQL.

**Reproduksi**:

```php
DB::table('users')->order_by('id', 'ASC, (SELECT password FROM users LIMIT 1)')->get();
// SELECT * FROM "users" ORDER BY "id" COLLATE NOCASE ASC, (SELECT PASSWORD FROM USERS LIMIT 1)
// dieksekusi tanpa error
```

Arah pengurutan hampir selalu datang dari query string (`?sort=nama&dir=asc`), jadi ini lebih
mudah dijangkau penyerang daripada K5. Nama kolomnya sendiri aman karena lewat `wrap()`.

**Saran**: terima hanya `asc`/`desc` (case-insensitive), lempar exception untuk selain itu.

### K7. Rate limit bisa dilewati lewat header `CF-Connecting-IP`

- [ ] Belum diperbaiki

`Throttle::client()` (`system/routing/throttle.php:58`):

```php
return Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip();
```

`CF-Connecting-IP` adalah header kiriman klien. Selama aplikasi tidak benar-benar di belakang
Cloudflare (dan memverifikasinya), penyerang cukup mengirim header itu dengan nilai acak setiap
request untuk mendapat ember hitungan baru, sehingga throttle tidak pernah kena.
`Request::ip()` sendiri aman karena lewat mekanisme trusted proxy Symfony.

**Saran**: hanya percaya header proxy kalau IP asal ada di daftar proxy tepercaya, dan jadikan
itu konfigurasi, bukan hardcode satu vendor.

---

## Tinggi — fungsi rusak

### T1. Throttle melempar exception di driver cache redis

- [ ] Belum diperbaiki

`Throttle::check()` menyimpan hitungan awal dengan `Cache::put($key, 1, ..)`, dan driver redis
menyimpannya sebagai `serialize(1)` = `"i:1;"`. Request berikutnya memanggil `Cache::increment()`
yang menjalankan `INCR` di atas string itu.

**Reproduksi** (Redis hidup):

```
put(1) lalu increment:
  EX: Exception: Redis error:  value is not an integer or out of range
```

Route mana pun yang memakai `throttle:60,1` **melempar 500 pada request kedua di dalam jendela
yang sama**, bukan membatasi. Bergantung pada T2.

### T2. `Cache::get()` merusak nilai hasil `Cache::increment()` di redis

- [ ] Belum diperbaiki

`Cache\Drivers\Redis::put()` menyimpan `serialize($value)` sementara `increment()`
(`system/cache/drivers/redis.php:104`) memakai `INCR` yang menulis integer mentah. Saat dibaca
kembali, `retrieve()` gagal meng-`unserialize`, lalu **menghapus kuncinya**.

**Reproduksi** (Redis hidup):

```
increment #1 = 1
increment #2 = 2
get()        = NULL        <- seharusnya 2
increment #3 = 1           <- penghitungnya ter-reset
```

Semua penghitung berbasis cache (view counter, rate limit, kuota) rusak dan datanya hilang di
driver redis. Driver apc dan memcached tidak kena karena keduanya menyimpan nilai mentah;
driver file dan database aman karena `increment()`-nya lewat `put()` yang sama.

### T3. `URL::to_route()` membocorkan kunci internal untuk route ber-domain

- [ ] Belum diperbaiki

Route yang didaftarkan di dalam `Route::domain()` / `Route::group(['domain' => ..])` disimpan
dengan kunci gabungan `domain||uri`. `URL::explicit()` dan `URL::to_route()`
(`system/url.php:119` dan `:172`) memakai `key($route)` apa adanya.

**Reproduksi**:

```php
Route::group(['domain' => 'admin.contoh.test'], function () {
    Route::get('panel', ['as' => 'panel', function () {}]);
});

URL::to_route('panel');   // http://situs/index.php/admin.contoh.test||panel
```

Seluruh URL bernama untuk route ber-domain rusak, termasuk lewat `Redirect::to_route()`,
`URL::to_action()` dan `@route` di view.

### T4. Aturan `size`/`min`/`max`/`between` salah menghitung array

- [ ] Belum diperbaiki

`Validator::size()` (`system/validator.php:976`) hanya menangani numerik dan berkas upload;
selain itu jatuh ke `Str::length(trim((string) $value))`. Untuk array, itu berarti PHP warning
**Array to string conversion** dan panjang string `"Array"`, yaitu 5.

**Reproduksi**:

```
[1,2,3]        array|size:3        => gagal   (harusnya lolos)
[1,2,3]        array|min:1         => LOLOS   (kebetulan, 5 >= 1)
[1,2,3,4,5,6]  array|max:2         => gagal   (kebetulan benar, alasannya salah)
[1,2]          array|between:1,3   => gagal   (harusnya lolos)
```

Semua disertai `PHP Warning: Array to string conversion in system/validator.php on line 986`.

**Saran**: kembalikan `count($value)` untuk array dan `Countable`, seperti Laravel.

---

## Sedang

### S1. Driver cache redis mengabaikan prefix `cache.key`

- [ ] Belum diperbaiki

Driver apc, memcached, database dan file semuanya memakai `$this->key.$key`. Driver redis
memakai `$key` mentah di `has()`, `retrieve()`, `put()`, `increment()` dan `forget()`. Dua
aplikasi yang berbagi satu database Redis akan saling menimpa cache.

### S2. `Cache\Drivers\Redis::flush()` menjalankan `flushdb()`

- [ ] Belum diperbaiki

`system/cache/drivers/redis.php:139`. `flushdb()` menghapus **seluruh** database Redis, bukan
hanya kunci milik cache. Kalau session atau antrian job memakai database Redis yang sama,
semuanya ikut hilang. Driver lain hanya menghapus kunci yang berprefix.

### S3. `Cache\Drivers\Database::increment()` tanpa kunci baris

- [ ] Belum diperbaiki

`system/cache/drivers/database.php:89` membungkus baca-ubah-tulis dalam transaksi tapi
`SELECT`-nya tidak mengunci baris. Di MySQL dengan REPEATABLE READ, dua transaksi paralel
membaca nilai yang sama dan sama-sama menulis N+1 (*lost update*). Framework sudah punya
`lock_for_update()`, tinggal dipakai.

### S4. `Cache\Drivers\File::increment()` tidak atomik

- [ ] Belum diperbaiki

Driver file memakai `Driver::increment()` bawaan yang baca-tambah-tulis tanpa penguncian.
Karena `file` adalah driver cache **default**, rate limiter bawaan pun tidak atomik.

### S5. `required` meloloskan array kosong

- [ ] Belum diperbaiki

`Validator::validate_required()` (`system/validator.php:254`) hanya menolak `null`, string
kosong, dan berkas upload kosong. Laravel juga menolak array/`Countable` yang kosong.

**Reproduksi**: `Validator::make(['tags' => []], ['tags' => 'required'])->passes()` => `true`.

Form dengan sekumpulan checkbox yang wajib diisi akan lolos meski tidak ada yang dicentang.

### S6. `date_format` tidak ketat

- [ ] Belum diperbaiki

`Validator::validate_date_format()` (`system/validator.php:1915`) hanya memeriksa
`date_create_from_format()` tidak mengembalikan `false`, padahal parser PHP itu longgar.

**Reproduksi**: `date_format:Y-m-d` meloloskan `2026-1-1`.

**Saran**: bandingkan `$date->format($format) === $value` seperti Laravel.

### S7. `Container::instance()` tidak terlihat oleh `Container::registered()`

- [ ] Belum diperbaiki

`instance()` menulis ke `static::$singletons`, sedangkan `registered()`
(`system/container.php:43`) hanya memeriksa `static::$registry`.

**Reproduksi**:

```php
Container::instance('layanan', new stdClass());
Container::registered('layanan');   // false
Container::resolve('layanan');      // jalan, mengembalikan objeknya
```

Semua kode yang bergerbang pada `registered()` melewatkan instance semacam ini, termasuk
`Controller::__get()` yang jadi mengembalikan `null` untuk layanan yang sebenarnya terdaftar.

---

## Rendah / pengerasan

### R1. Kunci aplikasi efektif hanya 112 bit

- [ ] Belum diperbaiki

`system/init.php` menghasilkan kunci berbentuk UUID (36 karakter). `openssl_encrypt()` dengan
`aes-256-cbc` memotongnya ke 32 byte pertama, yang berisi 28 digit heksa dan 4 tanda hubung —
jadi 112 bit, bukan 256. Masih di atas ambang yang bisa dibobol, tapi tidak perlu.

**Saran**: simpan 32 byte acak ter-base64 dan dekode sebelum dipakai, seperti Laravel.

### R2. Aturan validasi `image` menerima SVG

- [ ] Belum diperbaiki

`Validator::validate_image()` (`system/validator.php:1168`) memasukkan `svg` ke daftar yang
diizinkan. SVG bisa memuat JavaScript, jadi menyajikannya dari origin yang sama adalah XSS
tersimpan. Laravel versi baru mengeluarkan SVG dari default.

### R3. Facile tidak menjaga mass-assignment secara default

- [ ] Belum diperbaiki

`Model::$guarded = []` dan `Model::$fillable = null` (`system/database/facile/model.php:90`
dan `:97`), jadi **semua kolom bisa diisi massal** kecuali model menyatakan sebaliknya. Laravel
memakai `$guarded = ['*']` supaya aman secara default. `Model::create(Input::all())` di Rakit
membiarkan penyerang mengisi `id`, `is_admin`, dan kolom apa pun yang ada.

### R4. `Redirect::back()` mengikuti header `Referer`

- [ ] Belum diperbaiki

`Request::referrer()` dikendalikan penyerang, dan `URL::to()` mengembalikan URL absolut apa
adanya, jadi `Redirect::back()` bisa diarahkan ke situs luar. Laravel berperilaku sama, jadi
ini pengerasan, bukan penyimpangan.

### R5. `Route::forward()` fatal kalau route tidak ada

- [ ] Belum diperbaiki

`system/routing/route.php:414`. `Router::route()` bisa mengembalikan `null`, dan `forward()`
langsung memanggil `->call()` di atasnya.

### R6. Penggantian placeholder `Lang` saling menimpa

- [ ] Belum diperbaiki

`Lang::get()` (`system/lang.php:116`) mengganti placeholder sesuai urutan array, jadi
placeholder yang namanya awalan dari placeholder lain merusaknya.

**Reproduksi**:

```php
// 'Halo :nama, selamat datang :nama_lengkap'
Lang::line('probe.halo', ['nama' => 'Budi', 'nama_lengkap' => 'Budi Purnomo'])->get();
// => 'Halo Budi, selamat datang Budi_lengkap'
```

**Saran**: urutkan penggantian dari nama terpanjang ke terpendek, seperti Laravel.

### R7. `Input::json(true)` mengubah list menjadi objek berkunci angka

- [ ] Belum diperbaiki

`system/input.php:118` memakai `JSON_FORCE_OBJECT`, jadi `{"tags":[1,2,3]}` terbaca sebagai
`tags => {"0":1,"1":2,"2":3}` alih-alih array biasa.

### R8. `Cookie::get()` melempar untuk nama cookie yang sah

- [ ] Belum diperbaiki

Pemeriksaan namanya `/^[a-zA-Z0-9_-]+$/`, padahal titik sah di nama cookie HTTP. `Cookie::has()`
memanggil `get()`, jadi memeriksa cookie milik aplikasi lain di domain yang sama melempar
exception alih-alih mengembalikan `false`.

### R9. `e()` memakai `double_encode = false`

- [ ] Belum diperbaiki

`htmlentities($value, ENT_QUOTES, 'UTF-8', false)` membiarkan entitas yang sudah ada. Bukan
lubang XSS (karakter `<`, `>`, `"` tetap di-escape), tapi berbeda dari default Laravel dan
membuat keluaran sulit ditalar saat data sudah mengandung entitas.

### R10. `Container::resolve()` bisa rekursi tanpa henti

- [ ] Belum diperbaiki

Alias yang saling menunjuk (`register('a', 'b')` dan `register('b', 'a')`) atau dua kelas yang
konstruktornya saling membutuhkan membuat `resolve()` memanggil dirinya terus sampai stack
habis. Laravel mendeteksi ini lewat `buildStack` dan melempar `CircularDependencyException`.

---

## Belum diaudit

Bagian framework yang belum disisir pada putaran ini:

- Blade dan View di luar pemeriksaan escaping
- Paginator di luar pemeriksaan batas dasar
- Facile: relasi, eager loading, soft delete (test-nya sudah tebal)
- Validator di luar aturan yang disebut di atas
- `Collection`, `Str`, `Arr`, `Carbon`
- Console command selain migrasi
- `Email`, WebSocket, `Curl`, `Image`, `Markdown`, `Log`
- `Package`, `Autoloader`, `Config`
- Driver job redis dan memcached
- `JWT`, `RSA`, `Storage`
