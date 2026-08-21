<?php

defined('DS') or exit('No direct access.');

use System\Package;
use System\Routing\Router;
use System\Routing\Resource;

class RoutingResourceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Package::$booted = [];
        Package::$routed = [];
        Router::$names = [];
        Router::$routes = [];
        Router::$fallback = [];
        Router::$uses = [];
        Router::$group = null;
        Router::$groups = [];
        Router::$domains = false;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->setUp();
    }

    /**
     * Collect the registered routes as 'METHOD uri' => action pairs.
     *
     * @return array
     */
    protected function registered()
    {
        $result = [];

        foreach (Router::routes() as $method => $routes) {
            foreach ($routes as $uri => $action) {
                $result[$method . ' ' . $uri] = $action;
            }
        }

        return $result;
    }

    /**
     * Test that the seven default resource routes are registered.
     *
     * @group system
     */
    public function testDefaultResourceRoutes()
    {
        Resource::make('photos');

        $routes = $this->registered();

        $this->assertArrayHasKey('GET photos', $routes);
        $this->assertArrayHasKey('GET photos/create', $routes);
        $this->assertArrayHasKey('POST photos', $routes);
        $this->assertArrayHasKey('GET photos/(:any)', $routes);
        $this->assertArrayHasKey('GET photos/(:any)/edit', $routes);
        $this->assertArrayHasKey('PUT photos/(:any)', $routes);
        $this->assertArrayHasKey('DELETE photos/(:any)', $routes);
    }

    /**
     * Test the action and the name assigned to each resource route.
     *
     * @group system
     */
    public function testResourceRouteActionsAndNames()
    {
        Resource::make('photos');

        $routes = $this->registered();

        $this->assertEquals('photos@index', $routes['GET photos']['uses']);
        $this->assertEquals('photos.index', $routes['GET photos']['as']);

        $this->assertEquals('photos@create', $routes['GET photos/create']['uses']);
        $this->assertEquals('photos.create', $routes['GET photos/create']['as']);

        $this->assertEquals('photos@store', $routes['POST photos']['uses']);
        $this->assertEquals('photos.store', $routes['POST photos']['as']);

        $this->assertEquals('photos@show', $routes['GET photos/(:any)']['uses']);
        $this->assertEquals('photos.show', $routes['GET photos/(:any)']['as']);

        $this->assertEquals('photos@edit', $routes['GET photos/(:any)/edit']['uses']);
        $this->assertEquals('photos.edit', $routes['GET photos/(:any)/edit']['as']);

        $this->assertEquals('photos@update', $routes['PUT photos/(:any)']['uses']);
        $this->assertEquals('photos.update', $routes['PUT photos/(:any)']['as']);

        $this->assertEquals('photos@delete', $routes['DELETE photos/(:any)']['uses']);
        $this->assertEquals('photos.delete', $routes['DELETE photos/(:any)']['as']);
    }

    /**
     * A nested resource is prefixed with its parent.
     *
     * @group system
     */
    public function testNestedResource()
    {
        Resource::make('albums.photos');

        $routes = $this->registered();

        $this->assertArrayHasKey('GET albums/(:any?)/photos', $routes);
        $this->assertArrayHasKey('GET albums/(:any?)/photos/(:any)', $routes);

        $this->assertEquals('albums.photos@index', $routes['GET albums/(:any?)/photos']['uses']);
        $this->assertEquals('albums.photos.index', $routes['GET albums/(:any?)/photos']['as']);
    }

    /**
     * Only the given subset of routes is registered when options are passed.
     *
     * @group system
     */
    public function testResourceWithCustomOptions()
    {
        Resource::make('photos', [
            [
                'method' => 'get',
                'route' => '',
                'as' => ':name.index',
                'uses' => ':name@index',
            ],
            [
                'method' => 'delete',
                'route' => '/(:any)',
                'as' => ':name.delete',
                'uses' => ':name@delete',
            ],
        ]);

        $routes = $this->registered();

        $this->assertArrayHasKey('GET photos', $routes);
        $this->assertArrayHasKey('DELETE photos/(:any)', $routes);
        $this->assertArrayNotHasKey('POST photos', $routes);
        $this->assertArrayNotHasKey('GET photos/create', $routes);
    }

    /**
     * An unsupported HTTP method is refused.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testResourceRejectsUnsupportedMethod()
    {
        Resource::make('photos', [
            [
                'method' => 'trace',
                'route' => '',
                'as' => ':name.index',
                'uses' => ':name@index',
            ],
        ]);
    }

    /**
     * A resource route must be reachable through the router.
     *
     * @group system
     */
    public function testResourceRoutesAreRoutable()
    {
        Resource::make('photos');

        $route = Router::route('GET', 'photos/42');

        $this->assertInstanceOf('\System\Routing\Route', $route);
        $this->assertEquals('photos@show', $route->action['uses']);
        $this->assertEquals(['42'], $route->parameters);
    }
}
