<?php

namespace SystemCookieTest;

defined('DS') or exit('No direct access.');

use System\Cookie;
use System\Crypter;
use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

/**
 * Override the native setcookie function.
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
     * Helper: set value on $_SERVER and restart request.
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
     * Re-initialize the request instance.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];
        Request::$foundation = FoundationRequest::createFromGlobals();
    }

    /**
     * Test for Cookie::has().
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
     * Test for Cookie::get().
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
     * Test for Cookie::forever().
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
     * Test that a cookie this application cannot read counts as missing.
     *
     * Rethrowing here used to take the whole site down: Session::load()
     * reads the session cookie while the application is still booting, so
     * a cookie left behind by another application on the same host, or a
     * rotated key, turned every single request into a fatal error.
     *
     * @group system
     */
    public function testUnreadableCookieInJarIsTreatedAsMissing()
    {
        $payload = base64_encode(json_encode([
            'iv' => base64_encode(str_repeat('a', 16)),
            'value' => 'ciphertext',
            'mac' => str_repeat('0', 64),
        ]));

        Cookie::$jar['foreign'] = ['value' => $payload];

        $this->assertNull(Cookie::get('foreign'));
        $this->assertEquals('fallback', Cookie::get('foreign', 'fallback'));
        $this->assertFalse(Cookie::has('foreign'));
    }

    /**
     * Test that an unreadable cookie sent by the browser counts as missing.
     *
     * @group system
     */
    public function testUnreadableRequestCookieIsTreatedAsMissing()
    {
        $_COOKIE['stale'] = 'not-an-encrypted-payload';
        $this->restartRequest();

        $this->assertNull(Cookie::get('stale'));
        $this->assertEquals('fallback', Cookie::get('stale', 'fallback'));

        unset($_COOKIE['stale']);
        $this->restartRequest();
    }

    /**
     * Test for Cookie::forget().
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
