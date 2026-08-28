# Daftar Bug Rakit

Hasil audit menyeluruh terhadap framework. Setiap poin di sini **sudah dibuktikan dengan
menjalankan kode**, bukan dari membaca sekilas, dan menyertakan cara mereproduksinya.

- **Cara verifikasi**: SQLite in-memory, MariaDB 10.11 lokal, dan Redis 7 lokal.
- **Mulai diaudit**: 2026-08-28.
- **Putaran kedua** (area yang semula ditandai belum diaudit): 2026-08-28.
- **Putaran ketiga** (RSA dan Autoloader): 2026-08-28.
- **Putaran keempat** (Facile, Carbon, Image, memcached, console): 2026-08-28.
- **Putaran kelima** (Image, Curl, WebSocket — menutup daftar): 2026-08-28.
- **Putaran keenam** (Blade, Foundation HTTP, Debugger — menyisir yang paling dangkal): 2026-08-28.
- **Aturan main**: centang hanya setelah ada perbaikan **dan** test yang menutupinya.
- **Test regresi**: `tests/cases/regression.test.php`, nama methodnya mengikuti id di bawah,
  jadi kegagalan langsung menunjuk ke poin yang menjelaskan apa yang salah. Test yang tidak
  muat di sana ada di berkas subjeknya masing-masing (`routing-extras`, `redirect`).

## Ringkasan

