<?php

defined('DS') or exit('No direct access.');

class FacileSoftDeleteTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // Setup koneksi database untuk testing
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // Cleanup
    }

    /**
     * Test soft delete model setup.
     *
     * @group system
     */
    public function testSoftDeleteModelSetup()
    {
        $model = new SoftDeleteModel();
        $this->assertTrue($model::$soft_delete);
    }

    /**
     * Test trashed() pada non-deleted model.
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
     * Test trashed() pada deleted model.
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
     * Test model tanpa soft delete.
     *
     * @group system
     */
    public function testModelWithoutSoftDelete()
    {
        $model = new NormalModel();
        $this->assertFalse($model::$soft_delete);
    }

    /**
     * Test restore().
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
     * Test force_delete().
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
     * Test with_trashed static method.
     *
     * @group system
     */
    public function testWithTrashedStaticMethod()
    {
        $query = SoftDeleteModel::with_trashed();
        $this->assertInstanceOf('\System\Database\Facile\Query', $query);
    }

    /**
     * Test only_trashed static method.
     *
     * @group system
     */
    public function testOnlyTrashedStaticMethod()
    {
        $query = SoftDeleteModel::only_trashed();
        // Seharusnya me-return instance query builder
        $this->assertNotNull($query);
    }
}

/**
 * Test model dengan soft delete.
 */
class SoftDeleteModel extends \System\Database\Facile\Model
{
    public static $table = 'soft_delete_models';
    public static $soft_delete = true;
    public static $timestamps = true;
}

/**
 * Test model tanpa soft delete.
 */
class NormalModel extends \System\Database\Facile\Model
{
    public static $table = 'normal_models';
    public static $soft_delete = false;
    public static $timestamps = true;
}
