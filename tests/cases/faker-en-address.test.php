<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\Address;

class FakerEnAddressTest extends \PHPUnit_Framework_TestCase
{
    private $faker;

    /**
     * Setup.
     */
    public function setUp()
    {
        $faker = new FakerGenerator();
        $faker->addProvider(new Address($faker));
        $this->faker = $faker;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testLatitude()
    {
        $latitude = $this->faker->latitude();
        $this->assertInternalType('float', $latitude);
        $this->assertGreaterThanOrEqual(-90, $latitude);
        $this->assertLessThanOrEqual(90, $latitude);
    }

    public function testLongitude()
    {
        $longitude = $this->faker->longitude();
        $this->assertInternalType('float', $longitude);
        $this->assertGreaterThanOrEqual(-180, $longitude);
        $this->assertLessThanOrEqual(180, $longitude);
    }
}
