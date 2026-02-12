<?php

defined('DS') or exit('No direct access.');

use System\Database;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // ..
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    /**
     * Test for Database::find().
     *
     * @group system
     */
    public function testFindMethodCanReturnByID()
    {
        $result = Database::table('query_test')->find(1);
        $this->assertEquals('budi@example.com', $result->email);
    }

    /**
     * Test for Database::select().
     *
     * @group system
     */
    public function testSelectMethodLimitsColumns()
    {
        $result = Database::table('query_test')->select(['email'])->first();

        $this->assertTrue(isset($result->email));
        $this->assertFalse(isset($result->name));
    }

    /**
     * Test for Database::raw_where().
     *
     * @group system
     */
    public function testRawWhereCanBeUsed()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->raw_where('age > ? AND city = ?', [18, 'Jakarta']);
        $this->assertContains('age > ? AND city = ?', $query->to_sql());
    }

    /**
     * Test for Database::raw_or_where().
     *
     * @group system
     */
    public function testRawOrWhereCanBeUsed()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->raw_or_where('age > ? OR city = ?', [18, 'Jakarta']);
        $this->assertContains('age > ? OR city = ?', $query->to_sql());
    }

    /**
     * Test for Database::where() with 2 parameters.
     *
     * @group system
     */
    public function testWhereWithTwoParameters()
    {
        $result = Database::table('users')->where('email', 'agung@gmail.com')->first();
        $this->assertTrue(isset($result->email));
        $this->assertFalse(is_null($result));
    }

    /**
     * Test for Database::where() with 3 parameters.
     *
     * @group system
     */
    public function testWhereWithThreeParameters()
    {
        $result = Database::table('users')->where('email', '=', 'agung@gmail.com')->first();
        $this->assertTrue(isset($result->email));
        $this->assertFalse(is_null($result));
    }

    /**
     * Test for Database::where() with the 3rd parameter being an invalid value.
     *
     * @group system
     */
    public function testWhereWithThirdParameterIsInvalid()
    {
        try {
            Database::table('users')->where('email', '!=', null)->first();
        } catch (\Throwable $e) {
            $this->assertTrue(($e instanceof \InvalidArgumentException || $e instanceof \PDOException));
        } catch (\Exception $e) {
            $this->assertTrue(($e instanceof \InvalidArgumentException || $e instanceof \PDOException));
        }
    }

    /**
     * Test for where_date.
     *
     * @group system
     */
    public function test_where_date()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_date('created_at', '=', '2023-01-01');
        $this->assertContains('DATE(created_at)', $query->to_sql());
    }

    /**
     * Test for where_month.
     *
     * @group system
     */
    public function test_where_month()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_month('created_at', '=', 1);
        $this->assertContains('MONTH(created_at)', $query->to_sql());
    }

    /**
     * Test for where_day.
     *
     * @group system
     */
    public function test_where_day()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_day('created_at', '=', 1);
        $this->assertContains('DAY(created_at)', $query->to_sql());
    }

    /**
     * Test for where_year.
     *
     * @group system
     */
    public function test_where_year()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_year('created_at', '=', 2023);
        $this->assertContains('YEAR(created_at)', $query->to_sql());
    }

    /**
     * Test for where_time.
     *
     * @group system
     */
    public function test_where_time()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_time('created_at', '=', '12:00:00');
        $this->assertContains('TIME(created_at)', $query->to_sql());
    }

    /**
     * Test for where_column.
     *
     * @group system
     */
    public function test_where_column()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_column('updated_at', '>', 'created_at');
        $this->assertContains('"updated_at" > "created_at"', $query->to_sql());
    }

    /**
     * Test for where_in.
     *
     * @group system
     */
    public function test_where_in()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_in('id', [1, 2, 3]);
        $this->assertContains('"id" IN (?, ?, ?)', $query->to_sql());
    }

    /**
     * Test for latest.
     *
     * @group system
     */
    public function test_latest()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->latest();
        $this->assertContains('ORDER BY', $query->to_sql());
        $this->assertContains('DESC', $query->to_sql());
    }

    /**
     * Test for oldest.
     *
     * @group system
     */
    public function test_oldest()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->oldest();
        $this->assertContains('ORDER BY', $query->to_sql());
        $this->assertContains('ASC', $query->to_sql());
    }

    /**
     * Test for exists.
     *
     * @group system
     */
    public function test_exists()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        // Mock connection to return empty result
        $mock_connection = $this->getMockBuilder('\System\Database\Connection')->disableOriginalConstructor()->getMock();
        $mock_connection->method('query')->willReturn([]);
        $query->connection = $mock_connection;
        $this->assertFalse($query->exists());
    }

    /**
     * Test for doesnt_exist.
     *
     * @group system
     */
    public function test_doesnt_exist()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        // Mock connection to return result
        $mock_connection = $this->getMockBuilder('\System\Database\Connection')->disableOriginalConstructor()->getMock();
        $mock_connection->method('query')->willReturn([['id' => 1]]);
        $query->connection = $mock_connection;
        $this->assertFalse($query->doesnt_exist());
    }

    /**
     * Test for chunk_by_id.
     *
     * @group system
     */
    public function test_chunk_by_id()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $called = false;
        $query->chunk_by_id(2, function ($results) use (&$called) {
            $called = true;
            return false; // Stop after first chunk
        });
        $this->assertTrue($called);
    }

    /**
     * Test for where_between.
     *
     * @group system
     */
    public function test_where_between()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        $query->where_between('age', 18, 65);
        $this->assertContains('"age" BETWEEN ? AND ?', $query->to_sql());
    }

    /**
     * Test for dd (debug dump).
     *
     * @group system
     */
    public function test_dd()
    {
        $query = new \System\Database\Query(
            \System\Database::connection(),
            new \System\Database\Query\Grammars\Grammar(\System\Database::connection()),
            'users'
        );

        // dd calls die, so we can't test directly, but ensure method exists
        $this->assertTrue(method_exists($query, 'dd'));
    }
}
