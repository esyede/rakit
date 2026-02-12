<?php

defined('DS') or exit('No direct access.');

use System\Magic;

class MagicTest extends \PHPUnit_Framework_TestCase
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
     * Test for Magic::__construct().
     *
     * @group system
     */
    public function testAttributesAreSetByConstructor()
    {
        $array = ['name' => 'Budi', 'age' => 25];
        $magic = new Magic($array);

        $this->assertEquals($array, $magic->attributes);
    }

    /**
     * Test for Magic::get().
     *
     * @group system
     */
    public function testGetMethodReturnsAttribute()
    {
        $magic = new Magic(['name' => 'Budi']);

        $this->assertEquals('Budi', $magic->get('name'));
        $this->assertEquals('Default', $magic->get('foo', 'Default'));
        $this->assertEquals('Budi', $magic->name);
        $this->assertNull($magic->foo);
    }

    /**
     * Test for Magic's magic methods.
     *
     * @group system
     */
    public function testMagicMethodsCanBeUsedToSetAttributes()
    {
        $magic = new Magic();

        $magic->name = 'Budi';
        $magic->developer();
        $magic->age(25);

        $this->assertEquals('Budi', $magic->name);
        $this->assertTrue($magic->developer);
        $this->assertEquals(25, $magic->age);
        $this->assertInstanceOf('\System\Magic', $magic->programmer());
    }
}
