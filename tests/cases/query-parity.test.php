<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;

class QueryParityTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('database.connections.qparity', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $pdo = Database::connection('qparity')->pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS qparity (id INTEGER PRIMARY KEY, name TEXT UNIQUE, grp INTEGER, qty INTEGER)');
        $pdo->exec('DELETE FROM qparity');

        foreach ([['alpha', 1, 5], ['beta', 1, 2], ['gamma', 2, 9]] as $row) {
            $pdo->exec("INSERT INTO qparity (name, grp, qty) VALUES ('$row[0]', $row[1], $row[2])");
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Database::connection('qparity')->pdo()->exec('DROP TABLE IF EXISTS qparity');
    }

    /**
     * Get a fresh query builder.
     *
     * @return \System\Database\Query
     */
    protected function query()
    {
        return Database::connection('qparity')->table('qparity');
    }

    /**
     * Test that get() returns a collection.
     *
     * @group system
     */
    public function testGetReturnsACollection()
    {
        $results = $this->query()->get();

        $this->assertInstanceOf('\System\Collection', $results);
        $this->assertEquals(3, count($results));
        $this->assertEquals('alpha', $results[0]->name);
        $this->assertEquals(['alpha', 'beta', 'gamma'], $results->pluck('name')->all());
    }

    /**
     * Test value() and pluck().
     *
     * @group system
     */
    public function testValueAndPluck()
    {
        $this->assertEquals('alpha', $this->query()->value('name'));
        $this->assertInstanceOf('\System\Collection', $this->query()->pluck('name'));
        $this->assertEquals(['alpha', 'beta', 'gamma'], $this->query()->pluck('name')->all());
        $this->assertEquals([1 => 'alpha', 2 => 'beta', 3 => 'gamma'], $this->query()->pluck('name', 'id')->all());
    }

    /**
     * Test where_like() and its variants.
     *
     * @group system
     */
    public function testWhereLike()
    {
        $this->assertEquals(['alpha'], $this->query()->where_like('name', 'alp%')->get()->pluck('name')->all());
        $this->assertEquals(2, count($this->query()->where_not_like('name', 'alp%')->get()));
        $this->assertContains('LIKE', $this->query()->where_like('name', 'a%')->to_sql());

        $any = $this->query()->where('name', '=', 'alpha')->or_where_like('name', 'bet%')->get();

        $this->assertEquals(2, count($any));
    }

    /**
     * Test that a reserved laravel method is not silently turned into a column.
     *
     * @group system
     */
    public function testReservedDynamicWhereThrows()
    {
        $this->setExpectedException('\Exception', 'Query::where_has()');
        $this->query()->where_has('posts');
    }

    /**
     * Test that a plain dynamic where still works.
     *
     * @group system
     */
    public function testDynamicWhereStillWorks()
    {
        $this->assertEquals('alpha', $this->query()->where_name('alpha')->first()->name);
    }

    /**
     * Test chunk() and each().
     *
     * @group system
     */
    public function testChunkAndEach()
    {
        $seen = [];

        $this->query()->chunk(2, function ($rows) use (&$seen) {
            foreach ($rows as $row) {
                $seen[] = $row->name;
            }
        });

        $this->assertEquals(['alpha', 'beta', 'gamma'], $seen);

        $counted = 0;

        $this->query()->each(function ($row) use (&$counted) {
            ++$counted;
        });

        $this->assertEquals(3, $counted);
    }

    /**
     * Test that returning false from the chunk callback stops the iteration.
     *
     * @group system
     */
    public function testChunkStopsWhenCallbackReturnsFalse()
    {
        $pages = 0;

        $result = $this->query()->chunk(1, function ($rows) use (&$pages) {
            ++$pages;
            return false;
        });

        $this->assertFalse($result);
        $this->assertEquals(1, $pages);
    }

    /**
     * Test when(), unless() and tap().
     *
     * @group system
     */
    public function testWhenUnlessAndTap()
    {
        $filtered = $this->query()->when(true, function ($query) {
            return $query->where('grp', '=', 1);
        })->get();

        $untouched = $this->query()->when(false, function ($query) {
            return $query->where('grp', '=', 1);
        })->get();

        $fallback = $this->query()->when(false, function ($query) {
            return $query;
        }, function ($query) {
            return $query->where('grp', '=', 2);
        })->get();

        $this->assertEquals(2, count($filtered));
        $this->assertEquals(3, count($untouched));
        $this->assertEquals(1, count($fallback));
        $this->assertEquals(1, count($this->query()->unless(false, function ($query) {
            return $query->where('grp', '=', 2);
        })->get()));

        $tapped = null;

        $this->query()->tap(function ($query) use (&$tapped) {
            $tapped = $query;
        });

        $this->assertInstanceOf('\System\Database\Query', $tapped);
    }

    /**
     * Test sole().
     *
     * @group system
     */
    public function testSole()
    {
        $this->assertEquals('alpha', $this->query()->where('id', '=', 1)->sole()->name);

        try {
            $this->query()->where('id', '>', 9999)->sole();
            $this->fail('sole() should complain when there is no record.');
        } catch (\Exception $e) {
            $this->assertContains('No record found', $e->getMessage());
        }

        try {
            $this->query()->sole();
            $this->fail('sole() should complain when there is more than one record.');
        } catch (\Exception $e) {
            $this->assertContains('More than one record', $e->getMessage());
        }
    }

    /**
     * Test the raw clause helpers.
     *
     * @group system
     */
    public function testRawClauseHelpers()
    {
        $this->assertContains('ORDER BY qty desc', $this->query()->order_by_raw('qty desc')->to_sql());
        $this->assertContains('GROUP BY grp, qty', $this->query()->group_by_raw('grp, qty')->to_sql());
        $this->assertContains('SELECT COUNT(*) AS c', $this->query()->select_raw('COUNT(*) AS c')->to_sql());
        $this->assertContains('RANDOM()', $this->query()->in_random_order()->to_sql());

        $this->assertEquals(['gamma', 'alpha', 'beta'], $this->query()->order_by_raw('qty desc')->get()->pluck('name')->all());
    }

    /**
     * Test having_raw() and or_having().
     *
     * @group system
     */
    public function testHavingHelpers()
    {
        $sql = $this->query()->group_by('grp')->having_raw('COUNT(*) > 1')->to_sql();

        $this->assertContains('HAVING COUNT(*) > 1', $sql);

        $sql = $this->query()->group_by('grp')->having('grp', '>', 0)->or_having('grp', '=', 2)->to_sql();

        $this->assertContains('OR "grp" = ?', $sql);

        $grouped = $this->query()->group_by('grp')->having_raw('COUNT(*) > 1')->get(['grp']);

        $this->assertEquals(1, count($grouped));
    }

    /**
     * Test re_order().
     *
     * @group system
     */
    public function testReOrder()
    {
        $query = $this->query()->order_by('qty', 'desc');

        $this->assertContains('ORDER BY', $query->to_sql());
        $this->assertNotContains('ORDER BY', $query->re_order()->to_sql());
        $this->assertContains('ORDER BY', $this->query()->order_by('id', 'asc')->re_order('qty', 'desc')->to_sql());
    }

    /**
     * Test right_join() and cross_join().
     *
     * @group system
     */
    public function testJoinHelpers()
    {
        $this->assertContains('RIGHT JOIN', $this->query()->right_join('other', 'a', '=', 'b')->to_sql());
        $this->assertContains('CROSS JOIN "other"', $this->query()->cross_join('other')->to_sql());
        $this->assertNotContains('CROSS JOIN "other" ON', $this->query()->cross_join('other')->to_sql());
    }

    /**
     * Test add_select().
     *
     * @group system
     */
    public function testAddSelect()
    {
        $sql = $this->query()->select(['id'])->add_select(['name'])->to_sql();

        $this->assertContains('"id", "name"', $sql);
    }

    /**
     * Test update_or_insert().
     *
     * @group system
     */
    public function testUpdateOrInsert()
    {
        $this->assertTrue($this->query()->update_or_insert(['name' => 'delta'], ['grp' => 3, 'qty' => 1]));
        $this->assertEquals(3, $this->query()->where('name', '=', 'delta')->value('grp'));

        $this->query()->update_or_insert(['name' => 'delta'], ['qty' => 42]);

        $this->assertEquals(42, $this->query()->where('name', '=', 'delta')->value('qty'));
        $this->assertEquals(1, count($this->query()->where('name', '=', 'delta')->get()));
    }

    /**
     * Test insert_or_ignore().
     *
     * @group system
     */
    public function testInsertOrIgnore()
    {
        $this->query()->insert_or_ignore(['name' => 'alpha', 'grp' => 9, 'qty' => 9]);

        $this->assertEquals(3, count($this->query()->get()));
        $this->assertEquals(1, $this->query()->where('name', '=', 'alpha')->value('grp'));

        $this->query()->insert_or_ignore(['name' => 'epsilon', 'grp' => 4, 'qty' => 4]);

        $this->assertEquals(4, count($this->query()->get()));
    }

    /**
     * Test the row locking clauses on every grammar.
     *
     * @group system
     */
    public function testRowLocking()
    {
        $connection = Database::connection('qparity');
        $grammars = [
            'Grammar' => ['FOR UPDATE', 'FOR SHARE'],
            'MySQL' => ['FOR UPDATE', 'LOCK IN SHARE MODE'],
            'Postgres' => ['FOR UPDATE', 'FOR SHARE'],
        ];

        foreach ($grammars as $name => $clauses) {
            $class = '\\System\\Database\\Query\\Grammars\\' . $name;
            $exclusive = new \System\Database\Query($connection, new $class($connection), 'qparity');
            $shared = new \System\Database\Query($connection, new $class($connection), 'qparity');
            $plain = new \System\Database\Query($connection, new $class($connection), 'qparity');

            $this->assertContains($clauses[0], $exclusive->lock_for_update()->to_sql(), $name);
            $this->assertContains($clauses[1], $shared->shared_lock()->to_sql(), $name);
            $this->assertNotContains('FOR UPDATE', $plain->to_sql(), $name);
        }
    }

    /**
     * Test that sql server asks for a lock through a table hint.
     *
     * @group system
     */
    public function testRowLockingOnSqlServerUsesATableHint()
    {
        $connection = Database::connection('qparity');
        $grammar = new \System\Database\Query\Grammars\SQLServer($connection);
        $query = new \System\Database\Query($connection, $grammar, 'qparity');

        $sql = $query->where('id', '=', 1)->lock_for_update()->to_sql();

        $this->assertContains('[qparity] WITH (ROWLOCK, UPDLOCK, HOLDLOCK)', $sql);
        $this->assertNotContains('FOR UPDATE', $sql);
    }

    /**
     * Test that sqlite compiles no lock at all.
     *
     * @group system
     */
    public function testRowLockingIsIgnoredOnSqlite()
    {
        $sql = $this->query()->where('id', '=', 1)->lock_for_update()->to_sql();

        $this->assertNotContains('FOR UPDATE', $sql);
        $this->assertNotContains('LOCK', $sql);
        $this->assertEquals(1, count($this->query()->where('id', '=', 1)->lock_for_update()->get()));
    }

    /**
     * Test a raw lock clause.
     *
     * @group system
     */
    public function testRawLockClause()
    {
        $connection = Database::connection('qparity');
        $grammar = new \System\Database\Query\Grammars\MySQL($connection);
        $query = new \System\Database\Query($connection, $grammar, 'qparity');

        $this->assertContains('FOR UPDATE NOWAIT', $query->lock('FOR UPDATE NOWAIT')->to_sql());
    }

    /**
     * Test that copy() carries the lock and reset() drops it.
     *
     * @group system
     */
    public function testLockSurvivesCopyAndIsDroppedByReset()
    {
        $connection = Database::connection('qparity');
        $grammar = new \System\Database\Query\Grammars\MySQL($connection);
        $query = new \System\Database\Query($connection, $grammar, 'qparity');
        $query->where('id', '=', 1)->lock_for_update();

        $this->assertContains('FOR UPDATE', $query->copy()->to_sql());
        $this->assertNotContains('FOR UPDATE', $query->copy()->reset()->to_sql());
    }

    /**
     * Test increment() and decrement() with extra values.
     *
     * @group system
     */
    public function testIncrementWithExtraValues()
    {
        $this->query()->where('id', '=', 1)->increment('qty', 5, ['grp' => 7]);

        $row = $this->query()->find(1);

        $this->assertEquals(10, $row->qty);
        $this->assertEquals(7, $row->grp);
    }
}
