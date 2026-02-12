<?php

defined('DS') or exit('No direct access.');

class FacileTest extends \PHPUnit_Framework_TestCase
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
     * Test for TestModel::__construct().
     *
     * @group system
     */
    public function testAttributesAreSetByConstructor()
    {
        $array = ['name' => 'Budi', 'age' => 25, 'setter' => 'setter: foo'];

        $model = new TestModel($array);

        $this->assertEquals('Budi', $model->name);
        $this->assertEquals(25, $model->age);
        $this->assertEquals('setter: foo', $model->setter);
    }

    /**
     * Test for TestModel::fill().
     *
     * @group system
     */
    public function testAttributesAreSetByFillMethod()
    {
        $array = ['name' => 'Budi', 'age' => 25, 'setter' => 'setter: foo'];

        $model = new TestModel();
        $model->fill($array);

        $this->assertEquals('Budi', $model->name);
        $this->assertEquals(25, $model->age);
        $this->assertEquals('setter: foo', $model->setter);
    }

    /**
     * Test for TestModel::fill_raw().
     *
     * @group system
     */
    public function testAttributesAreSetByFillRawMethod()
    {
        $array = ['name' => 'Budi', 'age' => 25, 'setter' => 'foo'];

        $model = new TestModel();
        $model->fill_raw($array);

        $this->assertEquals($array, $model->attributes);
    }

    /**
     * Test for TestModel::fill with $fillable property.
     *
     * @group system
     */
    public function testAttributesAreSetByFillMethodWithFillable()
    {
        TestModel::$fillable = ['name', 'age'];

        $array = ['name' => 'Budi', 'age' => 25, 'foo' => 'bar'];

        $model = new TestModel();
        $model->fill($array);

        $this->assertEquals('Budi', $model->name);
        $this->assertEquals(25, $model->age);
        $this->assertNull($model->foo);

        TestModel::$fillable = null;
    }

    /**
     * Test for TestModel::fill() with empty array for $fillable property.
     *
     * @group system
     */
    public function testAttributesAreSetByFillMethodWithEmptyAccessible()
    {
        TestModel::$fillable = [];

        $array = ['name' => 'Budi', 'age' => 25, 'foo' => 'bar'];

        $model = new TestModel();
        $model->fill($array);

        $this->assertEquals([], $model->attributes);
        $this->assertNull($model->name);
        $this->assertNull($model->age);
        $this->assertNull($model->foo);

        TestModel::$fillable = null;
    }

    /**
     * Test for TestModel::fill_raw() with $fillable property.
     *
     * @group system
     */
    public function testAttributesAreSetByFillRawMethodWithAccessible()
    {
        TestModel::$fillable = ['name', 'age'];

        $array = ['name' => 'budi', 'age' => 25, 'setter' => 'foo'];

        $model = new TestModel();
        $model->fill_raw($array);

        $this->assertEquals($array, $model->attributes);

        TestModel::$fillable = null;
    }

    /**
     * Test for TestModel::__set().
     *
     * @group system
     */
    public function testAttributeMagicSetterMethodChangesAttribute()
    {
        TestModel::$fillable = ['setter'];

        $array = ['setter' => 'foo', 'getter' => 'bar'];

        $model = new TestModel($array);
        $model->setter = 'setter: bar';
        $model->getter = 'getter: foo';

        $this->assertEquals('setter: bar', $model->get_attribute('setter'));
        $this->assertEquals('getter: foo', $model->get_attribute('getter'));

        TestModel::$fillable = null;
    }

    /**
     * Test for TestModel::__get().
     *
     * @group system
     */
    public function testAttributeMagicGetterMethodReturnsAttribute()
    {
        $array = ['setter' => 'setter: foo', 'getter' => 'getter: bar'];

        $model = new TestModel($array);

        $this->assertEquals('setter: foo', $model->setter);
        $this->assertEquals('getter: bar', $model->getter);
    }

    /**
     * Test for TestModel::set_xxx() (mutator).
     *
     * @group system
     */
    public function testAttributeSetterMethodChangesAttribute()
    {
        TestModel::$fillable = ['setter'];

        $array = ['setter' => 'foo', 'getter' => 'bar'];

        $model = new TestModel($array);
        $model->set_setter('setter: bar');
        $model->set_getter('getter: foo');

        $this->assertEquals('setter: bar', $model->get_attribute('setter'));
        $this->assertEquals('getter: foo', $model->get_attribute('getter'));

        TestModel::$fillable = null;
    }

    /**
     * Test for TestModel::get_xxx() (accessor).
     *
     * @group system
     */
    public function testAttributeGetterMethodReturnsAttribute()
    {
        $array = ['setter' => 'setter: foo', 'getter' => 'getter: bar'];

        $model = new TestModel($array);

        $this->assertEquals('setter: foo', $model->get_setter());
        $this->assertEquals('getter: bar', $model->get_getter());
    }

    /**
     * Test for TestModel::changed() and TestModel::dirty().
     *
     * @group system
     */
    public function testDeterminationOfChangedAttributes()
    {
        $array = ['name' => 'Budi', 'age' => 25, 'foo' => null];

        $model = new TestModel($array, true);
        $model->name = 'Purnomo';
        $model->new = null;

        $this->assertTrue($model->changed('name'));
        $this->assertFalse($model->changed('age'));
        $this->assertFalse($model->changed('foo'));
        $this->assertFalse($model->changed('new'));
        $this->assertTrue($model->dirty());
        $this->assertEquals(['name' => 'Purnomo', 'new' => null], $model->get_dirty());

        $model->sync();

        $this->assertFalse($model->changed('name'));
        $this->assertFalse($model->changed('age'));
        $this->assertFalse($model->changed('foo'));
        $this->assertFalse($model->changed('new'));

        $this->assertFalse($model->dirty());
        $this->assertEquals([], $model->get_dirty());
    }

    /**
     * Test for TestModel::purge().
     *
     * @group system
     */
    public function testAttributePurge()
    {
        $array = ['name' => 'Budi', 'age' => 25];

        $model = new TestModel($array);
        $model->name = 'Purnomo';
        $model->age = 26;

        $model->purge('name');

        $this->assertFalse($model->changed('name'));
        $this->assertNull($model->name); // should be null

        $this->assertFalse($model->changed('age'));
        $this->assertEquals(26, $model->age);
        $this->assertEquals(['age' => 26], $model->get_dirty());
    }

    /**
     * Test for TestModel::table().
     *
     * @group system
     */
    public function testTableMethodReturnsCorrectName()
    {
        $model = new TestModel();

        // default, plural form of the class name
        $this->assertEquals('testmodels', $model->table());

        TestModel::$table = 'table';
        $this->assertEquals('table', $model->table());

        TestModel::$table = null;
        // default, plural form of the class name
        $this->assertEquals('testmodels', $model->table());
    }

    /**
     * Test for TestModel::to_array().
     *
     * @group system
     */
    public function testConvertingToArray()
    {
        TestModel::$hidden = ['password', 'hidden'];

        $array = ['name' => 'Budi', 'age' => 25, 'password' => 'rakit', 'null' => null];

        $model = new TestModel($array);

        $first = new TestModel(['first' => 'foo', 'password' => 'hidden']);
        $second = new TestModel(['second' => 'bar', 'password' => 'hidden']);
        $third = new TestModel(['third' => 'baz', 'password' => 'hidden']);

        $model->relationships['one'] = new TestModel(['foo' => 'bar', 'password' => 'hidden']);
        $model->relationships['many'] = [$first, $second, $third];
        $model->relationships['hidden'] = new TestModel(['should' => 'not_visible']);
        $model->relationships['null'] = null;

        $expected = [
            'name' => 'Budi',
            'age' => 25,
            'null' => null,
            'one' => ['foo' => 'bar'],
            'many' => [['first' => 'foo'], ['second' => 'bar'], ['third' => 'baz']],
            'null' => null,
        ];

        $this->assertEquals($expected, $model->to_array());
    }

    /**
     * Test for TestModel::add_global_scope() with string name and implementation.
     *
     * @group system
     */
    public function testAddGlobalScopeWithStringAndImplementation()
    {
        TestModel::add_global_scope('test_scope', function ($query) {
            $query->where('active', '=', 1);
        });

        $scopes = TestModel::get_global_scopes();
        $this->assertArrayHasKey('test_scope', $scopes);
        $this->assertInstanceOf('Closure', $scopes['test_scope']);

        TestModel::remove_global_scope('test_scope');
    }

    /**
     * Test for TestModel::add_global_scope() with closure only.
     *
     * @group system
     */
    public function testAddGlobalScopeWithClosure()
    {
        $scope = function ($query) {
            $query->where('status', '=', 'active');
        };

        TestModel::add_global_scope($scope);

        $scopes = TestModel::get_global_scopes();
        $this->assertContains($scope, $scopes);

        TestModel::remove_global_scope(spl_object_hash($scope));
    }

    /**
     * Test for TestModel::remove_global_scope().
     *
     * @group system
     */
    public function testRemoveGlobalScope()
    {
        TestModel::add_global_scope('remove_test', function ($query) {
            // dummy
        });

        $this->assertArrayHasKey('remove_test', TestModel::get_global_scopes());

        TestModel::remove_global_scope('remove_test');

        $this->assertArrayNotHasKey('remove_test', TestModel::get_global_scopes());
    }

    /**
     * Test for TestModel::get_global_scopes().
     *
     * @group system
     */
    public function testGetGlobalScopes()
    {
        $initial = TestModel::get_global_scopes();

        TestModel::add_global_scope('get_test', function ($query) {
            // dummy
        });

        $after = TestModel::get_global_scopes();

        $this->assertCount(count($initial) + 1, $after);
        $this->assertArrayHasKey('get_test', $after);

        TestModel::remove_global_scope('get_test');
    }

    /**
     * Test that global scopes are applied to query (simple).
     *
     * @group system
     */
    public function testGlobalScopesAppliedToQuery()
    {
        $applied = false;

        TestModel::add_global_scope('apply_test', function ($query) use (&$applied) {
            $applied = true;
        });

        $model = new TestModel();

        // Use reflection to access protected _query() method
        $reflection = new \ReflectionMethod($model, '_query');
        /** @disregard */
        $reflection->setAccessible(true);
        $reflection->invoke($model);

        $this->assertTrue($applied);

        TestModel::remove_global_scope('apply_test');
    }

    /**
     * Test for local scope method.
     *
     * @group system
     */
    public function test_local_scope()
    {
        $query = TestModel::active();
        $this->assertInstanceOf('\System\Database\Facile\Query', $query);
    }
}

/**
 * Test model
 */
class TestModel extends \System\Database\Facile\Model
{
    /**
     * Setter for 'setter' attribute.
     *
     * @param mixed $value
     */
    public function set_setter($value)
    {
        $this->set_attribute('setter', $value);
    }

    /**
     * Getter for 'setter' attribute.
     *
     * @return mixed
     */
    public function get_setter()
    {
        return $this->get_attribute('setter');
    }

    /**
     * Setter for 'getter' attribute.
     *
     * @param mixed $value
     */
    public function set_getter($value)
    {
        $this->set_attribute('getter', $value);
    }

    /**
     * Getter for 'getter' attribute.
     *
     * @return mixed
     */
    public function get_getter()
    {
        return $this->get_attribute('getter');
    }

    /**
     * Local scope for the 'active' query.
     *
     * @param Query $query
     *
     * @return Query
     */
    public function scope_active($query)
    {
        return $query->where('active', '=', 1);
    }
}
