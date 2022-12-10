<?php

defined('DS') or exit('No direct script access.');

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
     * Test untuk method TestModel::__construct().
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
     * Test untuk method TestModel::fill().
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
     * Test untuk method TestModel::fill_raw().
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
     * Test untuk method TestModel::fill dengan property $fillable.
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
     * Test untuk method TestModel::fill() dengan property $fillable berisi array kosong.
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
     * Test untuk method TestModel::fill_raw() dengan property $fillable.
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
     * Test untuk method TestModel::__set().
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
     * Test untuk method TestModel::__get().
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
     * Test untuk method TestModel::set_XXX() (mutator).
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
     * Test untuk method TestModel::get_XXX() (accessor).
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
     * Test pengecekan perubahan atribut (cek dirty).
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
     * Test untuk method TestModel::purge().
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

        $this->assertNull($model->name); // seharusnya null

        $this->assertTrue($model->changed('age'));
        $this->assertEquals(26, $model->age);
        $this->assertEquals(['age' => 26], $model->get_dirty());
    }

    /**
     * Test untuk method TestModel::table().
     *
     * @group system
     */
    public function testTableMethodReturnsCorrectName()
    {
        $model = new TestModel();

        // default, bentuk plural dari nama model.
        $this->assertEquals('testmodels', $model->table());

        TestModel::$table = 'table';
        $this->assertEquals('table', $model->table());

        TestModel::$table = null;
        // default, bentuk plural dari nama model.
        $this->assertEquals('testmodels', $model->table());
    }

    /**
     * Test untuk method TestModel::to_array().
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
            'name' => 'Budi', 'age' => 25, 'null' => null,
            'one' => ['foo' => 'bar'],
            'many' => [['first' => 'foo'], ['second' => 'bar'], ['third' => 'baz']],
            'null' => null,
        ];

        $this->assertEquals($expected, $model->to_array());
    }
}

class TestModel extends \System\Database\Facile\Model
{
    // ..
}
