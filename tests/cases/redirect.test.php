<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Router;
use System\Request;
use System\Foundation\Http\Request as FoundationRequest;

class RedirectTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('session.driver', 'foo');

        Router::$routes = [];
        Router::$names = [];

        URL::$base = 'http://localhost/';

        Config::set('application.index', '');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // TODO: bersihkan data request di http foundation.
        Config::set('session.driver', '');

        Router::$routes = [];
        Router::$names = [];

        URL::$base = '';

        Config::set('application.index', 'index.php');

        Session::$instance = null;
    }

    /**
     * Test untuk method Redirect::to().
     *
     * @group system
     */
    public function testSimpleRedirectSetsCorrectHeaders()
    {
        $redirect = Redirect::to('user/profile');

        $this->assertEquals(302, $redirect->status());
        $this->assertEquals('http://localhost/user/profile', $redirect->headers()->get('location'));

        $this->setServerVar('HTTPS', 'on');
        $redirect = Redirect::to('user/profile', 301);
        $this->setServerVar('HTTPS', 'off');

        $this->assertEquals(301, $redirect->status());
        $this->assertEquals('https://localhost/user/profile', $redirect->headers()->get('location'));
    }

    /**
     * Test untuk method Redirect::to_route().
     *
     * @group system
     */
    public function testRedirectsCanBeGeneratedForNamedRoutes()
    {
        Route::get('redirect', ['as' => 'redirect']);
        Route::get('redirect/(:any)/(:any)', ['as' => 'redirect-2']);
        Route::get('secure/redirect', ['https' => true, 'as' => 'redirect-3']);

        $redirect1 = Redirect::to_route('redirect', [], 301, true)->status();
        $redirect2 = Redirect::to_route('redirect')->headers()->get('location');

        $this->setServerVar('HTTPS', 'on');
        $redirect3 = Redirect::to_route('redirect-3', [], 302)->headers()->get('location');
        $this->setServerVar('HTTPS', 'off');

        $redirect4 = Redirect::to_route('redirect-2', ['1', '2'])->headers()->get('location');

        $this->assertEquals(301, $redirect1);
        $this->assertEquals('http://localhost/redirect', $redirect2);
        $this->assertEquals('https://localhost/secure/redirect', $redirect3);
        $this->assertEquals('http://localhost/redirect/1/2', $redirect4);
    }

    /**
     * Test untuk method Redirect::with().
     *
     * @group system
     */
    public function testWithMethodFlashesItemToSession()
    {
        $this->instantiateSession();

        $redirect = Redirect::to('')->with('name', 'Budi');

        $this->assertEquals('Budi', Session::instance()->session['data'][':new:']['name']);
    }

    /**
     * Test untuk method Redirect::with_input().
     *
     * @group system
     */
    public function testWithInputMethodFlashesInputToTheSession()
    {
        $this->instantiateSession();

        $input = ['name' => 'Budi', 'age' => 25];
        Request::foundation()->request->add($input);

        $redirect = Redirect::to('')->with_input();
        $this->assertEquals($input, Session::instance()->session['data'][':new:'][Input::OLD]);

        $redirect = Redirect::to('')->with_input('only', ['name']);
        $this->assertEquals(['name' => 'Budi'], Session::instance()->session['data'][':new:'][Input::OLD]);

        $redirect = Redirect::to('')->with_input('except', ['name']);
        $this->assertEquals(['age' => 25], Session::instance()->session['data'][':new:'][Input::OLD]);
    }

    /**
     * Test untuk method Redirect::with_errors().
     *
     * @group system
     */
    public function testWithErrorsFlashesErrorsToTheSession()
    {
        $this->instantiateSession();

        Redirect::to('')->with_errors(['name' => 'Budi']);

        $this->assertEquals(['name' => 'Budi'], Session::instance()->session['data'][':new:']['errors']);

        $validator = Validator::make([], []);
        $validator->errors = ['name' => 'Budi'];

        Redirect::to('')->with_errors($validator);

        $this->assertEquals(['name' => 'Budi'], Session::instance()->session['data'][':new:']['errors']);
    }

    /**
     * Instansiasi payload session.
     */
    protected function instantiateSession()
    {
        $driver = $this->getMock('\System\Session\Drivers\Driver');

        Session::$instance = new \System\Session\Payload($driver);
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

        Request::$foundation = FoundationRequest::createFromGlobals();
    }
}
