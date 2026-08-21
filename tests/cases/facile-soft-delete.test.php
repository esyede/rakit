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
