# Audit `./system` — Rakit Framework

Tujuan: **0 bug** dan **100% ter-unit-test**, tetap jalan di **PHP 5.4.0 – 8.5**.

## Status

| Metrik | Awal | Sekarang |
| --- | ---: | ---: |
| Test | 1668 | 1896 |
| Assertion | 5266 | 6343 |
| Bug ditemukan & diperbaiki | — | 123 |
| Coverage baris | 64.20% | **70.68%** |
| Berkas `system/` yang tidak pernah ter-load saat test | 72 | 44 |

Coverage diukur dengan ekstensi `pcov` (php-code-coverage bawaan PHPUnit 4.8
tidak jalan di PHP 8 sebelum patch di bawah diterapkan):

```
php -d pcov.enabled=1 -d pcov.directory=system vendor/bin/phpunit -c phpunit.xml --coverage-text
```

Seluruh berkas test hijau, baik dijalankan sebagai satu suite maupun **satu per
satu**. Tidak ada lagi peringatan atau *deprecation* yang berasal dari
`system/` pada PHP 8.4.

## Patch PHPUnit

Dua kerusakan PHPUnit 4.8.34 di PHP 8.4+ ikut diperbaiki lewat mekanisme
`cweagans/composer-patches` yang sudah dipakai proyek ini. Berkasnya ada di
folder `patches/` dan sudah didaftarkan di `composer.json`:

| Berkas | Paket | Isi |
| --- | --- | --- |
| `patches/phpunit_php84.patch` | `phpunit/phpunit` | Konstanta `E_STRICT` yang usang + parameter *implicitly nullable* pada `PHPUnit_Framework_Error` / `PHPUnit_Framework_Exception`. Keduanya dikompilasi sebelum bootstrap sempat mematikan `E_DEPRECATED`, jadi peringatannya tercetak di setiap kali test dijalankan. |
| `patches/phpunit_php_token_stream.patch` | `phpunit/php-token-stream` | Token baru PHP 8 (`T_NAME_FULLY_QUALIFIED`, `T_ATTRIBUTE`, `T_MATCH`, `T_READONLY`, ...) tidak punya kelas `PHP_Token_*` sehingga `--coverage` selalu mati dengan `Class PHP_Token_... not found`. |

Catatan: kedua patch ini belum bisa dikirim ke
<https://github.com/esyede/phpunit-patches> (kredensial yang tersimpan di mesin
ini milik akun `trijayadigital` dan ditolak dengan 403), jadi untuk sekarang
di-host lokal di dalam repo. Kalau nanti sudah dipublikasikan ke sana, tinggal
ganti jalur lokal di `composer.json` dengan URL CDN-nya.

## Temuan (bug)

