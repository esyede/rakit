# Migrasi Database

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Menyiapkan Database](#menyiapkan-database)
-   [Membuat File Migrasi](#membuat-file-migrasi)
-   [Menjalankan Migrasi](#menjalankan-migrasi)
-   [Roll Back](#roll-back)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Katakanlah anda bekerja dalam tim, dan masing-masing individu di tim anda memiliki database lokal untuk pengembangan.

Seorang anggota tim membuat perubahan ke database, dia menambahkan kolom baru. Anda mengambil kode tersebut dengan Git dan mencobanya di lokal, lalu aplikasi anda rusak karena anda belum memiliki kolom baru tersebut. Apa yang akan anda lakukan?

Migrasi adalah jawabannya. Migrasi bisa digunakan sebagai version control untuk database anda. Mari kita gali lebih dalam untuk mencari tahu cara menggunakannya!

<a id="menyiapkan-database"></a>

## Menyiapkan Database

Sebelum menjalankan migrasi, kami perlu melakukan beberapa pekerjaan pada database anda. Rakit menggunakan tabel khusus untuk mencatat migrasi mana yang sudah berjalan. Untuk membuat tabel tersebut, cukup jalankan perintah konsol berikut:

**Membuat tabel catatan migrasi:**

```bash
php rakit migrate:install
```

> Disini kami menganggap bahwa anda telah memiliki akses ke PHP CLI secara global.

<a id="membuat-file-migrasi"></a>

## Membuat File Migrasi

Anda dapat dengan mudah membuat migrasi melalui [console](/docs/id/console) seperti berikut ini:

**Membuat sebuah file migrasi:**

```bash
php rakit make:migration create_users_table
```

Sekarang, coba buka folder `application/migrations/`. Anda akan melihat file migrasi yang baru anda buat disana! Perhatikan bahwa nama filenya diawali dengan timestamp. Ini memungkinkan Rakit untuk menjalankan migrasi anda dalam urutan yang benar.

Anda juga dapat membuat file migrasi untuk sebuah package.

**Membuat file migrasi untuk sebuah package:**

```bash
php rakit make:migration nama_package::create_users_table
```

_Bacaan lebih lanjut:_

-   [Schema Builder](/docs/id/database/schema)

<a id="menjalankan-migrasi"></a>

## Menjalankan Migrasi

**Menjalankan seluruh file migrasi milik aplikasi dan package:**

```bash
php rakit migrate
```

**Menjalankan seluruh file migrasi milik aplikasi:**

```bash
php rakit migrate application
```

**Menjalankan seluruh file migrasi milik sebuah package:**

```bash
php rakit migrate nama_package
```

<a id="roll-back"></a>

## Roll Back

Saat anda melakukan roll back, seluruh operasi migrasi anda akan dikembalikan. Jadi, jika perintah migrasi terakhir menjalankan 122 operasi migrasi, maka 122 operasi tersebut akan di dikembalikan.

**Roll back ke migrasi terakhir:**

```bash
php rakit migrate:rollback
```

**Reset seluruh migrasi:**

```bash
php rakit migrate:reset
```

**Reset dan jalankan ulang seluruh migrasi:**

```bash
php rakit migrate:rebuild
```
