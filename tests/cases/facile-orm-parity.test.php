<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;
use System\Database\Facile\Model;

class FacileOrmParityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The default connection before the test.
     *
     * @var string
     */
    protected $connection;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->connection = Config::get('database.default');
        Config::set('database.default', 'sqlite');
        Database::$connections = [];
        Model::prevent_lazy_loading(false);

        Schema::create('orm_users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('name');
            $table->integer('active');
            $table->integer('verified');
        });

        Schema::create('orm_posts', function ($table) {
            $table->increments('id');
            $table->integer('orm_user_id');
            $table->integer('votes');
        });

        Schema::create('orm_comments', function ($table) {
            $table->increments('id');
            $table->integer('orm_post_id');
            $table->string('body');
        });

        Schema::create('orm_roles', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('orm_role_user', function ($table) {
            $table->integer('orm_user_id');
            $table->integer('orm_role_id');
            $table->string('level')->nullable();
        });

        $users = [
            ['email' => 'a@x.co', 'name' => 'A', 'active' => 1, 'verified' => 1],
            ['email' => 'b@x.co', 'name' => 'B', 'active' => 1, 'verified' => 0],
            ['email' => 'c@x.co', 'name' => 'C', 'active' => 0, 'verified' => 1],
        ];

        foreach ($users as $user) {
            Database::table('orm_users')->insert($user);
        }

        foreach ([[1, 3], [1, 7], [2, 5]] as $post) {
            Database::table('orm_posts')->insert(['orm_user_id' => $post[0], 'votes' => $post[1]]);
        }

        Database::table('orm_comments')->insert(['orm_post_id' => 1, 'body' => 'hi']);

        foreach (['admin', 'editor', 'viewer'] as $role) {
            Database::table('orm_roles')->insert(['name' => $role]);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach (['orm_users', 'orm_posts', 'orm_comments', 'orm_roles', 'orm_role_user'] as $table) {
            Schema::drop_if_exists($table);
        }

        Model::prevent_lazy_loading(false);
        Database::$connections = [];
        Config::set('database.default', $this->connection);
    }

    /**
     * upsert() inserts new records and updates the existing ones in one query.
     *
     * @group system
     */
    public function testUpsert()
    {
        if (version_compare(Database::connection()->only('SELECT sqlite_version()'), '3.24.0', '<')) {
            $this->markTestSkipped('SQLite 3.24+ is needed for ON CONFLICT.');
        }

        Database::table('orm_users')->upsert([
            ['email' => 'a@x.co', 'name' => 'A2', 'active' => 0, 'verified' => 1],
            ['email' => 'd@x.co', 'name' => 'D', 'active' => 1, 'verified' => 1],
        ], 'email', ['name', 'active']);

        $this->assertEquals(4, Database::table('orm_users')->count());
        $this->assertEquals('A2', Database::table('orm_users')->where('email', '=', 'a@x.co')->only('name'));
        $this->assertEquals(0, Database::table('orm_users')->where('email', '=', 'a@x.co')->only('active'));

        // Through a model too, and with nothing to update it only inserts.
        OrmUser::upsert(['email' => 'e@x.co', 'name' => 'E', 'active' => 1, 'verified' => 1], 'email', []);
        OrmUser::upsert(['email' => 'e@x.co', 'name' => 'E2', 'active' => 1, 'verified' => 1], 'email', []);
        $this->assertEquals('E', OrmUser::where('email', '=', 'e@x.co')->first()->name);
    }

    /**
     * simple_paginate() fetches one row more instead of counting.
     *
     * @group system
     */
    public function testSimplePaginate()
    {
        $first = OrmUser::order_by('id', 'asc')->simple_paginate(2, ['*'], 'page', 1);
        $this->assertCount(2, $first->results);
        $this->assertNull($first->total);
        $this->assertTrue($first->has_more_pages());
        $this->assertInstanceOf('OrmUser', $first->results[0]);

        $last = Database::table('orm_users')->order_by('id', 'asc')->simple_paginate(2, ['*'], 'page', 2);
        $this->assertCount(1, $last->results);
        $this->assertFalse($last->has_more_pages());
    }

    /**
     * Local scopes chain after one another and after other calls.
     *
     * @group system
     */
    public function testChainedScopes()
    {
        $this->assertEquals(1, OrmUser::active()->verified()->count());
        $this->assertEquals(1, OrmUser::where('id', '>', 1)->active()->count());
        $this->assertEquals('A', OrmUser::active()->named('A')->first()->name);
    }

    /**
     * with_sum(), with_avg(), with_min(), with_max() and with_exists().
     *
     * @group system
     */
    public function testRelationAggregates()
    {
        $users = OrmUser::with_sum('posts', 'votes')
            ->with_max('posts', 'votes')
            ->with_min('posts', 'votes')
            ->with_avg('posts', 'votes')
            ->with_exists('posts')
            ->with_count('posts')
            ->order_by('id', 'asc')
            ->get();

        $this->assertEquals(10, $users[0]->posts_sum_votes);
        $this->assertEquals(7, $users[0]->posts_max_votes);
        $this->assertEquals(3, $users[0]->posts_min_votes);
        $this->assertEquals(5, $users[0]->posts_avg_votes);
        $this->assertEquals(1, $users[0]->posts_exists);
        $this->assertEquals(2, $users[0]->posts_count);
        $this->assertEquals(0, $users[2]->posts_exists);
        $this->assertNull($users[2]->posts_sum_votes);

        $filtered = OrmUser::with_sum(['posts' => function ($query) {
            $query->where('votes', '>', 4);
        }], 'votes')->order_by('id', 'asc')->first();
        $this->assertEquals(7, $filtered->posts_sum_votes);
    }

    /**
     * A nested eager load brings its parents along.
     *
     * @group system
     */
    public function testNestedEagerLoadingLoadsTheParents()
    {
        $user = OrmUser::with('posts.comments')->order_by('id', 'asc')->first();

        $this->assertArrayHasKey('posts', $user->relationships);
        $this->assertCount(2, $user->posts);
        $this->assertArrayHasKey('comments', $user->posts[0]->relationships);
        $this->assertEquals('hi', $user->posts[0]->comments[0]->body);
    }

    /**
     * load() and load_missing() on a model and on a collection.
     *
     * @group system
     */
    public function testLoadAndLoadMissing()
    {
        $user = OrmUser::find(1)->load('posts');
        $this->assertArrayHasKey('posts', $user->relationships);
        $this->assertCount(2, $user->posts);

        $users = OrmUser::order_by('id', 'asc')->get()->load('posts.comments');
        $this->assertCount(2, $users[0]->relationships['posts']);
        $this->assertCount(1, $users[1]->relationships['posts']);
        $this->assertCount(0, $users[2]->relationships['posts']);

        // Already loaded: left as is, even when the rows change.
        Database::table('orm_posts')->delete();
        $users->load_missing('posts');
        $this->assertCount(2, $users[0]->relationships['posts']);

        $user->load_missing(['posts', 'roles']);
        $this->assertCount(2, $user->posts);
        $this->assertArrayHasKey('roles', $user->relationships);
    }

    /**
     * prevent_lazy_loading() throws on N+1 access, not on a single model.
     *
     * @group system
     */
    public function testPreventLazyLoading()
    {
        Model::prevent_lazy_loading();

        $this->assertCount(2, OrmUser::find(1)->posts);
        $this->assertCount(2, OrmUser::with('posts')->order_by('id', 'asc')->get()[0]->posts);

        try {
            OrmUser::order_by('id', 'asc')->get()[0]->posts;
            $this->fail('Lazy loading was allowed');
        } catch (\System\Exceptions\LazyLoadingException $e) {
            $this->assertContains('[posts]', $e->getMessage());
        }

        Model::prevent_lazy_loading(false);
        $this->assertCount(2, OrmUser::order_by('id', 'asc')->get()[0]->posts);
    }

    /**
     * sync() in bulk with pivot attributes, sync_without_detaching(), toggle() and
     * update_existing_pivot().
     *
     * @group system
     */
    public function testPivotOperations()
    {
        $user = OrmUser::find(1);

        $changes = $user->roles()->sync([1, 2 => ['level' => 'senior']]);
        $this->assertEquals(['1', '2'], $changes['attached']);
        $this->assertEquals(['1', '2'], $this->roles());
        $this->assertEquals('senior', $this->level(2));

        $changes = $user->roles()->sync([2 => ['level' => 'lead'], 3]);
        $this->assertEquals(['3'], $changes['attached']);
        $this->assertEquals(['1'], $changes['detached']);
        $this->assertEquals(['2'], $changes['updated']);
        $this->assertEquals('lead', $this->level(2));

        $user->roles()->sync_without_detaching([1]);
        $this->assertEquals(['1', '2', '3'], $this->roles());

        $changes = $user->roles()->toggle([1, 2]);
        $this->assertEquals(['1', '2'], $changes['detached']);
        $this->assertEquals(['3'], $this->roles());
        $user->roles()->toggle(1);
        $this->assertEquals(['1', '3'], $this->roles());

        $this->assertEquals(1, $user->roles()->update_existing_pivot(3, ['level' => 'junior']));
        $this->assertEquals('junior', $this->level(3));

        // The variadic form still works.
        $user->roles()->sync(2, 3);
        $this->assertEquals(['2', '3'], $this->roles());
    }

    /**
     * Get the role IDs of user 1, sorted, as strings.
     *
     * @return array
     */
    protected function roles()
    {
        $ids = array_map('strval', Database::table('orm_role_user')->where('orm_user_id', '=', 1)->lists('orm_role_id'));
        sort($ids);

        return $ids;
    }

    /**
     * Get the pivot level of a role of user 1.
     *
     * @param int $role
     *
     * @return string|null
     */
    protected function level($role)
    {
        return Database::table('orm_role_user')->where('orm_user_id', '=', 1)->where('orm_role_id', '=', $role)->only('level');
    }
}

class OrmUser extends Model
{
    public static $table = 'orm_users';
    public static $timestamps = false;
    public static $guarded = [];
    public static $fillable = null;

    public function posts()
    {
        return $this->has_many('OrmPost', 'orm_user_id');
    }

    public function roles()
    {
        return $this->belongs_to_many('OrmRole', 'orm_role_user', 'orm_user_id', 'orm_role_id');
    }

    public function scope_active($query)
    {
        return $query->where('active', '=', 1);
    }

    public function scope_verified($query)
    {
        return $query->where('verified', '=', 1);
    }

    public function scope_named($query, $name)
    {
        return $query->where('name', '=', $name);
    }
}

class OrmPost extends Model
{
    public static $table = 'orm_posts';
    public static $timestamps = false;

    public function comments()
    {
        return $this->has_many('OrmComment', 'orm_post_id');
    }
}

class OrmComment extends Model
{
    public static $table = 'orm_comments';
    public static $timestamps = false;
}

class OrmRole extends Model
{
    public static $table = 'orm_roles';
    public static $timestamps = false;
}
