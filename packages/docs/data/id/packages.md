# Paket

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Membuat Paket](#membuat-paket)
-   [Mendaftarkan Paket](#mendaftarkan-paket)
-   [Paket & Autoloading](#paket--autoloading)
-   [Booting Paket](#booting-paket)
-   [Routing Ke Paket](#routing-ke-paket)
-   [Menggunakan Paket](#menggunakan-paket)
-   [Aset Paket](#aset-paket)
-   [Menginstall Paket](#menginstall-paket)
-   [Mengupgrade Paket](#mengupgrade-paket)
-   [Menghapus Paket](#menghapus-paket)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Paket adalah cara sederhana untuk memisahkan kode kedalam unit-unit yang lebih kecil sehingga
lebih mudah diorganisir dan digunakan kembali di aplikasi lain.

Sebuah paket bisa memiliki controller, view, config, route, migration, command, dan lain-lain
miliknya sendiri. Sebuah paket bisa berupa apapun, mulai dari library database, sistem otentikasi
sampai CMS.

Faktanya, folder `application/` juga merupakan sbuah paket, yaitu paket default. Bahkan halaman
dokumentasi yang sedang anda baca ini juga merupakan sebuah paket.

<a id="membuat-paket"></a>

## Membuat Paket

Langkah pertama untuk membuat paket adalah membuat folder baru didalam folder `packages/`.
Untuk contoh ini, mari kita buat paket bernama `'admin'`, yang berisi halaman administrasi
aplikasi kita.

File `boot.php` menyediakan beberapa konfigurasi dasar yang membantu menentukan bagaimana
aplikasi kita akan berjalan.

Kita juga akan membuat file `boot.php` dalam folder paket kita untuk tujuan yang sama.
File ini akan dijalankan setiap kali paket di-boot (dimuat).

#### Membuat file `boot.php` milik paket:

```php
// file: packages/admin/boot.php

Autoloader::namespaces([
    'Admin' => Package::path('admin').'libraries',
]);
```

Kode diatas memberi tahu rakit bahwa kelas yang bernamespace `Admin` harus dimuat
dari direktori `libraries/` milik paket kita.

Anda dapat melakukan apa pun yang anda inginkan di file `boot.php`, tetapi file ini
biasanya hanya digunakan untuk mendaftarkan kelas ke autoloader.

Dan sebetulnya, anda **tidak diharuskan** membuat file `boot.php` untuk paket anda.

Selanjutnya, kita akan belajar bagaimana cara mendaftarkan paket kita ke rakit!

<a id="mendaftarkan-paket"></a>

## Mendaftarkan Paket

Setelah kita membuat paket admin, kita perlu mendaftarkannya ke rakit.

Bukalah file `application/packages.php`. File itulah tempat dimana kita bisa mendaftarkan paket kita.

Mari kita daftarkan paket admin kita:

#### Mendaftarkan paket sederhana:

```php
return ['admin'];
```

Menurut konvensi, kode diatas akan memberitahu rakit bahwa paket `admin` berada di
folder `packages/admin/`. Tetapi, kita juga bisa mengubah lokasinya ke lokasi lain jika diperlukan:

#### Mendaftarkan paket dengan lokasi kustom:

```php
return [

    'admin' => ['location' => 'backend/admin'],

];
```

Sekarang rakit akan mencari paket kita di folder `packages/backend/admin`.

<a id="paket--autoloading"></a>

## Paket & Autoloading

Biasanya, file `boot.php` milik paket hanya berisi pendaftaran autoloading kelas.
Jadi, anda hanya perlu mengubah file `boot.php` dan mendefinisikan mapping kelas-kelas
milik paket anda via array. Begini caranya:

#### Mendefinisikan mapping autoloader untuk paket:

```php
return [

    'admin' => [
        'autoloads' => [

            'map' => [
                'Admin' => '(:package)/admin.php',
            ],
            'namespaces' => [
                'Admin' => '(:package)/libraries',
            ],
            'directories' => [
                '(:package)/models',
            ],

        ],
    ],

];
```

Perhatikan bahwa masing-masing key dari array diatas sesuai dengan nama-nama method
di kelas [autoloader](/docs/id/autoloading).

Benar, value dari masing-masing array konfigurasi diatas akan secara otomatis be dioper
ke masing-masing method yang sesuai di kelas `Autoloader`.

Anda pasti juga melihat placeholder `(:package)` kan? Untuk kenyamanan, placeholder ini juga
akan digantikan dengan path ke paket anda secara otomatis.

<a id="booting-paket"></a>

## Booting Paket

Jadi sampai disini paket kita sudah dibuat dan didaftarkan, tetapi kita belum dapat menggunakannya.
Kita harus memuatnya (boot) terlebih dahulu:

#### Booting paket `'admin'`:

```php
Package::boot('admin');
```

Method ini akan menjalankan file `boot.php` milik paket admin,
yangmana file tersebut akan mendaftarkan kelas-kelas di paket admin ke autoloader.

Pemanggilan method `boot()` tersebut juga sekaligus akan memuat file `routes.php` milik
paket jika paket kita mempunyai file `routes.php`.

> Paket hanya akan booting sekali saja. Pemanggilan berikutnya ke method `boot()` akan diabaikan.

Jika anda ingin menggunakan paket di seluruh aplikasi anda, anda mungkin perlu melakukan
booting paket pada setiap request. Ini tentu kurang menyenangkan. Jika demikian, anda dapat
memerintahkan paket agar ia selalu booting secara otomatis. Caranya dengan menambahkan
konfigurasi `autoboot` ke file `application/packages.php` seperti berikut:

#### Memerintahkan paket agar booting otomatis:

```php
return [

    'admin' => ['autoboot' => true],

];
```

Anda tidak selalu harus mendefinisikan `'autoboot'` secara eksplisit supaya paket anda
bisa booting secara otomatis. Bahkan, anda bisa membuat _seolah-olah_ paket tersebut
booting secara otomatis.

Misalnya, jika anda memanggil views, config, language, route atau middleware milik sebuah paket,
maka paket tersebut akan boot sendiri secara otomatis.

Setiap kali sebuah paket melakukan booting, sebuah event akan dijalankan. Event ini bisa anda
gunakan ketika anda perlu melakukan sesuatu sebelum sebuah paket selesai booting:

<a href="#listen-event-pengaktifan-paket"></a>

#### Listen event pengaktifan paket:

```php
Event::listen('rakit.booted: admin', function () {
    // Booting paket 'admin' berhasil!
});
```

Anda juga bisa _"membekukan"_ sebuah paket agar ia tidak bisa booting.

#### Membekukan paket sehingga ia tidak akan pernah bisa booting:

```php
Package::freeze('admin');
```

<a id="routing-ke-paket"></a>

## Routing Ke Paket

Silahkan merujuk ke halaman [routing paket](/docs/id/routing#routing-paket) dan
[controller paket](/docs/id/controllers#controller-paket) untuk panduan lebih detail mengenai
mekanisme routing paket.

<a id="menggunakan-paket"></a>

## Menggunakan Paket

Seperti yang telah disebutkan sebelumnya, paket bisa memiliki controller, view, config,
route, migration, command, dan lain-lain miliknya sendiri, seperti struktur direktori di
folder `application/`.

Rakit menggunakan sintaks `::` (kolon ganda) untuk memuat item-item ini. Mari lihat contohnya:

#### Memuat sebuah view milik paket:

```php
return View::make('admin::dashboard');
```

#### Mengambil sebuah item config milik paket:

```php
return Config::get('admin::uploads.max_size');
```

#### Mengambil item config language milik sebuah paket:

```php
return Lang::line('admin::themes.default_theme');
```

Terkadang, anda ingin melihat informasi _"meta-data"_ yang lebih lengkap tentang sebuah paket,
seperti memeriksa ada atau tidaknya sebuah paket, dimana lokasinya, atau mungkin kita butuh
seluruh meta-data yang dimilikinya. Begini cara melihatnya:

#### Memeriksa ada atau tidaknya sebuah paket:

```php
if (Package::exists('admin')) {
    // Paket 'admin' ada!
}
```

#### Mengambil lokasi instalasi sebuah paket:

```php
$location = Package::path('admin');
// dd($location);
```

#### Mengambil meta-data sebuah paket:

```php
$metadata = Package::get('admin');
// dd($metadata);
```

#### Mengambil daftar nama paket yang terinstall:

```php
$names = Package::names();
// dd($names);
```

<a id="aset-paket"></a>

## Aset Paket

Jika paket yang ingin anda buat mengandung view, pastinya paket tersebut memiliki aset
seperti CSS, JavaScript dan Gambar yang harus disertakan.

Gampang, Cukup buat folder `assets/` didalam paket anda dan taruh file-file aset anda kedalamnya.
Jadi, misalnya paket anda bernama `admin`, maka taruh file-file aset anda ke folder `admin/assets/`.

Loh, nanti bagaimana jika pengguna paket saya ingin menggunakan asetnya? sedangkan
folder `admin/assets/` kan tidak readable?

Tenang saja, rakit sudah dibekali dengan perintah console sederhana untuk menyalin
aset paket tersebut ke direktori `assets/` di root path. Begini caranya:

#### Mem-publish aset milik sebuah paket:

```bash
php rakit package:publish <nama-paket>
```

Perintah ini akan membuat sebuah subfolder di `assets/packages/` sesuai nama paket yang diinstall.
Misalnya, jika paket yang diinstall bernama `admin`, maka akan dibuat
folder `assets/packages/admin`, yang akan berisi file-file salinan dari file aset bawaan paket admin.

Lalu bagaimana cara mendapatkan path ke aset paket setelah dipublish?
Mudah saja, silahkan gunakan method `URL::to_asset()` atau helper `asset()` seperti berikut:

```php
<link href="<?php echo URL::to_asset('packages/themable/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo URL::to_asset('packages/themable/js/app.min.js') ?>"></script>
```

atau,

```php
<link href="<?php echo asset('packages/themable/css/app.min.css') ?>" rel="stylesheet"/>
<script src="<?php echo asset('packages/themable/js/app.min.js') ?>"></script>
```

<a id="menginstall-paket"></a>

## Menginstall Paket

Tentu saja, anda boleh menginstall paket secara manual dengan mendownload arsipnya dan mengekstraknya
ke folder `packages/`. Akan tetapi, ada cara yang lebih asyik untuk menginstall paket, yaitu
via [rakit console](/docs/id/console).

Rakit menggunakan mekanisme ekstraksi Zip sederhana untuk penginstalan paket. Begini caranya:

#### Menginstall paket via rakit console:

```bash
php rakit package:install themable
```

> Pastikan anda sudah mengaktifkan ekstensi [cURL](https://www.php.net/manual/en/book.curl.php)
> sebelum menjalankan perintah ini.

Mantap! sekarang paket yang anda mau sudah terinstall, langkah selanjutnya tinggal
[mendaftarkan](#mendaftarkan-paket) paket tersebut ke rakit.

Ingin tahu paket apa saja yang bisa anda install? silahkan kunjungi
[repositori resmi kami](https://rakit.esyede.my.id/repositories)

<a id="mengupgrade-paket"></a>

## Mengupgrade Paket

Ketika anda mengupgrade paket, Rakit akan menghapus file-file paket versi lama dan menginstall
rilis stable terbaru dari paket tersebut.

#### Mengupgrade paket via console:

```bash
php rakit package:upgrade <nama-paket>
```

> Karena seluruh file paket lama akan dihapus ketika diupgrade, anda harus pastikan bahwa setiap
> perubahan yang anda buat pada paket tersebut harus sudah di-backup sebelum menjalankan upgrade.

> Sebaiknya, jika anda perlu mengubah beberapa config sebuah paket, jangan mengubahnya
> secara langsung, lakukan perubahan tersebut dengan me-listen event
> [rakit.booted](#rakit.booted). Dan selalu letakkan kode-kode
> seperti ini di file `application/boot.php` anda.

<a href="rakit.booted"></a>

#### Me-listen event `'rakit-booted'` milik paket `admin`:

```php
// File: application/boot.php

Event::listen('rakit.booted: admin', function () {
    Config::set('admin::general.pagename', 'Panel Admin');
});
```

<a id="menghapus-paket"></a>

## Menghapus Paket

Selain menginstall dan mengupgrade paket, tentu anda juga dapat menghapus paket ketika ia
sudah tidak anda gunakan lagi.

Tersedia 2 cara untuk melakukannya, yaitu cara otomatis via console dan cara manual. Mari kita coba!

#### Menghapus paket secara otomatis:

Pertama, jika paket yang hendak anda hapus melakukn migrasi ke database, anda perlu menghapus
tabel-tabel database yang dulu pernah dibuatnya:

```bash
php rakit migrate:reset <nama-paket>
```

> Indikasi sebuah paket melakukan operasi migrasi database adalah paket tersebut
> memiliki folder `migrations/` yang berisi file-file migrasi.

Selanjutnya, kita perlu bersihkan file dan aset bawaan paketnya:

```bash
php rakit package:uninstall <nama-paket>
```

Perintah ini akan menghapus folder `packages/<nama-paket>/` dan
folder `assets/packages/<nama-paket>/` dari apklikasi anda.

Terakhir, tinggal hapus registri paket tersebut dari file `application/packages.php`.

#### Menghapus paket secara manual:

Untuk menghapus paket secara manual, anda perlu mengulangi perintah-perintah diatas secara manual:

1. Jika paketnya menjalankan migrasi, hapus tabel-tabel migrasinya dari database anda.
2. Hapus folder `assets/packages/<nama-paket>/` dan `packages/<nama-paket>/` jika ada.
3. Terakhir, hapus registri paketnya dari file `application/packages.php`
