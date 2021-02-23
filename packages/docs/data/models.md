# Model & Library

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Model](#model)
- [Library](#library)
- [Autoloading](#autoloading)

<!-- /MarkdownTOC -->


<a id="model"></a>
## Model

Model adalah jantung dari aplikasi anda. Logika aplikasi anda (controller / route) dan view (HTML)
hanyalah media yang digunakan pengguna untuk berinteraksi dengan model anda.
Jenis logika paling umum yang terkandung dalam model adalah
[Business Logic](http://en.wikipedia.org/wiki/Business_logic).

Beberapa contoh fungsi yang akan ada dalam suatu model antara lain:

- Interaksi Database
- Input/Output File
- Interaksi dengan Web Service

Misalnya, mungkin anda sedang membuat mesin blog. Anda mungkin ingin memiliki model `Post`.
User mungkin ingin mengomentari artikel sehingga anda juga akan memiliki model `Comment`.
Jika user akan berkomentar maka kita juga akan membutuhkan model `User`. Dapet kan idenya?


<a id="library"></a>
## Library

Library adalah kelas yang melakukan tugas yang tidak spesifik untuk aplikasi anda. Misalnya seperti
library PDF generator yang mengubah HTML menjadi file PDF. Tugas itu, meskipun rumit, tidak spesifik
untuk aplikasi anda, sehingga dianggap sebagai "library".

Pembuatan library di Rakit sangatlah mudah, cukup buat kelas dan simpan ke folder `libraries/`.

Dalam contoh berikut, kita akan membuat library sederhana untuk mengubah teks menjadi huruf kapital.
Kita buat file `printer.php` di folder `libraries/` berisi kode berikut:

```php
class Printer
{
	public static function uppercase($text)
    {
		return strtoupper($text);
	}
}
```

Sekarang, anda dapat memanggilnya dari mana saja dalam aplikasi anda:

```php
echo Printer::uppercase('halo dunia!');
```


<a id="autoloading"></a>
## Autoloading

Library dan model sudah secara otomatis dimuat untuk anda tanpa harus meng-include secara manual.
Anda hanya perlu mengimpor kelasnya saja untuk mulai menggunakan. Ini berkat fitur autoloading.

_Bacaan lebih lanjut:_

- [Autoloading](/docs/autoloading)