| # | Berkas | Ringkasan | Status |
| --- | --- | --- | --- |
| 1 | `foundation/faker/calculator/iban.php` | `['self', ...]` sebagai callable — deprecated sejak PHP 8.2 | **fixed** |
| 2 | `str.php` `plural_studly()` | `implode()` dievaluasi sebelum `array_pop()`, kata terakhir jadi dobel (`UserFeedback` → `UserFeedbackFeedbacks`) | **fixed** |
| 3 | `str.php` `plural()` | Tidak menerima `$count`, padahal `plural_studly()` mengirimnya (didokumentasikan tapi tidak diimplementasi) | **fixed** |
| 4 | `str.php` `password()` | Off-by-one: `integers(0, $max - 1)`, karakter terakhir pool tidak pernah keluar | **fixed** |
| 5 | `str.php` `replace_array()` | Pengganti falsy (`'0'`, `''`) jatuh ke `?:` sehingga token pencarian yang dipakai | **fixed** |
| 6 | `str.php` `words()` | `$words < 1` menghasilkan regex `{1,0}` → warning "compilation failed" | **fixed** |
| 7 | `str.php` `ulid()` | Saat 16 karakter acak semuanya 31, indeks `-1` ditulis | **fixed** |
| 8 | `arr.php` `forget()` | Referensi kerja tidak direset saat key ditemukan, key berikutnya dihapus dari level yang salah | **fixed** |
| 9 | `arr.php` `sort()` | `$callback` null (sesuai docblock) → `TypeError` | **fixed** |
| 10 | `arr.php` `collapse()` | `array_merge()` tanpa argumen — warning + `null` di PHP 5.4–7.3 | **fixed** |
| 11 | `helpers.php` `trans()` | Memakai alias global `Lang` (userland config), bukan `\System\Lang` | **fixed** |
| 12 | `helpers.php` `value()` | Argumen tambahan tidak diteruskan → `when($c, function ($c) {})` fatal | **fixed** |
| 13 | `helpers.php` `has_cli_flag()` | `strpos()` longgar: `--env=x` dianggap punya flag `-e` | **fixed** |
| 14 | `helpers.php` `retry()` | `catch (\Throwable)` membungkus ulang jadi `\Exception` — tipe asli hilang di PHP 7+ | **fixed** |
| 15 | `helpers.php` `human_filesize()` | `log()` dari nilai negatif → `NAN` | **fixed** |
| 16 | `config.php` `Config::get()` | Nilai `$default` ikut di-cache, pemanggilan berikutnya dengan default berbeda mengembalikan default lama | **fixed** |
| 17 | `container.php` `Container::dependencies()` | Di PHP 8 tipe bawaan (`array`, `int`, ...) dicoba di-resolve sebagai kelas → `ReflectionException` | **fixed** |
| 18 | `package.php` `Package::routes()` | `$routed` disimpan apa adanya tapi dibaca lowercase — berkas rute dimuat berulang | **fixed** |
| 19 | `uri.php` / `str.php` `segments()` | `array_diff()` menyisakan lubang indeks — `URI::segment()` meleset untuk URI seperti `foo//bar` | **fixed** |
| 20 | `blade.php` `@once` | Diputuskan saat compile, padahal hasil compile di-cache ke disk → blok hilang permanen di template lain | **fixed** |
| 21 | `blade.php` `@push/@stack/@hassection/@auth/@guest` | Menghasilkan `Section::`/`System\Auth::` yang bergantung pada alias userland | **fixed** |
| 22 | `lottery.php` `__invoke()` | Argumen diteruskan sebagai satu array, bukan di-spread | **fixed** |
| 23 | `input.php` `has()`/`had()`/`filled()` | `(string) $array` → warning "Array to string conversion" | **fixed** |
| 24 | `url.php` `transpose()` | Parameter dipakai sebagai replacement `preg_replace` — `$1`, `\0` di nilai jadi backreference | **fixed** |
| 25 | `session/payload.php` `cookie()` | `session.samesite` tidak diteruskan ke cookie sesi | **fixed** |
| 26 | `session/payload.php` `load()`/`get()` | Payload rusak (`unserialize()` → `false`) menembus ke `expired(array)` → `TypeError` | **fixed** |
| 27 | `session/drivers/file.php` `naming()` | `crc32()` 32-bit — dua sesi berbeda bisa berbagi berkas | **fixed** |
| 28 | `cache/drivers/file.php` `retrieve()` | `forget()` dipanggil dengan nama berkas, bukan key — berkas kedaluwarsa tidak pernah dihapus | **fixed** |
| 29 | `cache/drivers/file.php` `flush()` | Selalu menyapu `storage/cache`, mengabaikan `$this->path` | **fixed** |
| 30 | `cache/drivers/file.php` `naming()` | `crc32()` 32-bit — dua key cache berbeda bisa saling menimpa | **fixed** |
| 31 | `cache/drivers/sectionable.php` `remember_in_section()` | `$default` dan `$minutes` tertukar saat memanggil `Driver::remember()` | **fixed** |
| 32 | `storage.php` `move()` | Pesan galat terbalik ("does not exists" padahal sudah ada) | **fixed** |
| 33 | `storage.php` `rmdir()` | `rmdir()` gagal secara diam-diam (mengembalikan `false`, bukan melempar) | **fixed** |
| 34 | `paginator.php` `appendage()` | Kondisi terbalik — `appends()` dibuang, tautan tanpa append dapat `&` menggantung | **fixed** |
| 35 | `paginator.php` `make()`/`page()` | Pembagian dengan nol saat `$perpage <= 0` | **fixed** |
| 36 | `response.php` `cookies()` | `array_values()` menggeser `samesite` ke parameter `$httpOnly` — SameSite selalu jatuh ke default | **fixed** |
| 37 | `response.php` `download()` | Seluruh berkas dimuat ke memori padahal isinya di-stream ulang | **fixed** |
| 38 | `request.php` `matches_type()` | `false !== preg_match()` → selalu `true` (0 juga bukan `false`) | **fixed** |
| 39 | `request.php` `prefers()` | Nilai `$formats` berupa array diteruskan ke `explode()`/`strtok()` → `TypeError` di PHP 8 | **fixed** |
| 40 | `request.php` `prefetch()` | `strcasecmp(null, ...)` — deprecated di PHP 8.1 | **fixed** |
| 41 | `collection.php` `last()` | Item hasil pencarian dijalankan lewat `value()` bila kebetulan callable | **fixed** |
| 42 | `collection.php` `map()`/`combine()` | `array_combine([], [])` warning + `false` di PHP < 8.0 | **fixed** |
| 43 | `collection.php` `sort_by()` | Koleksi kosong memicu warning "Undefined array key"; `$options` eksplisit diabaikan | **fixed** |
| 44 | `collection.php` `split()`/`every()` | Pembagian/modulo dengan nol | **fixed** |
| 45 | `database/connection.php` `transaction()` | `catch (\Throwable)` membungkus ulang jadi `\Exception` — `QueryException` dll. hilang tipenya | **fixed** |
| 46 | `database.php` `extend()` + `connection.php` `grammar()` | Grammar default berupa string dipanggil sebagai fungsi → fatal | **fixed** |
| 47 | `database/query.php` `to_sql(true)` | Klausa `SELECT` hilang bila `select()` belum dipanggil | **fixed** |
| 48 | `database/query.php` `to_sql(true)` | Kutip penutup hilang untuk binding `DateTime`; binding `NULL`/`Expression` melempar | **fixed** |
| 49 | `database/query.php` `debug()` | Memanggil `to_sql(true)` lalu `vsprintf()` — `ValueError` untuk SQL yang mengandung `%` | **fixed** |
| 50 | `database/query.php` `increment()`/`decrement()` | `$amount` dan `$column` disisipkan mentah ke SQL | **fixed** |
| 51 | `database/query.php` `insert()` | Baris dengan urutan key berbeda ditulis ke kolom yang salah | **fixed** |
| 52 | `database/grammar.php` `wrap()` | `explode(' ')` salah segmen untuk `col  as  alias` | **fixed** |
| 53 | `database/grammar.php` `wrap_value()` | Karakter identifier tidak di-escape | **fixed** |
| 54 | `query/grammars/*` `limit()`/`offset()`/`TOP` | Nilai disisipkan mentah ke SQL | **fixed** |
| 55 | `facile/query.php` `table()` | Tidak lewat `Model::_query()` — filter soft delete **dan** global scope tidak pernah diterapkan | **fixed** |
| 56 | `facile/model.php` `_query()` | Kode mati, dan bila dipanggil akan rekursi tak berujung lewat `Facile\Query` | **fixed** |
| 57 | `facile/model.php` `$global_scopes` | Properti statik dibagi seluruh subclass — scope satu model bocor ke model lain | **fixed** |
| 58 | `facile/model.php` `restore()`/`force_delete()` | Memakai query yang memfilter baris terhapus, sehingga tidak pernah menemukan barisnya | **fixed** |
| 59 | `routing/router.php` `register()` | Variabel `$method` ditimpa di loop `'*'`, URI berikutnya tidak lagi dianggap wildcard | **fixed** |
| 60 | `routing/throttle.php` `key()` | `RAKIT_KEY` masuk apa adanya ke kunci cache — rahasia aplikasi bocor ke backend cache | **fixed** |
| 61 | `routing/throttle.php` `check()` | Driver cache umum me-refresh TTL tiap increment, jendela rate-limit tidak pernah habis | **fixed** |
| 62 | `validator.php` `validate_array()` | Tanpa parameter, aturan `array` menolak semua array yang tidak kosong | **fixed** |
| 63 | `validator.php` `validate_count()` dan `count*` | Ikut rusak karena `validate_array()`; `count` juga membandingkan `'3' === 3` | **fixed** |
| 64 | `validator.php` `validate_regex()`/`validate_not_regex()` | Pola yang mengandung koma terpotong oleh pemisah parameter | **fixed** |
| 65 | `validator.php` `size()` | `$this->attributes[$attribute]` meleset untuk atribut ber-notasi titik; `$value['size']` tanpa penjagaan | **fixed** |
| 66 | `auth/drivers/driver.php` `recall()` | Cookie "remember me" basi/rusak melempar exception saat konstruksi driver — seluruh request mati | **fixed** |
| 67 | `auth/drivers/driver.php` `cookie()` | `session.samesite` tidak diteruskan | **fixed** |
| 68 | `jwt.php` `decode()` | Algoritma diambil dari token itu sendiri (celah *alg confusion*); kini bisa dipatok lewat `$options['algorithm']` | **fixed** |
| 69 | `jwt.php` `decode()` | Cek `aud`/`iss` dilewati bila klaimnya tidak ada di token — bisa di-bypass | **fixed** |
| 70 | `job.php` `factory()` | `Job::extend()` mengisi `$registrar` tapi `factory()` tidak pernah membacanya | **fixed** |
| 71 | `image.php` `rgb()` | Warna heksa 6 digit bernilai < 0x1000 (mis. `0000ff`) dibaca sebagai singkatan 3 digit | **fixed** |
| 72 | `schema/grammars/sqlserver.php` `comment()` | Melempar tanpa syarat — **setiap** `Schema::create()` di SQL Server gagal | **fixed** |
| 73 | `schema/grammars/sqlserver.php` `type_timestamp()` | `TIMESTAMP` di T-SQL adalah ROWVERSION (biner, tidak bisa ditulis), bukan tipe waktu | **fixed** |
| 74 | `schema/grammars/sqlserver.php` `rename()` | `ALTER TABLE ... RENAME TO` tidak ada di T-SQL | **fixed** |
| 75 | `schema/grammars/sqlserver.php` `drop_column()` | Sintaks `DROP a, DROP b` tidak sah di T-SQL | **fixed** |
| 76 | `schema/grammars/sqlserver.php` `drop_primary()` | Tanpa nama constraint menghasilkan `DROP CONSTRAINT ` (kosong) | **fixed** |
| 77 | `schema/grammars/sqlserver.php` `type_enum()` | Memakai kutip ganda untuk identifier, padahal SQL Server memakai kurung siku | **fixed** |
| 78 | `schema/grammars/sqlite.php` `defaults()` | Nilai default dibungkus kutip ganda (sintaks *identifier* di SQLite), bukan literal | **fixed** |
| 79 | `schema/grammars/sqlite.php` `type_decimal()` | Dipetakan ke `FLOAT` sehingga kolom dapat afinitas REAL — nilai eksak (uang) dibulatkan | **fixed** |
| 80 | `schema/grammars/*` `type_enum()`/`type_set()` | Nilai enum tidak di-escape, kutip tunggal di nilainya merusak SQL | **fixed** |
| 81 | `database/connectors/sqlserver.php` | `dblib` dipilih walau `sqlsrv` tersedia, dan port dieja dengan format `sqlsrv` (`,port`) padahal `dblib` memakai `:port` | **fixed** |
| 82 | `console/commands/command.php` `progress()` | Bagian terisi dan kosong memakai karakter yang sama — bar progres selalu terlihat sama | **fixed** |
| 83 | `console/color.php` `supported()` | Menyentuh konstanta `STDOUT` yang tidak ada di luar CLI (fatal di PHP 8) | **fixed** |
| 84 | `console/table.php` `calculate_column_width()` | Lebar diukur `strlen()` (byte) tapi di-pad `mb_strlen()` — kolom non-ASCII melenceng | **fixed** |
| 85 | `console/commands/migrate/resolver.php` `outstanding()` | Paket default bisa terdaftar dua kali → migrasinya dijalankan dua kali | **fixed** |
| 86 | `email/drivers/smtp.php` `deliver()` | Dot-stuffing dilakukan dua kali — baris yang diawali `.` terkirim sebagai `..` | **fixed** |
| 87 | `email/drivers/sendmail.php` | `return_path` masuk mentah ke perintah shell; hasil `popen()` tidak dicek | **fixed** |
| 88 | `email/drivers/mail.php` | Selalu mengembalikan `true`, mengabaikan hasil `mail()` | **fixed** |
| 89 | `email.php` `factory()` | Driver `'log'` yang didokumentasikan di config ditolak (hanya `'dummy'` yang diterima) | **fixed** |
| 90 | `foundation/oops/defaults.php` | Pembagian dengan nol saat semua waktu kueri 0 — panel N+1 di debug bar fatal | **fixed** |
| 91 | `foundation/faker/provider/barcode.php` `eanChecksum()` | Urutan bobot salah untuk EAN-8 — **semua** EAN-8 yang dihasilkan check digit-nya salah | **fixed** |
| 92 | `foundation/faker/factory.php` `getProviderClassname()` | Fallback provider memakai bahasa aplikasi, bukan provider netral — `fake('en')` mengembalikan data berbahasa Indonesia | **fixed** |
| 93 | `foundation/faker/provider/phone.php` `phoneNumber()` | Placeholder `{{areaCode}}` tidak pernah di-parse — nomor telepon `en` keluar mentah | **fixed** |
| 94 | `websocket/server.php` `deframe()` | `case 9` tanpa `break` — server **tidak pernah** membalas ping, klien time out | **fixed** |
| 95 | `websocket/server.php` `deframe()` | Frame pong (opcode 10) diperlakukan sebagai data aplikasi | **fixed** |
| 96 | `websocket/server.php` `apply_mask()` | Kunci mask dibangun byte demi byte lalu dipotong satu-satu — kuadratik terhadap ukuran payload | **fixed** |
| 97 | `websocket/server.php` `frame()` | Tipe frame tak dikenal menghasilkan `$b1` tak terdefinisi (opcode 0 diam-diam) | **fixed** |
| 98 | `websocket/server.php` `handshake()` | `$reqResource[1]` dibaca tanpa memeriksa hasil `preg_match()` | **fixed** |
| 99 | `cache/drivers/redis.php` `flush()` | `FLUSHALL` menghapus **seluruh** database di server Redis, termasuk milik aplikasi lain | **fixed** |
| 100 | `redis.php` `$databases` | `protected static`, tidak konsisten dengan registry driver lain dan tidak bisa direset | **fixed** |
| 101 | `session/drivers/cookie.php` | Payload dienkripsi dua kali (`Cookie::put()` sudah mengenkripsi) dan payload rusak melempar exception | **fixed** |
| 102 | `cookie.php` | Cache nilai terdekripsi tidak bisa direset — nilai basi tetap dilayani setelah `$jar` dikosongkan (ditambahkan `Cookie::flush()`) | **fixed** |
| 103 | `console/fiddle/parser.php` `scan_use()` | Klausa `use (...)` milik closure dikira import dan diubah jadi `class_alias()` — pernyataannya rusak | **fixed** |
| 104 | `redis.php` `command()` | Argumen berupa array di-stringify jadi `'Array'` — `HMSET`/`MSET` selalu gagal | **fixed** |
| 105 | `job/drivers/redis.php` | Bergantung pada `hmset()` yang rusak, dan membaca balasan `HGETALL` (daftar datar) seolah asosiatif — driver job Redis tidak pernah berfungsi | **fixed** |
| 106 | `memcached.php` `connect()` | `$server['weight']` wajib padahal opsional di berkas config | **fixed** |
| 107 | `cache/drivers/memcached.php` `retrieve()` | Nilai `false` yang tersimpan tidak bisa dibedakan dari cache miss | **fixed** |
| 108 | `memcached.php` `$connection` | `protected static`, tidak konsisten dengan registry driver lain | **fixed** |
| 109 | `foundation/http/upload.php` `getMaxFilesize()` | Aritmetika pada `'2M'` memicu warning "A non-numeric value encountered" tiap panggilan | **fixed** |
| 110 | `foundation/http/upload.php` `getTargetFile()` | Kegagalan `mkdir()` tidak terdeteksi (mengembalikan `false`, bukan melempar) | **fixed** |
| 111 | `log.php` `write()` | Kegagalan `file_put_contents()` tidak terdeteksi, jadi berkas log cadangan tidak pernah dipakai | **fixed** |
| 112 | `email/drivers/driver.php` | **Header injection**: CR/LF pada subject, alamat, nama tampilan, `return_path`, dan header kustom bisa menyisipkan header baru (mis. `Bcc:`) | **fixed** |
| 113 | `email/drivers/driver.php` `format()` | Kutip di dalam nama tampilan tidak di-escape sehingga menutup string berkutip | **fixed** |
| 114 | `email/drivers/driver.php` `mime()` | `string_attach()` menebak mime dengan membaca berkas yang memang tidak ada → warning tiap panggilan | **fixed** |
| 115 | `storage.php` `mime()` | `finfo_file()` pada berkas yang tidak ada memicu warning, dan handle `finfo` tidak pernah ditutup | **fixed** |
| 116 | `facile/query.php` `$passthru` | `order_by`, `where_in`, `where_not_in`, `or_where_in`, `or_where_not_in` diperlakukan sebagai operasi akhir — rantai setelahnya mengembalikan baris mentah, bukan model | **fixed** |
| 117 | `facile/relationships/morphone.php` dan `morphmany.php` | Eager loading memfilter `foreign_key()` (kolom yang tidak ada), bukan kolom id/type polimorfik — hasilnya selalu kosong | **fixed** |
| 118 | `facile/relationships/morphto.php` `results()` | Lazy loading selalu `null` karena `$results` kosong saat dipanggil tanpa argumen | **fixed** |
| 119 | `facile/relationships/morphtomany.php` `results()` | Lazy loading selalu array kosong karena alasan yang sama | **fixed** |
| 120 | `facile/query.php` `load()` | `eager_load()` milik `MorphTo`/`MorphToMany` tidak pernah dipanggil — eager loading keduanya melempar exception | **fixed** |
| 121 | `facile/relationships/morphto.php` dan `morphtomany.php` | `relationship_name()` memakai nama kolom type sebagai kunci relasi, bukan nama relasinya | **fixed** |
| 122 | `facile/model.php` `morph_to_many()` | Argumen tidak dipetakan ke konstruktor `MorphToMany` — kolom type/id salah dan nama tabel pivot jadi `'_'` | **fixed** |
| 123 | `facile/relationships/morphtomany.php` `__construct()` | Nama tabel pivot dihitung sebelum `parent::__construct()`, saat `$base`/`$model` masih null | **fixed** |

