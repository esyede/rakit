<?php

defined('DS') or exit('No direct access.');

use System\Database\Schema\Table;

class SchemaTest extends \PHPUnit_Framework_TestCase
{
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

    /**
     * Test for Table::increments().
     *
     * @group system
     */
    public function testIncrementsMethod()
    {
        $table = new Table('users');
        $column = $table->increments('id');
        $this->assertEquals('id', $column->name);
        $this->assertEquals('integer', $column->type);
        $this->assertTrue($column->increment);
    }

    /**
     * Test for Table::string().
     *
     * @group system
     */
    public function testStringMethod()
    {
        $table = new Table('users');
        $column = $table->string('name', 100);
        $this->assertEquals('name', $column->name);
        $this->assertEquals('string', $column->type);
        $this->assertEquals(100, $column->length);
    }

    /**
     * Test for Table::double().
     *
     * @group system
     */
    public function testDoubleMethod()
    {
        $table = new Table('products');
        $column = $table->double('price');
        $this->assertEquals('price', $column->name);
        $this->assertEquals('double', $column->type);
    }

    /**
     * Test for Table::json().
     *
     * @group system
     */
    public function testJsonMethod()
    {
        $table = new Table('data');
        $column = $table->json('metadata');
        $this->assertEquals('metadata', $column->name);
        $this->assertEquals('json', $column->type);
    }

    /**
     * Test for Table::uuid().
     *
     * @group system
     */
    public function testUuidMethod()
    {
        $table = new Table('items');
        $column = $table->uuid('uuid');
        $this->assertEquals('uuid', $column->name);
        $this->assertEquals('uuid', $column->type);
    }

    /**
     * Test for the nullable() modifier.
     *
     * @group system
     */
    public function testNullableModifier()
    {
        $table = new Table('users');
        $table->string('email')->nullable();
        $column = end($table->columns);
        $this->assertTrue($column->nullable);
    }

    /**
     * Test for the defaults() modifier.
     *
     * @group system
     */
    public function testDefaultsModifier()
    {
        $table = new Table('users');
        $table->integer('status')->defaults(1);
        $column = end($table->columns);
        $this->assertEquals(1, $column->defaults);
    }

    /**
     * Test for the unsigned() modifier.
     *
     * @group system
     */
    public function testUnsignedModifier()
    {
        $table = new Table('users');
        $table->integer('age')->unsigned();
        $column = end($table->columns);
        $this->assertTrue($column->unsigned);
    }

    /**
     * Test for the comment() modifier.
     *
     * @group system
     */
    public function testCommentModifier()
    {
        $table = new Table('users');
        $table->string('name')->comment('User name');
        $column = end($table->columns);
        $this->assertEquals('User name', $column->comment);
    }

    /**
     * Test for foreign key definition using fluent syntax.
     *
     * @group system
     */
    public function testForeignKeyFluent()
    {
        $table = new Table('posts');
        $table->foreign('user_id')->references('id')->on('users')->on_delete('cascade');
        $command = end($table->commands);
        $this->assertEquals('foreign', $command->type);
        $this->assertEquals('id', $command->references);
        $this->assertEquals('users', $command->on);
        $this->assertEquals('cascade', $command->on_delete);
    }

    /**
     * Test for Table::rename_column().
     *
     * @group system
     */
    public function testRenameColumnMethod()
    {
        $table = new Table('users');
        $command = $table->rename_column('old_name', 'new_name');
        $this->assertEquals('rename_column', $command->type);
        $this->assertEquals('old_name', $command->from);
        $this->assertEquals('new_name', $command->to);
    }

    /**
     * Test for Table::spatial_index().
     *
     * @group system
     */
    public function testSpatialIndexMethod()
    {
        $table = new Table('locations');
        $command = $table->spatial_index(['location'], 'spatial_idx');
        $this->assertEquals('spatial', $command->type);
        $this->assertEquals(['location'], $command->columns);
        $this->assertEquals('spatial_idx', $command->name);
    }

