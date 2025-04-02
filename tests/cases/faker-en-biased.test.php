<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\Biased;

class FakerEnBiasedTest extends \PHPUnit_Framework_TestCase
{
    const MAX = 10;
    const NUMBERS = 25000;

    protected $generator;
    protected $results = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->generator = new FakerGenerator();
        $this->generator->addProvider(new Biased($this->generator));
        $this->results = array_fill(1, self::MAX, 0);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

     public function performFake($function)
    {
        for ($i = 0; $i < self::NUMBERS; $i++) {
            $this->results[$this->generator->biasedNumberBetween(1, self::MAX, $function)]++;
        }
    }

    public function testUnbiased()
    {
        $this->performFake(['\System\Foundation\Faker\Provider\Biased', 'unbiased']);
        foreach ($this->results as $number => $amount) {
            $assumed = (1 / self::MAX * $number) - (1 / self::MAX * ($number - 1));
            $assumed /= 1;
            $this->assertGreaterThan(self::NUMBERS * $assumed * .95, $amount, "Value was more than 5 percent under the expected value");
            $this->assertLessThan(self::NUMBERS * $assumed * 1.05, $amount, "Value was more than 5 percent over the expected value");
        }
    }

    public function testLinearHigh()
    {
        $this->performFake(array('\System\Foundation\Faker\Provider\Biased', 'linearHigh'));
        foreach ($this->results as $number => $amount) {
            $assumed = 0.5 * pow(1 / self::MAX * $number, 2) - 0.5 * pow(1 / self::MAX * ($number - 1), 2);
            $assumed /= pow(1, 2) * .5;
            $this->assertGreaterThan(self::NUMBERS * $assumed * .9, $amount, "Value was more than 10 percent under the expected value");
            $this->assertLessThan(self::NUMBERS * $assumed * 1.1, $amount, "Value was more than 10 percent over the expected value");
        }
    }

    public function testLinearLow()
    {
        $this->performFake(array('\System\Foundation\Faker\Provider\Biased', 'linearLow'));
        foreach ($this->results as $number => $amount) {
            $assumed = -0.5 * pow(1 / self::MAX * $number, 2) - -0.5 * pow(1 / self::MAX * ($number - 1), 2);
            $assumed += 1 / self::MAX;
            $assumed /= pow(1, 2) * .5;
            $this->assertGreaterThan(self::NUMBERS * $assumed * .9, $amount, "Value was more than 10 percent under the expected value");
            $this->assertLessThan(self::NUMBERS * $assumed * 1.1, $amount, "Value was more than 10 percent over the expected value");
        }
    }
}
