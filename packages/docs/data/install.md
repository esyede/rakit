# Instalasi & Konfigurasi Awal

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Kebutuhan Sistem](#kebutuhan-sistem)
- [Instalasi](#instalasi)
    - [Instal via Composer](#instal-via-composer)
    - [Install Manual](#install-manual)
- [Ada Kesulitan?](#ada-kesulitan)
- [Konfigurasi Awal](#konfigurasi-awal)
- [Mempercantik URL](#mempercantik-url)
    - [Apache](#apache)
    - [Nginx](#nginx)

<!-- /MarkdownTOC -->


<a id="kebutuhan-sistem"></a>
## Kebutuhan Sistem

- PHP 5.4.0 up to 8.0
- Ekstensi [Mbstring](https://www.php.net/manual/en/book.mbstring.php)
- Ekstensi [OpenSSL](https://www.php.net/manual/en/book.openssl.php)
- Ekstensi [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php)


**Ekstensi Tambahan:**

Menginstall ekstensi berikut akan membantu anda mendapatkan manfaat penuh dari rakit, tetapi tidak diwajibkan:


- Driver [PDO](https://www.php.net/manual/en/pdo.installation.php) untuk SQLite,
  MySQL, PostgreSQL, atau SQL Server untuk bekerja dengan database.
- Ekstensi [cURL](https://www.php.net/manual/en/book.curl.php) untuk menginstall paket via rakit console.
- Ekstensi [GD Image](https://www.php.net/manual/en/book.image.php) untuk mengolah gambar.


<a id="instalasi"></a>
## Instalasi

Rakit dapat diinstall dengan 2 cara yang sangat mudah, yaitu instalasi via [Composer](https://getcomposer.org)
dan instalasi manual.


<a id="instal-via-composer"></a>
### Instal via Composer

Jika anda telah menginstall Composer pada komputer anda, instalasi rakit akan menjadi
sangat mudah, cukup jalankan perintah berikut:

```bash
composer create-project esyede/rakit
```

Maka rakit akan terinstall pada folder `/rakit`, yang perlu anda lakukan tinggal menuju folder tersebut dan
menjalankan webserver bawaan:

```bash
cd rakit && php rakit serve
```


<a id="install-manual"></a>
### Install Manual

Cara instalasi ini pun juga sangat mudah, semudah menghitung satu sampai tiga:

  - [Unduh](https://rakit.esyede.my.id/download) dan ekstrak arsip Rakit ke web server anda.
  - Pastikan direktori `storage/views/` dan `assets/` dapat ditulisi oleh PHP.
  - Edit file `application/config/application.php` dan tambahkan app key anda, ingat, panjang minimalnya harus 32 karakter.
  Anda juga dapat meng-generate app key melalui link ini: [App Key Generator](https://rakit.esyede.my.id/key)

  ```php
  /*
  |--------------------------------------------------------------------------
  | Application Key
  |--------------------------------------------------------------------------
  |
  | Key ini digunakan oleh kelas Crypter dan Cookie untuk menghasilkan
  | string dan hash terenkripsi yang aman. Sangat penting bahwa key ini
  | harus dirahasiakan dan tidak boleh dibagikan kepada siapa pun.
  |
  | Isilah dengan 32 karakter acak dan jangan diubah-ubah lagi. Anda juga
  | dapat mengisinya secara otomatis via rakit console.
  |
  */

  'key' => 'isiAppKeyAndaDisiniMinimal32karakter',
  ```

Lihat hasilnya melalui peramban favorit anda. Jika semuanya baik-baik saja, anda akan melihat halaman splash Rakit yang cantik.

Bersiaplah, ada banyak lagi yang harus dipelajari!


<a id="ada-kesulitan"></a>
## Ada Kesulitan?

Jika ada kesulitan dalam pemasangan, cobalah beberapa saran berikut ini:

- Jika anda menggunakan `mod_rewrite`, ubah opsi konfigurasi `'index'`
  di `application/config/application.php` ke string kosong.
- Pastikan folder `storage/` dan `assets/` serta seluruh folder di dalamnya dapat ditulisi oleh PHP.


<a id="konfigurasi-awal"></a>
## Konfigurasi Awal

Semua file konfigurasi disimpan di dalam folder `config/`.
Kami menyarankan anda melihat file-file tersebut agar mendapatkan pemahaman dasar
tentang opsi konfigurasi yang tersedia untuk anda.

Berikan perhatian khusus pada file `application/config/application.php` karena file tersebut
berisi opsi konfigurasi dasar untuk aplikasi anda.

>  Jika anda menggunakan `mod_rewrite`, ubah opsi `'index'`
   di `application/config/application.php` ke string kosong.


<a id="mempercantik-url"></a>
## Mempercantik URL

Ketika anda siap untuk memasang aplikasi anda ke server produksi, ada beberapa hal penting yang
dapat anda lakukan untuk memastikan aplikasi anda berjalan seefisien mungkin.

Dalam dokumen ini, kami akan membahas beberapa poin awal yang bagus untuk memastikan
aplikasi anda digunakan dengan benar.

Pastinya, anda juga tidak ingin URL aplikasi anda mengandung `/index.php`.
Anda dapat membuangnya menggunakan URL Rewrite.

<a id="apache"></a>
### Apache

Jika web server anda menggunakan Apache, pastikan modul `mod_rewrite` sudah diaktifkan,
kemudian buat sebuah file bernama `.htaccess` di root web server anda
(berdampingan dengan file `index.php`) dan salin kode berikut kedalamnya:

```apacheconf
Options -MultiViews -Indexes
RewriteEngine on

RewriteCond %{HTTP:Authorization} .
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} (.+)/$
RewriteRule ^ %1 [L,R=301]

RewriteRule ^(application|cgi-bin|packages|storage|system|vendor)/(.*)?$ / [F,L]
RewriteRule ^composer\.(lock|json)$ / [F,L]

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule . index.php [L]
```

Apakah konfigurasi diatas tidak bekerja? Coba ganti dengan yang ini:

```apacheconf
<IfPackage mod_rewrite.c>
    <IfPackage mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfPackage>

    RewriteEngine On

    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    RewriteRule ^(application|cgi-bin|packages|storage|system|vendor)/(.*)?$ / [F,L]
    RewriteRule ^composer\.(lock|json)$ / [F,L]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfPackage>
```

<a id="nginx"></a>
### Nginx

Jika anda menggunakan aplikasi anda ke server yang menjalankan Nginx, anda dapat menggunakan
file konfigurasi berikut sebagai titik awal untuk mengkonfigurasi web server anda.

Kemungkinan besar, file ini perlu disesuaikan mengikuti konfigurasi server anda:

```nginx
server {
    listen 80;
    server_name example.com;
    root /srv/example.com;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    autoindex off;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location /(application|cgi-bin|packages|storage|system|vendor) {
        return 403;
    }

    location /composer\.(lock|json) {
        return 403;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```



Setelah selesai mengatur URL rewrite, anda perlu mengubah opsi `'index'`
di `application/config/application.php` ke string kosong.

>  Setiap web server memiliki metode yang berbeda dalam menangani HTTP rewrite,
   dan mungkin juga akan membutuhkan rule konfigurasi yang berbeda pula.
