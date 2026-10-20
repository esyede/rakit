<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;

class FacileMassAssignmentBypassTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];

        Schema::create('mass_assign_users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('is_admin')->default(0);
            $table->timestamp('deleted_at')->nullable();
        });

        Database::table('mass_assign_users')->insert(['name' => 'victim', 'is_admin' => 1]);
        Database::table('mass_assign_users')->insert(['name' => 'attacker', 'is_admin' => 0]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('mass_assign_users');
        Database::$connections = [];
    }

    /**
     * A key named after a declared property must not overwrite model state.
     *
     * @group system
     */
    public function testFillCannotOverwriteDeclaredProperties()
    {
        $model = new MassAssignGuardedUser();
        $model->fill([
            'name' => 'x',
            'attributes' => ['is_admin' => 1],
            'exists' => true,
        ]);

        $this->assertFalse($model->exists);
        $this->assertNull($model->is_admin);
        $this->assertEquals('x', $model->name);
    }

    /**
     * The fillable whitelist compares strictly, so an integer key matches nothing.
     *
     * @group system
     */
    public function testFillableComparesStrictly()
    {
        $model = new MassAssignFillableUser();
        $model->fill([0 => 'x', 'name' => 'y']);

        $this->assertEquals(['name' => 'y'], $model->attributes);
    }

    /**
     * Changing the key of a loaded model still updates the row it was loaded from.
     *
     * @group system
     */
    public function testSaveTargetsTheOriginalKey()
    {
        $user = MassAssignOpenUser::find(2);
        $user->fill(['id' => 99, 'name' => 'renamed'])->save();

        $this->assertEquals('victim', Database::table('mass_assign_users')->where('id', 1)->first()->name);
        $this->assertEquals('renamed', Database::table('mass_assign_users')->where('id', 99)->first()->name);
        $this->assertEquals(0, Database::table('mass_assign_users')->where('id', 2)->count());

        // Retargeting at an existing row must never rewrite that row.
        $user = MassAssignOpenUser::find(99);

        try {
            $user->fill(['id' => 1, 'name' => 'hijacked'])->save();
        } catch (\Exception $e) {
            // Primary key conflict: the row being saved keeps its own key.
        }

        $this->assertEquals('victim', Database::table('mass_assign_users')->where('id', 1)->first()->name);
    }

    /**
     * Delete and restore use the original key too.
     *
     * @group system
     */
    public function testDeleteAndRestoreTargetTheOriginalKey()
    {
        $user = MassAssignSoftUser::find(2);
        $user->id = 1;
        $user->delete();

        $this->assertNull(Database::table('mass_assign_users')->where('id', 1)->first()->deleted_at);
        $this->assertNotNull(Database::table('mass_assign_users')->where('id', 2)->first()->deleted_at);

        $user->restore();
        $this->assertNull(Database::table('mass_assign_users')->where('id', 2)->first()->deleted_at);

        $user->force_delete();
        $this->assertEquals(1, Database::table('mass_assign_users')->where('id', 1)->count());
        $this->assertEquals(0, Database::table('mass_assign_users')->where('id', 2)->count());
    }
}

class MassAssignGuardedUser extends \System\Database\Facile\Model
{
    public static $table = 'mass_assign_users';
    public static $timestamps = false;
    public static $fillable = null;
    public static $guarded = ['is_admin'];
}

class MassAssignFillableUser extends \System\Database\Facile\Model
{
    public static $table = 'mass_assign_users';
    public static $timestamps = false;
    public static $fillable = ['name'];
}

class MassAssignOpenUser extends \System\Database\Facile\Model
{
    public static $table = 'mass_assign_users';
    public static $timestamps = false;
    public static $fillable = null;
    public static $guarded = [];
}

class MassAssignSoftUser extends \System\Database\Facile\Model
{
    public static $table = 'mass_assign_users';
    public static $timestamps = false;
    public static $soft_delete = true;
    public static $fillable = null;
    public static $guarded = [];
}
