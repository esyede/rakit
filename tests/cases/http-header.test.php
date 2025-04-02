<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Header;

class HttpHeaderTest extends \PHPUnit_Framework_TestCase
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
        $this->assertTrue((new Header(['foo' => 'bar']))->has('foo'));
    }

    public function testAll()
    {
        $this->assertEquals(['Foo' => ['bar']], (new Header(['foo' => 'bar']))->all());
        $this->assertEquals(['Foo' => ['BAR']], (new Header(['FOO' => 'BAR']))->all());
    }

    public function testReplace()
    {
        $bag = new Header(['foo' => 'bar']);
        $bag->replace(['NOPE' => 'BAR']);
        $this->assertEquals(['Nope' => ['BAR']], $bag->all());
        $this->assertFalse($bag->has('foo'));
    }

    public function testGet()
    {
        $bag = new Header(['foo' => 'bar', 'fuzz' => 'bizz']);

        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals('bar', $bag->get('FoO'));
        $this->assertEquals(['bar'], $bag->get('foo', 'nope', false));

        $this->assertNull($bag->get('none'));
        $this->assertEquals('default', $bag->get('none', 'default'));
        $this->assertEquals(['default'], $bag->get('none', 'default', false));

        $bag->set('foo', 'bor', false);
        $this->assertEquals('bar', $bag->get('foo'));
        $this->assertEquals(['bar', 'bor'], $bag->get('foo', 'nope', false));
    }

    public function testSetAssociativeArray()
    {
        $bag = new Header();
        $bag->set('foo', ['bad-assoc-index' => 'value']);
        $this->assertSame('value', $bag->get('foo'));
        $this->assertEquals(['value'], $bag->get('foo', 'nope', false));
    }

    public function testContains()
    {
        $bag = new Header(['foo' => 'bar', 'fuzz' => 'bizz']);

        $this->assertTrue(  $bag->contains('foo', 'bar'));
        $this->assertTrue(  $bag->contains('fuzz', 'bizz'));
        $this->assertFalse(  $bag->contains('nope', 'nope'));
        $this->assertFalse(  $bag->contains('foo', 'nope'));

        $bag->set('foo', 'bor', false);
        $this->assertTrue(  $bag->contains('foo', 'bar'));
        $this->assertTrue(  $bag->contains('foo', 'bor'));
        $this->assertFalse(  $bag->contains('foo', 'nope'));
    }

    public function testCacheControlDirectiveAccessors()
    {
        $bag = new Header();
        $bag->addCacheControlDirective('public');

        $this->assertTrue($bag->hasCacheControlDirective('public'));
        $this->assertTrue($bag->getCacheControlDirective('public'));
        $this->assertEquals('public', $bag->get('cache-control'));

        $bag->addCacheControlDirective('max-age', 10);
        $this->assertTrue($bag->hasCacheControlDirective('max-age'));
        $this->assertEquals(10, $bag->getCacheControlDirective('max-age'));
        $this->assertEquals('max-age=10, public', $bag->get('cache-control'));

        $bag->removeCacheControlDirective('max-age');
        $this->assertFalse($bag->hasCacheControlDirective('max-age'));
    }

    public function testCacheControlDirectiveParsing()
    {
        $bag = new Header(['cache-control' => 'public, max-age=10']);
        $this->assertTrue($bag->hasCacheControlDirective('public'));
        $this->assertTrue($bag->getCacheControlDirective('public'));

        $this->assertTrue($bag->hasCacheControlDirective('max-age'));
        $this->assertEquals(10, $bag->getCacheControlDirective('max-age'));

        $bag->addCacheControlDirective('s-maxage', 100);
        $this->assertEquals('max-age=10, public, s-maxage=100', $bag->get('cache-control'));
    }

    public function testCacheControlDirectiveParsingQuotedZero()
    {
        $bag = new Header(['cache-control' => 'max-age="0"']);
        $this->assertTrue($bag->hasCacheControlDirective('max-age'));
        $this->assertEquals(0, $bag->getCacheControlDirective('max-age'));
    }

    public function testCacheControlDirectiveOverrideWithReplace()
    {
        $bag = new Header(['cache-control' => 'private, max-age=100']);
        $bag->replace(['cache-control' => 'public, max-age=10']);
        $this->assertTrue($bag->hasCacheControlDirective('public'));
        $this->assertTrue($bag->getCacheControlDirective('public'));

        $this->assertTrue($bag->hasCacheControlDirective('max-age'));
        $this->assertEquals(10, $bag->getCacheControlDirective('max-age'));
    }

    public function testGetIterator()
    {
        $headers   = ['foo' => 'bar', 'hello' => 'world', 'third' => 'charm'];
        $header = new Header($headers);

        $i = 0;
        foreach ($header as $key => $val) {
            $i++;
            $this->assertEquals([$headers[strtolower($key)]], $val);
        }

        $this->assertEquals(count($headers), $i);
    }

    public function testCount()
    {
        $headers   = ['foo' => 'bar', 'HELLO' => 'WORLD'];
        $header = new Header($headers);

        $this->assertEquals(count($headers), count($header));
    }
}
