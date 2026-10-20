<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;
use System\Validator;
use System\Database\Facile\Model;

class FacileSecurityTest extends \PHPUnit_Framework_TestCase
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
        Model::$morph_map = [];

        Schema::create('sec_posts', function ($table) {
            $table->increments('id');
            $table->string('title');
        });

        Schema::create('sec_comments', function ($table) {
            $table->increments('id');
            $table->string('body');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        Schema::create('sec_tags', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('approved');
        });

        Schema::create('sec_taggables', function ($table) {
            $table->integer('tag_id');
            $table->string('taggable_type');
            $table->integer('taggable_id');
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        foreach (['sec_posts', 'sec_comments', 'sec_tags', 'sec_taggables'] as $table) {
            Schema::drop_if_exists($table);
        }

        Model::$morph_map = [];
        Database::$connections = [];
        Config::set('database.default', $this->connection);
    }

    /**
     * MorphTo only creates mapped aliases or model classes, never any class a row names.
     *
     * @group system
     */
    public function testMorphToOnlyCreatesModels()
    {
        $post = SecPost::create(['title' => 'p']);

        Database::table('sec_comments')->insert([
            ['body' => 'model', 'commentable_type' => 'SecPost', 'commentable_id' => $post->id],
            ['body' => 'other class', 'commentable_type' => 'SecNotAModel', 'commentable_id' => 1],
            ['body' => 'garbage', 'commentable_type' => '../../etc/passwd', 'commentable_id' => 1],
        ]);

        SecNotAModel::$created = 0;

        $comments = SecComment::order_by('id', 'asc')->get();
        $this->assertEquals('p', $comments[0]->commentable->title);
        $this->assertNull($comments[1]->commentable);
        $this->assertNull($comments[2]->commentable);

        $eager = SecComment::with('commentable')->order_by('id', 'asc')->get();
        $this->assertEquals('p', $eager[0]->commentable->title);
        $this->assertNull($eager[1]->commentable);

        $this->assertSame(0, SecNotAModel::$created);
    }

    /**
     * With a morph map, models are stored and found under their alias.
     *
     * @group system
     */
    public function testMorphMap()
    {
        Model::morph_map(['post' => 'SecPost']);

        $post = SecPost::create(['title' => 'mapped']);
        $post->comments()->insert(['body' => 'hi']);

        $this->assertEquals('post', Database::table('sec_comments')->first()->commentable_type);
        $this->assertEquals('mapped', SecComment::first()->commentable->title);
        $this->assertEquals('post', $post->morph_class());
        $this->assertSame('SecPost', Model::morph_resolve('post'));
        $this->assertNull(Model::morph_resolve('stdClass'));
    }

    /**
     * Constraints on MorphToMany apply, called directly and eager loaded.
     *
     * @group system
     */
    public function testMorphToManyConstraintsApply()
    {
        $post = SecPost::create(['title' => 'p']);
        Database::table('sec_tags')->insert([['name' => 'ok', 'approved' => 1], ['name' => 'hidden', 'approved' => 0]]);

        foreach (Database::table('sec_tags')->get() as $tag) {
            Database::table('sec_taggables')->insert(['tag_id' => $tag->id, 'taggable_type' => 'SecPost', 'taggable_id' => $post->id]);
        }

        $this->assertCount(2, $post->tags()->get());
        $this->assertCount(1, $post->tags()->where('approved', '=', 1)->get());

        $loaded = SecPost::with(['tags' => function ($query) {
            $query->where('approved', '=', 1);
        }])->first();

        $this->assertCount(1, $loaded->tags);
        $this->assertEquals('ok', $loaded->tags[0]->name);
    }

    /**
     * Only methods the model declares itself can be named as relationships.
     *
     * @group system
     */
    public function testRelationshipNamesAreChecked()
    {
        SecPost::create(['title' => 'p']);

        foreach (['save', 'delete', 'push', 'to_array', 'title', 'helper'] as $name) {
            try {
                SecPost::with($name)->get();
                $this->fail('Accepted relationship name: '.$name);
            } catch (\PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (\Exception $e) {
                $this->assertRegExp('/Undefined relationship|is not a relationship/', $e->getMessage());
            }
        }

        $this->assertEquals(1, SecPost::count());
        $this->assertSame(0, SecPost::$helper_calls);
    }

    /**
     * unique and exists refuse non-scalar values and odd identifiers; unique_rule()
     * keeps an injected ID as one value.
     *
     * @group system
     */
    public function testUniqueAndExistsRules()
    {
        Database::table('sec_posts')->insert(['title' => 'taken']);
        $id = Database::table('sec_posts')->first()->id;

        $this->assertFalse(Validator::make(['title' => ['x']], ['title' => 'unique:sec_posts'])->passes());
        $this->assertFalse(Validator::make(['title' => [['x']]], ['title' => 'exists:sec_posts'])->passes());
        $this->assertFalse(Validator::make(['title' => 'taken'], ['title' => 'unique:sec_posts'])->passes());
        $this->assertTrue(Validator::make(['title' => ['taken']], ['title' => 'exists:sec_posts'])->passes());

        // The record itself is ignored...
        $rule = Validator::unique_rule('sec_posts', 'title', $id);
        $this->assertTrue(Validator::make(['title' => 'taken'], ['title' => [$rule]])->passes());

        // ...but an ID carrying a column of its own is one value, not a new parameter.
        $rule = Validator::unique_rule('sec_posts', 'title', $id.',title');
        $this->assertSame(['sec_posts', 'title', $id.',title', 'id'], str_getcsv(substr($rule, 7), ',', '"', '\\'));
        $this->assertFalse(Validator::make(['title' => 'taken'], ['title' => [$rule]])->passes());

        $this->setExpectedException('InvalidArgumentException');
        Validator::make(['title' => 'x'], ['title' => 'unique:sec_posts,title;drop'])->passes();
    }
}

class SecPost extends Model
{
    public static $table = 'sec_posts';
    public static $timestamps = false;
    public static $fillable = ['title'];
    public static $helper_calls = 0;

    public function comments()
    {
        return $this->morph_many('SecComment', 'commentable');
    }

    public function tags()
    {
        return $this->morph_to_many('SecTag', 'taggable', 'sec_taggables', null, 'tag_id');
    }

    public function helper($required)
    {
        ++static::$helper_calls;
    }
}

class SecComment extends Model
{
    public static $table = 'sec_comments';
    public static $timestamps = false;

    public function commentable()
    {
        return $this->morph_to('commentable');
    }
}

class SecTag extends Model
{
    public static $table = 'sec_tags';
    public static $timestamps = false;
}

class SecNotAModel
{
    public static $created = 0;

    public function __construct()
    {
        ++static::$created;
    }
}
