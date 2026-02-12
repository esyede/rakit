<?php

defined('DS') or exit('No direct access.');

use System\Container;

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
     * Test for Container::register() and Container::resolve().
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
     * Test for Container::singleton().
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
     * Test for Container::instance().
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
     * Test for Container::registered().
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
     * Test for Container::controller().
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
     * Test that TestOptionalParamClassForContainer class can be resolved.
     */
    public function testOptionalParamClassResolves()
    {
        $object = Container::resolve('TestOptionalParamClassForContainer');
        $this->assertInstanceOf('TestOptionalParamClassForContainer', $object);
    }

    /**
     * Test that TestParentClassForContainer class can be resolved.
     */
    public function testClassOneForContainerResolves()
    {
        $object = Container::resolve('TestParentClassForContainer');

        $this->assertInstanceOf('TestParentClassForContainer', $object);
    }

    /**
     * Test that TestChildClassForContainer class can be resolved.
     */
    public function testClassTwoForContainerResolves()
    {
        $object = Container::resolve('TestChildClassForContainer');

        $this->assertInstanceOf('TestChildClassForContainer', $object);
    }

    /**
     * Test that ketika kelas TestChildClassForContainer diresolve,
     * Dependencies of TestParentClassForContainer should be resolved otomatically.
     */
    public function testClassTwoResolvesClassOneDependency()
    {
        $child = Container::resolve('TestChildClassForContainer');
        $this->assertInstanceOf('TestParentClassForContainer', $child->parent);
    }

    /**
     * Test that when resolving TestChildClassForContainer with an argument,
     * the passed argument is used as dependency.
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
