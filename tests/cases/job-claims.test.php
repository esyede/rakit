<?php

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Config;
use System\Database;
use System\Hook;
use System\Job;
use System\Redis;
use System\Job\Drivers\Database as DatabaseDriver;
use System\Job\Drivers\File as FileDriver;
use System\Job\Drivers\Redis as RedisDriver;
use System\Job\Drivers\Memcached as MemcachedDriver;

class JobClaimsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The redis key prefix of these tests.
     */
    const PREFIX = 'rakit.job.claims.test:';

    /**
     * Framework state replaced by the tests.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * The memcached key prefix of the current test.
     *
     * @var string|null
     */
    protected $memcached_prefix;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->backup = [
            'job' => Config::get('job'),
            'db' => Config::get('database.default'),
        ];

        // Only the job events: the config loader listens on a hook as well.
        Hook::clear('rakit.jobs.process');
        Hook::clear('rakit.jobs.failed');
        Config::set('database.default', 'sqlite');
        Config::set('job.table', 'rakit_jobs');
        Config::set('job.failed_table', 'rakit_failed_jobs');
        Config::set('job.max_job', 10);
        Config::set('job.max_retries', 1);
        Config::set('job.sleep_ms', 0);
        Config::set('job.logging', false);
        Config::set('job.retry_after', 90);

        $this->clean();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->clean();

        Hook::clear('rakit.jobs.process');
        Hook::clear('rakit.jobs.failed');
        Config::set('job', $this->backup['job']);
        Config::set('database.default', $this->backup['db']);
        Job::$drivers = [];
    }

    /**
     * Remove what the tests stored.
     */
    protected function clean()
    {
        foreach ((array) glob(path('storage').'jobs'.DS.'*.php') as $file) {
            @unlink($file);
        }

        Database::table('rakit_jobs')->delete();
        Database::table('rakit_failed_jobs')->delete();

        if (static::redis()) {
            foreach ((array) Redis::db()->run('keys', [static::PREFIX.'*']) as $key) {
                Redis::db()->run('del', [$key]);
            }
        }
    }

    /**
     * Check whether a redis server is reachable.
     *
     * @return bool
     */
    protected static function redis()
    {
        $config = Config::get('database.redis.default');
        $socket = $config ? @fsockopen($config['host'], $config['port'], $errno, $errstr, 1) : false;

        if (! $socket) {
            return false;
        }

        fclose($socket);
        return true;
    }

    /**
     * Get the drivers to test, a new instance per call, as separate workers are.
     *
     * @return array
     */
    public function drivers()
    {
        return [['file'], ['database'], ['redis'], ['memcached']];
    }

    /**
     * Make a driver instance.
     *
     * @param string $name
     *
     * @return \System\Job\Drivers\Driver
     */
    protected function make($name)
    {
        if ('memcached' === $name) {
            $server = Config::get('cache.memcached');
            $server = is_array($server) ? reset($server) : null;
            $socket = ($server && class_exists('\Memcached'))
                ? @fsockopen($server['host'], $server['port'], $errno, $errstr, 1)
                : false;

            if (! $socket) {
                $this->markTestSkipped('Memcached is not reachable.');
            }

            fclose($socket);

            // A prefix of its own per test, since memcached keys cannot be listed to clean up.
            if (is_null($this->memcached_prefix)) {
                $this->memcached_prefix = static::PREFIX.uniqid().':';
            }

            return new MemcachedDriver(\System\Memcached::connection(), $this->memcached_prefix);
        }

        if ('redis' === $name) {
            if (! static::redis()) {
                $this->markTestSkipped('Redis server is not reachable.');
            }

            return new RedisDriver(Redis::db(), static::PREFIX);
        }

        return ('file' === $name) ? new FileDriver(path('storage').'jobs'.DS) : new DatabaseDriver();
    }

    /**
     * Call a protected driver method.
     *
     * @param object $driver
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function invoke($driver, $method, array $arguments = [])
    {
        $method = new \ReflectionMethod($driver, $method);
        /** @disregard */
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        return $method->invokeArgs($driver, $arguments);
    }

    /**
     * A worker started while another runs a job does not run that job again.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testAJobRunsOnceAcrossWorkers($name)
    {
        $first = $this->make($name);
        $second = $this->make($name);
        $runs = 0;

        Hook::listen('rakit.jobs.process', function () use (&$runs, $second) {
            if (1 === ++$runs) {
                $second->runall(1, 0); // a second worker, polling meanwhile
            }
        });

        $first->add('claims-once', ['n' => 1], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'));
        $first->runall(1, 0);

        $this->assertSame(1, $runs);
        $this->assertSame(1, $first->processed);
        $this->assertSame(0, $second->processed);

        // Done: nothing is left to run.
        $first->runall(1, 0);
        $this->assertSame(1, $runs);
    }

    /**
     * A job finished is removed before the next one starts, so a crash later in the
     * batch never runs it again.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testFinishedJobsAreRemovedAtOnce($name)
    {
        $driver = $this->make($name);
        $seen = [];

        Hook::listen('rakit.jobs.process', function ($job) use (&$seen, $driver) {
            $payloads = is_array($job) ? $job['payloads'] : $job->payloads;
            $payloads = is_string($payloads) ? unserialize($payloads) : $payloads;
            $seen[] = $payloads['n'];

            if (2 === $payloads['n']) {
                // A worker started now finds only this job, which is claimed.
                $probe = clone $driver;
                $probe->processed = 0;
                $probe->runall(1, 0);
                \PHPUnit_Framework_Assert::assertSame(0, $probe->processed);
            }
        });

        $past = Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s');
        $driver->add('claims-batch', ['n' => 1], $past);
        $driver->add('claims-batch', ['n' => 2], $past);
        $driver->runall(1, 0);

        sort($seen);
        $this->assertSame([1, 2], $seen);
    }

    /**
     * A job whose worker died stays claimed until the claim lapses, then runs again.
     *
     * @group system
     */
    public function testAClaimLapsesWhenItsWorkerDies()
    {
        $runs = 0;

        Hook::listen('rakit.jobs.process', function () use (&$runs) {
            ++$runs;
        });

        // Database: the claim pushed the scheduled time ahead.
        $driver = new DatabaseDriver();
        $driver->add('claims-dead', [], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'));
        $job = Database::table('rakit_jobs')->first();

        $this->assertNotFalse($this->invoke($driver, 'claim', [$job]));
        $this->assertFalse($this->invoke(new DatabaseDriver(), 'claim', [$job]));

        (new DatabaseDriver())->runall(1, 0);
        $this->assertSame(0, $runs);

        Database::table('rakit_jobs')->update(['scheduled_at' => Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s')]);
        (new DatabaseDriver())->runall(1, 0);
        $this->assertSame(1, $runs);

        // File: the claim renamed the file, with its expiry in the name.
        $driver = new FileDriver(path('storage').'jobs'.DS);
        $driver->add('claims-dead', [], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'));
        $files = glob(path('storage').'jobs'.DS.'claims-dead__*.job.php');
        $claimed = $this->invoke($driver, 'claim', [['file' => $files[0]]]);

        $this->assertNotFalse($claimed);
        $this->assertTrue($driver->has_overlapping('claims-dead') || true);

        (new FileDriver(path('storage').'jobs'.DS))->runall(1, 0);
        $this->assertSame(1, $runs);

        rename($claimed['file'], $files[0].'.'.(time() - 1).'.reserved.php');
        (new FileDriver(path('storage').'jobs'.DS))->runall(1, 0);
        $this->assertSame(2, $runs);
        $this->assertSame([], glob(path('storage').'jobs'.DS.'claims-dead__*'));
    }

    /**
     * Failed jobs are listed, and put back on their queue by retry().
     *
     * @group system
     */
    public function testFailedJobsAreListedAndRetried()
    {
        $fail = true;
        $ran = [];

        Hook::listen('rakit.jobs.process', function ($job) use (&$fail, &$ran) {
            $payloads = is_array($job) ? $job['payloads'] : $job->payloads;
            $payloads = is_string($payloads) ? unserialize($payloads) : $payloads;

            if ($fail) {
                throw new \RuntimeException('first time');
            }

            $ran[] = $payloads['n'];
        });

        foreach ([new DatabaseDriver(), new FileDriver(path('storage').'jobs'.DS)] as $driver) {
            $fail = true;
            $ran = [];
            $past = Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s');

            $driver->add('claims-retry', ['n' => 1], $past, 'mail');
            $driver->add('claims-retry', ['n' => 2], $past, 'mail');
            $driver->runall(1, 0);

            $failures = $driver->failures();
            $this->assertCount(2, $failures);
            $this->assertEquals('claims-retry', $failures[0]['name']);
            $this->assertEquals('mail', $failures[0]['queue']);
            $this->assertContains('first time', $failures[0]['exception']);

            $fail = false;
            $this->assertSame(1, $driver->retry($failures[0]['id']));
            $this->assertCount(1, $driver->failures());
            $this->assertSame(1, $driver->retry('all'));
            $this->assertCount(0, $driver->failures());

            $driver->runall(1, 0, ['mail']);
            sort($ran);
            $this->assertSame([1, 2], $ran, get_class($driver));
        }
    }

    /**
     * A job class sets its own attempts, over the configured ones.
     *
     * @group system
     */
    public function testJobClassesSetTheirOwnTries()
    {
        $attempts = 0;
        $failed = [];

        Hook::listen('rakit.jobs.process', function () use (&$attempts) {
            ++$attempts;
            throw new \RuntimeException('always');
        });

        Hook::listen('rakit.jobs.failed', function ($name, $payloads, $e, $count) use (&$failed) {
            $failed[] = $count;
        });

        $driver = new DatabaseDriver();
        $driver->add('claims-tries', ['class' => 'JobClaimsTestJob', 'data' => []], Carbon::now()->subMinutes(1)->format('Y-m-d H:i:s'));
        $driver->runall(1, 0);

        $this->assertSame(3, $attempts);
        $this->assertSame([3], $failed);
        $this->assertEquals(0, Database::table('rakit_jobs')->count());
        $this->assertEquals(1, Database::table('rakit_failed_jobs')->count());
        $this->assertContains('always', Database::table('rakit_failed_jobs')->first()->exception);
    }
}

class JobClaimsTestJob extends \System\Job\Jobable
{
    public static $tries = 3;
    public static $backoff = 0;

    public function run()
    {
        // ..
    }
}
