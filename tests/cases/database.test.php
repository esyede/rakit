<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;

class DatabaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Database::$connections = [];
    }

    /**
     * Test for Database::connection().
     *
     * @group system
     */
    public function testConnectionMethodReturnsConnection()
    {
        $test = DatabaseConnectStub::connection();
        $this->assertTrue(isset(Database::$connections[Config::get('database.default')]));

        $test = DatabaseConnectStub::connection('mysql');
        $this->assertTrue(isset(Database::$connections['mysql']));

        $test = Config::get('database.connections.mysql');
        $this->assertEquals(Database::$connections['mysql']->pdo()->testConfigs, $test);
    }

    /**
     * Test for Database::profile().
     *
     * @group system
     */
    public function testProfileMethodReturnsQueries()
    {
        \System\Database\Connection::$queries = ['Budi'];
        $this->assertEquals(['Budi'], Database::profile());

        \System\Database\Connection::$queries = [];
    }

    /**
     * Test for Database::__callStatic().
     *
     * @group system
     */
    public function testConnectionMethodsCanBeCalledStaticly()
    {
        $this->assertEquals('sqlite', Database::driver());
    }
}

class DatabaseConnectStub extends \System\Database
{
    protected static function connect(array $config)
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
