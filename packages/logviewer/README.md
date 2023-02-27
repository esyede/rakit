# logviewer

<p align="center"><img src="screenshot.png" alt="logviewer"></p>

Paket ini memungkinkan anda untuk mengelola dan melacak setiap file log di rakit framework.

## Instalasi
Jalankan perintah ini via rakit console:

```sh
php rakit package:install logviewer
```


## Mendaftarkan paket

Tambahkan kode berikut ke file `application/packages.php`:

```php
'logviewer' => ['handles' => 'logviewer'],
```

Lalu buka file konfigurasi milik paket ini dan tambahkan middleware
agar hanya admin saja yang bisa mengakses paket ini.

Caranya, buka file `packages/logviewer/config/main.php` dan
tambahkan middleeware anda disana:

```php
'middleware' => [
    'auth',
    'admin_only', // ubah ini sesuai nama middleware anda
],
```

**PENTING !!**

Saya ulangi, pastikan bahwa anda telah menambahkan middleware sehingga
hanya admin saja yang dapat mengakses routing milik paket ini.

Secara default, paket ini sudah menerapkan middleware `'auth'` sehingga
hanya user yang sudah login saja yang bisa nengaksesnya.

Tetapi, memberikan akses ke selain admin sangat berbahaya
karena orang lain akan dapat membuka dan melihat isi file log anda!


## Cara penggunaan

Baik, setelah middleware selesai ditambahkan, anda sudah dapat mengaksesnya
melalui url `/logviewer` seperti contoh berikut:

```
https://situsku.com/logviewer
```


## Lisensi

Paket ini dirilis dibawah [Lisensi MIT](https://github.com/esyede/logviewer/master/LICENSE)
