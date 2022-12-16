<?php

defined('DS') or exit('No direct script access.');

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
     * Test untuk method URI::current().
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
     * Test untuk method URI::segment().
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
     * Data provider untuk test URI::current().
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

        Request::$foundation = FoundationRequest::createFromGlobals();
    }
}
