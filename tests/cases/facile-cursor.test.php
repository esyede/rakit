<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;

class FacileCursorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $this->seed([1, 2, 5, 9, 10]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('cursor_posts');
        Schema::drop_if_exists('cursor_authors');
    }

    /**
     * Create authors with the given ids, each with one post.
     */
    protected function seed(array $ids)
    {
        $this->tearDown();
        Schema::create('cursor_authors', function ($table) {
            $table->increments('id');
            $table->string('name');
        });
        Schema::create('cursor_posts', function ($table) {
            $table->increments('id');
            $table->integer('cursor_author_id');
            $table->string('title');
        });

        foreach ($ids as $id) {
            Database::table('cursor_authors')->insert(['id' => $id, 'name' => 'author' . $id]);
            Database::table('cursor_posts')->insert(['cursor_author_id' => $id, 'title' => 'post' . $id]);
        }
    }

    /**
     * Run the callback with query logging on, returning [result, executed SQL list].
     */
    protected function logged($callback)
    {
        $debug = Config::get('debugger.database');
        Config::set('debugger.database', true);
        Database\Connection::$queries = [];

        $result = call_user_func($callback);

        $queries = Database\Connection::$queries;
        Database\Connection::$queries = [];
        Config::set('debugger.database', $debug);

        return [$result, array_map(function ($query) {
            return $query['sql'];
        }, $queries)];
    }

    /**
     * Assert the models are hydrated authors with their posts eager loaded, and
     * return "name:title" per model.
     */
    protected function loaded($models)
    {
        $out = [];

        foreach ($models as $key => $author) {
            $this->assertInstanceOf('CursorAuthor', $author);
            $this->assertTrue($author->exists);
            $this->assertTrue(array_key_exists('posts', $author->relationships));
            $out[$key] = $author->name . ':' . $author->relationships['posts'][0]->title;
        }

        return $out;
    }

    /**
     * @group system
     */
    public function testCursorHydratesInOneQueryWithoutEagerLoads()
    {
        list($models, $sql) = $this->logged(function () {
            return iterator_to_array(CursorAuthor::with('posts')->where('id', '>', 1)->order_by('id')->cursor());
        });

        $this->assertCount(1, $sql);
        $this->assertEquals([0, 1, 2, 3], array_keys($models));

        foreach ($models as $model) {
            $this->assertInstanceOf('CursorAuthor', $model);
            $this->assertTrue($model->exists);
            $this->assertFalse(array_key_exists('posts', $model->relationships));
        }

        $this->assertEquals('author2', $models[0]->name);
        $this->assertEquals('author10', $models[3]->name);
    }

    /**
     * @group system
     */
    public function testCursorReturnsGeneratorOrIterator()
    {
        $cursor = CursorAuthor::cursor();

        if (PHP_VERSION_ID >= 50500) {
            $this->assertInstanceOf('\Generator', $cursor);
        } else {
            $this->assertInstanceOf('\System\Database\Query\Cursor', $cursor);
        }
    }

    /**
     * @group system
     */
    public function testLazyHydratesAndEagerLoadsInChunks()
    {
        list($models, $sql) = $this->logged(function () {
            return iterator_to_array(CursorAuthor::with('posts')->lazy(2));
        });

        $this->assertEquals([
            0 => 'author1:post1', 1 => 'author2:post2', 2 => 'author5:post5',
            3 => 'author9:post9', 4 => 'author10:post10',
        ], $this->loaded($models));

        // 3 chunks, each with one eager load query; ordered by the key by default.
        $this->assertCount(6, $sql);
        $this->assertContains('ORDER BY "cursor_authors"."id"', $sql[0]);
    }

    /**
     * @group system
     */
    public function testLazyKeepsGivenOrder()
    {
        $names = [];

        foreach (CursorAuthor::order_by('id', 'desc')->lazy(2) as $author) {
            $names[] = $author->name;
        }

        $this->assertEquals(['author10', 'author9', 'author5', 'author2', 'author1'], $names);
    }

    /**
     * @group system
     */
    public function testLazyRejectsBadChunkSize()
    {
        $this->setExpectedException('\InvalidArgumentException');
        CursorAuthor::lazy(0);
    }

    /**
     * @group system
     */
    public function testLazyByIdHydratesAndEagerLoads()
    {
        list($models, $sql) = $this->logged(function () {
            return iterator_to_array(CursorAuthor::with('posts')->lazy_by_id(2));
        });

        $this->assertEquals([
            0 => 'author1:post1', 1 => 'author2:post2', 2 => 'author5:post5',
            3 => 'author9:post9', 4 => 'author10:post10',
        ], $this->loaded($models));

        $this->assertContains('"cursor_authors"."id" > ?', $sql[2]);
    }

    /**
     * @group system
     */
    public function testLazyByIdDescHydratesAndEagerLoads()
    {
        $models = iterator_to_array(CursorAuthor::with('posts')->lazy_by_id_desc(2));

        $this->assertEquals([
            0 => 'author10:post10', 1 => 'author9:post9', 2 => 'author5:post5',
            3 => 'author2:post2', 4 => 'author1:post1',
        ], $this->loaded($models));
    }

    /**
     * @group system
     */
    public function testChunkByIdHydratesModels()
    {
        $chunks = [];

        $result = CursorAuthor::with('posts')->chunk_by_id(2, function ($models, $page) use (&$chunks) {
            $this->assertInstanceOf('\System\Collection', $models);
            $chunks[$page] = array_values($this->loaded($models));
        });

        $this->assertTrue($result);
        $this->assertEquals([
            1 => ['author1:post1', 'author2:post2'],
            2 => ['author5:post5', 'author9:post9'],
            3 => ['author10:post10'],
        ], $chunks);
    }

    /**
     * @group system
     */
    public function testChunkByIdDescStops()
    {
        $chunks = [];

        $result = CursorAuthor::chunk_by_id_desc(2, function ($models) use (&$chunks) {
            $chunks[] = $models->first()->name;
            return false;
        });

        $this->assertFalse($result);
        $this->assertEquals(['author10'], $chunks);
    }

    /**
     * @group system
     */
    public function testEachById()
    {
        $seen = [];

        $result = CursorAuthor::where('id', '>', 1)->each_by_id(function ($author, $key) use (&$seen) {
            $this->assertInstanceOf('CursorAuthor', $author);
            $seen[$key] = $author->name;
        }, 2);

        $this->assertTrue($result);
        $this->assertEquals([0 => 'author2', 1 => 'author5', 2 => 'author9', 3 => 'author10'], $seen);
    }

    /**
     * The PHP 5.4 iterators, tested directly with Facile chunk sources.
     *
     * @group system
     */
    public function testIteratorsHydrateModels()
    {
        $query = CursorAuthor::with('posts');
        $lazy = new \System\Database\Query\Lazy(function ($page) use ($query) {
            $rows = $query->table->copy()->order_by('id')->for_page($page + 1, 2)->get();
            return $query->hydrate($query->model, $rows)->all();
        }, 2);

        $this->assertCount(5, $this->loaded($lazy));
        $this->assertCount(5, $this->loaded($lazy));
    }
}

class CursorAuthor extends \System\Database\Facile\Model
{
    public static $table = 'cursor_authors';
    public static $timestamps = false;

    public function posts()
    {
        return $this->has_many('CursorPost', 'cursor_author_id');
    }
}

class CursorPost extends \System\Database\Facile\Model
{
    public static $table = 'cursor_posts';
    public static $timestamps = false;
}
