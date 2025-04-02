<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Parameter;

class HttpParameterTest extends \PHPUnit_Framework_TestCase
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
        // ..
    }

    public function testConstructor()
    {
        $this->testAll();
    }

    public function testAll()
    {
        $bag = new Parameter(['foo' => 'bar']);
        $this->assertEquals(['foo' => 'bar'], $bag->all(), '->all() gets all the input');
    }

    public function testReplace()
    {
        $bag = new Parameter(['foo' => 'bar']);
        $bag->replace(['FOO' => 'BAR']);
        $this->assertEquals(['FOO' => 'BAR'], $bag->all(), '->replace() replaces the input with the argument');
        $this->assertFalse($bag->has('foo'), '->replace() overrides previously set the input');
    }

    public function testGet()
    {
        $bag = new Parameter(['foo' => 'bar', 'null' => null]);
        $this->assertEquals('bar', $bag->get('foo'), '->get() gets the value of a parameter');
        $this->assertEquals('default', $bag->get('unknown', 'default'), '->get() returns second argument as default if a parameter is not defined');
        $this->assertNull($bag->get('null', 'default'), '->get() returns null if null is set');
    }

    public function testGetDoesNotUseDeepByDefault()
    {
        $bag = new Parameter(['foo' => ['bar' => 'moo']]);
        $this->assertNull($bag->get('foo[bar]'));
    }

    /**
     * @dataProvider getInvalidPaths
     * @expectedException \Exception
     */
    public function testGetDeepWithInvalidPaths($path)
    {
        $bag = new Parameter(['foo' => ['bar' => 'moo']]);
        $bag->get($path, null, true);
    }

    public function getInvalidPaths()
    {
        return [['foo[['], ['foo[d'], ['foo[bar]]'], ['foo[bar]d']];
    }

    public function testGetDeep()
    {
        $bag = new Parameter(['foo' => ['bar' => ['moo' => 'boo']]]);

        $this->assertEquals(['moo' => 'boo'], $bag->get('foo[bar]', null, true));
        $this->assertEquals('boo', $bag->get('foo[bar][moo]', null, true));
        $this->assertEquals('default', $bag->get('foo[bar][foo]', 'default', true));
        $this->assertEquals('default', $bag->get('bar[moo][foo]', 'default', true));
    }

    public function testSet()
    {
        $bag = new Parameter([]);

        $bag->set('foo', 'bar');
        $this->assertEquals('bar', $bag->get('foo'), '->set() sets the value of parameter');

        $bag->set('foo', 'baz');
        $this->assertEquals('baz', $bag->get('foo'), '->set() overrides previously set parameter');
    }

    public function testHas()
    {
        $bag = new Parameter(['foo' => 'bar']);

        $this->assertTrue($bag->has('foo'), '->has() returns true if a parameter is defined');
        $this->assertFalse($bag->has('unknown'), '->has() return false if a parameter is not defined');
    }

    public function testGetAlpha()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('fooBAR', $bag->getAlpha('word'), '->getAlpha() gets only alphabetic characters');
        $this->assertEquals('', $bag->getAlpha('unknown'), '->getAlpha() returns empty string if a parameter is not defined');
    }

    public function testGetAlnum()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('fooBAR012', $bag->getAlnum('word'), '->getAlnum() gets only alphanumeric characters');
        $this->assertEquals('', $bag->getAlnum('unknown'), '->getAlnum() returns empty string if a parameter is not defined');
    }

    public function testGetDigits()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('012', $bag->getDigits('word'), '->getDigits() gets only digits as string');
        $this->assertEquals('', $bag->getDigits('unknown'), '->getDigits() returns empty string if a parameter is not defined');
    }

    public function testGetInt()
    {
        $bag = new Parameter(['digits' => '0123']);

        $this->assertEquals(123, $bag->getInt('digits'), '->getInt() gets a value of parameter as integer');
        $this->assertEquals(0, $bag->getInt('unknown'), '->getInt() returns zero if a parameter is not defined');
    }

    public function testFilter()
    {
        $bag = new Parameter([
            'digits' => '0123ab',
            'email' => 'example@example.com',
            'url' => 'http://example.com/foo',
            'dec' => '256',
            'hex' => '0x100',
            'array' => ['bang'],
        ]);

        $this->assertEmpty($bag->filter('nokey'), '->filter() should return empty by default if no key is found');
        $this->assertEquals('0123', $bag->filter('digits', '', false, FILTER_SANITIZE_NUMBER_INT), '->filter() gets a value of parameter as integer filtering out invalid characters');
        $this->assertEquals('example@example.com', $bag->filter('email', '', false, FILTER_VALIDATE_EMAIL), '->filter() gets a value of parameter as email');
        $this->assertEquals(
            'http://example.com/foo',
            $bag->filter('url', '', false, FILTER_VALIDATE_URL, ['flags' => FILTER_FLAG_PATH_REQUIRED]),
            '->filter() gets a value of parameter as url with a path'
        );

        $this->assertEquals(
            'http://example.com/foo',
            $bag->filter('url', '', false, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED),
            '->filter() gets a value of parameter as url with a path'
        );
        $this->assertFalse(
            $bag->filter('dec', '', false, FILTER_VALIDATE_INT, ['flags'   => FILTER_FLAG_ALLOW_HEX, 'options' => ['min_range' => 1, 'max_range' => 0xff]]),
            '->filter() gets a value of parameter as integer between boundaries'
        );

        $this->assertFalse(
            $bag->filter('hex', '', false, FILTER_VALIDATE_INT, ['flags' => FILTER_FLAG_ALLOW_HEX, 'options' => ['min_range' => 1, 'max_range' => 0xff]]),
            '->filter() gets a value of parameter as integer between boundaries'
        );

        $this->assertEquals(['bang'], $bag->filter('array', '', false), '->filter() gets a value of parameter as an array');

    }

    public function testGetIterator()
    {
        $parameters = ['foo' => 'bar', 'hello' => 'world'];
        $bag = new Parameter($parameters);
        $i = 0;
        foreach ($bag as $key => $val) {
            $i++;
            $this->assertEquals($parameters[$key], $val);
        }

        $this->assertEquals(count($parameters), $i);
    }

    public function testCount()
    {
        $parameters = ['foo' => 'bar', 'hello' => 'world'];
        $bag = new Parameter($parameters);
        $this->assertEquals(count($parameters), count($bag));
    }
}
