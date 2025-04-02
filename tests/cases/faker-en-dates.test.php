<?php

defined('DS') or exit('No direct access.');

use System\Foundation\Faker\Generator as FakerGenerator;
use System\Foundation\Faker\Provider\Dates;

class FakerEnDatesTest extends \PHPUnit_Framework_TestCase
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

    public function testUnixTime()
    {
        $timestamp = Dates::unixTime();
        $this->assertInternalType('int', $timestamp);
        $this->assertTrue($timestamp >= 0);
        $this->assertTrue($timestamp <= time());
    }

    public function testDateTime()
    {
        $date = Dates::dateTime();
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertGreaterThanOrEqual(new \DateTime('@0'), $date);
        $this->assertLessThanOrEqual(new \DateTime(), $date);
    }

    public function testDateTimeAD()
    {
        $date = Dates::dateTimeAD();
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertGreaterThanOrEqual(new \DateTime('0000-01-01 00:00:00'), $date);
        $this->assertLessThanOrEqual(new \DateTime(), $date);
    }

    public function testIso8601()
    {
        $date = Dates::iso8601();
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-Z](\d{4})?$/', $date);
        $this->assertGreaterThanOrEqual(new \DateTime('@0'), new \DateTime($date));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($date));
    }

    public function testDate()
    {
        $date = Dates::date();
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}$/', $date);
        $this->assertGreaterThanOrEqual(new \DateTime('@0'), new \DateTime($date));
        $this->assertLessThanOrEqual(new \DateTime(), new \DateTime($date));
    }

    public function testTime()
    {
        $this->assertRegExp('/^\d{2}:\d{2}:\d{2}$/', Dates::time());
    }

    /**
     *
     * @dataProvider providerDateTimeBetween
     */
    public function testDateTimeBetween($start, $end)
    {
        $date = Dates::dateTimeBetween($start, $end);
        $this->assertInstanceOf('\DateTime', $date);
        $this->assertGreaterThanOrEqual(new \DateTime($start), $date);
        $this->assertLessThanOrEqual(new \DateTime($end), $date);
    }

    public function providerDateTimeBetween()
    {
        return [['-1 year', false], ['-1 year', null], ['-1 day', '-1 hour'], ['-1 day', 'now']];
    }

    public function testFixedSeedWithMaximumTimestamp()
    {
        // FIXME: InvalidArgumentException: Start date must be anterior to end date.
        // $max = '2018-03-01 12:00:00';
        // mt_srand(1);
        // $unixTime = Dates::unixTime($max);
        // $datetimeAD = Dates::dateTimeAD($max);
        // $dateTime1 = Dates::dateTime($max);
        // $dateTimeBetween = Dates::dateTimeBetween('2014-03-01 06:00:00', $max);
        // $date = Dates::date('Y-m-d', $max);
        // $time = Dates::time('H:i:s', $max);
        // $iso8601 = Dates::iso8601($max);
        // $dateTimeThisCentury = Dates::dateTimeThisCentury($max);
        // $dateTimeThisDecade = Dates::dateTimeThisDecade($max);
        // $dateTimeThisMonth = Dates::dateTimeThisMonth($max);
        // $amPm = Dates::amPm($max);
        // $dayOfMonth = Dates::dayOfMonth($max);
        // $dayOfWeek = Dates::dayOfWeek($max);
        // $month = Dates::month($max);
        // $monthName = Dates::monthName($max);
        // $year = Dates::year($max);
        // $dateTimeThisYear = Dates::dateTimeThisYear($max);
        // mt_srand();
        // mt_srand(1);
        // $this->assertEquals($unixTime, Dates::unixTime($max));
        // $this->assertEquals($datetimeAD, Dates::dateTimeAD($max));
        // $this->assertEquals($dateTime1, Dates::dateTime($max));
        // $this->assertEquals($dateTimeBetween, Dates::dateTimeBetween('2014-03-01 06:00:00', $max));
        // $this->assertEquals($date, Dates::date('Y-m-d', $max));
        // $this->assertEquals($time, Dates::time('H:i:s', $max));
        // $this->assertEquals($iso8601, Dates::iso8601($max));
        // $this->assertEquals($dateTimeThisCentury, Dates::dateTimeThisCentury($max));
        // $this->assertEquals($dateTimeThisDecade, Dates::dateTimeThisDecade($max));
        // $this->assertEquals($dateTimeThisMonth, Dates::dateTimeThisMonth($max));
        // $this->assertEquals($amPm, Dates::amPm($max));
        // $this->assertEquals($dayOfMonth, Dates::dayOfMonth($max));
        // $this->assertEquals($dayOfWeek, Dates::dayOfWeek($max));
        // $this->assertEquals($month, Dates::month($max));
        // $this->assertEquals($monthName, Dates::monthName($max));
        // $this->assertEquals($year, Dates::year($max));
        // $this->assertEquals($dateTimeThisYear, Dates::dateTimeThisYear($max));
        // mt_srand();
    }
}
