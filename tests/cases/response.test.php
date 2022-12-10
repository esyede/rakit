<?php

defined('DS') or exit('No direct script access.');

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
     * Test untuk method Response::make().
     *
     * @group system
     */
    public function testMakeMethodProperlySetsContent()
    {
        $response = Response::make('foo', 201, ['bar' => 'baz']);

        $this->assertEquals('foo', $response->content);
        $this->assertEquals(201, $response->status());
        $this->assertArrayHasKey('bar', $response->headers()->all());
        $this->assertEquals('baz', $response->headers()->get('bar'));
    }

    /**
     * Test untuk method Response::view().
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
     * Test untuk method Response::error().
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
     * Test untuk method Response::prepare().
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
     * Test untuk method Response::header().
     *
     * @group system
     */
    public function testHeaderMethodSetsValueInHeaderArray()
    {
        $response = Response::make('')->header('foo', 'bar');

        $this->assertEquals('bar', $response->headers()->get('foo'));
    }

    /**
     * Test untuk method Response::status().
     *
     * @group system
     */
    public function testStatusMethodSetsStatusCode()
    {
        $response = Response::make('')->status(404);

        $this->assertEquals(404, $response->status());
    }
}
