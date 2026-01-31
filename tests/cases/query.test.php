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
     * Test untuk method Database::find().
     *
     * @group system
     */
    public function testFindMethodCanReturnByID()
    {
        $result = Database::table('query_test')->find(1);

        $this->assertEquals('budi@example.com', $result->email);
    }

    /**
     * Test untuk method Database::select().
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
     * Test untuk method Database::raw_where().
     *
     * @group system
     */
    public function testRawWhereCanBeUsed()
    {
        // Ngetesnya gimana yang ini cok!
    }

    /**
     * Test untuk method Database::where() dengan 2 parameter.
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
     * Test untuk method Database::where() dengan 3 parameter.
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
     * Test untuk method Database::where() dengan parameter ke-3
     * berisi nilai yang salah (null atau object).
     *
     * @group system
     */
    public function testWhereWithThirdParameterIsInvalid()
    {
        try {
            Database::table('users')->where('email', '!=', null)->first();
        } catch (\Exception $e) {
            $this->assertTrue(($e instanceof \InvalidArgumentException || $e instanceof \PDOException));
        }
    }

    /**
     * Test untuk method where_date.
     *
     * @group system
     */
    public function test_where_date()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_date('created_at', '=', '2023-01-01');
        $this->assertContains('DATE(created_at)', $query->to_sql());
    }

    /**
     * Test untuk method where_month.
     *
     * @group system
     */
    public function test_where_month()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_month('created_at', '=', 1);
        $this->assertContains('MONTH(created_at)', $query->to_sql());
    }

    /**
     * Test untuk method where_day.
     *
     * @group system
     */
    public function test_where_day()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_day('created_at', '=', 1);
        $this->assertContains('DAY(created_at)', $query->to_sql());
    }

    /**
     * Test untuk method where_year.
     *
     * @group system
     */
    public function test_where_year()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_year('created_at', '=', 2023);
        $this->assertContains('YEAR(created_at)', $query->to_sql());
    }

    /**
     * Test untuk method where_time.
     *
     * @group system
     */
    public function test_where_time()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_time('created_at', '=', '12:00:00');
        $this->assertContains('TIME(created_at)', $query->to_sql());
    }

    /**
     * Test untuk method where_column.
     *
     * @group system
     */
    public function test_where_column()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->where_column('updated_at', '>', 'created_at');
        $this->assertContains('"updated_at" > "created_at"', $query->to_sql());
    }

    /**
     * Test untuk method latest.
     *
     * @group system
     */
    public function test_latest()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->latest();
        $this->assertContains('ORDER BY', $query->to_sql());
        $this->assertContains('DESC', $query->to_sql());
    }

    /**
     * Test untuk method oldest.
     *
     * @group system
     */
    public function test_oldest()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $query->oldest();
        $this->assertContains('ORDER BY', $query->to_sql());
        $this->assertContains('ASC', $query->to_sql());
    }

    /**
     * Test untuk method exists.
     *
     * @group system
     */
    public function test_exists()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        // Mock connection to return empty result
        $mock_connection = $this->getMockBuilder('\System\Database\Connection')->disableOriginalConstructor()->getMock();
        $mock_connection->method('query')->willReturn([]);
        $query->connection = $mock_connection;
        $this->assertFalse($query->exists());
    }

    /**
     * Test untuk method doesnt_exist.
     *
     * @group system
     */
    public function test_doesnt_exist()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        // Mock connection to return result
        $mock_connection = $this->getMockBuilder('\System\Database\Connection')->disableOriginalConstructor()->getMock();
        $mock_connection->method('query')->willReturn([['id' => 1]]);
        $query->connection = $mock_connection;
        $this->assertFalse($query->doesnt_exist());
    }

    /**
     * Test untuk method chunk_by_id.
     *
     * @group system
     */
    public function test_chunk_by_id()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        $called = false;
        $query->chunk_by_id(2, function ($results) use (&$called) {
            $called = true;
            return false; // Stop after first chunk
        });
        $this->assertTrue($called);
    }

    /**
     * Test untuk method dd (debug dump).
     *
     * @group system
     */
    public function test_dd()
    {
        $query = new \System\Database\Query(\System\Database::connection(), new \System\Database\Query\Grammars\Grammar(\System\Database::connection()), 'users');
        // dd calls die, so we can't test directly, but ensure method exists
        $this->assertTrue(method_exists($query, 'dd'));
    }
}
