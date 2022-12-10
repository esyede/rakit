# Menggunakan Cache

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Menyimpan Item](#menyimpan-item)
-   [Mengambil Item](#mengambil-item)
-   [Menghapus Item](#menghapus-item)

<!-- /MarkdownTOC -->

<a id="menyimpan-item"></a>

## Menyimpan Item

Menyimpan item kedalam cache sangatlah sederhana. Cukup panggil method `put()` seperti ini:

```php
Cache::put('name', 'Budi', 10);
```

Parameter pertama adalah key ke item cache tersebut. Anda akan menggunakan key ini untuk mengambil item dari cache. Parameter kedua adalah valuenya. Parameter ketiga adalah jumlah `menit` anda ingin item tersebut di-cache.

```php
Cache::forever('name', 'Budi');
```

> Tidak perlu men-serialize object saat menyimpannya di cache karena rakit akan melakukannya untuk anda.

<a id="mengambil-item"></a>

## Mengambil Item

Mengambil item dari cache bahkan lebih mudah daripada menyimpannya. Cukup gunakan method `get()` dan sebutkan key item mana yang ingin anda ambil:

```php
$name = Cache::get('name');
```

Secara default, ia akan me-return `NULL` jika item yang diminta tidak ditemukan atau sudah kadaluwarsa. Namun, anda juga dapat memberikan default value yang berbeda sebagai parameter kedua jika anda mau:

```php
$name = Cache::get('name', 'Anonim');
```

Sekarang, ia akan me-return `'Anonim'` jika cache `'name'` tidak ditemukan atau telah kadaluwarsa.

Bagaimana jika anda membutuhkan value dari database sedangkan item cache tidak ditemukan? Solusinya sederhana. Anda bisa mengoper Closure ke method `get()` sebagai default value. Closure tadi hanya akan dijalankan jika item yang di-cache tidak ada:

```php
$users = Cache::get('count', function () {
	return DB::table('users')->count();
});
```

Mari kita kembangkan contoh ini selangkah lebih maju. Bayangkan anda ingin mendapatkan kembali jumlah user yang terdaftar di aplikasi anda; namun, jika valuenya tidak di-cache, anda ingin menyimpan default value tersebut ke cache menggunakan method `remember()`:

```php
$users = Cache::remember('count', function () {
	return DB::table('users')->count();
}, 5);
```

Mari kita bahas contoh diatas. Jika item `'count'` ada di dalam cache, itu yang akan di-return. Jika tidak ada, return value milik Closure akan disimpan di cache selama lima menit sekaligus di-return. Mantap, kan?

Rakit bahkan memberi anda cara sederhana untuk menentukan ada tidaknya item di cache menggunakan method `has()`:

```php
if (Cache::has('name')) {
	$name = Cache::get('name');
}
```

<a id="menghapus-item"></a>

## Menghapus Item

Sekarang tinggal bagaimana menghapus item, kan? Tidak masalah. Cukup oper key dari item yang ingin anda hapus ke method `forget()` seperti ini:

```php
Cache::forget('name');
```
