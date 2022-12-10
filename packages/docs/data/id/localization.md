# Alih Bahasa

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Mengambil baris bahasa](#mengambil-baris-bahasa)
-   [Placeholder & Replacement](#placeholder--replacement)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Alih bahasa adalah proses menerjemahkan aplikasi anda ke dalam berbagai bahasa. Komponen `Lang` menyediakan mekanisme sederhana untuk membantu anda mengatur dan mengambil teks aplikasi multibahasa anda.

Semua file bahasa untuk aplikasi anda berada di bawah direktori `application/language/`. Di dalam direktori `application/language/` tersebut, anda harus membuat direktori untuk setiap bahasa yang digunakan aplikasi anda.

Jadi, misalnya, jika aplikasi anda berbicara bahasa Inggris dan Indonesia, anda dapat membuat direktori `en/` dan `id/` di bawah direktori `language/`. Secara default, dua bahasa tersebut sudah kami sediakan. Anda boleh menambahkan bahasa - bahasa lain jika perlu.

Setiap file bahasa hanyalah array asosiatif berisi string dalam bahasa yang bersangkutan. Uniknya, file bahasa memiliki struktur yang identik dengan file konfigurasi. Misalnya, dalam direktori `application/language/en/`, anda dapat membuat file `marketing.php` yang terlihat seperti ini:

#### Membuat sebuah file bahasa:

```php
return [

	'welcome' => 'Welcome to our website!',

];
```

Selanjutnya, anda harus membuat file `marketing.php` yang sama di dalam direktori `application/language/id/`. File tersebut akan terlihat seperti ini:

```php
return [

	'welcome' => 'Selamat datang di situs kami!',

];
```

Mantap! Sekarang anda sudah tahu caranya membuat file alih bahasa. Sangat mudah bukan?

<a id="mengambil-baris-bahasa"></a>

## Mengambil baris bahasa

#### Mengambil sebuah baris bahasa:

```php
echo Lang::line('marketing.welcome')->get();
```

#### Mengambil baris bahasa menggunakan helper agar penulisannya lebih pendek:

```php
echo __('marketing.welcome');
```

Perhatikan bagaimana tanda dot (titik) digunakan untuk memisahkan `marketing` dan `welcome`? Teks sebelum dot berhubungan dengan file bahasa, sedangkan teks setelah dot berhubungan dengan string tertentu di dalam file itu.

Perlu mengambil baris dalam bahasa selain bahasa default anda? Tidak masalah. Cukup sebutkan bahasa yang anda mau ke method `get()`:

#### Mengambil baris dalam bahasa tertentu:

```php
echo Lang::line('marketing.welcome')->get('fr');
```

<a id="placeholder--replacement"></a>

## Placeholder & Replacement

Sekarang, coba kita buat pesan selamat datang yang lebih spesifik. _"Selamat datang di situs kami!"_ adalah pesan yang terlalu umum. Akan sangat membantu jika anda dapat menyebutkan nama orang yang akan kita sambut.

Namun, membuat baris bahasa untuk setiap pengguna aplikasi kita pastinya akan memakan waktu dan terasa konyol. Untungnya, anda tidak perlu melakukannya. Anda dapat menentukan _placeholder_ dalam baris bahasa anda. Placeholder diawali dengan titik dua:

#### Membuat sebuah baris bahasa dengan placeholder:

```php
'welcome' => 'Selamat datang di situs kami, :name!'
```

#### Mengambil sebuah baris bahasa dengan replacement:

```php
echo Lang::line('marketing.welcome', ['name' => 'Budi'])->get();
```

#### Mengambil sebuah baris bahasa dengan replacement menggunakan helper:

```php
echo __('marketing.welcome', ['name' => 'Budi']);
```
