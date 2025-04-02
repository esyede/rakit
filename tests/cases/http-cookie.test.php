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
        return [[''], [",MyName"], [";MyName"], [" MyName"], ["\tMyName"], ["\rMyName"], ["\nMyName"], ["\013MyName"], ["\014MyName"]];
    }

    /**
     * @dataProvider invalidNames
     * @expectedException InvalidArgumentException
     */
    public function testInstantiationThrowsExceptionIfCookieNameContainsInvalidCharacters($name)
    {
        new Cookie($name);
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testInvalidExpiration()
    {
        $cookie = new Cookie('MyCookie', 'foo','bar');
    }

    public function testGetValue()
    {
        $value = 'MyValue';
        $cookie = new Cookie('MyCookie', $value);
        $this->assertSame($value, $cookie->getValue(), '->getValue() returns the proper value');
    }

    public function testGetPath()
    {
        $cookie = new Cookie('foo', 'bar');
        $this->assertSame('/', $cookie->getPath(), '->getPath() returns / as the default path');
    }

    public function testGetExpiresTime()
    {
        $cookie = new Cookie('foo', 'bar', 3600);
        $this->assertEquals(3600, $cookie->getExpiresTime(), '->getExpiresTime() returns the expire date');
    }

    public function testGetDomain()
    {
        $cookie = new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com');
        $this->assertEquals('.myfoodomain.com', $cookie->getDomain(), '->getDomain() returns the domain name on which the cookie is valid');
    }

    public function testIsSecure()
    {
        $cookie = new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com', true);
        $this->assertTrue($cookie->isSecure(), '->isSecure() returns whether the cookie is transmitted over HTTPS');
    }

    public function testIsHttpOnly()
    {
        $cookie = new Cookie('foo', 'bar', 3600, '/', '.myfoodomain.com', false, true);
        $this->assertTrue($cookie->isHttpOnly(), '->isHttpOnly() returns whether the cookie is only transmitted over HTTP');
    }

    public function testCookieIsNotCleared()
    {
        $cookie = new Cookie('foo', 'bar', time()+3600*24);
        $this->assertFalse($cookie->isCleared(), '->isCleared() returns false if the cookie did not expire yet');
    }

    public function testCookieIsCleared()
    {
        $cookie = new Cookie('foo', 'bar', time()-20);
        $this->assertTrue($cookie->isCleared(), '->isCleared() returns true if the cookie has expired');
    }

    public function testToString()
    {
        $cookie = new Cookie('foo', 'bar', strtotime('Fri, 20-May-2011 15:25:52 GMT'), '/', '.myfoodomain.com', true);
        $this->assertEquals(
            'foo=bar; expires=Fri, 20-May-2011 15:25:52 GMT; samesite=lax; domain=.myfoodomain.com; secure; httponly',
            $cookie->__toString(),
            '->__toString() returns string representation of the cookie'
        );

        $cookie = new Cookie('foo', null, 1, '/admin/', '.myfoodomain.com');
        $this->assertEquals(
            'foo=deleted; expires=' . gmdate("D, d-M-Y H:i:s T", time()-31536001) . '; path=/admin/; samesite=lax; domain=.myfoodomain.com; httponly',
            $cookie->__toString(),
            '->__toString() returns string representation of a cleared cookie if value is NULL'
        );
    }
}