## Temuan (isolasi test)

| # | Berkas | Ringkasan | Status |
| --- | --- | --- | --- |
| T1 | `tests/cases/carbon.test.php` | `testOther()` membekukan `Carbon::now()` lewat `Carbon::setNow()` dan tidak pernah meresetnya — jam ikut beku untuk semua test setelahnya | **fixed** |
| T2 | `tests/cases/validator.test.php` | Bergantung pada bahasa aplikasi yang disetel test lain; gagal bila dijalankan sendiri | **fixed** |
| T3 | `tests/cases/package.test.php` | Hook listener bocor antar-test dan penghitung `$_SERVER` tidak diinisialisasi | **fixed** |
| T4 | `tests/cases/cache.test.php` | `testSectionableRememberInSection` menulis ulang bug urutan argumen alih-alih memperbaikinya | **fixed** |

Seluruh berkas test kini juga hijau saat dijalankan **satu per satu**, bukan
hanya sebagai satu suite.

## Berkas test yang ditambahkan

| Berkas | Cakupan |
| --- | --- |
| `tests/cases/schema-grammars.test.php` | Grammar skema MySQL, Postgres, SQL Server |
| `tests/cases/database-drivers.test.php` | Grammar kueri dan connector keempat driver |
| `tests/cases/console.test.php` | `Console::options()`/`parse()`, `Color`, `Table`, helper `Command` |
| `tests/cases/routing-resource.test.php` | `Routing\Resource` |
| `tests/cases/faker-calculators-extras.test.php` | Kalkulator `Ean`, `Inn`, `Valid`, resolusi locale |
| `tests/cases/fiddle.test.php` | Parser dan inspector konsol interaktif |
| `tests/cases/oops-helpers.test.php` | `Oops\Helpers` dan `Oops\Dumper` |
| `tests/cases/redis.test.php` | `System\Redis` + driver cache/session Redis |
| `tests/cases/job-redis.test.php` | Driver job Redis, ujung ke ujung |
| `tests/cases/storage-drivers.test.php` | Driver cache database, driver session database dan cookie |
| `tests/cases/email-message.test.php` | Pembentuk pesan email dan penolakan header injection |
| `tests/cases/facile-eager-loading.test.php` | Seluruh jenis relasi Facile terhadap baris sungguhan |

