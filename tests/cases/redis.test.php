<?php

defined('DS') or exit('No direct access.');

use System\Redis;
use System\Config;

/**
 * Covers System\Redis and the Redis-backed cache and session drivers.
 *
 * Every test is skipped when no Redis server answers on the configured host, so
 * the suite still passes on machines (and CI runners) without one.
 */
class RedisTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Keys written by these tests, removed again on tear down.
     *
     * @var array
     */
    protected $keys = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        if (!static::reachable()) {
            $this->markTestSkipped('Redis server is not reachable.');
        }

        Redis::$databases = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        if (!static::reachable()) {
            return;
        }

        foreach ($this->keys as $key) {
            try {
                Redis::db()->run('del', [$key]);
            } catch (\Exception $e) {
                // ignore, the key may never have been written
            }
        }

        $this->keys = [];
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
     * Register a key for cleanup and return it.
     *
     * @param string $key
     *
     * @return string
     */
    protected function key($key)
    {
        $key = 'rakit_test_' . $key;
        $this->keys[] = $key;

        return $key;
    }

    // -------------------------------------------------------------------------
    // Command building
    // -------------------------------------------------------------------------

    /**
     * Test for Redis::command() - builds a RESP array.
     *
     * @group system
     */
    public function testCommandFollowsTheRespProtocol()
    {
        $redis = Redis::db();

        $method = new \ReflectionMethod('\System\Redis', 'command');
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        $this->assertEquals(
            "*2\r\n\$3\r\nGET\r\n\$4\r\nname\r\n",
            $method->invoke($redis, 'get', ['name'])
        );

        $this->assertEquals(
            "*3\r\n\$3\r\nSET\r\n\$4\r\nname\r\n\$4\r\nBudi\r\n",
            $method->invoke($redis, 'set', ['name', 'Budi'])
        );

        $this->assertEquals(
            "*1\r\n\$4\r\nPING\r\n",
            $method->invoke($redis, 'ping', [])
        );
    }

    // -------------------------------------------------------------------------
    // Round trips against a live server
    // -------------------------------------------------------------------------

    /**
     * Test for Redis::db() - the same name yields the same instance.
     *
     * @group system
     */
    public function testDbReturnsTheSameInstance()
    {
        $this->assertSame(Redis::db(), Redis::db());
        $this->assertInstanceOf('\System\Redis', Redis::db('default'));
    }

    /**
     * An unknown database name is refused.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testDbThrowsForUnknownName()
    {
        Redis::db('this_database_is_not_configured');
    }

    /**
     * Test a SET / GET / DEL round trip.
     *
     * @group system
     */
    public function testSetGetDelete()
    {
        $key = $this->key('string');
        $redis = Redis::db();

        $this->assertEquals('OK', $redis->run('set', [$key, 'Budi Purnomo']));
        $this->assertEquals('Budi Purnomo', $redis->run('get', [$key]));

        $redis->run('del', [$key]);
        $this->assertNull($redis->run('get', [$key]));
    }

    /**
     * A value carrying CRLF and binary bytes must survive the round trip.
     *
     * @group system
     */
    public function testBinarySafeValues()
    {
        $key = $this->key('binary');
        $value = "baris satu\r\nbaris dua\0\x01\x02 selesai";

        Redis::db()->run('set', [$key, $value]);
        $this->assertEquals($value, Redis::db()->run('get', [$key]));
    }

    /**
     * Test an integer reply.
     *
     * @group system
     */
    public function testIntegerReply()
    {
        $key = $this->key('counter');
        $redis = Redis::db();

        $redis->run('del', [$key]);

        $this->assertEquals('1', $redis->run('incr', [$key]));
        $this->assertEquals('2', $redis->run('incr', [$key]));
    }

    /**
     * Test a multi bulk reply.
     *
     * @group system
     */
    public function testMultiBulkReply()
    {
        $key = $this->key('list');
        $redis = Redis::db();

        $redis->run('del', [$key]);
        $redis->run('rpush', [$key, 'satu']);
        $redis->run('rpush', [$key, 'dua']);
        $redis->run('rpush', [$key, 'tiga']);

        $this->assertEquals(['satu', 'dua', 'tiga'], $redis->run('lrange', [$key, 0, -1]));
    }

    /**
     * An empty bulk reply is an empty string, not NULL.
     *
     * @group system
     */
    public function testEmptyBulkReply()
    {
        $key = $this->key('empty');
        $redis = Redis::db();

        $redis->run('set', [$key, '']);
        $this->assertSame('', $redis->run('get', [$key]));
    }

    /**
     * A server error is turned into an exception.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testServerErrorBecomesAnException()
    {
        Redis::db()->run('this_is_not_a_redis_command', []);
    }

    /**
     * Dynamic calls go through run().
     *
     * @group system
     */
    public function testDynamicCall()
    {
        $key = $this->key('dynamic');
        $redis = Redis::db();

        $redis->set($key, 'halo');
        $this->assertEquals('halo', $redis->get($key));
    }

    // -------------------------------------------------------------------------
    // Cache driver
    // -------------------------------------------------------------------------

    /**
     * Test the Redis cache driver round trip.
     *
     * @group system
     */
    public function testRedisCacheDriver()
    {
        $driver = new \System\Cache\Drivers\Redis(Redis::db());
        $key = $this->key('cache');

        $this->assertFalse($driver->has($key));
        $this->assertEquals('fallback', $driver->get($key, 'fallback'));

        $driver->put($key, ['a' => 1, 'b' => [2, 3]], 5);

        $this->assertTrue($driver->has($key));
        $this->assertEquals(['a' => 1, 'b' => [2, 3]], $driver->get($key));

        $driver->forget($key);
        $this->assertFalse($driver->has($key));
    }

    /**
     * The driver must be able to store FALSE without mistaking it for a miss.
     *
     * @group system
     */
    public function testRedisCacheDriverStoresFalse()
    {
        $driver = new \System\Cache\Drivers\Redis(Redis::db());
        $key = $this->key('cache_false');

        $driver->put($key, false, 5);
        $this->assertFalse($driver->get($key, 'fallback'));
        $this->assertTrue($driver->has($key));
    }

    /**
     * Test the atomic increment of the driver.
     *
     * @group system
     */
    public function testRedisCacheDriverIncrement()
    {
        $driver = new \System\Cache\Drivers\Redis(Redis::db());
        $key = $this->key('cache_incr');

        $this->assertEquals(1, $driver->increment($key));
        $this->assertEquals(2, $driver->increment($key));
        $this->assertEquals(3, $driver->increment($key));
    }

    // -------------------------------------------------------------------------
    // Session driver
    // -------------------------------------------------------------------------

    /**
     * Test the Redis session driver round trip.
     *
     * @group system
     */
    public function testRedisSessionDriver()
    {
        $cache = new \System\Cache\Drivers\Redis(Redis::db());
        $driver = new \System\Session\Drivers\Redis($cache);

        $session = $driver->fresh();
        $session['data']['name'] = 'Budi';
        $this->keys[] = $session['id'];

        $driver->save($session, ['lifetime' => 5], false);

        $loaded = $driver->load($session['id']);
        $this->assertEquals('Budi', $loaded['data']['name']);

        $driver->delete($session['id']);
        $this->assertNull($driver->load($session['id']));
    }
}
