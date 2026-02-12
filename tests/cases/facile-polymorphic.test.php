<?php

defined('DS') or exit('No direct access.');

class FacilePolymorphicTest extends \PHPUnit_Framework_TestCase
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
     * Test initializesi morph one relationship.
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
     * Test initialize morph many relationship.
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
     * Test initialize morph to relationship.
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
     * Test initialize morph to many relationship.
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
     * Test initialize belongs to many relationship.
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
     * Test soft delete functionality.
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
     * Test timestamps functionality.
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
 * Test model for Post.
 */
class Post extends \System\Database\Facile\Model
{
    public static $table = 'posts';
    public static $timestamps = true;

    /**
     * The morph one relationship to Image.
     */
    public function image()
    {
        return $this->morph_one('Image', 'imageable');
    }

    /**
     * The morph many relationship to Comment.
     */
    public function comments()
    {
        return $this->morph_many('Comment', 'commentable');
    }

    /**
     * The morph to many relationship to Tag.
     */
    public function tags()
    {
        return $this->morph_to_many('Tag', 'taggable');
    }
}

/**
 * Test model for Comment.
 */
class Comment extends \System\Database\Facile\Model
{
    public static $table = 'comments';
    public static $timestamps = true;

    /**
     * The morph to relationship to Commentable.
     */
    public function commentable()
    {
        return $this->morph_to('commentable');
    }
}

/**
 * Test model for Image.
 */
class Image extends \System\Database\Facile\Model
{
    public static $table = 'images';
    public static $timestamps = true;

    /**
     * The morph to relationship to Imageable.
     */
    public function imageable()
    {
        return $this->morph_to('imageable');
    }
}

/**
 * Test model for Tag.
 */
class Tag extends \System\Database\Facile\Model
{
    public static $table = 'tags';
    public static $timestamps = true;

    /**
     * The morph to many relationship to Post.
     */
    public function posts()
    {
        return $this->morph_to_many('Post', 'taggable');
    }
}

/**
 * Test model for User.
 */
class User extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = true;

    /**
     * The belongs to many relationship to Role.
     */
    public function roles()
    {
        return $this->belongs_to_many('Role', 'role_user');
    }
}

/**
 * Test model for Role.
 */
class Role extends \System\Database\Facile\Model
{
    public static $table = 'roles';
    public static $timestamps = true;

    /**
     * The belongs to many relationship to User.
     */
    public function users()
    {
        return $this->belongs_to_many('User', 'role_user');
    }
}

/**
 * Test model for soft delete.
 */
class SoftDeletableModel extends \System\Database\Facile\Model
{
    public static $table = 'soft_deletable_models';
    public static $soft_delete = true;
    public static $timestamps = true;
}
