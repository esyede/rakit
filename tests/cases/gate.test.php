<?php

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Blade;
use System\Config;
use System\Gate;
use System\Routing\Middleware;

class GateTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Gate::flush();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Gate::flush();
        Auth::$drivers = [];
        \System\Worker\Worker::$handling = false;
    }

    /**
     * Abilities receive the user and the arguments; undefined ones are denied.
     *
     * @group system
     */
    public function testDefinedAbilities()
    {
        Gate::define('edit', function ($user, $post) {
            return $user->id === $post->owner;
        });

        $owner = (object) ['id' => 1];
        $other = (object) ['id' => 2];
        $post = (object) ['owner' => 1];

        $this->assertTrue(Gate::for_user($owner)->allows('edit', $post));
        $this->assertFalse(Gate::for_user($other)->allows('edit', $post));
        $this->assertTrue(Gate::for_user($other)->denies('edit', $post));
        $this->assertFalse(Gate::for_user($owner)->allows('undefined'));
        $this->assertTrue(Gate::has('edit'));
        $this->assertFalse(Gate::has(['edit', 'undefined']));
    }

    /**
     * Guests only reach callbacks whose user parameter is optional.
     *
     * @group system
     */
    public function testGuestsAreDeniedUnlessTheCallbackAcceptsThem()
    {
        Gate::define('strict', function ($user) {
            return true;
        });

        Gate::define('open', function ($user = null) {
            return true;
        });

        $this->assertFalse(Gate::for_user(null)->allows('strict'));
        $this->assertTrue(Gate::for_user(null)->allows('open'));
    }

    /**
     * Before decides when it answers, after only fills an undecided check.
     *
     * @group system
     */
    public function testBeforeAndAfterCallbacks()
    {
        Gate::define('edit', function ($user) {
            return false;
        });

        Gate::before(function ($user, $ability) {
            return $user->admin ? true : null;
        });

        Gate::after(function ($user, $ability, $result) {
            return 'report' === $ability;
        });

        $admin = (object) ['admin' => true];
        $member = (object) ['admin' => false];

        $this->assertTrue(Gate::for_user($admin)->allows('edit'));
        $this->assertFalse(Gate::for_user($member)->allows('edit'));
        $this->assertTrue(Gate::for_user($member)->allows('report'));
    }

    /**
     * Policies answer for their model, its subclasses, and its class name.
     *
     * @group system
     */
    public function testPolicies()
    {
        Gate::policy('GateTestPost', 'GateTestPostPolicy');

        $author = (object) ['id' => 5, 'banned' => false];
        $banned = (object) ['id' => 5, 'banned' => true];
        $post = new GateTestPost(5);

        $this->assertTrue(Gate::for_user($author)->allows('update', $post));
        $this->assertTrue(Gate::for_user($author)->allows('update', new GateTestDraft(5)));
        $this->assertFalse(Gate::for_user($author)->allows('update', new GateTestPost(6)));
        $this->assertFalse(Gate::for_user($banned)->allows('update', $post));
        $this->assertTrue(Gate::for_user($author)->allows('create', 'GateTestPost'));
        $this->assertTrue(Gate::for_user(null)->allows('view', $post));
        $this->assertFalse(Gate::for_user(null)->allows('update', $post));
        $this->assertInstanceOf('GateTestPostPolicy', Gate::get_policy_for($post));
        $this->assertNull(Gate::get_policy_for(new \stdClass()));
    }

    /**
     * check(), any() and none() combine abilities; 'Class@method' callbacks work.
     *
     * @group system
     */
    public function testCombinedChecksAndClassCallbacks()
    {
        Gate::define('yes', 'GateTestAbilities@yes');
        Gate::define('no', function ($user) {
            return false;
        });

        $user = (object) ['id' => 1];

        $this->assertTrue(Gate::for_user($user)->check('yes'));
        $this->assertFalse(Gate::for_user($user)->check(['yes', 'no']));
        $this->assertTrue(Gate::for_user($user)->any(['yes', 'no']));
        $this->assertFalse(Gate::for_user($user)->none(['yes', 'no']));
        $this->assertTrue(Gate::for_user($user)->none(['no']));
    }

    /**
     * Static calls check the logged in user.
     *
     * @group system
     */
    public function testStaticCallsUseTheLoggedInUser()
    {
        Auth::$drivers[Config::get('auth.driver')] = new GateTestAuth((object) ['id' => 9]);

        Gate::define('nine', function ($user) {
            return 9 === $user->id;
        });

        $this->assertTrue(Gate::allows('nine'));
        $this->assertFalse(Gate::denies('nine'));
    }

    /**
     * Internal methods are not reachable through the magic calls.
     *
     * @group system
     */
    public function testOnlyCheckMethodsAreForwarded()
    {
        $this->setExpectedException('BadMethodCallException');
        Gate::for_user(null)->raw('edit', []);
    }

    /**
     * authorize() passes through, or answers 403 (thrown inside a worker request).
     *
     * @group system
     */
    public function testAuthorize()
    {
        Gate::define('yes', function ($user) {
            return true;
        });

        $user = (object) ['id' => 1];
        $this->assertTrue(Gate::for_user($user)->authorize('yes'));

        \System\Worker\Worker::$handling = true;

        try {
            Gate::for_user($user)->authorize('no');
            $this->fail('Authorized an undefined ability');
        } catch (\System\Exceptions\HttpResponseException $e) {
            $this->assertEquals(403, $e->getResponse()->status());
        }
    }

    /**
     * The Blade directives compile to Gate calls, @canany and @cannot included.
     *
     * @group system
     */
    public function testBladeDirectives()
    {
        $compiled = Blade::translate("@can('edit', \$post) a @endcan @cannot('edit') b @endcannot @canany(['x', 'y'], \$post) c @endcanany");

        $this->assertContains("<?php if (\\System\\Gate::allows('edit', \$post)): ?>", $compiled);
        $this->assertContains("<?php if (\\System\\Gate::denies('edit')): ?>", $compiled);
        $this->assertContains("<?php if (\\System\\Gate::any(['x', 'y'], \$post)): ?>", $compiled);
        $this->assertEquals(3, substr_count($compiled, '<?php endif; ?>'));
        $this->assertNotContains('@', $compiled);
    }

    /**
     * The 'can' middleware answers 403 when the ability is denied.
     *
     * @group system
     */
    public function testCanMiddleware()
    {
        Auth::$drivers[Config::get('auth.driver')] = new GateTestAuth((object) ['id' => 9, 'banned' => false]);
        Gate::policy('GateTestPost', 'GateTestPostPolicy');
        Gate::define('admin', function ($user) {
            return false;
        });

        $can = Middleware::$middlewares['can'];

        $this->assertNull($can('create', 'GateTestPost'));
        $this->assertEquals(403, $can('admin')->status());
    }
}

class GateTestPost
{
    public $author;

    public function __construct($author)
    {
        $this->author = $author;
    }
}

class GateTestDraft extends GateTestPost
{
}

class GateTestPostPolicy
{
    public function before($user = null, $ability = null)
    {
        if ($user && $user->banned) {
            return false;
        }
    }

    public function view($user = null, $post = null)
    {
        return true;
    }

    public function update($user, $post)
    {
        return $user->id === $post->author;
    }

    public function create($user)
    {
        return true;
    }
}

class GateTestAbilities
{
    public function yes($user)
    {
        return true;
    }
}

class GateTestAuth
{
    protected $user;

    public function __construct($user)
    {
        $this->user = $user;
    }

    public function user()
    {
        return $this->user;
    }
}
