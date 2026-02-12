<?php

defined('DS') or exit('No direct access.');

use System\URI;
use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

class URITest extends \PHPUnit_Framework_TestCase
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
        unset($_SERVER['REQUEST_URI']);

        URI::$uri = null;
        URI::$segments = [];
    }

    /**
     * Test for URI::current().
     *
     * @group system
     *
     * @dataProvider requestUriProvider
     */
    public function testCorrectURIIsReturnedByCurrentMethod($uri, $expectation)
    {
        $this->setRequestUri($uri);
        $this->assertEquals($expectation, URI::current());
    }

    /**
     * Test for URI::segment().
     *
     * @group system
     */
    public function testSegmentMethodReturnsAURISegment()
    {
        $this->setRequestUri('/user/profile');
        $this->assertEquals('user', URI::segment(1));
        $this->assertEquals('profile', URI::segment(2));
    }

    /**
     * Data provider for URI::current().
     */
    public function requestUriProvider()
    {
        return [
            ['/user', 'user'],
            ['/user/', 'user'],
            ['', '/'],
            ['/', '/'],
            ['//', '/'],
            ['/user', 'user'],
            ['/user/', 'user'],
            ['/user/profile', 'user/profile'],
        ];
    }

    /**
     * Helper: set request uri.
     *
     * @param string $uri
     */
    protected function setRequestUri($uri)
    {
        $_FILES = [];
        $_SERVER['REQUEST_URI'] = $uri;

        // Ensure required server variables are set
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        // Reset URI cache
        URI::$uri = null;
        URI::$segments = [];

        Request::$foundation = FoundationRequest::createFromGlobals();
        // Reset cache foundation
        Request::reset_foundation();
    }
}
