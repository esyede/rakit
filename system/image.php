<?php

namespace System;

defined('DS') or exit('No direct access.');

class Image
{
    /**
     * Contains the singleton instance of this class.
     *
     * @var object
     */
    private static $singleton;

    /**
     * Contains the resource of the image.
     *
     * @var \GdImage|resource
     */
    protected $image;

    /**
     * Contains the file path (absolute).
     *
     * @var string
     */
    protected $path;

    /**
     * Contains the type of the image.
     *
     * @var string
     */
    protected $type;

    /**
     * Contains the quality of the image.
     *
     * @var int
     */
    protected $quality;

    /**
     * Contains the width of the image.
     *
     * @var int
     */
    protected $width;

    /**
     * Contains the height of the image.
     *
     * @var int
     */
    protected $height;

    /**
     * Contains the exif data.
     *
     * @var array
     */
    protected $exif = [];

    /**
     * Constructor.
     *
     * @param string $path
     * @param int    $quality
     */
    public function __construct($path, $quality = 75)
    {
        $this->reset();
        $this->path = $this->path($path);

        if (!is_file($this->path)) {
            throw new \Exception(sprintf('Source image does not exists: %s', $this->path));
        }

        $this->width = 0;
        $this->height = 0;
        $this->quality = $this->level($quality, 0, 100, 'quality');

        $this->load($path);
    }

    /**
     * Open image for processing (jpg, png, gif).
     *
     * @param string $path
     *
     * @return $this
     */
    public static function open($path, $quality = 75)
    {
        if (!is_null(self::$singleton)) {
            static::$singleton->reset();
            return static::$singleton;
        }

        return static::$singleton = new static($path, $quality);
    }

    /**
     * Load image file.
     *
     * @param string $path
     *
     * @return $this
     */
    protected function load($path)
    {
        if (!static::available()) {
            throw new \Exception('The PHP GD extension is not available.');
        }

        if (!$this->acceptable($path)) {
            throw new \Exception('Only JPG, PNG or GIF file type is supported.');
        }

        $this->exif = [];
        list($this->width, $this->height, $this->type) = getimagesize($path);

        if ($this->type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = exif_read_data($path, 'IFD0');
            $this->exif = (is_array($exif) && !empty($exif)) ? $exif : [];
        }

        switch ($this->type) {
            case IMAGETYPE_JPEG:
                $this->image = imagecreatefromjpeg($path);
                break;

            case IMAGETYPE_PNG:
                $this->image = imagecreatefrompng($path);
                break;

            case IMAGETYPE_GIF:
                $this->image = imagecreatefromgif($path);
                break;

            default:
                throw new \Exception('Attempting to load unsupported image type.');
        }

        return $this;
    }

    /**
     * Change the width of the image.
     *
     * @param int $value
     *
     * @return $this
     */
    public function width($value)
    {
        $value = (int) $value;
        $new_height = ($value / $this->width) * $this->height;
        $canvas = imagecreatetruecolor($value, $new_height);

        imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $value, $new_height, $this->width, $this->height);

        $this->image = $canvas;
        $this->maintain();

