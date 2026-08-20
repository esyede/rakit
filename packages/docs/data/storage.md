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
-   [Other Methods](#other-methods)

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

#### Add data to the beginning of a file:

```php
Storage::prepend('path/to/file', 'prepended file contents');
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

#### Empty a directory without deleting the directory itself:

```php
Storage::cleandir($directory);
```

<a id="other-methods"></a>

## Other Methods

| Method                              | Description                                                              |
| ----------------------------------- | ------------------------------------------------------------------------ |
| `mkdir($path, $chmod)`              | Create a directory recursively, throws if it already exists              |
| `move($from, $to, $overwrite)`      | Move or rename a file                                                    |
| `mvdir($from, $to, $overwrite)`     | Move or rename a directory                                               |
| `copy($path, $target)`              | Copy a file                                                              |
| `glob($pattern, $flags)`            | Search for paths matching a pattern                                      |
| `latest($directory, $options)`      | The most recently modified file in a directory, as a `SplFileInfo`       |
| `size($path)`                       | File size in bytes                                                       |
| `modified($path)`                   | Last modification time as a UNIX timestamp                               |
| `type($path)`                       | File type, for example `file` or `dir`                                   |
| `hash($path)`                       | MD5 hash of the file contents                                            |
| `chmod($path, $mode)`               | Change the permission, or read it when `$mode` is not given              |
| `name($path)`                       | File name without extension                                              |
| `basename($path)`                   | File name with extension                                                 |
| `dirname($path)`                    | Directory of the given path                                              |
| `protect($path)`                    | Drop an empty `index.html` into the directory so it cannot be browsed    |

> `move()`, `mvdir()`, `delete()`, `rmdir()`, and `mkdir()` throw an exception
> when the source does not exist or the target already exists. Check with
> `exists()` first if you are not sure.
