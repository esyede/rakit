<?php

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Input;
use System\Redirect;
use System\Response;
use System\Session;
use System\Validator;
use System\Routing\Router;

class TestingHttpTest extends \PHPUnit_Framework_TestCase
{
    use \System\Testing\Http;

    /**
     * Routes registered by the tests, method => URIs.
     *
     * @var array
     */
    protected $registered = [];

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
            'driver' => \System\Config::get('session.driver'),
            'foundation' => \System\Request::$foundation,
            'route' => \System\Request::$route,
            'session' => Session::$instance,
        ];

        \System\Config::set('session.driver', 'file');

        $this->route('GET', 'th/hello', function () {
            echo 'echoed ';
            return '<b>Hello '.e(Input::get('name', 'world')).'</b>';
        });

        $this->route('GET', 'th/json', function () {
            return Response::json(['user' => ['id' => 7, 'name' => 'Budi'], 'tags' => ['a', 'b']], 200, ['X-Api' => 'v1']);
        });

        $this->route('POST', 'th/echo-json', function () {
            return Response::json(['got' => Input::get('name'), 'json' => Input::json(true)], 201);
        });

        $this->route('POST', 'th/form', ['before' => 'csrf', function () {
            $validation = Validator::make(Input::all(), ['email' => 'required|email']);

            if ($validation->fails()) {
                return Redirect::to('th/form')->with_errors($validation);
            }

            return Redirect::to('th/done')->with('status', 'saved');
        }]);

        $this->route('GET', 'th/counter', function () {
            Session::put('count', Session::get('count', 0) + 1);
            return (string) Session::get('count');
        });

        $this->route('GET', 'th/private', ['before' => 'auth', function () {
            return 'hi '.Auth::user()->name;
        }]);

        $this->route('GET', 'th/abort', function () {
            abort(403);
        });

        $this->route('GET', 'th/boom', function () {
            throw new \RuntimeException('th-boom');
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->registered as $method => $uris) {
            foreach ($uris as $uri) {
                unset(Router::$routes[$method][$uri]);
            }
        }

        Auth::$drivers = [];
        \System\Cookie::flush();
        \System\Input::$json = null;
        \System\Config::set('session.driver', $this->backup['driver']);
        Session::$instance = $this->backup['session'];
        \System\Request::$route = $this->backup['route'];
        \System\Request::$foundation = $this->backup['foundation'];
        \System\Request::reset_foundation();
    }

    /**
     * Register a route for the test.
     *
     * @param string $method
     * @param string $uri
     * @param mixed  $action
     */
    protected function route($method, $uri, $action)
    {
        $this->registered[$method][] = $uri;
        Router::register($method, $uri, $action);
    }

    /**
     * Status, body, query input and echoed output.
     *
     * @group system
     */
    public function testGet()
    {
        $this->get('th/hello?name=<Sari>')
            ->assert_ok()
            ->assert_see('<Sari>')
            ->assert_see('<b>Hello', false)
            ->assert_see('echoed', false)
            ->assert_dont_see('world');

        $this->get('th/missing')->assert_not_found();
    }

    /**
     * JSON bodies, paths and headers.
     *
     * @group system
     */
    public function testJson()
    {
        $response = $this->get('th/json')
            ->assert_ok()
            ->assert_json(['user' => ['name' => 'Budi']])
            ->assert_json_path('user.id', 7)
            ->assert_json_path('tags.1', 'b')
            ->assert_header('X-Api', 'v1')
            ->assert_header_missing('X-Missing');

        $this->assertEquals('Budi', $response->json('user.name'));

        $this->json('POST', 'th/echo-json', ['name' => 'Sari', 'list' => [1, 2]])
            ->assert_created()
            ->assert_json(['got' => 'Sari', 'json' => ['list' => [1, 2]]]);
    }

    /**
     * A failing assertion reports the difference.
     *
     * @group system
     */
    public function testFailingAssertionsFail()
    {
        try {
            $this->get('th/json')->assert_json(['user' => ['name' => 'Sari']]);
            $this->fail('assert_json passed on a different value');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $this->assertContains('Sari', $e->getMessage().$e->getComparisonFailure()->getDiff());
        }

        try {
            $this->get('th/missing')->assert_ok();
            $this->fail('assert_ok passed on a 404');
        } catch (\PHPUnit_Framework_ExpectationFailedException $e) {
            $this->assertContains('Expected status 200, got 404', $e->getMessage());
        }
    }

    /**
     * POST goes through the real CSRF check with the session's token, and redirects
     * with flashed errors or data.
     *
     * @group system
     */
    public function testFormsWithCsrfAndValidation()
    {
        $this->post('th/form', ['email' => 'nope'])
            ->assert_redirect('th/form')
            ->assert_session_has_errors('email');

        $this->post('th/form', ['email' => 'a@b.co'])
            ->assert_redirect('th/done')
            ->assert_session_has('status', 'saved');

        // A wrong token is still refused.
        $this->post('th/form', ['email' => 'a@b.co'], ['X-Csrf-Token' => 'forged'])
            ->assert_unprocessable();
    }

    /**
     * The session cookie is kept between requests, until forget_client().
     *
     * @group system
     */
    public function testSessionPersistsBetweenRequests()
    {
        $this->get('th/counter')->assert_see('1');
        $this->get('th/counter')->assert_see('2');
        $this->get('th/counter')->assert_session_has('count', 3);

        $this->forget_client();
        $this->get('th/counter')->assert_see('1');
    }

    /**
     * acting_as() logs a user in without a login request.
     *
     * @group system
     */
    public function testActingAs()
    {
        $this->get('th/private')->assert_unauthorized();

        $this->acting_as((object) ['id' => 5, 'name' => 'Sari'])
            ->get('th/private')
            ->assert_ok()
            ->assert_see('hi Sari');
    }

    /**
     * abort() becomes its response; an exception reaches the test.
     *
     * @group system
     */
    public function testAbortAndExceptions()
    {
        $level = ob_get_level();

        $this->get('th/abort')->assert_forbidden();
        $this->assertFalse(\System\Worker\Worker::$handling);

        try {
            $this->get('th/boom');
            $this->fail('The exception was swallowed');
        } catch (\RuntimeException $e) {
            $this->assertEquals('th-boom', $e->getMessage());
        }

        $this->assertFalse(\System\Worker\Worker::$handling);
        $this->assertSame($level, ob_get_level());
    }

    /**
     * The test process' globals are left as they were.
     *
     * @group system
     */
    public function testGlobalsAreRestored()
    {
        $server = $_SERVER;
        $get = $_GET;

        $this->get('th/hello?name=x');

        $this->assertSame($server, $_SERVER);
        $this->assertSame($get, $_GET);
    }
}
