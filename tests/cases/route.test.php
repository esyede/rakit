<?php

defined('DS') or exit('No direct script access.');

use System\Request;
use System\Routing\Route;
use System\Routing\Middleware;

class RouteTest extends \PHPUnit_Framework_TestCase
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
        Request::$route = null;
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
     * Test untuk method Route::is().
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
     * Test untuk eksekusi route dasar.
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
     * Test bahwa parameter dioper di route bisa ditangkap oleh route handler.
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
     * Test bahwa middleware global 'before' dan 'after' otomatis ikut
     * terpanggil ketika route dijalankan.
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
     * Test bahwa middleware 'before' dapat memanipulasi respon route.
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
     * Test bahwa middleware 'after' tidak boleh mempengaruhi respon route.
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
     * Test bahwa route memanggil controller yang sesuai ketika menggunakan 'uses'.
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
     * Test bahwa parameter yang dioper pada middleware bisa ditangkap dengan benar.
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
     * Test bahwa sebuah route dapat dilampiri lebih dari satu middleware.
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
}
