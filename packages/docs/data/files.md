# Bekerja dengan File

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [File Path](#file-path)
- [Baca File](#baca-file)
- [Tulis File](#tulis-file)
- [Hapus File](#hapus-file)
- [Upload File](#upload-file)
- [Ekstensi File](#ekstensi-file)
- [Memeriksa Tipe File](#memeriksa-tipe-file)
- [MIME-Type](#mime-type)
- [Salin Direktori](#salin-direktori)
- [Hapus Direktori](#hapus-direktori)

<!-- /MarkdownTOC -->


<a id="file-path"></a>
## File Path

#### Memeriksa eksistensi direktori atau file:

```php
File::exists('foo/bar/');    // true
File::exists('foo/bar.txt'); // true
```


#### Memeriksa bahwa path yang diberikan merupakan sebuah direktori:

```php
File::isdir('foo/bar/');    // true
File::isdir('foo/bar.txt'); // false
```


<a id="baca-file"></a>
## Baca File

#### Mengambil isi file:

```php
$contents = File::get('path/to/file');
```

<a id="tulis-file"></a>
## Tulis File

#### Menulis data kedalam file:

```php
File::put('path/to/file', 'isi file');
```

#### Menambahkan data ke akhir file:

```php
File::append('path/to/file', 'isi file yang ditambahkan');
```

<a id="hapus-file"></a>
## Hapus File

#### Menghapus sebuah file:

```php
File::delete('path/to/file.ext');
```

<a id="upload-file"></a>
## Upload File

#### Memindahkan file dari `$_FILES` ke disk:

```php
Input::upload('picture', 'path/to/pictures', 'filename.ext');
```

>  Anda dapat dengan mudah mevalidasi file upload menggunakan [Validator](/docs/validation).


<a id="ekstensi-file"></a>
## Ekstensi File

#### Mengambil ekstensi sebuah file:

```php
File::extension('picture.png');
```

<a id="memeriksa-tipe-file"></a>
## Memeriksa Tipe File

#### Memeriksa apakah suatu file bertipe tertentu:

```php
if (File::is('jpg', 'path/to/file.jpg')) {
    // ..
}
```

Metode `is()` tidak hanya memeriksa ekstensi file saja. Ia juga akan menggunakan ekstensi
[Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) untuk membaca konten file dan
menentukan MIME-Type yang sebenarnya.


<a id="mime-type"></a>
## MIME-Type

#### Mengambil MIME-Type yang terkait dengan ekstensi:

```php
echo File::mime('lolcat.gif'); // output: 'image/gif'
```

>  Anda harus mengaktifkan ekstensi [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php)
   sebelum menggunakan method `mime()` ini.


<a id="salin-direktori"></a>
## Salin Direktori

#### Salin direktori ke lokasi tertentu secara rekursif:

```php
File::cpdir($directory, $destination);
```


<a id="hapus-direktori"></a>
## Hapus Direktori

#### Hapus direktori tertentu secara rekursif:

```php
File::rmdir($directory);
```
