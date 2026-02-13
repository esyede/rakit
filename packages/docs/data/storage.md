# Storage

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [File Path](#file-path)
-   [Read File](#read-file)
-   [Write File](#write-file)
-   [Delete File](#delete-file)
-   [Upload File](#upload-file)
-   [File Extension](#file-extension)
-   [Check File Type](#check-file-type)
-   [MIME-Type](#mime-type)
-   [Copy Directory](#copy-directory)
-   [Delete Directory](#delete-directory)

<!-- /MarkdownTOC -->

<a id="file-path"></a>

## File Path

#### Check existence of directory or file:

```php
Storage::exists('foo/bar/');    // true
Storage::exists('foo/bar.txt'); // true
```

#### Check that the given path is a file:

```php
Storage::isfile('foo/bar/');    // false
Storage::isfile('foo/bar.txt'); // true
```

#### Check that the given path is a directory:

```php
Storage::isdir('foo/bar/');    // true
Storage::isdir('foo/bar.txt'); // false
```

<a id="read-file"></a>

## Read File

#### Retrieve file contents:

```php
$contents = Storage::get('path/to/file');
```

<a id="write-file"></a>

## Write File

#### Write data to a file:

```php
Storage::put('path/to/file', 'file contents');
```

#### Append data to the end of a file:

```php
Storage::append('path/to/file', 'appended file contents');
```

<a id="delete-file"></a>

## Delete File

#### Delete a file:

```php
Storage::delete('path/to/file.ext');
```

<a id="upload-file"></a>

## Upload File

#### Move file from `$_FILES` to disk:

```php
Input::upload('picture', 'path/to/pictures', 'filename.ext');
```

> You can easily validate file uploads using [Validator](/docs/validation).

<a id="file-extension"></a>

## File Extension

#### Retrieve a file's extension:

```php
Storage::extension('picture.png');
```

<a id="check-file-type"></a>

## Check File Type

#### Check if a file is of a specific type:

```php
if (Storage::is('jpg', 'path/to/file.jpg')) {
    // ..
}
```

The `is()` method does not only check the file extension. It will also use the
[Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extension to read the file content and
determine the actual MIME-Type.

<a id="mime-type"></a>

## MIME-Type

#### Retrieve the MIME-Type associated with an extension:

```php
echo Storage::mime('lolcat.gif'); // output: 'image/gif'
```

> You must enable the [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php)
> extension before using this `mime()` method.

<a id="copy-directory"></a>

## Copy Directory

#### Copy directory to a specific location recursively:

```php
Storage::cpdir($directory, $destination);
```

<a id="delete-directory"></a>

## Delete Directory

#### Delete a specific directory recursively:

```php
Storage::rmdir($directory);
```
