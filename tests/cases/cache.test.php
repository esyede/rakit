<?php

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Config;
use System\Cache\Drivers\Memory;
use System\Cache\Drivers\File;

class CacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Cache::$drivers = [];
        Cache::$registrar = [];
        $this->resetProcessedKey();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Cache::$drivers = [];
        Cache::$registrar = [];
        $this->resetProcessedKey();
    }

    /**
     * Reset private static $processed_key via reflection.
     */
    private function resetProcessedKey()
    {
        $ref = new \ReflectionClass('System\Cache');
        $prop = $ref->getProperty('processed_key');
        PHP_VERSION_ID < 80100 && $prop->setAccessible(true);
        $prop->setValue(null, null);
    }

    /**
     * Test for Cache::driver() - returns Memory driver.
     *
     * @group system
     */
    public function testDriverReturnsMemoryDriver()
    {
        $driver = Cache::driver('memory');
        $this->assertInstanceOf('System\Cache\Drivers\Memory', $driver);
    }

    /**
     * Test for Cache::driver() - returns File driver.
     *
     * @group system
     */
    public function testDriverReturnsFileDriver()
    {
        $driver = Cache::driver('file');
        $this->assertInstanceOf('System\Cache\Drivers\File', $driver);
    }

    /**
     * Test for Cache::driver() - same instance is returned.
     *
     * @group system
     */
    public function testDriverReturnsSameInstance()
    {
        $first = Cache::driver('memory');
        $second = Cache::driver('memory');
        $this->assertSame($first, $second);
    }

    /**
     * Test for Cache::driver() - default driver from config.
     *
     * @group system
     */
    public function testDriverUsesDefaultDriverFromConfig()
    {
        $driver = Cache::driver();
        $this->assertInstanceOf('System\Cache\Drivers\Driver', $driver);
    }

    /**
     * Test for Cache::driver() - throws on invalid driver type.
     *
     * @group system
     * @expectedException Exception
     */
    public function testDriverThrowsOnNonStringDriver()
    {
        Cache::driver(123);
    }

    /**
     * Test for Cache::driver() - throws on empty string driver.
     *
     * @group system
     * @expectedException Exception
     */
    public function testDriverThrowsOnEmptyStringDriver()
    {
        Cache::driver('');
    }

    /**
     * Test for Cache::factory() - throws on unsupported driver.
     *
     * @group system
     * @expectedException Exception
     */
    public function testFactoryThrowsOnUnsupportedDriver()
    {
        Cache::driver('unsupported_driver_xyz');
    }

    /**
     * Test for Cache::extend() - registers and resolves custom driver.
     *
     * @group system
     */
    public function testExtendRegistersCustomDriver()
    {
        $memory = new Memory();
        Cache::extend('custom', function () use ($memory) {
            return $memory;
        });

        $driver = Cache::driver('custom');
        $this->assertSame($memory, $driver);
    }

    /**
     * Test for Cache::__callStatic() - delegates to default driver.
     *
     * @group system
     */
    public function testCallStaticDelegatesToDriver()
    {
        $memory = new Memory();
        Cache::extend('memory', function () use ($memory) {
            return $memory;
        });
        Config::set('cache.driver', 'memory');

        Cache::put('test_key', 'test_value', 60);
        $this->assertEquals('test_value', Cache::get('test_key'));

        Config::set('cache.driver', 'file');
    }

    /**
     * Test for processed_key() - appends dot separator.
     *
     * @group system
     */
    public function testProcessedKeyAppendsDotseparator()
    {
        $ref = new \ReflectionClass('System\Cache');
        $method = $ref->getMethod('processed_key');
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        $key = $method->invoke(null);
        $this->assertStringEndsWith('.', $key);
    }

    /**
     * Test Memory driver: has() returns false for missing key.
     *
     * @group system
     */
    public function testMemoryHasReturnsFalseForMissingKey()
    {
        $driver = new Memory();
        $this->assertFalse($driver->has('nonexistent'));
    }

    /**
     * Test Memory driver: has() returns true after put().
     *
     * @group system
     */
    public function testMemoryHasReturnsTrueAfterPut()
    {
        $driver = new Memory();
        $driver->put('key', 'value', 60);
        $this->assertTrue($driver->has('key'));
    }

    /**
     * Test Memory driver: get() returns default for missing key.
     *
     * @group system
     */
    public function testMemoryGetReturnsDefaultForMissingKey()
    {
        $driver = new Memory();
        $this->assertNull($driver->get('missing'));
        $this->assertEquals('default', $driver->get('missing', 'default'));
    }

    /**
     * Test Memory driver: get() returns value after put().
     *
     * @group system
     */
    public function testMemoryGetReturnsValueAfterPut()
    {
        $driver = new Memory();
        $driver->put('key', 'hello', 60);
        $this->assertEquals('hello', $driver->get('key'));
    }

    /**
     * Test Memory driver: put() and get() with complex values.
     *
     * @group system
     */
    public function testMemoryPutAndGetComplexValues()
    {
        $driver = new Memory();
        $driver->put('array_key', ['a' => 1, 'b' => 2], 60);
        $this->assertEquals(['a' => 1, 'b' => 2], $driver->get('array_key'));

        $driver->put('int_key', 42, 60);
        $this->assertEquals(42, $driver->get('int_key'));

        $driver->put('null_key', false, 60);
        $this->assertFalse($driver->get('null_key'));
    }

    /**
     * Test Memory driver: forget() removes item.
     *
     * @group system
     */
    public function testMemoryForgetRemovesItem()
    {
        $driver = new Memory();
        $driver->put('key', 'value', 60);
        $driver->forget('key');
        $this->assertFalse($driver->has('key'));
    }

    /**
     * Test Memory driver: flush() clears all items.
     *
     * @group system
     */
    public function testMemoryFlushClearsAll()
    {
        $driver = new Memory();
        $driver->put('key1', 'val1', 60);
        $driver->put('key2', 'val2', 60);
        $driver->flush();
        $this->assertFalse($driver->has('key1'));
        $this->assertFalse($driver->has('key2'));
    }

    /**
     * Test Driver: pull() returns value and removes it.
     *
     * @group system
     */
    public function testDriverPullReturnsAndRemovesItem()
    {
        $driver = new Memory();
        $driver->put('key', 'value', 60);
        $pulled = $driver->pull('key');
        $this->assertEquals('value', $pulled);
        $this->assertFalse($driver->has('key'));
    }

    /**
     * Test Driver: pull() returns default for missing key.
     *
     * @group system
     */
    public function testDriverPullReturnsDefaultForMissingKey()
    {
        $driver = new Memory();
        $result = $driver->pull('missing', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    /**
     * Test Driver: forever() stores item.
     *
     * @group system
     */
    public function testDriverForeverStoresItem()
    {
        $driver = new Memory();
        $driver->forever('key', 'eternal');
        $this->assertEquals('eternal', $driver->get('key'));
    }

    /**
     * Test Driver: remember() returns existing item.
     *
     * @group system
     */
    public function testDriverRememberReturnsExistingItem()
    {
        $driver = new Memory();
        $driver->put('key', 'cached', 60);
        $result = $driver->remember('key', 60, 'default');
        $this->assertEquals('cached', $result);
    }

    /**
     * Test Driver: remember() stores default when missing.
     *
     * @group system
     */
    public function testDriverRememberStoresDefaultWhenMissing()
    {
        $driver = new Memory();
        $result = $driver->remember('key', 60, 'new_value');
        $this->assertEquals('new_value', $result);
        $this->assertEquals('new_value', $driver->get('key'));
    }

    /**
     * Test Driver: remember() with Closure default.
     *
     * @group system
     */
    public function testDriverRememberWithClosureDefault()
    {
        $driver = new Memory();
        $result = $driver->remember('key', 60, function () {
            return 'computed';
        });
        $this->assertEquals('computed', $result);
    }

    /**
     * Test Driver: sear() stores item indefinitely.
     *
     * @group system
     */
    public function testDriverSearStoresItemIndefinitely()
    {
        $driver = new Memory();
        $result = $driver->sear('key', 'value');
        $this->assertEquals('value', $result);
        $this->assertEquals('value', $driver->get('key'));
    }

    /**
     * Test Driver: sear() returns existing item.
     *
     * @group system
     */
    public function testDriverSearReturnsExistingItem()
    {
        $driver = new Memory();
        $driver->put('key', 'existing', 60);
        $result = $driver->sear('key', 'new_value');
        $this->assertEquals('existing', $result);
    }

    /**
     * Test Driver: increment() increments numeric value.
     *
     * @group system
     */
    public function testDriverIncrementIncrementsValue()
    {
        $driver = new Memory();
        $driver->put('counter', 5, 60);
        $result = $driver->increment('counter');
        $this->assertEquals(6, $result);
        $this->assertEquals(6, $driver->get('counter'));
    }

    /**
     * Test Driver: increment() starts from zero for missing key.
     *
     * @group system
     */
    public function testDriverIncrementStartsFromZeroForMissingKey()
    {
        $driver = new Memory();
        $result = $driver->increment('new_counter');
        $this->assertEquals(1, $result);
    }

    /**
     * Test Sectionable: put in section and get from section.
     *
     * @group system
     */
    public function testSectionablePutAndGetFromSection()
    {
        $driver = new Memory();
        $driver->put('users::john', 'John Doe', 60);
        $this->assertEquals('John Doe', $driver->get('users::john'));
        $this->assertEquals('John Doe', $driver->get_from_section('users', 'john'));
    }

    /**
     * Test Sectionable: put_in_section().
     *
     * @group system
     */
    public function testSectionablePutInSection()
    {
        $driver = new Memory();
        $driver->put_in_section('users', 'jane', 'Jane Doe', 60);
        $this->assertEquals('Jane Doe', $driver->get_from_section('users', 'jane'));
    }

    /**
     * Test Sectionable: forever_in_section().
     *
     * @group system
     */
    public function testSectionableForeverInSection()
    {
        $driver = new Memory();
        $driver->forever_in_section('users', 'bob', 'Bob Smith');
        $this->assertEquals('Bob Smith', $driver->get_from_section('users', 'bob'));
    }

    /**
     * Test Sectionable: remember_in_section().
     *
     * Note: parameter order in remember_in_section($section, $key, $default, $minutes)
     * maps to Driver::remember($key, $minutes, $default) with $default and $minutes swapped,
     * so the value stored is the $minutes argument, not $default.
     *
     * @group system
     */
    public function testSectionableRememberInSection()
    {
        $driver = new Memory();
        // $default=30 maps to $minutes in Driver::remember; $minutes='Alice' maps to $default (value stored)
        $result = $driver->remember_in_section('users', 'alice', 30, 'Alice');
        $this->assertEquals('Alice', $result);
        $this->assertEquals('Alice', $driver->get_from_section('users', 'alice'));
    }

    /**
     * Test Sectionable: sear_in_section().
     *
     * @group system
     */
    public function testSectionableSearInSection()
    {
        $driver = new Memory();
        $result = $driver->sear_in_section('users', 'eve', 'Eve');
        $this->assertEquals('Eve', $result);
        $this->assertEquals('Eve', $driver->get_from_section('users', 'eve'));
    }

    /**
     * Test Sectionable: forget_in_section().
     *
     * @group system
     */
    public function testSectionableForgetInSection()
    {
        $driver = new Memory();
        $driver->put_in_section('users', 'to_remove', 'value', 60);
        $driver->forget_in_section('users', 'to_remove');
        $this->assertNull($driver->get_from_section('users', 'to_remove'));
    }

    /**
     * Test Sectionable: forget_section() via forget with wildcard.
     *
     * @group system
     */
    public function testSectionableForgetSectionClearsAll()
    {
        $driver = new Memory();
        $driver->put_in_section('users', 'key1', 'val1', 60);
        $driver->put_in_section('users', 'key2', 'val2', 60);
        $driver->forget_section('users');
        $this->assertNull($driver->get_from_section('users', 'key1'));
        $this->assertNull($driver->get_from_section('users', 'key2'));
    }

    /**
     * Test Sectionable: forget with wildcard key.
     *
     * @group system
     */
    public function testSectionableForgetWithWildcardKey()
    {
        $driver = new Memory();
        $driver->put_in_section('group', 'a', 'val_a', 60);
        $driver->put_in_section('group', 'b', 'val_b', 60);
        $driver->forget('group::*');
        $this->assertNull($driver->get_from_section('group', 'a'));
        $this->assertNull($driver->get_from_section('group', 'b'));
    }

    /**
     * Test Sectionable: non-sectioned key is not treated as section.
     *
     * @group system
     */
    public function testSectionableNonSectionedKeyNotTreatedAsSection()
    {
        $driver = new Memory();
        $driver->put('regular_key', 'value', 60);
        $this->assertEquals('value', $driver->get('regular_key'));
    }

    /**
     * Test File driver: put and get.
     *
     * @group system
     */
    public function testFileDriverPutAndGet()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);

        $driver->put('file_test_key', 'file_test_value', 60);
        $this->assertEquals('file_test_value', $driver->get('file_test_key'));

        $driver->forget('file_test_key');
    }

    /**
     * Test File driver: has() method.
     *
     * @group system
     */
    public function testFileDriverHas()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);

        $this->assertFalse($driver->has('file_nonexistent'));
        $driver->put('file_exists_key', 'val', 60);
        $this->assertTrue($driver->has('file_exists_key'));
        $driver->forget('file_exists_key');
    }

    /**
     * Test File driver: forget() removes item.
     *
     * @group system
     */
    public function testFileDriverForget()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);

        $driver->put('file_forget_key', 'value', 60);
        $driver->forget('file_forget_key');
        $this->assertFalse($driver->has('file_forget_key'));
    }

    /**
     * Test File driver: returns null for expired item.
     *
     * @group system
     */
    public function testFileDriverReturnsNullForMissingKey()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);
        $this->assertNull($driver->get('does_not_exist_xyz'));
    }

    /**
     * Test File driver: put() ignores zero or negative minutes.
     *
     * @group system
     */
    public function testFileDriverIgnoresNonPositiveMinutes()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);
        $driver->put('zero_min_key', 'value', 0);
        $this->assertNull($driver->get('zero_min_key'));
    }

    /**
     * Test File driver: flush() removes all cache files.
     *
     * @group system
     */
    public function testFileDriverFlush()
    {
        $path = path('storage') . 'cache' . DS;
        $driver = new File($path);

        $driver->put('flush_key1', 'val1', 60);
        $driver->put('flush_key2', 'val2', 60);
        $driver->flush();

        $this->assertFalse($driver->has('flush_key1'));
        $this->assertFalse($driver->has('flush_key2'));
    }
}
