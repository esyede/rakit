<?php

defined('DS') or exit('No direct access.');

use System\Email;
use System\Config;

class EmailTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        // Reset static properties
        $reflection = new \ReflectionClass('System\Email');
        $drivers = $reflection->getProperty('drivers');
        /** @disregard */
        $drivers->setAccessible(true);
        $drivers->setValue([]);

        $registrar = $reflection->getProperty('registrar');
        /** @disregard */
        $registrar->setAccessible(true);
        $registrar->setValue([]);

        // Set up config
        Config::set('email.driver', 'dummy');
        Config::set('email', [
            'driver' => 'dummy',
            'as_html' => null,
            'encoding' => '8bit',
            'encode_headers' => true,
            'priority' => Email::NORMAL,
            'from' => [
                'email' => 'noreply@example.com',
                'name' => 'Administrator',
            ],
            'validate' => true,
            'attachify' => true,
            'alternatify' => true,
            'force_mixed' => false,
            'wordwrap' => 76,
            'newline' => "\n",
            'return_path' => false,
            'strip_comments' => true,
            'protocol_replacement' => false,
        ]);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        // Reset static properties
        $reflection = new \ReflectionClass('\System\Email');
        $drivers = $reflection->getProperty('drivers');
        /** @disregard */
        $drivers->setAccessible(true);
        $drivers->setValue([]);
    }

    /**
     * Test email constants.
     */
    public function testConstants()
    {
        $this->assertEquals('5 (Lowest)', Email::LOWEST);
        $this->assertEquals('4 (Low)', Email::LOW);
        $this->assertEquals('3 (Normal)', Email::NORMAL);
        $this->assertEquals('2 (High)', Email::HIGH);
        $this->assertEquals('1 (Highest)', Email::HIGHEST);
    }

    /**
     * Test driver method returns correct instance.
     */
    public function testDriverReturnsInstance()
    {
        $driver = Email::driver();
        $this->assertInstanceOf('System\Email\Drivers\Log', $driver);
    }

    /**
     * Test driver method caches instance.
     */
    public function testDriverCachesInstance()
    {
        $driver1 = Email::driver();
        $driver2 = Email::driver();
        $this->assertSame($driver1, $driver2);
    }

    /**
     * Test factory creates correct driver.
     */
    public function testFactoryCreatesCorrectDriver()
    {
        $reflection = new \ReflectionClass('System\Email');
        $factory = $reflection->getMethod('factory');
        /** @disregard */
        $factory->setAccessible(true);

        $driver = $factory->invoke(null, 'dummy');
        $this->assertInstanceOf('System\Email\Drivers\Log', $driver);
    }

    /**
     * Test factory throws exception for invalid driver.
     */
    public function testFactoryThrowsForInvalidDriver()
    {
        $reflection = new \ReflectionClass('System\Email');
        $factory = $reflection->getMethod('factory');
        /** @disregard */
        $factory->setAccessible(true);

        $this->setExpectedException('Exception', 'Unsupported email driver: invalid');
        $factory->invoke(null, 'invalid');
    }

    /**
     * Test extend registers custom driver.
     */
    public function testExtendRegistersCustomDriver()
    {
        Email::extend('custom', function() {
            $config = Config::get('email');
            return new \System\Email\Drivers\Log($config);
        });

        $reflection = new \ReflectionClass('System\Email');
        $factory = $reflection->getMethod('factory');
        /** @disregard */
        $factory->setAccessible(true);

        $driver = $factory->invoke(null, 'custom');
        $this->assertInstanceOf('System\Email\Drivers\Log', $driver);
    }

    /**
     * Test reset clears drivers cache.
     */
    public function testResetClearsDrivers()
    {
        Email::driver(); // Load driver

        $reflection = new \ReflectionClass('System\Email');
        $drivers = $reflection->getProperty('drivers');
        /** @disregard */
        $drivers->setAccessible(true);
        $this->assertNotEmpty($drivers->getValue());

        Email::reset();
        $this->assertEmpty($drivers->getValue());
    }

    /**
     * Test __callStatic forwards to driver.
     */
    public function testCallStaticForwardsToDriver()
    {
        Email::subject('Test Subject');

        $reflection = new \ReflectionClass('System\Email');
        $drivers = $reflection->getProperty('drivers');
        /** @disregard */
        $drivers->setAccessible(true);
        $cached_drivers = $drivers->getValue();
        $driver = $cached_drivers['dummy'];

        $prop = new \ReflectionProperty($driver, 'subject');
        /** @disregard */
        $prop->setAccessible(true);
        $this->assertEquals('Test Subject', $prop->getValue($driver));
    }
}
