<?php

defined('DS') or exit('No direct access.');

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
     * Test for Messages::add() - 1.
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
     * Test for Messages::add() - 2.
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
     * Test for Messages::has() - 1.
     *
     * @group system
     */
    public function testHasMethodReturnsCorrectBoolean()
    {
        $this->object->add('zero', 'one');

        $this->assertTrue($this->object->has('zero'));
        $this->assertFalse($this->object->has('three'));
    }

    /**
     * Test for Messages::any().
     *
     * @group system
     */
    public function testAnyMethodReturnsCorrectBoolean()
    {
        $this->assertFalse($this->object->any());
        $this->object->add('zero', 'one');

        $this->assertTrue($this->object->any());
    }

    /**
     * Test for Messages::first().
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
     * Test for Messages::all().
     *
     * @group system
     */
    public function testAllMethodReturnsAllErrorMessages()
    {
        $this->object->messages = ['zero' => ['three', 'four'], 'five' => ['six']];

        $this->assertEquals(['three', 'four', 'six'], $this->object->all());
    }

    /**
     * Test for Messages::get() with custom message.
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
