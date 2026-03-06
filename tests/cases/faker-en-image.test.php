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

    public function testFakerImageUsable()
    {
        $this->assertTrue(extension_loaded('curl'));
    }

    public function testImageUrlUses640x680AsTheDefaultSize()
    {
        $this->assertRegExp('#^https://placehold.co/640/480/#', Image::imageUrl());
    }

    public function testImageUrlAcceptsCustomWidthAndHeight()
    {
        $this->assertRegExp('#^https://placehold.co/800/400/#', Image::imageUrl(800, 400));
    }

    public function testImageUrlAcceptsCustomBackgroundColor()
    {
        $this->assertRegExp('#^https://placehold.co/800/400/cdcdcd/#', Image::imageUrl(800, 400, 'cdcdcd'));
    }

    public function testImageUrlAcceptsCustomBackgroundAndForegroundColor()
    {
        $this->assertRegExp('#^https://placehold.co/800/400/cdcdcd/ffffff/#', Image::imageUrl(800, 400, 'cdcdcd', 'ffffff'));
    }

    public function testImageUrlAcceptsCustomText()
    {
        $this->assertEquals('https://placehold.co/800/400/cdcdcd/jpg?text=Hello+World', Image::imageUrl(800, 400, 'cdcdcd', null, 'Hello World'));
    }
}
