# Input & Cookies

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Input](#input)
- [JSON Input](#json-input)
- [File](#file)
- [Old Input](#old-input)
- [Redirect Dengan Old Input](#redirect-dengan-old-input)
- [Cookies](#cookies)
- [Merge & Replace](#merge--replace)
- [Menghapus Data Input](#menghapus-data-input)

<!-- /MarkdownTOC -->


<a id="input"></a>
## Input

Komponen ini membantu anda dalam menangani input yang dikirim user ke dalam aplikasi, baik
melalui `GET`, `POST`, `PUT`, atau `DELETE`. Dibawah ini tersedia beberapa contoh bagaimana
cara mengakses data inputan user.


#### Mengambil sebuah value dari array input:

```php
$email = Input::get('email');
```

>  Method `get()` ini digunakan untuk mengambil semua tipe request
   (`GET`, `POST`, `PUT`, dan `DELETE`), bukan hanya `GET` saja.


#### Mengambil semua data inputan:

```php
$input = Input::get();
```


#### Mengambil semua data inputan termasuk dari `$_FILES`:

```php
$input = Input::all();
```

Secara default method - method diatas akan mereturn `NULL` jika datanya tidak ketemu.

Akan tetapi, jika anda ingin mengganti default return ini, cukup oper default return value
yang anda kehendaki ke parameter ke-dua.


#### Mengganti default return value:

```php
$name = Input::get('name', 'Anonim');
```

#### Mengganti default return value via Closure:

```php
$name = Input::get('name', function () { return 'Anonim'; });
```

#### Memeriksa ada tidaknya suatu inputan:

```php
if (Input::has('name')) {
    // ..
}
```

>  Method `has()` ini akan mereturn `FALSE` jika inputan ada tetapi berisi string kosong.


<a id="json-input"></a>
## JSON Input

Ketika bekerja dengan framework JavaScript, anda mungkin akan perlu untuk mengambil value JSON
yang dikirim oleh aplikasi klien. Agar lebih mudah, kami juga telah menyertakan
method `json()` untuk anda:

#### Mengambil input JSON dari aplikasi klien:

```php
$data = Input::json();
```


<a id="file"></a>
## File

#### Mengambil semua data inputan dari `$_FILES`:

```php
$files = Input::file();
```

#### Mengambil salah satu data inputan saja:

```php
$picture = Input::file('picture');
```

#### Mengambil sebuah data spesifik dari `$_FILES`:

```php
$size = Input::file('picture.size');
```

>  Untuk menggunakan method `file()` ini, anda harus menambahkan `"multipart/form-data"` ke form HTMl anda.


<a id="old-input"></a>
## Old Input

Biasanya anda akan perlu untuk mempertahankan data inputan lama ketika validasi form gagal.
Komponen ini juga telah dirancang untuk membantu anda menanganinya.

Berikut ini adalah contoh bagaimana anda dapat dengan mudah mempertahankan data inputan lama
dari request sebelumnya.

Pertama, anda harus menitipkan (atau, istilahnya _"flashing"_) data inputan lama tersebut
kedalam session.

#### Flashing data inputan ke session:

```php
Input::flash();
```

#### Flashing beberapa inputan tertentu saja:

```php
Input::flash('only', ['username', 'email']);

Input::flash('except', ['password', 'credit_card']);
```

#### Mengambil flash data (inputan lama) dari request sebelumnya:

```php
$name = Input::old('name');
```

>  Anda harus mengatur driver session terlebih dahulu untuk dapat menggunakan method `flash()` dan `old()` ini.


_Bacaan lebih lanjut:_

- _[Session](/docs/id/session/config)_


<a id="redirect-dengan-old-input"></a>
## Redirect Dengan Old Input

Sekarang anda sudah tahu bagaimana caranya flashing data ke session.
Berikut adalah beberapa shortcut yang dapat anda gunakan ketika me-redirect user dengan
menyertakan data inputan lama:

#### Flashing data inputan lama saat Redirect:

```php
return Redirect::to('login')->with_input();
```

#### Flashing beberapa data inputan tertentu saja:

```php
return Redirect::to('login')->with_input('only', ['username']);

return Redirect::to('login')->with_input('except', ['password']);
```


<a id="cookies"></a>
## Cookies

Kami juga telah menyediakan komponen untuk menangani untuk variabel global `$_COOKIE`. Namun,
ada beberapa hal yang harus anda ketahui sebelum menggunakannya.

Pertama, setiap cookie akan mengandung _"signature hash"_. Hal ini memusngkinkan sisi framework  untuk
memverifikasi bahwa cookie belum diubah oleh klien. Kedua, saat membuat cookie, cookie tidak langsung
dikirim ke browser, namun ditampung sampai akhir request dan baru kemudian dikirim bersama-sama.

Ini berarti bahwa anda tidak akan dapat membuat dan mengambil value cookie secara bersamaan
dalam satu siklus request.

#### Mengambil item cookie:

```php
$name = Cookie::get('name');
```

#### Menentukan default value jika item cookie tidak ditemukan:

```php
$name = Cookie::get('name', 'Anonim');
```

#### Membuat cookie dengan masa aktif 60 menit:

```php
Cookie::put('name', 'Anonim', 60);
```

#### Membuat cookie dengan masa aktif permanen (5 tahun):

```php
Cookie::forever('name', 'Anonim');
```

#### Menghapus cookie:

```php
Cookie::forget('name');
```


<a id="merge--replace"></a>
## Merge & Replace

Kadang-kadang anda mungkin ingin menggabungkan atau mengganti data inputan. Begini caranya:

#### Menggabungkan data baru ke data inputan:

```php
Input::merge(['name' => 'Agus']);
```

#### Mengganti semua data inputan dengan data baru:

```php
Input::replace(['name' => 'Andi', 'age' => 23]);
```


<a id="menghapus-data-input"></a>
## Menghapus Data Input

Untuk menghapus seluruh data inputan pada siklus request saat ini, gunakan method `clear()`:

```php
Input::clear();
```
