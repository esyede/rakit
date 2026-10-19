<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;

class FacileSoftDeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];

        Schema::create('soft_delete_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('normal_models', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('soft_delete_models');
        Schema::drop_if_exists('normal_models');
        Database::$connections = [];
    }

    /**
     * Test for soft delete model setup.
     *
     * @group system
     */
    public function testSoftDeleteModelSetup()
    {
        $model = new SoftDeleteModel();
        $this->assertTrue($model::$soft_delete);
    }

    /**
     * Test the trashed() method on non-deleted model.
     *
     * @group system
     */
    public function testTrashedMethodOnNonDeletedModel()
    {
        $model = new SoftDeleteModel(['name' => 'Test'], true);
        $model->deleted_at = null;
        $this->assertFalse($model->trashed());
    }

    /**
     * Test the trashed() method on deleted model.
     *
     * @group system
     */
    public function testTrashedMethodOnDeletedModel()
    {
        $model = new SoftDeleteModel(['name' => 'Test'], true);
        $model->deleted_at = \System\Carbon::now();
        $this->assertTrue($model->trashed());
    }

    /**
     * Test for model without soft delete.
     *
     * @group system
     */
    public function testModelWithoutSoftDelete()
    {
        $model = new NormalModel();
        $this->assertFalse($model::$soft_delete);
    }

    // -------------------------------------------------------------------------
    // The soft delete filter must reach the query builder
    // -------------------------------------------------------------------------

    /**
     * A soft deleted record must disappear from the default query.
     *
     * @group system
     */
    public function testDeletedRecordIsExcludedFromDefaultQuery()
    {
        SoftDeleteModel::create(['name' => 'alive']);
        $gone = SoftDeleteModel::create(['name' => 'gone']);
        $gone->delete();

        $names = [];

        foreach (SoftDeleteModel::all() as $row) {
            $names[] = $row->name;
        }

        $this->assertEquals(['alive'], $names);
        $this->assertNull(SoftDeleteModel::find($gone->id));
    }

    /**
     * delete() must only stamp deleted_at, never remove the row.
     *
     * @group system
     */
    public function testDeleteOnlyStampsDeletedAt()
    {
        $model = SoftDeleteModel::create(['name' => 'gone']);
        $model->delete();

        $row = Database::table('soft_delete_models')->where('id', '=', $model->id)->first();

        $this->assertNotNull($row);
        $this->assertNotNull($row->deleted_at);
    }

    /**
     * with_trashed() must bring the deleted rows back.
     *
     * @group system
     */
    public function testWithTrashedIncludesDeletedRecords()
    {
        SoftDeleteModel::create(['name' => 'alive']);
        $gone = SoftDeleteModel::create(['name' => 'gone']);
        $gone->delete();

        $query = SoftDeleteModel::with_trashed();

        $this->assertInstanceOf('\System\Database\Facile\Query', $query);
        $this->assertEquals(2, count($query->get()));
    }

    /**
     * only_trashed() must return the deleted rows and nothing else.
     *
     * @group system
     */
    public function testOnlyTrashedReturnsDeletedRecordsOnly()
    {
        SoftDeleteModel::create(['name' => 'alive']);
        $gone = SoftDeleteModel::create(['name' => 'gone']);
        $gone->delete();

        $results = SoftDeleteModel::only_trashed()->get();

        $this->assertEquals(1, count($results));
        $this->assertEquals('gone', $results[0]->name);
    }

    /**
     * restore() must clear deleted_at even though the row is filtered out.
     *
     * @group system
     */
    public function testRestoreBringsRecordBack()
    {
        $model = SoftDeleteModel::create(['name' => 'gone']);
        $model->delete();

        $this->assertEquals(0, count(SoftDeleteModel::all()));
        $this->assertTrue($model->restore());
        $this->assertEquals(1, count(SoftDeleteModel::all()));

        $row = Database::table('soft_delete_models')->where('id', '=', $model->id)->first();
        $this->assertNull($row->deleted_at);
    }

    /**
     * force_delete() must remove the row for good.
     *
     * @group system
     */
    public function testForceDeleteRemovesTheRow()
    {
        $model = SoftDeleteModel::create(['name' => 'gone']);
        $model->delete();
        $model->force_delete();

        $row = Database::table('soft_delete_models')->where('id', '=', $model->id)->first();
        $this->assertNull($row);
    }

    /**
     * A model that does not use soft deletes must not gain a filter.
     *
     * @group system
     */
    public function testNormalModelIsDeletedForGood()
    {
        $model = NormalModel::create(['name' => 'gone']);
        $model->delete();

        $this->assertEquals(0, count(NormalModel::all()));
        $this->assertNull(Database::table('normal_models')->where('id', '=', $model->id)->first());
    }

    /**
     * A bulk delete on a soft deleting model must only stamp deleted_at.
     *
     * @group system
     */
    public function testBulkDeleteSoftDeletes()
    {
        SoftDeleteModel::create(['name' => 'a']);
        SoftDeleteModel::create(['name' => 'b']);
        SoftDeleteModel::create(['name' => 'keep']);

        $affected = SoftDeleteModel::where('name', '!=', 'keep')->delete();

        $this->assertEquals(2, $affected);
        $this->assertEquals(1, count(SoftDeleteModel::all()));
        $this->assertEquals(3, Database::table('soft_delete_models')->count());
        $this->assertEquals(2, Database::table('soft_delete_models')->where_not_null('deleted_at')->count());
    }

    /**
     * Bulk deleting with_trashed() rows still soft deletes them.
     *
     * @group system
     */
    public function testBulkDeleteWithTrashedStillSoftDeletes()
    {
        SoftDeleteModel::create(['name' => 'a']);
        SoftDeleteModel::with_trashed()->where('name', '=', 'a')->delete();

        $this->assertEquals(1, Database::table('soft_delete_models')->count());
        $this->assertEquals(0, count(SoftDeleteModel::all()));
    }

    /**
     * force_delete() on the query removes the rows for good, trashed ones included.
     *
     * @group system
     */
    public function testBulkForceDeleteRemovesRows()
    {
        SoftDeleteModel::create(['name' => 'a']);
        $trashed = SoftDeleteModel::create(['name' => 'b']);
        $trashed->delete();

        $this->assertEquals(2, SoftDeleteModel::with_trashed()->force_delete());
        $this->assertEquals(0, Database::table('soft_delete_models')->count());

        NormalModel::create(['name' => 'c']);
        $this->assertEquals(1, NormalModel::where('name', '=', 'c')->force_delete());
    }

    /**
     * A bulk delete on a normal model is a hard delete.
     *
     * @group system
     */
    public function testBulkDeleteOnNormalModelIsHard()
    {
        NormalModel::create(['name' => 'a']);
        NormalModel::where('name', '=', 'a')->delete();

        $this->assertEquals(0, Database::table('normal_models')->count());
    }

    /**
     * A bulk update stamps updated_at, unless the caller passed one.
     *
     * @group system
     */
    public function testBulkUpdateStampsUpdatedAt()
    {
        $model = NormalModel::create(['name' => 'a']);
        Database::table('normal_models')->update(['updated_at' => '2000-01-01 00:00:00']);

        NormalModel::where('id', '=', $model->id)->update(['name' => 'b']);
        $row = Database::table('normal_models')->where('id', '=', $model->id)->first();
        $this->assertEquals('b', $row->name);
        $this->assertNotEquals('2000-01-01 00:00:00', $row->updated_at);

        NormalModel::where('id', '=', $model->id)->update(['updated_at' => '2001-01-01 00:00:00']);
        $row = Database::table('normal_models')->where('id', '=', $model->id)->first();
        $this->assertEquals('2001-01-01 00:00:00', $row->updated_at);
    }

    /**
     * Bulk increment() / decrement() stamp updated_at and keep the extra values.
     *
     * @group system
     */
    public function testBulkIncrementStampsUpdatedAt()
    {
        $model = NormalModel::create(['name' => '1']);
        Database::table('normal_models')->update(['updated_at' => '2000-01-01 00:00:00']);

        NormalModel::where('id', '=', $model->id)->increment('id', 0, ['name' => 'x']);
        $row = Database::table('normal_models')->first();
        $this->assertEquals('x', $row->name);
        $this->assertNotEquals('2000-01-01 00:00:00', $row->updated_at);

        NormalModel::where('id', '=', $model->id)->decrement('id', 0, ['updated_at' => '2001-01-01 00:00:00']);
        $this->assertEquals('2001-01-01 00:00:00', Database::table('normal_models')->first()->updated_at);
    }

    /**
     * A bulk update / soft delete on a joined query stamps the model table's columns,
     * qualified so they are not ambiguous with the joined table.
     *
     * @group system
     */
    public function testJoinedBulkUpdateStampsQualifiedColumns()
    {
        NormalModel::create(['name' => 'a']);
        NormalModel::create(['name' => 'b']);
        SoftDeleteModel::create(['name' => 'a']);
        SoftDeleteModel::create(['name' => 'b']);
        Database::table('normal_models')->update(['updated_at' => '2000-01-01 00:00:00']);
        Database::table('soft_delete_models')->update(['updated_at' => '2000-01-01 00:00:00']);

        $query = NormalModel::join('soft_delete_models', 'soft_delete_models.name', '=', 'normal_models.name')
            ->where('soft_delete_models.name', '=', 'a');
        $stamp = new \ReflectionMethod($query, 'stamp');
        PHP_VERSION_ID < 80100 && $stamp->setAccessible(true);
        $this->assertEquals(['normal_models.updated_at'], array_keys($stamp->invoke($query, [])));
        $this->assertEquals(['updated_at'], array_keys($stamp->invoke(NormalModel::where('id', '=', 1), [])));
        $this->assertEquals(1, $query->update(['normal_models.name' => 'z']));

        $rows = Database::table('normal_models')->order_by('id')->get();
        $this->assertEquals('z', $rows[0]->name);
        $this->assertNotEquals('2000-01-01 00:00:00', $rows[0]->updated_at);
        $this->assertEquals('2000-01-01 00:00:00', $rows[1]->updated_at);
        $this->assertEquals(2, Database::table('soft_delete_models')->where('updated_at', '=', '2000-01-01 00:00:00')->count());

        SoftDeleteModel::join('normal_models', 'normal_models.name', '=', 'soft_delete_models.name')
            ->where('normal_models.name', '=', 'b')->delete();
        $this->assertEquals(['a'], SoftDeleteModel::all()->pluck('name')->all());
    }

    /**
     * Test the soft delete filter is qualified, so a joined table with its own deleted_at is not ambiguous.
     */
    public function testSoftDeleteFilterIsQualifiedOnJoins()
    {
        Database::query('CREATE TABLE soft_delete_tags (name VARCHAR(255), deleted_at DATETIME NULL)');
        Database::table('soft_delete_tags')->insert(['name' => 'a', 'deleted_at' => null]);
        SoftDeleteModel::create(['name' => 'a']);
        SoftDeleteModel::find(SoftDeleteModel::create(['name' => 'a'])->id)->delete();

        $joined = SoftDeleteModel::join('soft_delete_tags', 'soft_delete_tags.name', '=', 'soft_delete_models.name');
        $this->assertEquals(1, $joined->count());
        $this->assertEquals(1, SoftDeleteModel::only_trashed()
            ->join('soft_delete_tags', 'soft_delete_tags.name', '=', 'soft_delete_models.name')->count());

        Database::query('DROP TABLE soft_delete_tags');
    }
}

/**
 * Test model with soft delete.
 */
class SoftDeleteModel extends \System\Database\Facile\Model
{
    public static $table = 'soft_delete_models';
    public static $soft_delete = true;
    public static $timestamps = true;
}

/**
 * Test model without soft delete.
 */
class NormalModel extends \System\Database\Facile\Model
{
    public static $table = 'normal_models';
    public static $soft_delete = false;
    public static $timestamps = true;
}
