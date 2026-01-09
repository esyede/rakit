<?php

defined('DS') or exit('No direct access.');

use System\URI;
use System\URL;
use System\Input;
use System\Config;
use System\Request;
use System\Routing\Route;
use System\Routing\Router;

class URLTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        URL::$base = null;

        Router::$routes = [];
        Router::$names = [];
        Router::$uses = [];
        Router::$fallback = [];

        Config::set('application.url', 'http://localhost');
        Config::set('application.index', 'index.php');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Router::$routes = [];
        Router::$names = [];
        Router::$uses = [];
        Router::$fallback = [];

        Config::set('application.ssl', true);
        Config::set('application.url', '');
        Config::set('application.index', 'index.php');
    }

    /**
     * Test untuk method URL::to().
     *
     * @group system
     */
    public function testToMethodGeneratesURL()
    {
        $this->assertEquals('http://localhost/index.php/user/profile', URL::to('user/profile'));

        $this->setServerVar('HTTPS', 'on');
        $this->assertEquals('https://localhost/index.php/user/profile', URL::to('user/profile'));
        $this->setServerVar('HTTPS', 'off');

        Config::set('application.index', '');

        $this->assertEquals('http://localhost/user/profile', URL::to('user/profile'));
        $this->setServerVar('HTTPS', 'on');
        $this->assertEquals('https://localhost/user/profile', URL::to('user/profile'));
        $this->setServerVar('HTTPS', 'off');
    }

    /**
     * Test untuk method URL::to_action().
     *
     * @group system
     */
    public function testToActionMethodGeneratesURLToControllerAction()
    {
        Route::get('foo/bar/(:any?)', 'foo@baz');

        $this->assertEquals('http://localhost/index.php/x/y', URL::to_action('x@y'));
        $this->assertEquals('http://localhost/index.php/x/y/Budi', URL::to_action('x@y', ['Budi']));
        $this->assertEquals('http://localhost/index.php/foo/bar', URL::to_action('foo@baz'));
        $this->assertEquals('http://localhost/index.php/foo/bar/Budi', URL::to_action('foo@baz', ['Budi']));
    }

    /**
     * Test untuk method URL::to_asset().
     *
     * @group system
     */
    public function testToAssetGeneratesURLWithoutFrontControllerInURL()
    {
        $this->assertEquals('http://localhost/assets/image.jpg', URL::to_asset('image.jpg'));

        Config::set('application.index', '');
        $this->assertEquals('http://localhost/assets/image.jpg', URL::to_asset('image.jpg'));

        $this->setServerVar('HTTPS', 'on');
        $this->assertEquals('https://localhost/assets/image.jpg', URL::to_asset('image.jpg'));
        $this->setServerVar('HTTPS', 'off');
    }

    /**
     * Test untuk method URL::to_route().
     *
     * @group system
     */
    public function testToRouteMethodGeneratesURLsToRoutes()
    {
        Route::get('url/test', ['as' => 'url-test']);
        Route::get('url/test/(:any)/(:any?)', ['as' => 'url-test-2']);
        Route::get('url/secure/(:any)/(:any?)', ['as' => 'url-test-3', 'https' => true]);

        $out1 = 'http://localhost/index.php/url/test';
        $out2 = 'http://localhost/index.php/url/test/budi';
        $out3 = 'https://localhost/index.php/url/secure/budi';
        $out4 = 'http://localhost/index.php/url/test/budi/purnomo';

        $this->assertEquals($out1, URL::to_route('url-test'));
        $this->assertEquals($out2, URL::to_route('url-test-2', ['budi']));

        $this->setServerVar('HTTPS', 'on');
        $this->assertEquals($out3, URL::to_route('url-test-3', ['budi']));
        $this->setServerVar('HTTPS', 'off');

        $this->assertEquals($out4, URL::to_route('url-test-2', ['budi', 'purnomo']));
    }

    /**
     * Test untuk method URL::to_language().
     *
     * @group system
     */
    public function testToLanguageMethodGeneratesURLsToDifferentLanguage()
    {
        URI::$uri = 'foo/bar';
        Config::set('application.languages', ['en', 'id']);
        Config::set('application.language', 'en');

        $this->assertEquals('http://localhost/index.php/id/foo/bar', URL::to_language('id'));
        $this->assertEquals('http://localhost/index.php/id/', URL::to_language('id', true));

        Config::set('application.index', '');

        $this->assertEquals('http://localhost/id/foo/bar', URL::to_language('id'));
        $this->assertEquals('http://localhost/en/foo/bar', URL::to_language('en'));
    }

    /**
     * Test bahwa pembuatan URL berdasarkan lokalisasi bahasa bisa dihasilkan dengan benar.
     *
     * @group system
     */
    public function testUrlsGeneratedWithLanguages()
    {
        Config::set('application.languages', ['sp', 'fr']);
        Config::set('application.language', 'fr');

        $this->assertEquals('http://localhost/index.php/fr/foo', URL::to('foo'));
        $this->assertEquals('http://localhost/assets/foo.jpg', URL::to_asset('foo.jpg'));

        Config::set('application.index', '');

        $this->assertEquals('http://localhost/fr/foo', URL::to('foo'));

        Config::set('application.index', 'index.php');
        Config::set('application.language', 'sp');

        $this->assertEquals('http://localhost/index.php/sp/foo', URL::to('foo'));

        Config::set('application.languages', ['en', 'id']);
        Config::set('application.language', 'en');
    }

    /**
     * Helper: set variabel $_SERVER.
     *
     * @param string $key
     * @param mixed  $value
     */
    protected function setServerVar($key, $value)
    {
        $_SERVER[$key] = $value;

        $this->restartRequest();
    }

    /**
     * Inisialisasi ulang global request.
     *
     * @return void
     */
    protected function restartRequest()
    {
        $_FILES = [];

        // Pastikan SCRIPT_NAME ada
        if (!isset($_SERVER['SCRIPT_NAME'])) {
            $_SERVER['SCRIPT_NAME'] = '/index.php';
        }

        // Pastikan HTTP_HOST ada untuk URL generation
        if (!isset($_SERVER['HTTP_HOST'])) {
            $_SERVER['HTTP_HOST'] = 'localhost';
        }

        // Set port based on HTTPS status
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            $_SERVER['SERVER_PORT'] = 443;
        } else {
            $_SERVER['SERVER_PORT'] = 80;
        }

        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();

        // Reset cache foundation
        Request::reset_foundation();

        // Clear URL cache jika ada
        URL::$base = null;
    }
}
