<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Config::set('database.connections.trx', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $pdo = $this->connection()->pdo();
        $pdo->exec('CREATE TABLE IF NOT EXISTS trx (id INTEGER PRIMARY KEY, note TEXT)');
        $pdo->exec('DELETE FROM trx');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $connection = $this->connection();

        while ($connection->transaction_level() > 0) {
            $connection->rollback();
        }

        $connection->pdo()->exec('DROP TABLE IF EXISTS trx');
    }

    /**
     * Get the test connection.
     *
     * @return \System\Database\Connection
     */
    protected function connection()
    {
        return Database::connection('trx');
    }

    /**
     * Count the rows of the test table.
     *
     * @return int
     */
    protected function rows()
    {
        return $this->connection()->table('trx')->count();
    }

    /**
     * Insert a row into the test table.
     *
     * @param string $note
     */
    protected function insert($note)
    {
        $this->connection()->table('trx')->insert(['note' => $note]);
    }

    // -------------------------------------------------------------------------
    // Manual control
    // -------------------------------------------------------------------------

    /**
     * Test that the manual transaction methods exist and really open one.
     *
     * @group system
     */
    public function testManualTransactionMethodsExist()
    {
        $connection = $this->connection();

        $this->assertTrue(method_exists($connection, 'begin_transaction'));
        $this->assertTrue(method_exists($connection, 'commit'));
        $this->assertTrue(method_exists($connection, 'rollback'));

        $connection->begin_transaction();

        $this->assertTrue($connection->pdo()->inTransaction());
        $this->assertEquals(1, $connection->transaction_level());

        $connection->rollback();

        $this->assertFalse($connection->pdo()->inTransaction());
        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Test a manual commit.
     *
     * @group system
     */
    public function testManualCommitKeepsTheWork()
    {
        $connection = $this->connection();
        $connection->begin_transaction();
        $this->insert('disimpan');
        $connection->commit();

        $this->assertEquals(1, $this->rows());
        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Test a manual rollback.
     *
     * @group system
     */
    public function testManualRollbackUndoesTheWork()
    {
        $connection = $this->connection();
        $connection->begin_transaction();
        $this->insert('dibuang');
        $connection->rollback();

        $this->assertEquals(0, $this->rows());
    }

    /**
     * Test that committing or rolling back without an open transaction is
     * harmless, so it may be called from an error handler.
     *
     * @group system
     */
    public function testCommitAndRollbackWithoutATransactionAreHarmless()
    {
        $connection = $this->connection();

        $this->assertFalse($connection->rollback());
        $this->assertFalse($connection->commit());
        $this->assertEquals(0, $connection->transaction_level());
    }

    // -------------------------------------------------------------------------
    // transaction()
    // -------------------------------------------------------------------------

    /**
     * Test that transaction() hands back what the callback returns.
     *
     * @group system
     */
    public function testTransactionReturnsTheCallbackResult()
    {
        $result = $this->connection()->transaction(function ($connection) {
            $connection->table('trx')->insert(['note' => 'sesuatu']);
            return 'nilai-callback';
        });

        $this->assertEquals('nilai-callback', $result);
        $this->assertEquals(1, $this->rows());
    }

    /**
     * Test that a throwing callback rolls the work back and rethrows.
     *
     * @group system
     */
    public function testTransactionRollsBackAndRethrows()
    {
        $thrown = null;

        try {
            $this->connection()->transaction(function ($connection) {
                $connection->table('trx')->insert(['note' => 'dibuang']);
                throw new \Exception('batal');
            });
        } catch (\Exception $e) {
            $thrown = $e;
        }

        $this->assertNotNull($thrown);
        $this->assertEquals('batal', $thrown->getMessage());
        $this->assertEquals(0, $this->rows());
        $this->assertEquals(0, $this->connection()->transaction_level());
    }

    // -------------------------------------------------------------------------
    // Nesting
    // -------------------------------------------------------------------------

    /**
     * Test that a nested transaction does not blow up.
     *
     * @group system
     */
    public function testNestedTransactionIsAllowed()
    {
        $connection = $this->connection();

        $connection->transaction(function ($outer) use ($connection) {
            $outer->table('trx')->insert(['note' => 'luar']);

            $connection->transaction(function ($inner) {
                $inner->table('trx')->insert(['note' => 'dalam']);
            });
        });

        $this->assertEquals(2, $this->rows());
        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Test that only the inner work is undone when the inner one fails.
     *
     * @group system
     */
    public function testFailingInnerTransactionKeepsTheOuterOne()
    {
        $connection = $this->connection();

        $connection->transaction(function ($outer) use ($connection) {
            $outer->table('trx')->insert(['note' => 'luar']);

            try {
                $connection->transaction(function ($inner) {
                    $inner->table('trx')->insert(['note' => 'dalam']);
                    throw new \Exception('batal-dalam');
                });
            } catch (\Exception $e) {
                // The inner one is undone, the outer one carries on.
            }

            $outer->table('trx')->insert(['note' => 'luar-2']);
        });

        $notes = $connection->table('trx')->lists('note');

        $this->assertEquals(['luar', 'luar-2'], $notes);
        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Test that a failing outer transaction undoes everything.
     *
     * @group system
     */
    public function testFailingOuterTransactionUndoesTheInnerOneToo()
    {
        $connection = $this->connection();

        try {
            $connection->transaction(function ($outer) use ($connection) {
                $connection->transaction(function ($inner) {
                    $inner->table('trx')->insert(['note' => 'dalam']);
                });

                throw new \Exception('batal-luar');
            });
        } catch (\Exception $e) {
            // ..
        }

        $this->assertEquals(0, $this->rows());
        $this->assertEquals(0, $connection->transaction_level());
    }

    /**
     * Test that the nesting level is tracked.
     *
     * @group system
     */
    public function testTransactionLevelIsTracked()
    {
        $connection = $this->connection();

        $this->assertEquals(0, $connection->transaction_level());

        $connection->begin_transaction();
        $this->assertEquals(1, $connection->transaction_level());

        $connection->begin_transaction();
        $this->assertEquals(2, $connection->transaction_level());

        $connection->begin_transaction();
        $this->assertEquals(3, $connection->transaction_level());

        $connection->commit();
        $this->assertEquals(2, $connection->transaction_level());

        $connection->rollback();
        $this->assertEquals(1, $connection->transaction_level());

        $connection->commit();
        $this->assertEquals(0, $connection->transaction_level());
        $this->assertFalse($connection->pdo()->inTransaction());
    }

    // -------------------------------------------------------------------------
    // Savepoint sql
    // -------------------------------------------------------------------------

    /**
     * Test the savepoint statements of every grammar.
     *
     * @group system
     */
    public function testSavepointStatementsPerGrammar()
    {
        $connection = $this->connection();
        $expected = [
            'Grammar' => ['SAVEPOINT sp', 'RELEASE SAVEPOINT sp', 'ROLLBACK TO SAVEPOINT sp'],
            'MySQL' => ['SAVEPOINT sp', 'RELEASE SAVEPOINT sp', 'ROLLBACK TO SAVEPOINT sp'],
            'SQLite' => ['SAVEPOINT sp', 'RELEASE SAVEPOINT sp', 'ROLLBACK TO SAVEPOINT sp'],
            'Postgres' => ['SAVEPOINT sp', 'RELEASE SAVEPOINT sp', 'ROLLBACK TO SAVEPOINT sp'],
            'SQLServer' => ['SAVE TRANSACTION sp', '', 'ROLLBACK TRANSACTION sp'],
        ];

        foreach ($expected as $name => $statements) {
            $class = '\\System\\Database\\Query\\Grammars\\' . $name;
            $grammar = new $class($connection);

            $this->assertEquals($statements[0], $grammar->savepoint('sp'), $name);
            $this->assertEquals($statements[1], $grammar->release_savepoint('sp'), $name);
            $this->assertEquals($statements[2], $grammar->rollback_savepoint('sp'), $name);
        }
    }

    // -------------------------------------------------------------------------
    // Facade
    // -------------------------------------------------------------------------

    /**
     * Test that the transaction methods are reachable through the facade.
     *
     * @group system
     */
    public function testTransactionThroughTheFacade()
    {
        $previous = Config::get('database.default');
        Config::set('database.default', 'trx');

        $result = Database::transaction(function ($connection) {
            $connection->table('trx')->insert(['note' => 'facade']);
            return 'ok';
        });

        Database::begin_transaction();
        Database::table('trx')->insert(['note' => 'dibuang']);
        Database::rollback();

        Config::set('database.default', $previous);

        $this->assertEquals('ok', $result);
        $this->assertEquals(1, $this->rows());
        $this->assertEquals(['facade'], $this->connection()->table('trx')->lists('note'));
    }
}
