<?php

defined('DS') or exit('No direct access.');

use System\Database\Schema\Table;

/**
 * Covers the MySQL, Postgres and SQL Server schema grammars.
 *
 * These grammars only turn a Table definition into SQL, so they can be
 * exercised without ever opening a connection to those servers. The grammar is
 * therefore built without its constructor (which only stores a Connection).
 */
class SchemaGrammarsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Build a grammar instance without needing a live connection.
     *
     * @param string $driver
     *
     * @return \System\Database\Schema\Grammars\Grammar
     */
    protected function grammar($driver)
    {
        $reflection = new \ReflectionClass('\System\Database\Schema\Grammars\\' . $driver);
        return $reflection->newInstanceWithoutConstructor();
    }

    /**
     * Compile every command queued on a table.
     *
     * @param string $driver
     * @param Table  $table
     *
     * @return array
     */
    protected function compile($driver, Table $table)
    {
        $grammar = $this->grammar($driver);
        $statements = [];

        // Mirror what Schema::execute() does before handing the table to the
        // grammar, so an implicit 'add' command is queued for columns that are
        // added to an existing table.
        $implications = new \ReflectionMethod('\System\Database\Schema', 'implications');
        PHP_VERSION_ID < 80100 && $implications->setAccessible(true);
        $implications->invoke(null, $table);

        foreach ($table->commands as $command) {
            foreach ((array) $grammar->{$command->type}($table, $command) as $sql) {
                $statements[] = $sql;
            }
        }

        return $statements;
    }

    // -------------------------------------------------------------------------
    // CREATE TABLE
    // -------------------------------------------------------------------------

    /**
     * Test the MySQL CREATE TABLE statement.
     *
     * @group system
     */
    public function testMySqlCreateTable()
    {
        $table = new Table('users');
        $table->create();
        $table->increments('id');
        $table->string('email', 191);
        $table->integer('age');
        $table->boolean('active');

        $sql = $this->compile('MySQL', $table);

        $this->assertCount(1, $sql);
        $this->assertStringStartsWith('CREATE TABLE `users` (', $sql[0]);
        $this->assertContains('`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY', $sql[0]);
        $this->assertContains('`email` VARCHAR(191) NOT NULL', $sql[0]);
        $this->assertContains('`age` INT NOT NULL', $sql[0]);
        $this->assertContains('`active` TINYINT(1) NOT NULL', $sql[0]);
    }

    /**
     * Test the Postgres CREATE TABLE statement.
     *
     * @group system
     */
    public function testPostgresCreateTable()
    {
        $table = new Table('users');
        $table->create();
        $table->increments('id');
        $table->string('email', 191);

        $sql = $this->compile('Postgres', $table);

        $this->assertCount(1, $sql);
        $this->assertStringStartsWith('CREATE TABLE "users" (', $sql[0]);
        $this->assertContains('"id" SERIAL PRIMARY KEY NOT NULL', $sql[0]);
        $this->assertContains('"email" VARCHAR(191) NOT NULL', $sql[0]);
    }

    /**
     * Test the SQL Server CREATE TABLE statement.
     *
     * A table without any column comment must compile, the grammar used to
     * reject every table because it refused comments unconditionally.
     *
     * @group system
     */
    public function testSqlServerCreateTable()
    {
        $table = new Table('users');
        $table->create();
        $table->increments('id');
        $table->string('email', 191);

        $sql = $this->compile('SQLServer', $table);

        $this->assertCount(1, $sql);
        $this->assertStringStartsWith('CREATE TABLE [users] (', $sql[0]);
        $this->assertContains('[id] INT IDENTITY PRIMARY KEY NOT NULL', $sql[0]);
        $this->assertContains('[email] NVARCHAR(191) NOT NULL', $sql[0]);
    }

    /**
     * A column comment is still rejected on SQL Server.
     *
     * @group system
     *
     * @expectedException Exception
     */
    public function testSqlServerRejectsColumnComment()
    {
        $table = new Table('users');
        $table->create();
        $table->string('email');
        $table->comment('the address');

        $this->compile('SQLServer', $table);
    }

    // -------------------------------------------------------------------------
    // Column modifiers
    // -------------------------------------------------------------------------

    /**
     * A default value must be emitted as a literal on every driver.
     *
     * @group system
     */
    public function testDefaultValueIsEmittedAsLiteral()
    {
        foreach (['MySQL' => '`', 'Postgres' => '"', 'SQLServer' => '['] as $driver => $quote) {
            $table = new Table('users');
            $table->create();
            $table->string('role');
            $table->defaults('guest');

            $sql = $this->compile($driver, $table);
            $this->assertContains("DEFAULT 'guest'", $sql[0], $driver);
        }

        // SQLite used to quote the default with double quotes, which is its
        // identifier syntax rather than a string literal.
        $table = new Table('users');
        $table->create();
        $table->string('role');
        $table->defaults('name');

        $sql = $this->compile('SQLite', $table);
        $this->assertContains("DEFAULT 'name'", $sql[0]);
        $this->assertNotContains('DEFAULT "name"', $sql[0]);
    }

    /**
     * A nullable column must not be marked NOT NULL.
     *
     * @group system
     */
    public function testNullableColumn()
    {
        foreach (['MySQL', 'Postgres', 'SQLServer', 'SQLite'] as $driver) {
            $table = new Table('users');
            $table->create();
            $table->string('nickname');
            $table->nullable();

            $sql = $this->compile($driver, $table);
            $this->assertContains('NULL', $sql[0], $driver);
            $this->assertNotContains('NOT NULL', $sql[0], $driver);
        }
    }

    /**
     * An enum value carrying a quote must not break out of the literal.
     *
     * @group system
     */
    public function testEnumValuesAreEscaped()
    {
        foreach (['MySQL', 'Postgres', 'SQLServer', 'SQLite'] as $driver) {
            $table = new Table('users');
            $table->create();
            $table->enum('role', ["it's", 'guest']);

            $sql = $this->compile($driver, $table);
            $this->assertContains("'it''s'", $sql[0], $driver);
        }
    }

    // -------------------------------------------------------------------------
    // Indexes and keys
    // -------------------------------------------------------------------------

    /**
     * Test index creation per driver.
     *
     * @group system
     */
    public function testIndexes()
    {
        $build = function () {
            $table = new Table('users');
            $table->unique('email');
            $table->index('age');
            $table->primary('id');

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('ALTER TABLE `users` ADD UNIQUE users_email_unique(`email`)', $sql[0]);
        $this->assertEquals('ALTER TABLE `users` ADD INDEX users_age_index(`age`)', $sql[1]);
        $this->assertEquals('ALTER TABLE `users` ADD PRIMARY KEY (`id`)', $sql[2]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" ADD CONSTRAINT users_email_unique UNIQUE ("email")', $sql[0]);
        $this->assertEquals('CREATE INDEX users_age_index ON "users" ("age")', $sql[1]);
        $this->assertEquals('ALTER TABLE "users" ADD PRIMARY KEY ("id")', $sql[2]);

        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals('CREATE UNIQUE INDEX users_email_unique ON [users] ([email])', $sql[0]);
        $this->assertEquals('CREATE INDEX users_age_index ON [users] ([age])', $sql[1]);
        $this->assertEquals(
            'ALTER TABLE [users] ADD CONSTRAINT users_id_primary PRIMARY KEY ([id])',
            $sql[2]
        );
    }

    /**
     * Test foreign key creation per driver.
     *
     * @group system
     */
    public function testForeignKey()
    {
        $build = function () {
            $table = new Table('posts');
            $table->foreign('user_id')->references('id')->on('users')->on_delete('CASCADE');

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals(
            'ALTER TABLE `posts` ADD CONSTRAINT posts_user_id_foreign FOREIGN KEY (`user_id`)'
                . ' REFERENCES `users` (`id`) ON DELETE CASCADE',
            $sql[0]
        );

        $sql = $this->compile('Postgres', $build());
        $this->assertContains('REFERENCES "users" ("id") ON DELETE CASCADE', $sql[0]);

        $sql = $this->compile('SQLServer', $build());
        $this->assertContains('REFERENCES [users] ([id]) ON DELETE CASCADE', $sql[0]);
    }

    // -------------------------------------------------------------------------
    // ALTER / DROP
    // -------------------------------------------------------------------------

    /**
     * Test adding columns to an existing table.
     *
     * @group system
     */
    public function testAddColumn()
    {
        $build = function () {
            $table = new Table('users');
            $table->string('nickname', 30);

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('ALTER TABLE `users` ADD `nickname` VARCHAR(30) NOT NULL', $sql[0]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" ADD COLUMN "nickname" VARCHAR(30) NOT NULL', $sql[0]);

        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals('ALTER TABLE [users] ADD [nickname] NVARCHAR(30) NOT NULL', $sql[0]);
    }

    /**
     * Renaming a table must use the syntax the driver actually understands.
     *
     * @group system
     */
    public function testRenameTable()
    {
        $build = function () {
            $table = new Table('users');
            $table->rename('members');

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('RENAME TABLE `users` TO `members`', $sql[0]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" RENAME TO "members"', $sql[0]);

        // T-SQL has no 'ALTER TABLE ... RENAME TO'.
        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals("EXEC sp_rename 'users', 'members'", $sql[0]);
    }

    /**
     * Dropping columns must use the syntax the driver actually understands.
     *
     * @group system
     */
    public function testDropColumn()
    {
        $build = function () {
            $table = new Table('users');
            $table->drop_column(['age', 'nickname']);

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('ALTER TABLE `users` DROP `age`, DROP `nickname`', $sql[0]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" DROP COLUMN "age", DROP COLUMN "nickname"', $sql[0]);

        // T-SQL spells it 'DROP COLUMN a, b'.
        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals('ALTER TABLE [users] DROP COLUMN [age], [nickname]', $sql[0]);
    }

    /**
     * Dropping a primary key without knowing its name.
     *
     * @group system
     */
    public function testDropPrimaryWithoutName()
    {
        $build = function () {
            $table = new Table('users');
            $table->drop_primary();

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('ALTER TABLE `users` DROP PRIMARY KEY', $sql[0]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" DROP CONSTRAINT users_pkey', $sql[0]);

        // SQL Server always wants the constraint name, so it has to be looked up.
        $sql = $this->compile('SQLServer', $build());
        $this->assertContains('sys.key_constraints', $sql[0]);
        $this->assertContains("OBJECT_ID('users')", $sql[0]);
        $this->assertNotContains('DROP CONSTRAINT  ', $sql[0]);
    }

    /**
     * Dropping a primary key by name on SQL Server.
     *
     * @group system
     */
    public function testDropPrimaryWithName()
    {
        $table = new Table('users');
        $table->drop_primary('pk_users');

        $sql = $this->compile('SQLServer', $table);
        $this->assertEquals('ALTER TABLE [users] DROP CONSTRAINT [pk_users]', $sql[0]);

        $table = new Table('users');
        $table->drop_primary('pk_users');

        $sql = $this->compile('Postgres', $table);
        $this->assertEquals('ALTER TABLE "users" DROP CONSTRAINT pk_users', $sql[0]);
    }

    /**
     * Dropping columns that may or may not be there.
     *
     * @group system
     */
    public function testDropColumnIfExists()
    {
        $build = function () {
            $table = new Table('users');
            $table->drop_column_if_exists(['age', 'nickname']);

            return $table;
        };

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals(
            'ALTER TABLE "users" DROP COLUMN IF EXISTS "age", DROP COLUMN IF EXISTS "nickname"',
            $sql[0]
        );

        // T-SQL spells it once, then lists the columns.
        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals('ALTER TABLE [users] DROP COLUMN IF EXISTS [age], [nickname]', $sql[0]);
    }

    /**
     * Test dropping a whole table.
     *
     * @group system
     */
    public function testDropTable()
    {
        foreach (['MySQL' => '`users`', 'Postgres' => '"users"', 'SQLServer' => '[users]'] as $driver => $wrapped) {
            $table = new Table('users');
            $table->drop();

            $sql = $this->compile($driver, $table);
            $this->assertEquals('DROP TABLE ' . $wrapped, $sql[0], $driver);
        }
    }

    /**
     * Test dropping indexes and constraints.
     *
     * @group system
     */
    public function testDropIndexes()
    {
        $build = function () {
            $table = new Table('users');
            $table->drop_unique('users_email_unique');
            $table->drop_index('users_age_index');
            $table->drop_foreign('users_role_id_foreign');

            return $table;
        };

        $sql = $this->compile('MySQL', $build());
        $this->assertEquals('ALTER TABLE `users` DROP INDEX users_email_unique', $sql[0]);
        $this->assertEquals('ALTER TABLE `users` DROP INDEX users_age_index', $sql[1]);
        $this->assertEquals('ALTER TABLE `users` DROP FOREIGN KEY users_role_id_foreign', $sql[2]);

        $sql = $this->compile('Postgres', $build());
        $this->assertEquals('ALTER TABLE "users" DROP CONSTRAINT users_email_unique', $sql[0]);
        $this->assertEquals('DROP INDEX users_age_index', $sql[1]);
        $this->assertEquals('ALTER TABLE "users" DROP CONSTRAINT users_role_id_foreign', $sql[2]);

        $sql = $this->compile('SQLServer', $build());
        $this->assertEquals('DROP INDEX users_email_unique ON [users]', $sql[0]);
        $this->assertEquals('DROP INDEX users_age_index ON [users]', $sql[1]);
        $this->assertEquals('ALTER TABLE [users] DROP CONSTRAINT users_role_id_foreign', $sql[2]);
    }

    // -------------------------------------------------------------------------
    // Column types
    // -------------------------------------------------------------------------

    /**
     * Test the column type mapping of every driver.
     *
     * @group system
     */
    public function testColumnTypes()
    {
        $expected = [
            'MySQL' => [
                'string' => 'VARCHAR(200)', 'text' => 'TEXT', 'longtext' => 'LONGTEXT',
                'integer' => 'INT', 'biginteger' => 'BIGINT', 'smallinteger' => 'SMALLINT',
                'tinyinteger' => 'TINYINT', 'mediuminteger' => 'MEDIUMINT', 'float' => 'FLOAT',
                'double' => 'DOUBLE', 'boolean' => 'TINYINT(1)', 'date' => 'DATETIME',
                'timestamp' => 'TIMESTAMP', 'blob' => 'BLOB', 'json' => 'JSON', 'uuid' => 'CHAR(36)',
            ],
            'Postgres' => [
                'string' => 'VARCHAR(200)', 'text' => 'TEXT', 'longtext' => 'TEXT',
                'biginteger' => 'BIGINT', 'smallinteger' => 'SMALLINT', 'double' => 'DOUBLE PRECISION',
                'timestamp' => 'TIMESTAMP', 'blob' => 'BYTEA', 'json' => 'JSON', 'uuid' => 'UUID',
            ],
            'SQLServer' => [
                'string' => 'NVARCHAR(200)', 'text' => 'NVARCHAR(MAX)', 'longtext' => 'NVARCHAR(MAX)',
                'integer' => 'INT', 'biginteger' => 'BIGINT', 'smallinteger' => 'SMALLINT',
                'tinyinteger' => 'TINYINT', 'float' => 'FLOAT', 'double' => 'FLOAT',
                'boolean' => 'TINYINT', 'date' => 'DATETIME', 'blob' => 'VARBINARY(MAX)',
                'json' => 'NVARCHAR(MAX)', 'uuid' => 'UNIQUEIDENTIFIER',
                // 'TIMESTAMP' means ROWVERSION in T-SQL and cannot be written to.
                'timestamp' => 'DATETIME',
            ],
        ];

        foreach ($expected as $driver => $types) {
            foreach ($types as $method => $sqltype) {
                $table = new Table('probe');
                $table->create();
                $table->{$method}('c');

                $sql = $this->compile($driver, $table);
                $this->assertContains($sqltype, $sql[0], $driver . '::' . $method);
            }
        }
    }

    /**
     * Test the decimal column type, which carries precision and scale.
     *
     * @group system
     */
    public function testDecimalColumnType()
    {
        foreach (['MySQL', 'Postgres', 'SQLServer', 'SQLite'] as $driver) {
            $table = new Table('probe');
            $table->create();
            $table->decimal('amount', 8, 3);

            $sql = $this->compile($driver, $table);
            $this->assertContains('DECIMAL(8, 3)', $sql[0], $driver);
        }
    }
}
