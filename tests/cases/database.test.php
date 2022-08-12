<?php

defined('DS') or exit('No direct script access.');

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        DB::$connections = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        DB::$connections = [];
    }

    /**
     * Test untuk method DB::connection().
     *
     * @group system
     */
    public function testConnectionMethodReturnsConnection()
    {
        $test = DatabaseConnectStub::connection();
        $this->assertTrue(isset(DB::$connections[Config::get('database.default')]));

        $test = DatabaseConnectStub::connection('mysql');
        $this->assertTrue(isset(DB::$connections['mysql']));

        $test = Config::get('database.connections.mysql');
        $this->assertEquals(DB::$connections['mysql']->pdo()->testConfigs, $test);
    }

    /**
     * Test untuk method DB::profile().
     *
     * @group system
     */
    public function testProfileMethodReturnsQueries()
    {
        \System\Database\Connection::$queries = ['Budi'];

        $this->assertEquals(['Budi'], DB::profile());

        \System\Database\Connection::$queries = [];
    }

    /**
     * Test untuk method DB::__callStatic().
     *
     * @group system
     */
    public function testConnectionMethodsCanBeCalledStaticly()
    {
        $this->assertEquals('sqlite', DB::driver());
    }
}

class DatabaseConnectStub extends \System\Database
{
    protected static function connect($config)
    {
        return new PDOStub($config);
    }
}

class PDOStub extends \PDO
{
    public $testConfigs;

    public function __construct($config)
    {
        $this->testConfigs = $config;
    }

    public function foo()
    {
        return 'foo';
    }
}
