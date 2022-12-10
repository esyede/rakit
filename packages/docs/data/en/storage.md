# Storage

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [File Path](#file-path)
-   [Reading File](#baca-file)
-   [Writing File](#tulis-file)
-   [Deleting File](#hapus-file)
-   [Uploading File](#upload-file)
-   [File Extension](#ekstensi-file)
-   [Checking File Type](#memeriksa-tipe-file)
-   [MIME-Type](#mime-type)
-   [Copying Directory](#salin-direktori)
-   [Deleting Directory](#hapus-direktori)

<!-- /MarkdownTOC -->

<a id="file-path"></a>

## File Path

#### Checking directory/file existence:

```php
Storage::exists('foo/bar/');    // true
Storage::exists('foo/bar.txt'); // true
```

#### Checks that the given path is a file:

```php
Storage::isfile('foo/bar/');    // false
Storage::isfile('foo/bar.txt'); // true
```

#### Checks that the given path is a directory:

```php
Storage::isdir('foo/bar/');    // true
Storage::isdir('foo/bar.txt'); // false
```

<a id="baca-file"></a>

## Reading File

#### Reading file data:

```php
$contents = Storage::get('path/to/file');
```

<a id="tulis-file"></a>

## Writing File

#### Write data to file:

```php
Storage::put('path/to/file', 'isi file');
```

#### Appending data to file:

```php
Storage::append('path/to/file', 'isi file yang ditambahkan');
```

<a id="hapus-file"></a>

## Deleting File

#### Deleting a file:

```php
Storage::delete('path/to/file.ext');
```

<a id="upload-file"></a>

## Uploading File

#### Moving file from `$_FILES` to disk:

```php
Input::upload('picture', 'path/to/pictures', 'filename.ext');
```

> Anda dapat dengan mudah mevalidasi file upload menggunakan [Validator](/docs/id/validation).

<a id="ekstensi-file"></a>

## File Extension

#### Retrieving file extension:

```php
Storage::extension('picture.png');
```

<a id="memeriksa-tipe-file"></a>

## Checking File Type

#### Checks if a file is of a certain type:

```php
if (Storage::is('jpg', 'path/to/file.jpg')) {
    // ..
}
```

The `is()` method doesn't just check for file extensions. It uses the
[Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extention to read
the contents of the file and determine the actual MIME-Type.

<a id="mime-type"></a>

## MIME-Type

#### Retrieves the MIME-Type associated with the extension:

```php
echo Storage::mime('lolcat.gif'); // output: 'image/gif'
```

> You need to enable the [Fileinfo] extension(https://www.php.net/manual/en/book.fileinfo.php)
> before using this `mime()` method.

<a id="salin-direktori"></a>

## Copying Directory

#### Copy the directory to a specific location recursively:

```php
Storage::cpdir($directory, $destination);
```

<a id="hapus-direktori"></a>

## Deleting Directory

#### Remove specific directories recursively:

```php
Storage::rmdir($directory);
```
