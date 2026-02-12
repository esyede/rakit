<?php

defined('DS') or exit('No direct access.');

use System\Image;

class ImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test image path.
     *
     * @var string
     */
    protected $imagePath;

    /**
     * Temp image path for export.
     *
     * @var string
     */
    protected $tempPath;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->imagePath = 'tests/fixtures/storage/test.png';
        $this->tempPath = 'tests/fixtures/storage/temp_test_' . time() . '.png';

        if (file_exists(path('base') . $this->tempPath)) {
            unlink(path('base') . $this->tempPath);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (file_exists(path('base') . $this->tempPath)) {
            unlink(path('base') . $this->tempPath);
        }

        // Reset singleton
        $reflection = new \ReflectionClass('System\Image');
        $singleton = $reflection->getProperty('singleton');
        /** @disregard */
        $singleton->setAccessible(true);
        $singleton->setValue(null);
    }

    /**
     * Test GD availability.
     */
    public function testAvailable()
    {
        $this->assertTrue(Image::available());
    }

    /**
     * Test acceptable image types.
     */
    public function testAcceptable()
    {
        $this->assertTrue(Image::acceptable($this->imagePath));
        $this->assertFalse(Image::acceptable(path('storage') . 'test.unknown'));
    }

    /**
     * Test opening an image.
     */
    public function testOpen()
    {
        $image = Image::open($this->imagePath);
        $this->assertInstanceOf('System\Image', $image);
    }

    /**
     * Test opening non-existent image throws exception.
     */
    public function testOpenNonExistentThrowsException()
    {
        $this->setExpectedException('Exception', 'Source image does not exists');
        Image::open('nonexistent.png');
    }

    /**
     * Test opening unsupported image throws exception.
     */
    public function testOpenUnsupportedThrowsException()
    {
        $this->setExpectedException('Exception', 'Only JPG, PNG or GIF file type is supported');
        Image::open('tests/fixtures/storage/test.unknown');
    }

    /**
     * Test getting image info.
     */
    public function testInfo()
    {
        $image = Image::open($this->imagePath);
        $info = $image->info();

        $this->assertArrayHasKey('path', $info);
        $this->assertArrayHasKey('type', $info);
        $this->assertArrayHasKey('width', $info);
        $this->assertArrayHasKey('height', $info);
        $this->assertArrayHasKey('quality', $info);
        $this->assertArrayHasKey('exif', $info);
        $this->assertEquals('image/png', $info['type']);
    }

    /**
     * Test changing width.
     */
    public function testWidth()
    {
        $image = Image::open($this->imagePath);
        $originalWidth = $image->info()['width'];
        $newWidth = 100;

        $image->width($newWidth);
        $info = $image->info();

        $this->assertEquals($newWidth, $info['width']);
        $this->assertNotEquals($originalWidth, $info['width']);
    }

    /**
     * Test changing height.
     */
    public function testHeight()
    {
        $image = Image::open($this->imagePath);
        $originalHeight = $image->info()['height'];
        $newHeight = 100;

        $image->height($newHeight);
        $info = $image->info();

        $this->assertEquals($newHeight, $info['height']);
        $this->assertNotEquals($originalHeight, $info['height']);
    }

    /**
     * Test rotating image.
     */
    public function testRotate()
    {
        $image = Image::open($this->imagePath);
        $originalWidth = $image->info()['width'];
        $originalHeight = $image->info()['height'];

        $image->rotate(90);
        $info = $image->info();

        $this->assertEquals($originalHeight, $info['width']);
        $this->assertEquals($originalWidth, $info['height']);
    }

    /**
     * Test rotating invalid angle throws exception.
     */
    public function testRotateInvalidAngleThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The image can only be rotated at 90 degree intervals');
        $image->rotate(45);
    }

    /**
     * Test cropping image.
     */
    public function testCrop()
    {
        $image = Image::open($this->imagePath);
        $cropWidth = 50;
        $cropHeight = 50;

        $image->crop(0, 0, $cropWidth, $cropHeight);
        $info = $image->info();

        $this->assertEquals($cropWidth, $info['width']);
        $this->assertEquals($cropHeight, $info['height']);
    }

    /**
     * Test cropping out of bounds throws exception.
     */
    public function testCropOutOfBoundsThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The cropping selection is out of bounds');
        $image->crop(0, 0, 9999, 9999);
    }

    /**
     * Test applying ratio.
     */
    public function testRatio()
    {
        $image = Image::open($this->imagePath);
        $image->ratio(1, 1); // Square
        $info = $image->info();

        $this->assertEquals($info['width'], $info['height']);
    }

    /**
     * Test ratio with invalid values throws exception.
     */
    public function testRatioInvalidThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The width ratio must be a greater than zero');
        $image->ratio(-1, 1);
    }

    /**
     * Test adjusting contrast.
     */
    public function testContrast()
    {
        $image = Image::open($this->imagePath);
        $image->contrast(50);
        // Just check no exception
        $this->assertTrue(true);
    }

    /**
     * Test contrast out of range throws exception.
     */
    public function testContrastOutOfRangeThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The contrast level should be between -100 to 100');
        $image->contrast(150);
    }

    /**
     * Test adjusting brightness.
     */
    public function testBrightness()
    {
        $image = Image::open($this->imagePath);
        $image->brightness(50);
        $this->assertTrue(true);
    }

    /**
     * Test brightness out of range throws exception.
     */
    public function testBrightnessOutOfRangeThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The brightness level should be between -100 to 100');
        $image->brightness(150);
    }

    /**
     * Test applying blur.
     */
    public function testBlur()
    {
        $image = Image::open($this->imagePath);
        $image->blur();
        $this->assertTrue(true);
    }

    /**
     * Test applying grayscale.
     */
    public function testGrayscale()
    {
        $image = Image::open($this->imagePath);
        $image->grayscale();
        $this->assertTrue(true);
    }

    /**
     * Test applying sepia.
     */
    public function testSepia()
    {
        $image = Image::open($this->imagePath);
        $image->sepia();
        $this->assertTrue(true);
    }

    /**
     * Test applying edge.
     */
    public function testEdge()
    {
        $image = Image::open($this->imagePath);
        $image->edge();
        $this->assertTrue(true);
    }

    /**
     * Test applying emboss.
     */
    public function testEmboss()
    {
        $image = Image::open($this->imagePath);
        $image->emboss();
        $this->assertTrue(true);
    }

    /**
     * Test applying sketch.
     */
    public function testSketch()
    {
        $image = Image::open($this->imagePath);
        $image->sketch();
        $this->assertTrue(true);
    }

    /**
     * Test applying invert.
     */
    public function testInvert()
    {
        $image = Image::open($this->imagePath);
        $image->invert();
        $this->assertTrue(true);
    }

    /**
     * Test applying pixelate.
     */
    public function testPixelate()
    {
        $image = Image::open($this->imagePath);
        $image->pixelate(5);
        $this->assertTrue(true);
    }

    /**
     * Test pixelate out of range throws exception.
     */
    public function testPixelateOutOfRangeThrowsException()
    {
        $image = Image::open($this->imagePath);
        $this->setExpectedException('Exception', 'The pixelate level should be between -100 to 100');
        $image->pixelate(150);
    }

    /**
     * Test exporting image.
     */
    public function testExport()
    {
        $image = Image::open($this->imagePath);
        $image->export($this->tempPath, true);

        $this->assertFileExists(path('base') . $this->tempPath);
    }

    /**
     * Test exporting to existing file without overwrite throws exception.
     */
    public function testExportWithoutOverwriteThrowsException()
    {
        $image = Image::open($this->imagePath);
        $image->export($this->tempPath, true); // Create file

        $image2 = new \System\Image($this->imagePath);
        $this->setExpectedException('Exception', 'Destination file already exists');
        $image2->export($this->tempPath, false);
    }

    /**
     * Test dumping image.
     */
    public function testDump()
    {
        $image = Image::open($this->imagePath);
        ob_start();
        $result = $image->dump();
        ob_end_clean();

        $this->assertTrue($result);
    }

    /**
     * Test resetting image.
     */
    public function testReset()
    {
        $image = Image::open($this->imagePath);
        $image->reset();

        $reflection = new \ReflectionClass($image);
        $path = $reflection->getProperty('path');
        /** @disregard */
        $path->setAccessible(true);

        $this->assertNull($path->getValue($image));
    }

    /**
     * Test creating identicon.
     */
    public function testIdenticon()
    {
        ob_start();
        $result = Image::identicon('test', 64, false);
        ob_end_clean();
        $this->assertTrue($result);
    }

    /**
     * Test identicon with display returns Response.
     */
    public function testIdenticonWithDisplay()
    {
        ob_start();
        $result = Image::identicon('test', 64, true);
        ob_end_clean();
        $this->assertInstanceOf('\System\Response', $result);
    }
}
