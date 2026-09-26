<?php

defined('DS') or exit('No direct access.');

use System\Email;
use System\Config;
use System\Hook;

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
        PHP_VERSION_ID < 80100 && $drivers->setAccessible(true);
        $drivers->setValue(null, []);

        $registrar = $reflection->getProperty('registrar');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $registrar->setAccessible(true);
        $registrar->setValue(null, []);

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
        PHP_VERSION_ID < 80100 && $drivers->setAccessible(true);
        $drivers->setValue(null, []);
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
        PHP_VERSION_ID < 80100 && $factory->setAccessible(true);

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
        PHP_VERSION_ID < 80100 && $factory->setAccessible(true);

        $this->setExpectedException('Exception', 'Unsupported email driver: invalid');
        $factory->invoke(null, 'invalid');
    }

    /**
     * Test extend registers custom driver.
     */
    public function testExtendRegistersCustomDriver()
    {
        Email::extend('custom', function () {
            $config = Config::get('email');
            return new \System\Email\Drivers\Log($config);
        });

        $reflection = new \ReflectionClass('System\Email');
        $factory = $reflection->getMethod('factory');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $factory->setAccessible(true);

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
        PHP_VERSION_ID < 80100 && $drivers->setAccessible(true);
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
        PHP_VERSION_ID < 80100 && $drivers->setAccessible(true);
        $cached_drivers = $drivers->getValue();
        $driver = $cached_drivers['dummy'];

        $prop = new \ReflectionProperty($driver, 'subject');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $prop->setAccessible(true);
        $this->assertEquals('Test Subject', $prop->getValue($driver));
    }

    /**
     * Test that a 'rakit.mail.sending' listener can call the sending off, and
     * that 'rakit.mail.sent' then stays quiet.
     *
     * @group system
     */
    public function testSendingListenerCanCancelTheSending()
    {
        $sending = [];
        $sent = [];

        Hook::listen('rakit.mail.sending', function ($message) use (&$sending) {
            $sending[] = $message;

            return false;
        });

        Hook::listen('rakit.mail.sent', function ($message) use (&$sent) {
            $sent[] = $message;
        });

        $driver = Email::driver();
        $result = $driver->to('budi@example.com')->subject('Hi')->body('Hello')->send();

        $this->assertFalse($result);
        $this->assertCount(1, $sending);
        $this->assertCount(0, $sent);
        $this->assertSame($driver, $sending[0]);

        Hook::clear('rakit.mail.sending');
        Hook::clear('rakit.mail.sent');
    }

    /**
     * Test that a message that goes out announces 'rakit.mail.sent'.
     *
     * @group system
     */
    public function testSentIsAnnouncedWhenTheMessageGoesOut()
    {
        $sending = [];
        $sent = [];

        Hook::listen('rakit.mail.sending', function ($message) use (&$sending) {
            $sending[] = $message;
        });

        Hook::listen('rakit.mail.sent', function ($message) use (&$sent) {
            $sent[] = $message;
        });

        $driver = Email::driver();
        $result = $driver->to('budi@example.com')->subject('Hi')->body('Hello')->send();

        $this->assertTrue($result);
        $this->assertCount(1, $sending);
        $this->assertCount(1, $sent);
        $this->assertSame($driver, $sending[0]);
        $this->assertSame($driver, $sent[0]);

        Hook::clear('rakit.mail.sending');
        Hook::clear('rakit.mail.sent');
    }
}