    /**
     * Test for Table::engine().
     *
     * @group system
     */
    public function testEngineMethod()
    {
        $table = new Table('users');
        $table->engine('InnoDB');
        $this->assertEquals('InnoDB', $table->engine);
    }

    /**
     * Test for Table::soft_deletes().
     *
     * @group system
     */
    public function testSoftDeletesMethod()
    {
        $table = new Table('posts');
        $table->soft_deletes();
        $column = end($table->columns);
        $this->assertEquals('deleted_at', $column->name);
        $this->assertEquals('timestamp', $column->type);
        $this->assertTrue($column->nullable);
    }

    /**
     * Test for Table::mediuminteger().
     *
     * @group system
     */
    public function testMediumintegerMethod()
    {
        $table = new Table('users');
        $column = $table->mediuminteger('views');
        $this->assertEquals('views', $column->name);
        $this->assertEquals('mediuminteger', $column->type);
    }

    /**
     * Test for Table::tinyinteger().
     *
     * @group system
     */
    public function testTinyintegerMethod()
    {
        $table = new Table('users');
        $column = $table->tinyinteger('flag');
        $this->assertEquals('flag', $column->name);
        $this->assertEquals('tinyinteger', $column->type);
    }

    /**
     * Test for Table::smallinteger().
     *
     * @group system
     */
    public function testSmallintegerMethod()
    {
        $table = new Table('users');
        $column = $table->smallinteger('count');
        $this->assertEquals('count', $column->name);
        $this->assertEquals('smallinteger', $column->type);
    }

    /**
     * Test for Table::ipaddress().
     *
     * @group system
     */
    public function testIpaddressMethod()
    {
        $table = new Table('logs');
        $column = $table->ipaddress('ip');
        $this->assertEquals('ip', $column->name);
        $this->assertEquals('ipaddress', $column->type);
    }

    /**
     * Test for Table::macaddress().
     *
     * @group system
     */
    public function testMacaddressMethod()
    {
        $table = new Table('devices');
        $column = $table->macaddress('mac');
        $this->assertEquals('mac', $column->name);
        $this->assertEquals('macaddress', $column->type);
    }

    /**
     * Test for Table::geometry().
     *
     * @group system
     */
    public function testGeometryMethod()
    {
        $table = new Table('maps');
        $column = $table->geometry('shape');
        $this->assertEquals('shape', $column->name);
        $this->assertEquals('geometry', $column->type);
    }

    /**
     * Test for Table::point().
     *
     * @group system
     */
    public function testPointMethod()
    {
        $table = new Table('locations');
        $column = $table->point('coord');
        $this->assertEquals('coord', $column->name);
        $this->assertEquals('point', $column->type);
    }

    /**
     * Test for Table::linestring().
     *
     * @group system
     */
    public function testLinestringMethod()
    {
        $table = new Table('routes');
        $column = $table->linestring('path');
        $this->assertEquals('path', $column->name);
        $this->assertEquals('linestring', $column->type);
    }

    /**
     * Test for Table::polygon().
     *
     * @group system
     */
    public function testPolygonMethod()
    {
        $table = new Table('areas');
        $column = $table->polygon('boundary');
        $this->assertEquals('boundary', $column->name);
        $this->assertEquals('polygon', $column->type);
    }

    /**
     * Test for Table::geometrycollection().
     *
     * @group system
     */
    public function testGeometrycollectionMethod()
    {
        $table = new Table('collections');
        $column = $table->geometrycollection('geoms');
        $this->assertEquals('geoms', $column->name);
        $this->assertEquals('geometrycollection', $column->type);
    }

    /**
     * Test for Table::multipoint().
     *
     * @group system
     */
    public function testMultipointMethod()
    {
        $table = new Table('multi');
        $column = $table->multipoint('points');
        $this->assertEquals('points', $column->name);
        $this->assertEquals('multipoint', $column->type);
    }

    /**
     * Test for Table::multilinestring().
     *
     * @group system
     */
    public function testMultilinestringMethod()
    {
        $table = new Table('multi');
        $column = $table->multilinestring('lines');
        $this->assertEquals('lines', $column->name);
        $this->assertEquals('multilinestring', $column->type);
    }

