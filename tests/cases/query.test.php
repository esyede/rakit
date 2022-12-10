<?php

defined('DS') or exit('No direct script access.');

use System\Database as DB;

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
     * Test untuk method DB::find().
     *
     * @group system
     */
    public function testFindMethodCanReturnByID()
    {
        $result = DB::table('query_test')->find(1);

        $this->assertEquals('budi@example.com', $result->email);
    }

    /**
     * Test untuk method DB::select().
     *
     * @group system
     */
    public function testSelectMethodLimitsColumns()
    {
        $result = DB::table('query_test')->select(['email'])->first();

        $this->assertTrue(isset($result->email));
        $this->assertFalse(isset($result->name));
    }

    /**
     * Test untuk method DB::raw_where().
     *
     * @group system
     */
    public function testRawWhereCanBeUsed()
    {
        // Ngetesnya gimana yang ini cok!
    }

    /**
     * Test untuk method DB::where() dengan 2 parameter.
     *
     * @group system
     */
    public function testWhereWithTwoParameters()
    {
        $result = DB::table('users')->where('email', 'agung@gmail.com')->first();

        $this->assertTrue(isset($result->email));
        $this->assertFalse(is_null($result));
    }

    /**
     * Test untuk method DB::where() dengan 3 parameter.
     *
     * @group system
     */
    public function testWhereWithThreeParameters()
    {
        $result = DB::table('users')->where('email', '=', 'agung@gmail.com')->first();

        $this->assertTrue(isset($result->email));
        $this->assertFalse(is_null($result));
    }

    /**
     * Test untuk method DB::where() dengan parameter ke-3
     * berisi nilai yang salah (null atau object).
     *
     * @group system
     */
    public function testWhereWithThirdParameterIsInvalid()
    {
        try {
            DB::table('users')->where('email', '!=', null)->first();
        } catch (\Exception $e) {
            $this->assertTrue(($e instanceof \InvalidArgumentException || $e instanceof \PDOException));
        }
    }
}
