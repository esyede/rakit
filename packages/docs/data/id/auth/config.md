# Konfigurasi Otentikasi

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Pengetahuan Dasar](#pengetahuan-dasar)
- [Driver Otentikasi](#driver-otentikasi)
- [Username Default](#username-default)
- [Model Otentikasi](#model-otentikasi)
- [Tabel Otentikasi](#tabel-otentikasi)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Pengetahuan Dasar

Sebagian besar aplikasi interaktif memiliki kemampuan bagi user untuk log in dan log out. Rakit menyediakan kelas sederhana untuk membantu anda memvalidasi kredensial user dan mengambil informasi tentang user aplikasi anda saat ini.

Untuk memulai, mari kita lihat file `application/config/auth.php`. File konfigurasi ini berisi beberapa opsi dasar untuk membantu anda memulai otentikasi.


<a id="driver-otentikasi"></a>
## Driver Otentikasi

Mekanisme otentikasi Rakit adalah berbasis driver, yang berarti tanggung jawab untuk mengambil user selama otentikasi didelegasikan ke berbagai "driver".

Secara default, kami telah menyertakan dua buah driver:

  - Driver `'facile'` yang menggunakan [Facile Model](/docs/database/facile) untuk memuat user aplikasi anda, dan merupakan driver default.
  - Driver `'magic'` yang menggunakan [Magic Query Builder](/docs/database/magic) untuk memuat user anda.


Namun, anda anda bebas membuat dan menambahkan driver anda sendiri jika diperlukan!


<a id="username-default"></a>
## Username Default

Opsi kedua di file konfigurasi menentukan "username" default untuk user anda.
Ini biasanya akan sesuai dengan kolom database di tabel users, dan biasanya akan berupa 'email' atau 'username'.

```php
'username' => 'email',
```

> Pada keadaan default, rakit dikonfigurasi untuk menggunakan `email`, namun tentu saja anda bebas mengubahnya.


<a id="model-otentikasi"></a>
## Model Otentikasi

Ketika menggunakan driver `'facile'`, opsi ini menentukan model mana yang harus digunakan saat memuat data user.

```php
'model' => 'User',
```


<a id="tabel-otentikasi"></a>
## Tabel Otentikasi

Ketika menggunakan driver `'magic'`, opsi ini menentukan tabel mana yang harus digunakan untuk memuat data user.

```php
'table' => 'users',
```
