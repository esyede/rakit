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
        unset(Hook::$events['test.event'], Hook::$queued['test.queue'], Hook::$flushers['test.queue']);
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

    /**
     * Test that queued events are handed to the flusher on flush.
     *
     * @group system
     */
    public function testQueuedEventsAreFlushed()
    {
        $flushed = [];

        Hook::flusher('test.queue', function ($key, $value) use (&$flushed) {
            $flushed[$key] = $value;
        });

        Hook::queue('test.queue', 'first', ['foo']);
        Hook::queue('test.queue', 'second', ['bar']);
        Hook::flush('test.queue');

        $this->assertEquals(['first' => 'foo', 'second' => 'bar'], $flushed);
    }

    /**
     * Test that queuing the same key twice replaces the earlier payload.
     *
     * @group system
     */
    public function testQueuedEventsAreKeyedAndDeduplicated()
    {
        $flushed = [];

        Hook::flusher('test.queue', function ($key, $value) use (&$flushed) {
            $flushed[] = $key . ':' . $value;
        });

        Hook::queue('test.queue', 'dupe', ['first']);
        Hook::queue('test.queue', 'dupe', ['second']);
        Hook::flush('test.queue');

        $this->assertEquals(['dupe:second'], $flushed);
    }
}
