<?php

defined('DS') or exit('No direct script access.');

class QueryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('application.key', 'mySecretKeyIsSoDarnLongSoPeopleCantRememberIt');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('application.key', '');
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
}
