<?php

defined('DS') or exit('No direct access.');

use System\Request;
use System\Session;

class RequestTest extends \PHPUnit_Framework_TestCase
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
        $_POST = [];

        $scriptname = $_SERVER['SCRIPT_NAME'];

        $_SERVER = [];

        $_SERVER['SCRIPT_NAME'] = $scriptname;

        Request::$route = null;
        Session::$instance = null;
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
     * Helper: set value di $_POST.
     *
     * @param string $key
     * @param string $value
     */
    protected function setPostVar($key, $value)
    {
        $_POST[$key] = $value;

        $this->restartRequest();
    }

    /**
     * Helper: inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
    }

    /**
     * Test untuk method Request::method().
     *
     * @group system
     */
    public function testMethodReturnsTheHTTPRequestMethod()
    {
        $this->setServerVar('REQUEST_METHOD', 'POST');
        $this->assertEquals('POST', Request::method());

        $this->setPostVar(Request::SPOOFER, 'PUT');
        $this->assertEquals('PUT', Request::method());
    }

    /**
     * Test untuk method Request::server().
     *
     * @group system
     */
    public function testServerMethodReturnsFromServerArray()
    {
        $this->setServerVar('TEST', 'something');
        $this->setServerVar('USER', ['NAME' => 'budi']);

        $this->assertEquals('something', Request::server('test'));
        $this->assertEquals('budi', Request::server('user.name'));
    }

    /**
     * Test untuk method Request::ip().
     *
     * @group system
     */
    public function testIPMethodReturnsClientIPAddress()
    {
        $this->setServerVar('REMOTE_ADDR', 'something');
        $this->assertEquals('something', Request::ip());

        $this->setServerVar('HTTP_CLIENT_IP', 'something');
        $this->assertEquals('something', Request::ip());

        $this->setServerVar('HTTP_CLIENT_IP', 'something');
        $this->assertEquals('something', Request::ip());

        $scriptname = $_SERVER['SCRIPT_NAME'];
        $_SERVER = [];
        $_SERVER['SCRIPT_NAME'] = $scriptname;

        $this->restartRequest();
        $this->assertEquals('0.0.0.0', Request::ip());
    }

    /**
     * Test untuk method Request::secure().
     *
     * @group system
     */
    public function testSecureMethodsIndicatesIfHTTPS()
    {
        $this->setServerVar('HTTPS', 'on');
        $this->assertTrue(Request::secure());

        $this->setServerVar('HTTPS', 'off');
        $this->assertFalse(Request::secure());
    }

    /**
     * Test untuk method Request::ajax().
     *
     * @group system
     */
    public function testAjaxMethodIndicatesWhenAjax()
    {
        $this->assertFalse(Request::ajax());

        $this->setServerVar('HTTP_X_REQUESTED_WITH', 'XMLHttpRequest');
        $this->assertTrue(Request::ajax());
    }

    /**
     * Test untuk method Request::forged().
     *
     * @group system
     */
    public function testForgedMethodIndicatesIfRequestWasForged()
    {
        Session::$instance = new SessionPayloadTokenStub();

        $input = [Session::TOKEN => 'Foo'];
        Request::foundation()->request->add($input);

        $this->assertFalse(Request::forged());
    }

    /**
     * Test untuk method Request::route().
     *
     * @group system
     */
    public function testRouteMethodReturnsStaticRoute()
    {
        Request::$route = 'Budi';

        $this->assertEquals('Budi', Request::route());
    }
}

class SessionPayloadTokenStub
{
    public function token()
    {
        return 'Budi';
    }
}
