<?php

defined('DS') or exit('No direct access.');

use System\Event;

class EventTest extends \PHPUnit_Framework_TestCase
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
        unset(Event::$events['test.event']);
    }

    /**
     * Test event listeners are fired when an event is fired.
     *
     * @group system
     */
    public function testListenersAreFiredForEvents()
    {
        Event::listen('test.event', function () {
            return 'event_one';
        });
        Event::listen('test.event', function () {
            return 'event_two';
        });

        $responses = Event::fire('test.event');
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
        Event::listen('test.event', function ($var) {
            return $var;
        });

        $responses = Event::fire('test.event', ['foo']);
        $this->assertEquals('foo', $responses[0]);
    }
}
