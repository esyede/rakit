# Image

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#pengetahuan-dasar)
- [Loading Image](#memuat-gambar)
- [Image Manipulation](#manipulasi-gambar)
    - [Resize](#resize-gambar)
    - [Rotate & Crop](#rotasi-dan-cropping)
    - [Watermark](#watermark)
- [Effects](#efek-gambar)
    - [Brightness, Contrast & Smoothness](#brightness-contrast-dan-smoothness)
    - [Blur & Grayscale](#blur-dan-grayscale)
- [Exporting](#export-gambar)
- [Additional Features](#fitur-tambahan)
    - [Image info](#melihat-info-gambar)
    - [Identicon](#membuat-identicon)

<!-- /MarkdownTOC -->


<a id="pengetahuan-dasar"></a>
## Basic Knowledge

When developing web-based applications, of course you will be dealing with images,
such as the size of the image uploaded by the user whose size is too large, for example.

Of course you don't want to save those large image directly because it takes up too much space.
Don't worry, this component is ready to help you!


<a id="memuat-gambar"></a>
## Loading Image

To start editing images, of course, you first need to determine
which image you want to edit. Use the `open()` method to open the image.


<a id="memuat-gambar-1"></a>
#### Loading image targets:


```php
$image = Image::open('assets/images/test.jpg');
```

If you also want to determine the quality of your exported image,
you can pass your desired export quality to the second parameter,
the value ranges between `0 - 100`, the default value is `75`.


#### Load and adjust image quality:

```php
$image = Image::open('assets/images/test.jpg', 75);
```


<a id="manipulasi-gambar"></a>
## Image Manipulation

As described above, several methods have been provided for manipulating images,
starting from adjusting the quality, adjusting the length, width, cropping,
rotation and giving effects to the image.


Are you ready? let's try!


<a id="resize-gambar"></a>
### Resize

When handling image uploads, of course you want to save the image file with a
smaller size to save your storage space.


#### Mengatur lebar gambar:

```php
$image->width(100); // 100 pixels
```

#### Mengatur tinggi gambar:

```php
$image->height(100); // 100 pixels
```


<a id="rotasi-dan-cropping"></a>
### Rotate & Crop

Sometimes, images uploaded by users are not always in an upright position,
especially photos taken through cellphone cameras.


Don't worry, you rotate those angle.


#### Rotate an image:


```php
$image->rotate(90); // rotate 90 degrees

$image->rotate(180); // rotate 180 degrees
```

>  This `rotate()` method only accepts values in multiples of 90.



#### Cropping gambar

Image cropping operation is super easy. Rakit provides 2 ways to do this,
namely standard cropping and ratio cropping.

```php
$left = 50;
$top = 20;
$width = 100;
$height = 100;

$image->crop($left, $top, $width, $height);
```

Is it too complicated?


Okay, if the above method is too complicated, please use the _"ratio"_ method like this:


```php
$width = 2;
$height = 1;

$image->ratio($width, $height);
```

Well, easier isn't it? which way do you prefer?



<a id="watermark"></a>
### Watermark

Besides crop and rotate images, you can also add a watermark to the image:

```php
$image->watermark('assets/images/watermark.png');
```


<a id="efek-gambar"></a>
## Effects

Sometimes, user uploaded images looks too dim, too bright
or maybe the uploaded image is a convidential image that you need to pixelate.


<a id="brightness-contrast-dan-smoothness"></a>
### Brightness, Contrast & Smoothness

You can also adjust the brightness, contrast and smoothness of the image easily.


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

#### Blur effect:

```php
$image->blur();    // standard (gaussian) blur
$image->blur(true); // selective blur
```

#### Grayscale effect:

```php
$image->grayscale();
```

#### Sepia effect:

```php
$image->sepia();
```

#### Emboss effect:

```php
$image->emboss();
```

#### Edge-highlight effect:

```php
$image->edge();
```


<a id="export-gambar"></a>
## Exporting

Once the image is finished being manipulated, you can export it into a file:


#### Export to file:

```php
$image->export('assets/images/budi.png');
```


<a id="fitur-tambahan"></a>
## Additional Features

In addition to the features above, this library also provides several bonus features:



<a id="melihat-info-gambar"></a>
### Image info

To view image information, use this `info()` method:


```php
$image->info();
```

<a id="membuat-identicon"></a>
### Identicon

You can also create an [identicon](https://en.wikipedia.org/wiki/Identicon) with it:


```php
// Make 64x64 pixels identicon image
$identicon = Image::identicon('budi');

// Make 200x200 pixels identicon image
$identicon = Image::identicon('budi', 200);

// Preview identicon image in browser
return Image::identicon('budi', 64, true);

// Save identicon image to file
Storage::put(path('storage').'budi.jpg', $identicon);
```
