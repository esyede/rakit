<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Cookie;
use System\Foundation\Http\Helper;

class HttpHelperTest extends \PHPUnit_Framework_TestCase
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

    public function testCacheControlHeader()
    {
        $bag = new Helper([]);
        $this->assertEquals('no-cache', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('no-cache'));

        $bag = new Helper(['Cache-Control' => 'public']);
        $this->assertEquals('public', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('public'));

        $bag = new Helper(['ETag' => 'abcde']);
        $this->assertEquals('private, must-revalidate', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('private'));
        $this->assertTrue($bag->hasCacheControlDirective('must-revalidate'));
        $this->assertFalse($bag->hasCacheControlDirective('max-age'));

        $bag = new Helper(['Expires' => 'Wed, 16 Feb 2011 14:17:43 GMT']);
        $this->assertEquals('private, must-revalidate', $bag->get('Cache-Control'));

        $bag = new Helper(['Expires' => 'Wed, 16 Feb 2011 14:17:43 GMT', 'Cache-Control' => 'max-age=3600']);
        $this->assertEquals('max-age=3600, private', $bag->get('Cache-Control'));

        $bag = new Helper(['Last-Modified' => 'abcde']);
        $this->assertEquals('private, must-revalidate', $bag->get('Cache-Control'));

        $bag = new Helper(['Etag' => 'abcde', 'Last-Modified' => 'abcde']);
        $this->assertEquals('private, must-revalidate', $bag->get('Cache-Control'));

        $bag = new Helper(['cache-control' => 'max-age=100']);
        $this->assertEquals('max-age=100, private', $bag->get('Cache-Control'));

        $bag = new Helper(['cache-control' => 's-maxage=100']);
        $this->assertEquals('s-maxage=100', $bag->get('Cache-Control'));

        $bag = new Helper(['cache-control' => 'private, max-age=100']);
        $this->assertEquals('max-age=100, private', $bag->get('Cache-Control'));

        $bag = new Helper(['cache-control' => 'public, max-age=100']);
        $this->assertEquals('max-age=100, public', $bag->get('Cache-Control'));

        $bag = new Helper();
        $bag->set('Last-Modified', 'abcde');
        $this->assertEquals('private, must-revalidate', $bag->get('Cache-Control'));
    }

    public function testToStringIncludesCookieHeaders()
    {
        $bag = new Helper([]);
        $bag->setCookie(new Cookie('foo', 'bar'));
        $this->assertContains("Set-Cookie: foo=bar; samesite=lax; httponly", explode("\r\n", $bag->__toString()));

        $bag->clearCookie('foo');

        $this->assertContains(
            "Set-Cookie: foo=deleted; expires=" . gmdate("D, d-M-Y H:i:s T", time() - 31536001) . "; samesite=lax; httponly",
            explode("\r\n", $bag->__toString())
        );
    }

    public function testReplace()
    {
        $bag = new Helper([]);
        $this->assertEquals('no-cache', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('no-cache'));

        $bag->replace(['Cache-Control' => 'public']);
        $this->assertEquals('public', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('public'));
    }

    public function testReplaceWithRemove()
    {
        $bag = new Helper([]);
        $this->assertEquals('no-cache', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('no-cache'));

        $bag->remove('Cache-Control');
        $bag->replace([]);
        $this->assertEquals('no-cache', $bag->get('Cache-Control'));
        $this->assertTrue($bag->hasCacheControlDirective('no-cache'));
    }

    public function testCookiesWithSameNames()
    {
        $bag = new Helper();
        $bag->setCookie(new Cookie('foo', 'bar', 0, '/path/foo', 'foo.bar'));
        $bag->setCookie(new Cookie('foo', 'bar', 0, '/path/bar', 'foo.bar'));
        $bag->setCookie(new Cookie('foo', 'bar', 0, '/path/bar', 'bar.foo'));
        $bag->setCookie(new Cookie('foo', 'bar'));

        $this->assertCount(4, $bag->getCookies());

        $headers = explode("\r\n", $bag->__toString());
        $this->assertContains("Set-Cookie: foo=bar; path=/path/foo; samesite=lax; domain=foo.bar; httponly", $headers);
        $this->assertContains("Set-Cookie: foo=bar; path=/path/foo; samesite=lax; domain=foo.bar; httponly", $headers);
        $this->assertContains("Set-Cookie: foo=bar; path=/path/bar; samesite=lax; domain=bar.foo; httponly", $headers);
        $this->assertContains("Set-Cookie: foo=bar; samesite=lax; httponly", $headers);

        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertTrue(isset($cookies['foo.bar']['/path/foo']['foo']));
        $this->assertTrue(isset($cookies['foo.bar']['/path/bar']['foo']));
        $this->assertTrue(isset($cookies['bar.foo']['/path/bar']['foo']));
        $this->assertTrue(isset($cookies['']['/']['foo']));
    }

    public function testRemoveCookie()
    {
        $bag = new Helper();
        $bag->setCookie(new Cookie('foo', 'bar', 0, '/path/foo', 'foo.bar'));
        $bag->setCookie(new Cookie('bar', 'foo', 0, '/path/bar', 'foo.bar'));

        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertTrue(isset($cookies['foo.bar']['/path/foo']));

        $bag->removeCookie('foo', '/path/foo', 'foo.bar');

        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertFalse(isset($cookies['foo.bar']['/path/foo']));

        $bag->removeCookie('bar', '/path/bar', 'foo.bar');

        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertFalse(isset($cookies['foo.bar']));
    }

    public function testRemoveCookieWithNullRemove()
    {
        $bag = new Helper();
        $bag->setCookie(new Cookie('foo', 'bar', 0));
        $bag->setCookie(new Cookie('bar', 'foo', 0));

        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertTrue(isset($cookies['']['/']));

        $bag->removeCookie('foo', null);
        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertFalse(isset($cookies['']['/']['foo']));

        $bag->removeCookie('bar', null);
        $cookies = $bag->getCookies(Helper::COOKIES_ARRAY);
        $this->assertFalse(isset($cookies['']['/']['bar']));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetCookiesWithInvalidArgument()
    {
        $bag = new Helper();
        $cookies = $bag->getCookies('invalid_argument');
    }

    /**
     * @expectedException \Exception
     */
    public function testMakeDispositionInvalidDisposition()
    {
        $headers = new Helper();
        $headers->makeDisposition('invalid', 'foo.html');
    }

    public function testMakeDisposition()
    {
        $disp = (new Helper())->makeDisposition('attachment', 'foo.html', 'foo.html');
        $this->assertEquals('attachment; filename="foo.html"', $disp);

        $disp = (new Helper())->makeDisposition('attachment', 'foo.html', '');
        $this->assertEquals('attachment; filename="foo.html"', $disp);

        $disp = (new Helper())->makeDisposition('attachment', 'foo bar.html', '');
        $this->assertEquals('attachment; filename="foo bar.html"', $disp);

        $disp = (new Helper())->makeDisposition('attachment', 'foo "bar".html', '');
        $this->assertEquals('attachment; filename="foo \\"bar\\".html"; filename*=utf-8\'\'foo%20%22bar%22.html', $disp);

        $disp = (new Helper())->makeDisposition('attachment', 'foo%20bar.html', 'foo bar.html');
        $this->assertEquals('attachment; filename="foo bar.html"; filename*=utf-8\'\'foo%2520bar.html', $disp);

        $disp = (new Helper())->makeDisposition('attachment', 'föö.html', 'foo.html');
        $this->assertEquals('attachment; filename="foo.html"; filename*=utf-8\'\'f%C3%B6%C3%B6.html', $disp);
    }

    /**
     * @expectedException \Exception
     */
    public function testMakeDispositionFail()
    {
        (new Helper())->makeDisposition('attachment', 'foo%20bar.html');
        (new Helper())->makeDisposition('attachment', 'foo/bar.html');
        (new Helper())->makeDisposition('attachment', '/foo.html');
        (new Helper())->makeDisposition('attachment', 'foo\bar.html');
        (new Helper())->makeDisposition('attachment', '\foo.html');
        (new Helper())->makeDisposition('attachment', 'föö.html');
    }
}
