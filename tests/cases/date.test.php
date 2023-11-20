<?php

defined('DS') or exit('No direct access.');

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
     * Test tambah.
     *
     * @group system
     */
    public function testAdd()
    {
        $this->assertSame(1976, Date::createFromDate(1975)->addYears(1)->year);
        $this->assertSame(1975, Date::createFromDate(1975)->addYears(0)->year);
        $this->assertSame(1974, Date::createFromDate(1975)->addYears(-1)->year);
        $this->assertSame(1976, Date::createFromDate(1975)->addYear()->year);
        $this->assertSame(1, Date::createFromDate(1975, 12)->addMonths(1)->month);
        $this->assertSame(12, Date::createFromDate(1975, 12)->addMonths(0)->month);
        $this->assertSame(11, Date::createFromDate(1975, 12, 1)->addMonths(-1)->month);
        $this->assertSame(1, Date::createFromDate(1975, 12)->addMonth()->month);
        $this->assertSame(3, Date::createFromDate(2012, 1, 31)->addMonth()->month);

        $this->assertSame('2012-02-29', Date::createFromDate(2012, 1, 31)->addMonthNoOverflow()->toDateString());
        $this->assertSame('2012-03-31', Date::createFromDate(2012, 1, 31)->addMonthsNoOverflow(2)->toDateString());
        $this->assertSame('2012-03-29', Date::createFromDate(2012, 2, 29)->addMonthNoOverflow()->toDateString());
        $this->assertSame('2012-02-29', Date::createFromDate(2011, 12, 31)->addMonthsNoOverflow(2)->toDateString());
        $this->assertSame(12, Date::createFromDate(1975, 12)->addMonths(0)->month);
        $this->assertSame('2012-01-29', Date::createFromDate(2012, 2, 29)->addMonthsNoOverflow(-1)->toDateString());
        $this->assertSame('2012-01-31', Date::createFromDate(2012, 3, 31)->addMonthsNoOverflow(-2)->toDateString());
        $this->assertSame('2012-02-29', Date::createFromDate(2012, 3, 31)->addMonthsNoOverflow(-1)->toDateString());
        $this->assertSame('2011-12-31', Date::createFromDate(2012, 1, 31)->addMonthsNoOverflow(-1)->toDateString());

        $this->assertSame(1, Date::createFromDate(1975, 5, 31)->addDays(1)->day);
        $this->assertSame(31, Date::createFromDate(1975, 5, 31)->addDays(0)->day);
        $this->assertSame(30, Date::createFromDate(1975, 5, 31)->addDays(-1)->day);
        $this->assertSame(1, Date::createFromDate(1975, 5, 31)->addDay()->day);

        $this->assertSame(17, Date::createFromDate(2012, 1, 4)->addWeekdays(9)->day);
        $this->assertSame(4, Date::createFromDate(2012, 1, 4)->addWeekdays(0)->day);
        $this->assertSame(18, Date::createFromDate(2012, 1, 31)->addWeekdays(-9)->day);

        $this->assertSame(28, Date::createFromDate(1975, 5, 21)->addWeeks(1)->day);
        $this->assertSame(21, Date::createFromDate(1975, 5, 21)->addWeeks(0)->day);
        $this->assertSame(14, Date::createFromDate(1975, 5, 21)->addWeeks(-1)->day);
        $this->assertSame(28, Date::createFromDate(1975, 5, 21)->addWeek()->day);

        $this->assertSame(1, Date::createFromTime(0)->addHours(1)->hour);
        $this->assertSame(0, Date::createFromTime(0)->addHours(0)->hour);
        $this->assertSame(23, Date::createFromTime(0)->addHours(-1)->hour);
        $this->assertSame(1, Date::createFromTime(0)->addHour()->hour);

        $this->assertSame(1, Date::createFromTime(0, 0)->addMinutes(1)->minute);
        $this->assertSame(0, Date::createFromTime(0, 0)->addMinutes(0)->minute);
        $this->assertSame(59, Date::createFromTime(0, 0)->addMinutes(-1)->minute);
        $this->assertSame(1, Date::createFromTime(0, 0)->addMinute()->minute);

        $this->assertSame(1, Date::createFromTime(0, 0, 0)->addSeconds(1)->second);
        $this->assertSame(0, Date::createFromTime(0, 0, 0)->addSeconds(0)->second);
        $this->assertSame(59, Date::createFromTime(0, 0, 0)->addSeconds(-1)->second);
        $this->assertSame(1, Date::createFromTime(0, 0, 0)->addSecond()->second);
    }

    /**
     * Test komparasi.
     *
     * @group system
     */
    public function testCompare()
    {
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->eq(Date::createFromDate(2000, 1, 1)));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->eq(Date::createFromDate(2000, 1, 2)));
        $this->assertTrue(Date::create(2000, 1, 1, 12, 0, 0, 'America/Toronto')->eq(Date::create(2000, 1, 1, 9, 0, 0, 'America/Vancouver')));
        $this->assertFalse(Date::createFromDate(2000, 1, 1, 'America/Toronto')->eq(Date::createFromDate(2000, 1, 1, 'America/Vancouver')));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->ne(Date::createFromDate(2000, 1, 2)));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->ne(Date::createFromDate(2000, 1, 1)));
        $this->assertTrue(Date::createFromDate(2000, 1, 1, 'America/Toronto')->ne(Date::createFromDate(2000, 1, 1, 'America/Vancouver')));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->gt(Date::createFromDate(1999, 12, 31)));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->gt(Date::createFromDate(2000, 1, 2)));

        $dt1 = Date::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $dt2 = Date::create(2000, 1, 1, 8, 59, 59, 'America/Vancouver');
        $this->assertTrue($dt1->gt($dt2));

        $dt1 = Date::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $dt2 = Date::create(2000, 1, 1, 9, 0, 1, 'America/Vancouver');
        $this->assertFalse($dt1->gt($dt2));

        $this->assertTrue(Date::createFromDate(2000, 1, 1)->gte(Date::createFromDate(1999, 12, 31)));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->gte(Date::createFromDate(2000, 1, 1)));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->lt(Date::createFromDate(2000, 1, 2)));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->lt(Date::createFromDate(1999, 12, 31)));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->lte(Date::createFromDate(2000, 1, 2)));
        $this->assertTrue(Date::createFromDate(2000, 1, 1)->lte(Date::createFromDate(2000, 1, 1)));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->lte(Date::createFromDate(1999, 12, 31)));
        $this->assertTrue(Date::createFromDate(2000, 1, 15)->between(Date::createFromDate(2000, 1, 1), Date::createFromDate(2000, 1, 31), true));
        $this->assertTrue(Date::createFromDate(2000, 1, 15)->between(Date::createFromDate(2000, 1, 1), Date::createFromDate(2000, 1, 31), false));
        $this->assertFalse(Date::createFromDate(1999, 12, 31)->between(Date::createFromDate(2000, 1, 1), Date::createFromDate(2000, 1, 31), true));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->between(Date::createFromDate(2000, 1, 1), Date::createFromDate(2000, 1, 31), false));
        $this->assertTrue(Date::createFromDate(2000, 1, 15)->between(Date::createFromDate(2000, 1, 31), Date::createFromDate(2000, 1, 1), true));
        $this->assertTrue(Date::createFromDate(2000, 1, 15)->between(Date::createFromDate(2000, 1, 31), Date::createFromDate(2000, 1, 1), false));
        $this->assertFalse(Date::createFromDate(1999, 12, 31)->between(Date::createFromDate(2000, 1, 31), Date::createFromDate(2000, 1, 1), true));
        $this->assertFalse(Date::createFromDate(2000, 1, 1)->between(Date::createFromDate(2000, 1, 31), Date::createFromDate(2000, 1, 1), false));

        $this->assertTrue((Date::now())->min() instanceof Date);
        $this->assertTrue(Date::create(2012, 1, 1, 0, 0, 0)->min() instanceof Date);
        $dt1 = Date::create(2013, 12, 31, 23, 59, 59);
        $dt2 = Date::create(2012, 1, 1, 0, 0, 0)->min($dt1);
        $this->assertTrue($dt2 instanceof Date);
        $this->assertTrue((Date::now())->max() instanceof Date);
        $this->assertTrue(Date::create(2099, 12, 31, 23, 59, 59)->max() instanceof Date);

        $dt1 = Date::create(2012, 1, 1, 0, 0, 0);
        $dt2 = Date::create(2099, 12, 31, 23, 59, 59)->max($dt1);
        $this->assertTrue($dt2 instanceof Date);

        $dt1 = Date::createFromDate(1987, 4, 23);
        $dt2 = Date::createFromDate(2014, 9, 26);
        $dt3 = Date::createFromDate(2014, 4, 23);
        $this->assertFalse($dt2->isBirthday($dt1));
        $this->assertTrue($dt3->isBirthday($dt1));
    }

    /**
     * Test konstruktor.
     *
     * @group system
     */
    public function testConstruct()
    {
        $dt = new Date();
        $now = Date::now();
        $p = Date::parse();
        $o = new Date('first day of January 2008');
        $ps = Date::parse('first day of January 2008');
        $this->assertTrue($dt instanceof Date);
        $this->assertTrue($now instanceof Date);
        $this->assertSame($now->tzName, $dt->tzName);
        $this->assertSame($now->tzName, $p->tzName);
        $this->assertSame(Config::get('application.timezone'), (new Date('now'))->tzName);

        $tz = 'Europe/London';
        $dst = (new \DateTime('now', new \DateTimeZone($tz)))->format('I');
        $dst2 = new Date('now', new \DateTimeZone($tz));
        $this->assertSame($tz, $dst2->tzName);
        $this->assertSame(0 + $dst, $dst2->offsetHours);

        $tz = 'Asia/Tokyo';
        $dst = (new \DateTime('now', new \DateTimeZone($tz)))->format('I');
        $dst2 = Date::parse('now', $tz);
        $this->assertSame($tz, $dst2->tzName);
        $this->assertSame(9 + $dst, $dst2->offsetHours);
    }

    /**
     * Test copy.
     *
     * @group system
     */
    public function testCopy()
    {
        $dt = Date::now();
        $dt2 = $dt->copy();
        $this->assertNotSame($dt, $dt2);

        $dt = Date::createFromDate(2000, 1, 1, 'Europe/London');
        $dt2 = $dt->copy();
        $this->assertSame($dt->tzName, $dt2->tzName);
        $this->assertSame($dt->offset, $dt2->offset);

        $micro = 254687;
        $dt = Date::createFromFormat('Y-m-d H:i:s.u', '2014-02-01 03:45:27.' . $micro);
        $dt2 = $dt->copy();
        $this->assertSame($micro, $dt2->micro);
    }

    /**
     * Test createFromDate.
     *
     * @group system
     */
    public function testCreateFromDate()
    {
        $dt = Date::createFromDate();
        $this->assertSame($dt->timestamp, Date::create(null, null, null, null, null, null)->timestamp);

        $dt = Date::createFromDate(1975, 5, 21);
        $this->assertTrue($dt instanceof Date);

        $dt = Date::createFromDate(1975);
        $this->assertSame(1975, $dt->year);

        $dt = Date::createFromDate(null, 5);
        $this->assertSame(5, $dt->month);

        $dt = Date::createFromDate(null, null, 21);
        $this->assertSame(21, $dt->day);

        $dt = Date::createFromDate(1975, 5, 21, 'Europe/London');
        $this->assertTrue($dt instanceof Date);
        $this->assertSame('Europe/London', $dt->tzName);

        $dt = Date::createFromDate(1975, 5, 21, new \DateTimeZone('Europe/London'));
        $this->assertTrue($dt instanceof Date);
        $this->assertSame('Europe/London', $dt->tzName);
    }

    /**
     * Test createFromFormat.
     *
     * @group system
     */
    public function testCreateFromFormat()
    {
        $dt = Date::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11');
        $this->assertTrue($dt instanceof Date);

        $dt = Date::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11', 'Europe/London');
        $this->assertSame('Europe/London', $dt->tzName);

        $dt = Date::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11', new \DateTimeZone('Europe/London'));
        $this->assertSame('Europe/London', $dt->tzName);

        $dt = Date::createFromFormat('Y-m-d H:i:s.u', '1975-05-21 22:32:11.254687');
        $this->assertSame(254687, $dt->micro);
    }

    /**
     * Test createFromTime.
     *
     * @group system
     */
    public function testCreateFromTime()
    {
        $dt = Date::createFromTime();
        $this->assertSame($dt->timestamp, Date::create(null, null, null, null, null, null)->timestamp);

        $dt = Date::createFromTime(23, 5, 21);
        $this->assertTrue($dt instanceof Date);

        $dt = Date::createFromTime(22);
        $this->assertSame(22, $dt->hour);
        $this->assertSame(0, $dt->minute);
        $this->assertSame(0, $dt->second);

        $dt = Date::createFromTime(null, 5);
        $this->assertSame(5, $dt->minute);

        $dt = Date::createFromTime(null, null, 21);
        $this->assertSame(21, $dt->second);

        $dt = Date::createFromTime(12, 0, 0, new \DateTimeZone('Europe/London'));
        $this->assertSame('Europe/London', $dt->tzName);

        $dt = Date::createFromTime(12, 0, 0, 'Europe/London');
        $this->assertSame('Europe/London', $dt->tzName);
    }

    /**
     * Test createFromTimestamp.
     *
     * @group system
     */
    public function testCreateFromTimestamp()
    {
        $dt = Date::createFromTimestamp(Date::create(1975, 5, 21, 22, 32, 5)->timestamp);
        $this->assertTrue($dt instanceof Date);

        $dt = Date::createFromTimestamp(0, new \DateTimeZone('UTC'));
        $this->assertSame('UTC', $dt->tzName);

        $dt = Date::createFromTimestamp(0, 'UTC');
        $this->assertSame(0, $dt->offset);
        $this->assertSame('UTC', $dt->tzName);

        $dt = Date::createFromTimestampUTC(0);
        $this->assertSame(0, $dt->offset);
    }

    /**
     * Test create instance.
     *
     * @group system
     */
    public function testCreateInstance()
    {
        $this->assertTrue(Date::create() instanceof Date);
        $this->assertSame(Date::create()->timestamp, Date::now()->timestamp);
        $this->assertSame(2012, Date::create(2012)->year);
        $this->assertSame(3, Date::create(null, 3)->month);
        $this->assertSame(21, Date::create(null, null, 21)->day);

        $dt = Date::create(null, null, null, 14);
        $this->assertSame(14, $dt->hour);
        $this->assertSame(0, $dt->minute);
        $this->assertSame(0, $dt->second);
        $this->assertSame(58, Date::create(null, null, null, null, 58)->minute);
        $this->assertSame(59, Date::create(null, null, null, null, null, 59)->second);
        $this->assertSame('Europe/London', Date::create(2012, 1, 1, 0, 0, 0, new \DateTimeZone('Europe/London'))->tzName);
    }

    /**
     * Test dayOfWeek.
     *
     * @group system
     */
    public function testDayOfWeek()
    {
        $this->assertTrue(Date::create(1980, 8, 7, 12, 11, 9)->startOfWeek() instanceof Date);
        $this->assertFalse(Date::createFromDate(1975, 12, 5)->nthOfMonth(6, Date::MONDAY));
        $this->assertFalse(Date::createFromDate(1975, 12, 5)->nthOfMonth(55, Date::MONDAY));
        $this->assertFalse(Date::createFromDate(1975, 1, 5)->nthOfQuarter(20, Date::MONDAY));
        $this->assertFalse(Date::createFromDate(1975, 1, 5)->nthOfQuarter(55, Date::MONDAY));
    }

    // TODO: selesaikan test unit test ini.
}
