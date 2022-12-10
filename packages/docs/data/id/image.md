# Image

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Memuat Gambar](#memuat-gambar)
-   [Manipulasi Gambar](#manipulasi-gambar)
    -   [Resize Gambar](#resize-gambar)
    -   [Rotasi & Crop](#rotasi-dan-cropping)
    -   [Watermark](#watermark)
-   [Efek Gambar](#efek-gambar)
    -   [Brightness, Contrast & Smoothness](#brightness-contrast-dan-smoothness)
    -   [Blur & Grayscale](#blur-dan-grayscale)
-   [Export Gambar](#export-gambar)
-   [Fitur Tambahan](#fitur-tambahan)
    -   [Info gambar](#melihat-info-gambar)
    -   [Identicon](#membuat-identicon)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Dalam pengembangan aplikasi berbasis web, pastinya anda akan berususan dengan masalah gambar,
seperti ukuran gambar hasil upload user yang ukurannya terlalu besar misalnya.

Tentu anda tidak mau menyimpan gambar besar itu langsung ke storage karena sangatlah memakan tempat.
Tenang saja, komponen ini siap membantu anda!

<a id="memuat-gambar"></a>

## Memuat Gambar

Untuk memulai pengeditan gambar, pastinya anda perlu terlebih dahulu menentukan
gambar mana yang ingin anda edit. Gunakan method `open()` untuk membuka gambar.

<a id="memuat-gambar-1"></a>

#### Memuat target gambar:

```php
$image = Image::open('assets/images/test.jpg');
```

Jika anda juga ingin sekalian menentukan kualitas hasil export gambar anda nanti,
silahkan tambahkn kualitas yang anda inginkan ke parameter ke-dua,
rentang nilainya antara `0 - 100`, nilai defaultnya adalah `75`.

#### Memuat dan mengatur kualitas gambar:

```php
$image = Image::open('assets/images/test.jpg', 75);
```

<a id="manipulasi-gambar"></a>

## Manipulasi Gambar

Seperti dijelaskan diatas, telah disediakan beberapa method untuk memanipulasi gambar,
mulai dari mengatur kualitas, mengatur panjang, lebar, cropping, rotasi dan memberi efek ke gambar.

Sudah siap, kan? mari kita coba!

<a id="resize-gambar"></a>

### Resize Gambar

Saat menangani upload gambar, tentu anda ingin menyimpan file gambar tersebut ke ukuran
yang lebih kecil agar lebih hemat dalam penggunaan storage space.

#### Mengatur lebar gambar:

```php
$image->width(100); // 100 pixel
```

#### Mengatur tinggi gambar:

```php
$image->height(100); // 100 pixel
```

<a id="rotasi-dan-cropping"></a>

### Rotasi & Crop

Terkadang, gambar yang diupload oleh user tidak selalu dalam posisi tegak,
terutama foto yang yang diambil memalui kamera handphone.

Tenang saja, anda memutar posisinya.

#### Memutar posisi gambar:

```php
$image->rotate(90); // putar 90 derajat

$image->rotate(180); // putar 180 derajat
```

> Method `rotate()` ini hanya menerima value dengan kelipatan 90.

#### Crop gambar

Operasi cropping (pemotongan gambar) sangatlah mudah. Rakit menyediakan
2 cara untuk melakukannya, yaitu cropping standar dan cropping rasio.

```php
$left = 50;
$top = 20;
$width = 100;
$height = 100;

$image->crop($left, $top, $width, $height);
```

Bagaimana, apakah terlalu ribet?

Oke, jika metode diatas terlalu ribet, silahkan gunakan metode _"rasio"_ untuk cropnya.
Contohnya seperti ini:

```php
$width = 2;
$height = 1;

$image->ratio($width, $height);
```

Nah, lebih mudah bukan? cara mana yang lebih anda sukai?

<a id="watermark"></a>

### Watermark

Selain dapat memotong dan merotasi gambar, anda juga dapat menambahkan watermark pada gambar anda:

```php
$image->watermark('assets/images/watermark.png');
```

<a id="efek-gambar"></a>

## Efek Gambar

Kadang kala, gambar yang diupload user terlihat terlalu redup, terlalu terang
atau mungkin gambar yang diuplad adalah gambar privat yang perlu sedikit anda samarkan.

<a id="brightness-contrast-dan-smoothness"></a>

### Brightness, Contrast & Smoothness

Anda juga dapat mengatur brightness, contrast dan smoothness gambar dengan mudah.

#### Mengatur brightness:

```php
$image->brightness(40);
```

#### Mengatur contrast:

```php
$image->contrast(80);
```

#### Mengatur contrast:

```php
$image->smoothness(80);
```

<a id="blur-dan-grayscale"></a>

### Blur & Grayscale

Anda juga dapat menambahkan efek blur (buram) dan grayscale (skala abu-abu) ke gambar target anda. Begini caranya:

#### Efek blur:

```php
$image->blur();    // standard (gaussian) blur
$image->blur(true); // selective blur
```

#### Efek grayscale:

```php
$image->grayscale();
```

#### Efek sepia:

```php
$image->sepia();
```

#### Efek emboss:

```php
$image->emboss();
```

#### Efek edge-highlight:

```php
$image->edge();
```

<a id="export-gambar"></a>

## Export Gambar

Setelah gambar selesai dimanipulasi, anda bisa menyimpannya kedalam sebuah file:

#### Menyimpan hasil gambar ke file:

```php
$image->export('assets/images/budi.png');
```

<a id="fitur-tambahan"></a>

## Fitur Tambahan

Selain fitur - fitur diatas, library ini juga menyediakan beberapa fitur bonus:

<a id="melihat-info-gambar"></a>

### Info gambar

Untuk melihat info gambar, silahkan gunakan method `info()`:

```php
$image->info();
```

<a id="membuat-identicon"></a>

### Identicon

Anda juga dapat membuat [identicon](https://en.wikipedia.org/wiki/Identicon)
menggunakan komponen ini. Begini cara melakukannya:

```php
// Membuat identicon (dengan ukuran default 64 pixel)
$identicon = Image::identicon('budi');

// Membuat identicon dengan ukuran 200 pixel
$identicon = Image::identicon('budi', 200);

// Preview identicon image ke browser
return Image::identicon('budi', 64, true);

// Menyimpan identicon image ke file
Storage::put(path('storage').'budi.jpg', $identicon);
```
