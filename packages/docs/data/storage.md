# Storage

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [File Path](#file-path)
-   [Read File](#read-file)
-   [Write File](#write-file)
-   [Delete File](#delete-file)
-   [Directories](#directories)
-   [File Type & MIME-Type](#file-type--mime-type)
-   [Upload File](#upload-file)
-   [Other Methods](#other-methods)
-   [Path Restrictions](#path-restrictions)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Storage` is a thin wrapper around PHP's filesystem functions for reading, writing, moving and deleting files and directories. Relative paths are resolved against `path('base')`, and every path is checked against the allowed roots (see [Path Restrictions](#path-restrictions)).

<a id="file-path"></a>

## File Path

```php
Storage::exists('foo/bar.txt'); // file or directory exists
Storage::isfile('foo/bar.txt'); // true only for files
Storage::isdir('foo/bar/');     // true only for directories
```

<a id="read-file"></a>

## Read File

`get()` returns the file contents, or the second argument (default `null`) when the file does not exist:

```php
$contents = Storage::get('path/to/file');
$contents = Storage::get('path/to/file', 'default');
```

> **Security:** never pass raw user input such as `Storage::get(Input::get('file'))` (think `?file=../../etc/passwd`). Map it through an allowlist first:

```php
$allowed = ['report.pdf' => 'exports/report.pdf'];
$file = Input::get('file');

if (isset($allowed[$file])) {
    $contents = Storage::get(path('storage').$allowed[$file]);
}
```

<a id="write-file"></a>

## Write File

```php
Storage::put('path/to/file', 'file contents');      // overwrite (uses LOCK_EX)
Storage::append('path/to/file', 'appended text');   // add to the end
Storage::prepend('path/to/file', 'prepended text'); // add to the beginning
```

`put()` accepts `file_put_contents()` flags as a third argument. Every write also drops an `index.html` into the directory (see `protect()` below).

<a id="delete-file"></a>

## Delete File

```php
Storage::delete('path/to/file.ext');
```

<a id="directories"></a>
<a id="copy-directory"></a>
<a id="delete-directory"></a>

## Directories

```php
Storage::mkdir($path, 0755);              // create recursively
Storage::cpdir($directory, $destination); // copy recursively
Storage::mvdir($from, $to);               // move / rename
Storage::rmdir($directory);               // delete recursively
Storage::cleandir($directory);            // empty it, keep the directory
```

`rmdir()` refuses to remove `path('base')`, `path('storage')` or any allowed root itself.

<a id="file-type--mime-type"></a>
<a id="file-extension"></a>
<a id="check-file-type"></a>
<a id="mime-type"></a>

## File Type & MIME-Type

```php
Storage::extension('picture.png');           // 'png'
Storage::mime('path/to/lolcat.gif');         // 'image/gif', or false if missing
Storage::is('jpg', 'path/to/file.jpg');      // true
Storage::is(['jpg', 'png'], 'path/to/file'); // any of these
```

`mime()` and `is()` read the actual file content through the [Fileinfo](https://www.php.net/manual/en/book.fileinfo.php) extension (which must be enabled), so they are not fooled by a renamed extension.

<a id="upload-file"></a>

## Upload File

`Input::upload()` moves a file from `$_FILES` to disk. It returns `false` when no file was uploaded under that key:

```php
Input::upload('picture', path('storage').'pictures', 'filename.ext');
```

> **Security:** uploads are hardened by default. The extension, MIME type (via `finfo`), size (`upload_max_filesize` or `Upload::$max_size`) and double extensions (`shell.php.jpg`) are checked, and dangerous extensions (`php`, `phtml`, `phar`, `sh`, `htaccess`, `html`, `svg` ...) are rejected with an exception.

For tighter control, use a whitelist, or validate before moving:

```php
use System\Foundation\Http\Upload;

Upload::$allowed_extensions = ['jpg', 'jpeg', 'png', 'pdf'];
Upload::$allowed_mime_types = ['image/jpeg', 'image/png', 'application/pdf'];
Upload::$max_size = 2 * 1024 * 1024; // 2 MB

$rules = ['picture' => 'required|image|max:2048|mimes:jpg,png'];

if (Validator::make(Input::all(), $rules)->passes()) {
    Input::upload('picture', path('storage').'pictures');
}
```

<a id="other-methods"></a>

## Other Methods

| Method                              | Description                                                              |
| ----------------------------------- | ------------------------------------------------------------------------ |
| `move($from, $to, $overwrite)`      | Move or rename a file                                                    |
| `copy($path, $target)`              | Copy a file                                                              |
| `glob($pattern, $flags)`            | Paths matching a pattern (matches outside the allowed roots are dropped) |
| `latest($directory, $options)`      | The most recently modified file in a directory, as a `SplFileInfo`       |
| `size($path)`                       | File size in bytes                                                       |
| `modified($path)`                   | Last modification time as a UNIX timestamp                               |
| `type($path)`                       | File type, for example `file` or `dir`                                   |
| `hash($path)`                       | MD5 hash of the file contents                                            |
| `chmod($path, $mode)`               | Change the permission, or read it when `$mode` is not given              |
| `name($path)`                       | File name without extension                                              |
| `basename($path)`                   | File name with extension                                                 |
| `dirname($path)`                    | Directory of the given path                                              |
| `protect($path)`                    | Drop an `index.html` into the directory so it cannot be browsed          |

> `move()`, `mvdir()`, `delete()`, `rmdir()` and `mkdir()` throw an exception when the source does not exist or the target already exists (`move()` and `mvdir()` overwrite when `$overwrite` is `true`). Check with `exists()` first if you are not sure.

<a id="path-restrictions"></a>

## Path Restrictions

Paths are confined to `path('base')`, `path('storage')` and any directory you add to `Storage::$allowed_roots`. Traversal (`../`), null bytes and stream wrappers (`php://`, `phar://`) throw an exception, so `Storage::put(Input::get('name'), $data)` fails once the name resolves outside the allowed roots.

```php
Storage::$allowed_roots[] = '/mnt/shared';
```

`Storage::$enforce_containment = false` disables these checks entirely; it exists for testing only.
