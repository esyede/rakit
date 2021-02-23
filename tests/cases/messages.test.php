<?php

defined('DS') or exit('No direct script access.');

use System\Messages;

class MessagesTest extends \PHPUnit_Framework_TestCase
{
    public $object;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->object = new Messages();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    /**
     * Test untuk method Messages::add() - 1.
     *
     * @group system
     */
    public function testAddingMessagesDoesNotCreateDuplicateMessages()
    {
        $this->object->add('zero', 'one');
        $this->object->add('zero', 'two');

        $this->assertCount(1, $this->object->messages);
    }

    /**
     * Test untuk method Messages::add() - 2.
     *
     * @group system
     */
    public function testAddMethodPutsMessageInMessagesArray()
    {
        $this->object->add('zero', 'one');

        $this->assertArrayHasKey('zero', $this->object->messages);
        $this->assertEquals('one', $this->object->messages['zero'][0]);
    }

    /**
     * Test untuk method Messages::has() - 1.
     *
     * @group system
     */
    public function testHasMethodReturnsTrue()
    {
        $this->object->add('zero', 'one');

        $this->assertTrue($this->object->has('zero'));
    }

    /**
     * Test untuk method Messages::has() - 2.
     *
     * @group system
     */
    public function testHasMethodReturnsFalse()
    {
        $this->assertFalse($this->object->has('three'));
    }

    /**
     * Test untuk method Messages::first().
     *
     * @group system
     */
    public function testFirstMethodReturnsSingleString()
    {
        $this->object->add('zero', 'one');

        $this->assertEquals('one', $this->object->first('zero'));
        $this->assertEquals('', $this->object->first('three'));
    }

    /**
     * Test untuk Messages::get().
     *
     * @group system
     */
    public function testGetMethodReturnsAllMessagesForAttribute()
    {
        $this->object->messages = ['zero' => ['three', 'four']];

        $this->assertEquals(['three', 'four'], $this->object->get('zero'));
    }

    /**
     * Test untuk method Messages::all().
     *
     * @group system
     */
    public function testAllMethodReturnsAllErrorMessages()
    {
        $this->object->messages = ['zero' => ['three', 'four'], 'five' => ['six']];

        $this->assertEquals(['three', 'four', 'six'], $this->object->all());
    }

    /**
     * Test untuk method Messages::get() dengan custom message.
     *
     * @group system
     */
    public function testMessagesRespectFormat()
    {
        $this->object->add('zero', 'one');

        $this->assertEquals('<p>one</p>', $this->object->first('zero', '<p>:message</p>'));
        $this->assertEquals(['<p>one</p>'], $this->object->get('zero', '<p>:message</p>'));
        $this->assertEquals(['<p>one</p>'], $this->object->all('<p>:message</p>'));
    }
}
