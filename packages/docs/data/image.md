# Image

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Opening an Image](#opening-an-image)
-   [Resizing and Cropping](#resizing-and-cropping)
-   [Watermark](#watermark)
-   [Effects](#effects)
-   [Saving and Output](#saving-and-output)
    -   [Saving to a File](#saving-to-a-file)
    -   [Sending to the Browser](#sending-to-the-browser)
    -   [Image Info](#image-info)
-   [Identicon](#identicon)
-   [Other Methods](#other-methods)
-   [Example](#example)

<!-- /MarkdownTOC -->

<a id="introduction"></a>
<a id="basic-knowledge"></a>

## Introduction

`Image` resizes, crops, filters and re-encodes JPG, PNG and GIF images, which is
what an oversized upload usually needs before it is stored.

> This component requires the [PHP GD](https://www.php.net/manual/en/book.image.php) extension.

<a id="opening-an-image"></a>
<a id="loading-images"></a>

## Opening an Image

`open()` loads the image. The optional second argument is the JPG export quality,
`0` to `100` (default `75`):

```php
$image = Image::open('assets/images/test.jpg');
$image = Image::open('assets/images/test.jpg', 90);
```

All paths in this component are relative to the project root (`path('base')`).
Manipulation methods return the instance, so they can be chained.

An image over `Image::$max_pixels` pixels (40 million by default, about 160 MB once decoded)
throws before it is decoded, so an upload declaring a huge size in a tiny file cannot exhaust the
memory. Raise it, or set it to `null`, for images you trust:

```php
Image::$max_pixels = 100000000;
```

<a id="resizing-and-cropping"></a>
<a id="image-manipulation"></a>
<a id="resize-images"></a>
<a id="rotation-and-cropping"></a>

## Resizing and Cropping

```php
$image->width(800);              // height follows, keeping the aspect ratio
$image->height(600);             // width follows, keeping the aspect ratio
$image->rotate(90);              // multiples of 90 only, counter-clockwise
$image->crop(50, 20, 100, 100);  // left, top, width, height
$image->ratio(16, 9);            // crop from the center to 16:9
```

`rotate()` throws an exception for an angle that is not a multiple of 90, and
`crop()` throws one if the area falls outside the image.

<a id="watermark"></a>

## Watermark

The watermark (JPG, PNG or GIF, PNG transparency kept) is placed in the bottom right
corner, 10 pixels from the edges:

```php
$image->watermark('assets/images/watermark.png');
```

<a id="effects"></a>
<a id="image-effects"></a>
<a id="brightness-contrast-and-smoothness"></a>
<a id="blur-and-grayscale"></a>
<a id="other-effects"></a>

## Effects

| Method               | Effect                                                         |
| -------------------- | -------------------------------------------------------------- |
| `brightness($level)` | `-100` (darker) to `100` (brighter), `0` = unchanged           |
| `contrast($level)`   | `-100` to `100`, `0` = unchanged                               |
| `smoothness($level)` | `-100` to `100`, higher is smoother (reduces noise)            |
| `pixelate($size)`    | `-100` to `100`, block size in pixels                          |
| `blur($selective)`   | Gaussian blur, or selective blur when `true`                   |
| `grayscale()`        | Black and white                                                |
| `sepia()`            | Brownish vintage tone                                          |
| `emboss()`           | Raised relief                                                  |
| `edge()`             | Highlight edges                                                |
| `sketch()`           | Pencil sketch look                                             |
| `invert()`           | Negative colors                                                |

A level outside its range throws an exception.

```php
$image->brightness(10)->contrast(5)->grayscale();
```

<a id="saving-and-output"></a>
<a id="image-export"></a>

## Saving and Output

<a id="saving-to-a-file"></a>

### Saving to a File

`export()` picks the format (JPG, PNG or GIF) from the file extension. It throws an
exception if the file already exists, unless the second argument is `true`:

```php
$image->export('assets/images/result.jpg');
$image->export('assets/images/result.jpg', true); // overwrite
```

<a id="sending-to-the-browser"></a>
<a id="preview-to-browser"></a>

### Sending to the Browser

`dump()` returns a `Response` with the image as PNG (`Content-Type: image/png`),
then frees the image:

```php
return $image->dump();
```

<a id="image-info"></a>
<a id="viewing-image-info"></a>

### Image Info

`info()` returns an array with `path`, `type` (MIME type), `width`, `height`,
`quality` and `exif` (JPG only, when the exif extension is available):

```php
$info = $image->info();
```

<a id="identicon"></a>
<a id="additional-features"></a>
<a id="creating-identicon"></a>

## Identicon

`Image::identicon()` builds an [identicon](https://en.wikipedia.org/wiki/Identicon)
avatar from a string and returns the PNG bytes. The same string always gives the
same image. Size defaults to `64` (minimum `16`); pass `true` as the third argument
to get a `Response` instead:

```php
$png = Image::identicon('budi', 200);
Storage::put(path('storage').'avatars/budi.png', $png);

return Image::identicon('budi', 64, true);
```

<a id="other-methods"></a>

## Other Methods

| Method                        | Description                                         |
| ----------------------------- | --------------------------------------------------- |
| `Image::available()`          | Whether the GD extension is loaded                  |
| `Image::acceptable($path)`    | Whether a file's MIME type is JPG, PNG or GIF       |
| `$image->path($path)`         | Resolve a path relative to the project root         |
| `$image->reset()`             | Free the loaded image and reset the instance        |

<a id="example"></a>

## Example

```php
Image::open('uploads/photo.jpg', 85)
    ->width(800)
    ->watermark('assets/watermark.png')
    ->brightness(10)
    ->export('assets/photos/photo-processed.jpg');
```
