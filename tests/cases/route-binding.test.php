<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;
use System\Response;
use System\Routing\Route;
use System\Routing\Router;

class RouteBindingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Routes registered by the tests.
     *
     * @var array
     */
    protected $uris = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];

        Schema::create('binding_users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('slug');
        });

        Database::table('binding_users')->insert(['name' => 'Budi', 'slug' => 'budi']);
        Database::table('binding_users')->insert(['name' => 'Sari', 'slug' => 'sari']);

        Route::model('bt_user', 'BindingUser');
        Route::model('bt_slug', 'BindingUser', 'slug');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach ($this->uris as $uri) {
            unset(Router::$routes['GET'][$uri]);
        }

        foreach (['bt_user', 'bt_slug', 'bt_code'] as $name) {
            Route::unbind($name);
        }

        unset(\System\Routing\Middleware::$middlewares['bt_block']);
        Schema::drop_if_exists('binding_users');
        Database::$connections = [];
    }

    /**
     * Register a GET route for the test.
     *
     * @param string $uri
     * @param mixed  $action
     */
    protected function route($uri, $action)
    {
        $this->uris[] = $uri;
        Router::register('GET', $uri, $action);
    }

    /**
     * Dispatch a GET request and return the response.
     *
     * @param string $uri
     *
     * @return \System\Response
     */
    protected function get($uri)
    {
        $response = Router::route('GET', $uri)->call();
        $response->render();

        return $response;
    }

    /**
     * A bound wildcard passes the model; a missing one answers 404.
     *
     * @group system
     */
    public function testModelBinding()
    {
        $this->route('bt/users/(:bt_user)', function ($user) {
            return get_class($user).':'.$user->name;
        });

        $this->assertEquals('BindingUser:Sari', $this->get('bt/users/2')->content);
        $this->assertEquals(404, $this->get('bt/users/99')->status());
    }

    /**
     * A column binding looks the row up by that column.
     *
     * @group system
     */
    public function testColumnBinding()
    {
        $this->route('bt/profiles/(:bt_slug)', function ($user) {
            return $user->name;
        });

        $this->assertEquals('Budi', $this->get('bt/profiles/budi')->content);
        $this->assertEquals(404, $this->get('bt/profiles/nobody')->status());
    }

    /**
     * A closure binding returns whatever it resolves to.
     *
     * @group system
     */
    public function testClosureBinding()
    {
        Route::bind('bt_code', function ($value) {
            return ('ok' === $value) ? ['code' => strtoupper($value)] : null;
        });

        $this->route('bt/codes/(:bt_code)', function ($code) {
            return $code['code'];
        });

        $this->assertEquals('OK', $this->get('bt/codes/ok')->content);
        $this->assertEquals(404, $this->get('bt/codes/no')->status());
    }

    /**
     * Bound and plain wildcards keep their positions; an absent optional one is skipped.
     *
     * @group system
     */
    public function testPositionsAndOptionalWildcards()
    {
        $this->route('bt/mixed/(:num)/(:bt_user)/(:bt_slug?)', function ($number, $user, $other = null) {
            return $number.'|'.$user->name.'|'.(is_null($other) ? '-' : $other->name);
        });

        $this->assertEquals('7|Budi|-', $this->get('bt/mixed/7/1')->content);
        $this->assertEquals('7|Budi|Sari', $this->get('bt/mixed/7/1/sari')->content);
        $this->assertEquals(404, $this->get('bt/mixed/7/1/nobody')->status());
    }

    /**
     * Before middleware runs first: a refused request never reaches the resolver.
     *
     * @group system
     */
    public function testBeforeMiddlewareRunsBeforeBinding()
    {
        $calls = 0;

        Route::bind('bt_code', function ($value) use (&$calls) {
            ++$calls;
            return $value;
        });

        Route::middleware('bt_block', function () {
            return Response::error(401);
        });

        $this->route('bt/guarded/(:bt_code)', ['before' => 'bt_block', function ($code) {
            return $code;
        }]);

        $this->assertEquals(401, $this->get('bt/guarded/x')->status());
        $this->assertSame(0, $calls);
    }

    /**
     * A resolved route keeps its raw parameters.
     *
     * @group system
     */
    public function testRawParametersAreKept()
    {
        $this->route('bt/raw/(:bt_user)', function ($user) {
            return $user->name;
        });

        $route = Router::route('GET', 'bt/raw/1');
        $route->call();

        $this->assertSame(['1'], $route->parameters);
        $this->assertEquals('Budi', $route->call()->content);
    }

    /**
     * Controller actions receive the bound parameters too.
     *
     * @group system
     */
    public function testControllerActionsReceiveBoundParameters()
    {
        Route::bind('bt_code', function ($value) {
            return strtoupper($value);
        });

        $this->route('bt/controller/(:bt_code)', 'auth@profile');

        $this->assertEquals('ABC', $this->get('bt/controller/abc')->content);
    }

    /**
     * A named route takes models: their key, or the column of the binding.
     *
     * @group system
     */
    public function testUrlsTakeModels()
    {
        $this->route('bt/named/(:bt_user)/(:bt_slug?)', ['as' => 'bt-named', function () {
            return '';
        }]);

        $budi = BindingUser::find(1);
        $sari = BindingUser::find(2);

        $this->assertStringEndsWith('/bt/named/1', \System\URL::to_route('bt-named', [$budi]));
        $this->assertStringEndsWith('/bt/named/1/sari', \System\URL::to_route('bt-named', [$budi, $sari]));
        $this->assertStringEndsWith('/bt/named/5', \System\URL::to_route('bt-named', [5]));
    }

    /**
     * Binding names may not be invalid or shadow a built-in wildcard.
     *
     * @group system
     */
    public function testInvalidBindingNames()
    {
        foreach (['num', 'any', 'bad-name', '1st'] as $name) {
            try {
                Route::bind($name, function ($value) {
                    return $value;
                });
                $this->fail('Accepted binding name: '.$name);
            } catch (\InvalidArgumentException $e) {
                $this->assertContains('Invalid route binding name', $e->getMessage());
            }
        }

        $this->assertEquals('([0-9]+)', Router::$patterns['(:num)']);
    }
}

class BindingUser extends \System\Database\Facile\Model
{
    public static $table = 'binding_users';
    public static $timestamps = false;
}
