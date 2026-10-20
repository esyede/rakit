<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Cache\Drivers\APC;
use System\Cache\Drivers\Database as DatabaseDriver;
use System\Cache\Drivers\File as FileDriver;
use System\Cache\Drivers\Memcached as MemcachedDriver;
use System\Cache\Drivers\Memory as MemoryDriver;
use System\Cache\Drivers\Redis as RedisDriver;

class CacheLocksTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The key prefix of these tests.
     */
    const PREFIX = 'rakit.cache.locks.test:';

    /**
     * The drivers made by the current test.
     *
     * @var array
     */
    protected $made = [];

    /**
     * The default connection before the test.
     *
     * @var string
     */
    protected $connection;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->made = [];
        $this->connection = Config::get('database.default');
        Config::set('database.default', 'sqlite');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->made as $driver) {
            foreach (['k', 'c', 'm1', 'm2', 'lock:x', 'lock:y'] as $key) {
                $driver->forget($key);
            }
        }

        Config::set('database.default', $this->connection);
    }

    /**
     * Get the drivers to test.
     *
     * @return array
     */
    public function drivers()
    {
        return [['file'], ['database'], ['redis'], ['memcached'], ['apc'], ['memory']];
    }

    /**
     * Make a driver, or skip when its server or extension is not there.
     *
     * @param string $name
     *
     * @return \System\Cache\Drivers\Driver
     */
    protected function make($name)
    {
        switch ($name) {
            case 'file':
                $driver = new FileDriver(path('storage').'cache'.DS);
                break;

            case 'database':
                $driver = new DatabaseDriver(static::PREFIX);
                break;

            case 'redis':
                $config = Config::get('database.redis.default');
                $socket = $config ? @fsockopen($config['host'], $config['port'], $errno, $errstr, 1) : false;

                if (! $socket) {
                    $this->markTestSkipped('Redis server is not reachable.');
                }

                fclose($socket);
                $driver = new RedisDriver(\System\Redis::db(), static::PREFIX);
                break;

            case 'memcached':
                $server = Config::get('cache.memcached');
                $server = is_array($server) ? reset($server) : null;
                $socket = ($server && class_exists('\Memcached'))
                    ? @fsockopen($server['host'], $server['port'], $errno, $errstr, 1)
                    : false;

                if (! $socket) {
                    $this->markTestSkipped('Memcached is not reachable.');
                }

                fclose($socket);
                $driver = new MemcachedDriver(\System\Memcached::connection(), static::PREFIX.uniqid().':');
                break;

            case 'apc':
                if (! APC::usable() || ! ini_get('apc.enable_cli')) {
                    $this->markTestSkipped('APCu is not enabled on the CLI.');
                }

                $driver = new APC(static::PREFIX);
                break;

            default:
                $driver = new MemoryDriver();
        }

        return $this->made[] = $driver;
    }

    /**
     * add() stores only when the key is missing.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testAdd($name)
    {
        $driver = $this->make($name);

        $this->assertTrue($driver->add('k', 'first', 5));
        $this->assertFalse($driver->add('k', 'second', 5));
        $this->assertEquals('first', $driver->get('k'));

        $driver->forget('k');
        $this->assertTrue($driver->add('k', 'third', 5));
        $this->assertEquals('third', $driver->get('k'));
    }

    /**
     * increment() and decrement() take a step.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testIncrementAndDecrement($name)
    {
        $driver = $this->make($name);

        $this->assertEquals(5, $driver->increment('c', 5, 5));
        $this->assertEquals(6, $driver->increment('c', 5));
        $this->assertEquals(4, $driver->decrement('c', 5, 2));
        $this->assertEquals(3, $driver->decrement('c', 5));
        $this->assertEquals(3, (int) $driver->get('c'));
    }

    /**
     * many() and put_many() read and write several keys.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testManyAndPutMany($name)
    {
        $driver = $this->make($name);
        $driver->put_many(['m1' => 'one', 'm2' => ['two']], 5);

        $this->assertEquals(['m1' => 'one', 'm2' => ['two'], 'missing' => null], $driver->many(['m1', 'm2', 'missing']));
    }

    /**
     * A lock is held by one owner, released only by it, and released after a callback,
     * also one that throws.
     *
     * @dataProvider drivers
     * @group system
     */
    public function testLocks($name)
    {
        $driver = $this->make($name);
        $first = $driver->lock('x', 10);
        $second = $driver->lock('x', 10);

        $this->assertTrue($first->get());
        $this->assertFalse($second->get());

        try {
            $second->block(0);
            $this->fail('block() took a held lock');
        } catch (\System\Exceptions\LockTimeoutException $e) {
            $this->assertContains('x', $e->getMessage());
        }

        $this->assertNull($driver->lock('free', 10)->block(1, function () {
            // returns nothing
        }));
        $driver->forget('lock:free');
        $this->assertFalse($second->release());
        $this->assertFalse($second->get());

        // The same owner, elsewhere, may release it.
        $this->assertTrue($driver->lock('x', 10, $first->owner())->release());
        $this->assertTrue($second->get());
        $this->assertTrue($second->release());

        $this->assertEquals('done', $driver->lock('y', 10)->get(function () {
            return 'done';
        }));
        $this->assertTrue($driver->lock('y', 10)->get());
        $driver->forget('lock:y');

        try {
            $driver->lock('y', 10)->get(function () {
                throw new \RuntimeException('inside');
            });
            $this->fail('The exception was swallowed');
        } catch (\RuntimeException $e) {
            $this->assertEquals('inside', $e->getMessage());
        }

        $this->assertTrue($driver->lock('y', 10)->get());
    }

    /**
     * A taken lock whose time ran out can be taken again.
     *
     * @group system
     */
    public function testAnExpiredLockCanBeTaken()
    {
        $driver = $this->make('database');

        $this->assertTrue($driver->lock('x', 10)->get());
        $this->assertFalse($driver->lock('x', 10)->get());

        Database::table(Config::get('cache.database.table'))
            ->where('key', '=', static::PREFIX.'lock:x')
            ->update(['expiration' => (string) (time() - 1)]);

        $this->assertTrue($driver->lock('x', 10)->get());
    }

    /**
     * A database error other than a taken key is not hidden as a taken lock.
     *
     * @group system
     */
    public function testDatabaseErrorsAreNotHidden()
    {
        $table = Config::get('cache.database.table');
        Config::set('cache.database.table', 'no_such_cache_table');

        try {
            $this->make('database')->add('k', 'v', 5);
            $this->fail('The error was hidden');
        } catch (\PDOException $e) {
            $this->assertContains('no_such_cache_table', $e->getMessage());
        }

        Config::set('cache.database.table', $table);
    }
}
