<?php

defined('DS') or exit('No direct access.');

use System\Database\Expression;
use System\Database\Query\Join;
use System\Database\Exceptions\DatabaseException;
use System\Database\Exceptions\QueryException;
use System\Database\Exceptions\ModelNotFoundException;
use System\Database\Exceptions\MassAssignmentException;
use System\Session\Drivers\Memory as SessionMemory;
use System\Session\Drivers\File as SessionFile;

class DatabaseExtrasTest extends \PHPUnit_Framework_TestCase
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

    // -------------------------------------------------------------------------
    // Expression
    // -------------------------------------------------------------------------

    /**
     * Test for Expression::__construct() and get().
     *
     * @group system
     */
    public function testExpressionStoresAndReturnsValue()
    {
        $expr = new Expression('COUNT(*)');
        $this->assertEquals('COUNT(*)', $expr->get());
    }

    /**
     * Test for Expression::__toString().
     *
     * @group system
     */
    public function testExpressionToStringReturnsValue()
    {
        $expr = new Expression('NOW()');
        $this->assertEquals('NOW()', (string) $expr);
    }

    /**
     * Test for Expression - cast to string via concatenation.
     *
     * @group system
     */
    public function testExpressionUsableInStringContext()
    {
        $expr = new Expression('CURRENT_DATE');
        $result = 'SELECT ' . $expr;
        $this->assertEquals('SELECT CURRENT_DATE', $result);
    }

    // -------------------------------------------------------------------------
    // Query\Join
    // -------------------------------------------------------------------------

    /**
     * Test for Join::__construct().
     *
     * @group system
     */
    public function testJoinConstructorSetsTypeAndTable()
    {
        $join = new Join('INNER', 'users');
        $this->assertEquals('INNER', $join->type);
        $this->assertEquals('users', $join->table);
        $this->assertEquals([], $join->clauses);
    }

    /**
     * Test for Join::on() - adds clause with AND connector.
     *
     * @group system
     */
    public function testJoinOnAddsClauseWithAndConnector()
    {
        $join = new Join('LEFT', 'orders');
        $result = $join->on('users.id', '=', 'orders.user_id');

        $this->assertSame($join, $result);
        $this->assertCount(1, $join->clauses);

        $clause = $join->clauses[0];
        $this->assertEquals('users.id', $clause['column1']);
        $this->assertEquals('=', $clause['operator']);
        $this->assertEquals('orders.user_id', $clause['column2']);
        $this->assertEquals('AND', $clause['connector']);
    }

    /**
     * Test for Join::on() - multiple clauses.
     *
     * @group system
     */
    public function testJoinOnAddsMultipleClauses()
    {
        $join = new Join('INNER', 'orders');
        $join->on('users.id', '=', 'orders.user_id');
        $join->on('orders.status', '=', 'active');

        $this->assertCount(2, $join->clauses);
    }

    /**
     * Test for Join::or_on() - adds clause with OR connector.
     *
     * @group system
     */
    public function testJoinOrOnAddsClauseWithOrConnector()
    {
        $join = new Join('LEFT', 'profiles');
        $result = $join->or_on('users.id', '=', 'profiles.user_id');

        $this->assertSame($join, $result);
        $this->assertCount(1, $join->clauses);
        $this->assertEquals('OR', $join->clauses[0]['connector']);
    }

    /**
     * Test for Join - method chaining.
     *
     * @group system
     */
    public function testJoinMethodChaining()
    {
        $join = new Join('LEFT', 'tags');
        $join->on('posts.id', '=', 'tags.post_id')
             ->or_on('posts.id', '=', 'tags.other_id');

        $this->assertCount(2, $join->clauses);
        $this->assertEquals('AND', $join->clauses[0]['connector']);
        $this->assertEquals('OR', $join->clauses[1]['connector']);
    }

    // -------------------------------------------------------------------------
    // Exceptions\DatabaseException
    // -------------------------------------------------------------------------

    /**
     * Test for DatabaseException::__construct() - basic message.
     *
     * @group system
     */
    public function testDatabaseExceptionBasicMessage()
    {
        $e = new DatabaseException('Database error occurred');
        $this->assertEquals('Database error occurred', $e->getMessage());
    }

    /**
     * Test for DatabaseException::getQuery() and getBindings().
     *
     * @group system
     */
    public function testDatabaseExceptionStoresQueryAndBindings()
    {
        $e = new DatabaseException('Error', 'SELECT * FROM users WHERE id = ?', [42]);
        $this->assertEquals('SELECT * FROM users WHERE id = ?', $e->getQuery());
        $this->assertEquals([42], $e->getBindings());
    }

    /**
     * Test for DatabaseException::getFormattedQuery() - substitutes bindings.
     *
     * @group system
     */
    public function testDatabaseExceptionFormattedQuerySubstitutesBindings()
    {
        $e = new DatabaseException('Error', 'SELECT * FROM users WHERE id = ? AND name = ?', [42, 'John']);
        $formatted = $e->getFormattedQuery();
        $this->assertContains('42', $formatted);
        $this->assertContains("'John'", $formatted);
    }

    /**
     * Test for DatabaseException::getFormattedQuery() - no bindings.
     *
     * @group system
     */
    public function testDatabaseExceptionFormattedQueryWithNoBindings()
    {
        $e = new DatabaseException('Error', 'SELECT * FROM users', []);
        $this->assertEquals('SELECT * FROM users', $e->getFormattedQuery());
    }

    /**
     * Test for DatabaseException::getInner() - with previous exception.
     *
     * @group system
     */
    public function testDatabaseExceptionGetInner()
    {
        $inner = new \RuntimeException('Inner error');
        $e = new DatabaseException('', 'SELECT 1', [], 0, $inner);
        $this->assertSame($inner, $e->getInner());
    }

    /**
     * Test for DatabaseException - message includes inner exception message.
     *
     * @group system
     */
    public function testDatabaseExceptionMessageIncludesInnerMessage()
    {
        $inner = new \RuntimeException('Connection refused');
        $e = new DatabaseException('', 'SELECT 1', [], 0, $inner);
        $this->assertContains('Connection refused', $e->getMessage());
    }

    /**
     * Test for DatabaseException::getInner() - null when no previous.
     *
     * @group system
     */
    public function testDatabaseExceptionGetInnerNullWhenNoPrevious()
    {
        $e = new DatabaseException('Error');
        $this->assertNull($e->getInner());
    }

    // -------------------------------------------------------------------------
    // Exceptions\QueryException
    // -------------------------------------------------------------------------

    /**
     * Test for QueryException::__construct() and accessors.
     *
     * @group system
     */
    public function testQueryExceptionStoresDetails()
    {
        $previous = new \PDOException('SQLSTATE error');
        $e = new QueryException('mysql', 'SELECT * FROM foo WHERE id = ?', [1], $previous);

        $this->assertEquals('mysql', $e->getConnectionName());
        $this->assertEquals('SELECT * FROM foo WHERE id = ?', $e->getSql());
        $this->assertEquals([1], $e->getBindings());
    }

    /**
     * Test for QueryException - message contains connection and query info.
     *
     * @group system
     */
    public function testQueryExceptionMessageContainsConnectionAndQuery()
    {
        $previous = new \PDOException('Column not found');
        $e = new QueryException('sqlite', 'SELECT * FROM users WHERE name = ?', ['Alice'], $previous);
        $message = $e->getMessage();

        $this->assertContains('Column not found', $message);
        $this->assertContains('sqlite', $message);
        $this->assertContains("'Alice'", $message);
    }

    /**
     * Test for QueryException - substitutes string bindings with quotes.
     *
     * @group system
     */
    public function testQueryExceptionSubstitutesStringBindingsWithQuotes()
    {
        $previous = new \PDOException('Error');
        $e = new QueryException('mysql', 'INSERT INTO users (name) VALUES (?)', ['Bob'], $previous);
        $this->assertContains("'Bob'", $e->getMessage());
    }

    /**
     * Test for QueryException - substitutes numeric bindings without quotes.
     *
     * @group system
     */
    public function testQueryExceptionSubstitutesNumericBindingsWithoutQuotes()
    {
        $previous = new \PDOException('Error');
        $e = new QueryException('mysql', 'SELECT * FROM users WHERE id = ?', [99], $previous);
        $this->assertContains('99', $e->getMessage());
    }

    // -------------------------------------------------------------------------
    // Exceptions\ModelNotFoundException
    // -------------------------------------------------------------------------

    /**
     * Test for ModelNotFoundException::__construct() - basic.
     *
     * @group system
     */
    public function testModelNotFoundExceptionBasic()
    {
        $e = new ModelNotFoundException('User');
        $this->assertEquals('User', $e->getModel());
        $this->assertEquals([], $e->getIds());
        $this->assertContains('User', $e->getMessage());
    }

    /**
     * Test for ModelNotFoundException::__construct() - with IDs.
     *
     * @group system
     */
    public function testModelNotFoundExceptionWithIds()
    {
        $e = new ModelNotFoundException('Post', [1, 2, 3]);
        $this->assertEquals('Post', $e->getModel());
        $this->assertEquals([1, 2, 3], $e->getIds());
        $this->assertContains('Post', $e->getMessage());
        $this->assertContains('1', $e->getMessage());
        $this->assertContains('2', $e->getMessage());
        $this->assertContains('3', $e->getMessage());
    }

    /**
     * Test for ModelNotFoundException - is instance of DatabaseException.
     *
     * @group system
     */
    public function testModelNotFoundExceptionExtendsDatabaseException()
    {
        $e = new ModelNotFoundException('User');
        $this->assertInstanceOf('System\Database\Exceptions\DatabaseException', $e);
    }

    // -------------------------------------------------------------------------
    // Exceptions\MassAssignmentException
    // -------------------------------------------------------------------------

    /**
     * Test for MassAssignmentException::__construct().
     *
     * @group system
     */
    public function testMassAssignmentExceptionStoresDetails()
    {
        $e = new MassAssignmentException('User', ['password', 'is_admin']);
        $this->assertEquals('User', $e->getModel());
        $this->assertEquals(['password', 'is_admin'], $e->getAttributes());
    }

    /**
     * Test for MassAssignmentException - message contains model and attributes.
     *
     * @group system
     */
    public function testMassAssignmentExceptionMessage()
    {
        $e = new MassAssignmentException('User', ['password', 'is_admin']);
        $message = $e->getMessage();
        $this->assertContains('User', $message);
        $this->assertContains('password', $message);
        $this->assertContains('is_admin', $message);
    }

    /**
     * Test for MassAssignmentException - extends DatabaseException.
     *
     * @group system
     */
    public function testMassAssignmentExceptionExtendsDatabaseException()
    {
        $e = new MassAssignmentException('User', ['password']);
        $this->assertInstanceOf('System\Database\Exceptions\DatabaseException', $e);
    }

    // -------------------------------------------------------------------------
    // Session\Drivers\Memory
    // -------------------------------------------------------------------------

    /**
     * Test for Session\Drivers\Memory::load() - returns stored session.
     *
     * @group system
     */
    public function testSessionMemoryDriverLoad()
    {
        $driver = new SessionMemory();
        $session = ['id' => 'test123', 'last_activity' => time(), 'data' => []];
        $driver->session = $session;

        $this->assertSame($session, $driver->load('any_id'));
    }

    /**
     * Test for Session\Drivers\Memory::load() - returns null when not set.
     *
     * @group system
     */
    public function testSessionMemoryDriverLoadReturnsNullWhenNotSet()
    {
        $driver = new SessionMemory();
        $this->assertNull($driver->load('any_id'));
    }

    /**
     * Test for Session\Drivers\Memory::save() - is a no-op.
     *
     * @group system
     */
    public function testSessionMemoryDriverSaveIsNoOp()
    {
        $driver = new SessionMemory();
        $result = $driver->save(['id' => 'test', 'data' => []], [], true);
        $this->assertNull($result);
    }

    /**
     * Test for Session\Drivers\Memory::delete() - is a no-op.
     *
     * @group system
     */
    public function testSessionMemoryDriverDeleteIsNoOp()
    {
        $driver = new SessionMemory();
        $result = $driver->delete('test_id');
        $this->assertNull($result);
    }

    /**
     * Test for Session\Drivers\Memory::fresh() - generates fresh session array.
     *
     * @group system
     */
    public function testSessionMemoryDriverFreshGeneratesSession()
    {
        $driver = new SessionMemory();
        $session = $driver->fresh();

        $this->assertInternalType('array', $session);
        $this->assertArrayHasKey('id', $session);
        $this->assertArrayHasKey('data', $session);
        $this->assertArrayHasKey(':new:', $session['data']);
        $this->assertArrayHasKey(':old:', $session['data']);
        $this->assertEquals([], $session['data'][':new:']);
        $this->assertEquals([], $session['data'][':old:']);
    }

    /**
     * Test for Session\Drivers\Memory::id() - generates unique ID.
     *
     * @group system
     */
    public function testSessionMemoryDriverIdGeneratesUniqueId()
    {
        $driver = new SessionMemory();
        $id1 = $driver->id();
        $id2 = $driver->id();

        $this->assertInternalType('string', $id1);
        $this->assertEquals(40, strlen($id1));
        $this->assertNotEquals($id1, $id2);
    }

    // -------------------------------------------------------------------------
    // Session\Drivers\File
    // -------------------------------------------------------------------------

    /**
     * Test for Session\Drivers\File - save and load.
     *
     * @group system
     */
    public function testSessionFileDriverSaveAndLoad()
    {
        $path = path('storage') . 'sessions' . DS;
        is_dir($path) || mkdir($path, 0755, true);

        $driver = new SessionFile($path);
        $session = ['id' => 'testfile123', 'last_activity' => time(), 'data' => [':new:' => [], ':old:' => []]];

        $driver->save($session, [], false);
        $loaded = $driver->load('testfile123');

        $this->assertInternalType('array', $loaded);
        $this->assertEquals('testfile123', $loaded['id']);

        $driver->delete('testfile123');
    }

    /**
     * Test for Session\Drivers\File::load() - returns null for missing session.
     *
     * @group system
     */
    public function testSessionFileDriverLoadReturnsNullForMissingSession()
    {
        $path = path('storage') . 'sessions' . DS;
        is_dir($path) || mkdir($path, 0755, true);

        $driver = new SessionFile($path);
        $result = $driver->load('nonexistent_session_id_xyz_9999');

        $this->assertNull($result);
    }

    /**
     * Test for Session\Drivers\File::delete() - removes session file.
     *
     * @group system
     */
    public function testSessionFileDriverDelete()
    {
        $path = path('storage') . 'sessions' . DS;
        is_dir($path) || mkdir($path, 0755, true);

        $driver = new SessionFile($path);
        $session = ['id' => 'del_test_999', 'last_activity' => time(), 'data' => [':new:' => [], ':old:' => []]];

        $driver->save($session, [], false);
        $this->assertNotNull($driver->load('del_test_999'));

        $driver->delete('del_test_999');
        $this->assertNull($driver->load('del_test_999'));
    }

    /**
     * Test for Session\Drivers\File::delete() - no-op on missing session.
     *
     * @group system
     */
    public function testSessionFileDriverDeleteNoopOnMissing()
    {
        $path = path('storage') . 'sessions' . DS;
        is_dir($path) || mkdir($path, 0755, true);

        $driver = new SessionFile($path);
        $driver->delete('nonexistent_session_xyz_888');
        $this->assertTrue(true);
    }
}
