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
use System\Autoloader;
use System\Blade;
use System\Curl;
use System\Image;
use System\JWT;
use System\RSA;
use System\Response;
use System\Str;
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

        $this->cleanup($this->work().'autoload'.DS);
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
        $svg = $this->work().'probe.svg';
        file_put_contents($svg, '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $file = ['tmp_name' => $svg, 'name' => 'probe.svg', 'size' => filesize($svg), 'error' => 0];

        $recognised = \System\Storage::is('svg', $svg);
        $strict = Validator::make(['berkas' => $file], ['berkas' => 'image'])->passes();
        $lenient = Validator::make(['berkas' => $file], ['berkas' => 'image:allow_svg'])->passes();

        unlink($svg);

        if (! $recognised) {
            $this->markTestSkipped('This system does not identify the file as an SVG.');
        }

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
    // K8: a public key cannot be turned into an HMAC secret
    // -------------------------------------------------------------------------

    /**
     * A token that swaps RS256 for HS256 is refused even without naming the
     * algorithm, because the key material already says which ones fit.
     *
     * @group system
     */
    public function testK8JwtRefusesAlgorithmConfusion()
    {
        list($private, $public) = $this->keypair();

        $genuine = JWT::encode(['sub' => 'budi'], $private, [], 'RS256');
        $this->assertEquals('budi', JWT::decode($genuine, $public)->sub);

        $forged = JWT::encode(['sub' => 'admin'], $public, [], 'HS256');

        $this->setExpectedException('Exception', 'Algorithm not allowed');

        JWT::decode($forged, $public);
    }

    /**
     * A shared secret still signs and verifies with HS256.
     *
     * @group system
     */
    public function testK8JwtStillAcceptsSymmetricKeys()
    {
        $token = JWT::encode(['sub' => 'budi'], 'rahasia-bersama');

        $this->assertEquals('budi', JWT::decode($token, 'rahasia-bersama')->sub);
    }

    /**
     * A shared secret cannot be used to claim an RSA signature.
     *
     * @group system
     */
    public function testK8JwtRefusesAsymmetricAlgorithmForASharedSecret()
    {
        list($private) = $this->keypair();
        $token = JWT::encode(['sub' => 'admin'], $private, [], 'RS256');

        $this->setExpectedException('Exception', 'Algorithm not allowed');

        JWT::decode($token, 'rahasia-bersama');
    }

    // -------------------------------------------------------------------------
    // T6: an unknown Str method
    // -------------------------------------------------------------------------

    /**
     * It reports the name instead of recursing until the stack gives out.
     *
     * @group system
     */
    public function testT6UnknownStrMethodThrows()
    {
        $this->setExpectedException('BadMethodCallException', 'Method does not exist: metode_yang_tidak_ada');

        Str::metode_yang_tidak_ada('x');
    }

    /**
     * A macro is still reachable.
     *
     * @group system
     */
    public function testT6StrMacroStillWorks()
    {
        Str::macro('regression_balik', function ($value) {
            return strrev($value);
        });

        $this->assertEquals('cba', Str::regression_balik('abc'));
    }

    // -------------------------------------------------------------------------
    // T7, S8, S9: validation rules
    // -------------------------------------------------------------------------

    /**
     * A rule written with '*' runs against every element.
     *
     * @group system
     */
    public function testT7WildcardRulesReachEveryElement()
    {
        $this->assertTrue(Validator::make(['a' => [1, 2]], ['a.*' => 'integer'])->passes());
        $this->assertFalse(Validator::make(['a' => [1, 'x']], ['a.*' => 'integer'])->passes());
    }

    /**
     * It reaches into nested structures too.
     *
     * @group system
     */
    public function testT7WildcardRulesReachNestedKeys()
    {
        $data = ['orang' => [['nama' => 'Budi'], ['nama' => '']]];

        $this->assertFalse(Validator::make($data, ['orang.*.nama' => 'required'])->passes());

        $data = ['orang' => [['nama' => 'Budi'], ['nama' => 'Ani']]];

        $this->assertTrue(Validator::make($data, ['orang.*.nama' => 'required'])->passes());
    }

    /**
     * An error names the element it came from.
     *
     * @group system
     */
    public function testT7WildcardErrorsNameTheElement()
    {
        $validation = Validator::make(['a' => [1, 'x']], ['a.*' => 'integer']);
        $validation->passes();

        $this->assertTrue($validation->errors->has('a.1'));
        $this->assertFalse($validation->errors->has('a.0'));
    }

    /**
     * in_array reads the array the parameter names, '*' and all.
     *
     * @group system
     */
    public function testS8InArrayAcceptsTheDocumentedForm()
    {
        $data = ['colors' => ['merah', 'hijau'], 'color' => 'merah'];

        $this->assertTrue(Validator::make($data, ['color' => 'in_array:colors.*'])->passes());
        $this->assertTrue(Validator::make($data, ['color' => 'in_array:colors'])->passes());

        $data['color'] = 'ungu';

        $this->assertFalse(Validator::make($data, ['color' => 'in_array:colors.*'])->passes());
    }

    /**
     * A zero is a value like any other.
     *
     * @group system
     */
    public function testS9FilledAcceptsZero()
    {
        $this->assertTrue(Validator::make(['a' => '0'], ['a' => 'filled'])->passes());
        $this->assertTrue(Validator::make(['a' => 0], ['a' => 'filled'])->passes());
        $this->assertFalse(Validator::make(['a' => ''], ['a' => 'filled'])->passes());
        $this->assertFalse(Validator::make(['a' => []], ['a' => 'filled'])->passes());
    }

    // -------------------------------------------------------------------------
    // R11: the download filename
    // -------------------------------------------------------------------------

    /**
     * A name from the request cannot end the quoted string or start a header.
     *
     * @group system
     */
    public function testR11DownloadFilenameIsSanitized()
    {
        $method = new \ReflectionMethod('System\Response', 'disposition');
        $method->setAccessible(true);

        $nasty = $method->invoke(null, 'attachment', 'laporan.txt"; filename*=UTF-8\'\'evil.exe');
        $crlf = $method->invoke(null, 'attachment', "a.txt\r\nX-Injected: ya");
        $walk = $method->invoke(null, 'attachment', '../../etc/passwd');

        $this->assertNotContains('"; filename*', $nasty);
        $this->assertNotContains("\r", $crlf);
        $this->assertNotContains("\n", $crlf);
        $this->assertEquals('attachment; filename="passwd"', $walk);
        $this->assertEquals('attachment; filename="download"', $method->invoke(null, 'attachment', ''));
    }

    // -------------------------------------------------------------------------
    // T8: RSA chunking
    // -------------------------------------------------------------------------

    /**
     * A chunk that reads as falsy is still a chunk.
     *
     * @group system
     */
    public function testT8RsaEncryptsAZero()
    {
        $cipher = RSA::encrypt('0');

        $this->assertNotEquals('', $cipher);
        $this->assertEquals('0', RSA::decrypt($cipher));
    }

    /**
     * Data whose last chunk is a zero keeps that chunk.
     *
     * @group system
     */
    public function testT8RsaKeepsATrailingZeroChunk()
    {
        $data = str_repeat('a', 245).'0';
        $cipher = RSA::encrypt($data);

        $this->assertEquals(512, mb_strlen($cipher, '8bit'));
        $this->assertEquals($data, RSA::decrypt($cipher));
    }

    /**
     * Ordinary data still round trips, over more than one chunk.
     *
     * @group system
     */
    public function testT8RsaRoundTripsLongData()
    {
        $data = str_repeat('rakit ', 200);

        $this->assertEquals($data, RSA::decrypt(RSA::encrypt($data)));
    }

    // -------------------------------------------------------------------------
    // T9, S10: the autoloader
    // -------------------------------------------------------------------------

    /**
     * Two namespaces may each hold a class of the same short name.
     *
     * @group system
     */
    public function testT9NamespacesMayShareAClassName()
    {
        $base = $this->fixtures();

        Autoloader::namespaces([
            'RegresiSatu' => $base.'satu',
            'RegresiDua' => $base.'dua',
        ]);

        $this->assertEquals('satu', \RegresiSatu\Kotak::asal());
        $this->assertEquals('dua', \RegresiDua\Kotak::asal());
    }

    /**
     * The longest matching namespace wins, whatever order they were added in.
     *
     * @group system
     */
    public function testS10LongestNamespacePrefixWins()
    {
        $base = $this->fixtures();

        Autoloader::namespaces(['RegresiLuar\Dalam' => $base.'dalam']);
        Autoloader::namespaces(['RegresiLuar' => $base.'tidak-ada']);

        $this->assertEquals('spesifik', \RegresiLuar\Dalam\Kotak::asal());
    }

    // -------------------------------------------------------------------------
    // T10, T11, S11: Facile
    // -------------------------------------------------------------------------

    /**
     * A model fetched with with_trashed() can be restored.
     *
     * @group system
     */
    public function testT10RestoreWorksOnAFetchedModel()
    {
        $this->facile();

        RegressionArtikel::find(1)->delete();
        $this->assertEquals(2, RegressionArtikel::all()->count());

        $trashed = RegressionArtikel::with_trashed()->where('id', '=', 1)->first();

        $this->assertTrue($trashed->restore());
        $this->assertEquals(3, RegressionArtikel::all()->count());
    }

    /**
     * Eager loading leaves soft deleted rows out, the way lazy loading does.
     *
     * @group system
     */
    public function testT11EagerLoadingHonoursSoftDeletes()
    {
        $this->facile();

        RegressionArtikel::find(2)->delete();

        $lazy = count(RegressionPenulis::find(1)->artikel);
        $eager = RegressionPenulis::with('artikel')->get()->all();

        $this->assertEquals(1, $lazy);
        $this->assertEquals(1, count($eager[0]->artikel));
    }

    /**
     * has(), where_has() and with_count() leave them out too.
     *
     * @group system
     */
    public function testT11RelationQueriesHonourSoftDeletes()
    {
        $this->facile();

        // Penulis 3 keeps one article, and it is deleted.
        RegressionArtikel::find(3)->delete();

        $counted = RegressionPenulis::with_count('artikel')->get()->all();

        $this->assertEquals(1, RegressionPenulis::has('artikel')->get()->count());
        $this->assertEquals(1, RegressionPenulis::where_has('artikel', function ($query) {
        })->get()->count());
        $this->assertEquals(2, $counted[0]->artikel_count);
        $this->assertEquals(0, $counted[1]->artikel_count);
    }

    /**
     * A pivot table holding nothing but the two keys is enough.
     *
     * @group system
     */
    public function testS11BelongsToManyNeedsNoExtraPivotColumns()
    {
        $this->facile();

        $tags = RegressionArtikel::find(1)->tag;

        $this->assertEquals(2, count($tags));
        $this->assertNull($tags[0]->pivot->catatan);
    }

    /**
     * A pivot column is readable once it is asked for.
     *
     * @group system
     */
    public function testS11PivotColumnsAreOptIn()
    {
        $this->facile();

        $tags = RegressionArtikel::find(1)->tag()->with(['catatan'])->get();

        $this->assertEquals('x', $tags[0]->pivot->catatan);
    }

    // -------------------------------------------------------------------------
    // S12: image paths
    // -------------------------------------------------------------------------

    /**
     * A source path is read where path() puts it, not where the process
     * happens to be standing.
     *
     * @group system
     */
    public function testS12ImageReadsTheResolvedPath()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        $this->work();

        $relative = 'tests/fixtures/storage/work/regression_src.png';
        $absolute = path('base').str_replace('/', DS, $relative);

        $canvas = imagecreatetruecolor(4, 4);
        imagepng($canvas, $absolute);
        imagedestroy($canvas);

        $cwd = getcwd();
        chdir(sys_get_temp_dir());

        try {
            $info = Image::open($relative)->info();
            $width = $info['width'];
        } catch (\Exception $e) {
            chdir($cwd);
            unlink($absolute);

            throw $e;
        }

        chdir($cwd);
        unlink($absolute);

        $this->assertEquals(4, $width);
    }

    /**
     * A second open() loads the file it was given.
     *
     * @group system
     */
    public function testS12ImageOpenLoadsEveryFileItIsGiven()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        $this->work();

        $first = 'tests/fixtures/storage/work/regression_a.png';
        $second = 'tests/fixtures/storage/work/regression_b.png';

        foreach ([[$first, 4], [$second, 9]] as $pair) {
            $canvas = imagecreatetruecolor($pair[1], $pair[1]);
            imagepng($canvas, path('base').str_replace('/', DS, $pair[0]));
            imagedestroy($canvas);
        }

        $a = Image::open($first)->info();
        $b = Image::open($second)->info();

        @unlink(path('base').str_replace('/', DS, $first));
        @unlink(path('base').str_replace('/', DS, $second));

        $this->assertEquals(4, $a['width']);
        $this->assertEquals(9, $b['width']);
    }

    /**
     * The overwrite guard looks at the file export would actually write.
     *
     * @group system
     */
    public function testS12ImageOverwriteGuardChecksTheResolvedPath()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        $this->work();

        $source = 'tests/fixtures/storage/work/regression_src.png';
        $target = 'tests/fixtures/storage/work/regression_out.png';
        $paths = [path('base').str_replace('/', DS, $source), path('base').str_replace('/', DS, $target)];

        $canvas = imagecreatetruecolor(4, 4);
        imagepng($canvas, $paths[0]);
        imagedestroy($canvas);
        @unlink($paths[1]);

        $cwd = getcwd();
        chdir(sys_get_temp_dir());

        Image::open($source)->export($target);
        $refused = false;

        try {
            Image::open($source)->export($target);
        } catch (\Exception $e) {
            $refused = true;
        }

        chdir($cwd);
        @unlink($paths[0]);
        @unlink($paths[1]);

        $this->assertTrue($refused);
    }

    // -------------------------------------------------------------------------
    // T14, S13: image output
    // -------------------------------------------------------------------------

    /**
     * identicon() hands back the PNG instead of printing it.
     *
     * @group system
     */
    public function testT14IdenticonReturnsTheImage()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        ob_start();
        $png = Image::identicon('budi', 32);
        $leaked = ob_get_clean();

        $this->assertStringStartsWith("\x89PNG", $png);
        $this->assertEquals('', $leaked);
        $this->assertEquals($png, Image::identicon('budi', 32));
        $this->assertNotEquals($png, Image::identicon('ani', 32));
    }

    /**
     * Asking for a response gives one carrying the image, not a boolean.
     *
     * @group system
     */
    public function testT14IdenticonResponseCarriesTheImage()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        ob_start();
        $response = Image::identicon('budi', 32, true);
        $leaked = ob_get_clean();

        $this->assertInstanceOf('System\Response', $response);
        $this->assertEquals('image/png', $response->foundation()->headers->get('Content-Type'));
        $this->assertStringStartsWith("\x89PNG", $response->content);
        $this->assertEquals('', $leaked);
    }

    /**
     * A size GD cannot work with is reported before it gets there.
     *
     * @group system
     */
    public function testS13ImageRefusesAnEmptySize()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        $this->work();

        $relative = 'tests/fixtures/storage/work/regression_size.png';
        $absolute = path('base').str_replace('/', DS, $relative);

        $canvas = imagecreatetruecolor(8, 8);
        imagepng($canvas, $absolute);
        imagedestroy($canvas);

        $caught = 0;

        foreach ([function ($image) {
            $image->width(0);
        }, function ($image) {
            $image->crop(0, 0, 0, 0);
        }] as $call) {
            try {
                $call(Image::open($relative));
            } catch (\Exception $e) {
                $caught += (false === strpos($e->getMessage(), 'at least 1x1')) ? 0 : 1;
            }
        }

        unlink($absolute);

        $this->assertEquals(2, $caught);
    }

    /**
     * export() writes every format it claims to support, whatever case the
     * extension was written in.
     *
     * @group system
     */
    public function testS17ImageExportsEveryFormatItClaims()
    {
        if (! Image::available()) {
            $this->markTestSkipped('The GD extension is not available.');
        }

        $this->work();

        $relative = 'tests/fixtures/storage/work/regression_fmt.png';
        $absolute = path('base').str_replace('/', DS, $relative);

        $canvas = imagecreatetruecolor(4, 4);
        imagepng($canvas, $absolute);
        imagedestroy($canvas);

        $written = [];
        $refused = [];

        foreach (['out.gif', 'out.jpg', 'out.jpeg', 'out.png', 'out.JPG', 'out.Gif'] as $name) {
            $target = 'tests/fixtures/storage/work/regression_'.$name;
            $path = path('base').str_replace('/', DS, $target);
            @unlink($path);

            try {
                Image::open($relative)->export($target);
                $written[] = (is_file($path) && filesize($path) > 0) ? $name : $name.' (kosong)';
            } catch (\Exception $e) {
                $refused[] = $name;
            }

            @unlink($path);
        }

        unlink($absolute);

        $this->assertEquals([], $refused);
        $this->assertEquals(['out.gif', 'out.jpg', 'out.jpeg', 'out.png', 'out.JPG', 'out.Gif'], $written);
    }

    // -------------------------------------------------------------------------
    // S14: curl state
    // -------------------------------------------------------------------------

    /**
     * reset() puts back everything a previous call set, credentials included.
     *
     * @group system
     */
    public function testS14CurlResetClearsCredentials()
    {
        Curl::auth('pengguna', 'sandi');
        Curl::cookie('a=b');

        $auth = new \ReflectionProperty('System\Curl', 'auth');
        PHP_VERSION_ID < 80100 && $auth->setAccessible(true);
        $cookie = new \ReflectionProperty('System\Curl', 'cookie');
        PHP_VERSION_ID < 80100 && $cookie->setAccessible(true);

        $before = $auth->getValue();

        Curl::reset();

        $this->assertEquals('pengguna', $before['user']);
        $this->assertEquals('', $auth->getValue() === null ? '' : $auth->getValue()['user']);
        $this->assertFalse($cookie->getValue());
    }

    // -------------------------------------------------------------------------
    // T15, S15: websocket frames
    // -------------------------------------------------------------------------

    /**
     * A frame shorter than its own header is reported as partial instead of
     * being read past the end.
     *
     * @group system
     */
    public function testS15ShortWebsocketFrameIsPartial()
    {
        $server = new \System\Websocket\Server('tcp://127.0.0.1:0');
        $method = new \ReflectionMethod('System\Websocket\Server', 'extract_headers');
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        foreach (["\x81", '', "\x81\xFE", "\x81\xFF"] as $frame) {
            $headers = $method->invokeArgs($server, [$frame]);

            $this->assertTrue($headers['partial'], 'frame of '.strlen($frame).' bytes');
            $this->assertEquals(0, $headers['length']);
        }
    }

    /**
     * A complete frame is still read the way it was.
     *
     * @group system
     */
    public function testS15CompleteWebsocketFrameIsRead()
    {
        $server = new \System\Websocket\Server('tcp://127.0.0.1:0');
        $method = new \ReflectionMethod('System\Websocket\Server', 'extract_headers');
        PHP_VERSION_ID < 80100 && $method->setAccessible(true);

        $headers = $method->invokeArgs($server, ["\x81\x84abcd"]);

        $this->assertFalse($headers['partial']);
        $this->assertEquals(4, $headers['length']);
        $this->assertEquals(1, $headers['opcode']);
        $this->assertEquals('abcd', $headers['mask']);
    }

    /**
     * The server knows how big a message it is willing to buffer.
     *
     * @group system
     */
    public function testT15WebsocketHasAPayloadLimit()
    {
        $server = new \System\Websocket\Server('tcp://127.0.0.1:0');
        $config = new \ReflectionProperty('System\Websocket\Server', 'config');
        PHP_VERSION_ID < 80100 && $config->setAccessible(true);

        $value = $config->getValue($server);
        $this->assertArrayHasKey('max_payload_size', $value);
        $this->assertGreaterThan(0, $value['max_payload_size']);
    }

    // -------------------------------------------------------------------------
    // T19, S18: blade sections
    // -------------------------------------------------------------------------

    /**
     * @show ends the section and prints it, instead of raising.
     *
     * @group system
     */
    public function testT19ShowEndsAndPrintsTheSection()
    {
        $this->assertEquals('Home', $this->blade("@section('nav')Home@show"));
    }

    /**
     * A child section inherits what the layout put there.
     *
     * @group system
     */
    public function testT19ParentInheritsTheLayoutSection()
    {
        \System\Section::$sections = [];

        // The child runs first, then the layout, which is the order @layout
        // compiles to.
        $this->blade("@section('nav')@parent<li>Kontak</li>@endsection");
        $out = $this->blade("@section('nav')<li>Home</li>@show");

        \System\Section::$sections = [];

        $this->assertEquals('<li>Home</li><li>Kontak</li>', $out);
    }

    /**
     * A @parent with nothing to inherit drops out of the page.
     *
     * @group system
     */
    public function testS18StrayParentDoesNotReachThePage()
    {
        \System\Section::$sections = [];

        $this->blade("@section('nav')@parent<li>Kontak</li>@endsection");
        $out = \System\Section::yield_content('nav');

        \System\Section::$sections = [];

        $this->assertEquals('<li>Kontak</li>', trim($out));
        $this->assertNotContains('@parent', $out);
    }

    /**
     * Two directives on one line are compiled one at a time.
     *
     * @group system
     */
    public function testS18DirectivesShareALineCleanly()
    {
        $this->assertEquals(
            '<?php echo yield_content("a") ?><?php echo yield_content("b") ?>',
            Blade::translate('@yield("a")@yield("b")')
        );

        $this->assertEquals(
            '<?php section_start("nav") ?>Home<?php echo yield_section() ?>',
            Blade::translate('@section("nav")Home@show')
        );
    }

    /**
     * A malformed forelse is left alone instead of raising a warning and
     * emitting count() with nothing in it.
     *
     * @group system
     */
    public function testS18MalformedForelseIsLeftAlone()
    {
        $compiled = Blade::translate('@forelse($a)x@endforelse');

        $this->assertContains('@forelse($a)', $compiled);
        $this->assertNotContains('count()', $compiled);
    }

    // -------------------------------------------------------------------------
    // K9: the Host header decides generated URLs
    // -------------------------------------------------------------------------

    /**
     * A request naming a host the application does not answer to is refused.
     *
     * @group system
     */
    public function testK9UntrustedHostIsRefused()
    {
        Foundation::setTrustedHosts(['situs-asli.test', '*.situs-asli.test']);

        $accepted = [];
        $refused = [];

        foreach (['situs-asli.test', 'app.situs-asli.test', 'situs-asli.test:8080',
            'jahat.test', 'situs-asli.test.jahat.test', ] as $host) {
            $request = Foundation::create('http://x/', 'GET', [], [], [], ['HTTP_HOST' => $host]);

            try {
                $accepted[] = $request->getHost();
            } catch (\UnexpectedValueException $e) {
                $refused[] = $host;
            }
        }

        Foundation::setTrustedHosts([]);

        $this->assertEquals(['situs-asli.test', 'app.situs-asli.test', 'situs-asli.test'], $accepted);
        $this->assertEquals(['jahat.test', 'situs-asli.test.jahat.test'], $refused);
    }

    /**
     * An empty list accepts whatever arrives, which is what development wants.
     *
     * @group system
     */
    public function testK9EmptyTrustedHostListAcceptsAnything()
    {
        Foundation::setTrustedHosts([]);

        $request = Foundation::create('http://x/', 'GET', [], [], [], ['HTTP_HOST' => 'apa-saja.test']);

        $this->assertEquals('apa-saja.test', $request->getHost());
    }

    // -------------------------------------------------------------------------
    // T17, T18, S16: blade
    // -------------------------------------------------------------------------

    /**
     * forelse falls through to its empty half instead of dying there.
     *
     * @group system
     */
    public function testT17ForelseHandlesAnEmptyCollection()
    {
        $this->assertEquals('tidak ada', $this->blade(
            '@forelse ($items as $i){{ $i }}@empty tidak ada @endforelse',
            ['items' => []]
        ));

        $this->assertEquals('12', $this->blade(
            '@forelse ($items as $i){{ $i }}@empty tidak ada @endforelse',
            ['items' => [1, 2]]
        ));
    }

    /**
     * A standalone @empty is its own directive, not the half of a forelse.
     *
     * @group system
     */
    public function testT18StandaloneEmptyDirectiveCompiles()
    {
        $this->assertEquals('kosong', $this->blade('@empty($x)kosong@endempty', ['x' => []]));
        $this->assertEquals('', $this->blade('@empty($x)kosong@endempty', ['x' => ['a']]));
    }

    /**
     * Directives nested on one line are read one at a time.
     *
     * @group system
     */
    public function testS16NestedDirectivesOnOneLine()
    {
        $this->assertEquals('luar dalam', $this->blade(
            '@if ($a) luar @if ($b) dalam @endif @endif',
            ['a' => true, 'b' => true]
        ));

        $this->assertEquals('luar', $this->blade(
            '@if ($a) luar @if ($b) dalam @endif @endif',
            ['a' => true, 'b' => false]
        ));
    }

    /**
     * A condition of its own may hold parentheses.
     *
     * @group system
     */
    public function testS16ConditionsMayHoldParentheses()
    {
        $this->assertEquals('ya', $this->blade('@if (count($a) > 0)ya@endif', ['a' => [1]]));
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Compile and run a blade snippet, answering what it printed.
     *
     * @param string $source
     * @param array  $data
     *
     * @return string
     */
    private function blade($source, array $data = [])
    {
        $compiled = Blade::translate($source);

        extract($data, EXTR_SKIP);
        ob_start();
        eval('?>'.$compiled);

        return trim(preg_replace('/\s+/', ' ', (string) ob_get_clean()));
    }

    /**
     * Build the tables and rows the Facile tests read.
     */
    private function facile()
    {
        \System\Database\Schema::drop_if_exists('regression_artikel_tag');
        \System\Database\Schema::drop_if_exists('regression_artikel');
        \System\Database\Schema::drop_if_exists('regression_penulis');
        \System\Database\Schema::drop_if_exists('regression_tag');

        \System\Database\Schema::create('regression_penulis', function ($table) {
            $table->increments('id');
            $table->string('nama');
        });

        \System\Database\Schema::create('regression_artikel', function ($table) {
            $table->increments('id');
            $table->integer('penulis_id');
            $table->string('judul');
            $table->timestamp('deleted_at')->nullable();
        });

        \System\Database\Schema::create('regression_tag', function ($table) {
            $table->increments('id');
            $table->string('nama');
        });

        // Nothing but the two keys and one extra column, the way the docs
        // describe a pivot table.
        \System\Database\Schema::create('regression_artikel_tag', function ($table) {
            $table->integer('artikel_id');
            $table->integer('tag_id');
            $table->string('catatan')->nullable();
        });

        Database::table('regression_penulis')->insert([
            ['id' => 1, 'nama' => 'Budi'],
            ['id' => 2, 'nama' => 'Ani'],
        ]);

        Database::table('regression_artikel')->insert([
            ['id' => 1, 'penulis_id' => 1, 'judul' => 'A', 'deleted_at' => null],
            ['id' => 2, 'penulis_id' => 1, 'judul' => 'B', 'deleted_at' => null],
            ['id' => 3, 'penulis_id' => 2, 'judul' => 'C', 'deleted_at' => null],
        ]);

        Database::table('regression_tag')->insert([
            ['id' => 1, 'nama' => 'php'],
            ['id' => 2, 'nama' => 'web'],
        ]);

        Database::table('regression_artikel_tag')->insert([
            ['artikel_id' => 1, 'tag_id' => 1, 'catatan' => 'x'],
            ['artikel_id' => 1, 'tag_id' => 2, 'catatan' => 'y'],
        ]);
    }

    /**
     * Get the scratch directory, making it when a fresh checkout has none.
     * Git does not carry empty directories, so it cannot be assumed to exist.
     *
     * @return string
     */
    private function work()
    {
        $path = path('storage').'work'.DS;

        if (! is_dir($path)) {
            mkdir($path, 0777, true);
        }

        return $path;
    }

    /**
     * Delete a directory of class files the autoloader tests wrote.
     *
     * @param string $path
     */
    private function cleanup($path)
    {
        if (! is_dir($path)) {
            return;
        }

        foreach ((array) glob($path.'*', GLOB_ONLYDIR) as $directory) {
            foreach ((array) glob($directory.DS.'*.php') as $file) {
                @unlink($file);
            }

            @rmdir($directory);
        }

        @rmdir($path);
    }

    /**
     * Write the class files the autoloader tests look for.
     *
     * @return string
     */
    private function fixtures()
    {
        $base = $this->work().'autoload'.DS;

        $files = [
            'satu'.DS.'Kotak.php' => 'namespace RegresiSatu; class Kotak { public static function asal() { return "satu"; } }',
            'dua'.DS.'Kotak.php' => 'namespace RegresiDua; class Kotak { public static function asal() { return "dua"; } }',
            'dalam'.DS.'Kotak.php' => 'namespace RegresiLuar\\Dalam; class Kotak { public static function asal() { return "spesifik"; } }',
        ];

        foreach ($files as $path => $source) {
            $file = $base.$path;
            $directory = dirname($file);

            if (! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            if (! is_file($file)) {
                file_put_contents($file, '<?php'.LF.LF.$source.LF);
            }
        }

        return $base;
    }

    /**
     * Generate an RSA key pair for the JWT tests.
     *
     * @return array
     */
    private function keypair()
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if (false === $resource) {
            $this->markTestSkipped('OpenSSL cannot generate a key pair here.');
        }

        openssl_pkey_export($resource, $private);
        $details = openssl_pkey_get_details($resource);

        return [$private, $details['key']];
    }

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

class RegressionPenulis extends \System\Database\Facile\Model
{
    public static $table = 'regression_penulis';
    public static $timestamps = false;

    public function artikel()
    {
        return $this->has_many('RegressionArtikel', 'penulis_id');
    }
}

class RegressionArtikel extends \System\Database\Facile\Model
{
    public static $table = 'regression_artikel';
    public static $timestamps = false;
    public static $soft_delete = true;

    public function tag()
    {
        return $this->belongs_to_many('RegressionTag', 'regression_artikel_tag', 'artikel_id', 'tag_id');
    }
}

class RegressionTag extends \System\Database\Facile\Model
{
    public static $table = 'regression_tag';
    public static $timestamps = false;
}
