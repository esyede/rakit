<?php

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Cache;
use System\Config;
use System\Cookie;
use System\Container;
use System\Database;
use System\Input;
use System\Lang;
use System\Redis;
use System\Request;
use System\Session;
use System\URL;
use System\Validator;
use System\Routing\Middleware;
use System\Routing\Throttle;
use System\Routing\Route;
use System\Routing\Router;
use System\Session\Payload;
use System\Session\Drivers\Memory;
use System\Foundation\Http\Request as Foundation;

/**
 * Regression tests for the findings recorded in BUG.md.
 *
 * Each test is named after the id it locks down, so a failure points straight
 * at the entry that describes what went wrong and why it matters.
 */
class RegressionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Request foundation in place before the test ran.
     *
     * @var mixed
     */
    private $foundation;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->foundation = Request::$foundation;

        // Start from a request of our own, so a test that never sets one up
        // does not inherit whatever the class before it left behind.
        $this->request(Foundation::create('http://localhost/', 'GET'));

        Cookie::flush();
        Session::$instance = null;

        Router::$routes = array_fill_keys(Router::$methods, []);
        Router::$fallback = array_fill_keys(Router::$methods, []);
        Router::$names = [];
        Router::$uses = [];
        Router::$groups = [];
        Router::$group = null;
        Router::$domains = false;

        Middleware::$patterns = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->request($this->foundation);

        Cookie::flush();
        Session::$instance = null;
        Container::$singletons = [];

        Router::$routes = array_fill_keys(Router::$methods, []);
        Router::$fallback = array_fill_keys(Router::$methods, []);
        Router::$names = [];
        Router::$uses = [];
        Router::$groups = [];
        Router::$group = null;
        Router::$domains = false;
    }

    // -------------------------------------------------------------------------
    // K1: CSRF must not be skippable by spoofing the request method
    // -------------------------------------------------------------------------

    /**
     * A POST that spoofs itself as GET is still checked for a token.
     *
     * @group system
     */
    public function testK1SpoofedMethodDoesNotSkipTheCsrfCheck()
    {
        $this->session();

        foreach ([['_method' => 'GET'], []] as $extra) {
            $server = $extra ? [] : ['HTTP_X_HTTP_METHOD_OVERRIDE' => 'GET'];
            $this->request(Foundation::create('/kirim', 'POST', $extra, [], [], $server));

            $this->assertEquals('GET', Request::method());
            $this->assertEquals('POST', Request::real_method());
            $this->assertTrue(Request::forged());
        }
    }

    /**
     * A real GET is still exempt, spoofing or not.
     *
     * @group system
     */
    public function testK1RealReadRequestIsStillExempt()
    {
        $this->session();
        $this->request(Foundation::create('/lihat', 'GET'));

        $this->assertFalse(Request::forged());
    }

    // -------------------------------------------------------------------------
    // K3: nested route groups
    // -------------------------------------------------------------------------

    /**
     * A nested group chains its prefix behind the outer one.
     *
     * @group system
     */
    public function testK3NestedGroupPrefixesAreChained()
    {
        Route::group(['prefix' => 'admin'], function () {
            Route::group(['prefix' => 'users'], function () {
                Route::get('list', function () {
                    return 'ok';
                });
            });
        });

        $this->assertArrayHasKey('admin/users/list', Router::$routes['GET']);
        $this->assertArrayNotHasKey('users/list', Router::$routes['GET']);
    }

    /**
     * A nested group keeps the middlewares of the group it sits in.
     *
     * @group system
     */
    public function testK3NestedGroupKeepsOuterMiddlewares()
    {
        Route::group(['before' => 'auth'], function () {
            Route::group(['before' => 'admin'], function () {
                Route::get('panel', function () {
                    return 'ok';
                });
            });
        });

        $this->assertEquals('auth|admin', Router::$routes['GET']['panel']['before']);
    }

    // -------------------------------------------------------------------------
    // K4: every input reader agrees on where a value comes from
    // -------------------------------------------------------------------------

    /**
     * The body wins over the query string, in all of them.
     *
     * @group system
     */
    public function testK4InputReadersAgreeOnTheSameValue()
    {
        $this->request(Foundation::create('/simpan?peran=user', 'POST', ['peran' => 'admin']));

        $all = Input::all();
        $only = Input::only(['peran']);

        $this->assertEquals('admin', Input::get('peran'));
        $this->assertEquals('admin', $all['peran']);
        $this->assertEquals('admin', $only['peran']);
    }

    /**
     * What gets validated is what the controller reads back.
     *
     * @group system
     */
    public function testK4ValidationSeesTheValueTheControllerUses()
    {
        $this->request(Foundation::create('/simpan?peran=user', 'POST', ['peran' => 'admin']));

        $validation = Validator::make(Input::all(), ['peran' => 'required|in:user,tamu']);

        $this->assertFalse($validation->passes());
    }

    /**
     * The query string is still readable when the body has nothing to say.
     *
     * @group system
     */
    public function testK4QueryStringIsStillRead()
    {
        $this->request(Foundation::create('/cari?q=rakit', 'GET'));

        $all = Input::all();

        $this->assertEquals('rakit', Input::get('q'));
        $this->assertEquals('rakit', $all['q']);
    }

    // -------------------------------------------------------------------------
    // K5, K6: operators and order directions never reach the SQL unchecked
    // -------------------------------------------------------------------------

    /**
     * An operator that is not one the grammar knows is refused.
     *
     * @group system
     */
    public function testK5UnknownWhereOperatorIsRefused()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported SQL operator');

        Database::table('users')->where('id', '> 0 OR 1=1 AND "id" >', 999999)->get();
    }

    /**
     * The shorthand where($column, $value) still works.
     *
     * @group system
     */
    public function testK5TwoArgumentWhereStillWorks()
    {
        $query = Database::table('users')->where('id', 1);

        $this->assertContains('"id" = ?', $query->to_sql());
    }

    /**
     * A join clause refuses an unknown operator too.
     *
     * @group system
     */
    public function testK5UnknownJoinOperatorIsRefused()
    {
        $this->setExpectedException('InvalidArgumentException', 'Unsupported SQL operator');

        Database::table('users')->join('posts', 'users.id', '= 1 OR 1=1 --', 'posts.user_id');
    }

    /**
     * Only asc and desc are accepted as an order direction.
     *
     * @group system
     */
    public function testK6UnknownOrderDirectionIsRefused()
    {
        $this->setExpectedException('InvalidArgumentException', 'Order direction must be');

        Database::table('users')->order_by('id', 'ASC, (SELECT password FROM users LIMIT 1)');
    }

    /**
     * Both directions still compile, in any casing.
     *
     * @group system
     */
    public function testK6BothDirectionsStillCompile()
    {
        $this->assertContains('DESC', Database::table('users')->order_by('id', 'DESC')->to_sql());
        $this->assertContains('ASC', Database::table('users')->order_by('id', 'Asc')->to_sql());
    }

    // -------------------------------------------------------------------------
    // T5: middleware attached by URI pattern
    // -------------------------------------------------------------------------

    /**
     * A pattern may name the middlewares to attach.
     *
     * @group system
     */
    public function testT5PatternAcceptsMiddlewareNames()
    {
        $seen = [];

        Middleware::register('regression_auth', function () use (&$seen) {
            $seen[] = 'auth';
        });
        Middleware::register('regression_admin', function () use (&$seen) {
            $seen[] = 'admin';
        });

        Route::middleware('pattern: admin/*', 'regression_auth|regression_admin');
        Route::get('admin/panel', function () {
            return 'isi';
        });

        $content = Router::route('GET', 'admin/panel')->call()->content;

        $this->assertEquals('isi', $content);
        $this->assertEquals(['auth', 'admin'], $seen);
    }

    /**
     * A pattern may carry its own callback, named or not.
     *
     * @group system
     */
    public function testT5PatternAcceptsCallbacks()
    {
        $seen = [];

        Route::middleware('pattern: api/*', ['name' => 'regression_api', function () use (&$seen) {
            $seen[] = 'bernama';
        }]);

        Route::middleware('pattern: sisi/*', function () use (&$seen) {
            $seen[] = 'telanjang';
        });

        Route::get('api/data', function () {
            return 'satu';
        });
        Route::get('sisi/data', function () {
            return 'dua';
        });

        $this->assertEquals('satu', Router::route('GET', 'api/data')->call()->content);
        $this->assertEquals('dua', Router::route('GET', 'sisi/data')->call()->content);
        $this->assertEquals(['bernama', 'telanjang'], $seen);
    }

    /**
     * A pattern that does not match leaves the route alone.
     *
     * @group system
     */
    public function testT5PatternOnlyAppliesToMatchingUri()
    {
        $seen = false;

        Route::middleware('pattern: admin/*', function () use (&$seen) {
            $seen = true;
        });

        Route::get('publik', function () {
            return 'isi';
        });

        Router::route('GET', 'publik')->call();

        $this->assertFalse($seen);
    }

    // -------------------------------------------------------------------------
    // K7: proxy headers are only read once a proxy is trusted
    // -------------------------------------------------------------------------

    /**
     * A client sending its own CF-Connecting-IP gets no new bucket.
     *
     * @group system
     */
    public function testK7ThrottleIgnoresUntrustedProxyHeader()
    {
        $this->request($this->from('203.0.113.9'));
        $base = Throttle::key();

        $this->request($this->from('203.0.113.9', '198.51.100.7'));

        $this->assertEquals($base, Throttle::key());
    }

    /**
     * Once the proxy is trusted the header is what identifies the client.
     *
     * @group system
     */
    public function testK7ThrottleReadsProxyHeaderWhenTrusted()
    {
        $this->request($this->from('203.0.113.9'));
        $base = Throttle::key();

        Foundation::setTrustedProxies(['203.0.113.9']);
        $this->request($this->from('203.0.113.9', '198.51.100.7'));
        $trusted = Throttle::key();
        Foundation::setTrustedProxies([]);

        $this->assertNotEquals($base, $trusted);
    }

    // -------------------------------------------------------------------------
    // R5: forwarding to a URI nothing handles
    // -------------------------------------------------------------------------

    /**
     * A URI without a route answers 404 instead of ending the request.
     *
     * @group system
     */
    public function testR5ForwardAnswersNotFoundWithoutARoute()
    {
        $response = Route::forward('GET', 'tidak/ada/sama/sekali');

        $this->assertEquals(404, $response->status());
    }

    // -------------------------------------------------------------------------
    // S3: the database cache counter
    // -------------------------------------------------------------------------

    /**
     * Increment reads back what it wrote.
     *
     * @group system
     */
    public function testS3DatabaseCacheIncrementRoundTrips()
    {
        $before = Config::get('cache.database');
        Config::set('cache.database', ['table' => 'regression_caches', 'connection' => null]);

        \System\Database\Schema::drop_if_exists('regression_caches');
        \System\Database\Schema::create('regression_caches', function ($table) {
            $table->string('key')->nullable();
            $table->text('value');
            $table->integer('expiration');
        });

        $driver = new \System\Cache\Drivers\Database('regression.');

        $first = $driver->increment('hit', 5);
        $second = $driver->increment('hit', 5);
        $read = $driver->get('hit');

        \System\Database\Schema::drop_if_exists('regression_caches');
        Config::set('cache.database', $before);

        $this->assertEquals(1, $first);
        $this->assertEquals(2, $second);
        $this->assertEquals(2, $read);
    }

    // -------------------------------------------------------------------------
    // T3: named URLs of domain scoped routes
    // -------------------------------------------------------------------------

    /**
     * The composite route key never reaches the generated URL.
     *
     * @group system
     */
    public function testT3NamedUrlOfDomainRouteHasNoCompositeKey()
    {
        Route::group(['domain' => 'admin.contoh.test'], function () {
            Route::get('panel', ['as' => 'panel', function () {
                return 'ok';
            }]);
        });

        $url = URL::to_route('panel');

        $this->assertNotContains('||', $url);
        $this->assertNotContains('admin.contoh.test', $url);
        $this->assertEquals(URL::to('panel'), $url);
    }

    // -------------------------------------------------------------------------
    // T4, S5, S6, R2: validation rules
    // -------------------------------------------------------------------------

    /**
     * Size rules count the elements of an array.
     *
     * @group system
     */
    public function testT4SizeRulesCountArrayElements()
    {
        $this->assertTrue(Validator::make(['a' => [1, 2, 3]], ['a' => 'array|size:3'])->passes());
        $this->assertTrue(Validator::make(['a' => [1, 2]], ['a' => 'array|between:1,3'])->passes());
        $this->assertTrue(Validator::make(['a' => [1, 2]], ['a' => 'array|min:2'])->passes());
        $this->assertTrue(Validator::make(['a' => [1, 2]], ['a' => 'array|max:2'])->passes());
        $this->assertFalse(Validator::make(['a' => [1, 2, 3]], ['a' => 'array|max:2'])->passes());
    }

    /**
     * An empty array does not count as filled in.
     *
     * @group system
     */
    public function testS5RequiredRefusesAnEmptyArray()
    {
        $this->assertFalse(Validator::make(['tags' => []], ['tags' => 'required'])->passes());
        $this->assertTrue(Validator::make(['tags' => ['a']], ['tags' => 'required'])->passes());
    }

    /**
     * A date has to match the format exactly.
     *
     * @group system
     */
    public function testS6DateFormatIsStrict()
    {
        $this->assertTrue(Validator::make(['a' => '2026-01-01'], ['a' => 'date_format:Y-m-d'])->passes());
        $this->assertFalse(Validator::make(['a' => '2026-1-1'], ['a' => 'date_format:Y-m-d'])->passes());
        $this->assertFalse(Validator::make(['a' => '2026-01-01 10:00'], ['a' => 'date_format:Y-m-d'])->passes());
    }

    /**
     * SVG is only an image when the rule says so.
     *
     * @group system
     */
    public function testR2ImageRuleExcludesSvgByDefault()
    {
        $svg = path('storage').'work'.DS.'probe.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $file = ['tmp_name' => $svg, 'name' => 'probe.svg', 'size' => filesize($svg), 'error' => 0];

        $strict = Validator::make(['berkas' => $file], ['berkas' => 'image'])->passes();
        $lenient = Validator::make(['berkas' => $file], ['berkas' => 'image:allow_svg'])->passes();

        unlink($svg);

        $this->assertFalse($strict);
        $this->assertTrue($lenient);
    }

    // -------------------------------------------------------------------------
    // S4: the file cache counts without losing a request
    // -------------------------------------------------------------------------

    /**
     * Increment reads back what it wrote.
     *
     * @group system
     */
    public function testS4FileCacheIncrementRoundTrips()
    {
        $driver = Cache::driver('file');
        $key = 'regression_file_increment';

        $driver->forget($key);

        $this->assertEquals(1, $driver->increment($key, 5));
        $this->assertEquals(2, $driver->increment($key, 5));
        $this->assertEquals(2, $driver->get($key));

        $driver->forget($key);
    }

    // -------------------------------------------------------------------------
    // T1, T2, S1: the redis cache
    // -------------------------------------------------------------------------

    /**
     * A counter survives being read back.
     *
     * @group system
     */
    public function testT2RedisIncrementSurvivesAGet()
    {
        $driver = $this->redis();
        $key = 'regression_redis_increment';

        $driver->forget($key);

        $this->assertEquals(1, $driver->increment($key, 5));
        $this->assertEquals(2, $driver->increment($key, 5));
        $this->assertEquals(2, $driver->get($key));
        $this->assertEquals(3, $driver->increment($key, 5));

        $driver->forget($key);
    }

    /**
     * Incrementing a counter that put() created does not blow up.
     *
     * @group system
     */
    public function testT1RedisIncrementAfterPutKeepsCounting()
    {
        $driver = $this->redis();
        $key = 'regression_redis_throttle';

        $driver->forget($key);
        $driver->put($key, 1, 5);

        $this->assertEquals(2, $driver->increment($key, 5));
        $this->assertEquals(3, $driver->increment($key, 5));

        $driver->forget($key);
    }

    /**
     * Values still round trip with their type intact.
     *
     * @group system
     */
    public function testT2RedisKeepsValueTypes()
    {
        $driver = $this->redis();
        $key = 'regression_redis_types';

        foreach ([['teks', 'teks'], ['7', '7'], [['a' => 1], ['a' => 1]], [7, 7]] as $pair) {
            $driver->forget($key);
            $driver->put($key, $pair[0], 5);
            $this->assertSame($pair[1], $driver->get($key));
        }

        $driver->forget($key);
    }

    /**
     * Keys are written under the configured cache prefix.
     *
     * @group system
     */
    public function testS1RedisCacheHonoursThePrefix()
    {
        $driver = $this->redis();
        $key = 'regression_redis_prefix';
        $prefix = rtrim((string) Config::get('cache.key'), '.').'.';

        $driver->forget($key);
        $driver->put($key, 'nilai', 5);

        $raw = Redis::db()->get($prefix.$key);
        $value = $driver->get($key);

        Redis::db()->del($prefix.$key);

        $this->assertNotNull($raw);
        $this->assertEquals('nilai', $value);
    }

    // -------------------------------------------------------------------------
    // S7, R10: the container
    // -------------------------------------------------------------------------

    /**
     * An instance counts as registered.
     *
     * @group system
     */
    public function testS7InstanceCountsAsRegistered()
    {
        Container::instance('regression_layanan', new \stdClass());

        $this->assertTrue(Container::registered('regression_layanan'));
    }

    /**
     * A cycle of aliases is reported instead of exhausting the stack.
     *
     * @group system
     */
    public function testR10CircularAliasIsReported()
    {
        Container::register('regression_a', 'regression_b');
        Container::register('regression_b', 'regression_a');

        $this->setExpectedException('Exception', 'Circular dependency');

        try {
            Container::resolve('regression_a');
        } catch (\Exception $e) {
            unset(Container::$registry['regression_a'], Container::$registry['regression_b']);
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // R3, R6, R7, R8, R9: the tail
    // -------------------------------------------------------------------------

    /**
     * A wildcard in $guarded blocks every attribute.
     *
     * @group system
     */
    public function testR3GuardedWildcardBlocksEverything()
    {
        $model = new RegressionGuardedModel();
        $model->fill(['name' => 'Budi', 'is_admin' => 1]);

        $this->assertNull($model->name);
        $this->assertNull($model->is_admin);
    }

    /**
     * The longest placeholder is replaced first.
     *
     * @group system
     */
    public function testR6LanguagePlaceholdersDoNotClobberEachOther()
    {
        $reflection = new \ReflectionProperty('System\Lang', 'lines');
        $reflection->setAccessible(true);
        $before = $reflection->getValue();

        $reflection->setValue(null, ['application' => ['en' => ['regression' => [
            'halo' => 'Halo :nama, selamat datang :nama_lengkap',
        ]]]]);

        $line = Lang::line('regression.halo', [
            'nama' => 'Budi',
            'nama_lengkap' => 'Budi Purnomo',
        ])->get('en');

        $reflection->setValue(null, $before);

        $this->assertEquals('Halo Budi, selamat datang Budi Purnomo', $line);
    }

    /**
     * A JSON list stays a list.
     *
     * @group system
     */
    public function testR7JsonKeepsListsIntact()
    {
        $this->request(Foundation::create('/x', 'POST', [], [], [], [], '{"tags":[1,2,3]}'));
        Input::$json = null;

        $object = Input::json(true);

        $this->assertEquals([1, 2, 3], $object->tags);
        $this->assertEquals(['tags' => [1, 2, 3]], Input::json());
    }

    /**
     * A dot is allowed in a cookie name.
     *
     * @group system
     */
    public function testR8CookieNameMayContainDots()
    {
        Cookie::put('utm.source', 'newsletter');

        $this->assertEquals('newsletter', Cookie::get('utm.source'));
        $this->assertFalse(Cookie::has('utm.tidak.ada'));
    }

    /**
     * Escaping does not leave an entity half done.
     *
     * @group system
     */
    public function testR9EscapeDoubleEncodes()
    {
        $this->assertEquals('&amp;lt;script&amp;gt;', e('&lt;script&gt;'));
        $this->assertEquals('&lt;script&gt;', e('<script>'));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Put a request in place of the current one.
     *
     * @param mixed $foundation
     */
    private function request($foundation)
    {
        Request::$foundation = $foundation;

        $cached = new \ReflectionProperty('System\Request', 'cached_foundation');
        $cached->setAccessible(true);
        $cached->setValue(null, $foundation);
    }

    /**
     * Build a request coming from an address, optionally through a proxy that
     * announces the original client.
     *
     * @param string $remote
     * @param string $forwarded
     *
     * @return \System\Foundation\Http\Request
     */
    private function from($remote, $forwarded = null)
    {
        $server = ['REMOTE_ADDR' => $remote];

        if (! is_null($forwarded)) {
            $server['HTTP_CF_CONNECTING_IP'] = $forwarded;
        }

        return Foundation::create('http://localhost/api', 'GET', [], [], [], $server);
    }

    /**
     * Start a session holding a CSRF token.
     */
    private function session()
    {
        Session::$instance = new Payload(new Memory());
        Session::instance()->load(null);
        Session::put(Session::TOKEN, 'token-regresi');
    }

    /**
     * Get the redis cache driver, skipping the test when no server answers.
     *
     * @return \System\Cache\Drivers\Redis
     */
    private function redis()
    {
        $config = Config::get('database.redis.default');
        $socket = empty($config) ? false : @fsockopen($config['host'], $config['port'], $errno, $errstr, 1);

        if (! $socket) {
            $this->markTestSkipped('Redis server is not reachable.');
        }

        fclose($socket);

        return Cache::driver('redis');
    }
}

class RegressionGuardedModel extends \System\Database\Facile\Model
{
    public static $table = 'regression_guarded';
    public static $guarded = ['*'];
}
