<?php

defined('DS') or exit('No direct script access.');

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
     * Test pemanggilan event.
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
     * Test bahwa bisa mengoper parameter ke listener.
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
