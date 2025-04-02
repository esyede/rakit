<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\id\Person;

class FakerIdPersonTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        $faker = new FakerGenerator();
        $faker->addProvider(new Person($faker));
        $this->faker = $faker;
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // ..
    }

    public function testIfFirstNameMaleCanReturnData()
    {
        $this->assertNotEmpty($this->faker->firstNameMale());
    }

    public function testIfLastNameMaleCanReturnData()
    {
        $this->assertNotEmpty($this->faker->lastNameMale());
    }

    public function testIfFirstNameFemaleCanReturnData()
    {
        $this->assertNotEmpty($this->faker->firstNameFemale());
    }

    public function testIfLastNameFemaleCanReturnData()
    {
        $this->assertNotEmpty($this->faker->lastNameFemale());
    }
}
