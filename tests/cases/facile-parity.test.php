<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;

class FacileParityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('database.connections.parity', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $pdo = Database::connection('parity')->pdo();

        $pdo->exec('CREATE TABLE IF NOT EXISTS parity_users (id INTEGER PRIMARY KEY, name TEXT)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS parity_posts (
            id INTEGER PRIMARY KEY, user_id INTEGER, title TEXT, active INTEGER,
            meta TEXT, price TEXT, views INTEGER DEFAULT 0, published INTEGER DEFAULT 0,
            created_at TEXT, updated_at TEXT
        )');

        $pdo->exec('DELETE FROM parity_users');
        $pdo->exec('DELETE FROM parity_posts');

        foreach (['ani', 'budi', 'cici'] as $name) {
            $pdo->exec("INSERT INTO parity_users (name) VALUES ('$name')");
        }

        // ani owns 2 posts (1 published), budi owns 1 post (unpublished), cici owns none.
        $pdo->exec("INSERT INTO parity_posts (user_id, title, active, meta, price, published, created_at, updated_at)
            VALUES (1, 'one', 1, '{\"x\":1}', '10.5', 1, '2026-01-02 03:04:05', '2026-01-02 03:04:05')");
        $pdo->exec("INSERT INTO parity_posts (user_id, title, active, meta, price) VALUES (1, 'two', 0, '{\"x\":2}', '7.25')");
        $pdo->exec("INSERT INTO parity_posts (user_id, title, active, meta, price) VALUES (2, 'three', 1, '{\"x\":3}', '3')");
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $pdo = Database::connection('parity')->pdo();
        $pdo->exec('DROP TABLE IF EXISTS parity_posts');
        $pdo->exec('DROP TABLE IF EXISTS parity_users');
    }

    // -------------------------------------------------------------------------
    // Dirty tracking
    // -------------------------------------------------------------------------

    /**
     * Test that the original attributes are synced when the model is loaded.
     *
     * @group system
     */
    public function testOriginalAttributesAreSyncedOnHydration()
    {
        $post = ParityPost::find(1);

        $this->assertEquals($post->attributes, $post->original);
        $this->assertFalse($post->dirty());
        $this->assertEquals([], $post->get_dirty());
    }

    /**
     * Test that only the changed attribute counts as dirty.
     *
     * @group system
     */
    public function testOnlyTheChangedAttributeIsDirty()
    {
        $post = ParityPost::find(1);
        $post->title = 'changed';

        $this->assertTrue($post->dirty());
        $this->assertEquals(['title' => 'changed'], $post->get_dirty());
        $this->assertTrue($post->changed('title'));
        $this->assertFalse($post->changed('user_id'));
    }

    /**
     * Test was_changed() and get_original().
     *
     * @group system
     */
    public function testWasChangedAndGetOriginal()
    {
        $post = ParityPost::find(1);

        $this->assertFalse($post->was_changed());

        $post->title = 'changed';
        $post->save();

        $this->assertTrue($post->was_changed());
        $this->assertTrue($post->was_changed('title'));
        $this->assertFalse($post->was_changed('user_id'));
        $this->assertEquals('changed', $post->get_original('title'));
        $this->assertNull($post->get_original('missing'));
    }

    /**
     * Test that delete() returns a boolean.
     *
     * @group system
     */
    public function testDeleteReturnsABoolean()
    {
        $this->assertTrue(ParityPost::find(3)->delete());
        $this->assertFalse((new ParityPost())->delete());
        $this->assertNull(ParityPost::find(3));
    }

    // -------------------------------------------------------------------------
    // Casting
    // -------------------------------------------------------------------------

    /**
     * Test attribute casting.
     *
     * @group system
     */
    public function testAttributesAreCastToTheirDeclaredType()
    {
        $post = ParityPost::find(1);

        $this->assertTrue($post->active);
        $this->assertInternalType('bool', $post->active);
        $this->assertEquals(['x' => 1], $post->meta);
        $this->assertEquals('10.50', $post->price);
        $this->assertInternalType('int', $post->views);
        $this->assertInstanceOf('\System\Carbon', $post->created_at);
    }

    /**
     * Test that a structural cast is encoded back when it is stored.
     *
     * @group system
     */
    public function testStructuralCastIsEncodedOnAssignment()
    {
        $post = ParityPost::find(1);
        $post->meta = ['x' => 99, 'y' => 1];
        $post->save();

        $this->assertEquals(['x' => 99, 'y' => 1], ParityPost::find(1)->meta);
    }

    // -------------------------------------------------------------------------
    // Serialization
    // -------------------------------------------------------------------------

    /**
     * Test hidden, appends and casting in to_array().
     *
     * @group system
     */
    public function testToArrayHonorsHiddenAndAppends()
    {
        $array = ParityPost::find(1)->to_array();

        $this->assertFalse(array_key_exists('meta', $array));
        $this->assertTrue($array['active']);
        $this->assertEquals('ONE', $array['label']);
        $this->assertEquals('2026-01-02 03:04:05', $array['created_at']);
    }

    /**
     * Test make_visible(), make_hidden() and append().
     *
     * @group system
     */
    public function testPerInstanceVisibilityOverrides()
    {
        $post = ParityPost::find(1);

        $this->assertTrue(array_key_exists('meta', $post->make_visible('meta')->to_array()));
        $this->assertFalse(array_key_exists('title', ParityPost::find(1)->make_hidden('title')->to_array()));

        // Without a $visible whitelist, making one attribute visible keeps
        // the others.
        $shown = ParityPost::find(1)->make_visible('meta')->to_array();
        $this->assertTrue(array_key_exists('title', $shown));

        $user = ParityUser::find(1)->append('shout');

        $this->assertEquals('ANI', $user->to_array()['shout']);
    }

    /**
     * Test the $visible whitelist.
     *
     * @group system
     */
    public function testVisibleWhitelistWins()
    {
        $array = ParityOnlyTitle::find(1)->to_array();

        $this->assertEquals(['title'], array_keys($array));
    }

    // -------------------------------------------------------------------------
    // Model helpers
    // -------------------------------------------------------------------------

    /**
     * Test first_or_new(), first_or_create() and update_or_create().
     *
     * @group system
     */
    public function testFirstOrCreateFamily()
    {
        $new = ParityPost::first_or_new(['title' => 'not-yet']);

        $this->assertFalse($new->exists);
        $this->assertEquals('not-yet', $new->title);

        $created = ParityPost::first_or_create(['title' => 'fresh'], ['user_id' => 3]);

        $this->assertTrue($created->exists);
        $this->assertEquals($created->id, ParityPost::first_or_create(['title' => 'fresh'])->id);

        $updated = ParityPost::update_or_create(['title' => 'fresh'], ['views' => 9]);

        $this->assertEquals($created->id, $updated->id);
        $this->assertEquals(9, ParityPost::find($created->id)->views);
    }

    /**
     * Test find_many() and destroy().
     *
     * @group system
     */
    public function testFindManyAndDestroy()
    {
        $this->assertEquals(2, count(ParityPost::find_many([1, 2])));
        $this->assertEquals(0, count(ParityPost::find_many([])));
        $this->assertEquals(2, ParityPost::destroy([1, 2]));
        $this->assertNull(ParityPost::find(1));
    }

    /**
     * Test is() and is_not().
     *
     * @group system
     */
    public function testIsAndIsNot()
    {
        $this->assertTrue(ParityPost::find(1)->is(ParityPost::find(1)));
        $this->assertFalse(ParityPost::find(1)->is(ParityPost::find(2)));
        $this->assertTrue(ParityPost::find(1)->is_not(ParityPost::find(2)));
        $this->assertFalse(ParityPost::find(1)->is(ParityUser::find(1)));
    }

    /**
     * Test refresh() mutates the very same instance.
     *
     * @group system
     */
    public function testRefreshMutatesTheInstance()
    {
        $post = ParityPost::find(1);
        Database::connection('parity')->pdo()->exec("UPDATE parity_posts SET title = 'from-outside' WHERE id = 1");

        $same = $post->refresh();

        $this->assertSame($post, $same);
        $this->assertEquals('from-outside', $post->title);
        $this->assertFalse($post->dirty());
    }

    /**
     * Test replicate().
     *
     * @group system
     */
    public function testReplicate()
    {
        $copy = ParityPost::find(1)->replicate();

        $this->assertFalse($copy->exists);
        $this->assertNull($copy->id);
        $this->assertEquals('one', $copy->title);
    }

    /**
     * Test instance level increment() and decrement().
     *
     * @group system
     */
    public function testIncrementAndDecrement()
    {
        $post = ParityPost::find(1);

        $this->assertTrue($post->increment('views', 3));
        $this->assertEquals(3, ParityPost::find(1)->views);
        $this->assertEquals(3, $post->views);

        $post->decrement('views');

        $this->assertEquals(2, ParityPost::find(1)->views);
        $this->assertFalse((new ParityPost())->increment('views'));
    }

    /**
     * Test touch().
     *
     * @group system
     */
    public function testTouch()
    {
        $post = ParityPost::find(1);

        $this->assertTrue($post->touch());
        $this->assertNotNull(ParityPost::find(1)->updated_at);
    }

    // -------------------------------------------------------------------------
    // Collection results
    // -------------------------------------------------------------------------

    /**
     * Test that query results are collections.
     *
     * @group system
     */
    public function testQueryResultsAreCollections()
    {
        $this->assertInstanceOf('\System\Collection', ParityPost::all());
        $this->assertInstanceOf('\System\Collection', ParityPost::where('id', '>', 0)->get());
        $this->assertInstanceOf('\System\Collection', Database::connection('parity')->table('parity_posts')->get());
        $this->assertInstanceOf('\System\Collection', ParityPost::paginate(2)->results);

        $titles = ParityPost::all()->pluck('title')->all();

        $this->assertEquals(['one', 'two', 'three'], $titles);
    }

    // -------------------------------------------------------------------------
    // Relationship queries
    // -------------------------------------------------------------------------

    /**
     * Test has() and doesnt_have().
     *
     * @group system
     */
    public function testHasAndDoesntHave()
    {
        $this->assertEquals(['ani', 'budi'], ParityUser::has('posts')->get()->pluck('name')->all());
        $this->assertEquals(['cici'], ParityUser::doesnt_have('posts')->get()->pluck('name')->all());
        $this->assertEquals(['ani'], ParityUser::has('posts', '>=', 2)->get()->pluck('name')->all());
        $this->assertEquals(['budi', 'cici'], ParityUser::has('posts', '<', 2)->get()->pluck('name')->all());
    }

    /**
     * Test where_has() with extra constraints.
     *
     * @group system
     */
    public function testWhereHasWithConstraints()
    {
        $users = ParityUser::where_has('posts', function ($query) {
            $query->where('published', '=', 1);
        })->get();

        $this->assertEquals(['ani'], $users->pluck('name')->all());
    }

    /**
     * Test with_count().
     *
     * @group system
     */
    public function testWithCount()
    {
        $counts = [];

        foreach (ParityUser::with_count('posts')->get() as $user) {
            $counts[$user->name] = (int) $user->posts_count;
        }

        $this->assertEquals(['ani' => 2, 'budi' => 1, 'cici' => 0], $counts);
    }

    /**
     * Test has() on a belongs_to relationship.
     *
     * @group system
     */
    public function testHasOnBelongsTo()
    {
        $this->assertEquals(3, count(ParityPost::has('user')->get()));
    }

    /**
     * Test that an unknown relationship is reported clearly.
     *
     * @group system
     */
    public function testUnknownRelationshipThrows()
    {
        $this->setExpectedException('\Exception', 'Undefined relationship on ParityUser: missing');
        ParityUser::has('missing')->get();
    }

    // -------------------------------------------------------------------------
    // Facile query helpers
    // -------------------------------------------------------------------------

    /**
     * Test chunk() and each() hydrate models.
     *
     * @group system
     */
    public function testChunkAndEachHydrateModels()
    {
        $seen = [];

        ParityPost::chunk(2, function ($models) use (&$seen) {
            foreach ($models as $model) {
                $seen[] = get_class($model);
            }
        });

        $this->assertEquals(3, count($seen));
        $this->assertEquals('ParityPost', $seen[0]);

        $counted = 0;

        ParityPost::each(function ($model) use (&$counted) {
            ++$counted;
        });

        $this->assertEquals(3, $counted);
    }

    /**
     * Test when() and unless().
     *
     * @group system
     */
    public function testWhenAndUnless()
    {
        $filtered = ParityPost::when(true, function ($query) {
            return $query->where('published', '=', 1);
        })->get();

        $untouched = ParityPost::unless(true, function ($query) {
            return $query->where('published', '=', 1);
        })->get();

        $this->assertEquals(1, count($filtered));
        $this->assertEquals(3, count($untouched));
    }

    /**
     * Test sole().
     *
     * @group system
     */
    public function testSole()
    {
        $this->assertEquals('one', ParityPost::where('id', '=', 1)->sole()->title);

        $this->setExpectedException('\Exception', 'More than one ParityPost found.');
        ParityPost::sole();
    }

    /**
     * Test where_key().
     *
     * @group system
     */
    public function testWhereKey()
    {
        $this->assertEquals('one', ParityPost::where_key(1)->first()->title);
        $this->assertEquals(2, count(ParityPost::where_key([1, 2])->get()));
        $this->assertEquals(2, count(ParityPost::where('id', '>', 0)->where_key_not(1)->get()));
    }

    /**
     * Test where_relation() and or_where_relation().
     *
     * @group system
     */
    public function testWhereRelation()
    {
        $this->assertEquals(['ani'], ParityUser::where_relation('posts', 'published', '=', 1)->get()->pluck('name')->all());
        $this->assertEquals(['budi'], ParityUser::where_relation('posts', 'title', 'three')->get()->pluck('name')->all());
        $this->assertEquals(['three'], ParityPost::where_relation('user', 'name', '=', 'budi')->get()->pluck('title')->all());

        $users = ParityUser::where('name', '=', 'cici')->or_where_relation('posts', 'title', '=', 'three')->get();

        $this->assertEquals(['budi', 'cici'], $users->pluck('name')->all());
    }

    /**
     * Test where_belongs_to() and or_where_belongs_to().
     *
     * @group system
     */
    public function testWhereBelongsTo()
    {
        $ani = ParityUser::find(1);
        $budi = ParityUser::find(2);

        $this->assertEquals(['one', 'two'], ParityPost::where_belongs_to($ani, 'user')->get()->pluck('title')->all());
        $this->assertContains('"parity_posts"."user_id" = ?', ParityPost::where_belongs_to($ani, 'user')->to_sql());

        // The relationship name is guessed from the class of the parent.
        $this->assertEquals(['three'], ParityPost::where_belongs_to($budi)->get()->pluck('title')->all());

        $many = ParityPost::where_belongs_to(ParityUser::where_in('id', [1, 2])->get(), 'user');

        $this->assertContains('"parity_posts"."user_id" IN (?, ?)', $many->to_sql());
        $this->assertEquals(3, count($many->get()));
        $this->assertEquals(3, count(ParityPost::where_belongs_to([$ani, $budi, $ani], 'user')->get()));

        $either = ParityPost::where('title', '=', 'one')->or_where_belongs_to($budi, 'user')->get();

        $this->assertEquals(['one', 'three'], $either->pluck('title')->all());

        // Nothing can belong to a parent that was never saved.
        $this->assertEquals(0, count(ParityPost::where_belongs_to(new ParityUser(), 'user')->get()));
    }

    /**
     * Test that where_belongs_to() complains about a wrong model or relationship.
     *
     * @group system
     */
    public function testWhereBelongsToRejectsWrongInput()
    {
        $cases = [
            'expects ParityUser models' => function () {
                ParityPost::where_belongs_to(ParityPost::find(1), 'user');
            },
            'is not a belongs_to relationship' => function () {
                ParityUser::where_belongs_to(ParityPost::find(1), 'posts');
            },
            'Undefined relationship on ParityPost: parity_post' => function () {
                ParityPost::where_belongs_to(ParityPost::find(1));
            },
            'non-empty collection or array of models' => function () {
                ParityPost::where_belongs_to([], 'user');
            },
        ];

        foreach ($cases as $message => $callback) {
            try {
                $callback();
                $this->fail('No exception for: ' . $message);
            } catch (\PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->assertContains($message, $e->getMessage());
            }
        }
    }

    /**
     * Test that eager loading inlines native integer keys instead of binding them.
     *
     * @group system
     */
    public function testEagerLoadingInlinesIntegerKeys()
    {
        $debug = Config::get('debugger.database');
        Config::set('debugger.database', true);
        \System\Database\Connection::$queries = [];

        $users = ParityUser::with('posts')->get();
        $posts = ParityPost::with('user')->get();

        $queries = \System\Database\Connection::$queries;
        \System\Database\Connection::$queries = [];
        Config::set('debugger.database', $debug);

        $sql = implode("\n", array_map(function ($query) {
            return $query['sql'];
        }, $queries));

        // Drivers that return integer columns as strings (e.g. pdo_sqlite before PHP 8.1)
        // keep binding the keys, since it can not be told they are integers.
        if (is_int($users[0]->get_key())) {
            $this->assertContains('"user_id" IN (1, 2, 3)', $sql);
            $this->assertContains('"id" IN (1, 2)', $sql);
        } else {
            $this->assertContains('"user_id" IN (?, ?, ?)', $sql);
            $this->assertContains('"id" IN (?, ?)', $sql);
        }

        $this->assertEquals(2, count($users[0]->posts));
        $this->assertEquals(1, count($users[1]->posts));
        $this->assertEquals(0, count($users[2]->posts));
        $this->assertEquals('ani', $posts[0]->user->name);
        $this->assertEquals('budi', $posts[2]->user->name);
    }
}