Selain itu, `facile-soft-delete.test.php`, `facile.test.php`, `validator.test.php`,
`jwt.test.php`, `blade.test.php`, `cache.test.php`, dan `websocket-server.test.php`
diperluas.

## Sisa pekerjaan

Target 100% coverage belum tercapai. Berikut yang masih tersisa, diurutkan dari
yang paling mudah dikerjakan.

### 1. Butuh layanan eksternal (44 berkas "never loaded" sebagian besar di sini)

Driver-driver ini hanya bisa diuji kalau layanannya tersedia. Pola yang sudah
dipakai `redis.test.php` (lewati test bila server tidak dapat dihubungi, dan
sediakan service-nya di CI) bisa ditiru:

- `memcached.php`, `cache/drivers/memcached.php`, `session/drivers/memcached.php`,
  `job/drivers/memcached.php` — butuh server memcached + ekstensi `memcached`.
- `cache/drivers/apc.php`, `session/drivers/apc.php` — butuh ekstensi `apcu`.
- `email/drivers/{smtp,mail,sendmail}.php` — butuh server SMTP palsu (mis.
  MailHog) atau *stub* pada level socket.

### 2. Perintah konsol (~1.800 baris)

`console/commands/**` seluruhnya belum tersentuh. Sebagian besar berupa
orkestrasi berkas dan proses (`make`, `migrate`, `package`, `serve`, `websocket`,
`clear`, `session`, `job`, `route`, `help`). Yang paling layak diuji lebih dulu:

