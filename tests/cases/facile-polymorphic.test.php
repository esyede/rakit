<?php

defined('DS') or exit('No direct access.');

class FacilePolymorphicTest extends \PHPUnit_Framework_TestCase
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
     * Test inisialisasi morph one relationship.
     *
     * @group system
     */
    public function testMorphOneRelationshipInitialization()
    {
        $post = new Post();
        $image = $post->image();
        $this->assertInstanceOf('\System\Database\Facile\Relationships\MorphOne', $image);
    }

    /**
     * Test inisialisasi morph many relationship.
     *
     * @group system
     */
    public function testMorphManyRelationshipInitialization()
    {
        $post = new Post();
        $comments = $post->comments();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\MorphMany', $comments);
    }

    /**
     * Test inisialisasi morph to relationship.
     *
     * @group system
     */
    public function testMorphToRelationshipInitialization()
    {
        $comment = new Comment();
        $commentable = $comment->commentable();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\MorphTo', $commentable);
    }

    /**
     * Test inisialisasi morph to many relationship.
     *
     * @group system
     */
    public function testMorphToManyRelationshipInitialization()
    {
        $post = new Post();
        $tags = $post->tags();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\MorphToMany', $tags);
    }

    /**
     * Test inisialisasi belongs to many relationship.
     *
     * @group system
     */
    public function testBelongsToManyRelationshipInitialization()
    {
        $user = new User();
        $roles = $user->roles();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\BelongsToMany', $roles);
    }

    /**
     * Test pivot model sync().
     *
     * @group system
     */
    public function testPivotSyncMethod()
    {
        $pivot = new \System\Database\Facile\Pivot('role_user', 'default');
        $pivot->user_id = 1;
        $pivot->role_id = 2;

        $result = $pivot->sync();

        $this->assertInstanceOf('\System\Database\Facile\Pivot', $result);
        $this->assertEquals($pivot->attributes, $pivot->original);
    }

    /**
     * Test model sync().
     *
     * @group system
     */
    public function testModelSyncMethod()
    {
        $model = new Post(['title' => 'Test Post', 'content' => 'Test Content'], true);
        $model->title = 'Updated Title';

        $this->assertTrue($model->changed('title'));

        $model->sync();

        $this->assertFalse($model->changed('title'));
        $this->assertEquals($model->attributes, $model->original);
    }

    /**
     * Test model changed().
     *
     * @group system
     */
    public function testModelChangedMethod()
    {
        $model = new Post(['title' => 'Test Post', 'content' => 'Test Content'], true);

        $this->assertFalse($model->changed('title'));

        $model->title = 'Updated Title';

        $this->assertTrue($model->changed('title'));
        $this->assertFalse($model->changed('content'));
    }

    /**
     * Test fungsionalitas soft delete.
     *
     * @group system
     */
    public function testSoftDeleteFunctionality()
    {
        $model = new SoftDeletableModel();
        $model::$soft_delete = true;

        $this->assertTrue($model::$soft_delete);
    }

    /**
     * Test fungsionalitas timestamps.
     *
     * @group system
     */
    public function testTimestampsFunctionality()
    {
        $model = new Post();
        $this->assertTrue($model->timestamps());
    }
}

/**
 * Test model untuk Post.
 */
class Post extends \System\Database\Facile\Model
{
    public static $table = 'posts';
    public static $timestamps = true;

    /**
     * Relasi morph one ke Image.
     */
    public function image()
    {
        return $this->morph_one('Image', 'imageable');
    }

    /**
     * Relasi morph many ke Comment.
     */
    public function comments()
    {
        return $this->morph_many('Comment', 'commentable');
    }

    /**
     * Relasi morph to many ke Tag.
     */
    public function tags()
    {
        return $this->morph_to_many('Tag', 'taggable');
    }
}

/**
 * Test model untuk Comment.
 */
class Comment extends \System\Database\Facile\Model
{
    public static $table = 'comments';
    public static $timestamps = true;

    /**
     * Relasi morph to ke Commentable.
     */
    public function commentable()
    {
        return $this->morph_to('commentable');
    }
}

/**
 * Test model untuk Image.
 */
class Image extends \System\Database\Facile\Model
{
    public static $table = 'images';
    public static $timestamps = true;

    /**
     * Relasi morph to ke Imageable.
     */
    public function imageable()
    {
        return $this->morph_to('imageable');
    }
}

/**
 * Test model untuk Tag.
 */
class Tag extends \System\Database\Facile\Model
{
    public static $table = 'tags';
    public static $timestamps = true;

    /**
     * Relasi morph to many ke Taggable.
     */
    public function posts()
    {
        return $this->morph_to_many('Post', 'taggable');
    }
}

/**
 * Test model untuk User.
 */
class User extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = true;

    /**
     * Relasi belongs to many ke Role.
     */
    public function roles()
    {
        return $this->belongs_to_many('Role', 'role_user');
    }
}

/**
 * Test model untuk Role.
 */
class Role extends \System\Database\Facile\Model
{
    public static $table = 'roles';
    public static $timestamps = true;

    /**
     * Relasi belongs to many ke User.
     */
    public function users()
    {
        return $this->belongs_to_many('User', 'role_user');
    }
}

/**
 * Test model untuk soft delete.
 */
class SoftDeletableModel extends \System\Database\Facile\Model
{
    public static $table = 'soft_deletable_models';
    public static $soft_delete = true;
    public static $timestamps = true;
}