/**
 * Test model with casts, hidden attributes and an appended accessor.
 */
class ParityPost extends \System\Database\Facile\Model
{
    public static $connection = 'parity';
    public static $table = 'parity_posts';
    public static $casts = [
        'active' => 'boolean',
        'meta' => 'array',
        'price' => 'decimal:2',
        'views' => 'integer',
        'created_at' => 'datetime',
    ];
    public static $hidden = ['meta'];
    public static $appends = ['label'];

    public function get_label($value = null)
    {
        return strtoupper((string) $this->title);
    }

    public function user()
    {
        return $this->belongs_to('ParityUser', 'user_id');
    }

    public function parity_user()
    {
        return $this->belongs_to('ParityUser', 'user_id');
    }
}

/**
 * Test model owning a has_many relationship.
 */
class ParityUser extends \System\Database\Facile\Model
{
    public static $connection = 'parity';
    public static $table = 'parity_users';
    public static $timestamps = false;

    public function posts()
    {
        return $this->has_many('ParityPost', 'user_id');
    }

    public function get_shout($value = null)
    {
        return strtoupper((string) $this->name);
    }
}

/**
 * Test model using the $visible whitelist.
 */
class ParityOnlyTitle extends \System\Database\Facile\Model
{
    public static $connection = 'parity';
    public static $table = 'parity_posts';
    public static $timestamps = false;
    public static $visible = ['title'];
    public static $hidden = ['title'];
}
