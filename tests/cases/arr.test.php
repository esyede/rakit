<?php

defined('DS') or exit('No direct access.');

use System\Arr;

class ArrTest extends \PHPUnit_Framework_TestCase
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
     * Test for Arr::accessible().
     *
     * @group system
     */
    public function testAccessible()
    {
        $this->assertTrue(Arr::accessible([]));
        $this->assertTrue(Arr::accessible([1, 2]));
        $this->assertTrue(Arr::accessible(['a' => 1, 'b' => 2]));

        $this->assertFalse(Arr::accessible(null));
        $this->assertFalse(Arr::accessible('abc'));
        $this->assertFalse(Arr::accessible(new \stdClass()));
        $this->assertFalse(Arr::accessible((object) ['a' => 1, 'b' => 2]));
    }

    /**
     * Test for Arr::add().
     *
     * @group system
     */
    public function testAdd()
    {
        $array = Arr::add(['name' => 'Desk'], 'price', 100);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
    }

    /**
     * Test for Arr::collapse().
     *
     * @group system
     */
    public function testCollapse()
    {
        $data = [['foo', 'bar'], ['baz']];
        $this->assertEquals(['foo', 'bar', 'baz'], Arr::collapse($data));
    }

    /**
     * Test for Arr::divide().
     *
     * @group system
     */
    public function testDivide()
    {
        list($keys, $values) = Arr::divide(['name' => 'Desk']);
        $this->assertEquals(['name'], $keys);
        $this->assertEquals(['Desk'], $values);
    }

    /**
     * Test for Arr::dot().
     *
     * @group system
     */
    public function testDot()
    {
        $array = Arr::dot(['foo' => ['bar' => 'baz']]);
        $this->assertEquals(['foo.bar' => 'baz'], $array);

        $array = Arr::dot([]);
        $this->assertEquals([], $array);

        $array = Arr::dot(['foo' => []]);
        $this->assertEquals(['foo' => []], $array);

        $array = Arr::dot(['foo' => ['bar' => []]]);
        $this->assertEquals(['foo.bar' => []], $array);
    }

    /**
     * Test for Arr::undot().
     *
     * @group system
     */
    public function testUndot()
    {
        $array = Arr::undot(['user.name' => 'Budi', 'user.age' => 28]);
        $this->assertEquals(['user' => ['name' => 'Budi', 'age' => 28]], $array);
    }

    /**
     * Test for Arr::except().
     *
     * @group system
     */
    public function testExcept()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $array = Arr::except($array, ['price']);
        $this->assertEquals(['name' => 'Desk'], $array);
    }

    /**
     * Test for Arr::exists().
     *
     * @group system
     */
    public function testExists()
    {
        $this->assertTrue(Arr::exists([1], 0));
        $this->assertTrue(Arr::exists([null], 0));
        $this->assertTrue(Arr::exists(['a' => 1], 'a'));
        $this->assertTrue(Arr::exists(['a' => null], 'a'));

        $this->assertFalse(Arr::exists([1], 1));
        $this->assertFalse(Arr::exists([null], 1));
        $this->assertFalse(Arr::exists(['a' => 1], 0));
    }

    /**
     * Test for Arr::first().
     *
     * @group system
     */
    public function testFirst()
    {
        $array = [100, 200, 300];

        $value = Arr::first($array, function ($key, $value) {
            return $value >= 150;
        });

        $this->assertEquals(200, $value);
        $this->assertEquals(100, Arr::first($array));
    }

    /**
     * Test for Arr::last().
     *
     * @group system
     */
    public function testLast()
    {
        $array = [100, 200, 300];
        $last = Arr::last($array, function () {
            return true;
        });
        $this->assertEquals(300, $last);
        $this->assertEquals(300, Arr::last($array));
    }

    /**
     * Test for Arr::flatten() - 1.
     *
     * @group system
     */
    public function testFlatten()
    {
        $array = ['#foo', '#bar', '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten(['#foo', '#bar', '#baz']));

        $array = [['#foo', '#bar'], '#baz'];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        $array = [['#foo', '#bar'], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));

        $array = [['#foo', ['#bar']], ['#baz']];
        $this->assertEquals(['#foo', '#bar', '#baz'], Arr::flatten($array));
    }

    /**
     * Test for Arr::flatten() - 2.
     *
     * @group system
     */
    public function testFlattenWithDepth()
    {
        $array = [['#foo', ['#bar', ['#baz']]], '#qux'];
        $this->assertEquals(['#foo', '#bar', '#baz', '#qux'], Arr::flatten($array));

        $array = [['#foo', ['#bar', ['#baz']]], '#qux'];
        $this->assertEquals(['#foo', ['#bar', ['#baz']], '#qux'], Arr::flatten($array, 1));

        $array = [['#foo', ['#bar', ['#baz']]], '#qux'];
        $this->assertEquals(['#foo', '#bar', ['#baz'], '#qux'], Arr::flatten($array, 2));
    }

    /**
     * Test for Arr::get().
     *
     * @group system
     */
    public function testGet()
    {
        $array = ['products.desk' => ['price' => 100]];
        $this->assertEquals(['price' => 100], Arr::get($array, 'products.desk'));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $value = Arr::get($array, 'products.desk');
        $this->assertEquals(['price' => 100], $value);

        $array = ['foo' => null, 'bar' => ['baz' => null]];
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $object = new \ArrayObject($array);
        $value = Arr::get($object, 'products.desk');
        $this->assertEquals(['price' => 100], $value);

        $child = new \ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $array = ['child' => $child];
        $value = Arr::get($array, 'child.products.desk');
        $this->assertEquals(['price' => 100], $value);

        $child = new \ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $parent = new \ArrayObject(['child' => $child]);
        $array = ['parent' => $parent];
        $value = Arr::get($array, 'parent.child.products.desk');
        $this->assertEquals(['price' => 100], $value);

        $child = new \ArrayObject(['products' => ['desk' => ['price' => 100]]]);
        $parent = new \ArrayObject(['child' => $child]);
        $array = ['parent' => $parent];
        $value = Arr::get($array, 'parent.child.desk');
        $this->assertNull($value);

        $object = new \ArrayObject(['products' => ['desk' => null]]);
        $array = ['parent' => $object];
        $value = Arr::get($array, 'parent.products.desk.price');
        $this->assertNull($value);

        $array = new \ArrayObject(['foo' => null, 'bar' => new \ArrayObject(['baz' => null])]);
        $this->assertNull(Arr::get($array, 'foo', 'default'));
        $this->assertNull(Arr::get($array, 'bar.baz', 'default'));

        $array = ['foo', 'bar'];
        $this->assertEquals($array, Arr::get($array, null));

        $this->assertSame('default', Arr::get(null, 'foo', 'default'));
        $this->assertSame('default', Arr::get(false, 'foo', 'default'));

        $this->assertSame('default', Arr::get(null, null, 'default'));

        $this->assertSame([], Arr::get([], null));
        $this->assertSame([], Arr::get([], null, 'default'));
    }

    /**
     * Test for Arr::has().
     *
     * @group system
     */
    public function testHas()
    {
        $array = ['products.desk' => ['price' => 100]];
        $this->assertTrue(Arr::has($array, 'products.desk'));

        $array = ['products' => ['desk' => ['price' => 100]]];
        $this->assertTrue(Arr::has($array, 'products.desk'));
        $this->assertTrue(Arr::has($array, 'products.desk.price'));
        $this->assertFalse(Arr::has($array, 'products.foo'));
        $this->assertFalse(Arr::has($array, 'products.desk.foo'));

        $array = ['foo' => null, 'bar' => ['baz' => null]];
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = new \ArrayObject(['foo' => 10, 'bar' => new \ArrayObject(['baz' => 10])]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));
        $this->assertFalse(Arr::has($array, 'xxx'));
        $this->assertFalse(Arr::has($array, 'xxx.yyy'));
        $this->assertFalse(Arr::has($array, 'foo.xxx'));
        $this->assertFalse(Arr::has($array, 'bar.xxx'));

        $array = new \ArrayObject(['foo' => null, 'bar' => new \ArrayObject(['baz' => null])]);
        $this->assertTrue(Arr::has($array, 'foo'));
        $this->assertTrue(Arr::has($array, 'bar.baz'));

        $array = ['foo', 'bar'];
        $this->assertFalse(Arr::has($array, null));

        $this->assertFalse(Arr::has(null, 'foo'));
        $this->assertFalse(Arr::has(false, 'foo'));

        $this->assertFalse(Arr::has(null, null));
        $this->assertFalse(Arr::has([], null));
    }

    /**
     * Test for Arr::has_any().
     *
     * @group system
     */
    public function testHasAny()
    {
        $array = ['name' => 'Budi', 'age' => '', 'city' => null];
        $this->assertTrue(Arr::has_any($array, 'name'));
        $this->assertTrue(Arr::has_any($array, 'age'));
        $this->assertTrue(Arr::has_any($array, 'city'));
        $this->assertFalse(Arr::has_any($array, 'foo'));
        $this->assertTrue(Arr::has_any($array, 'name', 'email'));
        $this->assertTrue(Arr::has_any($array, ['name', 'email']));

        $array = ['name' => 'Budi', 'email' => 'foo'];
        $this->assertTrue(Arr::has_any($array, 'name', 'email'));
        $this->assertFalse(Arr::has_any($array, 'surname', 'password'));
        $this->assertFalse(Arr::has_any($array, ['surname', 'password']));

        $array = ['foo' => ['bar' => null, 'baz' => '']];
        $this->assertTrue(Arr::has_any($array, 'foo.bar'));
        $this->assertTrue(Arr::has_any($array, 'foo.baz'));
        $this->assertFalse(Arr::has_any($array, 'foo.bax'));
        $this->assertTrue(Arr::has_any($array, ['foo.bax', 'foo.baz']));
    }

    /**
     * Test for Arr::associative().
     *
     * @group system
     */
    public function testAssociative()
    {
        $this->assertTrue(Arr::associative(['a' => 'a', 0 => 'b']));
        $this->assertTrue(Arr::associative([1 => 'a', 0 => 'b']));
        $this->assertTrue(Arr::associative([1 => 'a', 2 => 'b']));
        $this->assertFalse(Arr::associative([0 => 'a', 1 => 'b']));
        $this->assertFalse(Arr::associative(['a', 'b']));
    }

    /**
     * Test for Arr::only().
     *
     * @group system
     */
    public function testOnly()
    {
        $array = ['name' => 'Desk', 'price' => 100, 'orders' => 10];
        $array = Arr::only($array, ['name', 'price']);
        $this->assertEquals(['name' => 'Desk', 'price' => 100], $array);
    }

    /**
     * Test for Arr::pluck() - 1.
     *
     * @group system
     */
    public function testPluck()
    {
        $array = [
            ['developer' => ['name' => 'Budi']],
            ['developer' => ['name' => 'Dewi']],
        ];

        $array = Arr::pluck($array, 'developer.name');

        $this->assertEquals(['Budi', 'Dewi'], $array);
    }

    /**
     * Test for Arr::pluck() - 2.
     *
     * @group system
     */
    public function testPluckWithKeys()
    {
        $array = [
            ['name' => 'Budi', 'role' => 'developer'],
            ['name' => 'Dewi', 'role' => 'developer'],
        ];

        $test1 = Arr::pluck($array, 'role', 'name');
        $test2 = Arr::pluck($array, null, 'name');

        $this->assertEquals(['Budi' => 'developer', 'Dewi' => 'developer'], $test1);
        $this->assertEquals([
            'Budi' => ['name' => 'Budi', 'role' => 'developer'],
            'Dewi' => ['name' => 'Dewi', 'role' => 'developer'],
        ], $test2);
    }

    /**
     * Test for Arr::prepend().
     *
     * @group system
     */
    public function testPrepend()
    {
        $array = Arr::prepend(['one', 'two', 'three', 'four'], 'zero');
        $this->assertEquals(['zero', 'one', 'two', 'three', 'four'], $array);

        $array = Arr::prepend(['one' => 1, 'two' => 2], 0, 'zero');
        $this->assertEquals(['zero' => 0, 'one' => 1, 'two' => 2], $array);
    }

    /**
     * Test for Arr::pull().
     *
     * @group system
     */
    public function testPull()
    {
        $array = ['name' => 'Desk', 'price' => 100];
        $name = Arr::pull($array, 'name');
        $this->assertEquals('Desk', $name);
        $this->assertEquals(['price' => 100], $array);

        $array = ['agung@gmail.com' => 'Agung', 'sarah@localhost' => 'Sarah'];
        $name = Arr::pull($array, 'agung@gmail.com');
        $this->assertEquals('Agung', $name);
        $this->assertEquals(['sarah@localhost' => 'Sarah'], $array);

        $array = ['emails' => ['agung@gmail.com' => 'Agung', 'sarah@localhost' => 'Sarah']];
        $name = Arr::pull($array, 'emails.agung@gmail.com');
        $this->assertEquals(null, $name);
        $this->assertEquals(['emails' => ['agung@gmail.com' => 'Agung', 'sarah@localhost' => 'Sarah']], $array);
    }

    /**
     * Test for Arr::set().
     *
     * @group system
     */
    public function testSet()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::set($array, 'products.desk.price', 200);
        $this->assertEquals(['products' => ['desk' => ['price' => 200]]], $array);
    }

    /**
     * Test for Arr::sort().
     *
     * @group system
     */
    public function testSort()
    {
        $array = [['name' => 'Desk'], ['name' => 'Chair']];

        $array = array_values(Arr::sort($array, function ($value) {
            return $value['name'];
        }));

        $expected = [['name' => 'Chair'], ['name' => 'Desk']];
        $this->assertEquals($expected, $array);
    }

    /**
     * Test for Arr::recsort().
     *
     * @group system
     */
    public function testRecsort()
    {
        $array = [
            'users' => [
                ['name' => 'agung', 'mail' => 'agung@gmail.com', 'numbers' => [2, 1, 0]],
                ['name' => 'sarah', 'age' => 25],
            ],
            'repositories' => [['id' => 1], ['id' => 0]],
            20 => [2, 1, 0],
            30 => [2 => 'a', 1 => 'b', 0 => 'c'],
        ];

        $expect = [
            20 => [0, 1, 2],
            30 => [0 => 'c', 1 => 'b', 2 => 'a'],
            'repositories' => [['id' => 0], ['id' => 1]],
            'users' => [
                ['age' => 25, 'name' => 'sarah'],
                ['mail' => 'agung@gmail.com', 'name' => 'agung', 'numbers' => [0, 1, 2]],
            ],
        ];

        $this->assertEquals($expect, Arr::recsort($array));
    }

    /**
     * Test for Arr::where().
     *
     * @group system
     */
    public function testWhere()
    {
        $array = [100, '200', 300, '400', 500];

        $array = Arr::where($array, function ($key, $value) {
            return is_string($value);
        });

        $this->assertEquals([1 => 200, 3 => 400], $array);
    }

    /**
     * Test for Arr::forget().
     *
     * @group system
     */
    public function testForget()
    {
        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, null);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, []);
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.desk');
        $this->assertEquals(['products' => []], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.desk.price');
        $this->assertEquals(['products' => ['desk' => []]], $array);

        $array = ['products' => ['desk' => ['price' => 100]]];
        Arr::forget($array, 'products.final.price');
        $this->assertEquals(['products' => ['desk' => ['price' => 100]]], $array);

        $array = ['shop' => ['cart' => [150 => 0]]];
        Arr::forget($array, 'shop.final.cart');
        $this->assertEquals(['shop' => ['cart' => [150 => 0]]], $array);

        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arr::forget($array, 'products.desk.price.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50]]]], $array);

        $array = ['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]];
        Arr::forget($array, 'products.desk.final.taxes');
        $this->assertEquals(['products' => ['desk' => ['price' => ['original' => 50, 'taxes' => 60]]]], $array);

        $array = ['products' => ['desk' => ['price' => 50], null => 'something']];
        Arr::forget($array, ['products.amount.all', 'products.desk.price']);
        $this->assertEquals(['products' => ['desk' => [], null => 'something']], $array);

        $array = ['agung@gmail.com' => 'Agung', 'sarah@gmail.com' => 'Sarah'];
        Arr::forget($array, 'agung@gmail.com');
        $this->assertEquals(['sarah@gmail.com' => 'Sarah'], $array);

        $array = ['emails' => ['agung@gmail.com' => ['name' => 'Agung'], 'sarah@localhost' => ['name' => 'Sarah']]];
        Arr::forget($array, ['emails.agung@gmail.com', 'emails.sarah@localhost']);
        $this->assertEquals(['emails' => ['agung@gmail.com' => ['name' => 'Agung']]], $array);
    }
}
