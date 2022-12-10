<?php

defined('DS') or exit('No direct script access.');

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
    }

    /**
     * Test untuk method Router::find().
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
     * Test untuk mekanisme routing dasar.
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
     * Test bahwa router dapat menangani wildcard dasar.
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
     * Test bahwa wildcard opsional juga bisa ditangani dengan benar.
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
     * Test bahwa conntroller dasar bisa bekerja dengan benar.
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
     * Test bahwa routing dasar untuk paket dapat ditangani dengan benar.
     *
     * @group system
     */
    public function testRoutesToPackagesCanBeResolved()
    {
        $this->assertNull(Router::route('GET', 'dashboard/foo'));
        $this->assertEquals('dashboard', Router::route('GET', 'dashboard')->uri);
    }

    /**
     * Test bahwa controller milik paket dapat ditemukan dengan benar.
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
     * Test bahwa karakter asing juga bisa digunakan untuk routing.
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
}