    /**
     * Test for Table::multipolygon().
     *
     * @group system
     */
    public function testMultipolygonMethod()
    {
        $table = new Table('multi');
        $column = $table->multipolygon('polys');
        $this->assertEquals('polys', $column->name);
        $this->assertEquals('multipolygon', $column->type);
    }

    /**
     * Test for Table::set().
     *
     * @group system
     */
    public function testSetMethod()
    {
        $table = new Table('options');
        $column = $table->set('choices', ['a', 'b', 'c']);
        $this->assertEquals('choices', $column->name);
        $this->assertEquals('set', $column->type);
        $this->assertEquals(['a', 'b', 'c'], $column->allowed);
    }

    /**
     * Test for the after() modifier.
     *
     * @group system
     */
    public function testAfterModifier()
    {
        $table = new Table('users');
        $table->string('name')->after('id');
        $column = end($table->columns);
        $this->assertEquals('id', $column->after);
    }

    /**
     * Test for the first() modifier.
     *
     * @group system
     */
    public function testFirstModifier()
    {
        $table = new Table('users');
        $table->string('name')->first();
        $column = end($table->columns);
        $this->assertTrue($column->first);
    }

    /**
     * Test for the first() modifier.
     *
     * @group system
     */
    public function testChangeModifier()
    {
        $table = new Table('users');
        $table->string('name')->change();
        $column = end($table->columns);
        $this->assertTrue($column->change);
    }

    /**
     * Test for the collate() modifier.
     *
     * @group system
     */
    public function testCollateModifier()
    {
        $table = new Table('users');
        $table->string('name')->collate('utf8_general_ci');
        $column = end($table->columns);
        $this->assertEquals('utf8_general_ci', $column->collate);
    }

    /**
     * Test for Table::drop_column_if_exists().
     *
     * @group system
     */
    public function testDropColumnIfExistsMethod()
    {
        $table = new Table('users');
        $command = $table->drop_column_if_exists(['old_col']);
        $this->assertEquals('drop_column_if_exists', $command->type);
        $this->assertEquals(['old_col'], $command->columns);
    }

    /**
     * Test for Table::drop_index_if_exists().
     *
     * @group system
     */
    public function testDropIndexIfExistsMethod()
    {
        $table = new Table('users');
        $command = $table->drop_index_if_exists('idx_name');
        $this->assertEquals('drop_index_if_exists', $command->type);
        $this->assertEquals('idx_name', $command->name);
    }

    /**
     * Test for Table::drop_unique_if_exists().
     *
     * @group system
     */
    public function testDropUniqueIfExistsMethod()
    {
        $table = new Table('users');
        $command = $table->drop_unique_if_exists('uniq_name');
        $this->assertEquals('drop_unique_if_exists', $command->type);
        $this->assertEquals('uniq_name', $command->name);
    }

    /**
     * Test for Table::drop_fulltext_if_exists().
     *
     * @group system
     */
    public function testDropFulltextIfExistsMethod()
    {
        $table = new Table('users');
        $command = $table->drop_fulltext_if_exists('ft_name');
        $this->assertEquals('drop_fulltext_if_exists', $command->type);
        $this->assertEquals('ft_name', $command->name);
    }

    /**
     * Test for Table::drop_foreign_if_exists().
     *
     * @group system
     */
    public function testDropForeignIfExistsMethod()
    {
        $table = new Table('users');
        $command = $table->drop_foreign_if_exists('fk_name');
        $this->assertEquals('drop_foreign_if_exists', $command->type);
        $this->assertEquals('fk_name', $command->name);
    }

    /**
     * Test for the table charset().
     *
     * @group system
     */
    public function testCharsetMethod()
    {
        $table = new Table('users');
        $table->charset('utf8');
        $this->assertEquals('utf8', $table->charset);
    }

    /**
     * Test for the table collate().
     *
     * @group system
     */
    public function testTableCollateMethod()
    {
        $table = new Table('users');
        $table->collate('utf8_general_ci');
        $this->assertEquals('utf8_general_ci', $table->collation);
    }
}