        return $this;
    }

    /**
     * Change the height of the image.
     *
     * @param int $value
     *
     * @return $this
     */
    public function height($value)
    {
        $value = (int) $value;
        $width = ($value / $this->height) * $this->width;
        $canvas = imagecreatetruecolor($width, $value);

        imagecopyresampled($canvas, $this->image, 0, 0, 0, 0, $width, $value, $this->width, $this->height);

        $this->image = $canvas;
        $this->maintain();

        return $this;
    }

    /**
     * Rotate the image by 90 degrees.
     *
     * @param int $angle
     *
     * @return $this
     */
    public function rotate($angle = 90)
    {
        $angle = (int) $angle;

        if ($angle % 90 !== 0) {
            throw new \Exception('The image can only be rotated at 90 degree intervals.');
        }

        $this->image = imagerotate($this->image, $angle, 0);
        $this->maintain();

        return $this;
    }

    /**
     * Crop the image.
     *
     * @param int $left
     * @param int $top
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function crop($left, $top, $width, $height)
    {
        if (($left + $width) > $this->width || ($top + $height) > $this->height) {
            throw new \Exception('The cropping selection is out of bounds.');
        }

        $canvas = imagecreatetruecolor($width, $height);
        imagecopy($canvas, $this->image, 0, 0, $left, $top, $width, $height);

        $this->image = $canvas;
        $this->maintain();

        return $this;
    }

    /**
     * Resize the image from the center using the given ratio.
     * e.g. 500x200 ratio 1:1 (square) = 200x200.
     * e.g: 500x200 ratio 3:4 = 150x200.
     *
     * @param int $width
     * @param int $height
     *
     * @return $this
     */
    public function ratio($width = 1, $height = 1)
    {
        if ($width < 0) {
            throw new \Exception('The width ratio must be a greater than zero.');
        }

        if ($height < 0) {
            throw new \Exception('The height ratio must be a greater than zero.');
        }

        $original = $this->width / $this->height;
        $new = $width / $height;

        if ($new === $original) {
            return $this;
        }

        if ($new < $original) {
            $new_width = ($this->height / $height) * $width;
            $new_height = $this->height;
            $x = ($this->width / 2) - $new_width / 2;
            $y = 0;
        }

        if ($new > $original) {
            $new_height = ($this->width / $width) * $height;
            $new_width = $this->width;
            $x = 0;
            $y = ($this->height / 2) - $new_height / 2;
        }

        // Crop from center
        $this->crop($x, $y, $new_width, $new_height);
        return $this;
    }

    /**
     * Adjust the contrast of the image (range: -100 to +100).
     *
     * @param int $level
     *
     * @return $this
     */
    public function contrast($level)
    {
        $level = $this->level($level, -100, 100, 'contrast');
        imagefilter($this->image, IMG_FILTER_CONTRAST, $level);
        return $this;
    }

    /**
     * Adjust the brightness of the image (range: -100 to +100).
     *
     * @param int $level
     *
     * @return $this
     */
    public function brightness($level)
    {
        $level = $this->level($level, -100, 100, 'brightness');
        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
        return $this;
    }

    /**
     * Adjust the smoothness of the image (range: -100 to +100).
     *
     * @param int $level
     *
     * @return $this
     */
    public function smoothness($level)
    {
        $level = $this->level($level, -100, 100, 'smoothness');
        imagefilter($this->image, IMG_FILTER_SMOOTH, $level);
        return $this;
    }

    /**
     * Apply selective blur effect.
     *
     * @param bool $selective
     *
     * @return $this
     */
    public function blur($selective = false)
    {
        imagefilter($this->image, $selective ? IMG_FILTER_SELECTIVE_BLUR : IMG_FILTER_GAUSSIAN_BLUR);
        return $this;
    }

    /**
     * Apply grayscale effect.
     *
     * @return $this
     */
    public function grayscale()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        return $this;
    }

    /**
     * Apply sepia effect.
     *
     * @return $this
     */
    public function sepia()
    {
        imagefilter($this->image, IMG_FILTER_GRAYSCALE);
        imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 45);
        return $this;
    }

    /**
     * Apply edge-highlight effect.
     *
     * @return $this
     */
    public function edge()
    {
        imagefilter($this->image, IMG_FILTER_EDGEDETECT);
        return $this;
    }

    /**
     * Apply emboss effect.
     *
     * @return $this
     */
    public function emboss()
    {
        imagefilter($this->image, IMG_FILTER_EMBOSS);
        return $this;
    }

    /**
     * Apply sketch effect.
     *
     * @return $this
     */
    public function sketch()
    {
        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }

    /**
     * Apply invert effect.
     *
     * @return $this
     */
    public function invert()
    {
        imagefilter($this->image, IMG_FILTER_NEGATE);
        return $this;
    }

    /**
     * Apply pixelate effect (range: -100 to +100).
     *
     * @param int $value
     *
     * @return $this
     */
    public function pixelate($value)
    {
        $value = $this->level($value, -100, 100, 'pixelate');
        imagefilter($this->image, IMG_FILTER_PIXELATE, $value);
        return $this;
    }

    /**
     * Apply watermark to image.
     *
     * @param string $watermark
     *
     * @return $this
     */
    public function watermark($watermark)
    {
        $watermark = $this->path($watermark);

        if (!is_file($watermark)) {
            throw new \Exception(sprintf('Watermark file does not exists: %s', $watermark));
        }

        $extension = strtolower((string) Storage::extension($watermark));

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $watermark = imagecreatefromjpeg($watermark);
                break;

            case 'png':
                $watermark = imagecreatefrompng($watermark);
                break;

            case 'gif':
                $watermark = imagecreatefromgif($watermark);
                break;

            default:
                throw new \Exception('Only png, jpg and gif images are supported');
        }

        imagealphablending($this->image, true);

        $src_w = imagesx($watermark);
        $src_h = imagesy($watermark);
        $dst_x = $this->width - $src_w - 10;
        $dst_y = $this->height - $src_h - 10;

        imagecopy($this->image, $watermark, $dst_x, $dst_y, 0, 0, $src_w, $src_h);

         if (PHP_VERSION_ID < 80000) {
             /** @disregard */
             imagedestroy($watermark);
         } else {
             $watermark = null;
         }

        return $this;
    }

    /**
     * Save changes to disk.
     *
     * @param string $path
     * @param bool   $overwrite
     *
     * @return bool
     */
    public function export($path, $overwrite = false)
    {
        $this->maintain();
        $this->path = $this->path($path);

        if (is_file($path) && !$overwrite) {
            throw new \Exception(sprintf('Destination file already exists: %s', $this->path));
        }

        $extension = Storage::extension($this->path);

        switch ($extension) {
            case 'jpg':
                if (!imagejpeg($this->image, $this->path, $this->quality)) {
                    throw new \Exception('The JPG file could not be saved!');
                }
                break;

            case 'png':
                imagealphablending($this->image, false);
                imagesavealpha($this->image, true);

                if (!imagepng($this->image, $this->path)) {
                    throw new \Exception('The PNG file could not be saved.');
                }
                break;

            case 'gif':
                if (!imagegif($this->image, $this->path, $this->quality)) {
                    throw new \Exception('The GIF file could not be saved.');
                }
                break;

            default:
                throw new \Exception('Bad filetype given, must be JPG, PNG or GIF.');
        }
    }

    /**
     * Return the image resource.
     *
     * @return resource
     */
    public function dump()
    {
        $result = imagepng($this->image);
        $this->reset();

        return $result;
    }

    /**
     * Get image information.
     *
     * @return array
     */
    public function info()
    {
        $type = null;

        switch ($this->type) {
            case IMAGETYPE_JPEG:
                $type = 'image/jpeg';
                break;

            case IMAGETYPE_PNG:
                $type = 'image/png';
                break;

            case IMAGETYPE_GIF:
                $type = 'image/gif';
                break;

            default:
                throw new \Exception('Only jpg, png and gif image are supported');
        }

        return [
            'path' => $this->path,
            'type' => $type,
            'width' => $this->width,
            'height' => $this->height,
            'quality' => $this->quality,
            'exif' => $this->exif,
        ];
    }

    /**
     * Reset the image resource.
     *
     * @return void
     */
    public function reset()
    {
        if (
            false !== stripos(gettype($this->image), 'resource')
            && 'gd' === strtolower(get_resource_type($this->image))
        ) {
            if (PHP_VERSION_ID < 80000) {
                /** @disregard */
                imagedestroy($this->image);
            } else {
                $this->image = null;
            }
        }

        $this->image = null;
        $this->path = null;
        $this->width = 0;
        $this->height = 0;
        $this->quality = 75;
        $this->exif = [];
    }

    /**
     * Create an identicon.
     *
     * @param string $seed
     * @param int    $size
     * @param bool   $display
     *
     * @return Response|resource
     */
    public static function identicon($seed, $size = 64, $display = false)
    {
        if (!static::available()) {
            throw new \Exception('The PHP GD extension is not available');
        }

        $size = ($size < 16) ? 16 : (int) $size;
        $hash = sha1($seed);
        $image = imagecreatetruecolor($size, $size);
        $sprites = [
            [.5, 1, 1, 0, 1, 1],
            [.5, 0, 1, 0, .5, 1, 0, 1],
            [.5, 0, 1, 0, 1, 1, .5, 1, 1, .5],
            [0, .5, .5, 0, 1, .5, .5, 1, .5, .5],
            [0, .5, 1, 0, 1, 1, 0, 1, 1, .5],
            [1, 0, 1, 1, .5, 1, 1, .5, .5, .5],
            [0, 0, 1, 0, 1, .5, 0, 0, .5, 1, 0, 1],
            [0, 0, .5, 0, 1, .5, .5, 1, 0, 1, .5, .5],
            [.5, 0, .5, .5, 1, .5, 1, 1, .5, 1, .5, .5, 0, .5],
            [0, 0, 1, 0, .5, .5, 1, .5, .5, 1, .5, .5, 0, 1],
            [0, .5, .5, 1, 1, .5, .5, 0, 1, 0, 1, 1, 0, 1],
            [.5, 0, 1, 0, 1, 1, .5, 1, 1, .75, .5, .5, 1, .25],
            [0, .5, .5, 0, .5, .5, 1, 0, 1, .5, .5, 1, .5, .5, 0, 1],
            [0, 0, 1, 0, 1, 1, 0, 1, 1, .5, .5, .25, .5, .75, 0, .5, .5, .25],
            [0, .5, .5, .5, .5, 0, 1, 0, .5, .5, 1, .5, .5, 1, .5, .5, 0, 1],
            [0, 0, 1, 0, .5, .5, .5, 0, 0, .5, 1, .5, .5, 1, .5, .5, 0, 1],
        ];

        list($r, $g, $b) = static::rgb(hexdec(substr((string) $hash, -3)));

        $color = imagecolorallocate($image, $r, $g, $b);
        imagefill($image, 0, 0, IMG_COLOR_TRANSPARENT);

        $ctr = count($sprites);
        $dim = 4 * floor($size / 4) * 0.5;

        for ($j = 0, $y = 2; $j < $y; $j++) {
            for ($i = $j, $x = 3 - $j; $i < $x; $i++) {
                $sprite = imagecreatetruecolor($dim, $dim);
                imagefill($sprite, 0, 0, IMG_COLOR_TRANSPARENT);
                $block = $sprites[hexdec($hash[($j * 4 + $i) * 2]) % $ctr];

                for ($k = 0, $points = count($block); $k < $points; $k++) {
                    $block[$k] *= $dim;
                }

                imagefilledpolygon($sprite, $block, $points / 2, $color);

                $sh = $dim / 2;

                for ($k = 0; $k < 4; $k++) {
                    imagecopyresampled($image, $sprite, $i * $sh, $j * $sh, 0, 0, $sh, $sh, $dim, $dim);
                    $image = imagerotate($image, 90, imagecolorallocatealpha($image, 0, 0, 0, 127));
                }

                if (PHP_VERSION_ID >= 80000) {
                    /** @disregard */
                    imagedestroy($sprite);
                } else {
                    $sprite = null;
                }
            }
        }

        imagesavealpha($image, true);
        $result = imagepng($image);

        if (PHP_VERSION_ID >= 80000) {
            /** @disregard */
            imagedestroy($image);
        } else {
            $image = null;
        }

        return $display ? Response::make($result, 200, ['Content-Type' => 'image/png']) : $result;
    }

    /**
     * Transform RGB color to array.
     *
     * @param int|string $color
     *
     * @return array|false
     */
    private static function rgb($color)
    {
        $color = is_string($color) ? hexdec($color) : $color;
        $hex = str_pad(dechex($color), (($color < 4096) ? 3 : 6), '0', STR_PAD_LEFT);
        $length = mb_strlen($hex, '8bit');

        if ($length > 6) {
            throw new \Exception(sprintf('Invalid color specified: 0x%s', $hex));
        }

        $color = str_split($hex, $length / 3);

        foreach ($color as &$hue) {
            $hue = hexdec(str_repeat($hue, 6 / $length));
            unset($hue);
        }

        return $color;
    }

    /**
     * Helper method to maintain image dimensions.
     */
    protected function maintain()
    {
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
    }

    /**
     * Retrieve the path to the image file (absolute).
     *
     * @param string $path
     *
     * @return string
     */
    public function path($path)
    {
        return path('base') . str_replace(['/', '\\'], DS, ltrim(ltrim($path, '/'), '\\'));
    }

    /**
     * Check if PHP GD extension is loaded.
     *
     * @return bool
     */
    public static function available()
    {
        return extension_loaded('gd');
    }

    /**
     * Check if the image file is acceptable.
     *
     * @param string $path
     *
     * @return bool
     */
    public static function acceptable($path)
    {
        return in_array(Storage::mime($path), ['image/gif', 'image/jpeg', 'image/pjpeg', 'image/png']);
    }

    /**
     * Helper method to validate level.
     *
     * @param int    $value
     * @param int    $low
     * @param int    $high
     * @param string $method
     *
     * @return int
     */
    private function level($value, $low, $high, $method)
    {
        $bounds = range($low, $high);

        if (!in_array($value, $bounds)) {
            throw new \Exception(sprintf('The %s level should be between %s to %s', $method, $low, $high));
        }

        return  (int) $value;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->reset();
    }
}
