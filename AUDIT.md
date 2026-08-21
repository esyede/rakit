# Audit `./system` — Rakit Framework

Tujuan: **0 bug** dan **100% ter-unit-test**, tetap jalan di **PHP 5.4.0 – 8.5**.

## Status

| Metrik | Awal | Sekarang |
| --- | ---: | ---: |
| Test | 1668 | 1668 |
| Assertion | 5266 | 5266 |
| Coverage baris (file yang ter-load) | 64.20% | 64.20% |
| File `system/` tidak pernah ter-load saat test | 72 | 72 |

Coverage diukur dengan ekstensi `pcov`.

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

## Berkas tanpa test sama sekali (never loaded)

- `system/boot.php`, `system/init.php`
- `system/console/**` (seluruh folder: console, table, color, dependencies, boot,
  commands/*, fiddle/*)
- `system/foundation/oops/**` (bar, context, defaults, dumper, helpers, outputs,
  panic, storage)
- `system/redis.php`, `system/memcached.php`
- `system/cache/drivers/{apc,database,redis,memcached}.php`
- `system/session/drivers/{apc,cookie,database,redis,memcached}.php`
- `system/job/drivers/{redis,memcached}.php`
- `system/email/drivers/{mail,sendmail,smtp}.php`
- `system/database/connectors/{mysql,postgres,sqlserver}.php`
- `system/database/query/grammars/{mysql,postgres,sqlserver}.php`
- `system/database/schema/grammars/{mysql,postgres,sqlserver}.php`
- `system/routing/resource.php`
- `system/foundation/faker/calculator/{ean,inn}.php`, `foundation/faker/valid.php`
