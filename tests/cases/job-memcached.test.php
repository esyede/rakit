<?php

defined('DS') or exit('No direct access.');

use System\Hook;
use System\Config;

/**
 * Covers the Memcached-backed job driver.
 *
 * The functional part needs both the PECL extension and a running server, and
 * is skipped when either is missing - like the other server-backed tests. The
 * wiring test below runs everywhere, because the driver used to ask for the
 * wrong class and could not be built at all.
 */
class JobMemcachedTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Prefix used by the driver under test.
     */
    const PREFIX = 'rakit_test_job_mc:';

    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('job.logging', false);
        Config::set('job.max_retries', 1);
        Config::set('job.max_job', 50);
        Config::set('job.sleep_ms', 0);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Hook::clear('rakit.jobs.process');

        if (static::reachable()) {
            $this->connection()->flush();
        }
    }

    /**
     * Check whether the extension is loaded and a server answers.
     *
     * @return bool
     */
    protected static function reachable()
    {
        if (!class_exists('\Memcached')) {
            return false;
        }

        $config = Config::get('cache.memcached');

        if (empty($config)) {
            return false;
        }

        $server = reset($config);
        $socket = @fsockopen($server['host'], $server['port'], $errno, $errstr, 1);

        if (!$socket) {
            return false;
        }

        fclose($socket);

        return true;
    }

    /**
     * Build the raw connection.
     *
     * @return \Memcached
     */
    protected function connection()
    {
        return \System\Memcached::connection();
    }

    /**
     * Build the driver under test.
     *
     * @return \System\Job\Drivers\Memcached
     */
    protected function driver()
    {
        if (!static::reachable()) {
            $this->markTestSkipped('Memcached server is not reachable.');
        }

        return new \System\Job\Drivers\Memcached($this->connection(), self::PREFIX);
    }

    // -------------------------------------------------------------------------

    /**
     * The driver takes the same connection object Job::factory() hands it.
     *
     * It used to type hint \System\Memcached - the static facade, which owns no
     * instance methods - while the factory passes what Memcached::connection()
     * returns, so building the driver was always a TypeError.
     *
     * @group system
     */
    public function testConstructorTakesTheNativeConnection()
    {
        $constructor = new \ReflectionMethod('\System\Job\Drivers\Memcached', '__construct');
        $parameters = $constructor->getParameters();

        $this->assertNotEmpty($parameters);

        if (method_exists($parameters[0], 'getType')) {
            $type = $parameters[0]->getType();
            $this->assertNotNull($type, 'The first parameter should be type hinted.');
            $name = method_exists($type, 'getName') ? $type->getName() : (string) $type;
        } else {
            preg_match('/<\w+>\s+([^\s$]\S*)\s/', (string) $parameters[0], $matches);
            $name = isset($matches[1]) ? $matches[1] : '';
        }

        $this->assertEquals('Memcached', ltrim($name, '\\'));
    }

    /**
     * Job::factory() builds the driver without blowing up.
     *
     * @group system
     */
    public function testFactoryCanBuildTheDriver()
    {
        if (!static::reachable()) {
            $this->markTestSkipped('Memcached server is not reachable.');
        }

        $default = Config::get('job.driver');
        Config::set('job.driver', 'memcached');
        \System\Job::$drivers = [];

        try {
            $driver = \System\Job::driver('memcached');
            $this->assertInstanceOf('\System\Job\Drivers\Memcached', $driver);
        } catch (\Exception $e) {
            Config::set('job.driver', $default);
            \System\Job::$drivers = [];

            throw $e;
        }

        Config::set('job.driver', $default);
        \System\Job::$drivers = [];
    }

    /**
     * Adding a job writes both the payload and the queue entry.
     *
     * @group system
     */
    public function testAddStoresTheJob()
    {
        $driver = $this->driver();

        $this->assertTrue($driver->add('kirim-email', ['to' => 'budi@example.com']));

        $all = $this->connection()->get(self::PREFIX . 'all_jobs');

        $this->assertCount(1, (array) $all);

        $entry = reset($all);
        $stored = $this->connection()->get(self::PREFIX . 'data:' . $entry['id']);

        $this->assertEquals('kirim-email', $stored['name']);
        $this->assertEquals('default', $stored['queue']);
        $this->assertEquals(['to' => 'budi@example.com'], unserialize($stored['payloads']));
    }

    /**
     * Test for has_overlapping().
     *
     * @group system
     */
    public function testHasOverlapping()
    {
        $driver = $this->driver();

        $this->assertFalse($driver->has_overlapping('kirim-email'));

        $driver->add('kirim-email', [], null, 'default', false);
        $this->assertFalse($driver->has_overlapping('kirim-email'));

        $driver->add('kirim-email', [], null, 'default', true);
        $this->assertTrue($driver->has_overlapping('kirim-email'));
    }

    /**
     * A scheduled job is executed and then removed.
     *
     * @group system
     */
    public function testRunExecutesAndRemovesTheJob()
    {
        $driver = $this->driver();
        $seen = [];

        Hook::listen('rakit.jobs.process', function ($data) use (&$seen) {
            $seen[] = $data;
        });

        $driver->add('kirim-email', ['to' => 'budi@example.com']);

        $this->assertTrue($driver->run('kirim-email'));
        $this->assertCount(1, $seen);
        $this->assertEquals('kirim-email', $seen[0]['name']);

        $this->assertCount(0, (array) $this->connection()->get(self::PREFIX . 'all_jobs'));
    }

    /**
     * A job scheduled for the future is left alone.
     *
     * @group system
     */
    public function testRunSkipsFutureJobs()
    {
        $driver = $this->driver();
        $ran = 0;

        Hook::listen('rakit.jobs.process', function () use (&$ran) {
            $ran++;
        });

        $driver->add('nanti', [], \System\Carbon::now()->addHours(2)->format('Y-m-d H:i:s'));
        $driver->run('nanti');

        $this->assertEquals(0, $ran);
        $this->assertCount(1, (array) $this->connection()->get(self::PREFIX . 'all_jobs'));
    }

    /**
     * Test for forget().
     *
     * @group system
     */
    public function testForget()
    {
        $driver = $this->driver();
        $driver->add('kirim-email', [], null, 'default');

        $this->assertTrue($driver->forget('kirim-email'));
        $this->assertCount(0, (array) $this->connection()->get(self::PREFIX . 'all_jobs'));
    }

    /**
     * Test for runall().
     *
     * @group system
     */
    public function testRunAll()
    {
        $driver = $this->driver();
        $ran = [];

        Hook::listen('rakit.jobs.process', function ($data) use (&$ran) {
            $ran[] = $data['name'];
        });

        $driver->add('satu', [], null, 'default');
        $driver->add('dua', [], null, 'high');

        $this->assertTrue($driver->runall());

        sort($ran);
        $this->assertEquals(['dua', 'satu'], $ran);
    }

    /**
     * A failing job is moved out of the queue and recorded as failed.
     *
     * @group system
     */
    public function testFailedJobIsMovedAside()
    {
        $driver = $this->driver();

        Hook::listen('rakit.jobs.process', function () {
            throw new \Exception('gagal');
        });

        $driver->add('rusak', []);
        $driver->run('rusak');

        $this->assertCount(1, (array) $this->connection()->get(self::PREFIX . 'failed_jobs'));
        $this->assertCount(0, (array) $this->connection()->get(self::PREFIX . 'all_jobs'));
    }
}
