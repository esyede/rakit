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
        $this->assertEquals(['foo' => 'bar'], (new Parameter(['foo' => 'bar']))->all());
    }

    public function testReplace()
    {
        $bag = new Parameter(['foo' => 'bar']);
        $bag->replace(['FOO' => 'BAR']);
        $this->assertEquals(['FOO' => 'BAR'], $bag->all());
        $this->assertFalse($bag->has('foo'));
    }

    public function testGet()
    {
        $bag = new Parameter(['foo' => 'bar', 'null' => null]);
        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals('default', $bag->get('unknown', 'default'));
        $this->assertNull($bag->get('null', 'default'));
    }

    public function testGetDoesNotUseDeepByDefault()
    {
        $this->assertNull((new Parameter(['foo' => ['bar' => 'moo']]))->get('foo[bar]'));
    }

    /**
     * @dataProvider getInvalidPaths
     * @expectedException \Exception
     */
    public function testGetDeepWithInvalidPaths($path)
    {
        (new Parameter(['foo' => ['bar' => 'moo']]))->get($path, null, true);
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
        $this->assertEquals('bar', $bag->get('foo'));

        $bag->set('foo', 'baz');
        $this->assertEquals('baz', $bag->get('foo'));
    }

    public function testHas()
    {
        $bag = new Parameter(['foo' => 'bar']);

        $this->assertTrue($bag->has('foo'));
        $this->assertFalse($bag->has('unknown'));
    }

    public function testGetAlpha()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('fooBAR', $bag->getAlpha('word'));
        $this->assertEquals('', $bag->getAlpha('unknown'));
    }

    public function testGetAlnum()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('fooBAR012', $bag->getAlnum('word'));
        $this->assertEquals('', $bag->getAlnum('unknown'));
    }

    public function testGetDigits()
    {
        $bag = new Parameter(['word' => 'foo_BAR_012']);

        $this->assertEquals('012', $bag->getDigits('word'));
        $this->assertEquals('', $bag->getDigits('unknown'));
    }

    public function testGetInt()
    {
        $bag = new Parameter(['digits' => '0123']);

        $this->assertEquals(123, $bag->getInt('digits'));
        $this->assertEquals(0, $bag->getInt('unknown'));
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

        $this->assertEmpty($bag->filter('nokey'));
        $this->assertEquals('0123', $bag->filter('digits', '', false, FILTER_SANITIZE_NUMBER_INT));
        $this->assertEquals('example@example.com', $bag->filter('email', '', false, FILTER_VALIDATE_EMAIL));
        $this->assertEquals(
            'http://example.com/foo',
            $bag->filter('url', '', false, FILTER_VALIDATE_URL, ['flags' => FILTER_FLAG_PATH_REQUIRED])
        );

        $this->assertEquals(
            'http://example.com/foo',
            $bag->filter('url', '', false, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED)
        );
        $this->assertFalse(
            $bag->filter('dec', '', false, FILTER_VALIDATE_INT, ['flags' => FILTER_FLAG_ALLOW_HEX, 'options' => ['min_range' => 1, 'max_range' => 0xff]])
        );

        $this->assertFalse(
            $bag->filter('hex', '', false, FILTER_VALIDATE_INT, ['flags' => FILTER_FLAG_ALLOW_HEX, 'options' => ['min_range' => 1, 'max_range' => 0xff]])
        );

        $this->assertEquals(['bang'], $bag->filter('array', '', false));

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
