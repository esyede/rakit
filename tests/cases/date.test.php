<?php

defined('DS') or exit('No direct script access.');

use System\Date;
use System\Config;

class DateTest extends \PHPUnit_Framework_TestCase
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
     * Test untuk Date::make().
     *
     * @group system
     */
    public function testMake()
    {
        $this->assertInstanceOf('\System\Date', Date::make());
        $this->assertInstanceOf('\System\Date', Date::make(1621987200));
        $this->assertInstanceOf('\System\Date', Date::make(new \DateTime()));
        $this->assertInstanceOf('\System\Date', Date::make('last sunday'));
    }

    /**
     * Test untuk Date::remake().
     *
     * @group system
     */
    public function testAdjust()
    {
        $date = Date::make('2021-05-23');

        $this->assertInstanceOf('\System\Date', $date->remake('-2 days'));
        $this->assertInstanceOf('\System\Date', $date->remake('-2 days', true));

        $date = Date::make('2021-05-23');

        $this->assertSame('2021-05-21', $date->remake('-2 days')->format('Y-m-d'));
        $this->assertSame('2021-07-21', $date->remake('+2 months')->format('Y-m-d'));
    }

    /**
     * Test untuk Date::time().
     *
     * @group system
     */
    public function testTimestamp()
    {
        $this->assertTrue(is_numeric(Date::make()->timestamp()));
    }

    /**
     * Test untuk Date::format().
     *
     * @group system
     */
    public function testFormat()
    {
        $this->assertTrue(is_string(Date::make()->format('Y-m-d H:i:s')));
        $this->assertSame(date('F, j Y H:i:s'), Date::make()->format('F, j Y H:i:s'));

        $this->assertFalse(Date::make()->format('F, j Y H:i:s') === date('Y-m-d H:i:s'));
    }

    /**
     * Test untuk Date::remake().
     *
     * @group system
     */
    public function testRemake()
    {
        $this->assertInstanceOf('\System\Date', Date::make()->remake('+1 day'));
        $this->assertInstanceOf('\System\Date', Date::make()->remake('+1 day', true));
    }

    /**
     * Test untuk Date::ago().
     *
     * @group system
     */
    public function testAgo()
    {
        $language = Config::get('application.language');

        Config::set('application.language', 'en');
        $this->assertSame('3 days from now', Date::make()->remake('+3 days')->ago());
        $this->assertSame('1 week ago', Date::make()->remake('-8 days')->ago());

        Config::set('application.language', 'id');
        $this->assertSame('3 hari dari sekarang', Date::make()->remake('+3 days')->ago());
        $this->assertSame('1 minggu yang lalu', Date::make()->remake('-8 days')->ago());
        Config::set('application.language', $language);
    }

    /**
     * Test untuk Date::diff().
     *
     * @group system
     */
    public function testDiff()
    {
        $this->assertInstanceOf('\DateInterval', Date::diff('2021-05-23', '2021-05-26'));
        $this->assertInstanceOf('\DateInterval', Date::diff('2021-05-23', 1621987200));
        $this->assertInstanceOf('\DateInterval', Date::diff('2021-05-23', new \DateTime('2021-05-26')));

        $diff = Date::diff('2021-05-23', '2021-05-26');

        $this->assertTrue($diff->d === 3);
        $this->assertSame('3 hari', $diff->format('%d hari'));
    }

    /**
     * Test untuk Date::eq().
     *
     * @group system
     */
    public function testEq()
    {
        $date1 = Date::make('2021-05-23');
        $date2 = Date::make('2021-05-23');

        $this->assertTrue(Date::eq($date1, $date2));
        $this->assertFalse(Date::eq($date1, $date2->remake('-2 days')));
    }

    /**
     * Test untuk Date::gt().
     *
     * @group system
     */
    public function testGt()
    {
        $date1 = Date::make('2021-05-23');
        $date2 = Date::make('2021-05-23');

        $this->assertFalse(Date::gt($date1, $date2));
        $this->assertTrue(Date::gt($date1->remake('+2 days'), $date2));
    }

    /**
     * Test untuk Date::lt().
     *
     * @group system
     */
    public function testLt()
    {
        $date1 = Date::make('2021-05-23');
        $date2 = Date::make('2021-05-23');

        $this->assertFalse(Date::lt($date1, $date2));
        $this->assertTrue(Date::lt($date1->remake('-2 days'), $date2));
    }

    /**
     * Test untuk Date::gte().
     *
     * @group system
     */
    public function testGte()
    {
        $date1 = Date::make('2021-05-23');
        $date2 = Date::make('2021-05-23');

        $this->assertTrue(Date::gte($date1, $date2));
        $this->assertFalse(Date::gte($date1->remake('-2 days'), $date2));
        $this->assertTrue(Date::gte($date1->remake('+4 days'), $date2));
    }

    /**
     * Test untuk Date::lte().
     *
     * @group system
     */
    public function testLte()
    {
        $date1 = Date::make('2021-05-23');
        $date2 = Date::make('2021-05-23');

        $this->assertTrue(Date::lte($date1, $date2));
        $this->assertTrue(Date::lte($date1->remake('-2 days'), $date2));
        $this->assertFalse(Date::lte($date1->remake('+4 days'), $date2));
    }
}
