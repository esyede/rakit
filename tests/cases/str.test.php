<?php

defined('DS') or exit('No direct script access.');

class StrTest extends \PHPUnit_Framework_TestCase
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

    public function testStringCanBeLimitedByWords()
    {
        $this->assertSame('Budi...', Str::words('Budi Purnomo', 1));
        $this->assertSame('Budi___', Str::words('Budi Purnomo', 1, '___'));
        $this->assertSame('Budi Purnomo', Str::words('Budi Purnomo', 3));
    }

    public function testStringTrimmedOnlyWhereNecessary()
    {
        $this->assertSame(' Budi Purnomo ', Str::words(' Budi Purnomo ', 3));
        $this->assertSame(' Budi...', Str::words(' Budi Purnomo ', 1));
    }

    public function testStringTitle()
    {
        $this->assertSame('Budi Purnomo', Str::title('budi purnomo'));
        $this->assertSame('Budi Purnomo', Str::title('bUdI PuRNOmO'));
    }

    public function testStringWithoutWordsDoesntProduceError()
    {
        $this->assertSame(' ', Str::words(' '));

        $nbsp = chr(0xC2).chr(0xA0);
        $this->assertEquals($nbsp, Str::words($nbsp));
    }

    public function testStartsWith()
    {
        $this->assertTrue(Str::starts_with('budi', 'bud'));
        $this->assertTrue(Str::starts_with('budi', 'budi'));
        $this->assertFalse(Str::starts_with('budi', 'nomo'));

        $this->assertFalse(Str::starts_with('budi', null));

        $this->assertTrue(Str::starts_with('0123', 0));

        $this->assertFalse(Str::starts_with('budi', 'B'));
        $this->assertFalse(Str::starts_with('budi', ''));

        $this->assertFalse(Str::starts_with('', ''));

        $this->assertFalse(Str::starts_with('7', ' 7'));
        $this->assertTrue(Str::starts_with('7a', '7'));

        $this->assertTrue(Str::starts_with('7a', 7));
        $this->assertTrue(Str::starts_with('7.12a', 7.12));
        $this->assertFalse(Str::starts_with('7.12a', 7.13));
        $this->assertTrue(Str::starts_with(7.123, '7'));
        $this->assertTrue(Str::starts_with(7.123, '7.12'));
        $this->assertFalse(Str::starts_with(7.123, '7.13'));

        $this->assertTrue(Str::starts_with('Tönö', 'Tö'));
        $this->assertTrue(Str::starts_with('Tonö', 'Tonö'));
        $this->assertFalse(Str::starts_with('Tönö', 'To'));
        $this->assertFalse(Str::starts_with('Tonö', 'Tono'));

        $this->assertTrue(Str::starts_with('你好', '你'));
        $this->assertFalse(Str::starts_with('你好', '好'));
        $this->assertFalse(Str::starts_with('你好', 'a'));
    }

    public function testEndsWith()
    {
        $this->assertTrue(Str::ends_with('purnomo', 'mo'));
        $this->assertTrue(Str::ends_with('purnomo', 'purnomo'));
        $this->assertFalse(Str::ends_with('purnomo', 'om'));
        $this->assertFalse(Str::ends_with('purnomo', ''));

        $this->assertFalse(Str::ends_with('', ''));

        $this->assertFalse(Str::ends_with('purnomo', null));
        $this->assertFalse(Str::ends_with('purnomo', 'O'));

        $this->assertFalse(Str::ends_with('7', ' 7'));
        $this->assertTrue(Str::ends_with('a7', '7'));

        $this->assertTrue(Str::ends_with('a7', 7));
        $this->assertTrue(Str::ends_with('a7.12', 7.12));
        $this->assertFalse(Str::ends_with('a7.12', 7.13));

        $this->assertTrue(Str::ends_with(0.27, '7'));
        $this->assertTrue(Str::ends_with(0.27, '0.27'));
        $this->assertFalse(Str::ends_with(0.27, '8'));

        $this->assertTrue(Str::ends_with('Purnömo', 'ömo'));
        $this->assertTrue(Str::ends_with('Tonö', 'nö'));
        $this->assertFalse(Str::ends_with('Purnömö', 'omo'));
        $this->assertFalse(Str::ends_with('Tonö', 'no'));

        $this->assertTrue(Str::ends_with('你好', '好'));
        $this->assertFalse(Str::ends_with('你好', '你'));
        $this->assertFalse(Str::ends_with('你好', 'a'));
    }

    public function testStrBefore()
    {
        $this->assertSame('maw', Str::before('mawar', 'ar'));
        $this->assertSame('ma', Str::before('mawar', 'w'));

        $this->assertSame('ééé ', Str::before('ééé mawar', 'maw'));

        $this->assertSame('mawar', Str::before('mawar', 'xxxx'));
        $this->assertSame('mawar', Str::before('mawar', ''));

        $this->assertSame('maw', Str::before('maw0ar', '0'));
        $this->assertSame('maw', Str::before('maw0ar', 0));
        $this->assertSame('maw', Str::before('maw2ar', 2));
    }

    public function testStrAfter()
    {
        $this->assertSame('war', Str::after('mawar', 'ma'));
        $this->assertSame('ar', Str::after('mawar', 'w'));

        $this->assertSame('war', Str::after('ééé mawar', 'ma'));

        $this->assertSame('mawar', Str::after('mawar', 'xxxx'));
        $this->assertSame('mawar', Str::after('mawar', ''));

        $this->assertSame('war', Str::after('ma0war', '0'));
        $this->assertSame('war', Str::after('ma0war', 0));
        $this->assertSame('war', Str::after('ma2war', 2));
    }

    public function testStrContains()
    {
        $this->assertTrue(Str::contains('mawar', 'aw'));
        $this->assertTrue(Str::contains('mawar', 'mawar'));
        $this->assertTrue(Str::contains('mawar', ['awa']));
        $this->assertTrue(Str::contains('mawar', ['xxx', 'awa']));
        $this->assertFalse(Str::contains('mawar', 'xxx'));
        $this->assertFalse(Str::contains('mawar', ['xxx']));
        $this->assertFalse(Str::contains('mawar', ''));
        $this->assertFalse(Str::contains('', ''));
    }

    public function testStrContainsAll()
    {
        $this->assertTrue(Str::contains_all('mawar melati', ['mawar', 'melati']));
        $this->assertTrue(Str::contains_all('mawar melati', ['mawar']));
        $this->assertFalse(Str::contains_all('mawar melati', ['mawar', 'xxx']));
    }

    public function testParseCallback()
    {
        $this->assertEquals(['Class', 'method'], Str::parse_callback('Class@method', 'foo'));
        $this->assertEquals(['Class', 'foo'], Str::parse_callback('Class', 'foo'));
        $this->assertEquals(['Class', null], Str::parse_callback('Class'));
    }

    public function testSlug()
    {
        $this->assertSame('hello-world', Str::slug('hello world'));
        $this->assertSame('hello-world', Str::slug('hello-world'));
        $this->assertSame('hello-world', Str::slug('hello_world'));
        $this->assertSame('hello_world', Str::slug('hello_world', '_'));
        $this->assertSame('user-at-host', Str::slug('user@host'));

        $this->assertSame('سلام-دنیا', Str::slug('سلام دنیا', '-', null));

        $this->assertSame('foobar', Str::slug('foo bar', ''));
        $this->assertSame('', Str::slug('', ''));
        $this->assertSame('', Str::slug(''));
    }

    public function testStrStart()
    {
        $this->assertSame('/test/string', Str::start('test/string', '/'));
        $this->assertSame('/test/string', Str::start('/test/string', '/'));
        $this->assertSame('/test/string', Str::start('//test/string', '/'));
    }

    public function testFinish()
    {
        $this->assertSame('abbc', Str::finish('ab', 'bc'));
        $this->assertSame('abbc', Str::finish('abbcbc', 'bc'));
        $this->assertSame('abcbbc', Str::finish('abcbbcbc', 'bc'));
    }

    public function testIs()
    {
        $this->assertTrue(Str::is('/', '/'));
        $this->assertFalse(Str::is('/', ' /'));
        $this->assertFalse(Str::is('/', '/a'));
        $this->assertTrue(Str::is('foo/*', 'foo/bar/baz'));

        $this->assertTrue(Str::is('*@*', 'Class@method'));
        $this->assertTrue(Str::is('*@*', 'class@'));
        $this->assertTrue(Str::is('*@*', '@method'));

        $this->assertFalse(Str::is('*BAZ*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('*FOO*', 'foo/bar/baz'));
        $this->assertFalse(Str::is('A', 'a'));

        $this->assertTrue(Str::is(['a*', 'b*'], 'a/'));
        $this->assertTrue(Str::is(['a*', 'b*'], 'b/'));
        $this->assertFalse(Str::is(['a*', 'b*'], 'f/'));

        $this->assertFalse(Str::is(['a*', 'b*'], 123));
        $this->assertTrue(Str::is(['*2*', 'b*'], 11211));

        $this->assertTrue(Str::is('*/foo', 'blah/baz/foo'));

        $this->assertFalse(Str::is([], 'test'));
    }

    public function testKebab()
    {
        $this->assertSame('rakit-php-framework', Str::kebab('RakitPhpFramework'));
    }

    public function testLower()
    {
        $this->assertSame('foo bar baz', Str::lower('FOO BAR BAZ'));
        $this->assertSame('foo bar baz', Str::lower('fOo Bar bAz'));
    }

    public function testUpper()
    {
        $this->assertSame('FOO BAR BAZ', Str::upper('foo bar baz'));
        $this->assertSame('FOO BAR BAZ', Str::upper('foO bAr BaZ'));
    }

    public function testLimit()
    {
        $this->assertSame('Rakit is...', Str::limit('Rakit is a free, open source PHP framework.', 8));
        $this->assertSame('这是一...', Str::limit('这是一段中文', 6));

        $string = 'Rakit is open source.';
        $this->assertSame('Rakit is open...', Str::limit($string, 13));
        $this->assertSame('Rakit is open', Str::limit($string, 13, ''));
        $this->assertSame('Rakit is open source.', Str::limit($string, 100));

        $nonAsciiString = '这是一段中文';
        $this->assertSame('这是一...', Str::limit($nonAsciiString, 6));
        $this->assertSame('这是一', Str::limit($nonAsciiString, 6, ''));
    }

    public function testTrim()
    {
        $value = '      test1       ';
        $this->assertSame('test1', Str::trim($value));

        $value = html_entity_decode(' test2 &#160; ');
        $this->assertSame('test2', Str::trim($value));

        $value = ' 𩸽 test3 ホ 𩸽 ';
        $this->assertSame('𩸽 test3 ホ 𩸽', Str::trim($value));
    }

    public function testLength()
    {
        $this->assertEquals(11, Str::length('foo bar baz'));
        $this->assertEquals(11, Str::length('foo bar baz', 'UTF-8'));
    }

    public function testRandom()
    {
        $this->assertEquals(16, mb_strlen(Str::random(), '8bit'));
        $integers = Str::integers(1, 100);
        $this->assertEquals($integers, mb_strlen(Str::random($integers), '8bit'));
        $this->assertTrue(is_string(Str::random()));
    }

    public function testUuid()
    {
        $uuid = Str::uuid();
        $this->assertEquals(36, mb_strlen($uuid, '8bit'));
        $this->assertTrue((bool) preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}$/Di', $uuid));
    }

    public function testUlid()
    {
        $this->assertTrue((bool) preg_match('/[0-9][A-Z]/', Str::ulid()));
        $this->assertTrue((bool) preg_match('/[0-9][a-z]/', Str::ulid(true)));
        $this->assertTrue(strlen(Str::ulid()) === 26);

        $a = Str::ulid();
        sleep(1);
        $b = Str::ulid();
        $ulids = [$b, $a];
        usort($ulids, 'strcmp');
        $this->assertSame([$a, $b], $ulids);
    }

    public function testNanoid()
    {
        $nanoid = Str::nanoid(7);
        $this->assertEquals(7, mb_strlen($nanoid, '8bit'));
        $match = preg_match('/^[a-z0-9]+$/iu', $nanoid)
            || preg_match('/^[a-z]+$/iu', $nanoid)
            || preg_match('/^[0-9]+$/u', $nanoid);
        $this->assertTrue($match);

        $nanoid = Str::nanoid(10, '0123456789abcdefghi');
        $this->assertEquals(10, mb_strlen($nanoid, '8bit'));
        $match = preg_match('/^[a-z0-9]+$/u', $nanoid)
            || preg_match('/^[a-z]+$/u', $nanoid)
            || preg_match('/^[0-9]+$/u', $nanoid);
        $this->assertTrue((bool) $match);

        $nanoid = Str::nanoid(10, '0123456789ABCDEFGHI');
        $this->assertEquals(10, mb_strlen($nanoid, '8bit'));
        $match = preg_match('/^[A-Z0-9]+$/u', $nanoid)
            || preg_match('/^[A-Z]+$/u', $nanoid)
            || preg_match('/^[0-9]+$/u', $nanoid);
        $this->assertTrue((bool) $match);

        $nanoid = Str::nanoid(10, '0123456789');
        $this->assertEquals(10, strlen($nanoid));
        $this->assertTrue((bool) preg_match('/^[0-9]+$/', $nanoid));
    }

    public function testReplaceArray()
    {
        $this->assertSame('foo/bar/baz', Str::replace_array('?', ['foo', 'bar', 'baz'], '?/?/?'));
        $this->assertSame('foo/bar/baz/?', Str::replace_array('?', ['foo', 'bar', 'baz'], '?/?/?/?'));
        $this->assertSame('foo/bar', Str::replace_array('?', ['foo', 'bar', 'baz'], '?/?'));
        $this->assertSame('?/?/?', Str::replace_array('x', ['foo', 'bar', 'baz'], '?/?/?'));

        $this->assertSame('foo?/bar/baz', Str::replace_array('?', ['foo?', 'bar', 'baz'], '?/?/?'));

        $this->assertSame('foo/bar', Str::replace_array('?', [1 => 'foo', 2 => 'bar'], '?/?'));
        $this->assertSame('foo/bar', Str::replace_array('?', ['x' => 'foo', 'y' => 'bar'], '?/?'));
    }

    public function testReplaceFirst()
    {
        $this->assertSame('fooqux foobar', Str::replace_first('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/qux? foo/bar?', Str::replace_first('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foo foobar', Str::replace_first('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replace_first('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replace_first('', 'yyy', 'foobar foobar'));

        $this->assertSame('Jxxxnköping Malmö', Str::replace_first('ö', 'xxx', 'Jönköping Malmö'));
        $this->assertSame('Jönköping Malmö', Str::replace_first('', 'yyy', 'Jönköping Malmö'));
    }

    public function testReplaceLast()
    {
        $this->assertSame('foobar fooqux', Str::replace_last('bar', 'qux', 'foobar foobar'));
        $this->assertSame('foo/bar? foo/qux?', Str::replace_last('bar?', 'qux?', 'foo/bar? foo/bar?'));
        $this->assertSame('foobar foo', Str::replace_last('bar', '', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replace_last('xxx', 'yyy', 'foobar foobar'));
        $this->assertSame('foobar foobar', Str::replace_last('', 'yyy', 'foobar foobar'));

        $this->assertSame('Malmö Jönkxxxping', Str::replace_last('ö', 'xxx', 'Malmö Jönköping'));
        $this->assertSame('Malmö Jönköping', Str::replace_last('', 'yyy', 'Malmö Jönköping'));
    }

    public function testSnake()
    {
        $this->assertSame('rakit_p_h_p_framework', Str::snake('RakitPHPFramework'));
        $this->assertSame('rakit_php_framework', Str::snake('RakitPhpFramework'));
        $this->assertSame('rakit php framework', Str::snake('RakitPhpFramework', ' '));
        $this->assertSame('rakit_php_framework', Str::snake('Rakit Php Framework'));
        $this->assertSame('rakit_php_framework', Str::snake('Rakit    Php      Framework   '));

        $this->assertSame('rakit__php__framework', Str::snake('RakitPhpFramework', '__'));
        $this->assertSame('rakit_php_framework_', Str::snake('RakitPhpFramework_', '_'));
        $this->assertSame('rakit_php_framework', Str::snake('rakit php Framework'));
        $this->assertSame('rakit_php_frame_work', Str::snake('rakit php FrameWork'));

        $this->assertSame('foo-bar', Str::snake('foo-bar'));
        $this->assertSame('foo-_bar', Str::snake('Foo-Bar'));
        $this->assertSame('foo__bar', Str::snake('Foo_Bar'));
        $this->assertSame('żółtałódka', Str::snake('ŻółtaŁódka'));
    }

    public function testStudly()
    {
        $this->assertSame('RakitPHPFramework', Str::studly('rakit_p_h_p_framework'));
        $this->assertSame('RakitPhpFramework', Str::studly('rakit_php_framework'));
        $this->assertSame('RakitPhPFramework', Str::studly('rakit-phP-framework'));
        $this->assertSame('RakitPhpFramework', Str::studly('rakit  -_-  php   -_-   framework   '));

        $this->assertSame('FooBar', Str::studly('fooBar'));
        $this->assertSame('FooBar', Str::studly('foo_bar'));
        $this->assertSame('FooBar', Str::studly('foo_bar')); // test cache
        $this->assertSame('FooBarBaz', Str::studly('foo-barBaz'));
        $this->assertSame('FooBarBaz', Str::studly('foo-bar_baz'));
    }

    public function testCamel()
    {
        $this->assertSame('rakitPHPFramework', Str::camel('Rakit_p_h_p_framework'));
        $this->assertSame('rakitPhpFramework', Str::camel('Rakit_php_framework'));
        $this->assertSame('rakitPhPFramework', Str::camel('Rakit-phP-framework'));
        $this->assertSame('rakitPhpFramework', Str::camel('Rakit  -_-  php   -_-   framework   '));

        $this->assertSame('fooBar', Str::camel('FooBar'));
        $this->assertSame('fooBar', Str::camel('foo_bar'));
        $this->assertSame('fooBar', Str::camel('foo_bar'));
        $this->assertSame('fooBarBaz', Str::camel('Foo-barBaz'));
        $this->assertSame('fooBarBaz', Str::camel('foo-bar_baz'));
    }

    public function testSubstr()
    {
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1));
        $this->assertSame('ЛЁ', Str::substr('БГДЖИЛЁ', -2));
        $this->assertSame('И', Str::substr('БГДЖИЛЁ', -3, 1));
        $this->assertSame('ДЖИЛ', Str::substr('БГДЖИЛЁ', 2, -1));
        $this->assertEmpty(Str::substr('БГДЖИЛЁ', 4, -4));
        $this->assertSame('ИЛ', Str::substr('БГДЖИЛЁ', -3, -1));
        $this->assertSame('ГДЖИЛЁ', Str::substr('БГДЖИЛЁ', 1));
        $this->assertSame('ГДЖ', Str::substr('БГДЖИЛЁ', 1, 3));
        $this->assertSame('БГДЖ', Str::substr('БГДЖИЛЁ', 0, 4));
        $this->assertSame('Ё', Str::substr('БГДЖИЛЁ', -1, 1));
        $this->assertEmpty(Str::substr('Б', 2));
    }

    public function testUcfirst()
    {
        $this->assertSame('Rakit', Str::ucfirst('rakit'));
        $this->assertSame('Rakit framework', Str::ucfirst('rakit framework'));
        $this->assertSame('Мама', Str::ucfirst('мама'));
        $this->assertSame('Мама мыла раму', Str::ucfirst('мама мыла раму'));
    }

    public function testMacro()
    {
        Str::$macros = [];
        Str::macro('reverse', function ($value) {
            return strrev($value);
        });

        $this->assertSame('!dlrow olleH', Str::reverse('Hello world!'));

        try {
            Str::macro('reverse', function ($value) {
                return strrev($value);
            });
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \Exception);
        }

        Str::$macros = [];
    }
}
