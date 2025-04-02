<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Factory;

class FakerEnLocalizationTest extends \PHPUnit_Framework_TestCase
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

    public function testLocalizedNameProvidersDoNotThrowErrors()
    {
        $glob = glob(path('system') . 'foundation/faker/provider/*/person.php');
        foreach ($glob as $localized) {
            preg_match('#/([a-z]+)/person\.php#', $localized, $matches);
            $faker = Factory::create($matches[1]);
            $this->assertNotNull($faker->name(), 'Localized Name Provider ' . $matches[1] . ' does not throw errors');
        }
    }

    public function testLocalizedAddressProvidersDoNotThrowErrors()
    {
        $glob = glob(path('system') . 'foundation/faker/provider/*/address.php');
        foreach ($glob as $localized) {
            preg_match('#/([a-z]+)/address\.php#', $localized, $matches);
            $faker = Factory::create($matches[1]);
            $this->assertNotNull($faker->address(), 'Localized Address Provider ' . $matches[1] . ' does not throw errors');
        }
    }
}
