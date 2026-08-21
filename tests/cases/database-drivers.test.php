<?php

defined('DS') or exit('No direct access.');

use System\Database\Query;
use System\Database\Connection;

/**
 * Covers the query grammars and the connectors of the drivers that cannot be
 * reached from the test suite's sqlite connection.
 *
 * Both only build strings, so no MySQL/Postgres/SQL Server server is needed.
 */
class DatabaseDriversTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Build a query grammar without needing a live connection.
     *
     * @param string $driver
     *
     * @return \System\Database\Query\Grammars\Grammar
     */
    protected function grammar($driver)
    {
        $reflection = new \ReflectionClass('\System\Database\Query\Grammars\\' . $driver);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Build a query bound to the given grammar.
     *
     * @param string $driver
     * @param string $table
     *
     * @return Query
     */
    protected function query($driver, $table = 'users')
    {
        $connection = (new \ReflectionClass('\System\Database\Connection'))->newInstanceWithoutConstructor();
        return new Query($connection, $this->grammar($driver), $table);
    }

    /**
     * Build a connector without needing a live connection.
     *
     * @param string $driver
     *
     * @return \System\Database\Connectors\Connector
     */
    protected function connector($driver)
    {
        $reflection = new \ReflectionClass('\System\Database\Connectors\\' . $driver);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Call a protected method on a connector.
     *
     * @param object $object
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     */
    protected function call($object, $method, array $arguments = [])
    {
        $reflection = new \ReflectionMethod($object, $method);
        PHP_VERSION_ID < 80100 && $reflection->setAccessible(true);

        return $reflection->invokeArgs($object, $arguments);
    }

    // -------------------------------------------------------------------------
    // Identifier wrapping
    // -------------------------------------------------------------------------

    /**
     * Every driver has its own identifier quoting.
     *
     * @group system
     */
    public function testIdentifierWrapping()
    {
        $this->assertEquals('`users`', $this->grammar('MySQL')->wrap('users'));
        $this->assertEquals('"users"', $this->grammar('Postgres')->wrap('users'));
        $this->assertEquals('[users]', $this->grammar('SQLServer')->wrap('users'));
        $this->assertEquals('"users"', $this->grammar('SQLite')->wrap('users'));
    }

    /**
     * A dotted identifier is wrapped segment by segment.
     *
     * @group system
     */
    public function testQualifiedIdentifierWrapping()
    {
        $this->assertEquals('`users`.`id`', $this->grammar('MySQL')->wrap('users.id'));
        $this->assertEquals('[users].[id]', $this->grammar('SQLServer')->wrap('users.id'));
    }

    /**
     * An alias keeps working even with extra spacing around 'as'.
     *
     * @group system
     */
    public function testAliasWrapping()
    {
        $this->assertEquals('`users`.`id` AS `uid`', $this->grammar('MySQL')->wrap('users.id as uid'));
        $this->assertEquals('`id` AS `uid`', $this->grammar('MySQL')->wrap('id  as  uid'));
        $this->assertEquals('"id" AS "uid"', $this->grammar('Postgres')->wrap('id AS uid'));
    }

    /**
     * The identifier quote character itself must be escaped.
     *
     * @group system
     */
    public function testIdentifierQuoteIsEscaped()
    {
        $this->assertEquals('`we`` ird`', $this->grammar('MySQL')->wrap('we` ird'));
        $this->assertEquals('"we"" ird"', $this->grammar('Postgres')->wrap('we" ird'));
        $this->assertEquals('[we]] ird]', $this->grammar('SQLServer')->wrap('we] ird'));
    }

    // -------------------------------------------------------------------------
    // SELECT compilation
    // -------------------------------------------------------------------------

    /**
     * Test a simple SELECT for every driver.
     *
     * @group system
     */
    public function testSelect()
    {
        $this->assertEquals(
            'SELECT * FROM `users` WHERE `id` = ?',
            $this->query('MySQL')->where('id', '=', 1)->to_sql()
        );

        $this->assertEquals(
            'SELECT * FROM "users" WHERE "id" = ?',
            $this->query('Postgres')->where('id', '=', 1)->to_sql()
        );

        $this->assertEquals(
            'SELECT * FROM [users] WHERE [id] = ?',
            $this->query('SQLServer')->where('id', '=', 1)->to_sql()
        );
    }

    /**
     * MySQL and Postgres use LIMIT / OFFSET.
     *
     * @group system
     */
    public function testLimitAndOffset()
    {
        $sql = $this->query('MySQL')->take(10)->skip(20)->to_sql();
        $this->assertContains('LIMIT 10', $sql);
        $this->assertContains('OFFSET 20', $sql);

        $sql = $this->query('Postgres')->take(10)->skip(20)->to_sql();
        $this->assertContains('LIMIT 10', $sql);
        $this->assertContains('OFFSET 20', $sql);
    }

    /**
     * A non numeric limit must never reach the statement.
     *
     * @group system
     */
    public function testLimitIsForcedToInteger()
    {
        $query = $this->query('MySQL');
        $query->limit = '10; DROP TABLE users';

        $sql = $query->to_sql();
        $this->assertContains('LIMIT 10', $sql);
        $this->assertNotContains('DROP TABLE', $sql);
    }

    /**
     * SQL Server has no LIMIT, it uses TOP instead.
     *
     * @group system
     */
    public function testSqlServerUsesTop()
    {
        $sql = $this->query('SQLServer')->take(10)->to_sql();

        $this->assertContains('SELECT TOP 10 *', $sql);
        $this->assertNotContains('LIMIT', $sql);
    }

    /**
     * With an offset SQL Server falls back to a ROW_NUMBER() window.
     *
     * @group system
     */
    public function testSqlServerUsesRowNumberForOffset()
    {
        $sql = $this->query('SQLServer')->take(10)->skip(20)->order_by('id', 'asc')->to_sql();

        $this->assertContains('ROW_NUMBER() OVER (ORDER BY [id] ASC) AS RowNum', $sql);
        $this->assertContains('WHERE RowNum BETWEEN 21 AND 30', $sql);
    }

    /**
     * Without an ORDER BY the window still needs one.
     *
     * @group system
     */
    public function testSqlServerOffsetWithoutOrdering()
    {
        $sql = $this->query('SQLServer')->skip(5)->to_sql();

        $this->assertContains('ORDER BY (SELECT 0)', $sql);
        $this->assertContains('WHERE RowNum >= 6', $sql);
    }

    /**
     * SQLite orders case-insensitively.
     *
     * @group system
     */
    public function testSqliteOrderingCollatesNoCase()
    {
        $sql = $this->query('SQLite')->order_by('name', 'asc')->to_sql();
        $this->assertContains('ORDER BY "name" COLLATE NOCASE ASC', $sql);
    }

    // -------------------------------------------------------------------------
    // INSERT compilation
    // -------------------------------------------------------------------------

    /**
     * Test a single row INSERT for every driver.
     *
     * @group system
     */
    public function testInsert()
    {
        $values = ['name' => 'Budi', 'age' => 30];

        $this->assertEquals(
            'INSERT INTO `users` (`name`, `age`) VALUES (?, ?)',
            $this->grammar('MySQL')->insert($this->query('MySQL'), $values)
        );

        $this->assertEquals(
            'INSERT INTO "users" ("name", "age") VALUES (?, ?)',
            $this->grammar('Postgres')->insert($this->query('Postgres'), $values)
        );

        $this->assertEquals(
            'INSERT INTO [users] ([name], [age]) VALUES (?, ?)',
            $this->grammar('SQLServer')->insert($this->query('SQLServer'), $values)
        );
    }

    /**
     * Test a multi row INSERT.
     *
     * @group system
     */
    public function testMultiRowInsert()
    {
        $values = [['name' => 'Budi'], ['name' => 'Ani']];

        $this->assertEquals(
            'INSERT INTO `users` (`name`) VALUES (?), (?)',
            $this->grammar('MySQL')->insert($this->query('MySQL'), $values)
        );

        // SQLite builds it with UNION SELECT instead.
        $this->assertEquals(
            'INSERT INTO "users" ("name") SELECT ? AS "name" UNION SELECT ? AS "name"',
            $this->grammar('SQLite')->insert($this->query('SQLite'), $values)
        );
    }

    /**
     * Only Postgres needs a RETURNING clause to read back the new id.
     *
     * @group system
     */
    public function testInsertGetId()
    {
        $values = ['name' => 'Budi'];

        $this->assertEquals(
            'INSERT INTO "users" ("name") VALUES (?) RETURNING id',
            $this->grammar('Postgres')->insert_get_id($this->query('Postgres'), $values, 'id')
        );

        $this->assertEquals(
            'INSERT INTO `users` (`name`) VALUES (?)',
            $this->grammar('MySQL')->insert_get_id($this->query('MySQL'), $values, 'id')
        );
    }

    // -------------------------------------------------------------------------
    // UPDATE and DELETE compilation
    // -------------------------------------------------------------------------

    /**
     * Test UPDATE for every driver.
     *
     * @group system
     */
    public function testUpdate()
    {
        $this->assertEquals(
            'UPDATE `users` SET `name` = ? WHERE `id` = ?',
            $this->grammar('MySQL')->update($this->query('MySQL')->where('id', '=', 1), ['name' => 'Budi'])
        );

        $this->assertEquals(
            'UPDATE [users] SET [name] = ? WHERE [id] = ?',
            $this->grammar('SQLServer')->update($this->query('SQLServer')->where('id', '=', 1), ['name' => 'Budi'])
        );
    }

    /**
     * Test DELETE for every driver.
     *
     * @group system
     */
    public function testDelete()
    {
        $this->assertEquals(
            'DELETE FROM `users` WHERE `id` = ?',
            $this->grammar('MySQL')->delete($this->query('MySQL')->where('id', '=', 1))
        );

        $this->assertEquals(
            'DELETE FROM "users" WHERE "id" = ?',
            $this->grammar('Postgres')->delete($this->query('Postgres')->where('id', '=', 1))
        );
    }

    // -------------------------------------------------------------------------
    // Connectors
    // -------------------------------------------------------------------------

    /**
     * Test the MySQL DSN.
     *
     * @group system
     */
    public function testMySqlDsn()
    {
        $connector = $this->connector('MySQL');

        $this->assertEquals(
            'mysql:host=localhost;dbname=rakit',
            $this->call($connector, 'dsn', [['host' => 'localhost', 'database' => 'rakit']])
        );

        $this->assertEquals(
            'mysql:host=localhost;dbname=rakit;port=3307',
            $this->call($connector, 'dsn', [['host' => 'localhost', 'database' => 'rakit', 'port' => 3307]])
        );

        $this->assertContains(
            ';unix_socket=/tmp/mysql.sock',
            $this->call($connector, 'dsn', [[
                'host' => 'localhost',
                'database' => 'rakit',
                'unix_socket' => '/tmp/mysql.sock',
            ]])
        );
    }

    /**
     * Test the Postgres DSN.
     *
     * @group system
     */
    public function testPostgresDsn()
    {
        $connector = $this->connector('Postgres');

        $this->assertEquals(
            'pgsql:host=localhost;dbname=rakit',
            $this->call($connector, 'dsn', [['host' => 'localhost', 'database' => 'rakit']])
        );

        $this->assertEquals(
            'pgsql:dbname=rakit',
            $this->call($connector, 'dsn', [['database' => 'rakit']])
        );

        $this->assertEquals(
            'pgsql:host=localhost;dbname=rakit;port=5433',
            $this->call($connector, 'dsn', [['host' => 'localhost', 'database' => 'rakit', 'port' => 5433]])
        );
    }

    /**
     * Test the SQLite DSN.
     *
     * @group system
     */
    public function testSqliteDsn()
    {
        $connector = $this->connector('SQLite');

        $this->assertEquals(
            'sqlite::memory:',
            $this->call($connector, 'dsn', [['database' => ':memory:']])
        );

        $this->assertEquals(
            'sqlite:' . path('storage') . 'database' . DS . 'application.sqlite',
            $this->call($connector, 'dsn', [['database' => 'application']])
        );
    }

    /**
     * sqlsrv must win over dblib, and the two spell the port differently.
     *
     * @group system
     */
    public function testSqlServerDsn()
    {
        $connector = $this->connector('SQLServer');
        $config = ['host' => 'db.local', 'database' => 'rakit', 'port' => 1433];

        $this->assertEquals(
            'sqlsrv:Server=db.local,1433;Database=rakit',
            $this->call($connector, 'dsn', [$config, ['sqlsrv']])
        );

        // Both available: sqlsrv is the one that gets used.
        $this->assertEquals(
            'sqlsrv:Server=db.local,1433;Database=rakit',
            $this->call($connector, 'dsn', [$config, ['dblib', 'sqlsrv']])
        );

        // Only dblib: it wants 'host=server:port'.
        $this->assertEquals(
            'dblib:host=db.local:1433;dbname=rakit',
            $this->call($connector, 'dsn', [$config, ['dblib']])
        );

        // Without a port at all.
        $this->assertEquals(
            'sqlsrv:Server=db.local;Database=rakit',
            $this->call($connector, 'dsn', [['host' => 'db.local', 'database' => 'rakit'], ['sqlsrv']])
        );
    }

    /**
     * Connection options given in the config win over the defaults.
     *
     * @group system
     */
    public function testConnectorOptionsAreMerged()
    {
        $connector = $this->connector('MySQL');

        $options = $this->call($connector, 'options', [[]]);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
        $this->assertEquals(PDO::CASE_LOWER, $options[PDO::ATTR_CASE]);

        $options = $this->call($connector, 'options', [[
            'options' => [PDO::ATTR_CASE => PDO::CASE_NATURAL, PDO::ATTR_TIMEOUT => 5],
        ]]);

        $this->assertEquals(PDO::CASE_NATURAL, $options[PDO::ATTR_CASE]);
        $this->assertEquals(5, $options[PDO::ATTR_TIMEOUT]);
        $this->assertEquals(PDO::ERRMODE_EXCEPTION, $options[PDO::ATTR_ERRMODE]);
    }
}
