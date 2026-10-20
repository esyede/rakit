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
-   [Disks (Local & S3)](#disks-local--s3)

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

<a id="disks-local--s3"></a>

## Disks (Local & S3)

A disk is a place to keep files, configured in `application/config/storage.php` and reached with
`Storage::disk()`. The `local` driver keeps files in a directory; the `s3` driver keeps them in
Amazon S3 or an S3-compatible service (MinIO, Cloudflare R2, DigitalOcean Spaces, Wasabi). Both
answer the same calls, so moving uploads to S3 is a config change:

```php
$disk = Storage::disk('s3');         // Storage::disk() is the 'default' disk

$disk->put('avatars/1.jpg', $contents);
$disk->put('avatars/1.jpg', $contents, ['visibility' => 'public', 'cache_control' => 'max-age=86400']);
$disk->put_file('reports/may.pdf', Input::file('report.tmp_name'));

$disk->get('avatars/1.jpg');         // contents, or NULL (second argument) when missing
$disk->exists('avatars/1.jpg');
$disk->size('avatars/1.jpg');        // bytes
$disk->modified('avatars/1.jpg');    // UNIX timestamp
$disk->files('avatars');             // every file below, as paths
$disk->copy('a.jpg', 'b.jpg');
$disk->move('a.jpg', 'c.jpg');
$disk->delete(['b.jpg', 'c.jpg']);   // a missing file is not an error

$disk->url('avatars/1.jpg');                 // public URL (public objects, or the 'url' CDN)
$disk->temporary_url('reports/may.pdf', 600); // S3: signed link to a private file, 10 minutes
```

An S3 disk needs `key`, `secret`, `region` and `bucket`. For an S3-compatible service set its
`endpoint`, and `path_style => true` for MinIO:

```php
's3' => [
    'driver' => 's3',
    'key' => 'AKIA...',
    'secret' => '...',
    'region' => 'ap-southeast-1',
    'bucket' => 'my-app',
    'endpoint' => null,          // 'http://127.0.0.1:9000' for MinIO, 'https://<account>.r2.cloudflarestorage.com' for R2
    'path_style' => false,
    'url' => null,               // e.g. 'https://cdn.example.com' for url()
    'root' => '',                // keep the disk under a prefix, e.g. 'production'
],
```

Requests are signed with AWS Signature Version 4 and sent through [Curl](/docs/curl), so no SDK
is needed and `Curl::fake()` fakes S3 in tests. A failed request throws with S3's error code
(`AccessDenied`, `SignatureDoesNotMatch`...). Paths containing `..` are refused on both drivers.
Objects are sent in one request (no multipart upload): fine for uploads, not for files of
several gigabytes.
