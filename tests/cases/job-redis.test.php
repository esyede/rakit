<?php

defined('DS') or exit('No direct access.');

use System\Hook;
use System\Redis;
use System\Config;

/**
 * Covers the Redis-backed job driver end to end.
 *
 * Skipped when no Redis server answers, like the other Redis-backed tests.
 */
class JobRedisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prefix used by the driver under test.
     */
    const PREFIX = 'rakit_test_job:';

    /**
     * Setup.
     */
    public function setUp()
    {
        if (!static::reachable()) {
            $this->markTestSkipped('Redis server is not reachable.');
        }

        Redis::$databases = [];
        $this->clean();

        Config::set('job.logging', false);
        Config::set('job.max_retries', 1);
        Config::set('job.sleep_ms', 0);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (!static::reachable()) {
            return;
        }

        $this->clean();
        Hook::clear('rakit.jobs.process');
        Redis::$databases = [];
    }

    /**
     * Check whether a Redis server answers.
     *
     * @return bool
     */
    protected static function reachable()
    {
        $config = Config::get('database.redis.default');

        if (empty($config)) {
            return false;
        }

        $socket = @fsockopen($config['host'], $config['port'], $errno, $errstr, 1);

        if (!$socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * Remove every key this test may have written.
     */
    protected function clean()
    {
        $keys = Redis::db()->run('keys', [self::PREFIX . '*']);

        foreach ((array) $keys as $key) {
            Redis::db()->run('del', [$key]);
        }
    }

    /**
     * Build the driver under test.
     *
     * @return \System\Job\Drivers\Redis
     */
    protected function driver()
    {
        return new \System\Job\Drivers\Redis(Redis::db(), self::PREFIX);
    }

    // -------------------------------------------------------------------------

    /**
     * Adding a job writes both the hash and the queue entry.
     *
     * The payload used to be written with HMSET and an array argument, which the
     * client stringified to the literal 'Array', so nothing was ever stored.
     *
     * @group system
     */
    public function testAddStoresTheJob()
    {
        $driver = $this->driver();
        $this->assertTrue($driver->add('send-email', ['to' => 'budi@example.com']));

        $queues = Redis::db()->run('keys', [self::PREFIX . 'queue_*']);
        $this->assertCount(1, $queues);

        $jobs = Redis::db()->run('keys', [self::PREFIX . 'job_*']);
        $this->assertCount(1, $jobs);

        $stored = Redis::db()->run('hgetall', [$jobs[0]]);
        $stored = $this->pairs($stored);

        $this->assertEquals('send-email', $stored['name']);
        $this->assertEquals('default', $stored['queue']);
        $this->assertEquals(['to' => 'budi@example.com'], unserialize($stored['payloads']));
    }

    /**
     * Pair a flat HGETALL reply.
     *
     * @param array $reply
     *
     * @return array
     */
    protected function pairs($reply)
    {
        $result = [];
        $reply = (array) $reply;

        for ($i = 0; $i + 1 < count($reply); $i += 2) {
            $result[$reply[$i]] = $reply[$i + 1];
        }

        return $result;
    }

    /**
     * A job lands on the queue it was given.
     *
     * @group system
     */
    public function testAddRespectsTheQueueName()
    {
        $driver = $this->driver();
        $driver->add('report', [], null, 'reports');

        $queues = Redis::db()->run('keys', [self::PREFIX . 'queue_reports:*']);
        $this->assertCount(1, $queues);
    }

    /**
     * Test for has_overlapping().
     *
     * @group system
     */
    public function testHasOverlapping()
    {
        $driver = $this->driver();

        $this->assertFalse($driver->has_overlapping('send-email'));

        $driver->add('send-email', [], null, 'default', false);
        $this->assertFalse($driver->has_overlapping('send-email'));

        $driver->add('send-email', [], null, 'default', true);
        $this->assertTrue($driver->has_overlapping('send-email'));
    }

    /**
     * Test for forget() with an explicit queue.
     *
     * @group system
     */
    public function testForgetWithQueue()
    {
        $driver = $this->driver();
        $driver->add('send-email', [], null, 'default');

        $this->assertTrue($driver->forget('send-email', 'default'));

        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'queue_*']));
        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'job_*']));
    }

    /**
     * Test for forget() across every queue.
     *
     * @group system
     */
    public function testForgetAcrossQueues()
    {
        $driver = $this->driver();
        $driver->add('send-email', [], null, 'default');
        $driver->add('send-email', [], null, 'high');

        $this->assertTrue($driver->forget('send-email'));

        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'queue_*']));
        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'job_*']));
    }

    /**
     * A scheduled job is executed and then removed.
     *
     * @group system
     */
    public function testRunExecutesAndRemovesTheJob()
    {
        $seen = [];

        Hook::listen('rakit.jobs.process', function ($data) use (&$seen) {
            $seen[] = $data;
        });

        $driver = $this->driver();
        $driver->add('send-email', ['to' => 'budi@example.com']);

        $this->assertTrue($driver->run('send-email'));

        $this->assertCount(1, $seen);
        $this->assertEquals('send-email', $seen[0]['name']);
        $this->assertEquals(
            ['to' => 'budi@example.com'],
            unserialize($seen[0]['payloads'])
        );

        // The job is gone once it ran.
        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'job_*']));
    }

    /**
     * A job scheduled for the future is left alone.
     *
     * @group system
     */
    public function testRunSkipsFutureJobs()
    {
        $ran = 0;

        Hook::listen('rakit.jobs.process', function () use (&$ran) {
            $ran++;
        });

        $driver = $this->driver();
        $driver->add('later', [], \System\Carbon::now()->addHours(2)->format('Y-m-d H:i:s'));

        $driver->run('later');

        $this->assertEquals(0, $ran);
        $this->assertCount(1, (array) Redis::db()->run('keys', [self::PREFIX . 'job_*']));
    }

    /**
     * Running an unknown job reports that there was nothing to do.
     *
     * @group system
     */
    public function testRunWithoutQueue()
    {
        $this->assertFalse($this->driver()->run('missing'));
    }

    /**
     * Test for runall().
     *
     * @group system
     */
    public function testRunAll()
    {
        $ran = [];

        Hook::listen('rakit.jobs.process', function ($data) use (&$ran) {
            $ran[] = $data['name'];
        });

        $driver = $this->driver();
        $driver->add('one', [], null, 'default');
        $driver->add('two', [], null, 'high');

        $this->assertTrue($driver->runall());

        sort($ran);
        $this->assertEquals(['one', 'two'], $ran);
    }

    /**
     * A failing job is moved out of the queue and recorded as failed.
     *
     * @group system
     */
    public function testFailedJobIsMovedAside()
    {
        Hook::listen('rakit.jobs.process', function () {
            throw new \Exception('failed');
        });

        $driver = $this->driver();
        $driver->add('broken', []);

        $driver->run('broken');

        $this->assertCount(0, (array) Redis::db()->run('keys', [self::PREFIX . 'job_broken_*']));
        $this->assertCount(1, (array) Redis::db()->run('keys', [self::PREFIX . 'failed:*']));
    }
}
