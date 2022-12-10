# Autoloading

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Mendaftarkan Folder](#mendaftarkan-folder)
-   [Mendaftarkan Mapping](#mendaftarkan-mapping)
-   [Mendaftarkan Namespace](#mendaftarkan-namespace)
-   [Composer Autoloader](#composer-autoloader)
    -   [Catatan untuk folder vendor](#catatan-untuk-folder-vendor)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Autoloading memungkinkan anda untuk me-lazyload file kelas (memuat file kelas saat dibutuhkan saja)
tanpa secara eksplisit memanggil `reuqire()` ataupun `include()`.

Jadi, hanya kelas yang benar-benar anda butuhkan saja yang akan dimuat di aplikasi anda, dan anda
bisa langsung menggunakan kelas yang anda mau tanpa harus capek-capek memuatnya secara manual.

Secara default, folder `application/models/` dan `application/libraries/` telah di autoload via
file `application/boot.php` seingga anda tidak perlu capek meregistrasikannya secara manual.

Autoloader di rakit mengitkuti konvensi `nama kelas sama dengan nama file`, dimana nama file
ditulis dengan huruf kecil seluruhnya.

Jadi misalnya, kelas `User` yang ditaruh di folder `models/` harus ditaruh didalam file
bernama `user.php` agar bisa dimuat secara otomatis.

Anda juga boleh menaruhnya kedalam subfolder. Cukup beri namespace kelasnya mengikuti
struktur folder yang anda buat. Jadi, kelas `Entities\User` harus ditaruh ke
file `entities/user.php` didalam folder `models/`.

<a id="mendaftarkan-folder"></a>

## Mendaftarkan Folder

Seperti yang sudah dijelaskan diatas, folder `models/` dan `libraries/` secara default
sudah didaftarkan ke autoload; tetapi, anda juga dapat mendaftarkan folder manapun yang anda
suka dengan menggunakan konvensi yang sama:

#### Mendaftarkan beberapa folder ke autoloader:

```php
Autoloader::directories([
	path('app').'classes',
	path('app').'utilities',
]);
```

<a id="mendaftarkan-mapping"></a>

## Mendaftarkan Mapping

Terkadang anda mungkin ingin memetakan kelas secara manual ke file terkaitnya. Ini adalah cara
memuat kelas yang paling efisien karena si autoloader tidak harus melakukan scanning folder
untuk mencari dimana lokasi kelas anda berada:

#### Mendaftarkan mapping ke autoloader:

```php
Autoloader::map([
	'Forms\Bootstrap' => path('app').'classes/forms/bootstrap.php',
	'Forms\Bulma'     => path('app').'classes/forms/bulma.php',
]);
```

<a id="mendaftarkan-namespace"></a>

## Mendaftarkan Namespace

Banyak library pihak ketiga menggunakan standar PSR-4 dan PSR-0 untuk meng-autoload kelasnya.
[PSR-4](https://www.php-fig.org/psr/psr-4/) dan [PSR-0](https://www.php-fig.org/psr/psr-0/)
menyatakan bahwa nama kelas harus cocok dengan nama file mereka, termasuk besar-kecil huruf
dan struktur folder ditunjukkan oleh namespace.

Jika anda menggunakan library dengan konvensi seperti ini, cukup daftarkan root namespace
dan lokasi foldernya ke autoloader. Rakit akan menangani sisanya.

#### Mendaftarkan namespace ke autoloader:

```php
Autoloader::namespaces([
	'Doctrine' => path('libraries').'Doctrine',
]);
```

Sebelum fitur namespace ada di PHP, Banyak library yang menggunakan _underscore_ sebagai
indikator folder mereka.

Jika anda ingin menggunakan library dengan konvensi seperti ini, anda juga tetap bisa
mendaftarkannya ke autoloader.

Contohnya, jika anda ingin menggunakan [SwiftMailer](https://github.com/swiftmailer/swiftmailer)
versi lama, anda pasti bisa perhatikan bahwa seluruh nama kelasnya diawali dengan `Swift_`.

Jadi, yang perlu kita daftarkan ke autoloader adalah kata `Swift` tersebut, dimana kata itu
adalah root namespace miliknya.

#### Mendaftarkan kelas _underscore_ ke autoloader:

```php
Autoloader::underscored([
	'Swift' => path('libraries').'Swift_Mailer',
]);
```

<a id="composer-autoloader"></a>

## Composer Autoloader

Tentunya anda pernah menggunakan [Composer](https://getcomposer.org). Composer umumnya
sudah membawa autoloader tersendiri, yang pada keadaan default terletak di `vendor/autoload.php`.

Jadi, tugas kita disini hanya tinggal meng-include file tersebut ke aplikasi kita agar library yang
diinstall olehnya bisa dkenali oleh rakit.

Caranya cukup mudah, cukup edit di file `application/config/aplication.php` dan isi
opsi `composer_autoload` dengan <ins>absolute path</ins> tempat file autoload itu berada.

Jadi misalkan file autoload anda berada di `<root>/vendor/autoload.php` maka diisi seperti ini:

```php
'composer_autoload' => path('base').'vendor/autoload.php',
```

> Jika file autoload gagal dimuat karena path salah ataupun sebab yang lain,
> aplikasi anda akan terus berjalan tanpa menampilkan error.

<a id="catatan-untuk-folder-vendor"></a>

### Catatan untuk folder vendor

Patut diingat bahwa pada keadaan default, **tidak ada proteksi** yang disediakan jika
folder `vendor/` ditaruh di root folder, yang berarti seluruh file dan subfolder didalamnya
dapat diakses olek publik.

Hal ini tentu sangat berbahaya karena mereka akan tahu library apa saja yang anda gunakan.
Untuk itu, kami menyediakan beberapa opsi untuk menangani hal ini:

#### Opsi 1: Rewrite URL

Jika anda menggunakan Apache atau Nginx, ikuti panduan
[mempercantik url](/docs/id/install#mempercantik-url) karena pada fitur tersebut, kami telah
sekaligus menyediakan rule untuk memproteksi folder vendor.

#### Opsi 2: Taruh diatas document root

Jika hosting anda memperbolehkan upload file ke folder diatas document root, taruh folder
vendor anda disitu, lalu ubah konfigurasi `composer_autoload` anda menjadi seperti berikut:

```php
'composer_autoload' => dirname(path('base')).'/vendor/autoload.php',
```

Dengan begitu, folder vendor anda tdak akan bisa diakses oleh publik dan anda tetap dapat
menggunakan library yang anda install via composer.
