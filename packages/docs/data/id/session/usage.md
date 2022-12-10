# Menggunakan Session

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Menyimpan Item](#menyimpan-item)
-   [Mengambil Item](#mengambil-item)
-   [Menghapus Item](#menghapus-item)
-   [Flash Item](#flash-item)
-   [Regenerasi](#regenerasi)

<!-- /MarkdownTOC -->

<a id="menyimpan-item"></a>

## Menyimpan Item

Menyimpan item kedalam session sangatlah sederhana. Cukup panggil method `put()` seperti ini:

```php
Session::put('name', 'Budi');
```

Parameter pertama adalah key ke item cache tersebut. Anda akan menggunakan key ini untuk mengambil item dari cache. Parameter kedua adalah valuenya.

<a id="mengambil-item"></a>

## Mengambil Item

Anda bisa menggunakan method `get()` untuk mengambil item dari session, termasuk flash data. Cukup sebutkan key item mana yang ingin anda ambil:

```php
$name = Session::get('name');
```

Secara default, ia akan me-return `NULL` jika item sesi tidak ada. Namun, anda dapat mengoper default value pengganti sebagai parameter kedua jika perlu:

```php
$name = Session::get('name', 'Andi');

$name = Session::get('name', function () { return 'Andi'; });
```

Sekarang, ia akan me-return `'Andi'` jika item `'name'` tidak ada di session.

Rakit bahkan memberi anda cara sederhana untuk menentukan ada tidaknya item di session menggunakan method has():

```php
if (Session::has('name')) {
	$name = Session::get('name');
}
```

<a id="menghapus-item"></a>

## Menghapus Item

Sekarang tinggal bagaimana menghapus item, kan? Tidak masalah. Cukup oper key dari item yang ingin anda hapus ke method `forget()` seperti ini:

```php
Session::forget('name');
```

Anda bahkan dapat menghapus semua item dari sesi menggunakan metode `flush()`:

```php
Session::flush();
```

<a id="flash-item"></a>

## Flash Item

Metode `flash()` menyimpan item kedalam session yang akan kadaluwarsa setelah request berikutnya. Ini berguna untuk menyimpan data sementara seperti status atau pesan error validasi:

```php
Session::flash('status', 'Welcome Back!');
```

Item flash yang kadaluwarsa dalam request berikutnya dapat dipertahankan untuk request lain dengan menggunakan metode `reflash()` atau `keep()`:

Pertahankan seluruh item flash untuk request lain:

```php
Session::reflash();
```

Pertahankan sebuah item flash untuk request lain:

```php
Session::keep('status');
```

Pertahankan beberapa item flash untuk request lain:

```php
Session::keep(['status', 'other_item']);
```

<a id="regenerasi"></a>

## Regenerasi

Terkadang anda mungkin ingin me-regenerasi" ID sesi. Ini berarti bahwa ID sesi lama akan diganti dengan ID ssi acak yang baru. Begini caranya:

```php
Session::regenerate();
```
