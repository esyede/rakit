<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;

/**
 * Exercises the relationships against real rows, both lazily and through
 * eager loading, which is where initialize()/match()/eagerly_constrain() live.
 */
class FacileEagerLoadingTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Database::$connections = [];

        $this->drop();

        Schema::create('el_authors', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('el_profiles', function ($table) {
            $table->increments('id');
            $table->integer('el_author_id');
            $table->string('bio');
        });

        Schema::create('el_posts', function ($table) {
            $table->increments('id');
            $table->integer('el_author_id');
            $table->string('title');
        });

        Schema::create('el_comments', function ($table) {
            $table->increments('id');
            $table->integer('el_post_id');
            $table->string('body');
        });

        Schema::create('el_tags', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        // The pivot table follows the framework's convention: BelongsToMany
        // selects 'id' plus the timestamps out of it (see its $with property).
        Schema::create('el_post_el_tag', function ($table) {
            $table->increments('id');
            $table->integer('el_post_id');
            $table->integer('el_tag_id');
            $table->timestamps();
        });

        Schema::create('el_images', function ($table) {
            $table->increments('id');
            $table->integer('imageable_id');
            $table->string('imageable_type');
            $table->string('url');
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        $this->drop();
        Database::$connections = [];
    }

    /**
     * Drop every table used here.
     */
    protected function drop()
    {
        $tables = [
            'el_images', 'el_post_el_tag', 'el_tags',
            'el_comments', 'el_posts', 'el_profiles', 'el_authors',
        ];

        foreach ($tables as $table) {
            Schema::drop_if_exists($table);
        }
    }

    /**
     * Seed two authors, their profiles, posts, comments, tags and images.
     */
    protected function seed()
    {
        $budi = ElAuthor::create(['name' => 'Budi']);
        $ani = ElAuthor::create(['name' => 'Ani']);

        ElProfile::create(['el_author_id' => $budi->id, 'bio' => 'Bio Budi']);
        ElProfile::create(['el_author_id' => $ani->id, 'bio' => 'Bio Ani']);

        $satu = ElPost::create(['el_author_id' => $budi->id, 'title' => 'Satu']);
        $dua = ElPost::create(['el_author_id' => $budi->id, 'title' => 'Dua']);
        $tiga = ElPost::create(['el_author_id' => $ani->id, 'title' => 'Tiga']);

        ElComment::create(['el_post_id' => $satu->id, 'body' => 'Komentar A']);
        ElComment::create(['el_post_id' => $satu->id, 'body' => 'Komentar B']);
        ElComment::create(['el_post_id' => $tiga->id, 'body' => 'Komentar C']);

        $merah = ElTag::create(['name' => 'merah']);
        $biru = ElTag::create(['name' => 'biru']);

        $now = \System\Carbon::now()->format('Y-m-d H:i:s');

        Database::table('el_post_el_tag')->insert([
            ['el_post_id' => $satu->id, 'el_tag_id' => $merah->id, 'created_at' => $now, 'updated_at' => $now],
            ['el_post_id' => $satu->id, 'el_tag_id' => $biru->id, 'created_at' => $now, 'updated_at' => $now],
            ['el_post_id' => $dua->id, 'el_tag_id' => $biru->id, 'created_at' => $now, 'updated_at' => $now],
        ]);

        ElImage::create([
            'imageable_id' => $satu->id,
            'imageable_type' => 'ElPost',
            'url' => '/satu.png',
        ]);

        return compact('budi', 'ani', 'satu', 'dua', 'tiga');
    }

    // -------------------------------------------------------------------------
    // has_one
    // -------------------------------------------------------------------------

    /**
     * Test lazy loading a has_one relationship.
     *
     * @group system
     */
    public function testHasOneLazy()
    {
        $this->seed();

        $author = ElAuthor::where('name', '=', 'Budi')->first();
        $this->assertEquals('Bio Budi', $author->profile->bio);
    }

    /**
     * Test eager loading a has_one relationship.
     *
     * @group system
     */
    public function testHasOneEager()
    {
        $this->seed();

        $authors = ElAuthor::with('profile')->get();
        $this->assertCount(2, $authors);

        foreach ($authors as $author) {
            $this->assertArrayHasKey('profile', $author->relationships);
            $this->assertEquals('Bio ' . $author->name, $author->relationships['profile']->bio);
        }
    }

    /**
     * A parent without a match keeps a NULL relationship.
     *
     * @group system
     */
    public function testHasOneEagerWithoutMatch()
    {
        $this->seed();
        ElAuthor::create(['name' => 'Cici']);

        $authors = ElAuthor::with('profile')->get();
        $cici = null;

        foreach ($authors as $author) {
            if ('Cici' === $author->name) {
                $cici = $author;
            }
        }

        $this->assertNotNull($cici);
        $this->assertArrayHasKey('profile', $cici->relationships);
        $this->assertNull($cici->relationships['profile']);
    }

    // -------------------------------------------------------------------------
    // has_many
    // -------------------------------------------------------------------------

    /**
     * Test lazy loading a has_many relationship.
     *
     * @group system
     */
    public function testHasManyLazy()
    {
        $this->seed();

        $author = ElAuthor::where('name', '=', 'Budi')->first();
        $this->assertCount(2, $author->posts);
    }

    /**
     * Test eager loading a has_many relationship.
     *
     * @group system
     */
    public function testHasManyEager()
    {
        $this->seed();

        $counts = [];

        foreach (ElAuthor::with('posts')->get() as $author) {
            $counts[$author->name] = count($author->relationships['posts']);
        }

        $this->assertEquals(['Budi' => 2, 'Ani' => 1], $counts);
    }

    /**
     * A parent without children gets an empty array, not NULL.
     *
     * @group system
     */
    public function testHasManyEagerWithoutMatch()
    {
        $this->seed();
        ElAuthor::create(['name' => 'Cici']);

        foreach (ElAuthor::with('posts')->get() as $author) {
            $this->assertInternalType('array', $author->relationships['posts']);
        }
    }

    // -------------------------------------------------------------------------
    // belongs_to
    // -------------------------------------------------------------------------

    /**
     * Test lazy loading a belongs_to relationship.
     *
     * @group system
     */
    public function testBelongsToLazy()
    {
        $this->seed();

        $post = ElPost::where('title', '=', 'Tiga')->first();
        $this->assertEquals('Ani', $post->author->name);
    }

    /**
     * Test eager loading a belongs_to relationship.
     *
     * @group system
     */
    public function testBelongsToEager()
    {
        $this->seed();

        $names = [];

        foreach (ElPost::with('author')->get() as $post) {
            $names[$post->title] = $post->relationships['author']->name;
        }

        $this->assertEquals(['Satu' => 'Budi', 'Dua' => 'Budi', 'Tiga' => 'Ani'], $names);
    }

    // -------------------------------------------------------------------------
    // Nested eager loading
    // -------------------------------------------------------------------------

    /**
     * A nested relationship is loaded too.
     *
     * @group system
     */
    public function testNestedEagerLoading()
    {
        $this->seed();

        $authors = ElAuthor::with(['posts', 'posts.comments'])->get();
        $total = 0;

        foreach ($authors as $author) {
            foreach ($author->relationships['posts'] as $post) {
                $this->assertArrayHasKey('comments', $post->relationships);
                $total += count($post->relationships['comments']);
            }
        }

        $this->assertEquals(3, $total);
    }

    // -------------------------------------------------------------------------
    // belongs_to_many
    // -------------------------------------------------------------------------

    /**
     * Test lazy loading a belongs_to_many relationship.
     *
     * @group system
     */
    public function testBelongsToManyLazy()
    {
        $this->seed();

        $post = ElPost::where('title', '=', 'Satu')->first();
        $names = [];

        foreach ($post->tags as $tag) {
            $names[] = $tag->name;
        }

        sort($names);
        $this->assertEquals(['biru', 'merah'], $names);
    }

    /**
     * Test eager loading a belongs_to_many relationship.
     *
     * @group system
     */
    public function testBelongsToManyEager()
    {
        $this->seed();

        $counts = [];

        foreach (ElPost::with('tags')->get() as $post) {
            $counts[$post->title] = count($post->relationships['tags']);
        }

        $this->assertEquals(['Satu' => 2, 'Dua' => 1, 'Tiga' => 0], $counts);
    }

    /**
     * The pivot row is available on each related model.
     *
     * @group system
     */
    public function testBelongsToManyPivot()
    {
        $this->seed();

        $post = ElPost::where('title', '=', 'Satu')->first();

        foreach ($post->tags as $tag) {
            $this->assertArrayHasKey('pivot', $tag->relationships);
            $this->assertEquals($post->id, $tag->relationships['pivot']->el_post_id);
        }
    }

    // -------------------------------------------------------------------------
    // Polymorphic
    // -------------------------------------------------------------------------

    /**
     * Test lazy loading a morph_one relationship.
     *
     * @group system
     */
    public function testMorphOneLazy()
    {
        $this->seed();

        $post = ElPost::where('title', '=', 'Satu')->first();
        $this->assertEquals('/satu.png', $post->image->url);

        $other = ElPost::where('title', '=', 'Dua')->first();
        $this->assertNull($other->image);
    }

    /**
     * Test eager loading a morph_one relationship.
     *
     * @group system
     */
    public function testMorphOneEager()
    {
        $this->seed();

        $found = 0;

        foreach (ElPost::with('image')->get() as $post) {
            $this->assertArrayHasKey('image', $post->relationships);

            if (!is_null($post->relationships['image'])) {
                $found++;
            }
        }

        $this->assertEquals(1, $found);
    }

    /**
     * The type column keeps the relationship scoped to its own model.
     *
     * @group system
     */
    public function testMorphOneIsScopedByType()
    {
        $seeded = $this->seed();

        ElImage::create([
            'imageable_id' => $seeded['budi']->id,
            'imageable_type' => 'ElAuthor',
            'url' => '/budi.png',
        ]);

        $post = ElPost::where('title', '=', 'Satu')->first();
        $this->assertEquals('/satu.png', $post->image->url);
    }
}

/**
 * Fixtures.
 */
class ElAuthor extends \System\Database\Facile\Model
{
    public static $table = 'el_authors';
    public static $timestamps = false;

    public function profile()
    {
        return $this->has_one('ElProfile', 'el_author_id');
    }

    public function posts()
    {
        return $this->has_many('ElPost', 'el_author_id');
    }
}

class ElProfile extends \System\Database\Facile\Model
{
    public static $table = 'el_profiles';
    public static $timestamps = false;
}

class ElPost extends \System\Database\Facile\Model
{
    public static $table = 'el_posts';
    public static $timestamps = false;

    public function author()
    {
        return $this->belongs_to('ElAuthor', 'el_author_id');
    }

    public function comments()
    {
        return $this->has_many('ElComment', 'el_post_id');
    }

    public function tags()
    {
        return $this->belongs_to_many('ElTag', 'el_post_el_tag', 'el_post_id', 'el_tag_id');
    }

    public function image()
    {
        return $this->morph_one('ElImage', 'imageable');
    }
}

class ElComment extends \System\Database\Facile\Model
{
    public static $table = 'el_comments';
    public static $timestamps = false;
}

class ElTag extends \System\Database\Facile\Model
{
    public static $table = 'el_tags';
    public static $timestamps = false;
}

class ElImage extends \System\Database\Facile\Model
{
    public static $table = 'el_images';
    public static $timestamps = false;
}