| Tingkat | Total | Selesai | Sisa |
|---|---|---|---|
| [Kritis — keamanan](#kritis--keamanan) | 9 | 9 | 0 |
| [Tinggi — fungsi rusak](#tinggi--fungsi-rusak) | 18 | 18 | 0 |
| [Sedang](#sedang) | 16 | 16 | 0 |
| [Rendah / pengerasan](#rendah--pengerasan) | 11 | 11 | 0 |
| **Total** | **54** | **54** | **0** |

Cakupan test naik dari 2064 menjadi **2142 test**, semuanya lolos.

Tidak ada lagi bagian yang menunggu giliran.

Suite juga dijalankan di PHP 7.1, 7.4, 8.0, 8.2, 8.3, 8.4 dan 8.5. PHP 5.4–7.0 hanya
diperiksa sampai tingkat sintaks (`php -l` atas seluruh berkas yang berubah), karena `vendor/`
di mesin ini terpasang untuk PHP 8 dan tidak bisa dimuat di sana; matriks penuhnya dijalankan
CI.

Area yang belum disisir dicatat di [Belum diaudit](#belum-diaudit).

---

## Kritis — keamanan

### K1. CSRF bisa dilewati lewat spoofing method

- [x] Selesai

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

**Yang dikerjakan**: `Foundation\Http\Request::getRealMethod()` dan `Request::real_method()`
mengembalikan method yang dilaporkan server, tanpa peduli spoofing, dan `forged()` memakainya
untuk memutuskan. Spoofing tetap bekerja untuk routing, jadi `@method('PUT')` pada form resource
tidak berubah; yang berubah hanya POST yang menyamar sebagai method aman, yang kini tetap
diminta token.

### K2. Middleware yang tidak terdaftar dilewati diam-diam (fail open)

- [x] Selesai

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

**Yang dikerjakan**: `Middleware::run()` melempar `Undefined middleware: <nama>`. Middleware
global (`before`, `after`, dan pasangan berprefix package) memang konvensi dan boleh tidak ada,
jadi keduanya dipisah ke koleksi tersendiri yang ditandai `Middlewares::$optional`. Semua yang
diminta route atau controller diperlakukan ketat.

### K3. Middleware grup luar hilang di grup bersarang

- [x] Selesai

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

**Yang dikerjakan**: `Router::merge_groups()` menyambung `prefix` berjenjang lewat
`join_prefix()` dan menggabungkan `before`/`after` lewat `join_middlewares()` (duplikat dibuang).
Atribut lain tetap ditimpa grup terdalam, karena itu yang diharapkan untuk `domain` dan `as`.

### K4. `Input::all()` dan `Input::get()` memilih sumber yang berbeda

- [x] Selesai

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

**Yang dikerjakan**: `Input::get()` tanpa kunci mengembalikan `array_merge($query, $body)`
sehingga body yang menang, dan `all()` dibangun dari situ. Karena `only()`, `except()`, `has()`
dan `flash()` semuanya lewat `get()`, tidak ada lagi yang bisa berbeda pendapat.

### K5. SQL injection lewat operator `where()`

- [x] Selesai

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

**Yang dikerjakan**: `Query::validate_operator()` mencocokkan operator dengan daftar
`Query::$operators` yang sudah ada dan melempar `InvalidArgumentException` untuk selain itu.
Dipakai `where()` dan `having()`. `Query\Join::on()` punya daftarnya sendiri, karena klausa
join hanya masuk akal dengan operator pembanding.

### K6. SQL injection lewat arah `order_by()`

- [x] Selesai

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

**Yang dikerjakan**: `Query::order_by()` menormalkan arah ke huruf kecil dan melempar
`InvalidArgumentException` untuk selain `asc`/`desc`. `order_by_raw()` tetap apa adanya, karena
memang itu gunanya.

### K7. Rate limit bisa dilewati lewat header `CF-Connecting-IP`

- [x] Selesai

`Throttle::client()` (`system/routing/throttle.php:58`):

```php
return Request::server('HTTP_CF_CONNECTING_IP') ?: Request::ip();
```

`CF-Connecting-IP` adalah header kiriman klien. Selama aplikasi tidak benar-benar di belakang
Cloudflare (dan memverifikasinya), penyerang cukup mengirim header itu dengan nilai acak setiap
request untuk mendapat ember hitungan baru, sehingga throttle tidak pernah kena.
`Request::ip()` sendiri aman karena lewat mekanisme trusted proxy Symfony.

**Yang dikerjakan**: `Throttle::client()` hanya membaca `CF-Connecting-IP` kalau
`Foundation\Http\Request::isProxyTrusted()` benar dan nilainya IP yang sah. Daftar proxy-nya
diisi lewat opsi baru `application.trusted_proxies`, yang dipasang di `boot.php` sebelum request
diproses. Selama daftar itu kosong, alamat peer yang dipakai.

### K8. Token JWT bisa dipalsukan lewat pertukaran algoritma

- [x] Selesai

Ditemukan di putaran kedua. `JWT::decode($token, $key)` membaca nama algoritma **dari
tokennya sendiri** kalau opsi `algorithm` tidak diberikan. Untuk kunci RSA, itu berarti
penyerang tinggal menandatangani token dengan HS256 memakai **kunci publik** sebagai secret
HMAC — dan kunci publik memang untuk dibagikan.

**Reproduksi**:

```php
$asli  = JWT::encode(['sub' => 'budi'],  $private, [], 'RS256');
$palsu = JWT::encode(['sub' => 'admin'], $public,  [], 'HS256');

JWT::decode($asli,  $public);   // sub=budi
JWT::decode($palsu, $public);   // sub=admin   <== diterima
```

Siapa pun yang tahu kunci publik bisa menerbitkan token apa pun. `firebase/php-jwt` menjadikan
daftar algoritma sebagai argumen wajib justru karena ini.

**Yang dikerjakan**: kalau opsi `algorithm` tidak ada, algoritma yang diperbolehkan diturunkan
dari bahan kuncinya lewat `JWT::suitable()`: kunci yang bisa dibaca OpenSSL sebagai kunci PEM
hanya menerima `RS*`, kunci lain hanya menerima `HS*`. Menyebut `algorithm` secara eksplisit
tetap didahulukan, dan tidak ada pemanggil lama yang perlu diubah.

### K9. URL dibangun dari header `Host` kiriman klien

- [x] Selesai

Ditemukan di putaran keenam. `application.url` bawaannya kosong, dan waktu kosong `URL::base()`
jatuh ke `Request::foundation()->getRootUrl()` — yang menyusun host dari header `Host`.
`getHost()` hanya memeriksa bentuk karakternya, tidak pernah membandingkannya dengan host yang
memang dilayani aplikasi.

**Reproduksi**:

```
GET /reset HTTP/1.1
Host: jahat.test

URL::to('reset/token123')  =>  http://jahat.test/index.php/reset/token123
```

Tautan reset kata sandi yang dikirim lewat surel akan menunjuk ke host penyerang, dan tokennya
ikut terbawa begitu korban mengeklik.

**Yang dikerjakan**: opsi baru `application.trusted_hosts`, dipasang di `boot.php` sebelum
request diproses. Selama daftarnya kosong host apa pun diterima (yang memang cocok untuk
pengembangan); begitu diisi, request yang menyebut host lain ditolak. Nama boleh diawali `*.`
untuk mencakup subdomainnya sekaligus dirinya sendiri, dan pengecekannya tahan terhadap
kebingungan akhiran — `situs-asli.test.jahat.test` tetap ditolak.

---

## Tinggi — fungsi rusak

### T1. Throttle melempar exception di driver cache redis

- [x] Selesai

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

**Yang dikerjakan**: ikut selesai lewat T2. Ada juga jaring pengaman: kalau `INCR` menolak
sebuah kunci (data yang ditulis versi sebelumnya), kuncinya ditulis ulang sebagai integer biasa
lalu hitungannya dilanjutkan, alih-alih melempar.

### T2. `Cache::get()` merusak nilai hasil `Cache::increment()` di redis

- [x] Selesai

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

**Yang dikerjakan**: `put()` menyimpan integer sebagai angka biasa (nilai lain tetap
ter-serialize), dan `retrieve()` mengenali bentuk itu. Dengan begitu `INCR` bekerja di atas data
yang sama dengan yang dibaca `get()`, dan tipenya tetap terjaga: `put($k, 7)` kembali sebagai
`int`, `put($k, '7')` kembali sebagai `string`.

### T3. `URL::to_route()` membocorkan kunci internal untuk route ber-domain

- [x] Selesai

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

**Yang dikerjakan**: `Router::uri()` mengupas bagian domain dari kunci route, dan dipakai
`URL::explicit()`, `URL::to_route()` serta dua tempat di `Router` yang selama ini menyalin
logika yang sama.

### T4. Aturan `size`/`min`/`max`/`between` salah menghitung array

- [x] Selesai

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

**Yang dikerjakan**: `Validator::size()` mengembalikan `count($value)` untuk array dan
`Countable`, setelah cabang berkas upload supaya ukuran berkas tetap dihitung dalam kilobyte.

### T5. Middleware berbasis pattern sama sekali tidak jalan

- [x] Selesai

Ditemukan saat memverifikasi perbaikan K2. Ketiga bentuk yang didokumentasikan di
`packages/docs/data/routing.md` gagal:

```php
Route::middleware('pattern: admin/*', 'auth');
// TypeError: Argument #2 ($handler) must be of type callable, string given

Route::middleware('pattern: api/*', ['name' => 'api_auth', function () { .. }]);
// TypeError: Argument #2 ($handler) must be of type callable, array given

Route::middleware('pattern: admin/*', function () { .. });
// Error: Object of class Closure could not be converted to string
```

Type hint `callable` menolak nama middleware maupun bentuk array, sementara closure telanjang
lolos type hint lalu mati di `Middlewares::get()` yang memperlakukannya sebagai string. Cabang
`is_array($middleware)` di `Route::patterns()` pun tidak pernah tercapai. Jadi cara memasang
middleware ke sekumpulan URI sekaligus — misalnya `auth` untuk seluruh `admin/*` — tidak pernah
bisa dipakai.

**Yang dikerjakan**: type hint `callable` dilepas dari `Route::middleware()` dan
`Middleware::register()`, dan `Route::patterns()` menangani ketiga bentuk: string diurai sebagai
daftar nama, array `[nama, callback]` didaftarkan dengan namanya, dan callable telanjang
didaftarkan dengan nama turunan dari pattern-nya.

### T6. Memanggil method `Str` yang tidak ada membuat proses segfault

- [x] Selesai

`Str::__callStatic()` mengembalikan pemanggilan ke `['\System\Str', $method]` untuk nama yang
bukan macro. Karena methodnya memang tidak ada, pemanggilan itu masuk kembali ke
`__callStatic`, dan seterusnya sampai stack habis.

**Reproduksi**:

```
$ php -r "require 'system/core.php'; System\Str::metode_yang_tidak_ada('x');"
$ echo $?
139        # SIGSEGV
```

Bukan exception, bukan fatal error — proses PHP-nya mati. Di produksi itu berarti 500 kosong
tanpa satu baris pun di log, dan di FPM worker-nya ikut mati. Salah ketik satu nama method
sudah cukup.

**Yang dikerjakan**: nama yang bukan macro melempar `BadMethodCallException`, seperti trait
`Macroable` yang sudah dipakai kelas lain di framework ini.

### T7. Aturan validasi ber-wildcard diam-diam tidak memvalidasi apa pun

- [x] Selesai

`Validator` tidak mengenal `*` di nama atribut. Aturan seperti `'items.*' => 'integer'`
mencari atribut yang namanya harfiah `items.*`, tidak menemukannya, lalu **lolos** karena
`integer` bukan aturan yang implisit wajib.

**Reproduksi**:

```php
Validator::make(['a' => [1, 'x']], ['a.*' => 'integer'])->passes();   // true
```

Ini idiom yang dibawa orang dari Laravel, dan hasilnya rasa aman yang palsu: form yang
mengirim array dianggap tervalidasi padahal tidak ada satu elemen pun yang diperiksa.

**Yang dikerjakan**: `Validator::expand_rules()` memperluas atribut ber-`*` menjadi atribut
sebenarnya sebelum validasi berjalan, termasuk yang bersarang (`orang.*.nama`). Karena
perluasannya menulis ke `$this->rules`, `has_rule()`, `nullable`, dan pesan error ikut bekerja
seperti biasa — pesan error menyebut elemennya (`a.1`), dan pesan kustom yang ditulis untuk
`a.*` diwariskan ke tiap elemen lewat `inherit_messages()`.

### T8. RSA memotong data yang blok terakhirnya `"0"`

- [x] Selesai

Ditemukan di putaran ketiga. `RSA::encrypt()` dan `decrypt()` memotong data dengan
`while ($data)`, sambil memangkas `$data` tiap putaran. Begitu sisanya kebetulan berupa string
`"0"`, kondisinya jadi falsy dan **perulangannya berhenti** — blok terakhirnya hilang tanpa
error.

**Reproduksi**:

```php
RSA::encrypt('0');                        // '' (cipher kosong)

$data = str_repeat('a', 245) . '0';       // 246 byte, harusnya 2 blok
strlen(RSA::encrypt($data));              // 256, bukan 512
RSA::decrypt(RSA::encrypt($data));        // karakter terakhirnya hilang
```

Ini kerusakan data yang diam: yang dienkripsi tidak sama dengan yang dikembalikan, dan tidak
ada satu pun tanda bahwa ada yang salah.

**Yang dikerjakan**: keduanya berjalan dengan offset (`for ($offset = 0; $offset < $total;
$offset += $length)`) alih-alih menguji sisa stringnya, jadi isi blok tidak lagi menentukan
kapan perulangan berhenti.

### T9. Dua namespace tidak boleh punya kelas bernama sama

- [x] Selesai

`Autoloader::load_psr()` menyimpan catatan berkas yang sudah dimuat dengan kunci **potongan
nama kelas**, bukan berkas hasilnya:

```php
if (isset(static::$loaded[$file]) || isset(static::$loaded[$lowercased])) {
    return;
}
```

Setelah namespace dilepas, `Satu\Bar` dan `Dua\Bar` sama-sama menjadi `Bar`. Yang pertama
dimuat mencatat `Bar`, dan yang kedua langsung keluar tanpa memuat apa pun.

**Reproduksi**:

```php
Autoloader::namespaces(['Satu' => $a, 'Dua' => $b]);

Satu\Bar::asal();   // 'satu'
Dua\Bar::asal();    // Error: Class "Dua\Bar" not found
```

Punya `Model`, `Kernel` atau `Bar` di dua namespace itu hal yang sangat biasa.

**Yang dikerjakan**: catatannya dikunci dengan path berkas yang benar-benar dimuat, sehingga
tetap menjaga satu berkas tidak di-`require` dua kali tanpa memblokir kelas lain yang namanya
kebetulan sama.

### T10. `restore()` tidak bekerja pada model yang diambil dari database

- [x] Selesai

Ditemukan di putaran keempat. Syaratnya menuntut `! $this->exists`:

```php
if (static::$soft_delete && ! $this->exists && ! is_null($this->deleted_at)) {
```

Model yang dihidrasi dari database selalu punya `exists = true`, jadi satu-satunya instance
yang bisa dipulihkan adalah yang barusan dipanggil `delete()`-nya. Cara yang **dicontohkan
dokumentasi** justru diam-diam tidak melakukan apa-apa dan mengembalikan `false`:

```php
$user = User::with_trashed()->find(1);
$user->restore();      // false, baris di database tidak berubah
```

**Yang dikerjakan**: syarat `! $this->exists` dilepas. Yang menentukan adalah modelnya memakai
soft delete dan barisnya memang bertanda terhapus.

### T11. Soft delete bocor lewat eager loading dan query relasi

- [x] Selesai

`Facile\Query::load()` dan seluruh `correlate()` memanggil `$this->table->reset_where()` untuk
melepas ikatan ke satu induk — dan ikut membuang `WHERE deleted_at IS NULL` yang baru saja
dipasang `_query()`, berikut global scope model.

Hasilnya baris yang sudah dihapus muncul di mana-mana kecuali di lazy loading:

```
lazy    Penulis::find(1)->artikel     = 1   (benar)
eager   Penulis::with('artikel')      = 2   <== termasuk yang terhapus
where_has('artikel')                  = 3   <== termasuk penulis yang semua artikelnya terhapus
has('artikel')                        = 3
with_count                            = Budi=2, Ani=1, Cici=1
```

Jadi `with()` menampilkan data yang sudah dihapus kepada pengguna, tanpa tanda apa pun.

**Yang dikerjakan**: `Relationship::reset_constraints()` menggantikan `reset_where()` telanjang
di tujuh tempat. Ia tetap melepas ikatan ke induk, tapi memasang kembali `deleted_at IS NULL`
milik model terkait. `Model::soft_deleting()` ditambahkan supaya relasi bisa menanyakannya.

### T12. `Image::open()` hanya bisa dipakai sekali per request

- [x] Selesai

`open()` menyimpan satu instance singleton, dan pemanggilan kedua **membuang argumen
`$path`-nya**:

```php
if (! is_null(self::$singleton)) {
    static::$singleton->reset();
    return static::$singleton;   // di-reset, tapi tidak pernah memuat $path
}
```

`reset()` mengosongkan `$this->image`, jadi yang dikembalikan adalah objek kosong dan operasi
apa pun setelahnya melempar `imagesx(): Argument #1 ($image) must be of type GdImage, null
given`.

**Reproduksi**:

```php
Image::open('a.png')->export('a2.png');   // jalan
Image::open('b.png')->export('b2.png');   // TypeError, dan 'b.png' tidak pernah dibuka
```

Membuat thumbnail untuk galeri, atau memproses dua gambar dalam satu request, tidak mungkin.
Test bawaannya lolos hanya karena `tearDown`-nya mengosongkan properti singleton lewat
Reflection — sesuatu yang tidak bisa dilakukan kode aplikasi, karena propertinya `private`.

**Yang dikerjakan**: instance sebelumnya tetap di-`reset()` (itu gunanya memegang singleton:
melepas memori gambar lama), tapi yang dikembalikan selalu instance baru yang benar-benar
memuat berkas yang diminta.

### T13. `belongs_to_many` menuntut kolom yang tidak ada di skema yang didokumentasikan

- [x] Selesai

`BelongsToMany::$with` diawali `['id']`, dan konstruktornya menambahkan `created_at` serta
`updated_at` karena `Pivot::$timestamps` bernilai `true`. Ketiganya lalu dipilih dari tabel
pivot:

```
SQLSTATE[HY000]: General error: 1 no such column: role_user.id
```

Sementara dokumentasinya menyebut tabel pivot berisi **`user_id, role_id`** saja. Jadi relasi
many-to-many yang dibangun persis seperti di dokumentasi gagal di setiap query, dan `attach()`
ikut gagal karena menulis kolom timestamp yang tidak ada.

Test bawaannya tidak menangkap ini karena hanya memeriksa tipe objek relasinya, tidak pernah
menjalankan querynya.

**Yang dikerjakan**: `$with` dikosongkan dan `Pivot::$timestamps` menjadi `false`, jadi
defaultnya cocok dengan skema yang didokumentasikan. Kolom pivot tambahan diminta lewat
`->with(['catatan'])` seperti `withPivot()` di Laravel, dan timestamps dinyalakan dengan
`Pivot::$timestamps = true`.

### T14. `identicon()` mencetak gambar ke keluaran, bukan mengembalikannya

- [x] Selesai

`imagepng($image)` tanpa argumen kedua menulis PNG-nya **langsung ke buffer keluaran** dan
mengembalikan `bool`. Nilai bool itulah yang dikembalikan:

```php
$result = imagepng($image);
return $display ? Response::make($result, ..) : $result;
```

Jadi keduanya rusak. Cara yang dicontohkan dokumentasi menulis berkas berisi `"1"`:

```php
$identicon = Image::identicon('budi');                    // true, dan PNG-nya tercetak
Storage::put(path('storage').'avatars/budi.png', $identicon);   // isinya "1"
```

Sementara `Image::identicon('budi', 64, true)` mencetak gambarnya lalu membuat Response yang
badannya juga `"1"`. `Image::dump()` punya masalah yang sama persis, dan dokumentasinya
mengklaim ia mengirim header yang sesuai — padahal tidak mengirim header apa pun.

Test bawaannya membungkus pemanggilan dengan `ob_start()`/`ob_end_clean()` lalu menegaskan
`assertTrue($result)` — jadi keluaran nyasarnya memang disembunyikan, bukan diperbaiki.

**Yang dikerjakan**: `Image::render()` menangkap byte PNG-nya lewat buffer keluaran.
`identicon()` mengembalikan byte itu, atau Response ber-`Content-Type: image/png` yang berisi
byte itu. `dump()` mengembalikan Response yang sama.

### T15. Satu klien WebSocket bisa menghabiskan memori server

- [x] Selesai

Panjang payload yang diumumkan klien dipercaya apa adanya, tanpa batas atas apa pun. Server
menyangga potongan yang masuk ke `$user->buffer` sampai frame-nya lengkap — dan frame yang
mengumumkan 2^40 byte tidak akan pernah lengkap.

**Reproduksi** (harness dua proses):

```
RSS server sebelum : 16.9 MB
kirim 13 MB di bawah satu frame yang mengumumkan panjang 1 TB
RSS server sesudah : 41.2 MB
```

Terus saja begitu sampai server mati, dan matinya server berlaku untuk semua klien.

**Yang dikerjakan**: opsi baru `websocket.max_payload_size` (bawaan 10 MB). Frame yang
mengumumkan lebih dari itu ditolak dan koneksinya diputus. Konfigurasi lama yang belum punya
opsi itu memakai nilai bawaan yang sama, bukan tanpa batas. Sesudah perbaikan, uji yang sama
menaikkan RSS dari 28.4 ke 28.8 MB, dan pesan biasa tetap sampai.

### T16. Kredensial Curl ikut terkirim ke host berikutnya

- [x] Selesai

`Curl::auth()`, `cookie()` dan `proxy()` menyimpan nilainya di properti statis yang tidak
pernah dibersihkan. Jadi setelah mengautentikasi ke satu API, **setiap** permintaan berikutnya
membawa kredensial yang sama — termasuk ke host yang sama sekali berbeda.

**Reproduksi**:

```php
Curl::auth('pengguna', 'sandi');
Curl::get('https://api-saya.test/data');
Curl::get('https://pihak-ketiga.test/apa-saja');   // header Authorization ikut terkirim
```

Dokumentasinya menyediakan `clear_default_headers()` dan `clear_curl_options()`, tapi tidak ada
padanannya untuk kredensial, cookie, atau proxy.

**Yang dikerjakan**: `clear_auth()`, `clear_cookie()`, `clear_proxy()` dan `reset()`
ditambahkan, dan dokumentasinya menyebut bahwa kredensial bertahan sampai dibersihkan.

### T17. `@forelse` mati justru saat koleksinya kosong

- [x] Selesai

Bingkai `$loop` didorong **di dalam** cabang "ada isinya", sementara `@endforelse` selalu
mem-`pop`-nya:

```php
<?php if (count($a) > 0): ?><?php $__loop_stack = ..; $__loop_stack[] = ..; foreach ..
..
<?php endif; array_pop($__loop_stack); ?>
```

Koleksi kosong berarti cabang `else` yang jalan, `$__loop_stack` tidak pernah ada, dan
`array_pop()` menerima `null`:

```
TypeError: array_pop(): Argument #1 ($array) must be of type array, null given
```

Jadi `@forelse` gagal persis pada satu-satunya kasus yang jadi alasan keberadaannya.

**Yang dikerjakan**: bingkainya didorong sebelum `if`, sehingga kedua cabang punya sesuatu
untuk di-`pop` dan tumpukannya tetap seimbang — termasuk saat `@forelse` bersarang di dalam
loop lain.

### T18. `@empty($x)` dirusak oleh pemisah `@forelse`

- [x] Selesai

`compile_empty()` mengganti **setiap** `@empty` dengan `<?php endforeach; ?><?php else: ?>`,
tanpa memedulikan apakah itu pemisah `@forelse` atau direktif tersendiri. Jadi:

```blade
@empty($x)
    kosong
@endempty
```

dikompilasi menjadi `<?php endforeach; ?><?php else: ?>($x) kosong @endempty` — dan itu
**parse error**, bukan sekadar hasil yang salah.

**Yang dikerjakan**: `@empty` yang diikuti kurung diperlakukan sebagai direktif tersendiri dan
dikompilasi menjadi `if (empty(..))`, sementara `@empty` telanjang tetap jadi pemisah
`@forelse`. `@endempty` ditambahkan sebagai penutupnya.

---

## Sedang

### S1. Driver cache redis mengabaikan prefix `cache.key`

- [x] Selesai

Driver apc, memcached, database dan file semuanya memakai `$this->key.$key`. Driver redis
memakai `$key` mentah di `has()`, `retrieve()`, `put()`, `increment()` dan `forget()`. Dua
aplikasi yang berbagi satu database Redis akan saling menimpa cache.

**Yang dikerjakan**: konstruktornya menerima prefix seperti driver lain, dan semua method
memakainya. Kunci yang ditulis versi sebelumnya jadi tidak terbaca lagi — isinya cache, jadi
akan terisi ulang sendiri.

### S2. `Cache\Drivers\Redis::flush()` menjalankan `flushdb()`

- [x] Selesai

`system/cache/drivers/redis.php:139`. `flushdb()` menghapus **seluruh** database Redis, bukan
hanya kunci milik cache. Kalau session atau antrian job memakai database Redis yang sama,
semuanya ikut hilang. Driver lain hanya menghapus kunci yang berprefix.

**Yang dikerjakan**: `flush()` mencari kunci berprefix lalu menghapusnya satu per satu.
`flushdb()` hanya dipakai kalau prefixnya memang kosong, karena saat itu tidak ada cara
membedakan kunci milik cache dari kunci lain.

### S3. `Cache\Drivers\Database::increment()` tanpa kunci baris

- [x] Selesai

`system/cache/drivers/database.php:89` membungkus baca-ubah-tulis dalam transaksi tapi
`SELECT`-nya tidak mengunci baris. Di MySQL dengan REPEATABLE READ, dua transaksi paralel
membaca nilai yang sama dan sama-sama menulis N+1 (*lost update*). Framework sudah punya
`lock_for_update()`, tinggal dipakai.

**Yang dikerjakan**: `SELECT`-nya memakai `lock_for_update()`.

### S4. `Cache\Drivers\File::increment()` tidak atomik

- [x] Selesai

Driver file memakai `Driver::increment()` bawaan yang baca-tambah-tulis tanpa penguncian.
Karena `file` adalah driver cache **default**, rate limiter bawaan pun tidak atomik.

**Yang dikerjakan**: driver file punya `increment()` sendiri yang membaca, menambah dan menulis
di bawah satu `flock(LOCK_EX)`, dan jatuh ke implementasi bawaan kalau berkasnya tidak bisa
dibuka atau dikunci.

### S5. `required` meloloskan array kosong

- [x] Selesai

`Validator::validate_required()` (`system/validator.php:254`) hanya menolak `null`, string
kosong, dan berkas upload kosong. Laravel juga menolak array/`Countable` yang kosong.

**Reproduksi**: `Validator::make(['tags' => []], ['tags' => 'required'])->passes()` => `true`.

Form dengan sekumpulan checkbox yang wajib diisi akan lolos meski tidak ada yang dicentang.

**Yang dikerjakan**: array dan `Countable` yang kosong ditolak, setelah cabang berkas upload
supaya unggahan kosong tetap ditangani sebagaimana mestinya.

### S6. `date_format` tidak ketat

- [x] Selesai

`Validator::validate_date_format()` (`system/validator.php:1915`) hanya memeriksa
`date_create_from_format()` tidak mengembalikan `false`, padahal parser PHP itu longgar.

**Reproduksi**: `date_format:Y-m-d` meloloskan `2026-1-1`.

**Yang dikerjakan**: `validate_date_format()` membandingkan `$date->format($format)` dengan
nilai aslinya, jadi hanya yang benar-benar cocok yang lolos.

### S7. `Container::instance()` tidak terlihat oleh `Container::registered()`

- [x] Selesai

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

**Yang dikerjakan**: `registered()` ikut memeriksa `static::$singletons`.

### S8. `in_array` dengan bentuk yang didokumentasikan selalu gagal

- [x] Selesai

Dokumentasi mencontohkan `'color' => 'in_array:colors.*'`, tapi implementasinya mencari kunci
yang namanya harfiah `colors.*` di dalam data:

```php
if (! array_key_exists($parameters[0], $this->attributes)) {
    return false;
}
```

Kunci itu tidak pernah ada, jadi aturan yang ditulis persis seperti di dokumentasi **tidak
pernah lolos**.

**Yang dikerjakan**: akhiran `.*` dilepas dan parameternya dibaca lewat `Arr::get()`, jadi
`in_array:colors.*` dan `in_array:colors` sama-sama menunjuk ke array `colors`, dan jalur
bersarang seperti `in_array:form.colors` ikut bekerja.

### S9. `filled` menolak `'0'`

- [x] Selesai

`validate_filled()` memakai `! empty($value)`, dan `'0'` itu kosong menurut PHP. Jadi form
dengan nilai nol yang sah — jumlah `0`, pilihan `'0'` — ditolak.

**Yang dikerjakan**: `filled` memakai definisi kosong yang sama dengan `required`, jadi `'0'`
dan `0` lolos sementara string kosong dan array kosong tidak.

### S10. Namespace dicocokkan menurut urutan pendaftaran, bukan yang paling spesifik

- [x] Selesai

`Autoloader::load()` mengambil namespace pertama yang cocok dari `static::$namespaces`, dan
urutannya ditentukan kapan namespace itu didaftarkan. Jadi mendaftarkan `Luar\Dalam\` lebih
dulu lalu `Luar\` membuat yang kedua berada di depan, dan `Luar\Dalam\Bar\Baz` dicari di
bawah akar yang salah.

**Reproduksi**:

```php
Autoloader::namespaces(['Luar\Dalam' => $dalam]);
Autoloader::namespaces(['Luar' => $lain]);

Luar\Dalam\Bar\Baz::asal();   // Error: class not found
```

Dibalik urutannya, jalan. Composer menyelesaikan ini dengan prefix terpanjang, bukan urutan.

**Yang dikerjakan**: `namespaces()` mengurutkan petanya dari prefix terpanjang ke terpendek
setiap kali ada pendaftaran baru, jadi urutan pendaftaran tidak lagi menentukan hasil.

### S11. `Image` mencampur path mentah dengan path yang sudah diresolusi

- [x] Selesai

`Image::path()` menambatkan setiap path ke `path('base')`. Konstruktornya memeriksa hasil
resolusi itu, lalu meneruskan path **mentah** ke `load()`:

```php
$this->path = $this->path($path);
if (! is_file($this->path)) { throw .. }    // memeriksa yang sudah diresolusi
$this->load($path);                          // membaca yang mentah
```

`load()` lalu membaca relatif terhadap direktori kerja proses. Selama direktori kerjanya
kebetulan akar proyek keduanya sama; begitu tidak — layout dengan `public/`, job runner, atau
perintah konsol yang dijalankan dari tempat lain — berkasnya lolos pemeriksaan lalu gagal
dibaca dengan pesan yang menyesatkan: *Only JPG, PNG or GIF file type is supported*.

Pengaman `$overwrite` di `export()` punya masalah yang sama terbalik: `is_file($path)`
memeriksa path mentah sementara tulisannya ke path hasil resolusi, jadi berkas yang sudah ada
bisa tertimpa diam-diam.

**Yang dikerjakan**: keduanya memakai path hasil resolusi.

### S13. Ukuran gambar nol diserahkan mentah-mentah ke GD

- [x] Selesai

`width(0)`, `width(-5)` dan `crop(0, 0, 0, 0)` melempar `ValueError: imagecreatetruecolor():
Argument #1 ($width) must be greater than 0` dari GD, sementara `crop()` yang melebihi batas
dan `rotate()` yang bukan kelipatan 90 sudah punya pesan sendiri.

**Yang dikerjakan**: `Image::dimension()` memeriksa hasil ukurannya lebih dulu dan melempar
pesan yang menyebutkan ukuran yang diminta.

### S14. `CURLOPT_ENCODING` diisi encoding karakter aplikasi

- [x] Selesai

```php
CURLOPT_ENCODING => Config::get('application.encoding', 'UTF-8'),
```

`CURLOPT_ENCODING` menyetel header **`Accept-Encoding`**, bukan set karakter. Jadi setiap
permintaan mengirim `Accept-Encoding: UTF-8` — nilai yang tidak berarti apa-apa. Kompresi
transparan jadi tidak pernah aktif, dan sebagian server menjawab 406 untuk encoding yang tidak
dikenalnya.

**Yang dikerjakan**: diisi string kosong, yang artinya "terima semua encoding yang bisa
didekode libcurl ini". Diverifikasi: yang terkirim sekarang `deflate, gzip, br, zstd`.

### S15. `extract_headers()` membaca melewati ujung frame pendek

- [x] Selesai

Frame yang lebih pendek dari headernya sendiri tetap dibaca sampai indeks 13:

```
$ kirim satu byte "\x81" ke server
PHP Warning: Uninitialized string offset 1 in system/websocket/server.php on line 617
PHP Warning: Uninitialized string offset 1 in system/websocket/server.php on line 621
..
```

Frame-nya juga jadi salah tafsir, karena bagian yang belum sampai dibaca sebagai nol.

**Yang dikerjakan**: `extract_headers()` mengembalikan penanda `partial` untuk frame yang belum
lengkap di setiap titik yang butuh byte tambahan, dan `split_packet()` menyimpannya ke buffer
untuk digabung dengan potongan berikutnya alih-alih menebak.

### S16. Direktif bersarang dalam satu baris jadi parse error

- [x] Selesai

`compile_structure_start()` mencocokkan `@(if|elseif|for|while)(\s*\(.*\))`. `.*` itu rakus,
jadi untuk satu baris yang memuat dua direktif ia menelan keduanya:

```blade
@if ($a) luar @if ($b) dalam @endif @endif
```

menjadi `<?php if($a) @if($b): ?>` — `ParseError: syntax error, unexpected token "@"`. Ditulis
di beberapa baris hasilnya benar, jadi bug ini hanya muncul saat template ditulis rapat.

**Yang dikerjakan**: pola kurungnya diganti pencocokan berpasangan
(`\((?:[^()]++|(?N))*\)`), yang dipakai juga oleh `@foreach` dan `@forelse`. Kondisi yang
memuat kurung sendiri seperti `@if (count($a) > 0)` tetap bekerja.

### S17. `Image::export()` hanya bisa menulis PNG dan `.jpg` huruf kecil

- [x] Selesai

Tiga masalah menumpuk di satu `switch`:

```php
$extension = Storage::extension($this->path);   // tidak di-lowercase
switch ($extension) {
    case 'jpg': ..                              // 'jpeg' tidak ada
    case 'gif':
        imagegif($this->image, $this->path, $this->quality)   // imagegif hanya 2 argumen
```

Hasilnya:

```
export .gif   => ArgumentCountError: imagegif() expects at most 2 arguments, 3 given
export .jpeg  => Bad filetype given, must be JPG, PNG or GIF.
export .JPG   => Bad filetype given, must be JPG, PNG or GIF.
export .PNG   => Bad filetype given, must be JPG, PNG or GIF.
```

Jadi GIF — format yang disebut sendiri oleh pesan errornya dan diterima `Image::open()` —
tidak pernah bisa disimpan, `.jpeg` ditolak padahal itu ejaan yang paling umum ditulis
peralatan lain, dan huruf besar apa pun di ekstensi menggagalkannya. Test bawaannya hanya
mengekspor PNG huruf kecil, jadi tidak satu pun tertangkap.

**Yang dikerjakan**: ekstensinya di-`strtolower()`, `jpeg` menempel ke cabang `jpg`, dan
argumen kualitas dilepas dari `imagegif()` yang memang tidak punya parameter itu.

Sekalian seluruh panggilan fungsi bawaan PHP di `system/` diperiksa jumlah argumennya terhadap
tanda tangan aslinya. `imagegif()` satu-satunya yang salah.

---

## Rendah / pengerasan

### R1. Kunci aplikasi efektif hanya 112 bit

- [x] Selesai

`system/init.php` menghasilkan kunci berbentuk UUID (36 karakter). `openssl_encrypt()` dengan
`aes-256-cbc` memotongnya ke 32 byte pertama, yang berisi 28 digit heksa dan 4 tanda hubung —
jadi 112 bit, bukan 256. Masih di atas ambang yang bisa dibobol, tapi tidak perlu.

**Yang dikerjakan**: kunci baru dihasilkan sebagai `bin2hex(openssl_random_pseudo_bytes(32))`,
64 karakter heksa tanpa tanda hubung, jadi 32 byte pertama yang dipakai OpenSSL seluruhnya
heksa: 128 bit. Pola validasi di `system/init.php` menerima format baru maupun UUID lama, jadi
aplikasi yang sudah jalan tidak perlu berbuat apa-apa. Yang ingin naik ke 128 bit tinggal
menghapus `key.php` dan membiarkannya dibuat ulang — perlu diingat itu membatalkan seluruh
cookie dan session yang sudah beredar.

Tidak ada test otomatis untuk poin ini: pembuatan kunci berjalan di `init.php`, sebelum harness
test ada. Diverifikasi manual dengan menjalankan alur yang sama di direktori sementara.

### R2. Aturan validasi `image` menerima SVG

- [x] Selesai

`Validator::validate_image()` (`system/validator.php:1168`) memasukkan `svg` ke daftar yang
diizinkan. SVG bisa memuat JavaScript, jadi menyajikannya dari origin yang sama adalah XSS
tersimpan. Laravel versi baru mengeluarkan SVG dari default.

**Yang dikerjakan**: `svg` dikeluarkan dari daftar default. Yang memang membutuhkannya menulis
`image:allow_svg`.

### R3. Facile tidak menjaga mass-assignment secara default

- [x] Selesai

`Model::$guarded = []` dan `Model::$fillable = null` (`system/database/facile/model.php:90`
dan `:97`), jadi **semua kolom bisa diisi massal** kecuali model menyatakan sebaliknya. Laravel
memakai `$guarded = ['*']` supaya aman secara default. `Model::create(Input::all())` di Rakit
membiarkan penyerang mengisi `id`, `is_admin`, dan kolom apa pun yang ada.

Lebih buruk lagi, `$guarded = ['*']` — idiom yang orang bawa dari Laravel untuk menutup semuanya
— **diam-diam tidak melakukan apa-apa**, karena `fill()` mencocokkan nama kolom dengan
`in_array()` sehingga `'*'` tidak pernah cocok dengan kolom mana pun.

**Yang dikerjakan**: `'*'` di `$guarded` kini benar-benar menjaga seluruh kolom.

Defaultnya sengaja **tidak** diubah menjadi `['*']`: itu akan mematikan setiap `fill()` dan
`create()` pada aplikasi yang sudah jalan, dan itu keputusan rilis, bukan perbaikan bug.
Sekarang menutupnya cukup satu baris di modelnya, atau `Model::$guarded = ['*']` global di
`application/boot.php` kalau memang mau aman secara default.

### R4. `Redirect::back()` mengikuti header `Referer`

- [x] Selesai

`Request::referrer()` dikendalikan penyerang, dan `URL::to()` mengembalikan URL absolut apa
adanya, jadi `Redirect::back()` bisa diarahkan ke situs luar. Laravel berperilaku sama, jadi
ini pengerasan, bukan penyimpangan.

**Yang dikerjakan**: `back()` hanya mengikuti referrer yang tidak menyebut host lain. Selain itu
ia jatuh ke `$fallback`, atau ke `/` kalau tidak ada.

### R5. `Route::forward()` fatal kalau route tidak ada

- [x] Selesai

`system/routing/route.php:414`. `Router::route()` bisa mengembalikan `null`, dan `forward()`
langsung memanggil `->call()` di atasnya.

**Yang dikerjakan**: `forward()` mengembalikan `Response::error(404)` kalau tidak ada route yang
cocok.

### R6. Penggantian placeholder `Lang` saling menimpa

- [x] Selesai

`Lang::get()` (`system/lang.php:116`) mengganti placeholder sesuai urutan array, jadi
placeholder yang namanya awalan dari placeholder lain merusaknya.

**Reproduksi**:

```php
// 'Halo :nama, selamat datang :nama_lengkap'
Lang::line('probe.halo', ['nama' => 'Budi', 'nama_lengkap' => 'Budi Purnomo'])->get();
// => 'Halo Budi, selamat datang Budi_lengkap'
```

**Yang dikerjakan**: `Lang::get()` mengurutkan penggantian dari nama terpanjang ke terpendek
sebelum menjalankannya.

### R7. `Input::json(true)` mengubah list menjadi objek berkunci angka

- [x] Selesai

`system/input.php:118` memakai `JSON_FORCE_OBJECT`, jadi `{"tags":[1,2,3]}` terbaca sebagai
`tags => {"0":1,"1":2,"2":3}` alih-alih array biasa. Ia juga menimpa `Input::$json` dengan hasil
konversinya, sehingga pemanggilan berikutnya bekerja di atas data yang sudah berubah bentuk.

**Yang dikerjakan**: `Input::$json` menyimpan body mentahnya, dan tiap pemanggilan mendekode
ulang dengan flag yang diminta. Tidak ada lagi `JSON_FORCE_OBJECT`.

### R8. `Cookie::get()` melempar untuk nama cookie yang sah

- [x] Selesai

Pemeriksaan namanya `/^[a-zA-Z0-9_-]+$/`, padahal titik sah di nama cookie HTTP. `Cookie::has()`
memanggil `get()`, jadi memeriksa cookie milik aplikasi lain di domain yang sama melempar
exception alih-alih mengembalikan `false`.

**Yang dikerjakan**: pemeriksaannya pindah ke `Cookie::guard_name()` dan kini menerima titik.

### R9. `e()` memakai `double_encode = false`

- [x] Selesai

`htmlentities($value, ENT_QUOTES, 'UTF-8', false)` membiarkan entitas yang sudah ada. Bukan
lubang XSS (karakter `<`, `>`, `"` tetap di-escape), tapi berbeda dari default Laravel dan
membuat keluaran sulit ditalar saat data sudah mengandung entitas.

**Yang dikerjakan**: parameternya jadi `true`, sama seperti Laravel. Nilai yang sudah berisi
entitas kini di-escape sekali lagi, jadi `&lt;` tampil apa adanya sebagai teks.

### R10. `Container::resolve()` bisa rekursi tanpa henti

- [x] Selesai

Alias yang saling menunjuk (`register('a', 'b')` dan `register('b', 'a')`) atau dua kelas yang
konstruktornya saling membutuhkan membuat `resolve()` memanggil dirinya terus sampai stack
habis. Laravel mendeteksi ini lewat `buildStack` dan melempar `CircularDependencyException`.

**Yang dikerjakan**: `Container::resolve()` mencatat apa yang sedang dibangun dan melempar
`Circular dependency while resolving: ..` berikut rantainya begitu satu nama muncul dua kali.

### R11. Nama berkas unduhan tidak dibersihkan

- [x] Selesai

`Response::download($path, $name)` menyisipkan `$name` ke `Content-Disposition` apa adanya:

```php
sprintf('attachment; filename="%s"', $name ?: basename($path))
```

Nama itu sering datang dari request. Tanda kutip di dalamnya mengakhiri string berkutip lebih
awal sehingga penyerang bisa menambahkan parameter sendiri:

```
$name = 'laporan.txt"; filename*=UTF-8\'\'evil.exe'
=> attachment; filename="laporan.txt"; filename*=UTF-8''evil.exe"
```

CR/LF tersimpan mentah di header bag juga, meski `header()` bawaan PHP menolaknya saat dikirim
sehingga tidak sampai jadi response splitting.

**Yang dikerjakan**: `Response::disposition()` membuang CR, LF, NUL, kutip ganda dan garis
miring terbalik, lalu mengambil `basename()`-nya, dan memakai `download` kalau yang tersisa
kosong.

---

## Dokumentasi yang ikut diperbarui

| Berkas | Perubahan |
|---|---|
| `application/config/application.php` | Opsi baru: `trusted_proxies` |
| `packages/docs/data/routing.md` | Middleware yang tidak dikenal kini melempar |
| `packages/docs/data/database/magic.md` | Batasan operator `where()` dan arah `order_by()` |
| `packages/docs/data/database/facile.md` | `$guarded = ['*']`, catatan mass-assignment |
| `packages/docs/data/validation.md` | `required` dengan array, arti "size", `date_format` ketat, `image:allow_svg` |
| `packages/docs/data/input.md` | Body menang atas query string |
| `packages/docs/data/validation.md` | Aturan ber-wildcard, `filled` dengan nol, bentuk `in_array` |
| `packages/docs/data/jwt.md` | Algoritma yang diperbolehkan mengikuti bahan kunci |
| `packages/docs/data/database/facile.md` | Kolom pivot bersifat opt-in |
| `packages/docs/data/curl.md` | Kredensial bertahan sampai dibersihkan, `reset()` |
| `packages/docs/data/image.md` | `dump()` mengembalikan Response |
| `application/config/websocket.php` | Opsi baru: `max_payload_size` |
| `application/config/application.php` | Opsi baru: `trusted_hosts` |

Halaman routing sudah menggambarkan grup bersarang yang menyambung prefix dan menggabungkan
middleware — yang selama ini tidak dilakukan kodenya. Sekarang kodenya menyusul, jadi
contohnya tidak perlu diubah.

Bagian "Middleware Pattern" di halaman routing juga sudah benar sejak awal; ketiga bentuk yang
dicontohkan di sana baru sekarang benar-benar bisa dijalankan (lihat T5).

---

## Sudah disisir tanpa temuan

Bagian yang diperiksa di putaran kedua dan ternyata bersih:

- **Paginator** — nilai `?page=` yang aneh (huruf, negatif, array, angka raksasa) dijepit
  dengan benar, dan query string yang dipertahankan di tautan halaman ter-escape.
- **Blade** — `{{ }}` selalu lewat `e()`, `{!! !!}` memang mentah, `@json` memakai flag
  `JSON_HEX_*` sehingga aman disematkan di HTML, `@{{ }}` benar-benar meloloskan literal.
- **View** — nama view dengan `../` ditolak, bukan dibaca dari luar direktori view.
- **Package, Config** — nama dengan `..`, `/` dan `\` tidak menembus direktori.
- **Email** — CR, LF dan NUL dibuang dari seluruh header, termasuk `to`, `cc`, `bcc` dan
  `subject`, jadi tidak ada penyisipan header.
- **Markdown** — HTML mentah memang diteruskan, tapi `safety()` ada dan dokumentasinya sudah
  menyuruh menyalakannya untuk masukan dari pengguna.
- **Console `make`** — `slashes()` mengubah titik menjadi garis miring, jadi `..` runtuh
  menjadi `//` dan tidak bisa menembus direktori.
- **Driver job redis** — alur `add`, `run`, `forget` berjalan benar.
- **Validator** — 32 aturan lain diuji satu per satu terhadap perilaku Laravel dan cocok
  semua, termasuk `unique`, `exists`, `distinct`, keluarga `required_*`, `regex`, `digits`
  dan `same`/`different`.
- **Collection, Arr** — kasus batas (koleksi kosong, `slice` negatif, `chunk(0)`, `flatten`
  berkedalaman, `pluck` bersarang) berperilaku wajar.
- **RSA** selain pemotongan blok — kunci memang sengaja dibuat ulang tiap proses dan
  dokumentasinya sudah menyebutkan itu berikut `load_keys()`, jadi bukan bug.
- **Autoloader** selain dua poin di atas — nama kelas dengan `..` atau berawalan pemisah
  ditolak sebelum menyentuh berkas.
- **Carbon** — luapan akhir bulan (`addMonth` vs `addMonthNoOverflow`), tahun kabisat,
  timestamp negatif, `addDays` nol dan negatif, `diffInDays`, `diffForHumans`, dan konversi
  zona waktu semuanya cocok dengan `nesbot/carbon`.
- **Driver job memcached** — diuji terhadap memcached 1.x sungguhan (server dan ekstensinya
  dijalankan di container): antrean, queue terpisah, job gagal dan `runall` semuanya benar.
- **Console command** — `help`, `route:list`, `clear:*` dan `session:gc` dijalankan langsung.
  `session:gc` sekalian dirapikan: dulu hanya mau menyapu driver `database`, sekarang ia
  bekerja untuk driver apa pun yang mengimplementasi `Session\Drivers\Sweeper`, jadi driver
  `file` ikut tersapu.
- **Facile** selain empat poin di atas — `attach`, `detach`, `sync`, relasi bersarang
  (`with('penulis.profil')`), `to_array` beserta relasinya, dan hidrasi `has_one` kosong
  semuanya benar.
- **Image** selain tiga poin di atas — `rotate` menolak sudut yang bukan kelipatan 90, `crop`
  menolak seleksi di luar batas, `ratio` menjaga perbandingan, dan seluruh filter (`grayscale`,
  `sepia`, `invert`, `edge`, `emboss`, `sketch`, `blur`, `brightness`, `contrast`, `pixelate`)
  berjalan.
- **Curl** selain dua poin di atas — verifikasi TLS menyala secara bawaan
  (`verify_peer = 1`, `verify_host = 2`), yang penting untuk pustaka HTTP.
- **WebSocket** selain dua poin di atas — jabat tangan RFC 6455 benar, pemeriksaan origin ada
  meski `origin_required` bawaannya `false` sebagaimana didokumentasikan, dan pesan biasa
  melewati jalur frame dengan utuh.
- **Blade** selain tiga poin di atas — `@foreach`, `@for`, `@while`, `@unless`, `@section`,
  `@yield`, `@push`, `@stack`, `@once`, `@php`, `@csrf`, `@method`, `@include`, `@json` dan
  komentar semuanya dikompilasi benar; `@json` memakai flag `JSON_HEX_*` sehingga aman
  disematkan. Direktif Laravel yang memang belum ada (`@isset`, `@switch`, `@class`,
  `@checked`, `@selected`, `@disabled`, `@lang`) lewat begitu saja sebagai teks biasa — itu
  fitur yang belum dibuat, bukan bug, dan sudah tercatat di LARAVEL-PARITY.md.
- **Debugger/Oops** — dengan `debugger.activate = false`, halaman 500 tidak membocorkan pesan
  exception maupun path berkas; diuji lewat HTTP sungguhan. Satu catatan kecil:
  `Debugger::detectDebugMode()` dipanggil di `boot.php` tapi nilainya dibuang, jadi mode debug
  murni ditentukan konfigurasi. Perilakunya benar, hanya pemanggilannya yang sia-sia dan bisa
  membuat pembaca mengira ada deteksi otomatis berbasis IP.

## Belum diaudit

Kosong.

Yang tidak berarti framework ini bebas bug — hanya berarti setiap bagian sudah pernah dilihat,
dan setiap temuan yang muncul sudah ditutup berikut testnya. Enam putaran, 53 temuan.
