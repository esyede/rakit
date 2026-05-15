<?php

defined('DS') or exit('No direct access.');

use System\Routing\Middleware;
use System\Routing\Middlewares;
use System\Cache;
use System\Config;

class RoutingExtrasTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Middleware::$middlewares = [];
        Middleware::$patterns = [];
        Middleware::$aliases = [];
        Cache::$drivers = [];
        Cache::$registrar = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Middleware::$middlewares = [];
        Middleware::$patterns = [];
        Middleware::$aliases = [];
        Cache::$drivers = [];
        Cache::$registrar = [];
    }

    // -------------------------------------------------------------------------
    // Middleware
    // -------------------------------------------------------------------------

    /**
     * Test for Middleware::register() - registers a closure.
     *
     * @group system
     */
    public function testMiddlewareRegistersClosure()
    {
        $handler = function () {
            return 'middleware';
        };

        Middleware::register('auth', $handler);
        $this->assertSame($handler, Middleware::$middlewares['auth']);
    }

    /**
     * Test for Middleware::register() - registers with alias resolution.
     *
     * @group system
     */
    public function testMiddlewareRegistersWithAliasResolution()
    {
        Middleware::alias('application.auth', 'auth');

        $handler = function () {
            return 'auth handler';
        };

        Middleware::register('auth', $handler);
        $this->assertArrayHasKey('application.auth', Middleware::$middlewares);
    }

    /**
     * Test for Middleware::register() - registers pattern middlewares.
     *
     * @group system
     */
    public function testMiddlewareRegistersPatternMiddlewares()
    {
        $handler = function () {
            return 'pattern handler';
        };

        Middleware::register('pattern: /admin/*, /api/*', $handler);
        $this->assertArrayHasKey('/admin/*', Middleware::$patterns);
        $this->assertArrayHasKey('/api/*', Middleware::$patterns);
        $this->assertSame($handler, Middleware::$patterns['/admin/*']);
    }

    /**
     * Test for Middleware::register() - registers single pattern middleware.
     *
     * @group system
     */
    public function testMiddlewareRegistersSinglePatternMiddleware()
    {
        $handler = function () {
            return 'handler';
        };

        Middleware::register('pattern: /admin', $handler);
        $this->assertArrayHasKey('/admin', Middleware::$patterns);
    }

    /**
     * Test for Middleware::alias() - creates an alias.
     *
     * @group system
     */
    public function testMiddlewareCreatesAlias()
    {
        Middleware::alias('mypackage.auth', 'auth');
        $this->assertEquals('mypackage.auth', Middleware::$aliases['auth']);
    }

    /**
     * Test for Middleware::parse() - parses string to array.
     *
     * @group system
     */
    public function testMiddlewareParseStringToArray()
    {
        $result = Middleware::parse('before|after|auth');
        $this->assertEquals(['before', 'after', 'auth'], $result);
    }

    /**
     * Test for Middleware::parse() - returns array as-is.
     *
     * @group system
     */
    public function testMiddlewareParseArrayAsIs()
    {
        $input = ['before', 'after'];
        $result = Middleware::parse($input);
        $this->assertEquals($input, $result);
    }

    /**
     * Test for Middleware::parse() - single middleware string.
     *
     * @group system
     */
    public function testMiddlewareParseSingleString()
    {
        $result = Middleware::parse('before');
        $this->assertEquals(['before'], $result);
    }

    /**
     * Test for Middleware::run() - calls registered middlewares.
     *
     * @group system
     */
    public function testMiddlewareRunCallsRegisteredMiddlewares()
    {
        $called = [];
        Middleware::register('test-mw-1', function () use (&$called) {
            $called[] = 'first';
        });
        Middleware::register('test-mw-2', function () use (&$called) {
            $called[] = 'second';
        });

        $collection1 = new Middlewares('test-mw-1');
        $collection2 = new Middlewares('test-mw-2');

        Middleware::run([$collection1, $collection2]);
        $this->assertEquals(['first', 'second'], $called);
    }

    /**
     * Test for Middleware::run() - returns response when override=true.
     *
     * @group system
     */
    public function testMiddlewareRunReturnsResponseWhenOverride()
    {
        Middleware::register('blocking-mw', function () {
            return 'blocked';
        });

        $collection = new Middlewares('blocking-mw');
        $result = Middleware::run([$collection], [], true);
        $this->assertEquals('blocked', $result);
    }

    /**
     * Test for Middleware::run() - skips unregistered middleware.
     *
     * @group system
     */
    public function testMiddlewareRunSkipsUnregisteredMiddleware()
    {
        $called = false;
        Middleware::register('exists', function () use (&$called) {
            $called = true;
        });

        $coll1 = new Middlewares('nonexistent-mw');
        $coll2 = new Middlewares('exists');

        Middleware::run([$coll1, $coll2]);
        $this->assertTrue($called);
    }

    /**
     * Test for Middleware::run() - passes parameters.
     *
     * @group system
     */
    public function testMiddlewareRunPassesParameters()
    {
        $received = [];
        Middleware::register('param-mw', function ($a, $b) use (&$received) {
            $received = [$a, $b];
        });

        $collection = new Middlewares('param-mw', ['x', 'y']);
        Middleware::run([$collection]);
        $this->assertEquals(['x', 'y'], $received);
    }

    // -------------------------------------------------------------------------
    // Middlewares
    // -------------------------------------------------------------------------

    /**
     * Test for Middlewares::__construct() - parses string of middlewares.
     *
     * @group system
     */
    public function testMiddlewaresConstructorParsesString()
    {
        $mws = new Middlewares('before|after');
        $this->assertEquals(['before', 'after'], $mws->middlewares);
    }

    /**
     * Test for Middlewares::__construct() - accepts array.
     *
     * @group system
     */
    public function testMiddlewaresConstructorAcceptsArray()
    {
        $mws = new Middlewares(['before', 'after']);
        $this->assertEquals(['before', 'after'], $mws->middlewares);
    }

    /**
     * Test for Middlewares::get() - returns middleware name and empty params.
     *
     * @group system
     */
    public function testMiddlewaresGetReturnsNameAndEmptyParams()
    {
        $mws = new Middlewares('auth');
        list($name, $params) = $mws->get('auth');
        $this->assertEquals('auth', $name);
        $this->assertEquals([], $params);
    }

    /**
     * Test for Middlewares::get() - parses middleware with parameters.
     *
     * @group system
     */
    public function testMiddlewaresGetParsesMiddlewareWithColon()
    {
        $mws = new Middlewares('role:admin,editor');
        list($name, $params) = $mws->get('role:admin,editor');
        $this->assertEquals('role', $name);
        $this->assertEquals(['admin', 'editor'], $params);
    }

    /**
     * Test for Middlewares::get() - returns constructor parameters when set.
     *
     * @group system
     */
    public function testMiddlewaresGetReturnsConstructorParameters()
    {
        $mws = new Middlewares('auth', ['admin']);
        list($name, $params) = $mws->get('auth');
        $this->assertEquals('auth', $name);
        $this->assertEquals(['admin'], $params);
    }

    /**
     * Test for Middlewares::get() - evaluates Closure parameters.
     *
     * @group system
     */
    public function testMiddlewaresGetEvaluatesClosureParameters()
    {
        $mws = new Middlewares('auth', function () {
            return ['role' => 'admin'];
        });

        list($name, $params) = $mws->get('auth');
        $this->assertEquals('auth', $name);
        $this->assertEquals(['role' => 'admin'], $params);
    }

    /**
     * Test for Middlewares::except() - sets excluded methods.
     *
     * @group system
     */
    public function testMiddlewaresExceptSetsExcludedMethods()
    {
        $mws = new Middlewares('auth');
        $result = $mws->except(['index', 'show']);
        $this->assertSame($mws, $result);
        $this->assertEquals(['index', 'show'], $mws->except);
    }

    /**
     * Test for Middlewares::except() - accepts variadic arguments.
     *
     * @group system
     */
    public function testMiddlewaresExceptAcceptsVariadicArguments()
    {
        $mws = new Middlewares('auth');
        $mws->except('index', 'show');
        $this->assertEquals(['index', 'show'], $mws->except);
    }

    /**
     * Test for Middlewares::only() - sets included methods.
     *
     * @group system
     */
    public function testMiddlewaresOnlySetsIncludedMethods()
    {
        $mws = new Middlewares('auth');
        $result = $mws->only(['create', 'store']);
        $this->assertSame($mws, $result);
        $this->assertEquals(['create', 'store'], $mws->only);
    }

    /**
     * Test for Middlewares::only() - accepts variadic arguments.
     *
     * @group system
     */
    public function testMiddlewaresOnlyAcceptsVariadicArguments()
    {
        $mws = new Middlewares('auth');
        $mws->only('create', 'store');
        $this->assertEquals(['create', 'store'], $mws->only);
    }

    /**
     * Test for Middlewares::on() - sets HTTP methods.
     *
     * @group system
     */
    public function testMiddlewaresOnSetsHttpMethods()
    {
        $mws = new Middlewares('csrf');
        $result = $mws->on(['post', 'put']);
        $this->assertSame($mws, $result);
        $this->assertEquals(['post', 'put'], $mws->methods);
    }

    /**
     * Test for Middlewares::on() - normalizes to lowercase.
     *
     * @group system
     */
    public function testMiddlewaresOnNormalizesToLowercase()
    {
        $mws = new Middlewares('csrf');
        $mws->on(['POST', 'PUT']);
        $this->assertEquals(['post', 'put'], $mws->methods);
    }

    /**
     * Test for Middlewares::on() - accepts variadic arguments.
     *
     * @group system
     */
    public function testMiddlewaresOnAcceptsVariadicArguments()
    {
        $mws = new Middlewares('csrf');
        $mws->on('post', 'put');
        $this->assertEquals(['post', 'put'], $mws->methods);
    }

    /**
     * Test for Middlewares::applies() - returns true when no filters set.
     *
     * @group system
     */
    public function testMiddlewaresAppliesReturnsTrueWhenNoFiltersSet()
    {
        $mws = new Middlewares('auth');
        $this->assertTrue($mws->applies('index'));
    }

    /**
     * Test for Middlewares::applies() - returns false when method is in except.
     *
     * @group system
     */
    public function testMiddlewaresAppliesReturnsFalseWhenMethodInExcept()
    {
        $mws = new Middlewares('auth');
        $mws->except(['index', 'show']);
        $this->assertFalse($mws->applies('index'));
        $this->assertTrue($mws->applies('store'));
    }

    /**
     * Test for Middlewares::applies() - returns false when method not in only.
     *
     * @group system
     */
    public function testMiddlewaresAppliesReturnsFalseWhenMethodNotInOnly()
    {
        $mws = new Middlewares('auth');
        $mws->only(['create', 'store']);
        $this->assertFalse($mws->applies('index'));
        $this->assertTrue($mws->applies('create'));
    }

    // -------------------------------------------------------------------------
    // Throttle (using Memory cache driver)
    // -------------------------------------------------------------------------

    /**
     * Test for Throttle::key() - returns a non-empty string.
     *
     * @group system
     */
    public function testThrottleKeyReturnsNonEmptyString()
    {
        $key = \System\Routing\Throttle::key();
        $this->assertInternalType('string', $key);
        $this->assertNotEmpty($key);
        $this->assertContains('throttle', $key);
    }

    /**
     * Test for Throttle::check() - allows first request.
     *
     * @group system
     */
    public function testThrottleCheckAllowsFirstRequest()
    {
        Cache::extend('memory', function () {
            return new \System\Cache\Drivers\Memory();
        });
        Config::set('cache.driver', 'memory');

        $result = \System\Routing\Throttle::check(5, 1);
        $this->assertTrue($result);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for Throttle::check() - denies after max_attempts.
     *
     * @group system
     */
    public function testThrottleCheckDeniesAfterMaxAttempts()
    {
        $memory = new \System\Cache\Drivers\Memory();
        Cache::extend('memory', function () use ($memory) {
            return $memory;
        });
        Config::set('cache.driver', 'memory');

        for ($i = 0; $i < 3; $i++) {
            \System\Routing\Throttle::check(3, 1);
        }

        $result = \System\Routing\Throttle::check(3, 1);
        $this->assertFalse($result);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for Throttle::exceeded() - returns true when limit exceeded.
     *
     * @group system
     */
    public function testThrottleExceededReturnsTrueWhenLimitExceeded()
    {
        $memory = new \System\Cache\Drivers\Memory();
        Cache::extend('memory', function () use ($memory) {
            return $memory;
        });
        Config::set('cache.driver', 'memory');

        for ($i = 0; $i < 2; $i++) {
            \System\Routing\Throttle::check(2, 1);
        }

        $result = \System\Routing\Throttle::exceeded(2, 1);
        $this->assertTrue($result);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for Throttle::exceeded() - returns false when limit not exceeded.
     *
     * @group system
     */
    public function testThrottleExceededReturnsFalseWhenLimitNotExceeded()
    {
        Cache::extend('memory', function () {
            return new \System\Cache\Drivers\Memory();
        });
        Config::set('cache.driver', 'memory');

        $result = \System\Routing\Throttle::exceeded(10, 1);
        $this->assertFalse($result);

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }

    /**
     * Test for Throttle::error() - returns 429 Response.
     *
     * @group system
     */
    public function testThrottleErrorReturns429Response()
    {
        Cache::extend('memory', function () {
            return new \System\Cache\Drivers\Memory();
        });
        Config::set('cache.driver', 'memory');

        $response = \System\Routing\Throttle::error();
        $this->assertEquals(429, $response->foundation()->getStatusCode());

        Config::set('cache.driver', 'file');
        Cache::$drivers = [];
    }
}
