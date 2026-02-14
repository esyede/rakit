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

In web application development, you will often deal with image-related issues,
such as user-uploaded images that are too large.

Of course, you don't want to store these large images directly in storage as they consume a lot of space.
No worries, this component is ready to help!

> This component requires the [PHP GD](https://php.net/manual/en/book.image.php) extension.
> Make sure this extension is active on your server.

<a id="loading-images"></a>

## Loading Images

To start editing an image, you need to first open the image you want to edit.
Use the `open()` method to open the image.

#### Loading a target image:

```php
$image = Image::open('assets/images/test.jpg');
```

If you want to set the quality of the exported image result,
add the quality value to the second parameter.
The value range is between `0 - 100`, the default is `75`.

#### Loading and setting image quality:

```php
$image = Image::open('assets/images/test.jpg', 90); // High quality
```

Supported image formats: **JPG**, **PNG**, **GIF**, **WEBP**, **BMP**

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

<a id="rotation-and-cropping"></a>

### Rotation & Cropping

Sometimes, images uploaded by users are not always upright,
especially photos taken through mobile phone cameras.

No worries, you can rotate their position.

#### Rotating image position:

```php
$image->rotate(90); // rotate 90 degrees

$image->rotate(180); // rotate 180 degrees
```

> The `rotate()` method only accepts values in multiples of 90.

#### Crop image

Image cropping (cutting) is very easy. Rakit provides
2 ways to do it, namely manual cropping and ratio cropping.

```php
$left = 50;   // Starting X position for crop
$top = 20;    // Starting Y position for crop
$width = 100; // Crop area width
$height = 100; // Crop area height

$image->crop($left, $top, $width, $height);
```

If the above method is too manual, you can use ratio-based cropping.
This method will automatically calculate the crop position to keep the image proportional:

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

In addition to cutting and rotating images, you can also add a watermark to the image.
The watermark will be placed in the bottom right corner by default:

```php
$image->watermark('assets/images/watermark.png');
```

> This method supports watermark images with transparency (PNG with alpha channel).

<a id="image-effects"></a>

## Image Effects

Sometimes, images uploaded by users look too dim, too bright,
or perhaps you want to add special effects to the image.

<a id="brightness-contrast-and-smoothness"></a>

### Brightness, Contrast & Smoothness

You can easily adjust the brightness (brightness), contrast (contrast),
and smoothness (softness) of the image.

#### Setting brightness:

```php
$image->brightness(40);  // Value: -255 to 255 (0 = normal)
```

Positive values will make the image brighter, negative values darker.

#### Setting contrast:

```php
$image->contrast(80);    // Value: -100 to 100 (0 = normal)
```

Positive values will increase contrast, negative values decrease contrast.

#### Setting smoothness:

```php
$image->smoothness(5);   // Value: smoothing level (higher means smoother)
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

Rakit also provides various other visual effects:

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
$image->pixelate(10);  // Parameter: pixel block size (default: 3)
```

Provides a pixelated/mosaic effect on the image. Larger values make larger pixels.

<a id="image-export"></a>

## Image Export

After the image has been manipulated, you can save it to a file:

#### Saving the image result to a file:

```php
$image->export('assets/images/result.jpg');
```

The `export()` method will automatically detect the format based on the file extension.
Supported formats: **JPG**, **PNG**, **GIF**

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

// Resize
$image->width(800);
$image->height(600);

// Add watermark
$image->watermark('assets/watermark.png');

// Add effects
$image->brightness(10);
$image->contrast(5);

// Save result
$image->export('public/photos/photo-processed.jpg');
```

<a id="additional-features"></a>

## Additional Features

In addition to the above features, this component also provides some additional features:

<a id="viewing-image-info"></a>

### Image Info

To view detailed image information, use the `info()` method:

```php
$info = $image->info();

// Available information:
// - width: Image width (pixels)
// - height: Image height (pixels)
// - type: Image type (jpg, png, gif, etc)
// - mime: MIME type (image/jpeg, image/png, etc)
// - size: File size (bytes)
// - exif: EXIF data (if available, for photos from cameras)
```

<a id="preview-to-browser"></a>

### Preview to Browser

You can directly display the image to the browser without saving it to a file:

```php
// Preview manipulated image
return $image->dump();
```

The `dump()` method will send the appropriate headers and display the image directly to the browser.
Useful for preview or testing.

<a id="creating-identicon"></a>

### Identicon

You can create an [identicon](https://en.wikipedia.org/wiki/Identicon)
(unique avatar based on a string) using this component:

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

Identicons are useful for:
- Default user avatars
- Visual identifiers for data
- Unique image placeholders

> The same identicon will always be generated from the same string,
> so it's suitable for consistent avatars without needing to store images.
