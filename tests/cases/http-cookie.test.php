<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Http\Cookie;

class HttpCookieTest extends \PHPUnit_Framework_TestCase
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

    public function invalidNames()
    {
        return [[''], [',MyName'], [';MyName'], [' MyName'], ["\tMyName"], ["\rMyName"], ["\nMyName"], ["\013MyName"], ["\014MyName"]];
    }

    /**
     * @dataProvider invalidNames
     * @expectedException \InvalidArgumentException
     */
    public function testInstantiationThrowsExceptionIfCookieNameContainsInvalidCharacters($name)
    {
        new Cookie($name);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidExpiration()
    {
        $cookie = new Cookie('MyCookie', 'foo', 'bar');
    }

    public function testGetValue()
    {
        $this->assertSame('MyValue', (new Cookie('MyCookie', 'MyValue'))->getValue());
    }

    public function testGetPath()
    {
        $this->assertSame('/', (new Cookie('foo', 'bar'))->getPath());
    }

    public function testGetExpiresTime()
    {
        $this->assertEquals(3600, (new Cookie('foo', 'bar', 3600))->getExpiresTime());
    }

    public function testGetDomain()
    {
        $this->assertEquals('.myfoodomain.com', (new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com'))->getDomain());
    }

    public function testIsSecure()
    {
        $this->assertTrue((new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com', true))->isSecure());
    }

    public function testIsHttpOnly()
    {
        $this->assertTrue((new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com', false, true))->isHttpOnly());
    }

    public function testCookieIsNotCleared()
    {
        $this->assertFalse((new Cookie('foo', 'bar', time() + 3600 * 24))->isCleared());
    }

    public function testCookieIsCleared()
    {
        $this->assertTrue((new Cookie('foo', 'bar', time() - 20))->isCleared());
    }

    public function testToString()
    {
        $cookie = new Cookie('foo', 'bar', strtotime('Fri, 20-May-2011 15:25:52 GMT'), '/', '.myfoodomain.com', true);
        $this->assertEquals(
            'foo=bar; expires=Fri, 20-May-2011 15:25:52 GMT; samesite=lax; domain=.myfoodomain.com; secure; httponly',
            $cookie->__toString()
        );

        $cookie = new Cookie('foo', null, 1, '/admin/', '.myfoodomain.com');
        $this->assertEquals(
            'foo=deleted; expires=' . gmdate('D, d-M-Y H:i:s T', time() - 31536001) . '; path=/admin/; samesite=lax; domain=.myfoodomain.com; httponly',
            $cookie->__toString()
        );
    }

    public function testSessionCookieIsNotReportedAsCleared()
    {
        // An expiration of 0 marks a session cookie (dropped when the browser
        // closes), not a cookie that expired at the epoch.
        $this->assertFalse((new Cookie('foo', 'bar'))->isCleared());
        $this->assertFalse((new Cookie('foo', 'bar', 0))->isCleared());
        $this->assertFalse((new Cookie('foo', 'bar', '0'))->isCleared());
    }

    public function testExpirationTimeIsNormalizedToInteger()
    {
        $this->assertSame(0, (new Cookie('foo', 'bar'))->getExpiresTime());
        $this->assertSame(3600, (new Cookie('foo', 'bar', 3600))->getExpiresTime());
        $this->assertSame(3600, (new Cookie('foo', 'bar', '3600'))->getExpiresTime());
        $this->assertSame(3600, (new Cookie('foo', 'bar', new \DateTime('@3600')))->getExpiresTime());
    }

    public function testAcceptsDateTimeImmutableAsExpiration()
    {
        if (!class_exists('DateTimeImmutable')) {
            $this->markTestSkipped('DateTimeImmutable is available on PHP 5.5.0+ only');
        }

        // DateTimeImmutable is not a subclass of DateTime, so it used to reach
        // the strtotime() branch and die on the object-to-string cast.
        $cookie = new Cookie('foo', 'bar', new \DateTimeImmutable('@3600'));

        $this->assertSame(3600, $cookie->getExpiresTime());
    }

    /**
     * A path or domain carrying a separator or a line break would end the
     * Set-Cookie attribute, or the header itself, early. Servers that render the
     * header themselves have no setcookie() to fall back on, so the cookie
     * refuses the characters rather than relying on what emits it.
     *
     * @group system
     */
    public function testRejectsPathAndDomainThatCouldSplitTheHeader()
    {
        $rejected = [
            ["/\r\nSet-Cookie: admin=1", null],
            ['/', "evil.com\r\nX-Injected: 1"],
            ['/a;b', null],
            ['/', 'evil .com'],
            ["/a\tb", null],
        ];

        foreach ($rejected as $case) {
            try {
                new Cookie('name', 'value', 0, $case[0], $case[1]);
                $this->fail('Expected the cookie to reject '.json_encode($case));
            } catch (\InvalidArgumentException $e) {
                $this->assertContains('invalid characters', $e->getMessage());
            }
        }

        // What a real path and domain look like must still go through, including
        // the '=' that PHP allows in a cookie path.
        $cookie = new Cookie('name', 'value', 0, '/app/v1=2', '.example.com');

        $this->assertContains('path=/app/v1=2', (string) $cookie);
        $this->assertContains('domain=.example.com', (string) $cookie);
    }
}
