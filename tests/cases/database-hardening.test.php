<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;

class DatabaseHardeningTest extends \PHPUnit_Framework_TestCase
{
    /**
     * The default connection before the test.
     *
     * @var string
     */
    protected $connection;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->connection = Config::get('database.default');
        Config::set('database.default', 'sqlite');
        Database::$connections = [];

        Schema::create('hard_users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        foreach (['a@x.co', 'a@x.co', 'b@x.co', 'b@x.co', 'c@x.co'] as $email) {
            Database::table('hard_users')->insert(['email' => $email]);
        }
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('hard_users');
        Database::$connections = [];
        Config::set('database.default', $this->connection);
    }

    /**
     * A distinct query's page total counts the distinct rows.
     *
     * @group system
     */
    public function testDistinctPaginationTotal()
    {
        $page = Database::table('hard_users')->distinct()->select(['email'])->paginate(2);

        $this->assertEquals(3, $page->total);
        $this->assertCount(2, $page->results);
        $this->assertEquals(5, Database::table('hard_users')->paginate(2)->total);
    }

    /**
     * Connectors, join types and aggregate functions are whitelisted.
     *
     * @group system
     */
    public function testSqlKeywordsAreWhitelisted()
    {
        $this->assertContains(
            'AND NOT',
            Database::table('hard_users')->where('id', '=', 1)->where('id', '=', 2, 'and not')->to_sql()
        );
        $this->assertEquals(5, Database::table('hard_users')->aggregate('count', ['id']));

        $attempts = [
            function () {
                return Database::table('hard_users')->where('id', '=', 1)->where('id', '=', 2, 'OR 1=1 --')->to_sql();
            },
            function () {
                return Database::table('hard_users')->join('hard_users AS b', 'b.id', '=', 'hard_users.id', 'INNER JOIN x ON 1=1 --')->to_sql();
            },
            function () {
                return Database::table('hard_users')->aggregate('SLEEP', ['id']);
            },
            function () {
                return Database::table('hard_users')->having('id', '>', 1, 'OR 1=1')->to_sql();
            },
        ];

        foreach ($attempts as $index => $attempt) {
            try {
                $attempt();
                $this->fail('Accepted attempt #'.$index);
            } catch (\PHPUnit_Framework_AssertionFailedError $e) {
                throw $e;
            } catch (\InvalidArgumentException $e) {
                $this->assertRegExp('/Invalid (boolean connector|join type|aggregate function)/', $e->getMessage());
            }
        }
    }

    /**
     * The MySQL DSN carries a valid charset, so PDO::quote() escapes with it.
     *
     * @group system
     */
    public function testMysqlDsnCarriesTheCharset()
    {
        $connector = new \System\Database\Connectors\MySQL();
        $dsn = new \ReflectionMethod($connector, 'dsn');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $dsn->setAccessible(true);

        $config = ['host' => 'db', 'database' => 'app', 'charset' => 'utf8mb4'];
        $this->assertEquals('mysql:host=db;dbname=app;charset=utf8mb4', $dsn->invoke($connector, $config));

        $config['charset'] = 'utf8;evil=1';
        $this->assertEquals('mysql:host=db;dbname=app', $dsn->invoke($connector, $config));
    }
}
