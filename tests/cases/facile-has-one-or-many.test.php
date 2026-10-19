<?php

defined('DS') or exit('No direct access.');

use System\Database;
use System\Database\Schema;

class FacileHasOneOrManyTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        Database::$connections = [];

        Schema::create('hoom_parents', function ($table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('hoom_children', function ($table) {
            $table->increments('id');
            $table->integer('parent_id');
            $table->string('title');
            $table->timestamps();
        });
    }

    public function tearDown()
    {
        Schema::drop_if_exists('hoom_children');
        Schema::drop_if_exists('hoom_parents');
        Database::$connections = [];
    }

    // -------------------------------------------------------------------------
    // HasOneOrMany::insert() with array
    // -------------------------------------------------------------------------

    /**
     * Test for HasOneOrMany::insert() - inserts with array attributes.
     *
     * @group system
     */
    public function testHasOneOrManyInsertWithArrayCreatesRecord()
    {
        $parent = HoomParent::create(['name' => 'Parent One']);
        $child = $parent->children()->insert(['title' => 'Child Title']);

        $this->assertNotNull($child);
        $this->assertEquals('Child Title', $child->title);
        $this->assertEquals($parent->id, $child->parent_id);
    }

    /**
     * Test for HasOneOrMany::insert() - inserts with model instance.
     *
     * @group system
     */
    public function testHasOneOrManyInsertWithModelInstance()
    {
        $parent = HoomParent::create(['name' => 'Parent Two']);
        $childModel = new HoomChild(['title' => 'Model Child']);

        $result = $parent->children()->insert($childModel);

        $this->assertNotNull($result);
        $this->assertEquals('Model Child', $result->title);
        $this->assertEquals($parent->id, $result->parent_id);
    }

    // -------------------------------------------------------------------------
    // HasOneOrMany::update()
    // -------------------------------------------------------------------------

    /**
     * Test for HasOneOrMany::update() - updates related records.
     *
     * @group system
     */
    public function testHasOneOrManyUpdateUpdatesRelatedRecords()
    {
        $parent = HoomParent::create(['name' => 'Parent Three']);
        $parent->children()->insert(['title' => 'Old Title']);

        $result = $parent->children()->update(['title' => 'New Title']);
        $this->assertTrue($result !== false);

        $found = HoomChild::where('parent_id', '=', $parent->id)->first();
        $this->assertEquals('New Title', $found->title);
    }

    // -------------------------------------------------------------------------
    // HasOneOrMany::constrain() - via query
    // -------------------------------------------------------------------------

    /**
     * Test for HasOneOrMany::constrain() - query is constrained to parent key.
     *
     * @group system
     */
    public function testHasOneOrManyConstrainFiltersToParentKey()
    {
        $parent1 = HoomParent::create(['name' => 'P1']);
        $parent2 = HoomParent::create(['name' => 'P2']);

        $parent1->children()->insert(['title' => 'P1 Child']);
        $parent2->children()->insert(['title' => 'P2 Child']);

        $p1Children = $parent1->children()->get();
        $this->assertCount(1, $p1Children);
        $this->assertEquals('P1 Child', $p1Children[0]->title);

        $p2Children = $parent2->children()->get();
        $this->assertCount(1, $p2Children);
        $this->assertEquals('P2 Child', $p2Children[0]->title);
    }

    // -------------------------------------------------------------------------
    // HasOneOrMany::eagerly_constrain() - directly via relationship object
    // -------------------------------------------------------------------------

    /**
     * Test for HasOneOrMany::eagerly_constrain() - constrains query to given keys.
     *
     * @group system
     */
    public function testHasOneOrManyEagerlyConstrainFiltersToKeys()
    {
        $parent1 = HoomParent::create(['name' => 'Eager P1']);
        $parent2 = HoomParent::create(['name' => 'Eager P2']);

        $parent1->children()->insert(['title' => 'EP1 Child']);
        $parent2->children()->insert(['title' => 'EP2 Child']);

        // eagerly_constrain() is called with an array of parent results.
        // In eager loading, reset_where() clears the constrain() clause first.
        $relationship = $parent1->children();
        $relationship->table->reset_where();
        $relationship->eagerly_constrain([$parent1, $parent2]);

        $results = $relationship->get();
        $this->assertCount(2, $results);
    }

    /**
     * Test for HasOne::save() - sets the foreign key and saves the model.
     *
     * @group system
     */
    public function testHasOneSaveSetsForeignKey()
    {
        $parent = HoomParent::create(['name' => 'Parent']);
        $child = new HoomChild(['title' => 'Only Child']);

        $this->assertTrue($parent->child()->save($child));
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertEquals('Only Child', $parent->child()->first()->title);
    }

    /**
     * Test for save() - an existing model from another parent is moved, not re-inserted.
     *
     * @group system
     */
    public function testSaveUpdatesExistingModelFromAnotherParent()
    {
        $first = HoomParent::create(['name' => 'First']);
        $second = HoomParent::create(['name' => 'Second']);
        $child = $first->children()->insert(['title' => 'Mover']);

        $this->assertTrue($second->children()->save(HoomChild::find($child->id)));
        $this->assertEquals(1, Database::table('hoom_children')->count());
        $this->assertEquals(0, $first->children()->count());
        $this->assertEquals('Mover', $second->child()->first()->title);
    }

    /**
     * Test for relationship delete() / update() - routed through the facile query.
     *
     * @group system
     */
    public function testRelationshipBulkDeleteAndUpdate()
    {
        $parent = HoomParent::create(['name' => 'Parent']);
        $other = HoomParent::create(['name' => 'Other']);
        $parent->children()->insert(['title' => 'A']);
        $other->children()->insert(['title' => 'B']);

        $parent->children()->update(['title' => 'Z']);
        $this->assertEquals('Z', $parent->children()->first()->title);
        $this->assertEquals('B', $other->children()->first()->title);

        $parent->children()->delete();
        $this->assertEquals(0, $parent->children()->count());
        $this->assertEquals(1, $other->children()->count());
    }
}

class HoomParent extends \System\Database\Facile\Model
{
    public static $table = 'hoom_parents';
    public static $timestamps = false;

    public function children()
    {
        return $this->has_many('HoomChild', 'parent_id');
    }

    public function child()
    {
        return $this->has_one('HoomChild', 'parent_id');
    }
}

class HoomChild extends \System\Database\Facile\Model
{
    public static $table = 'hoom_children';
    public static $timestamps = true;
}
