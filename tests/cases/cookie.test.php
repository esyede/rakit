<?php

namespace SystemCookieTest;

defined('DS') or exit('No direct access.');

use System\Cookie;
use System\Crypter;
use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

/**
 * Override beberapa fungsi bawaan PHP untuk keperluan testing.
 */
function setcookie($name, $value, $time, $path, $domain, $secure)
{
    $_SERVER['cookie.stub'][$name] = compact('name', 'value', 'time', 'path', 'domain', 'secure');
}

function headers_sent()
{
    return $_SERVER['function.headers_sent'];
}

class CookieTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Cookie::$jar = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Cookie::$jar = [];
    }

    /**
     * Helper: set value di $_SERVER.
     *
     * @param string $key
     * @param string $value
     */
    protected function setServerVar($key, $value)
    {
        $_SERVER[$key] = $value;

        $this->restartRequest();
    }

    /**
     * Inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = FoundationRequest::createFromGlobals();
    }

    /**
     * Test untuk method Cookie::has().
     *
     * @group system
     */
    public function testHasMethodIndicatesIfCookieInSet()
    {
        Cookie::$jar['foo'] = ['value' => Crypter::encrypt('bar')];

        $this->assertTrue(Cookie::has('foo'));
        $this->assertFalse(Cookie::has('bar'));

        Cookie::put('baz', 'qux');

        $this->assertTrue(Cookie::has('baz'));
    }

    /**
     * Test untuk method Cookie::get().
     *
     * @group system
     */
    public function testGetMethodCanReturnValueOfCookies()
    {
        Cookie::$jar['foo'] = ['value' => Crypter::encrypt('bar')];

        $this->assertEquals('bar', Cookie::get('foo'));

        Cookie::put('bar', 'baz');

        $this->assertEquals('baz', Cookie::get('bar'));
    }

    /**
     * Test untuk method Cookie::forever().
     *
     * @group system
     */
    public function testForeverShouldUseATonOfMinutes()
    {
        Cookie::forever('foo', 'bar');
        $this->assertEquals('bar', Crypter::decrypt(Cookie::$jar['foo']['value']));

        $this->setServerVar('HTTPS', 'on');
        Cookie::forever('bar', 'baz', 'path', 'domain', true);

        $this->assertEquals('path', Cookie::$jar['bar']['path']);
        $this->assertEquals('domain', Cookie::$jar['bar']['domain']);
        $this->assertTrue(Cookie::$jar['bar']['secure']);

        $this->setServerVar('HTTPS', 'off');
    }

    /**
     * Test untuk method Cookie::forget().
     *
     * @group system
     */
    public function testForgetSetsCookieWithExpiration()
    {
        Cookie::forget('bar', 'path', 'domain');

        $this->assertEquals('path', Cookie::$jar['bar']['path']);
        $this->assertEquals('domain', Cookie::$jar['bar']['domain']);
        $this->assertFalse(Cookie::$jar['bar']['secure']);
    }
}
