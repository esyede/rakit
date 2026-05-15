<?php

defined('DS') or exit('No direct access.');

use System\Cache;
use System\Config;
use System\Str;

class HelpersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Cache::$drivers = [];
        Cache::$registrar = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Cache::$drivers = [];
        Cache::$registrar = [];
    }

    // -------------------------------------------------------------------------
    // e()
    // -------------------------------------------------------------------------

    /**
     * Test for e() - escapes HTML special characters.
     *
     * @group system
     */
    public function testEscapeHtmlChars()
    {
        $this->assertEquals('&lt;div&gt;', e('<div>'));
        $this->assertEquals('&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;', e('<script>alert("xss")</script>'));
        $this->assertEquals('Tom &amp; Jerry', e('Tom & Jerry'));
        $this->assertEquals('&#039;quoted&#039;', e("'quoted'"));
    }

    /**
     * Test for e() - leaves safe strings untouched.
     *
     * @group system
     */
    public function testEscapeHtmlSafeString()
    {
        $this->assertEquals('Hello World', e('Hello World'));
        $this->assertEquals('123', e(123));
    }

    // -------------------------------------------------------------------------
    // is_cli()
    // -------------------------------------------------------------------------

    /**
     * Test for is_cli() - returns true in test environment (CLI).
     *
     * @group system
     */
    public function testIsCliReturnsTrueInCliEnvironment()
    {
        $this->assertTrue(is_cli());
    }

    // -------------------------------------------------------------------------
    // data_get()
    // -------------------------------------------------------------------------

    /**
     * Test for data_get() - simple key access.
     *
     * @group system
     */
    public function testDataGetSimpleKey()
    {
        $data = ['name' => 'John', 'age' => 30];
        $this->assertEquals('John', data_get($data, 'name'));
        $this->assertEquals(30, data_get($data, 'age'));
    }

    /**
     * Test for data_get() - dot notation.
     *
     * @group system
     */
    public function testDataGetDotNotation()
    {
        $data = ['user' => ['name' => 'John', 'address' => ['city' => 'Jakarta']]];
        $this->assertEquals('John', data_get($data, 'user.name'));
        $this->assertEquals('Jakarta', data_get($data, 'user.address.city'));
    }

    /**
     * Test for data_get() - returns default for missing key.
     *
     * @group system
     */
    public function testDataGetReturnsDefaultForMissingKey()
    {
        $data = ['name' => 'John'];
        $this->assertNull(data_get($data, 'missing'));
        $this->assertEquals('default', data_get($data, 'missing', 'default'));
    }

    /**
     * Test for data_get() - returns target when key is null.
     *
     * @group system
     */
    public function testDataGetReturnsTargetWhenKeyIsNull()
    {
        $data = ['name' => 'John'];
        $this->assertSame($data, data_get($data, null));
    }

    /**
     * Test for data_get() - wildcard key.
     *
     * @group system
     */
    public function testDataGetWildcard()
    {
        $data = [
            ['name' => 'Alice', 'age' => 25],
            ['name' => 'Bob', 'age' => 30],
        ];
        $names = data_get($data, '*.name');
        $this->assertEquals(['Alice', 'Bob'], $names);
    }

    /**
     * Test for data_get() - wildcard with non-array target returns default.
     *
     * @group system
     */
    public function testDataGetWildcardWithNonArrayReturnsDefault()
    {
        $result = data_get('not_array', '*');
        $this->assertNull($result);
    }

    /**
     * Test for data_get() - access object property.
     *
     * @group system
     */
    public function testDataGetObjectProperty()
    {
        $obj = new \stdClass();
        $obj->name = 'Test';
        $this->assertEquals('Test', data_get($obj, 'name'));
    }

    /**
     * Test for data_get() - array key from array input.
     *
     * @group system
     */
    public function testDataGetArrayKeyInput()
    {
        $data = ['user' => ['name' => 'Jane']];
        $this->assertEquals('Jane', data_get($data, ['user', 'name']));
    }

    // -------------------------------------------------------------------------
    // data_set()
    // -------------------------------------------------------------------------

    /**
     * Test for data_set() - simple key.
     *
     * @group system
     */
    public function testDataSetSimpleKey()
    {
        $data = [];
        data_set($data, 'name', 'John');
        $this->assertEquals('John', $data['name']);
    }

    /**
     * Test for data_set() - dot notation.
     *
     * @group system
     */
    public function testDataSetDotNotation()
    {
        $data = [];
        data_set($data, 'user.name', 'Alice');
        $this->assertEquals('Alice', $data['user']['name']);
    }

    /**
     * Test for data_set() - overwrite=false does not overwrite.
     *
     * @group system
     */
    public function testDataSetDoesNotOverwriteWhenFalse()
    {
        $data = ['name' => 'John'];
        data_set($data, 'name', 'Alice', false);
        $this->assertEquals('John', $data['name']);
    }

    /**
     * Test for data_set() - wildcard sets all items.
     *
     * @group system
     */
    public function testDataSetWildcardSetsAllItems()
    {
        $data = [['name' => 'a'], ['name' => 'b']];
        data_set($data, '*.name', 'x');
        $this->assertEquals('x', $data[0]['name']);
        $this->assertEquals('x', $data[1]['name']);
    }

    /**
     * Test for data_set() - wildcard on non-array creates array.
     *
     * @group system
     */
    public function testDataSetWildcardOnNonArrayCreatesArray()
    {
        $data = 'not_array';
        data_set($data, '*.val', 1);
        $this->assertInternalType('array', $data);
    }

    /**
     * Test for data_set() - nested wildcard with segments.
     *
     * @group system
     */
    public function testDataSetWildcardWithSegments()
    {
        $data = [['user' => []], ['user' => []]];
        data_set($data, '*.user.name', 'test');
        $this->assertEquals('test', $data[0]['user']['name']);
        $this->assertEquals('test', $data[1]['user']['name']);
    }

    /**
     * Test for data_set() - sets on object properties.
     *
     * @group system
     */
    public function testDataSetOnObject()
    {
        $obj = new \stdClass();
        data_set($obj, 'name', 'Jane');
        $this->assertEquals('Jane', $obj->name);
    }

    /**
     * Test for data_set() - creates nested object properties.
     *
     * @group system
     */
    public function testDataSetCreatesNestedObjectProperties()
    {
        $obj = new \stdClass();
        data_set($obj, 'address.city', 'Surabaya');
        $this->assertEquals('Surabaya', $obj->address['city']);
    }

    /**
     * Test for data_set() - does not overwrite existing object property when overwrite=false.
     *
     * @group system
     */
    public function testDataSetDoesNotOverwriteObjectPropertyWhenFalse()
    {
        $obj = new \stdClass();
        $obj->name = 'Original';
        data_set($obj, 'name', 'New', false);
        $this->assertEquals('Original', $obj->name);
    }

    // -------------------------------------------------------------------------
    // data_fill()
    // -------------------------------------------------------------------------

    /**
     * Test for data_fill() - fills missing key.
     *
     * @group system
     */
    public function testDataFillFillsMissingKey()
    {
        $data = [];
        data_fill($data, 'name', 'John');
        $this->assertEquals('John', $data['name']);
    }

    /**
     * Test for data_fill() - does not overwrite existing key.
     *
     * @group system
     */
    public function testDataFillDoesNotOverwriteExistingKey()
    {
        $data = ['name' => 'Existing'];
        data_fill($data, 'name', 'New');
        $this->assertEquals('Existing', $data['name']);
    }

    // -------------------------------------------------------------------------
    // tap()
    // -------------------------------------------------------------------------

    /**
     * Test for tap() - calls callback and returns value.
     *
     * @group system
     */
    public function testTapCallsCallbackAndReturnsValue()
    {
        $called = false;
        $result = tap('hello', function ($val) use (&$called) {
            $called = true;
            $this->assertEquals('hello', $val);
        });

        $this->assertTrue($called);
        $this->assertEquals('hello', $result);
    }

    // -------------------------------------------------------------------------
    // head() and last()
    // -------------------------------------------------------------------------

    /**
     * Test for head() - returns first element.
     *
     * @group system
     */
    public function testHeadReturnsFirstElement()
    {
        $this->assertEquals(1, head([1, 2, 3]));
        $this->assertEquals('a', head(['a', 'b', 'c']));
    }

    /**
     * Test for last() - returns last element.
     *
     * @group system
     */
    public function testLastReturnsLastElement()
    {
        $this->assertEquals(3, last([1, 2, 3]));
        $this->assertEquals('c', last(['a', 'b', 'c']));
    }

    // -------------------------------------------------------------------------
    // root_namespace() and class_basename()
    // -------------------------------------------------------------------------

    /**
     * Test for root_namespace() - returns root namespace.
     *
     * @group system
     */
    public function testRootNamespaceReturnsRootNamespace()
    {
        $this->assertEquals('System', root_namespace('System\Arr'));
        $this->assertEquals('System', root_namespace('System\Cache\Drivers\Memory'));
        $this->assertNull(root_namespace('NoNamespaceClass'));
    }

    /**
     * Test for class_basename() - returns class name without namespace.
     *
     * @group system
     */
    public function testClassBasenameReturnsClassWithoutNamespace()
    {
        $this->assertEquals('Arr', class_basename('System\Arr'));
        $this->assertEquals('Memory', class_basename('System\Cache\Drivers\Memory'));
        $this->assertEquals('SimpleClass', class_basename('SimpleClass'));
    }

    /**
     * Test for class_basename() - accepts object.
     *
     * @group system
     */
    public function testClassBasenameAcceptsObject()
    {
        $obj = new \stdClass();
        $this->assertEquals('stdClass', class_basename($obj));
    }

    // -------------------------------------------------------------------------
    // value()
    // -------------------------------------------------------------------------

    /**
     * Test for value() - returns non-closure as-is.
     *
     * @group system
     */
    public function testValueReturnsNonClosureAsIs()
    {
        $this->assertEquals('hello', value('hello'));
        $this->assertEquals(42, value(42));
        $this->assertNull(value(null));
        $this->assertFalse(value(false));
    }

    /**
     * Test for value() - calls and returns Closure result.
     *
     * @group system
     */
    public function testValueCallsClosureAndReturnsResult()
    {
        $result = value(function () {
            return 'computed';
        });
        $this->assertEquals('computed', $result);
    }

    // -------------------------------------------------------------------------
    // when()
    // -------------------------------------------------------------------------

    /**
     * Test for when() - returns value when condition is true.
     *
     * @group system
     */
    public function testWhenReturnsValueWhenTrue()
    {
        $this->assertEquals('yes', when(true, 'yes', 'no'));
    }

    /**
     * Test for when() - returns default when condition is false.
     *
     * @group system
     */
    public function testWhenReturnsDefaultWhenFalse()
    {
        $this->assertEquals('no', when(false, 'yes', 'no'));
    }

    /**
     * Test for when() - condition as Closure.
     *
     * @group system
     */
    public function testWhenWithClosureCondition()
    {
        $result = when(function () {
            return true;
        }, 'yes', 'no');
        $this->assertEquals('yes', $result);
    }

    /**
     * Test for when() - value as Closure.
     *
     * @group system
     */
    public function testWhenWithClosureValue()
    {
        $result = when(true, function () {
            return 'computed';
        }, 'default');
        $this->assertEquals('computed', $result);
    }

    /**
     * Test for when() - default as Closure.
     *
     * @group system
     */
    public function testWhenWithClosureDefault()
    {
        $result = when(false, 'value', function () {
            return 'computed_default';
        });
        $this->assertEquals('computed_default', $result);
    }

    // -------------------------------------------------------------------------
    // blank() and filled()
    // -------------------------------------------------------------------------

    /**
     * Test for blank() - null is blank.
     *
     * @group system
     */
    public function testBlankNullIsBlank()
    {
        $this->assertTrue(blank(null));
    }

    /**
     * Test for blank() - empty string is blank.
     *
     * @group system
     */
    public function testBlankEmptyStringIsBlank()
    {
        $this->assertTrue(blank(''));
        $this->assertTrue(blank('   '));
    }

    /**
     * Test for blank() - non-empty string is not blank.
     *
     * @group system
     */
    public function testBlankNonEmptyStringNotBlank()
    {
        $this->assertFalse(blank('hello'));
        $this->assertFalse(blank('0'));
    }

    /**
     * Test for blank() - numeric values.
     *
     * @group system
     */
    public function testBlankNumericValues()
    {
        $this->assertFalse(blank(0));
        $this->assertFalse(blank(1));
        $this->assertFalse(blank(0.0));
    }

    /**
     * Test for blank() - booleans.
     *
     * @group system
     */
    public function testBlankBooleans()
    {
        $this->assertFalse(blank(true));
        $this->assertFalse(blank(false));
    }

    /**
     * Test for blank() - empty array is blank.
     *
     * @group system
     */
    public function testBlankEmptyArrayIsBlank()
    {
        $this->assertTrue(blank([]));
    }

    /**
     * Test for blank() - non-empty array is not blank.
     *
     * @group system
     */
    public function testBlankNonEmptyArrayNotBlank()
    {
        $this->assertFalse(blank([1, 2, 3]));
    }

    /**
     * Test for blank() - Countable with zero count is blank.
     *
     * @group system
     */
    public function testBlankCountableWithZeroIsBlank()
    {
        $collection = new \System\Collection([]);
        $this->assertTrue(blank($collection));
    }

    /**
     * Test for blank() - Countable with items is not blank.
     *
     * @group system
     */
    public function testBlankCountableWithItemsNotBlank()
    {
        $collection = new \System\Collection([1, 2]);
        $this->assertFalse(blank($collection));
    }

    /**
     * Test for filled() - opposite of blank().
     *
     * @group system
     */
    public function testFilledOppositeOfBlank()
    {
        $this->assertFalse(filled(null));
        $this->assertFalse(filled(''));
        $this->assertTrue(filled('hello'));
        $this->assertTrue(filled(0));
        $this->assertTrue(filled([1]));
    }

    // -------------------------------------------------------------------------
    // human_filesize()
    // -------------------------------------------------------------------------

    /**
     * Test for human_filesize() - bytes.
     *
     * @group system
     */
    public function testHumanFilesizeBytes()
    {
        $this->assertEquals('512.00 B', human_filesize(512));
        $this->assertEquals('1.00 KB', human_filesize(1024));
        $this->assertEquals('1.00 MB', human_filesize(1024 * 1024));
        $this->assertEquals('1.00 GB', human_filesize(1024 * 1024 * 1024));
    }

    /**
     * Test for human_filesize() - zero bytes.
     *
     * @group system
     */
    public function testHumanFilesizeZero()
    {
        $this->assertEquals('0.00 B', human_filesize(0));
    }

    /**
     * Test for human_filesize() - custom precision.
     *
     * @group system
     */
    public function testHumanFilesizeCustomPrecision()
    {
        $this->assertEquals('1.5 KB', human_filesize(1536, 1));
        $this->assertEquals('1.500 KB', human_filesize(1536, 3));
    }

    // -------------------------------------------------------------------------
    // system_os()
    // -------------------------------------------------------------------------

    /**
     * Test for system_os() - returns a non-empty string.
     *
     * @group system
     */
    public function testSystemOsReturnsString()
    {
        $os = system_os();
        $this->assertInternalType('string', $os);
        $this->assertNotEmpty($os);
    }

    // -------------------------------------------------------------------------
    // collect()
    // -------------------------------------------------------------------------

    /**
     * Test for collect() - creates Collection instance.
     *
     * @group system
     */
    public function testCollectCreatesCollectionInstance()
    {
        $col = collect([1, 2, 3]);
        $this->assertInstanceOf('System\Collection', $col);
        $this->assertEquals(3, $col->count());
    }

    /**
     * Test for collect() - default empty array.
     *
     * @group system
     */
    public function testCollectDefaultEmptyArray()
    {
        $col = collect();
        $this->assertInstanceOf('System\Collection', $col);
        $this->assertEquals(0, $col->count());
    }

    // -------------------------------------------------------------------------
    // now()
    // -------------------------------------------------------------------------

    /**
     * Test for now() - returns Carbon instance.
     *
     * @group system
     */
    public function testNowReturnsCarbonInstance()
    {
        $now = now();
        $this->assertInstanceOf('System\Carbon', $now);
    }

    // -------------------------------------------------------------------------
    // validate()
    // -------------------------------------------------------------------------

    /**
     * Test for validate() - creates Validator instance.
     *
     * @group system
     */
    public function testValidateCreatesValidatorInstance()
    {
        $validator = validate(['name' => 'John'], ['name' => 'required']);
        $this->assertInstanceOf('System\Validator', $validator);
    }

    /**
     * Test for validate() - validates and passes.
     *
     * @group system
     */
    public function testValidatePasses()
    {
        $validator = validate(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($validator->passes());
    }

    /**
     * Test for validate() - validates and fails.
     *
     * @group system
     */
    public function testValidateFails()
    {
        $validator = validate(['name' => ''], ['name' => 'required']);
        $this->assertTrue($validator->fails());
    }

    // -------------------------------------------------------------------------
    // optional()
    // -------------------------------------------------------------------------

    /**
     * Test for optional() - creates Optional instance for null.
     *
     * @group system
     */
    public function testOptionalCreatesInstanceForNull()
    {
        $opt = optional(null);
        $this->assertInstanceOf('System\Optional', $opt);
        $this->assertNull($opt->nonexistent);
    }

    /**
     * Test for optional() - passes value to callback.
     *
     * @group system
     */
    public function testOptionalPassesValueToCallback()
    {
        $result = optional('hello', function ($val) {
            return strtoupper($val);
        });
        $this->assertEquals('HELLO', $result);
    }

    // -------------------------------------------------------------------------
    // bcrypt()
    // -------------------------------------------------------------------------

    /**
     * Test for bcrypt() - creates hash.
     *
     * @group system
     */
    public function testBcryptCreatesHash()
    {
        $hash = bcrypt('password');
        $this->assertNotEmpty($hash);
        $this->assertNotEquals('password', $hash);
        $this->assertTrue(\System\Hash::check('password', $hash));
    }

    // -------------------------------------------------------------------------
    // encrypt() and decrypt()
    // -------------------------------------------------------------------------

    /**
     * Test for encrypt() and decrypt() - roundtrip.
     *
     * @group system
     */
    public function testEncryptDecryptRoundtrip()
    {
        $original = 'secret data';
        $encrypted = encrypt($original);
        $this->assertNotEquals($original, $encrypted);
        $decrypted = decrypt($encrypted);
        $this->assertEquals($original, $decrypted);
    }

    // -------------------------------------------------------------------------
    // config()
    // -------------------------------------------------------------------------

    /**
     * Test for config() - gets config value.
     *
     * @group system
     */
    public function testConfigGetsValue()
    {
        $encoding = config('application.encoding');
        $this->assertNotEmpty($encoding);
    }

    /**
     * Test for config() - returns default for missing key.
     *
     * @group system
     */
    public function testConfigReturnsDefaultForMissingKey()
    {
        $result = config('application.nonexistent_key_xyz', 'default_val');
        $this->assertEquals('default_val', $result);
    }

    /**
     * Test for config() - sets config values via array.
     *
     * @group system
     */
    public function testConfigSetsValuesViaArray()
    {
        $result = config(['application.test_key_xyz' => 'test_value']);
        $this->assertTrue($result);
        $this->assertEquals('test_value', config('application.test_key_xyz'));
        Config::set('application.test_key_xyz', null);
    }

    // -------------------------------------------------------------------------
    // cache()
    // -------------------------------------------------------------------------

    /**
     * Test for cache() - sets values via array (uses Cache::forever()).
     *
     * @group system
     */
    public function testCacheSetsValuesViaArray()
    {
        $memory = new \System\Cache\Drivers\Memory();
        Cache::extend('memory', function () use ($memory) {
            return $memory;
        });
        Config::set('cache.driver', 'memory');

        $result = cache(['helpers_cache_key' => 'helpers_cache_val']);
        $this->assertTrue($result);
        $this->assertEquals('helpers_cache_val', $memory->get('helpers_cache_key'));

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for cache() - gets cache values.
     *
     * @group system
     */
    public function testCacheGetsValues()
    {
        $memory = new \System\Cache\Drivers\Memory();
        $memory->put('helpers_get_key', 'helpers_get_val', 60);
        Cache::extend('memory', function () use ($memory) {
            return $memory;
        });
        Config::set('cache.driver', 'memory');

        $value = cache('helpers_get_key');
        $this->assertEquals('helpers_get_val', $value);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for cache() - returns null for missing key.
     *
     * @group system
     */
    public function testCacheReturnsNullForMissingKey()
    {
        Cache::extend('memory', function () {
            return new \System\Cache\Drivers\Memory();
        });
        Config::set('cache.driver', 'memory');

        $value = cache('nonexistent_cache_key_xyz');
        $this->assertNull($value);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    // -------------------------------------------------------------------------
    // retry()
    // -------------------------------------------------------------------------

    /**
     * Test for retry() - succeeds on first attempt.
     *
     * @group system
     */
    public function testRetrySucceedsOnFirstAttempt()
    {
        $attempts = 0;
        $result = retry(3, function ($attempt) use (&$attempts) {
            $attempts = $attempt;
            return 'success';
        });

        $this->assertEquals('success', $result);
        $this->assertEquals(1, $attempts);
    }

    /**
     * Test for retry() - retries on failure.
     *
     * @group system
     */
    public function testRetryRetriesOnFailure()
    {
        $attempts = 0;
        $result = retry(3, function ($attempt) use (&$attempts) {
            $attempts = $attempt;
            if ($attempt < 3) {
                throw new \Exception('Temporary failure');
            }
            return 'recovered';
        });

        $this->assertEquals('recovered', $result);
        $this->assertEquals(3, $attempts);
    }

    /**
     * Test for retry() - throws after max retries.
     *
     * @group system
     * @expectedException Exception
     */
    public function testRetryThrowsAfterMaxRetries()
    {
        retry(2, function () {
            throw new \Exception('Always fails');
        });
    }

    /**
     * Test for retry() - respects $when condition to stop early.
     *
     * @group system
     * @expectedException Exception
     */
    public function testRetryRespectsWhenCondition()
    {
        retry(5, function () {
            throw new \RuntimeException('Unrecoverable');
        }, 0, function ($e) {
            return !($e instanceof \RuntimeException);
        });
    }

    // -------------------------------------------------------------------------
    // get_cli_option() and has_cli_flag()
    // -------------------------------------------------------------------------

    /**
     * Test for get_cli_option() - returns default when option not found.
     *
     * @group system
     */
    public function testGetCliOptionReturnsDefaultWhenNotFound()
    {
        $result = get_cli_option('nonexistent_option_xyz', 'fallback');
        $this->assertEquals('fallback', $result);
    }

    /**
     * Test for get_cli_option() - returns null by default.
     *
     * @group system
     */
    public function testGetCliOptionReturnsNullByDefault()
    {
        $result = get_cli_option('nonexistent_option_xyz');
        $this->assertNull($result);
    }

    /**
     * Test for has_cli_flag() - returns false when flag not found.
     *
     * @group system
     */
    public function testHasCliFlagReturnsFalseWhenNotFound()
    {
        $this->assertFalse(has_cli_flag('nonexistent_flag_xyz'));
    }

    // -------------------------------------------------------------------------
    // csrf_name()
    // -------------------------------------------------------------------------

    /**
     * Test for csrf_name() - returns CSRF token name constant.
     *
     * @group system
     */
    public function testCsrfNameReturnsTokenName()
    {
        $name = csrf_name();
        $this->assertEquals(\System\Session::TOKEN, $name);
    }

    // -------------------------------------------------------------------------
    // dispatch()
    // -------------------------------------------------------------------------

    /**
     * Test for dispatch() - fires event via Hook.
     *
     * @group system
     */
    public function testDispatchFiresEvent()
    {
        $fired = false;
        \System\Hook::listen('test.dispatch.event', function () use (&$fired) {
            $fired = true;
        });

        dispatch('test.dispatch.event');
        $this->assertTrue($fired);

        unset(\System\Hook::$events['test.dispatch.event']);
    }
}
