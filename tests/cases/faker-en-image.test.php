<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Provider\Image;

class FakerEnImageTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // ..
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testImageUrlUses640x680AsTheDefaultSize()
    {
        $this->assertRegExp('#^http://lorempixel.com/640/480/#', Image::imageUrl());
    }

    public function testImageUrlAcceptsCustomWidthAndHeight()
    {
        $this->assertRegExp('#^http://lorempixel.com/800/400/#', Image::imageUrl(800, 400));
    }

    public function testImageUrlAcceptsCustomCategory()
    {
        $this->assertRegExp('#^http://lorempixel.com/800/400/nature/#', Image::imageUrl(800, 400, 'nature'));
    }

    public function testImageUrlAcceptsCustomText()
    {
        $this->assertRegExp('#^http://lorempixel.com/800/400/nature/Faker#', Image::imageUrl(800, 400, 'nature', false, 'Faker'));
    }

    public function testImageUrlAddsARandomGetParameterByDefault()
    {
        $url = Image::imageUrl(800, 400);
        $splitUrl = preg_split('/\?/', $url);
        $this->assertEquals(count($splitUrl), 2);
        $this->assertRegexp('#\d{5}#', $splitUrl[1]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUrlWithDimensionsAndBadCategory()
    {
        Image::imageUrl(800, 400, 'bullhonky');
    }

    public function testDownloadWithDefaults()
    {
        // FIXME: Error cannot get the image
        // $file = Image::image(sys_get_temp_dir());
        // $this->assertTrue(is_file($file));

        // if (function_exists('getimagesize')) {
        //     list($width, $height, $type, $attr) = getimagesize($file);
        //     $this->assertEquals(640, $width);
        //     $this->assertEquals(480, $height);
        //     $this->assertEquals(constant('IMAGETYPE_JPEG'), $type);
        // } else {
        //     $this->assertEquals('jpg', pathinfo($file, PATHINFO_EXTENSION));
        // }

        // if (file_exists($file)) {
        //     unlink($file);
        // }
    }
}
