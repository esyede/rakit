<?php

namespace SystemWorkerTest;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Container;
use System\Cookie;
use System\Crypter;
use System\Input;
use System\Request;
use System\Response;
use System\Session;
use System\URL;
use System\Routing\Router;
use System\Worker\Bridge;
use System\Worker\Worker;
use System\Foundation\Oops\Debugger;

class WorkerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Framework state replaced by the worker, restored after each test.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        Request::reset_foundation();

        $this->backup = [
            'foundation' => Request::$foundation,
            'env' => Request::env(),
            'route' => Request::$route,
            'session' => Session::$instance,
            'singletons' => Container::$singletons,
            'jar' => Cookie::$jar,
            'base' => URL::$base,
            'language' => Config::get('application.language'),
            'languages' => Config::get('application.languages'),
            'driver' => Config::get('session.driver'),
            'production' => Debugger::$productionMode,
            'depth' => Debugger::getPanic()->maxDepth,
            'server' => $_SERVER,
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
        ];

        FakeBridge::forget();
        ArrayDriver::$sessions = [];

        Session::extend('worker-test', function () {
            return new ArrayDriver();
        });

        Config::set('session.driver', '');
        Debugger::$productionMode = false;

        // The error page dumps the arguments of every stack frame. Inside PHPUnit they
        // reach the whole test suite, which on PHP 5 is enough to exhaust the default
        // memory limit of 128M. A shallow dump still shows the message.
        Debugger::getPanic()->maxDepth = 1;

        Router::register('GET', 'worker-test/echo', function () {
            echo 'stray-';
            return 'body:'.Input::get('q', '-');
        });

        Router::register('GET', 'worker-test/cookie', function () {
            Cookie::put('flavor', 'choco');
            return 'cookie:'.Cookie::get('incoming', '-');
        });

        Router::register('GET', 'worker-test/session', function () {
            $count = Session::get('count', 0) + 1;
            Session::put('count', $count);
            return 'count:'.$count;
        });

        Router::register('GET', 'worker-test/boom', function () {
            throw new \Exception('worker-boom');
        });

        Router::register('POST', 'worker-test/json', function () {
            return Response::json(Input::json(true));
        });

        Router::register('GET', 'worker-test/state', function () {
            $leftover = Container::registered('worker-test.request');
            Container::instance('worker-test.request', 'request-only');

            return Response::json([
                'language' => Config::get('application.language'),
                'env' => Request::env(),
                'boot' => Container::registered('worker-test.boot'),
                'leftover' => $leftover,
            ]);
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach (['echo', 'cookie', 'session', 'boom', 'state'] as $uri) {
            unset(Router::$routes['GET']['worker-test/'.$uri]);
        }

        unset(Router::$routes['POST']['worker-test/json'], Session::$registrar['worker-test']);

        Config::set('application.language', $this->backup['language']);
        Config::set('application.languages', $this->backup['languages']);
        Config::set('session.driver', $this->backup['driver']);

        Request::$foundation = $this->backup['foundation'];
        Request::$route = $this->backup['route'];
        Request::reset_foundation();

        if (is_null($this->backup['env'])) {
            Request::foundation()->server->remove('RAKIT_ENV');
        } else {
            Request::set_env($this->backup['env']);
        }
        Session::$instance = $this->backup['session'];
        Container::flush();
        Container::$singletons = $this->backup['singletons'];
        Cookie::flush();
        Cookie::$jar = $this->backup['jar'];
        URL::$base = $this->backup['base'];
        Input::$json = null;
        Debugger::$productionMode = $this->backup['production'];
        Debugger::getPanic()->maxDepth = $this->backup['depth'];

        $_SERVER = $this->backup['server'];
        $_GET = $this->backup['get'];
        $_POST = $this->backup['post'];
        $_COOKIE = $this->backup['cookie'];

        FakeBridge::forget();
        ArrayDriver::$sessions = [];
    }

    /**
     * Test that each request only sees its own input and output.
     *
     * @group system
     */
    public function testEachRequestIsServedWithItsOwnState()
    {
        $bridge = $this->serve([
            ['uri' => '/worker-test/echo', 'get' => ['q' => 'first']],
            ['uri' => '/worker-test/echo'],
        ]);

        $this->assertCount(2, $bridge->sent);
        $this->assertSame(200, $bridge->sent[0]['status']);
        $this->assertSame('stray-body:first', $bridge->sent[0]['body']);
        $this->assertSame('stray-body:-', $bridge->sent[1]['body']);
    }

    /**
     * Test that incoming cookies are readable but never sent back,
     * and that queued cookies do not leak into the next request.
     *
     * @group system
     */
    public function testCookiesStayWithTheirRequest()
    {
        $bridge = $this->serve([
            ['uri' => '/worker-test/cookie', 'cookie' => ['incoming' => Crypter::encrypt('hello')]],
            ['uri' => '/worker-test/echo'],
        ]);

        $this->assertSame('cookie:hello', $bridge->sent[0]['body']);
        $this->assertSame(['flavor'], $bridge->sent[0]['cookies']);
        $this->assertSame([], $bridge->sent[1]['cookies']);
    }

    /**
     * Test that the session is loaded for every request, not only at boot.
     *
     * @group system
     */
    public function testSessionIsLoadedForEveryRequest()
    {
        Config::set('session.driver', 'worker-test');

        $bridge = $this->serve([['uri' => '/worker-test/session']]);

        $this->assertSame('count:1', $bridge->sent[0]['body']);
        $this->assertCount(1, ArrayDriver::$sessions);

        $id = key(ArrayDriver::$sessions);
        $cookie = [Config::get('session.cookie') => Crypter::encrypt($id)];

        $bridge = $this->serve([
            ['uri' => '/worker-test/session', 'cookie' => $cookie],
            ['uri' => '/worker-test/session'],
        ]);

        $this->assertSame('count:2', $bridge->sent[0]['body']);
        $this->assertSame('count:1', $bridge->sent[1]['body']);
    }

    /**
     * Test that an exception becomes a 500 response and the worker keeps serving.
     *
     * @group system
     */
    public function testExceptionBecomesErrorResponse()
    {
        $bridge = $this->serve([
            ['uri' => '/worker-test/boom'],
            ['uri' => '/worker-test/boom', 'server' => ['HTTP_ACCEPT' => 'application/json']],
            ['uri' => '/worker-test/echo'],
        ]);

        $this->assertSame(500, $bridge->sent[0]['status']);
        $this->assertContains('worker-boom', $bridge->sent[0]['body']);
        $this->assertSame(500, $bridge->sent[1]['status']);
        $this->assertSame('{"status":500,"message":"worker-boom"}', $bridge->sent[1]['body']);
        $this->assertSame('stray-body:-', $bridge->sent[2]['body']);
    }

    /**
     * Test that the request body is taken from the bridge.
     *
     * @group system
     */
    public function testRequestBodyComesFromTheBridge()
    {
        $bridge = $this->serve([[
            'uri' => '/worker-test/json',
            'method' => 'POST',
            'server' => ['CONTENT_TYPE' => 'application/json'],
            'body' => '{"a":1}',
        ]]);

        $this->assertSame('{"a":1}', $bridge->sent[0]['body']);
    }

    /**
     * Test that the boot-time language, environment and singletons are restored.
     *
     * @group system
     */
    public function testBootStateIsRestoredForEveryRequest()
    {
        Config::set('application.languages', ['xx']);
        Request::set_env('worker-env');
        Container::instance('worker-test.boot', 'boot');

        $bridge = $this->serve([
            ['uri' => '/xx/worker-test/state'],
            ['uri' => '/worker-test/state'],
        ]);

        $first = json_decode($bridge->sent[0]['body'], true);
        $second = json_decode($bridge->sent[1]['body'], true);

        $this->assertSame('xx', $first['language']);
        $this->assertSame($this->backup['language'], $second['language']);
        $this->assertSame('worker-env', $second['env']);
        $this->assertTrue($second['boot']);
        $this->assertFalse($second['leftover']);
    }

    /**
     * Test that Swoole refuses the blocking loop.
     *
     * @group system
     */
    public function testSwooleCanNotRunTheBlockingLoop()
    {
        $this->setExpectedException('LogicException');
        Worker::create('swoole')->run();
    }

    /**
     * Test that an unknown adapter is rejected.
     *
     * @group system
     */
    public function testUnknownAdapterIsRejected()
    {
        $this->setExpectedException('Exception', 'Unknown bridge adapter: foo');
        Worker::create('foo');
    }

    /**
     * Run the given requests through a worker.
     *
     * @param array $requests
     *
     * @return FakeBridge
     */
    protected function serve(array $requests)
    {
        $bridge = new FakeBridge($requests);
        $level = ob_get_level();

        (new Worker($bridge))->run();

        $this->assertSame($level, ob_get_level());

        return $bridge;
    }
}

