<?php

defined('DS') or exit('No direct access.');

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
     * Test for existing target.
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
     * Test for non-existing target.
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
     * Test for isset on existing target (object).
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
     * Test for isset on non-existing target (object).
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
     * Test for get target that exists (array).
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
     * Test for get target that does not exist (array).
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
     * Test for isset target that exists (array).
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
     * Test for isset target that does not exist (array).
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
     * Test for isset on null target.
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
