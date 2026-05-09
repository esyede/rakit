<?php

defined('DS') or exit('No direct access.');

use System\Hook;

class HookTest extends \PHPUnit_Framework_TestCase
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
        unset(Hook::$events['test.event']);
    }

    /**
     * Test event listeners are fired when an event is fired.
     *
     * @group system
     */
    public function testListenersAreFiredForEvents()
    {
        Hook::listen('test.event', function () {
            return 'event_one';
        });
        Hook::listen('test.event', function () {
            return 'event_two';
        });

        $responses = Hook::fire('test.event');
        $this->assertEquals('event_one', $responses[0]);
        $this->assertEquals('event_two', $responses[1]);
    }

    /**
     * Test that parameters can be passed to event listeners when an event is fired.
     *
     * @group system
     */
    public function testParametersCanBePassedToEvents()
    {
        Hook::listen('test.event', function ($var) {
            return $var;
        });

        $responses = Hook::fire('test.event', ['foo']);
        $this->assertEquals('foo', $responses[0]);
    }
}
