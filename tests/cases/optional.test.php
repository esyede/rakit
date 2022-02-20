<?php

defined('DS') or exit('No direct script access.');

use System\Optional;

class OptionalTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Test untuk get target yang memang ada (object).
     *
     * @group system
     */
    public function testGetExistItemOnObject()
    {
        $target = new \stdClass();
        $target->item = 'test';
        $optional = new Optional($target);

        $this->assertEquals('test', $optional->item);
    }

    /**
     * Test untuk get target yang tidak ada (object).
     *
     * @group system
     */
    public function testGetNotExistItemOnObject()
    {
        $target = new \stdClass();
        $optional = new Optional($target);

        $this->assertNull($optional->item);
    }

    /**
     * Test untuk isset target yang memang ada (object).
     *
     * @group system
     */
    public function testIssetExistItemOnObject()
    {
        $target = new \stdClass();
        $target->item = '';
        $optional = new Optional($target);

        $this->assertTrue(isset($optional->item));
    }

    /**
     * Test untuk isset target yang tidak ada (object).
     *
     * @group system
     */
    public function testIssetNotExistItemOnObject()
    {
        $target = new \stdClass();
        $optional = new Optional($target);

        $this->assertFalse(isset($optional->item));
    }

    /**
     * Test untuk get target yang memang ada (array).
     *
     * @group system
     */
    public function testGetExistItemOnArray()
    {
        $target = ['item' => 'test'];
        $optional = new Optional($target);

        $this->assertEquals('test', $optional['item']);
    }

    /**
     * Test untuk get target yang tidak ada (array).
     *
     * @group system
     */
    public function testGetNotExistItemOnArray()
    {
        $target = [];
        $optional = new Optional($target);

        $this->assertNull($optional['item']);
    }

    /**
     * Test untuk isset target yang memang ada (array).
     *
     * @group system
     */
    public function testIssetExistItemOnArray()
    {
        $target = ['item' => ''];
        $optional = new Optional($target);

        $this->assertTrue(isset($optional['item']));
        $this->assertTrue(isset($optional->item));
    }

    /**
     * Test untuk isset target yang tidak ada (array).
     *
     * @group system
     */
    public function testIssetNotExistItemOnArray()
    {
        $target = [];
        $optional = new Optional($target);

        $this->assertFalse(isset($optional['item']));
        $this->assertFalse(isset($optional->item));
    }

    /**
     * Test untuk isset target yang memang ada tetapi null.
     *
     * @group system
     */
    public function testIssetExistItemOnNull()
    {
        $target = null;
        $optional = new Optional($target);

        $this->assertFalse(isset($optional->item));
    }
}
