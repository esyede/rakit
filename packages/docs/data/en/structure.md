# Struktur Direktori

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Hirarki Folder](#hirarki-folder)
    -   [Folder application](#folder-application)
    -   [Folder assets](#folder-assets)
    -   [Folder packages](#folder-packages)
    -   [Folder storage](#folder-storage)
    -   [Folder system](#folder-system)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Struktur default rakit dimaksudkan untuk memberikan titik awal yang bagus untuk aplikasi besar dan kecil.
Struktur ini dibuat mirip framework lain yang telah ada sehingga anda tidak akan merasa asing.

<a id="hirarki-folder"></a>

## Hirarki Folder

Dalam keadaan default, hirarki folder rakit akan terlihat seperti berikut:

```bash
├── /application
│   ├── /config
│   ├── /controllers
│   ├── /language
│   ├── /libraries
│   ├── /migrations
│   ├── /models
│   ├── /commands
│   ├── /views
│   ├── boot.php
│   ├── composers.php
│   ├── events.php
│   ├── middlewares.php
│   ├── packages.php
│   └── routes.php
├── /assets
├── /packages
│   └── /docs
├── /storage
│   ├── /cache
│   ├── /console
│   ├── /database
│   ├── /logs
│   ├── /sessions
│   └── /views
├── /system
├── index.php
├── key.php    (auto-generated secret key)
├── paths.php
├── rakit
└── robots.txt
```

Sekarang, mari kita bahas apa kegunaan folder-folder tersebut.

<a id="folder-application"></a>

### Folder application

Folder `application/` controller, view, file konfigurasi serta file default lainnya.
Pada dasarnya, folder ini adalah sebuah paket (yaitu paket default) yang digunakan untuk
mem-bootstrap sistem rakit serta paket lain yang anda install ke folder `packages/`.

Routing default serta setelan lain juga diletakkan dalam flder ini.

<a id="folder-assets"></a>

### Folder assets

Folder `assets/` berisi aset yang sifatnya publik seperti file - file CSS, JavaScript, gambar
serta file lain yang harus bisa diakses oleh web browser.

Didalam folder ini juga terdapat subfolder `packages/` yang digunakan untuk meletakkan file - file
asset bawaan paket yang anda install.

<a id="folder-packages"></a>

### Folder packages

Folder `packages/` berisi folder paket yang anda install.

<a id="folder-storage"></a>

### Folder storage

Folder `storage/` berisi subfolder bawaan rakit untuk penyimpanan file - file non-publik seperti
file cache, session , file database (sqlite) serta file hasil render
dari [Blade Template Engine](/docs/en/views/templating#blade-template-engine).

<a id="folder-system"></a>

### Folder system

Folder `system/` merupakan folder inti, didalamnya tersimpan file - file utama milik rakit.
Pada saat mengupgrade framework rakit, biasanya anda cukup menimpa folder ini dengan yang baru.
