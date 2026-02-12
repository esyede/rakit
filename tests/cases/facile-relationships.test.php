<?php

defined('DS') or exit('No direct access.');

class FacileRelationshipsTest extends \PHPUnit_Framework_TestCase
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
     * Test for has one relationship.
     *
     * @group system
     */
    public function testHasOneRelationship()
    {
        $user = new UserModel();
        $profile = $user->profile();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\HasOne', $profile);
    }

    /**
     * Test for has many relationship.
     *
     * @group system
     */
    public function testHasManyRelationship()
    {
        $user = new UserModel();
        $posts = $user->posts();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\HasMany', $posts);
    }

    /**
     * Test for belongs to relationship.
     *
     * @group system
     */
    public function testBelongsToRelationship()
    {
        $post = new PostModel();
        $author = $post->author();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\BelongsTo', $author);
    }

    /**
     * Test for belongs to many relationship.
     *
     * @group system
     */
    public function testBelongsToManyRelationship()
    {
        $user = new UserModel();
        $roles = $user->roles();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\BelongsToMany', $roles);
    }

    /**
     * Test for model's fillable.
     *
     * @group system
     */
    public function testModelFillable()
    {
        UserModel::$fillable = ['name', 'email'];

        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $this->assertEquals('John', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertNull($user->password);

        UserModel::$fillable = null;
    }

    /**
     * Test for model's guarded.
     *
     * @group system
     */
    public function testModelGuarded()
    {
        UserModel::$guarded = ['password'];

        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $this->assertEquals('John', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertNull($user->password);

        UserModel::$guarded = [];
    }

    /**
     * Test for model's dirty tracking.
     *
     * @group system
     */
    public function testModelDirtyTracking()
    {
        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com'], true);

        $this->assertFalse($user->dirty());
        $this->assertEquals([], $user->get_dirty());

        $user->name = 'Jane';

        $this->assertTrue($user->dirty());
        $this->assertEquals(['name' => 'Jane'], $user->get_dirty());
        $this->assertTrue($user->changed('name'));
        $this->assertFalse($user->changed('email'));
    }

    /**
     * Test for model sync().
     *
     * @group system
     */
    public function testModelSync()
    {
        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com'], true);
        $user->name = 'Jane';
        $this->assertTrue($user->dirty());

        $user->sync();

        $this->assertFalse($user->dirty());
        $this->assertEquals('Jane', $user->name);
        $this->assertEquals($user->attributes, $user->original);
    }

    /**
     * Test for model to_array().
     *
     * @group system
     */
    public function testModelToArray()
    {
        UserModel::$hidden = ['password'];
        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret']);

        $array = $user->to_array();

        $this->assertEquals('John', $array['name']);
        $this->assertEquals('john@example.com', $array['email']);
        $this->assertArrayNotHasKey('password', $array);

        UserModel::$hidden = [];
    }

    /**
     * Test for model purge().
     *
     * @group system
     */
    public function testModelPurge()
    {
        $user = new UserModel(['name' => 'John', 'email' => 'john@example.com'], true);
        $user->name = 'Jane';
        $this->assertEquals('Jane', $user->name);

        $user->purge('name');

        $this->assertNull($user->name);
        $this->assertFalse($user->changed('name'));
    }

    /**
     * Test for model's timestamps.
     *
     * @group system
     */
    public function testModelTimestamps()
    {
        $user = new UserModel();
        $this->assertTrue($user->timestamps());

        UserModel::$timestamps = false;
        $this->assertFalse($user->timestamps());

        UserModel::$timestamps = true;
    }

    /**
     * Test for model's table name.
     *
     * @group system
     */
    public function testModelTableName()
    {
        $user = new UserModel();

        // UserModel already has a public static property $table = 'users' from the start
        $this->assertEquals('users', $user->table());

        UserModel::$table = 'custom_users';
        $this->assertEquals('custom_users', $user->table());
        UserModel::$table = 'users';
    }

    /**
     * Test for model's key.
     *
     * @group system
     */
    public function testModelKey()
    {
        $user = new UserModel();
        $this->assertEquals('id', $user->key());

        $user->id = 123;
        $this->assertEquals(123, $user->get_key());

        $user->set_key(456);
        $this->assertEquals(456, $user->get_key());
        $this->assertEquals(456, $user->id);
    }
}

/**
 * Test model for User.
 */
class UserModel extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = true;

    /**
     * The has one relationship to Profile.
     */
    public function profile()
    {
        return $this->has_one('ProfileModel', 'user_id');
    }

    /**
     * The has many relationship to Post.
     */
    public function posts()
    {
        return $this->has_many('PostModel', 'user_id');
    }

    /**
     * The belongs to many relationship to Role.
     */
    public function roles()
    {
        return $this->belongs_to_many('RoleModel', 'role_user', 'user_id', 'role_id');
    }
}

/**
 * Test model for Profile.
 */
class ProfileModel extends \System\Database\Facile\Model
{
    public static $table = 'profiles';
    public static $timestamps = true;

    /**
     * The belongs to relationship to User.
     */
    public function user()
    {
        return $this->belongs_to('UserModel', 'user_id');
    }
}

/**
 * Test model for Post.
 */
class PostModel extends \System\Database\Facile\Model
{
    public static $table = 'posts';
    public static $timestamps = true;

    /**
     * The belongs to relationship to User.
     */
    public function author()
    {
        return $this->belongs_to('UserModel', 'user_id');
    }
}

/**
 * Test model for Role.
 */
class RoleModel extends \System\Database\Facile\Model
{
    public static $table = 'roles';
    public static $timestamps = true;

    /**
     * The belongs to many relationship to User.
     */
    public function users()
    {
        return $this->belongs_to_many('UserModel', 'role_user', 'role_id', 'user_id');
    }
}
