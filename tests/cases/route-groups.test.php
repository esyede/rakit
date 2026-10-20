<?php

defined('DS') or exit('No direct access.');

use System\URL;
use System\Routing\Middleware;
use System\Routing\Route;
use System\Routing\Router;

class RouteGroupsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The middlewares that ran, in order.
     *
     * @var array
     */
    public static $ran = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        static::$ran = [];

        foreach (['rg_a', 'rg_b', 'rg_c'] as $name) {
            Route::middleware($name, function () use ($name) {
                RouteGroupsTest::$ran[] = $name;
            });
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach (Router::$routes as $method => $routes) {
            foreach (array_keys($routes) as $uri) {
                if (0 === strpos($uri, 'rg/')) {
                    unset(Router::$routes[$method][$uri]);
                }
            }
        }

        foreach (['rg_a', 'rg_b', 'rg_c'] as $name) {
            unset(Middleware::$middlewares[$name]);
        }

        Middleware::$groups = [];
    }

    /**
     * A route's own middleware runs after the group's, never instead of it.
     *
     * @group system
     */
    public function testRouteMiddlewareAddsToTheGroups()
    {
        Route::group(['before' => 'rg_a'], function () {
            Route::get('rg/both', ['before' => 'rg_b', function () {
                return 'ok';
            }]);

            Route::get('rg/group-only', function () {
                return 'ok';
            });
        });

        $this->assertEquals('rg_a|rg_b', Router::$routes['GET']['rg/both']['before']);

        Router::route('GET', 'rg/both')->call();
        $this->assertSame(['rg_a', 'rg_b'], static::$ran);

        static::$ran = [];
        Router::route('GET', 'rg/group-only')->call();
        $this->assertSame(['rg_a'], static::$ran);
    }

    /**
     * A group's 'as' prefixes the names of its routes, through nested groups, and
     * leaves unnamed routes unnamed.
     *
     * @group system
     */
    public function testGroupNamePrefix()
    {
        Route::group(['prefix' => 'rg/admin', 'as' => 'rg.admin.'], function () {
            Route::get('users', ['as' => 'users', function () {
            }]);

            Route::get('unnamed', function () {
            });

            Route::group(['prefix' => 'system', 'as' => 'system.'], function () {
                Route::get('logs', ['as' => 'logs', function () {
                }]);
            });
        });

        $this->assertEquals('rg.admin.users', Router::$routes['GET']['rg/admin/users']['as']);
        $this->assertEquals('rg.admin.system.logs', Router::$routes['GET']['rg/admin/system/logs']['as']);
        $this->assertArrayNotHasKey('as', Router::$routes['GET']['rg/admin/unnamed']);
        $this->assertStringEndsWith('/rg/admin/system/logs', URL::to_route('rg.admin.system.logs'));
    }

    /**
     * A middleware group runs its middlewares, nested groups included; a group that
     * includes itself is refused.
     *
     * @group system
     */
    public function testMiddlewareGroups()
    {
        Route::middleware_group('rg_inner', 'rg_b|rg_c');
        Route::middleware_group('rg_web', ['rg_a', 'rg_inner']);

        Route::get('rg/web', ['before' => 'rg_web', function () {
            return 'ok';
        }]);

        Router::route('GET', 'rg/web')->call();
        $this->assertSame(['rg_a', 'rg_b', 'rg_c'], static::$ran);

        Route::middleware_group('rg_loop', 'rg_a|rg_loop');

        try {
            Middleware::parse('rg_loop');
            $this->fail('A self-including group was accepted');
        } catch (\Exception $e) {
            $this->assertContains('includes itself', $e->getMessage());
        }
    }

    /**
     * api_resource() leaves out the HTML form routes; except adds to that.
     *
     * @group system
     */
    public function testApiResource()
    {
        Route::api_resource('rg/photos');

        $this->assertArrayHasKey('rg/photos', Router::$routes['GET']);
        $this->assertArrayHasKey('rg/photos', Router::$routes['POST']);
        $this->assertArrayHasKey('rg/photos/(:any)', Router::$routes['GET']);
        $this->assertArrayHasKey('rg/photos/(:any)', Router::$routes['PUT']);
        $this->assertArrayHasKey('rg/photos/(:any)', Router::$routes['DELETE']);
        $this->assertArrayNotHasKey('rg/photos/create', Router::$routes['GET']);
        $this->assertArrayNotHasKey('rg/photos/(:any)/edit', Router::$routes['GET']);

        Route::api_resource('rg/tags', ['except' => ['delete']]);
        $this->assertArrayNotHasKey('rg/tags/(:any)', Router::$routes['DELETE']);
        $this->assertArrayNotHasKey('rg/tags/create', Router::$routes['GET']);
    }

    /**
     * singleton() registers show, edit and update, with no ID in the URIs.
     *
     * @group system
     */
    public function testSingleton()
    {
        Route::group(['as' => 'rg.'], function () {
            Route::singleton('rg/profile');
        });

        $this->assertEquals('rg.rg/profile.show', Router::$routes['GET']['rg/profile']['as']);
        $this->assertEquals('rg/profile@show', Router::$routes['GET']['rg/profile']['uses']);
        $this->assertEquals('rg/profile@edit', Router::$routes['GET']['rg/profile/edit']['uses']);
        $this->assertEquals('rg/profile@update', Router::$routes['PUT']['rg/profile']['uses']);
        $this->assertArrayNotHasKey('rg/profile', Router::$routes['POST']);

        Route::singleton('rg/settings', ['only' => ['show']]);
        $this->assertArrayHasKey('rg/settings', Router::$routes['GET']);
        $this->assertArrayNotHasKey('rg/settings/edit', Router::$routes['GET']);
    }

    /**
     * A name looked up in vain is remembered, until a route is added.
     *
     * @group system
     */
    public function testFindRemembersMissesUntilARouteIsAdded()
    {
        $this->assertNull(Router::find('rg.later'));
        $this->assertNull(Router::find('rg.later'));
        $this->assertNull(Router::uses('rg_later@index'));

        Route::get('rg/later', ['as' => 'rg.later', 'uses' => 'rg_later@index']);

        $this->assertArrayHasKey('rg/later', Router::find('rg.later'));
        $this->assertArrayHasKey('rg/later', Router::uses('rg_later@index'));
    }

    /**
     * The literal prefix filter keeps optional wildcards and leading wildcards working.
     *
     * @group system
     */
    public function testPrefixFilterKeepsWildcardsMatching()
    {
        Route::get('rg/opt/(:num?)', function ($id = null) {
            return 'opt:'.$id;
        });

        Route::get('(:any)/rg-tail', function ($head) {
            return 'tail:'.$head;
        });

        $this->assertEquals('opt:', Router::route('GET', 'rg/opt')->call()->content);
        $this->assertEquals('opt:5', Router::route('GET', 'rg/opt/5')->call()->content);
        $this->assertEquals('tail:x', Router::route('GET', 'x/rg-tail')->call()->content);

        unset(Router::$fallback['GET']['(:any)/rg-tail']);
    }
}
