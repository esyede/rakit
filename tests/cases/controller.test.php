<?php

defined('DS') or exit('No direct access.');

use System\View;
use System\Request;
use System\Container;
use System\Routing\Controller;
use System\Routing\Middleware;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        unset(
            Middleware::$middlewares['test-all-before'],
            Middleware::$middlewares['test-all-after'],
            Middleware::$middlewares['test-profile-before'],
            $_SERVER['REQUEST_METHOD']
        );
    }

    /**
     * Test for Controller::call().
     *
     * @group system
     */
    public function testBasicControllerActionCanBeCalled()
    {
        $this->assertEquals('action_index', Controller::call('auth@index')->content);
        $this->assertEquals('Admin_Panel_Index', Controller::call('admin.panel@index')->content);
        $this->assertEquals('Budi', Controller::call('auth@profile', ['Budi'])->content);
        $this->assertEquals('Dashboard_Panel_Index', Controller::call('dashboard::panel@index')->content);
    }

    /**
     * Test thet assigned 'before' and 'after' middlewares are run.
     *
     * @group system
     */
    public function testAssignedBeforeMiddlewaresAreRun()
    {
        $_SERVER['test-all-before'] = false;
        $_SERVER['test-all-after'] = false;

        Controller::call('middleware@index');

        $this->assertTrue($_SERVER['test-all-before']);
        $this->assertTrue($_SERVER['test-all-after']);
    }

    /**
     * Test that 'only' middlewares will only apply to their assigned methods.
     *
     * @group system
     */
    public function testOnlyMiddlewaresOnlyApplyToTheirAssignedMethods()
    {
        $_SERVER['test-profile-before'] = false;

        Controller::call('middleware@index');
        $this->assertFalse($_SERVER['test-profile-before']);

        Controller::call('middleware@profile');
        $this->assertTrue($_SERVER['test-profile-before']);
    }

    /**
     * Test thet 'except' middlewares will apply to all methods except the excluded ones.
     *
     * @group system
     */
    public function testExceptMiddlewaresOnlyApplyToTheExlucdedMethods()
    {
        $_SERVER['test-except'] = false;

        Controller::call('middleware@index');
        Controller::call('middleware@profile');
        $this->assertFalse($_SERVER['test-except']);

        Controller::call('middleware@show');
        $this->assertTrue($_SERVER['test-except']);
    }

    /**
     * Test that middlewares can be constrained by request method.
     * i.e. only run on POST, PUT, etc.
     *
     * @group system
     */
    public function testMiddlewaresCanBeConstrainedByRequestMethod()
    {
        $_SERVER['test-on-post'] = false;

        Request::foundation()->setMethod('GET');
        Controller::call('middleware@index');
        $this->assertFalse($_SERVER['test-on-post']);

        Request::foundation()->setMethod('POST');
        Controller::call('middleware@index');
        $this->assertTrue($_SERVER['test-on-post']);

        $_SERVER['test-on-get-put'] = false;
        Request::foundation()->setMethod('POST');
        Controller::call('middleware@index');
        $this->assertFalse($_SERVER['test-on-get-put']);

        Request::foundation()->setMethod('PUT');
        Controller::call('middleware@index');
        $this->assertTrue($_SERVER['test-on-get-put']);
    }

    /**
     * Test that global 'before' middleware is not called by controller actions.
     *
     * @group system
     */
    public function testGlobalBeforeMiddlewareIsNotCalledByController()
    {
        $_SERVER['before'] = false;
        $_SERVER['after'] = false;

        Controller::call('auth@index');
        $this->assertFalse($_SERVER['before']);
        $this->assertFalse($_SERVER['after']);
    }

    /**
     * Test that 'before' middleware can override controller responses.
     *
     * @group system
     */
    public function testBeforeMiddlewaresCanOverrideResponses()
    {
        $this->assertEquals('Middleware OK!', Controller::call('middleware@login')->content);
    }

    /**
     * Test 'after' middlewares do not affect controller responses.
     *
     * @group system
     */
    public function testAfterMiddlewaresDoNotAffectControllerResponse()
    {
        $this->assertEquals('action_logout', Controller::call('middleware@logout')->content);
    }

    /**
     * Test that middleware parameters are passed to the middleware.
     *
     * @group system
     */
    public function testMiddlewareParametersArePassedToTheMiddleware()
    {
        $this->assertEquals('12', Controller::call('middleware@edit')->content);
    }

    /**
     * Test that multiple middlewares can be assigned to a single action.
     *
     * @group system
     */
    public function testMultipleMiddlewaresCanBeAssignedToAnAction()
    {
        $_SERVER['test-multi-1'] = false;
        $_SERVER['test-multi-2'] = false;

        Controller::call('middleware@save');
        $this->assertTrue($_SERVER['test-multi-1']);
        $this->assertTrue($_SERVER['test-multi-2']);
    }

    /**
     * Test that restful controllers respond to restful methods.
     *
     * @group system
     */
    public function testRestfulControllersRespondWithRestfulMethods()
    {
        Request::foundation()->setMethod('GET');
        $this->assertEquals('get_index', Controller::call('restful@index')->content);

        Request::foundation()->setMethod('PUT');
        $this->assertEquals(404, Controller::call('restful@index')->status());

        Request::foundation()->setMethod('POST');
        $this->assertEquals('post_index', Controller::call('restful@index')->content);
    }

    /**
     * Test that template controllers return the template correctly.
     *
     * @group system
     */
    public function testTemplateControllersReturnTheTemplate()
    {
        $response = Controller::call('template.basic@index');
        $home = file_get_contents(path('app') . 'views' . DS . 'home' . DS . 'index.php');
        $this->assertEquals($home, $response->content);
    }

    /**
     * Test that controller templates can be named views.
     *
     * @group system
     */
    public function testControllerTemplatesCanBeNamedViews()
    {
        View::name('home.index', 'home');

        $response = Controller::call('template.named@index');

        $home = file_get_contents(path('app') . 'views' . DS . 'home' . DS . 'index.php');
        $home = trim(preg_replace('/[\t\n\r\s]+/', '', $home)); // ignore whitespace
        $response = trim(preg_replace('/[\t\n\r\s]+/', '', $response->content)); // ignore whitespace
        $this->assertEquals($home, $response);

        View::$names = [];
    }

    /**
     * Test that 'layout' template can be overridden.
     *
     * @group system
     */
    public function testTheTemplateCanBeOverriden()
    {
        $this->assertEquals('Layout', Controller::call('template.override@index')->content);
    }

    /**
     * Test for Controller::resolve().
     *
     * @group system
     */
    public function testResolveMethodChecksTheContainerContainer()
    {
        Container::register('controller: home', function () {
            require_once path('app') . 'controllers' . DS . 'home.php';
            $controller = new Home_Controller();
            $controller->foo = 'bar';
            return $controller;
        });

        $controller = Controller::resolve(DEFAULT_PACKAGE, 'home');
        $this->assertEquals('bar', $controller->foo);
    }
}
