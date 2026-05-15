<?php

defined('DS') or exit('No direct access.');

use System\Job;
use System\Config;

class JobJobableTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Config::set('job.driver', 'file');
        Config::set('job.max_job', 10);
        Config::set('job.max_retries', 3);
        Config::set('job.sleep_ms', 100);
        Config::set('job.logging', false);
        Job::$drivers = [];
    }

    public function tearDown()
    {
        Job::$drivers = [];
        $path = path('storage') . 'jobs' . DS;
        if (is_dir($path)) {
            $files = glob($path . '*.job.php');
            if ($files) {
                foreach ($files as $file) {
                    @unlink($file);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Jobable::name()
    // -------------------------------------------------------------------------

    /**
     * Test for Jobable::name() - returns lowercased class name slug.
     *
     * @group system
     */
    public function testJobableNameReturnsSlugFromClassName()
    {
        $name = TestJobableHandler::name();
        $this->assertEquals('testjobablehandler', $name);
    }

    /**
     * Test for Jobable::name() - uses called class (late static binding).
     *
     * @group system
     */
    public function testJobableNameUsesCalledClass()
    {
        $name = AnotherJobableHandler::name();
        $this->assertEquals('anotherjobablehandler', $name);
    }

    // -------------------------------------------------------------------------
    // Jobable::dispatch()
    // -------------------------------------------------------------------------

    /**
     * Test for Jobable::dispatch() - returns Pending instance.
     *
     * @group system
     */
    public function testJobableDispatchReturnsPendingInstance()
    {
        $pending = TestJobableHandler::dispatch(['key' => 'value']);
        $this->assertInstanceOf('System\Job\Pending', $pending);
    }

    /**
     * Test for Jobable::dispatch() - passes class name in payload.
     *
     * @group system
     */
    public function testJobableDispatchPassesClassInPayload()
    {
        $pending = TestJobableHandler::dispatch(['email' => 'test@example.com']);
        $this->assertNotNull($pending);
    }

    // -------------------------------------------------------------------------
    // Jobable::dispatch_at()
    // -------------------------------------------------------------------------

    /**
     * Test for Jobable::dispatch_at() - returns Pending instance.
     *
     * @group system
     */
    public function testJobableDispatchAtReturnsPendingInstance()
    {
        $pending = TestJobableHandler::dispatch_at('2025-12-31 10:00:00', ['key' => 'val']);
        $this->assertInstanceOf('System\Job\Pending', $pending);
    }

    // -------------------------------------------------------------------------
    // Jobable::execute()
    // -------------------------------------------------------------------------

    /**
     * Test for Jobable::execute() - runs job with valid payload.
     *
     * @group system
     */
    public function testJobableExecuteRunsJobWithValidPayload()
    {
        $payload = [
            'class' => 'TestJobableHandler',
            'data'  => ['key' => 'executed_value'],
        ];

        TestJobableHandler::execute($payload);
        $this->assertEquals('executed_value', TestJobableHandler::$lastRun);
    }

    /**
     * Test for Jobable::execute() - no-op when class key missing.
     *
     * @group system
     */
    public function testJobableExecuteIsNoopWhenClassKeyMissing()
    {
        TestJobableHandler::$lastRun = null;
        $payload = ['data' => ['key' => 'value']];
        TestJobableHandler::execute($payload);
        $this->assertNull(TestJobableHandler::$lastRun);
    }

    /**
     * Test for Jobable::execute() - no-op when data key missing.
     *
     * @group system
     */
    public function testJobableExecuteIsNoopWhenDataKeyMissing()
    {
        TestJobableHandler::$lastRun = null;
        $payload = ['class' => 'TestJobableHandler'];
        TestJobableHandler::execute($payload);
        $this->assertNull(TestJobableHandler::$lastRun);
    }

    /**
     * Test for Jobable::execute() - no-op when class does not exist.
     *
     * @group system
     */
    public function testJobableExecuteIsNoopWhenClassDoesNotExist()
    {
        TestJobableHandler::$lastRun = null;
        $payload = ['class' => 'NonExistentJobClass', 'data' => []];
        TestJobableHandler::execute($payload);
        $this->assertNull(TestJobableHandler::$lastRun);
    }

    /**
     * Test for Jobable::execute() - no-op when class is not a Jobable.
     *
     * @group system
     */
    public function testJobableExecuteIsNoopWhenClassIsNotJobable()
    {
        $payload = ['class' => 'NotAJobable', 'data' => []];
        NotAJobable::$called = false;
        TestJobableHandler::execute($payload);
        $this->assertFalse(NotAJobable::$called);
    }

    // -------------------------------------------------------------------------
    // Jobable protected get() via subclass
    // -------------------------------------------------------------------------

    /**
     * Test for Jobable::get() - retrieves data by key.
     *
     * @group system
     */
    public function testJobableGetRetrievesDataByKey()
    {
        $handler = new TestJobableHandler(['name' => 'Alice', 'age' => 30]);
        $this->assertEquals('Alice', $handler->publicGet('name'));
        $this->assertEquals(30, $handler->publicGet('age'));
    }

    /**
     * Test for Jobable::get() - returns default for missing key.
     *
     * @group system
     */
    public function testJobableGetReturnsDefaultForMissingKey()
    {
        $handler = new TestJobableHandler([]);
        $this->assertNull($handler->publicGet('missing'));
        $this->assertEquals('fallback', $handler->publicGet('missing', 'fallback'));
    }

    /**
     * Test for Jobable::data() - returns all data.
     *
     * @group system
     */
    public function testJobableDataReturnsAllData()
    {
        $data = ['a' => 1, 'b' => 2];
        $handler = new TestJobableHandler($data);
        $this->assertEquals($data, $handler->publicData());
    }
}

class TestJobableHandler extends \System\Job\Jobable
{
    public static $lastRun = null;

    public function run()
    {
        static::$lastRun = isset($this->data['key']) ? $this->data['key'] : null;
    }

    public function publicGet($key, $default = null)
    {
        return $this->get($key, $default);
    }

    public function publicData()
    {
        return $this->data();
    }
}

class AnotherJobableHandler extends \System\Job\Jobable
{
    public function run()
    {
        // ..
    }
}

class NotAJobable
{
    public static $called = false;

    public function run()
    {
        static::$called = true;
    }
}
