<?php

defined('DS') or exit('No direct script access.');

class ContainerTest extends \PHPUnit_Framework_TestCase
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
     * Test untuk method Container::register() dan Container::resolve().
     *
     * @group system
     */
    public function testRegisteredClassCanBeResolved()
    {
        Container::register('foo', function () {
            return 'bar';
        });

        $this->assertEquals('bar', Container::resolve('foo'));
    }

    /**
     * Test untuk method Container::singleton().
     *
     * @group system
     */
    public function testSingletonsAreCreatedOnce()
    {
        Container::singleton('foo', function () {
            return new \stdClass();
        });

        $object = Container::resolve('foo');

        $this->assertTrue($object === Container::resolve('foo'));
    }

    /**
     * Test untuk method Container::instance().
     *
     * @group system
     */
    public function testInstancesAreReturnedBySingleton()
    {
        $object = new \stdClass();

        Container::instance('bar', $object);

        $this->assertTrue($object === Container::resolve('bar'));
    }

    /**
     * Test untuk method Container::registered().
     */
    public function testRegisteredMethodIndicatesIfRegistered()
    {
        Container::register('registered', function () {
            return 'registered';
        });

        $this->assertTrue(Container::registered('foo'));
        $this->assertFalse(Container::registered('baz'));
    }

    /**
     * Test untuk method Container::controller().
     *
     * @group system
     */
    public function testControllerMethodRegistersAController()
    {
        Container::register('controller: container.test', function () {
            return 'test_controller';
        });

        $this->assertTrue(Container::registered('controller: container.test'));
    }

    /**
     * Test bahwa kelas dengan parameter opsional bisa diresolve.
     */
    public function testOptionalParamClassResolves()
    {
        $object = Container::resolve('TestOptionalParamClassForContainer');

        $this->assertInstanceOf('TestOptionalParamClassForContainer', $object);
    }

    /**
     * Test bahwa kelas TestParentClassForContainer bisa diresolve via Container.
     */
    public function testClassOneForContainerResolves()
    {
        $object = Container::resolve('TestParentClassForContainer');

        $this->assertInstanceOf('TestParentClassForContainer', $object);
    }

    /**
     * Test bahwa kelas TestChildClassForContainer bisa diresolve.
     */
    public function testClassTwoForContainerResolves()
    {
        $object = Container::resolve('TestChildClassForContainer');

        $this->assertInstanceOf('TestChildClassForContainer', $object);
    }

    /**
     * Test bahwa ketika kelas TestChildClassForContainer diresolve.
     * dependensi miliknya juga harus ter-resolve.
     */
    public function testClassTwoResolvesClassOneDependency()
    {
        $child = Container::resolve('TestChildClassForContainer');
        $this->assertInstanceOf('TestParentClassForContainer', $child->parent);
    }

    /**
     * Test bahwa ketika kelas TestChildClassForContainer diresolve dengan,
     * sebuah parameter, parameter itulah yang harus digunakan oleh si kelas ini,
     * bukan hanya objek TestParentClassForContainer kosong.
     */
    public function testClassTwoResolvesClassOneWithArgument()
    {
        $parent = Container::resolve('TestParentClassForContainer');
        $parent->age = 42;

        $child = Container::resolve('TestChildClassForContainer', [$parent]);
        $this->assertEquals(42, $child->parent->age);
        $this->assertEquals('This is parentClassMethod', $child->parent->parentClassMethod());
    }
}

class TestOptionalParamClassForContainer
{
    public function __construct($optional = 42)
    {
        // ..
    }
}

class TestParentClassForContainer
{
    public $age;

    public function parentClassMethod()
    {
        return 'This is parentClassMethod';
    }
}

class TestChildClassForContainer
{
    public $parent;

    public function __construct(TestParentClassForContainer $parent)
    {
        $this->parent = $parent;
    }
}