class FakeBridge extends Bridge
{
    /**
     * Requests waiting to be served.
     *
     * @var array
     */
    public $requests;

    /**
     * Responses sent so far.
     *
     * @var array
     */
    public $sent = [];

    /**
     * Raw body of the current request.
     *
     * @var string|null
     */
    protected $raw;

    /**
     * Constructor.
     *
     * @param array $requests
     */
    public function __construct(array $requests)
    {
        $this->requests = $requests;
    }

    /**
     * Forget the boot snapshot.
     */
    public static function forget()
    {
        self::$snapshot = null;
    }

    /**
     * {@inheritdoc}
     */
    public function wait_request(\Closure $handler)
    {
        if (empty($this->requests)) {
            return false;
        }

        $request = array_merge(
            ['method' => 'GET', 'get' => [], 'cookie' => [], 'server' => [], 'body' => ''],
            array_shift($this->requests)
        );

        $_SERVER = array_merge([
            'REQUEST_METHOD' => $request['method'],
            'REQUEST_URI' => $request['uri'],
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => path('base').'index.php',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'REMOTE_ADDR' => '127.0.0.1',
        ], $request['server']);

        $_GET = $request['get'];
        $_POST = [];
        $_COOKIE = $request['cookie'];
        $_FILES = [];
        $this->raw = $request['body'];

        $handler();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function content()
    {
        return $this->raw;
    }

    /**
     * {@inheritdoc}
     */
    public function send_response(Response $response, $level)
    {
        $output = static::output($level);
        $foundation = $this->prepare($response);
        $cookies = [];

        foreach ($foundation->headers->getCookies() as $cookie) {
            $cookies[] = $cookie->getName();
        }

        $this->sent[] = [
            'status' => $foundation->getStatusCode(),
            'body' => $this->body($foundation, $output),
            'cookies' => $cookies,
        ];

        $this->done($response, $level);
    }
}

class ArrayDriver extends \System\Session\Drivers\Driver
{
    /**
     * Stored sessions, keyed by ID.
     *
     * @var array
     */
    public static $sessions = [];

    /**
     * {@inheritdoc}
     */
    public function load($id)
    {
        return isset(static::$sessions[$id]) ? static::$sessions[$id] : null;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $session, array $config, $exists)
    {
        static::$sessions[$session['id']] = $session;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($id)
    {
        unset(static::$sessions[$id]);
    }
}
