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

    /**
     * Test for Database::disconnect().
     *
     * @group system
     */
    public function testDisconnectClosesTheConnection()
    {
        $connection = Database::connection();

        $this->assertTrue($connection->connected());

        Database::disconnect();

        $this->assertFalse($connection->connected());
    }

    /**
     * A closed connection says so instead of handing out a PDO that is gone.
     *
     * @group system
     */
    public function testClosedConnectionRefusesToRunAQuery()
    {
        $connection = Database::connection();
        Database::disconnect();

        try {
            $connection->pdo();
            $this->fail('Expected the closed connection to be refused.');
        } catch (\Exception $e) {
            $this->assertContains('has been closed', $e->getMessage());
        }
    }

    /**
     * Asking for the connection again opens it, on the same instance, so code
     * holding on to it keeps working.
     *
     * @group system
     */
    public function testConnectionReopensAClosedConnection()
    {
        $connection = Database::connection();
        Database::disconnect();

        $reopened = Database::connection();

        $this->assertSame($connection, $reopened);
        $this->assertTrue($connection->connected());
        $this->assertInstanceOf('PDO', $connection->pdo());
    }

    /**
     * Test for Database::reconnect().
     *
     * @group system
     */
    public function testReconnectKeepsTheSameInstance()
    {
        $connection = Database::connection();
        $pdo = $connection->pdo();

        $reconnected = Database::reconnect();

        $this->assertSame($connection, $reconnected);
        $this->assertTrue($reconnected->connected());
        $this->assertNotSame($pdo, $reconnected->pdo());
    }

    /**
     * Reconnecting a connection that was never opened simply opens it.
     *
     * @group system
     */
    public function testReconnectOpensAConnectionThatWasNeverUsed()
    {
        $this->assertFalse(isset(Database::$connections['sqlite']));

        $connection = Database::reconnect();

        $this->assertTrue($connection->connected());
    }

    /**
     * Test for Database::purge().
     *
     * @group system
     */
    public function testPurgeForgetsTheConnection()
    {
        $connection = Database::connection();

        Database::purge();

        $this->assertFalse(isset(Database::$connections['sqlite']));
        $this->assertFalse($connection->connected());
        $this->assertNotSame($connection, Database::connection());
    }

    /**
     * Disconnecting drops the transaction nesting with the connection, so the
     * next transaction is a real one and not a savepoint of a dead one.
     *
     * @group system
     */
    public function testDisconnectResetsTheTransactionLevel()
    {
        $connection = Database::connection();
        $connection->begin_transaction();
        $connection->begin_transaction();

        $this->assertEquals(2, $connection->transaction_level());

        Database::disconnect();

        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Closing a connection that was never opened is not an error.
     *
     * @group system
     */
    public function testDisconnectingAnUnusedConnectionIsHarmless()
    {
        Database::disconnect();
        Database::purge();

        $this->assertFalse(isset(Database::$connections['sqlite']));
    }

    /**
     * Reopening keeps the configuration the connection was built with, while
     * purging goes back to the configuration file.
     *
     * @group system
     */
    public function testPurgePicksUpAChangedConfiguration()
    {
        $original = Config::get('database.connections.mysql');

        try {
            DatabaseConnectStub::connection('mysql');

            $changed = $original;
            $changed['database'] = 'yang-lain';
            Config::set('database.connections.mysql', $changed);

            DatabaseConnectStub::disconnect('mysql');
            $reopened = DatabaseConnectStub::connection('mysql');

            $this->assertEquals($original['database'], $reopened->pdo()->testConfigs['database']);

            DatabaseConnectStub::purge('mysql');
            $rebuilt = DatabaseConnectStub::connection('mysql');

            $this->assertEquals('yang-lain', $rebuilt->pdo()->testConfigs['database']);
        } catch (\Exception $e) {
            Config::set('database.connections.mysql', $original);
            throw $e;
        }

        Config::set('database.connections.mysql', $original);
    }

    /**
     * PDO attributes are read from the 'options' key of the connection, and
     * only from there.
     *
     * @group system
     */
    public function testPdoOptionsAreTakenFromTheOptionsKey()
    {
        $original = Config::get('database.connections.sqlite');

        try {
            $config = $original;
            $config['options'] = [PDO::ATTR_CASE => PDO::CASE_UPPER];
            Config::set('database.connections.sqlite', $config);

            $this->assertEquals(
                PDO::CASE_UPPER,
                Database::connection()->pdo()->getAttribute(PDO::ATTR_CASE)
            );

            Database::purge();

            $config = $original;
            $config[PDO::ATTR_CASE] = PDO::CASE_UPPER;
            Config::set('database.connections.sqlite', $config);

            $this->assertEquals(
                PDO::CASE_LOWER,
                Database::connection()->pdo()->getAttribute(PDO::ATTR_CASE)
            );
        } catch (\Exception $e) {
            Config::set('database.connections.sqlite', $original);
            throw $e;
        }

        Config::set('database.connections.sqlite', $original);
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
