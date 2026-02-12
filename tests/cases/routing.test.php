<?php

defined('DS') or exit('No direct access.');

use System\Package;
use System\Routing\Route;
use System\Routing\Router;

class RoutingTest extends \PHPUnit_Framework_TestCase
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
        Router::$nodes = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Package::$booted = [];
        Package::$routed = [];
        Router::$names = [];
        Router::$routes = [];
        Router::$fallback = [];
        Router::$uses = [];
        Router::$nodes = [];
    }

    /**
     * Test for Router::find().
     *
     * @group system
     */
    public function testNamedRoutesCanBeLocatedByTheRouter()
    {
        Route::get('/', ['as' => 'home']);
        Route::get('dashboard', ['as' => 'dashboard']);

        $home = Router::find('home');
        $dashboard = Router::find('dashboard');

        $this->assertTrue(isset($home['/']));
        $this->assertTrue(isset($dashboard['dashboard']));
    }

    /**
     * Test for basic routing.
     *
     * @group system
     */
    public function testBasicRouteCanBeRouted()
    {
        Route::get('/', function () {
            return 'home';
        });

        Route::get('home, main', function () {
            return 'home, main';
        });

        $this->assertEquals('/', Router::route('GET', '/')->uri);
        $this->assertEquals('home', Router::route('GET', 'home')->uri);
        $this->assertEquals('main', Router::route('GET', 'main')->uri);
    }

    /**
     * Test that wildcard routes can be handled correctly.
     *
     * @group system
     */
    public function testWildcardRoutesCanBeRouted()
    {
        Route::get('user/(:num)', function ($parameters) {
            return 'foo';
        });

        Route::get('profile/(:any)/(:num)', function ($parameters) {
            return 'bar';
        });

        $this->assertNull(Router::route('GET', 'user/1.5'));
        $this->assertNull(Router::route('GET', 'user/budi'));

        $this->assertEquals([25], Router::route('GET', 'user/25')->parameters);
        $this->assertEquals('user/(:num)', Router::route('GET', 'user/1')->uri);

        $this->assertNull(Router::route('GET', 'profile/1/purnomo'));
        $this->assertNull(Router::route('POST', 'profile/budi/1'));
        $this->assertNull(Router::route('GET', 'profile/budi/purnomo'));
        $this->assertNull(Router::route('GET', 'profile/budi/1/purnomo'));

        $this->assertEquals(['budi', 25], Router::route('GET', 'profile/budi/25')->parameters);
        $this->assertEquals('profile/(:any)/(:num)', Router::route('GET', 'profile/budi/1')->uri);
    }

    /**
     * Test that optional wildcard routes can be handled correctly.
     *
     * @group system
     */
    public function testOptionalWildcardsCanBeRouted()
    {
        Route::get('user/(:num?)', function ($parameters) {
            return 'foo';
        });
        Route::get('profile/(:any)/(:any?)', function ($parameters) {
            return 'bar';
        });

        $this->assertNull(Router::route('GET', 'user/budi'));
        $this->assertEquals('user/(:num?)', Router::route('GET', 'user')->uri);
        $this->assertEquals([25], Router::route('GET', 'user/25')->parameters);
        $this->assertEquals('user/(:num?)', Router::route('GET', 'user/1')->uri);

        $this->assertNull(Router::route('GET', 'profile/budi/purnomo/test'));
        $this->assertEquals('profile/(:any)/(:any?)', Router::route('GET', 'profile/budi')->uri);
        $this->assertEquals('profile/(:any)/(:any?)', Router::route('GET', 'profile/budi/25')->uri);
        $this->assertEquals('profile/(:any)/(:any?)', Router::route('GET', 'profile/budi/purnomo')->uri);
        $this->assertEquals(['budi', 'purnomo'], Router::route('GET', 'profile/budi/purnomo')->parameters);
    }

    /**
     * Test that basic route to controller can be handled correctly.
     *
     * @group system
     */
    public function testBasicRouteToControllerIsRouted()
    {
        $this->assertEquals('auth@(:1)', Router::route('GET', 'auth')->action['uses']);
        $this->assertEquals('home@(:1)', Router::route('GET', 'home/index')->action['uses']);
        $this->assertEquals('home@(:1)', Router::route('GET', 'home/profile')->action['uses']);
        $this->assertEquals('admin.panel@(:1)', Router::route('GET', 'admin/panel')->action['uses']);
        $this->assertEquals('admin.panel@(:1)', Router::route('GET', 'admin/panel/show')->action['uses']);
    }

    /**
     * Test that routes to packages can be resolved correctly.
     *
     * @group system
     */
    public function testRoutesToPackagesCanBeResolved()
    {
        $this->assertNull(Router::route('GET', 'dashboard/foo'));
        $this->assertEquals('dashboard', Router::route('GET', 'dashboard')->uri);
    }

    /**
     * Test that package controllers can be resolved correctly.
     *
     * @group system
     */
    public function testPackageControllersCanBeResolved()
    {
        $uses = Router::route('GET', 'dashboard/panel')->action['uses'];
        $this->assertEquals('dashboard::panel@(:1)', $uses);

        $uses = Router::route('GET', 'dashboard/panel/show')->action['uses'];
        $this->assertEquals('dashboard::panel@(:1)', $uses);
    }

    /**
     * Test that foreign characters in routes are handled correctly.
     *
     * @group system
     */
    public function testForeignCharsInRoutes()
    {
        Route::get(urlencode('مدرس_رياضيات') . '/(:any)', function () {
            return 'foo';
        });

        Route::get(urlencode('مدرس_رياضيات'), function () {
            return 'bar';
        });

        Route::get(urlencode('ÇœŪ'), function () {
            return 'baz';
        });

        Route::get(urlencode('私は料理が大好き'), function () {
            return 'qux';
        });

        $test = Router::route('GET', urlencode('مدرس_رياضيات') . '/' . urlencode('مدرس_رياضيات'))->parameters;
        $this->assertEquals([urlencode('مدرس_رياضيات')], $test);

        $test = Router::route('GET', urlencode('مدرس_رياضيات'))->uri;
        $this->assertEquals(urlencode('مدرس_رياضيات'), $test);

        $test = Router::route('GET', urlencode('ÇœŪ'))->uri;
        $this->assertEquals(urlencode('ÇœŪ'), $test);

        $test = Router::route('GET', urlencode('私は料理が大好き'))->uri;
        $this->assertEquals(urlencode('私は料理が大好き'), $test);
    }

    /**
     * Test that subdomain routes can be handled correctly.
     *
     * @group system
     */
    public function testSubdomainRoutesCanBeRouted()
    {
        Route::domain('api', function () {
            Route::get('api', function () {
                return 'api home';
            });
            Route::get('api/users', function () {
                return 'api users';
            });
        });

        Route::domain('{subdomain}.example.com', function () {
            Route::get('sub', function () {
                return 'subdomain home';
            });
        });

        // Test exact domain match
        $route = Router::route('GET', 'api', 'api');
        $this->assertEquals('api', $route->uri);
        $this->assertEquals('api', $route->action['domain']);

        $route = Router::route('GET', 'api/users', 'api');
        $this->assertEquals('api/users', $route->uri);
        $this->assertEquals('api', $route->action['domain']);

        // Test pattern domain match
        $route = Router::route('GET', 'sub', 'sub.example.com');
        $this->assertEquals('sub', $route->uri);
        $this->assertEquals('{subdomain}.example.com', $route->action['domain']);

        // Test no match
        $this->assertNull(Router::route('GET', 'api', 'other'));
        $this->assertNull(Router::route('GET', 'api/users', 'other'));
        $this->assertNull(Router::route('GET', 'sub', 'other'));
    }

    /**
     * Test that prefix routes can be handled correctly.
     *
     * @group system
     */
    public function testPrefixRoutesCanBeRouted()
    {
        Route::group(['prefix' => 'api'], function () {
            Route::get('/', function () {
                return 'api home';
            });
            Route::get('users', function () {
                return 'api users';
            });
        });

        Route::group(['prefix' => 'admin/v1'], function () {
            Route::get('dashboard', function () {
                return 'admin dashboard';
            });
        });

        // Test prefix route
        $route = Router::route('GET', 'api/');
        $this->assertEquals('api', $route->uri);
        $this->assertEquals('api home', $route->response());

        $route = Router::route('GET', 'api/users');
        $this->assertEquals('api/users', $route->uri);
        $this->assertEquals('api users', $route->response());

        // Test nested prefix
        $route = Router::route('GET', 'admin/v1/dashboard');
        $this->assertEquals('admin/v1/dashboard', $route->uri);
        $this->assertEquals('admin dashboard', $route->response());

        // Test no match
        $this->assertNull(Router::route('GET', 'users'));
    }

    /**
     * Test that Router::find returns null for non-existent routes.
     *
     * @group system
     */
    public function testFindReturnsNullForNonExistentRoute()
    {
        $this->assertNull(Router::find('nonexistent'));
    }
}
