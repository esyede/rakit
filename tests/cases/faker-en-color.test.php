<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\Color;

class FakerEnColorTest extends \PHPUnit_Framework_TestCase
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

    public function testHexColor()
    {
        $this->assertRegExp('~^#[a-f0-9]{6}$~i', Color::hexColor());
    }

    public function testSafeHexColor()
    {
        $this->assertRegExp('~^#[a-f0-9]{6}$~i', Color::safeHexColor());
    }

    public function testRgbColorAsArray()
    {
        $this->assertEquals(3, count(Color::rgbColorAsArray()));
    }

    public function testRgbColor()
    {
        $regexp = '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])';
        $this->assertRegExp('/^' . $regexp . ',' . $regexp . ',' . $regexp . '$/i', Color::rgbColor());
    }

    public function testRgbCssColor()
    {
        $regexp = '([01]?[0-9]?[0-9]|2[0-4][0-9]|25[0-5])';
        $this->assertRegExp('/^rgb\(' . $regexp . ',' . $regexp . ',' . $regexp . '\)$/i', Color::rgbCssColor());
    }

    public function testSafeColorName()
    {
        $this->assertRegExp('/^[\w]+$/', Color::safeColorName());
    }

    public function testColorName()
    {
        $this->assertRegExp('/^[\w]+$/', Color::colorName());
    }
}
