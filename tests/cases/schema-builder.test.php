<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;
use System\Database\Schema\Table;
use System\Database\Schema\Grammars\SQLite as SQLiteGrammar;
use System\Database\Expression;
use System\Magic;

class SchemaBuilderTest extends \PHPUnit_Framework_TestCase
{
    private static $table_prefix = 'schema_test_';

    public function setUp()
    {
        Database::$connections = [];
        $this->dropTestTables();
    }

    public function tearDown()
    {
        $this->dropTestTables();
        Database::$connections = [];
    }

    private function dropTestTables()
    {
        $conn = Database::connection();
        $tables = $conn->pdo()->query("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'schema_test_%'")->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $conn->pdo()->exec('DROP TABLE IF EXISTS "' . $table . '"');
        }
    }

    private function table($name)
    {
        return self::$table_prefix . $name;
    }

    // -------------------------------------------------------------------------
    // Schema::create() and Schema::tables()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::create() - creates a table via SQLite.
     *
     * @group system
     */
    public function testSchemaCreateCreatesTable()
    {
        $name = $this->table('users');
        Schema::create($name, function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        $this->assertTrue(Schema::has_table($name));
    }

    /**
     * Test for Schema::tables() - lists tables including created one.
     *
     * @group system
     */
    public function testSchemaTablesListsCreatedTable()
    {
        $name = $this->table('products');
        Schema::create($name, function ($table) {
            $table->increments('id');
        });

        $tables = Schema::tables();
        $this->assertContains($name, $tables);
    }

    /**
     * Test for Schema::has_table() - returns false for non-existent table.
     *
     * @group system
     */
    public function testSchemaHasTableReturnsFalseForMissingTable()
    {
        $this->assertFalse(Schema::has_table($this->table('nonexistent_xyz')));
    }

    // -------------------------------------------------------------------------
    // Schema::columns() and Schema::has_column()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::columns() - lists columns of a table.
     *
     * @group system
     */
    public function testSchemaColumnsListsColumns()
    {
        $name = $this->table('items');
        Schema::create($name, function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('qty');
        });

        $columns = Schema::columns($name);
        $this->assertContains('id', $columns);
        $this->assertContains('title', $columns);
        $this->assertContains('qty', $columns);
    }

    /**
     * Test for Schema::has_column() - returns true for existing column.
     *
     * @group system
     */
    public function testSchemaHasColumnReturnsTrueForExistingColumn()
    {
        $name = $this->table('posts');
        Schema::create($name, function ($table) {
            $table->increments('id');
            $table->string('body');
        });

        $this->assertTrue(Schema::has_column($name, 'body'));
    }

    /**
     * Test for Schema::has_column() - returns false for missing column.
     *
     * @group system
     */
    public function testSchemaHasColumnReturnsFalseForMissingColumn()
    {
        $name = $this->table('tags');
        Schema::create($name, function ($table) {
            $table->increments('id');
        });

        $this->assertFalse(Schema::has_column($name, 'nonexistent'));
    }

    // -------------------------------------------------------------------------
    // Schema::table() - add a column
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::table() - adds a column to existing table.
     *
     * @group system
     */
    public function testSchemaTableAddsColumn()
    {
        $name = $this->table('orders');
        Schema::create($name, function ($table) {
            $table->increments('id');
        });

        $this->assertFalse(Schema::has_column($name, 'status'));

        Schema::table($name, function ($table) {
            $table->string('status')->nullable();
        });

        $this->assertTrue(Schema::has_column($name, 'status'));
    }

    // -------------------------------------------------------------------------
    // Schema::rename()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::rename() - renames a table.
     *
     * @group system
     */
    public function testSchemaRenameRenamesTable()
    {
        $old = $this->table('old_name');
        $new = $this->table('new_name');

        Schema::create($old, function ($table) {
            $table->increments('id');
        });

        $this->assertTrue(Schema::has_table($old));
        Schema::rename($old, $new);

        $this->assertFalse(Schema::has_table($old));
        $this->assertTrue(Schema::has_table($new));

        // cleanup
        Schema::drop($new);
    }

    // -------------------------------------------------------------------------
    // Schema::drop() and Schema::drop_if_exists()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::drop() - drops an existing table.
     *
     * @group system
     */
    public function testSchemaDropRemovesTable()
    {
        $name = $this->table('temp');
        Schema::create($name, function ($table) {
            $table->increments('id');
        });

        $this->assertTrue(Schema::has_table($name));
        Schema::drop($name);
        $this->assertFalse(Schema::has_table($name));
    }

    /**
     * Test for Schema::drop_if_exists() - no-op when table missing.
     *
     * @group system
     */
    public function testSchemaDropIfExistsIsNoopWhenMissing()
    {
        Schema::drop_if_exists($this->table('does_not_exist_xyz'));
        $this->assertTrue(true);
    }

    /**
     * Test for Schema::drop_if_exists() - drops when table exists.
     *
     * @group system
     */
    public function testSchemaDropIfExistsDropsWhenExists()
    {
        $name = $this->table('temp2');
        Schema::create($name, function ($table) {
            $table->increments('id');
        });

        Schema::drop_if_exists($name);
        $this->assertFalse(Schema::has_table($name));
    }

    // -------------------------------------------------------------------------
    // Schema::create_if_not_exists()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::create_if_not_exists() - creates table when missing.
     *
     * @group system
     */
    public function testSchemaCreateIfNotExistsCreatesWhenMissing()
    {
        $name = $this->table('created_once');
        Schema::create_if_not_exists($name, function ($table) {
            $table->increments('id');
        });

        $this->assertTrue(Schema::has_table($name));
    }

    /**
     * Test for Schema::create_if_not_exists() - no-op when table exists.
     *
     * @group system
     */
    public function testSchemaCreateIfNotExistsIsNoopWhenExists()
    {
        $name = $this->table('already_exists');
        Schema::create($name, function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create_if_not_exists($name, function ($table) {
            $table->increments('id');
        });

        $this->assertTrue(Schema::has_table($name));
        $this->assertTrue(Schema::has_column($name, 'name'));
    }

    // -------------------------------------------------------------------------
    // Schema::enable_fk_checks() / disable_fk_checks()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::enable_fk_checks() - executes without error on SQLite.
     *
     * @group system
     */
    public function testSchemaEnableFkChecksRunsOnSQLite()
    {
        $result = Schema::enable_fk_checks($this->table('any'));
        $this->assertTrue($result);
    }

    /**
     * Test for Schema::disable_fk_checks() - executes without error on SQLite.
     *
     * @group system
     */
    public function testSchemaDisableFkChecksRunsOnSQLite()
    {
        $result = Schema::disable_fk_checks($this->table('any'));
        $this->assertTrue($result);
    }

    // -------------------------------------------------------------------------
    // Schema::grammar()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema::grammar() - returns SQLite grammar for sqlite connection.
     *
     * @group system
     */
    public function testSchemaGrammarReturnsSQLiteGrammar()
    {
        $connection = Database::connection();
        $grammar = Schema::grammar($connection);
        $this->assertInstanceOf('System\Database\Schema\Grammars\SQLite', $grammar);
    }

    /**
     * Test for Schema::grammar() - throws for unknown driver.
     *
     * @group system
     */
    public function testSchemaGrammarThrowsForUnknownDriver()
    {
        $connection = $this->getMockBuilder('System\Database\Connection')
            ->disableOriginalConstructor()
            ->getMock();
        $connection->expects($this->any())->method('driver')->will($this->returnValue('unknown_driver'));

        $caught = false;
        try {
            Schema::grammar($connection);
        } catch (\Exception $e) {
            $caught = true;
            $this->assertContains('unknown_driver', $e->getMessage());
        }
        $this->assertTrue($caught);
    }

    // -------------------------------------------------------------------------
    // Schema Grammar base: wrap(), foreign(), drop()
    // -------------------------------------------------------------------------

    /**
     * Test for Schema Grammar wrap() - wraps a Table object.
     *
     * @group system
     */
    public function testSchemaGrammarWrapTable()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);
        $table = new Table('users');

        $result = $grammar->wrap($table);
        $this->assertContains('users', $result);
    }

    /**
     * Test for Schema Grammar wrap() - wraps a Magic column object.
     *
     * @group system
     */
    public function testSchemaGrammarWrapMagicColumn()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);
        $column = new Magic(['name' => 'email']);

        $result = $grammar->wrap($column);
        $this->assertContains('email', $result);
    }

    /**
     * Test for Schema Grammar wrap() - wraps a plain string.
     *
     * @group system
     */
    public function testSchemaGrammarWrapString()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->wrap('name');
        $this->assertContains('name', $result);
    }

    /**
     * Test for Schema Grammar foreign() - generates FK constraint SQL.
     *
     * @group system
     */
    public function testSchemaGrammarForeignGeneratesSql()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $table = new Table('orders');
        $command = new Magic([
            'name'       => 'fk_orders_user_id',
            'columns'    => ['user_id'],
            'on'         => 'users',
            'references' => 'id',
            'on_delete'  => null,
            'on_update'  => null,
        ]);

        $sql = $grammar->foreign($table, $command);

        $this->assertContains('ALTER TABLE', $sql);
        $this->assertContains('FOREIGN KEY', $sql);
        $this->assertContains('REFERENCES', $sql);
        $this->assertContains('fk_orders_user_id', $sql);
    }

    /**
     * Test for Schema Grammar foreign() - includes ON DELETE clause.
     *
     * @group system
     */
    public function testSchemaGrammarForeignIncludesOnDelete()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $table = new Table('posts');
        $command = new Magic([
            'name'       => 'fk_posts_user',
            'columns'    => ['user_id'],
            'on'         => 'users',
            'references' => 'id',
            'on_delete'  => 'CASCADE',
            'on_update'  => null,
        ]);

        $sql = $grammar->foreign($table, $command);
        $this->assertContains('ON DELETE CASCADE', $sql);
    }

    /**
     * Test for Schema Grammar foreign() - includes ON UPDATE clause.
     *
     * @group system
     */
    public function testSchemaGrammarForeignIncludesOnUpdate()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $table = new Table('comments');
        $command = new Magic([
            'name'       => 'fk_comments',
            'columns'    => ['post_id'],
            'on'         => 'posts',
            'references' => 'id',
            'on_delete'  => null,
            'on_update'  => 'SET NULL',
        ]);

        $sql = $grammar->foreign($table, $command);
        $this->assertContains('ON UPDATE SET NULL', $sql);
    }

    /**
     * Test for Schema Grammar drop() - generates DROP TABLE SQL.
     *
     * @group system
     */
    public function testSchemaGrammarDropGeneratesSql()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $table = new Table('tmp_table');
        $command = new Magic(['type' => 'drop']);

        $sql = $grammar->drop($table, $command);

        $this->assertContains('DROP TABLE', $sql);
        $this->assertContains('tmp_table', $sql);
    }

    // -------------------------------------------------------------------------
    // Base Grammar: parameterize(), parameter(), columnize(), wrap_table()
    // -------------------------------------------------------------------------

    /**
     * Test for Grammar::parameterize() - returns comma-separated ?s.
     *
     * @group system
     */
    public function testBaseGrammarParameterizeReturnsPlaceholders()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->parameterize(['a', 'b', 'c']);
        $this->assertEquals('?, ?, ?', $result);
    }

    /**
     * Test for Grammar::parameterize() - handles Expression values.
     *
     * @group system
     */
    public function testBaseGrammarParameterizeHandlesExpression()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->parameterize([new Expression('NOW()'), 'val']);
        $this->assertEquals('NOW(), ?', $result);
    }

    /**
     * Test for Grammar::parameter() - returns ? for plain value.
     *
     * @group system
     */
    public function testBaseGrammarParameterReturnsQuestionMark()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $this->assertEquals('?', $grammar->parameter('anything'));
    }

    /**
     * Test for Grammar::parameter() - returns raw expression value.
     *
     * @group system
     */
    public function testBaseGrammarParameterReturnsExpressionValue()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $this->assertEquals('COUNT(*)', $grammar->parameter(new Expression('COUNT(*)')));
    }

    /**
     * Test for Grammar::columnize() - comma-separates wrapped column names.
     *
     * @group system
     */
    public function testBaseGrammarColumnizeWrapsAndJoins()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->columnize(['id', 'name', 'email']);
        $this->assertContains('id', $result);
        $this->assertContains('name', $result);
        $this->assertContains('email', $result);
        $this->assertContains(',', $result);
    }

    /**
     * Test for Grammar::wrap_table() - wraps table name with prefix.
     *
     * @group system
     */
    public function testBaseGrammarWrapTableWithPrefix()
    {
        $connection = Database::connection();
        $connection->config['prefix'] = 'app_';
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->wrap_table('users');
        $this->assertContains('app_users', $result);

        unset($connection->config['prefix']);
    }

    /**
     * Test for Grammar::wrap_table() - handles Expression as table name.
     *
     * @group system
     */
    public function testBaseGrammarWrapTableHandlesExpression()
    {
        $connection = Database::connection();
        $grammar = new SQLiteGrammar($connection);

        $result = $grammar->wrap_table(new Expression('raw_table'));
        $this->assertEquals('raw_table', $result);
    }
}
