<?php

defined('DS') or exit('No direct script access.');

class MacroableTest extends \PHPUnit_Framework_TestCase
{
    private $macroable;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->macroable = new EmptyMacroable();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testRegisterMacro()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'foobar';
        });
        $this->assertSame('foobar', $macroable::{__CLASS__}());
    }

    public function testRegisterMacroAndCallWithoutStatic()
    {
        $macroable = $this->macroable;
        $macroable::macro(__CLASS__, function () {
            return 'foobar';
        });
        $this->assertSame('foobar', $macroable->{__CLASS__}());
    }

    public function testWhenCallingMacroClosureIsBoundToObject()
    {
        TestMacroable::macro('tryInstance', function () {
            return $this->protectedProp;
        });

        TestMacroable::macro('tryStatic', function () {
            return TestMacroable::getProtectedStatic();
        });

        $instance = new TestMacroable();

        $result = $instance->tryInstance();
        $this->assertSame('instance', $result);

        $result = TestMacroable::tryStatic();
        $this->assertSame('static', $result);
    }

    public function testClassBasedMacros()
    {
        TestMacroable::mixin(new TestMixin());
        $instance = new TestMacroable();
        $this->assertSame('instance-foobar', $instance->methodOne('foobar'));
    }

    public function testClassBasedMacrosNoReplace()
    {
        TestMacroable::macro('methodThree', function () {
            return 'bar';
        });
        TestMacroable::mixin(new TestMixin(), false);
        $instance = new TestMacroable();
        $this->assertSame('bar', $instance->methodThree());

        TestMacroable::mixin(new TestMixin());
        $this->assertSame('foo', $instance->methodThree());
    }

    public function testFlushMacros()
    {
        TestMacroable::macro('flushMethod', function () {
            return 'flushMethod';
        });

        $instance = new TestMacroable();
        $this->assertSame('flushMethod', $instance->flushMethod());

        try {
            TestMacroable::flushMacros();
        } catch (\Throwable $e) {
            $this->assertTrue($e instanceof \BadMethodCallException);
            $instance->flushMethod();
        } catch (\Exception $e) {
            $this->assertTrue($e instanceof \BadMethodCallException);
            $instance->flushMethod();
        }
    }
}

class EmptyMacroable
{
    use \System\Macroable;
}

class TestMacroable
{
    use \System\Macroable;

    protected $protectedProp = 'instance';

    protected static function getProtectedStatic()
    {
        return 'static';
    }
}

class TestMixin
{
    public function methodOne()
    {
        return function ($value) {
            return $this->methodTwo($value);
        };
    }

    protected function methodTwo()
    {
        return function ($value) {
            return $this->protectedProp . '-' . $value;
        };
    }

    protected function methodThree()
    {
        return function () {
            return 'foo';
        };
    }
}
