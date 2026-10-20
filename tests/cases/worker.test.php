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
            'logger' => Debugger::getLogger(),
            'email' => Debugger::$email,
            'log' => Config::get('log'),
            'server' => $_SERVER,
            'get' => $_GET,
            'post' => $_POST,
            'cookie' => $_COOKIE,
            'curl' => \System\Curl::state(),
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

        Router::register('GET', 'worker-test/curl', function () {
            $before = \System\Curl::state();
            \System\Curl::auth('request-user', 'secret');
            \System\Curl::cookie('request=1');
            \System\Curl::verify_peer(false);

            return Response::json([
                'user' => $before['auth']['user'],
                'cookie' => $before['cookie'],
                'peer' => $before['verify_peer'],
                'header' => isset($before['default_headers']['x-boot']) || isset($before['default_headers']['X-Boot']),
            ]);
        });

        Router::register('POST', 'worker-test/field', function () {
            return Input::get('a', '-').'|'.Input::get('a', '-');
        });

        Router::register('GET', 'worker-test/abort', function () {
            echo 'stray-';
            Session::put('aborted', 'yes');
            abort(404);

            return 'unreachable';
        });

        Router::register('GET', 'worker-test/abort-view', function () {
            return \System\View::make('workerabortprobe');
        });

        Router::register('GET', 'worker-test/views', function () {
            $count = count(\System\View::$files);
            \System\View::$files['worker-test'] = __FILE__;

            return 'views:'.$count;
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
        foreach (['echo', 'cookie', 'session', 'boom', 'state', 'curl', 'views', 'abort', 'abort-view'] as $uri) {
            unset(Router::$routes['GET']['worker-test/'.$uri]);
        }

        unset(
            Router::$routes['POST']['worker-test/json'],
            Router::$routes['POST']['worker-test/field'],
            Session::$registrar['worker-test']
        );

        \System\Curl::state($this->backup['curl']);
        \System\View::$files = [];
        \System\Hook::clear('rakit.terminated');

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
        Debugger::setLogger($this->backup['logger']);
        Debugger::$email = $this->backup['email'];
        Config::set('log', $this->backup['log']);
        \System\Log::$drivers = [];

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
            ['uri' => '/worker-test/cookie', 'cookie' => ['incoming' => Cookie::seal('incoming', 'hello')]],
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
        $cookie = [Config::get('session.cookie') => Cookie::seal(Config::get('session.cookie'), $id)];

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
     * Test that an exception in production sends the error email.
     *
     * @group system
     */
    public function testProductionExceptionSendsErrorEmail()
    {
        $dir = sys_get_temp_dir().DS.'rakit-worker-email-'.md5(uniqid('', true));
        mkdir($dir, 0777, true);

        $sent = [];
        $logger = new \System\Foundation\Oops\Logger($dir, 'dev@example.com');
        $logger->mailer = function ($message, $email) use (&$sent) {
            $sent[] = [$message, $email];
        };

        Debugger::setLogger($logger);
        Debugger::$email = 'dev@example.com';
        Debugger::$productionMode = true;
        Config::set('log', ['default' => 'null', 'channels' => ['null' => ['driver' => 'null']]]);
        \System\Log::$drivers = [];

        $bridge = $this->serve([
            ['uri' => '/worker-test/boom'],
            ['uri' => '/worker-test/boom'],
        ]);

        @unlink($dir.DS.'email-sent');
        @rmdir($dir);

        $this->assertSame(500, $bridge->sent[0]['status']);
        $this->assertSame(500, $bridge->sent[1]['status']);

        // The second one falls within the snooze period.
        $this->assertCount(1, $sent);
        $this->assertSame('worker-boom', $sent[0][0]->getMessage());
        $this->assertSame('dev@example.com', $sent[0][1]);
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
     * Test that one request's HTTP client settings never reach the next, while the
     * ones set at boot stay.
     *
     * @group system
     */
    public function testCurlSettingsAreRestoredForEveryRequest()
    {
        // Boot from clean settings: other tests leave credentials behind.
        \System\Curl::reset();
        \System\Curl::default_header('X-Boot', '1');

        $bridge = $this->serve([
            ['uri' => '/worker-test/curl'],
            ['uri' => '/worker-test/curl'],
        ]);

        $second = json_decode($bridge->sent[1]['body'], true);

        $this->assertSame('', $second['user']);
        $this->assertEmpty($second['cookie']);
        $this->assertSame(1, $second['peer']);
        $this->assertTrue($second['header']);
    }

    /**
     * Test that the decoded JSON body belongs to its own request.
     *
     * @group system
     */
    public function testJsonInputIsDecodedPerRequest()
    {
        $json = ['CONTENT_TYPE' => 'application/json'];

        $bridge = $this->serve([
            ['uri' => '/worker-test/field', 'method' => 'POST', 'server' => $json, 'body' => '{"a":"first"}'],
            ['uri' => '/worker-test/field', 'method' => 'POST', 'server' => $json, 'body' => '{"a":"second"}'],
            ['uri' => '/worker-test/field', 'method' => 'POST', 'server' => $json, 'body' => ''],
        ]);

        $this->assertSame('first|first', $bridge->sent[0]['body']);
        $this->assertSame('second|second', $bridge->sent[1]['body']);
        $this->assertSame('-|-', $bridge->sent[2]['body']);
    }

    /**
     * Test that view paths found in one request are looked up again in the next.
     *
     * @group system
     */
    public function testViewPathsAreForgottenBetweenRequests()
    {
        $bridge = $this->serve([
            ['uri' => '/worker-test/views'],
            ['uri' => '/worker-test/views'],
        ]);

        $this->assertSame('views:0', $bridge->sent[1]['body']);
    }

    /**
     * Test that 'rakit.terminated' fires after the response went out, and that its
     * failure or output does not affect the worker.
     *
     * @group system
     */
    public function testTerminatedFiresAfterTheResponse()
    {
        $seen = [];
        $holder = new \stdClass();
        $holder->bridge = null;

        \System\Hook::listen('rakit.terminated', function () use (&$seen, $holder) {
            $seen[] = count($holder->bridge->sent);
            echo 'dropped';
            throw new \Exception('terminated-boom');
        });

        $holder->bridge = new FakeBridge([
            ['uri' => '/worker-test/echo'],
            ['uri' => '/worker-test/echo'],
        ]);

        $level = ob_get_level();
        (new Worker($holder->bridge))->run();

        $this->assertSame($level, ob_get_level());
        $this->assertSame([1, 2], $seen);
        $this->assertSame('stray-body:-', $holder->bridge->sent[1]['body']);
    }

    /**
     * Test that abort() answers the request without ending the worker, drops the
     * output before it, and still saves the session.
     *
     * @group system
     */
    public function testAbortDoesNotEndTheWorker()
    {
        Config::set('session.driver', 'worker-test');

        $bridge = $this->serve([
            ['uri' => '/worker-test/abort'],
            ['uri' => '/worker-test/echo'],
        ]);

        $this->assertCount(2, $bridge->sent);
        $this->assertSame(404, $bridge->sent[0]['status']);
        $this->assertNotContains('stray-', $bridge->sent[0]['body']);
        $this->assertNotContains('unreachable', $bridge->sent[0]['body']);
        $this->assertSame('stray-body:-', $bridge->sent[1]['body']);
        $this->assertFalse(Worker::$handling);

        $saved = false;

        foreach (ArrayDriver::$sessions as $session) {
            $saved = $saved || (isset($session['data']['aborted']) && 'yes' === $session['data']['aborted']);
        }

        $this->assertTrue($saved);
    }

    /**
     * Test that abort() inside a view reaches the worker as well.
     *
     * @group system
     */
    public function testAbortInsideAViewDoesNotEndTheWorker()
    {
        $template = path('app').'views'.DS.'workerabortprobe.blade.php';
        file_put_contents($template, 'probe-marker-start {{ abort(403) }} probe-marker-end');

        $bridge = $this->serve([
            ['uri' => '/worker-test/abort-view'],
            ['uri' => '/worker-test/echo'],
        ]);

        unlink($template);
        $compiled = \System\Blade::compiled($template);
        is_file($compiled) && unlink($compiled);

        $this->assertSame(403, $bridge->sent[0]['status']);
        $this->assertNotContains('probe-marker', $bridge->sent[0]['body']);
        $this->assertSame('stray-body:-', $bridge->sent[1]['body']);
    }

    /**
     * Test that abort() outside a worker request still takes the normal path.
     *
     * @group system
     */
    public function testWorkerFlagIsOffOutsideARequest()
    {
        $this->serve([['uri' => '/worker-test/echo']]);
        $this->assertFalse(Worker::$handling);
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