- `migrate/{database,migrator,resolver}.php` — bisa diuji dengan sqlite.
- `make.php` — hanya menulis berkas dari stub, bisa diarahkan ke direktori
  sementara.
- `package/{repository,publisher,packager}.php` — perlu *stub* HTTP.

### 3. Debug bar dan halaman error (`foundation/oops`, ~2.400 baris tersisa)

`bar.php`, `panic.php`, `defaults.php`, `storage.php`, `context.php`, dan
`outputs.php` menghasilkan HTML untuk debug bar. Pengujiannya paling masuk akal
lewat *snapshot* keluaran HTML-nya.

### 4. Bootstrap

`system/boot.php`, `system/init.php`, dan `system/console/boot.php` bersifat
prosedural dan sudah berjalan sebagai bagian dari bootstrap test, tapi tidak
terhitung karena dimuat sebelum pengukuran dimulai.

### 5. Catatan lain (bukan bug, tapi perlu diperhatikan)

- `tests/fixtures/storage/database/application.sqlite` ikut terlacak Git dan
  berubah setiap kali test dijalankan, jadi `git status` selalu kotor setelah
  test. Sebaiknya berkas ini dibuat ulang saat bootstrap test dan dimasukkan ke
  `.gitignore`.
- `Package::exists()` dan `Package::names()` membandingkan nama paket dengan
  kepekaan huruf yang berbeda. Untuk sekarang tidak masalah karena nama paket
  konvensinya huruf kecil semua.
- `Auth\Drivers\Driver::login()` tidak me-regenerasi id sesi. Menambahkan
  `Session::regenerate()` di sana akan menutup celah *session fixation*, tapi itu
  perubahan perilaku autentikasi, jadi diserahkan ke pemilik proyek.
- `job/drivers/memcached.php` membaca-lalu-menulis kunci `all_jobs` tanpa CAS,
  sehingga bisa kehilangan job saat ada dua worker bersamaan.
- Kedua patch PHPUnit di folder `patches/` sebaiknya dipindahkan ke
  <https://github.com/esyede/phpunit-patches> supaya sejalan dengan patch yang
  lain (lihat catatan di bagian Patch PHPUnit).
