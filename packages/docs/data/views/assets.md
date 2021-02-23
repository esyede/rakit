# Mengelola Aset

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Mendaftarkan Aset](#mendaftarkan-aset)
- [Memanggil Aset](#memanggil-aset)
- [Dependensi Aset](#dependensi-aset)
- [Container Aset](#container-aset)
- [Aset Milik Paket](#aset-milik-paket)

<!-- /MarkdownTOC -->


<a id="mendaftarkan-aset"></a>
## Mendaftarkan Aset

Komponen `Asset` menyediakan cara sederhana untuk mengelola CSS dan JavaScript yang digunakan
oleh aplikasi anda. Untuk mendaftarkan aset, cukup panggil method `add()` milik kelas `Asset`:


#### Mendaftarkan sebuah aset:

```php
Asset::add('jquery-core', 'js/jquery.js');
```

Metohd `add()` menerima tiga parameter. Yang pertama adalah nama aset, yang kedua adalah
path aset (relatif ke direktori `assets/`), dan yang ketiga adalah daftar dependensi aset
(lebih lanjut tentang ini nanti).

Perhatikan bahwa kita tidak memberi tahu si method `add()`
tersebut apakah yang kita daftarkan adalah JavaScript atau CSS. Method `add()` akan secara cerdas
menggunakan ekstensi file untuk menebak jenis file yang kita daftarkan.


<a id="memanggil-aset"></a>
## Memanggil Aset

Untuk memanggil seluruh aset terdaftar dari dalam view, anda dapat menggunakan method `styles()`
atau `scripts()`:


#### Memanggil aset terdaftar dari dalam view:

```php
<head>
	<?php echo Asset::styles(); ?>

    <!-- kode html lain disini.. -->

    <?php echo Asset::scripts(); ?>
</head>

```


<a id="dependensi-aset"></a>
## Dependensi Aset

Terkadang anda mungkin perlu menyebutkan bahwa suatu aset memiliki dependensi. Ini berarti bahwa
aset tersebut membutuhkan aset lain untuk dipanggil terlebih dahulu sebelum ia dapat digunakan.

Mengelola dependensi aset tidaklah sulit. Ingat _"nama"_ yang anda berikan pada aset anda tadi?
anda dapat mengopernya pada parameter ketiga milik method `add()` saat mendeklarasikan dependensi:


#### Memdaftarkan aset yang memiliki dependensi:

```php
Asset::add('jquery-ui', 'js/jquery-ui.js', 'jquery-core');
```

Dalam contoh ini, kita mendaftarkan aset `jquery-ui`, serta mendefinisikan bahwa si `jquery-ui` ini
dependent (memiliki dependensi) yaitu aset `jquery-core`.

Sekarang, ketika anda memanggil link aset pada view anda, aset jQuery akan selalu dipanggil
sebelum aset jQuery UI.

Perlu mendeklarasikan lebih dari satu dependensi? Tidak masalah:


#### Mendaftarkan aset yang memiliki banyak dependensi:

```php
Asset::add('jquery-ui', 'js/jquery-ui.js', ['dependensi-1', 'dependensi-2']);
```


<a id="container-aset"></a>
## Container Aset

Untuk mengurangi waktu load halaman, biasanya kita meletakkan JavaScript di bagian bawah dokumen HTML.
Tetapi, bagaimana jika anda juga perlu meletakkan beberapa aset di Header dokumen anda?

Tidak masalah. Komponen ini menyediakan cara sederhana untuk mengelolanya. Cukup panggil
method `container()` milik kelas `Asset` dan sebutkan nama containernya.

Setelah anda memiliki instance container aset, anda bebas untuk menambahkan aset apa pun yang
anda inginkan ke container menggunakan sintaks yang biasa digunakan:


#### Mengambil instance dari container aset:

```php
Asset::container('footer')->add('foo', 'js/script.js');

Asset::container('footer')->add('bar', 'css/style.css');
```


#### Mengambil aset-aset dari suatu container:

```php
echo Asset::container('footer')->scripts();

echo Asset::container('footer')->styles();
```


<a id="aset-milik-paket"></a>
## Aset Milik Paket

Sebelum mempelajari mudahnya mendaftarkan dan mengambil aset milik paket, anda mungkin ingin
membaca dokumentasi tentang [membuat dan publish aset milik paket](/docs/packages#aset-paket).

Saat mendaftarkan aset, path aset biasanya relatif terhadap direktori `assets/`. Namun, akan
kurang nyaman ketika berhadapan dengan aset yang dibawa oleh suatu paket,
karena mereka terletak di direktori `assets/packages`.

Tapi ingat, Rakit ada di sini untuk membuat hidup anda lebih mudah. Jadi, mudah saja untuk menentukan
aset dari paket mana yang harus dikelola oleh container.


#### Menentukan aset dari paket mana yang harus dikelola oleh container:

```php
Asset::container('foo')->package('admin');
```

Sekarang, ketika anda menambahkan aset, anda dapat menggunakan path relatif ke direktori `assets/`
milik paket. Rakit akan secara otomatis menghasilkan path lengkap ke aset yang tepat.
