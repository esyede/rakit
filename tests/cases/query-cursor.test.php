<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;

class QueryCursorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $this->seed(0);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('cursor_items');
    }

    /**
     * Recreate cursor_items with the given ids (or 1..n when given a number).
     */
    protected function seed($ids)
    {
        Schema::drop_if_exists('cursor_items');
        Schema::create('cursor_items', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $ids = is_array($ids) ? $ids : ($ids > 0 ? range(1, $ids) : []);

        foreach ($ids as $id) {
            Database::table('cursor_items')->insert(['id' => $id, 'name' => 'item' . $id]);
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

    protected function names($rows)
    {
        $names = [];

        foreach ($rows as $row) {
            $names[] = $row->name;
        }

        return $names;
    }

    protected function items()
    {
        return Database::table('cursor_items');
    }

    // -------------------------------------------------------------------------
    // cursor()
    // -------------------------------------------------------------------------

    /**
     * @group system
     */
    public function testCursorReturnsGeneratorOrIterator()
    {
        $cursor = $this->items()->cursor();

        if (PHP_VERSION_ID >= 50500) {
            $this->assertInstanceOf('\Generator', $cursor);
        } else {
            $this->assertInstanceOf('\System\Database\Query\Cursor', $cursor);
        }
    }

    /**
     * @group system
     */
    public function testCursorRunsOneQuery()
    {
        $this->seed(5);

        list($names, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->where('id', '>', 1)->order_by('id')->cursor());
        });

        $this->assertEquals(['item2', 'item3', 'item4', 'item5'], $this->names($names));
        $this->assertCount(1, $sql);
        $this->assertNotContains('LIMIT', strtoupper($sql[0]));
    }

    /**
     * @group system
     */
    public function testCursorIsLazyAndUsesSelect()
    {
        $this->seed(3);

        list($first, $sql) = $this->logged(function () {
            $cursor = $this->items()->select(['id'])->order_by('id')->cursor();
            $before = count(Database\Connection::$queries);

            foreach ($cursor as $row) {
                break;
            }

            return [$before, $row];
        });

        $this->assertEquals(0, $first[0]);
        $this->assertEquals(1, $first[1]->id);
        $this->assertFalse(isset($first[1]->name));
        $this->assertCount(1, $sql);
    }

    /**
     * @group system
     */
    public function testCursorEmptyResult()
    {
        $this->assertEquals([], iterator_to_array($this->items()->cursor()));
    }

    /**
     * The PHP 5.4 iterator, tested directly: streams, keys, and rewind rules.
     *
     * @group system
     */
    public function testCursorIterator()
    {
        $this->seed(3);
        $query = $this->items()->order_by('id');
        $query->select(['*']);
        $sql = $query->grammar->select($query);
        $connection = $query->connection;
        $runs = 0;

        $cursor = new \System\Database\Query\Cursor(function () use ($connection, $sql, &$runs) {
            $runs++;
            $statement = $connection->pdo()->prepare($sql);
            $statement->execute();
            $statement->setFetchMode(\PDO::FETCH_OBJ);
            return $statement;
        }, function ($row) {
            return strtoupper($row->name);
        });

        $this->assertEquals(0, $runs);

        // Rewinding before moving past the first row is allowed, like a generator.
        $cursor->rewind();
        $this->assertEquals([0 => 'ITEM1', 1 => 'ITEM2', 2 => 'ITEM3'], iterator_to_array($cursor));
        $this->assertEquals(1, $runs);

        try {
            iterator_to_array($cursor);
            $this->fail('Rewinding a used cursor should throw.');
        } catch (\Exception $e) {
            $this->assertContains('rewind', $e->getMessage());
        }
    }

    /**
     * @group system
     */
    public function testConnectionCursorWrapsErrors()
    {
        $this->setExpectedException('\System\Exceptions\QueryException');
        iterator_to_array(Database::connection()->cursor('SELECT * FROM no_such_table'));
    }

    // -------------------------------------------------------------------------
    // lazy()
    // -------------------------------------------------------------------------

    /**
     * @group system
     */
    public function testLazyFetchesChunks()
    {
        $this->seed(5);

        list($rows, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->order_by('id')->lazy(2));
        });

        $this->assertEquals([0, 1, 2, 3, 4], array_keys($rows));
        $this->assertEquals(['item1', 'item2', 'item3', 'item4', 'item5'], $this->names($rows));
        $this->assertCount(3, $sql);
    }

    /**
     * @group system
     */
    public function testLazyExactMultipleCostsOneEmptyQuery()
    {
        $this->seed(4);

        list($rows, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->order_by('id')->lazy(2));
        });

        $this->assertCount(4, $rows);
        $this->assertCount(3, $sql);
    }

    /**
     * @group system
     */
    public function testLazyRequiresOrder()
    {
        $this->setExpectedException('\RuntimeException', 'order_by');
        $this->items()->lazy();
    }

    /**
     * @group system
     */
    public function testLazyRejectsBadChunkSize()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->items()->order_by('id')->lazy(0);
    }

    /**
     * The PHP 5.4 iterator, tested directly.
     *
     * @group system
     */
    public function testLazyIterator()
    {
        $this->seed(5);
        $pages = [];
        $query = $this->items()->order_by('id');

        $lazy = new \System\Database\Query\Lazy(function ($page) use ($query, &$pages) {
            $pages[] = $page;
            return $query->copy()->for_page($page + 1, 2)->get()->all();
        }, 2);

        $this->assertEquals([], $pages);
        $rows = iterator_to_array($lazy);
        $this->assertEquals([0, 1, 2, 3, 4], array_keys($rows));
        $this->assertEquals(['item1', 'item2', 'item3', 'item4', 'item5'], $this->names($rows));
        $this->assertEquals([0, 1, 2], $pages);

        // Rewinding starts over.
        $this->assertEquals($rows, iterator_to_array($lazy));
    }

    // -------------------------------------------------------------------------
    // lazy_by_id() / lazy_by_id_desc()
    // -------------------------------------------------------------------------

    /**
     * @group system
     */
    public function testLazyByIdWithGaps()
    {
        $this->seed([1, 2, 5, 9, 10]);

        list($rows, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->lazy_by_id(2));
        });

        $this->assertEquals(['item1', 'item2', 'item5', 'item9', 'item10'], $this->names($rows));
        $this->assertCount(3, $sql);
        $this->assertContains('"id" > ?', $sql[1]);
    }

    /**
     * @group system
     */
    public function testLazyByIdDesc()
    {
        $this->seed([1, 2, 5, 9, 10]);

        list($rows, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->order_by('id', 'asc')->lazy_by_id_desc(2));
        });

        $this->assertEquals(['item10', 'item9', 'item5', 'item2', 'item1'], $this->names($rows));
        $this->assertContains('"id" < ?', $sql[1]);
    }

    /**
     * @group system
     */
    public function testLazyByIdExactMultipleAndEmpty()
    {
        $this->seed(4);
        list($rows, $sql) = $this->logged(function () {
            return iterator_to_array($this->items()->lazy_by_id(2));
        });
        $this->assertCount(4, $rows);
        $this->assertCount(3, $sql);

        $this->seed(0);
        $this->assertEquals([], iterator_to_array($this->items()->lazy_by_id(2)));
    }

    /**
     * @group system
     */
    public function testLazyByIdWithAlias()
    {
        $this->seed(3);
        $rows = iterator_to_array($this->items()->select(['id as key', 'name'])->lazy_by_id(2, 'id', 'key'));
        $this->assertEquals(['item1', 'item2', 'item3'], $this->names($rows));
    }

    /**
     * @group system
     */
    public function testLazyByIdThrowsWithoutColumnInResult()
    {
        $this->seed(3);
        $this->setExpectedException('\RuntimeException', '[id] column is not present');
        iterator_to_array($this->items()->select(['name'])->lazy_by_id(2));
    }

    /**
     * @group system
     */
    public function testLazyByIdRejectsBadChunkSize()
    {
        $this->setExpectedException('\InvalidArgumentException');
        $this->items()->lazy_by_id_desc(0);
    }

    // -------------------------------------------------------------------------
    // chunk_by_id() / chunk_by_id_desc() / each_by_id()
    // -------------------------------------------------------------------------

    /**
     * @group system
     */
    public function testChunkById()
    {
        $this->seed([1, 3, 4, 8, 9]);
        $chunks = [];

        $result = $this->items()->chunk_by_id(2, function ($rows, $page) use (&$chunks) {
            $chunks[$page] = $this->names($rows);
        });

        $this->assertTrue($result);
        $this->assertEquals([1 => ['item1', 'item3'], 2 => ['item4', 'item8'], 3 => ['item9']], $chunks);
    }

    /**
     * @group system
     */
    public function testChunkByIdDesc()
    {
        $this->seed([1, 3, 4, 8, 9]);
        $chunks = [];

        $result = $this->items()->chunk_by_id_desc(2, function ($rows) use (&$chunks) {
            $chunks[] = $this->names($rows);
            return count($chunks) < 2;
        });

        $this->assertFalse($result);
        $this->assertEquals([['item9', 'item8'], ['item4', 'item3']], $chunks);
    }

    /**
     * @group system
     */
    public function testEachById()
    {
        $this->seed([2, 4, 6, 8, 10]);
        $seen = [];

        $result = $this->items()->each_by_id(function ($row, $key) use (&$seen) {
            $seen[$key] = $row->name;

            if ('item8' === $row->name) {
                return false;
            }
        }, 2);

        $this->assertFalse($result);
        $this->assertEquals([0 => 'item2', 1 => 'item4', 2 => 'item6', 3 => 'item8'], $seen);
    }
}
