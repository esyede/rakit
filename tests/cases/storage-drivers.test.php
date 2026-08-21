<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Cookie;
use System\Database;
use System\Database\Schema;

/**
 * Covers the database-backed cache driver and the cookie/database session
 * drivers, none of which the rest of the suite ever reaches.
 */
class StorageDriversTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Table names used by these tests.
     *
     * Note: deliberately not 'caches'/'sessions'. Those belong to the fixture
     * database that ships with the repository, and dropping them would leave the
     * checked-in file modified after every run.
     */
    const CACHE_TABLE = 'probe_caches';
    const SESSION_TABLE = 'probe_sessions';

    /**
     * Configuration in place before the test ran.
     *
     * @var array
     */
    private $config = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];
        Cookie::flush();

        $this->config = [
            'cache.database' => Config::get('cache.database'),
            'session.table' => Config::get('session.table'),
        ];

        Config::set('cache.database', ['table' => self::CACHE_TABLE]);
        Config::set('session.table', self::SESSION_TABLE);

        Schema::drop_if_exists(self::CACHE_TABLE);
        Schema::drop_if_exists(self::SESSION_TABLE);

        Schema::create(self::CACHE_TABLE, function ($table) {
            $table->string('key')->nullable();
            $table->text('value');
            $table->integer('expiration');
        });

        Schema::create(self::SESSION_TABLE, function ($table) {
            $table->string('id')->nullable();
            $table->integer('last_activity');
            $table->text('data');
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists(self::CACHE_TABLE);
        Schema::drop_if_exists(self::SESSION_TABLE);

        foreach ($this->config as $key => $value) {
            Config::set($key, $value);
        }

        Database::$connections = [];
        Cookie::flush();
    }

    // -------------------------------------------------------------------------
    // Cache\Drivers\Database
    // -------------------------------------------------------------------------

    /**
     * Test the database cache driver round trip.
     *
     * @group system
     */
    public function testDatabaseCacheRoundTrip()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');

        $this->assertFalse($driver->has('name'));
        $this->assertEquals('fallback', $driver->get('name', 'fallback'));

        $driver->put('name', 'Budi', 10);

        $this->assertTrue($driver->has('name'));
        $this->assertEquals('Budi', $driver->get('name'));

        $driver->forget('name');
        $this->assertFalse($driver->has('name'));
    }

    /**
     * Structured values survive the round trip.
     *
     * @group system
     */
    public function testDatabaseCacheStoresStructures()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');
        $value = ['a' => 1, 'b' => ['c' => true, 'd' => null]];

        $driver->put('structure', $value, 10);
        $this->assertEquals($value, $driver->get('structure'));
    }

    /**
     * Storing the same key twice updates the row instead of adding one.
     *
     * @group system
     */
    public function testDatabaseCacheOverwritesInPlace()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');

        $driver->put('name', 'Budi', 10);
        $driver->put('name', 'Ani', 10);

        $this->assertEquals('Ani', $driver->get('name'));
        $this->assertEquals(1, Database::table(self::CACHE_TABLE)->where('key', '=', 'rakit.name')->count());
    }

    /**
     * An expired item is a miss, and the row is dropped.
     *
     * @group system
     */
    public function testDatabaseCacheExpires()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');

        $driver->put('name', 'Budi', 10);
        Database::table(self::CACHE_TABLE)->where('key', '=', 'rakit.name')->update(['expiration' => time() - 60]);

        $this->assertNull($driver->get('name'));
        $this->assertEquals(0, Database::table(self::CACHE_TABLE)->where('key', '=', 'rakit.name')->count());
    }

    /**
     * Test the atomic increment of the database cache driver.
     *
     * @group system
     */
    public function testDatabaseCacheIncrement()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');

        $this->assertEquals(1, $driver->increment('hits'));
        $this->assertEquals(2, $driver->increment('hits'));
        $this->assertEquals(3, $driver->increment('hits'));
        $this->assertEquals(3, $driver->get('hits'));
    }

    /**
     * Test flushing the database cache.
     *
     * @group system
     */
    public function testDatabaseCacheFlush()
    {
        $driver = new \System\Cache\Drivers\Database('rakit.');

        $driver->put('a', 1, 10);
        $driver->put('b', 2, 10);
        $this->assertEquals(2, Database::table(self::CACHE_TABLE)->count());

        $driver->flush();
        $this->assertEquals(0, Database::table(self::CACHE_TABLE)->count());
    }

    // -------------------------------------------------------------------------
    // Session\Drivers\Database
    // -------------------------------------------------------------------------

    /**
     * Test the database session driver round trip.
     *
     * @group system
     */
    public function testDatabaseSessionRoundTrip()
    {
        $driver = new \System\Session\Drivers\Database(Database::connection());

        $session = $driver->fresh();
        $session['last_activity'] = time();
        $session['data']['name'] = 'Budi';

        $driver->save($session, ['lifetime' => 60], false);

        $loaded = $driver->load($session['id']);
        $this->assertEquals($session['id'], $loaded['id']);
        $this->assertEquals('Budi', $loaded['data']['name']);

        $driver->delete($session['id']);
        $this->assertNull($driver->load($session['id']));
    }

    /**
     * Saving an existing session updates it rather than inserting a second row.
     *
     * @group system
     */
    public function testDatabaseSessionUpdatesExisting()
    {
        $driver = new \System\Session\Drivers\Database(Database::connection());

        $session = $driver->fresh();
        $session['last_activity'] = time();
        $session['data']['name'] = 'Budi';
        $driver->save($session, ['lifetime' => 60], false);

        $session['data']['name'] = 'Ani';
        $driver->save($session, ['lifetime' => 60], true);

        $this->assertEquals(1, Database::table(self::SESSION_TABLE)->count());
        $loaded = $driver->load($session['id']);
        $this->assertEquals('Ani', $loaded['data']['name']);
    }

    /**
     * A corrupted payload must read back as an empty session, not blow up.
     *
     * @group system
     */
    public function testDatabaseSessionSurvivesCorruptedPayload()
    {
        $driver = new \System\Session\Drivers\Database(Database::connection());

        $session = $driver->fresh();
        $session['last_activity'] = time();
        $driver->save($session, ['lifetime' => 60], false);

        Database::table(self::SESSION_TABLE)
            ->where('id', '=', $session['id'])
            ->update(['data' => 'this is not serialized data']);

        $loaded = $driver->load($session['id']);
        $this->assertEquals([], $loaded['data']);
    }

    // -------------------------------------------------------------------------
    // Session\Drivers\Cookie
    // -------------------------------------------------------------------------

    /**
     * Test the cookie session driver round trip.
     *
     * @group system
     */
    public function testCookieSessionRoundTrip()
    {
        $driver = new \System\Session\Drivers\Cookie();

        $session = $driver->fresh();
        $session['last_activity'] = time();
        $session['data']['name'] = 'Budi';

        $driver->save($session, [
            'lifetime' => 60,
            'path' => '/',
            'domain' => null,
            'secure' => false,
            'samesite' => 'lax',
        ], false);

        $loaded = $driver->load($session['id']);
        $this->assertEquals('Budi', $loaded['data']['name']);

        $driver->delete($session['id']);
    }

    /**
     * A cookie session id is generated without querying the store.
     *
     * @group system
     */
    public function testCookieSessionGeneratesId()
    {
        $driver = new \System\Session\Drivers\Cookie();
        $session = $driver->fresh();

        $this->assertEquals(40, strlen($session['id']));
        $this->assertArrayHasKey(':new:', $session['data']);
        $this->assertArrayHasKey(':old:', $session['data']);
    }

    /**
     * A payload that does not unserialize must read back as "no session"
     * instead of letting the failure escape.
     *
     * @group system
     */
    public function testCookieSessionSurvivesCorruptedPayload()
    {
        $driver = new \System\Session\Drivers\Cookie();

        Cookie::put(\System\Session\Drivers\Cookie::PAYLOAD, 'bukan data terserialisasi');

        $this->assertNull($driver->load('apa saja'));
    }

    /**
     * Without a payload cookie there is simply no session.
     *
     * @group system
     */
    public function testCookieSessionWithoutPayload()
    {
        $driver = new \System\Session\Drivers\Cookie();

        $this->assertNull($driver->load('apa saja'));
    }
}
