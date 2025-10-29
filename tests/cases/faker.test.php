<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Common;
use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Calculator\Luhn;
use System\Foundation\Faker\Unique;

class FakerTest extends \PHPUnit_Framework_TestCase
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

    public function testGeneratorReturnsNullByDefault()
    {
        $generator = new Common;
        $this->assertSame(null, $generator->value);
    }

    public function testGeneratorReturnsDefaultValueForAnyPropertyGet()
    {
        $generator = new Common(123);
        $this->assertSame(123, $generator->foo);
        $this->assertNotSame(null, $generator->bar);
    }

    public function testGeneratorReturnsDefaultValueForAnyMethodCall()
    {
        $generator = new Common(123);
        $this->assertSame(123, $generator->foobar());
    }

    public function testAddProviderGivesPriorityToNewlyAddedProvider()
    {
        $generator = new FakerGenerator;
        $generator->addProvider(new FooProvider());
        $generator->addProvider(new BarProvider());
        $this->assertEquals('barfoo', $generator->format('fooFormatter'));
    }

    public function testGetFormatterReturnsCallable()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertTrue(is_callable($generator->getFormatter('fooFormatter')));
    }

    public function testGetFormatterReturnsCorrectFormatter()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $expected = array($provider, 'fooFormatter');
        $this->assertEquals($expected, $generator->getFormatter('fooFormatter'));
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetFormatterThrowsExceptionOnIncorrectProvider()
    {
        $generator = new FakerGenerator;
        $generator->getFormatter('fooFormatter');
    }

    /**
     * @expectedException InvalidArgumentException
     */
    public function testGetFormatterThrowsExceptionOnIncorrectFormatter()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $generator->getFormatter('barFormatter');
    }

    public function testFormatCallsFormatterOnProvider()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('foobar', $generator->format('fooFormatter'));
    }

    public function testFormatTransfersArgumentsToFormatter()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('bazfoo', $generator->format('fooFormatterWithArguments', array('foo')));
    }

    public function testParseReturnsSameStringWhenItContainsNoCurlyBraces()
    {
        $generator = new FakerGenerator();
        $this->assertEquals('fooBar#?', $generator->parse('fooBar#?'));
    }

    public function testParseReturnsStringWithTokensReplacedByFormatters()
    {
        $generator = new FakerGenerator();
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('This is foobar a text with foobar', $generator->parse('This is {{fooFormatter}} a text with {{ fooFormatter }}'));
    }

    public function testMagicGetCallsFormat()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('foobar', $generator->fooFormatter);
    }

    public function testMagicCallCallsFormat()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('foobar', $generator->fooFormatter());
    }

    public function testMagicCallCallsFormatWithArguments()
    {
        $generator = new FakerGenerator;
        $provider = new FooProvider();
        $generator->addProvider($provider);
        $this->assertEquals('bazfoo', $generator->fooFormatterWithArguments('foo'));
    }

    public function testSeed()
    {
        $generator = new FakerGenerator;
        $generator->seed(0);
        $mtRandWithSeedZero = mt_rand();
        $generator->seed(0);
        $this->assertEquals($mtRandWithSeedZero, mt_rand(), 'seed(0) should be deterministic.');

        $generator->seed();
        $mtRandWithoutSeed = mt_rand();
        $this->assertNotEquals($mtRandWithSeedZero, $mtRandWithoutSeed, 'seed() should be different than seed(0)');
        $generator->seed();
        $this->assertNotEquals($mtRandWithoutSeed, mt_rand(), 'seed() should not be deterministic.');
    }

    public function testUniqueReturnsUniqueScalarValues()
    {
        $gen = $this->getMockBuilder('\System\Foundation\Faker\Generator')->setMethods(['value'])->getMock();
        $gen->expects($this->any())->method('value')->will($this->returnCallback(function () {
            return mt_rand(1, 1000000);
        }));

        $unique = new Unique($gen);
        $values = [];
        for ($i = 0; $i < 1000; $i++) {
            $v = $unique->value();
            $this->assertNotContains($v, $values);
            $values[] = $v;
        }
    }

    public function testUniqueThrowsOverflowWhenExhausted()
    {
        $gen = $this->getMockBuilder('\System\Foundation\Faker\Generator')->setMethods(['value'])->getMock();
        $gen->expects($this->any())->method('value')->will($this->returnValue('same'));
        $this->setExpectedException('\OverflowException');
        $unique = new Unique($gen, 10);
        $unique->value();
        $unique->value();
    }

    public function checkDigitProvider()
    {
        return [
            ['7992739871', '3'],
            ['3852000002323', '7'],
            ['37144963539843', '1'],
            ['561059108101825', '0'],
            ['601100099013942', '4'],
            ['510510510510510', '0'],
            [7992739871, '3'],
            [3852000002323, '7'],
            [37144963539843, '1'],
            [561059108101825, '0'],
            [601100099013942, '4'],
            [510510510510510, '0']
        ];
    }

    /**
     * @dataProvider checkDigitProvider
     */
    public function testComputeCheckDigit($partialNumber, $checkDigit)
    {
        $this->assertInternalType('string', $checkDigit);
        $this->assertEquals($checkDigit, Luhn::computeCheckDigit($partialNumber));
    }

    public function validatorProvider()
    {
        return [
            ['79927398710', false],
            ['79927398711', false],
            ['79927398712', false],
            ['79927398713', true],
            ['79927398714', false],
            ['79927398715', false],
            ['79927398716', false],
            ['79927398717', false],
            ['79927398718', false],
            ['79927398719', false],
            [79927398713, true],
            [79927398714, false],
        ];
    }

    /**
     * @dataProvider validatorProvider
     */
    public function testIsValid($number, $isValid)
    {
        $this->assertEquals($isValid, Luhn::isValid($number));
    }
}

class FooProvider
{
    public function fooFormatter()
    {
        return 'foobar';
    }

    public function fooFormatterWithArguments($value = '')
    {
        return 'baz' . $value;
    }
}

class BarProvider
{
    public function fooFormatter()
    {
        return 'barfoo';
    }
}
