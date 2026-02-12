<?php

defined('DS') or exit('No direct access.');

use System\Request;
use System\Routing\Route;
use System\Routing\Middleware;
use System\URL;
use System\Config;

class RouteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        URL::$base = 'http://localhost/';
        Config::set('application.index', '');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Request::$route = null;
        URL::$base = '';
        Config::set('application.index', 'index.php');
    }

    /**
     * Tear down after class.
     */
    public static function tearDownAfterClass()
    {
        unset(
            $_SERVER['REQUEST_METHOD'],
            Middleware::$middlewares['test-after'],
            Middleware::$middlewares['test-before'],
            Middleware::$middlewares['test-params'],
            Middleware::$middlewares['test-multi-1'],
            Middleware::$middlewares['test-multi-2']
        );
    }

    /**
     * Test for Route::is().
     *
     * @group system
     */
    public function testIsMethodIndicatesIfTheRouteHasAGivenName()
    {
        $route = new Route('GET', '/', ['as' => 'profile']);
        $this->assertTrue($route->is('profile'));
        $this->assertFalse($route->is('something'));
    }

    /**
     * Test for Route::call().
     *
     * @group system
     */
    public function testBasicRoutesCanBeExecutedProperly()
    {
        $route = new Route('GET', '', [function () {
            return 'Route!';
        }]);

        $this->assertEquals('Route!', $route->call()->content);
        $this->assertInstanceOf('\System\Response', $route->call());
    }

    /**
     * Test that route parameters are passed into the handler.
     *
     * @group system
     */
    public function testRouteParametersArePassedIntoTheHandler()
    {
        $route = new Route('GET', '', [function ($var) {
            return $var;
        }], ['Budi']);

        $this->assertEquals('Budi', $route->call()->content);
        $this->assertInstanceOf('\System\Response', $route->call());
    }

    /**
     * Test that global 'before' and 'after' middlewares are automatically called when a route is executed.
     *
     * @group system
     */
    public function testCallingARouteCallsTheBeforeAndAfterMiddlewares()
    {
        $route = new Route('GET', '', [function () {
            $_SERVER['before'] = true;
            $_SERVER['after'] = true;
            return 'Hi!';
        }]);

        unset($_SERVER['before'], $_SERVER['after']);

        $route->call();

        $this->assertTrue($_SERVER['before']);
        $this->assertTrue($_SERVER['after']);

        unset($_SERVER['before'], $_SERVER['after']);
    }

    /**
     * Test that 'before' middleware can override the route response.
     *
     * @group system
     */
    public function testBeforeMiddlewaresOverrideTheRouteResponse()
    {
        Middleware::register('test-before', function () {
            return 'Middleware OK!';
        });

        $route = new Route('GET', '', ['before' => 'test-before', function () {
            return 'Route!';
        }]);

        $this->assertEquals('Middleware OK!', $route->call()->content);
    }

    /**
     * Test that 'after' middleware does not affect the route response.
     *
     * @group system
     */
    public function testAfterMiddlewareDoesNotAffectTheResponse()
    {
        $_SERVER['test-after'] = false;

        Middleware::register('test-after', function () {
            $_SERVER['test-after'] = true;

            return 'Middleware OK!';
        });

        $route = new Route('GET', '', ['after' => 'test-after', function () {
            return 'Route!';
        }]);

        $this->assertEquals('Route!', $route->call()->content);
        $this->assertTrue($_SERVER['test-after']);
    }

    /**
     * Test that controller action is called when delegating.
     *
     * @group system
     */
    public function testControllerActionCalledWhenDelegating()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $route = new Route('GET', '', ['uses' => 'auth@index']);
        $this->assertEquals('action_index', $route->call()->content);
    }

    /**
     * Test that middleware parameters are passed to the middleware.
     *
     * @group system
     */
    public function testMiddlewareParametersArePassedToMiddleware()
    {
        Middleware::register('test-params', function ($var1, $var2) {
            return $var1 . $var2;
        });

        $route = new Route('GET', '', ['before' => 'test-params:1,2']);
        $this->assertEquals('12', $route->call()->content);
    }

    /**
     * Test that multiple middlewares can be assigned to a route.
     *
     * @group system
     */
    public function testMultipleMiddlewaresCanBeAssignedToARoute()
    {
        $_SERVER['test-multi-1'] = false;
        $_SERVER['test-multi-2'] = false;

        Middleware::register('test-multi-1', function () {
            $_SERVER['test-multi-1'] = true;
        });

        Middleware::register('test-multi-2', function () {
            $_SERVER['test-multi-2'] = true;
        });

        $route = new Route('GET', '', ['before' => 'test-multi-1|test-multi-2']);

        $route->call();

        $this->assertTrue($_SERVER['test-multi-1']);
        $this->assertTrue($_SERVER['test-multi-2']);
    }

    /**
     * Test for Route::has().
     *
     * @group system
     */
    public function testHasMethodChecksIfNamedRouteExists()
    {
        Route::get('/', ['as' => 'home']);

        $this->assertTrue(Route::has('home'));
        $this->assertFalse(Route::has('nonexistent'));
    }

    /**
     * Test for Route::view().
     *
     * @group system
     */
    public function testViewMethodRegistersViewRoute()
    {
        Route::view('welcome', 'home.index', ['name' => 'Budi']);

        $route = Router::route('GET', 'welcome');

        $this->assertEquals('home.index', $route->response()->view);
        $this->assertEquals('Budi', $route->response()->data['name']);
    }

    /**
     * Test for Route::redirect().
     *
     * @group system
     */
    public function testRedirectMethodRegistersRedirectRoute()
    {
        Route::redirect('old', 'new', 301);

        $route = Router::route('GET', 'old');
        $response = $route->call();

        $this->assertEquals(301, $response->status());
        $this->assertEquals('http://localhost/new', $response->headers()->get('location'));
    }
}
