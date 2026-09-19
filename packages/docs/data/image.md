# Image

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Loading Images](#loading-images)
-   [Image Manipulation](#image-manipulation)
    -   [Resize Images](#resize-images)
    -   [Rotation & Cropping](#rotation-and-cropping)
    -   [Watermark](#watermark)
-   [Image Effects](#image-effects)
    -   [Brightness, Contrast & Smoothness](#brightness-contrast-and-smoothness)
    -   [Blur & Grayscale](#blur-and-grayscale)
    -   [Other Effects](#other-effects)
-   [Image Export](#image-export)
-   [Additional Features](#additional-features)
    -   [Image Info](#viewing-image-info)
    -   [Preview to Browser](#preview-to-browser)
    -   [Identicon](#creating-identicon)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Image` resizes, crops and re-encodes images, which is what an oversized upload
usually needs before it is stored.

> This component requires the [PHP GD](https://www.php.net/manual/en/book.image.php) extension.
> Make sure this extension is active on your server.

<a id="loading-images"></a>

## Loading Images

`open()` loads the image to work on.

#### Loading a target image:

```php
$image = Image::open('assets/images/test.jpg');
```

The second parameter is the export quality, `0` to `100`, defaulting to `75`.

#### Loading and setting image quality:

```php
$image = Image::open('assets/images/test.jpg', 90); // High quality
```

Supported image formats: **JPG**, **PNG**, **GIF**

<a id="image-manipulation"></a>

## Image Manipulation

As explained above, several methods are provided for manipulating images,
starting from setting quality, setting width, height, cropping, rotation, and adding effects to images.

Ready, right? Let's try!

<a id="resize-images"></a>

### Resize Images

When handling image uploads, you certainly want to save the image file to a smaller size to save storage space.

#### Setting image width:

```php
$image->width(100); // 100 pixels
```

#### Setting image height:

```php
$image->height(100); // 100 pixels
```

> Both methods keep the aspect ratio: `width()` recalculates the height proportionally, and `height()` recalculates the width.

<a id="rotation-and-cropping"></a>

### Rotation & Cropping

An upload is not always upright, least of all a photo from a phone camera.

#### Rotating image position:

```php
$image->rotate(90); // rotate 90 degrees

$image->rotate(180); // rotate 180 degrees
```

> The `rotate()` method only accepts values in multiples of 90.

#### Crop image

Cropping comes in two forms, manual and ratio-based.

```php
$left = 50;   // Starting X position for crop
$top = 20;    // Starting Y position for crop
$width = 100; // Crop area width
$height = 100; // Crop area height

$image->crop($left, $top, $width, $height);
```

Ratio-based cropping works out the position itself, keeping the image proportional:

```php
$width = 16;  // Width ratio
$height = 9;  // Height ratio

$image->ratio($width, $height); // Crop to 16:9 ratio
```

Common ratio examples:
- `1:1` - Square (for avatars, Instagram)
- `16:9` - Widescreen (for videos, banners)
- `4:3` - Standard (for classic photos)
- `21:9` - Ultrawide (for cinema)

<a id="watermark"></a>

### Watermark

A watermark goes in the bottom right corner, 10 pixels from the edges:

```php
$image->watermark('assets/images/watermark.png');
```

> Watermark images can be JPG, PNG or GIF. PNG images with transparency (alpha channel) are supported.

<a id="image-effects"></a>

## Image Effects

An upload may come out too dim or too bright, and a few effects are available besides.

<a id="brightness-contrast-and-smoothness"></a>

### Brightness, Contrast & Smoothness

Brightness, contrast and smoothness are each a single call.

#### Setting brightness:

```php
$image->brightness(40);  // Value: -100 to 100 (0 = normal)
```

Positive values will make the image brighter, negative values darker.

#### Setting contrast:

```php
$image->contrast(80);    // Value: -100 to 100 (0 = normal)
```

Positive values will increase contrast, negative values decrease contrast.

#### Setting smoothness:

```php
$image->smoothness(5);   // Value: -100 to 100
```

Higher values will make the image smoother (useful for reducing noise).

<a id="blur-and-grayscale"></a>

### Blur & Grayscale

You can add blur (blurring) and grayscale (black and white) effects to the image.

#### Blur effect:

```php
$image->blur();         // Gaussian blur (standard blur)
$image->blur(true);     // Selective blur (more subtle selective blur)
```

Blur effects are useful for obscuring parts of the image or adding depth of field.

#### Grayscale effect:

```php
$image->grayscale();
```

Converts the image to black and white (gray scale).

<a id="other-effects"></a>

### Other Effects

The remaining effects:

#### Sepia effect:

```php
$image->sepia();
```

Provides a vintage/classic effect with brownish tones.

#### Emboss effect:

```php
$image->emboss();
```

Provides a raised/relief effect on the image.

#### Edge-highlight effect:

```php
$image->edge();
```

Detects and highlights edges/lines in the image.

#### Sketch effect:

```php
$image->sketch();
```

Provides a pencil sketch effect on the image.

#### Invert effect:

```php
$image->invert();
```

Reverses the image colors (negative).

#### Pixelate effect:

```php
$image->pixelate(10);  // Parameter: pixel block size (-100 to 100)
```

Provides a pixelated/mosaic effect on the image. Larger values make larger pixels.

<a id="image-export"></a>

## Image Export

Save the result to a file:

#### Saving the image result to a file:

```php
$image->export('assets/images/result.jpg');
```

`export()` picks the format from the file extension. Supported: **JPG**, **PNG**, **GIF**.

#### Overwrite existing file:

```php
// By default, it will error if the file already exists
$image->export('assets/images/result.jpg'); // Error if file exists

// Force overwrite existing file
$image->export('assets/images/result.jpg', true);
```

The second parameter (`$overwrite`) is used to determine whether existing files can be overwritten.
- `false` (default) - Will throw an exception if the file exists
- `true` - Will overwrite the existing file without warning

#### Complete manipulation example:

```php
// Open image
$image = Image::open('uploads/photo.jpg', 85);

// Resize (the height follows proportionally)
$image->width(800);

// Add watermark
$image->watermark('assets/watermark.png');

// Add effects
$image->brightness(10);
$image->contrast(5);

// Save result
$image->export('assets/photos/photo-processed.jpg');
```

<a id="additional-features"></a>

## Additional Features

<a id="viewing-image-info"></a>

### Image Info

To view detailed image information, use the `info()` method:

```php
$info = $image->info();

// Available information:
// - path: Absolute path of the image file
// - type: MIME type (image/jpeg, image/png or image/gif)
// - width: Image width (pixels)
// - height: Image height (pixels)
// - quality: Export quality (0 - 100)
// - exif: EXIF data (JPG only, if available, for photos from cameras)
```

<a id="preview-to-browser"></a>

### Preview to Browser

Send the image to the browser without saving it first:

```php
// Preview manipulated image
return $image->dump();
```

The `dump()` method answers a `Response` carrying the PNG and a
`Content-Type: image/png` header, so returning it from a controller shows the
image in the browser. Useful for preview or testing.

<a id="creating-identicon"></a>

### Identicon

An [identicon](https://en.wikipedia.org/wiki/Identicon) is an avatar derived from a
string:

```php
// Create identicon (default 64 pixel size)
$identicon = Image::identicon('budi');

// Create identicon with custom size
$identicon = Image::identicon('budi', 200);

// Preview identicon directly to browser
return Image::identicon('budi', 64, true);

// Save identicon to file
Storage::put(path('storage').'avatars/budi.png', $identicon);
```

> The same string always gives the same identicon, so a default avatar needs no
> storage of its own.
