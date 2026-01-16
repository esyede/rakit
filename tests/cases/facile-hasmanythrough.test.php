<?php

defined('DS') or exit('No direct access.');

class FacileHasManyThroughTest extends \PHPUnit_Framework_TestCase
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
     * Test has many through relationship instance.
     *
     * @group system
     */
    public function testHasManyThroughRelationshipInstance()
    {
        $country = new CountryModel();
        $posts = $country->posts();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\HasManyThrough', $posts);
    }

    /**
     * Test has many through relationship initialization.
     *
     * @group system
     */
    public function testHasManyThroughInitialization()
    {
        $countries = [
            new CountryModel(['id' => 1, 'name' => 'Indonesia']),
            new CountryModel(['id' => 2, 'name' => 'Malaysia']),
        ];

        $country = new CountryModel();
        $relation = $country->posts();
        $relation->initialize($countries, 'posts');

        foreach ($countries as $country) {
            $this->assertArrayHasKey('posts', $country->relationships);
            $this->assertTrue(is_array($country->relationships['posts']));
            $this->assertEquals([], $country->relationships['posts']);
        }
    }

    /**
     * Test has many through relationship match.
     *
     * @group system
     */
    public function testHasManyThroughMatch()
    {
        $countries = [
            new CountryModel(['id' => 1, 'name' => 'Indonesia']),
            new CountryModel(['id' => 2, 'name' => 'Malaysia']),
        ];

        $posts = [
            new ThroughPostModel(['id' => 1, 'user_id' => 1, 'title' => 'Post 1', 'rakit_through_key' => 1]),
            new ThroughPostModel(['id' => 2, 'user_id' => 1, 'title' => 'Post 2', 'rakit_through_key' => 1]),
            new ThroughPostModel(['id' => 3, 'user_id' => 2, 'title' => 'Post 3', 'rakit_through_key' => 2]),
        ];

        $country = new CountryModel();
        $relation = $country->posts();
        $relation->initialize($countries, 'posts');
        $relation->match('posts', $countries, $posts);

        $this->assertCount(2, $countries[0]->relationships['posts']);
        $this->assertCount(1, $countries[1]->relationships['posts']);
        $this->assertEquals('Post 1', $countries[0]->relationships['posts'][0]->title);
        $this->assertEquals('Post 2', $countries[0]->relationships['posts'][1]->title);
        $this->assertEquals('Post 3', $countries[1]->relationships['posts'][0]->title);
    }

    /**
     * Test has many through foreign key.
     *
     * @group system
     */
    public function testHasManyThroughForeignKey()
    {
        $country = new CountryModel(['id' => 1]);
        $relation = $country->posts();

        $this->assertEquals('user_id', $relation->foreign_key());
    }

    /**
     * Test has many through with custom keys.
     *
     * @group system
     */
    public function testHasManyThroughWithCustomKeys()
    {
        $country = new CountryModel();
        $posts = $country->postsWithCustomKeys();

        $this->assertInstanceOf('\System\Database\Facile\Relationships\HasManyThrough', $posts);
    }

    /**
     * Test has many through results method.
     *
     * @group system
     */
    public function testHasManyThroughResults()
    {
        $country = new CountryModel(['id' => 1]);
        $relation = $country->posts();

        // results() method harus mereturn hasil query
        $this->assertTrue(method_exists($relation, 'results'));
    }

    /**
     * Test has many through keys extraction.
     *
     * @group system
     */
    public function testHasManyThroughKeys()
    {
        $countries = [
            new CountryModel(['id' => 1, 'name' => 'Indonesia']),
            new CountryModel(['id' => 2, 'name' => 'Malaysia']),
            new CountryModel(['id' => 3, 'name' => 'Singapore']),
        ];

        $country = new CountryModel();
        $relation = $country->posts();
        $keys = $relation->keys($countries);

        $this->assertEquals([1, 2, 3], $keys);
    }
}

/**
 * Test model untuk Country.
 */
class CountryModel extends \System\Database\Facile\Model
{
    public static $table = 'countries';
    public static $timestamps = true;

    /**
     * Relasi has many ke User.
     */
    public function users()
    {
        return $this->has_many('ThroughUserModel', 'country_id');
    }

    /**
     * Relasi has many through ke Post melalui User.
     */
    public function posts()
    {
        return $this->has_many_through('ThroughPostModel', 'ThroughUserModel');
    }

    /**
     * Relasi has many through dengan custom keys.
     */
    public function postsWithCustomKeys()
    {
        return $this->has_many_through(
            'ThroughPostModel',
            'ThroughUserModel',
            'country_id',
            'user_id',
            'id',
            'id'
        );
    }
}

/**
 * Test model untuk User (perantara).
 */
class ThroughUserModel extends \System\Database\Facile\Model
{
    public static $table = 'users';
    public static $timestamps = true;

    /**
     * Relasi belongs to ke Country.
     */
    public function country()
    {
        return $this->belongs_to('CountryModel', 'country_id');
    }

    /**
     * Relasi has many ke Post.
     */
    public function posts()
    {
        return $this->has_many('ThroughPostModel', 'user_id');
    }
}

/**
 * Test model untuk Post (tujuan).
 */
class ThroughPostModel extends \System\Database\Facile\Model
{
    public static $table = 'posts';
    public static $timestamps = true;

    /**
     * Relasi belongs to ke User.
     */
    public function user()
    {
        return $this->belongs_to('ThroughUserModel', 'user_id');
    }
}
