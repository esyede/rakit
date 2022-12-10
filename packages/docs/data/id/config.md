# Konfigurasi Runtime

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Mengambil Item Konfigurasi](#mengambil-item-konfigurasi)
-   [Menyetel Item Konfigurasi](#menyetel-item-konfigurasi)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Terkadang anda mungkin perlu mengubah opsi konfigurasi saat runtime. Contohnya ketika anda
memiliki dua buah koneksi database dan ingin berpindah koneksi secara dinamis.

Untuk kebutuhan ini, anda dapat memanfaatkan komponen `Config`, ia menggunakan sintaks **dot**
untuk mengakses file dan opsi konfigurasinya.

<a id="mengambil-item-konfigurasi"></a>

## Mengambil Item Konfigurasi

#### Mengambil sebuah opsi konfigurasi:

Mengambil konfigurasi url aplikasi:

```php
$value = Config::get('application.url');
```

Secara default method ini akan mereturn `NULL` jika datanya tidak ketemu.

Akan tetapi, jika anda ingin mengganti default return ini, cukup oper default return value yang
anda kehendaki ke parameter ke-dua seperti brikut:

```php
$value = Config::get('application.timezone', 'UTC');
```

#### Mengambil seluruh opsi milik sebuah file konfigurasi:

```php
$options = Config::get('database');
```

#### Mengambil seluruh data konfigurasi:

```php
$options = Config::all();
```

<a id="menyetel-item-konfigurasi"></a>

## Menyetel Item Konfigurasi

#### Menyetel sebuah opsi konfigurasi:

Menyetel komponen cache agar ia menggunakan driver APC.

```php
Config::set('cache.driver', 'apc');
```
