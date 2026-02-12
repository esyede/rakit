<?php

defined('DS') or exit('No direct access.');

class FacileSoftDeleteTest extends \PHPUnit_Framework_TestCase
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
     * Test model without soft delete.
     *
     * @group system
     */
    public function testModelWithoutSoftDelete()
    {
        $model = new NormalModel();
        $this->assertFalse($model::$soft_delete);
    }

    /**
     * Test for restore() method.
     *
     * @group system
     */
    public function testRestoreMethod()
    {
        $model = new SoftDeleteModel(['name' => 'Test'], true);
        $model->id = 1;
        $model->deleted_at = \System\Carbon::now();
        $model->exists = false;

        $this->assertTrue(method_exists($model, 'restore'));
    }

    /**
     * Test for force_delete() method.
     *
     * @group system
     */
    public function testForceDeleteMethod()
    {
        $model = new SoftDeleteModel(['name' => 'Test'], true);
        $model->id = 1;
        $model->exists = true;

        // Model has force_delete method
        $this->assertTrue(method_exists($model, 'force_delete'));
    }

    /**
     * Test for with_trashed static method.
     *
     * @group system
     */
    public function testWithTrashedStaticMethod()
    {
        $query = SoftDeleteModel::with_trashed();
        $this->assertInstanceOf('\System\Database\Facile\Query', $query);
    }

    /**
     * Test for only_trashed static method.
     *
     * @group system
     */
    public function testOnlyTrashedStaticMethod()
    {
        $query = SoftDeleteModel::only_trashed();
        // This should return a query builder instance
        $this->assertNotNull($query);
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
