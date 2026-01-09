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

        // Pastikan SCRIPT_NAME ada
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        // Pastikan HTTP_HOST ada
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();

        // Reset cache foundation
        Request::reset_foundation();
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
        $this->setServerVar('REMOTE_ADDR', '192.168.1.100');
        $this->assertEquals('192.168.1.100', Request::ip());

        $this->setServerVar('REMOTE_ADDR', '10.0.0.5');
        $this->assertEquals('10.0.0.5', Request::ip());

        $this->setServerVar('REMOTE_ADDR', '172.16.0.1');
        $this->assertEquals('172.16.0.1', Request::ip());

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

        // Set ke POST untuk menjalankan CSRF check
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['SCRIPT_NAME'] = '/index.php';

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
        Request::reset_foundation();

        $input = [Session::TOKEN => 'Budi'];
        Request::foundation()->request->add($input);

        $this->assertFalse(Request::forged());

        // Test untuk forged request
        $input2 = [Session::TOKEN => 'WrongToken'];
        Request::foundation()->request->replace($input2);

        $this->assertTrue(Request::forged());
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

    public function get($key, $default = null)
    {
        if ($key === Session::TOKEN) {
            return 'Budi';
        }

        return $default;
    }
}
