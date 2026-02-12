<?php

defined('DS') or exit('No direct access.');

use System\Response;

class ResponseTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Test for Response::make().
     *
     * @group system
     */
    public function testMakeMethodProperlySetsContent()
    {
        $response = Response::make('foo', 201, ['bar' => 'baz']);

        $this->assertEquals('foo', $response->content);
        $this->assertEquals(201, $response->status());
        $this->assertArrayHasKey('Bar', $response->headers()->all());
        $this->assertTrue($response->headers()->has('bar'));
        $this->assertTrue($response->headers()->has('Bar'));
        $this->assertTrue($response->headers()->has('bAr'));
        $this->assertEquals('baz', $response->headers()->get('bar'));
    }

    /**
     * Test for Response::view().
     *
     * @group system
     */
    public function testViewMethodSetsContentToView()
    {
        $response = Response::view('home.index', ['name' => 'Budi']);

        $this->assertEquals('home.index', $response->content->view);
        $this->assertEquals('Budi', $response->content->data['name']);
    }

    /**
     * Test for Response::error().
     *
     * @group system
     */
    public function testErrorMethodSetsContentToErrorView()
    {
        $response = Response::error(404);

        $this->assertEquals(404, $response->status());
        $this->assertEquals('Not Found', $response->content->data['message']);
    }

    /**
     * Test for Response::prepare().
     *
     * @group system
     */
    public function testPrepareMethodCreatesAResponseInstanceFromGivenValue()
    {
        $response = Response::prepare('Budi');

        $this->assertInstanceOf('\System\Response', $response);
        $this->assertEquals('Budi', $response->content);

        $response = Response::prepare(new Response('Budi'));

        $this->assertInstanceOf('\System\Response', $response);
        $this->assertEquals('Budi', $response->content);
    }

    /**
     * Test for Response::header().
     *
     * @group system
     */
    public function testHeaderMethodSetsValueInHeaderArray()
    {
        $response = Response::make('')->header('Foo', 'bar');

        $this->assertEquals('bar', $response->headers()->get('Foo'));
        $this->assertEquals('bar', $response->headers()->get('foo'));
        $this->assertEquals('bar', $response->headers()->get('FoO'));
    }

    /**
     * Test for Response::status().
     *
     * @group system
     */
    public function testStatusMethodSetsStatusCode()
    {
        $response = Response::make('')->status(404);

        $this->assertEquals(404, $response->status());
    }

    /**
     * Test for Response::json().
     *
     * @group system
     */
    public function testJsonMethodCreatesJsonResponse()
    {
        $data = ['name' => 'Budi'];
        $response = Response::json($data);

        $this->assertEquals('application/json; charset=utf-8', $response->headers()->get('Content-Type'));
        $this->assertEquals(json_encode($data), $response->content);
    }

    /**
     * Test for Response::jsonp().
     *
     * @group system
     */
    public function testJsonpMethodCreatesJsonpResponse()
    {
        $data = ['name' => 'Budi'];
        $response = Response::jsonp('callback', $data);

        $this->assertEquals('application/javascript; charset=utf-8', $response->headers()->get('Content-Type'));
        $this->assertEquals('callback(' . json_encode($data) . ');', $response->content);
    }

    /**
     * Test for Response::render().
     *
     * @group system
     */
    public function testRenderMethodRendersContentToString()
    {
        $response = Response::make('test');
        $this->assertEquals('test', $response->render());
    }

    /**
     * Test for Response::with_headers().
     *
     * @group system
     */
    public function testWithHeadersMethodSetsMultipleHeaders()
    {
        $response = Response::make('')->with_headers(['Foo' => 'bar', 'Baz' => 'qux']);

        $this->assertEquals('bar', $response->headers()->get('Foo'));
        $this->assertEquals('qux', $response->headers()->get('Baz'));
    }

    /**
     * Test for Response::with_cookie().
     *
     * @group system
     */
    public function testWithCookieMethodSetsCookie()
    {
        $response = Response::make('')->with_cookie('test', 'value');
        $this->assertInstanceOf('\System\Response', $response);
    }

    /**
     * Test for Response::with_status_code().
     *
     * @group system
     */
    public function testWithStatusCodeMethodSetsStatus()
    {
        $response = Response::make('')->with_status_code(404);
        $this->assertEquals(404, $response->status());
    }

    /**
     * Test for Response::__toString().
     *
     * @group system
     */
    public function testToStringMethodRendersResponse()
    {
        $response = Response::make('test');
        $this->assertEquals('test', (string) $response);
    }

    /**
     * Test for Response::foundation().
     *
     * @group system
     */
    public function testFoundationMethodReturnsFoundationInstance()
    {
        $response = Response::make('test');
        $this->assertInstanceOf('\System\Foundation\Http\Response', $response->foundation());
    }
}
