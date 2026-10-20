<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Routing\Router;

class CsrfGlobalTest extends \PHPUnit_Framework_TestCase
{
    use \System\Testing\Http;

    /**
     * Framework state replaced by the tests.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->backup = [
            'driver' => Config::get('session.driver'),
            'stateless' => Config::get('session.stateless'),
            'global' => Config::get('application.csrf_global'),
            'except' => Config::get('application.csrf_except'),
            'foundation' => \System\Request::$foundation,
            'route' => \System\Request::$route,
            'session' => \System\Session::$instance,
        ];

        Config::set('session.driver', 'file');
        Config::set('session.stateless', ['csrf-api/*']);
        Config::set('application.csrf_global', true);
        Config::set('application.csrf_except', ['csrf-hook']);

        foreach (['csrf-form', 'csrf-hook', 'csrf-api/items'] as $uri) {
            Router::register('POST', $uri, function () {
                return 'saved';
            });
        }

        Router::register('POST', 'csrf-open', ['csrf' => false, function () {
            return 'saved';
        }]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach (['csrf-form', 'csrf-hook', 'csrf-api/items', 'csrf-open'] as $uri) {
            unset(Router::$routes['POST'][$uri]);
        }

        Config::set('session.driver', $this->backup['driver']);
        Config::set('session.stateless', $this->backup['stateless']);
        Config::set('application.csrf_global', $this->backup['global']);
        Config::set('application.csrf_except', $this->backup['except']);

        \System\Cookie::flush();
        \System\Session::$instance = $this->backup['session'];
        \System\Request::$route = $this->backup['route'];
        \System\Request::$foundation = $this->backup['foundation'];
        \System\Request::reset_foundation();
    }

    /**
     * A route without the csrf middleware is still checked.
     *
     * @group system
     */
    public function testRoutesWithoutTheMiddlewareAreChecked()
    {
        $this->post('csrf-form', [], ['X-Csrf-Token' => 'forged'])->assert_unprocessable();
        $this->post('csrf-form')->assert_ok()->assert_see('saved');
    }

    /**
     * The exceptions: csrf_except, 'csrf' => false, stateless routes, the switch.
     *
     * @group system
     */
    public function testExceptions()
    {
        $this->post('csrf-hook', [], ['X-Csrf-Token' => 'forged'])->assert_ok();
        $this->post('csrf-open', [], ['X-Csrf-Token' => 'forged'])->assert_ok();
        $this->post('csrf-api/items', [], ['X-Csrf-Token' => 'forged'])->assert_ok();

        Config::set('application.csrf_global', false);
        $this->post('csrf-form', [], ['X-Csrf-Token' => 'forged'])->assert_ok();
    }

    /**
     * Reading is never checked.
     *
     * @group system
     */
    public function testSafeMethodsAreNotChecked()
    {
        Router::register('GET', 'csrf-read', function () {
            return 'read';
        });

        $this->get('csrf-read', ['X-Csrf-Token' => 'forged'])->assert_ok();

        unset(Router::$routes['GET']['csrf-read']);
    }
}
