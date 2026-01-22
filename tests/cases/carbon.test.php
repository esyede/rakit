<?php

defined('DS') or exit('No direct access.');

use System\Carbon;
use System\Config;

class CarbonTest extends \PHPUnit_Framework_TestCase
{
    private $lang;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->lang = Config::get('application.language');
        Config::set('application.language', 'en');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('application.language', $this->lang);
    }

    /**
     * Test tambah.
     *
     * @group system
     */
    public function testAdd()
    {
        $this->assertSame(1976, Carbon::createFromDate(1975)->addYears(1)->year);
        $this->assertSame(1975, Carbon::createFromDate(1975)->addYears(0)->year);
        $this->assertSame(1974, Carbon::createFromDate(1975)->addYears(-1)->year);
        $this->assertSame(1976, Carbon::createFromDate(1975)->addYear()->year);
        $this->assertSame(1, Carbon::createFromDate(1975, 12)->addMonths(1)->month);
        $this->assertSame(12, Carbon::createFromDate(1975, 12)->addMonths(0)->month);
        $this->assertSame(11, Carbon::createFromDate(1975, 12, 1)->addMonths(-1)->month);
        $this->assertSame(1, Carbon::createFromDate(1975, 12)->addMonth()->month);
        $this->assertSame(3, Carbon::createFromDate(2012, 1, 31)->addMonth()->month);
        $this->assertSame('2012-02-29', Carbon::createFromDate(2012, 1, 31)->addMonthNoOverflow()->toDateString());
        $this->assertSame('2012-03-31', Carbon::createFromDate(2012, 1, 31)->addMonthsNoOverflow(2)->toDateString());
        $this->assertSame('2012-03-29', Carbon::createFromDate(2012, 2, 29)->addMonthNoOverflow()->toDateString());
        $this->assertSame('2012-02-29', Carbon::createFromDate(2011, 12, 31)->addMonthsNoOverflow(2)->toDateString());
        $this->assertSame(12, Carbon::createFromDate(1975, 12)->addMonths(0)->month);
        $this->assertSame('2012-01-29', Carbon::createFromDate(2012, 2, 29)->addMonthsNoOverflow(-1)->toDateString());
        $this->assertSame('2012-01-31', Carbon::createFromDate(2012, 3, 31)->addMonthsNoOverflow(-2)->toDateString());
        $this->assertSame('2012-02-29', Carbon::createFromDate(2012, 3, 31)->addMonthsNoOverflow(-1)->toDateString());
        $this->assertSame('2011-12-31', Carbon::createFromDate(2012, 1, 31)->addMonthsNoOverflow(-1)->toDateString());
        $this->assertSame(1, Carbon::createFromDate(1975, 5, 31)->addDays(1)->day);
        $this->assertSame(31, Carbon::createFromDate(1975, 5, 31)->addDays(0)->day);
        $this->assertSame(30, Carbon::createFromDate(1975, 5, 31)->addDays(-1)->day);
        $this->assertSame(1, Carbon::createFromDate(1975, 5, 31)->addDay()->day);
        $this->assertSame(17, Carbon::createFromDate(2012, 1, 4)->addWeekdays(9)->day);
        $this->assertSame(4, Carbon::createFromDate(2012, 1, 4)->addWeekdays(0)->day);
        $this->assertSame(18, Carbon::createFromDate(2012, 1, 31)->addWeekdays(-9)->day);
        $this->assertSame(28, Carbon::createFromDate(1975, 5, 21)->addWeeks(1)->day);
        $this->assertSame(21, Carbon::createFromDate(1975, 5, 21)->addWeeks(0)->day);
        $this->assertSame(14, Carbon::createFromDate(1975, 5, 21)->addWeeks(-1)->day);
        $this->assertSame(28, Carbon::createFromDate(1975, 5, 21)->addWeek()->day);
        $this->assertSame(1, Carbon::createFromTime(0)->addHours(1)->hour);
        $this->assertSame(0, Carbon::createFromTime(0)->addHours(0)->hour);
        $this->assertSame(23, Carbon::createFromTime(0)->addHours(-1)->hour);
        $this->assertSame(1, Carbon::createFromTime(0)->addHour()->hour);
        $this->assertSame(1, Carbon::createFromTime(0, 0)->addMinutes(1)->minute);
        $this->assertSame(0, Carbon::createFromTime(0, 0)->addMinutes(0)->minute);
        $this->assertSame(59, Carbon::createFromTime(0, 0)->addMinutes(-1)->minute);
        $this->assertSame(1, Carbon::createFromTime(0, 0)->addMinute()->minute);
        $this->assertSame(1, Carbon::createFromTime(0, 0, 0)->addSeconds(1)->second);
        $this->assertSame(0, Carbon::createFromTime(0, 0, 0)->addSeconds(0)->second);
        $this->assertSame(59, Carbon::createFromTime(0, 0, 0)->addSeconds(-1)->second);
        $this->assertSame(1, Carbon::createFromTime(0, 0, 0)->addSecond()->second);
    }

    /**
     * Test kurang.
     *
     * @group system
     */
    public function testSubYearsPositive()
    {
        $this->assertSame(1974, Carbon::createFromDate(1975)->subYears(1)->year);
        $this->assertSame(1975, Carbon::createFromDate(1975)->subYears(0)->year);
        $this->assertSame(1976, Carbon::createFromDate(1975)->subYears(-1)->year);
        $this->assertSame(1974, Carbon::createFromDate(1975)->subYear()->year);
        $this->assertSame(12, Carbon::createFromDate(1975, 1, 1)->subMonths(1)->month);
        $this->assertSame(1, Carbon::createFromDate(1975, 1, 1)->subMonths(0)->month);
        $this->assertSame(2, Carbon::createFromDate(1975, 1, 1)->subMonths(-1)->month);
        $this->assertSame(12, Carbon::createFromDate(1975, 1, 1)->subMonth()->month);
        $this->assertSame(30, Carbon::createFromDate(1975, 5, 1)->subDays(1)->day);
        $this->assertSame(1, Carbon::createFromDate(1975, 5, 1)->subDays(0)->day);
        $this->assertSame(2, Carbon::createFromDate(1975, 5, 1)->subDays(-1)->day);
        $this->assertSame(30, Carbon::createFromDate(1975, 5, 1)->subDay()->day);
        $this->assertSame(22, Carbon::createFromDate(2012, 1, 4)->subWeekdays(9)->day);
        $this->assertSame(4, Carbon::createFromDate(2012, 1, 4)->subWeekdays(0)->day);
        $this->assertSame(13, Carbon::createFromDate(2012, 1, 31)->subWeekdays(-9)->day);
        $this->assertSame(6, Carbon::createFromDate(2012, 1, 9)->subWeekday()->day);
        $this->assertSame(14, Carbon::createFromDate(1975, 5, 21)->subWeeks(1)->day);
        $this->assertSame(21, Carbon::createFromDate(1975, 5, 21)->subWeeks(0)->day);
        $this->assertSame(28, Carbon::createFromDate(1975, 5, 21)->subWeeks(-1)->day);
        $this->assertSame(14, Carbon::createFromDate(1975, 5, 21)->subWeek()->day);
        $this->assertSame(23, Carbon::createFromTime(0)->subHours(1)->hour);
        $this->assertSame(0, Carbon::createFromTime(0)->subHours(0)->hour);
        $this->assertSame(1, Carbon::createFromTime(0)->subHours(-1)->hour);
        $this->assertSame(23, Carbon::createFromTime(0)->subHour()->hour);
        $this->assertSame(59, Carbon::createFromTime(0, 0)->subMinutes(1)->minute);
        $this->assertSame(0, Carbon::createFromTime(0, 0)->subMinutes(0)->minute);
        $this->assertSame(1, Carbon::createFromTime(0, 0)->subMinutes(-1)->minute);
        $this->assertSame(59, Carbon::createFromTime(0, 0)->subMinute()->minute);
        $this->assertSame(59, Carbon::createFromTime(0, 0, 0)->subSeconds(1)->second);
        $this->assertSame(0, Carbon::createFromTime(0, 0, 0)->subSeconds(0)->second);
        $this->assertSame(1, Carbon::createFromTime(0, 0, 0)->subSeconds(-1)->second);
        $this->assertSame(59, Carbon::createFromTime(0, 0, 0)->subSecond()->second);
    }

    /**
     * Test komparasi.
     *
     * @group system
     */
    public function testCompare()
    {
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->eq(Carbon::createFromDate(2000, 1, 1)));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->eq(Carbon::createFromDate(2000, 1, 2)));
        $this->assertTrue(Carbon::create(2000, 1, 1, 12, 0, 0, 'America/Toronto')->eq(Carbon::create(2000, 1, 1, 9, 0, 0, 'America/Vancouver')));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1, 'America/Toronto')->eq(Carbon::createFromDate(2000, 1, 1, 'America/Vancouver')));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->ne(Carbon::createFromDate(2000, 1, 2)));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->ne(Carbon::createFromDate(2000, 1, 1)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1, 'America/Toronto')->ne(Carbon::createFromDate(2000, 1, 1, 'America/Vancouver')));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->gt(Carbon::createFromDate(1999, 12, 31)));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->gt(Carbon::createFromDate(2000, 1, 2)));
        $d1 = Carbon::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $d2 = Carbon::create(2000, 1, 1, 8, 59, 59, 'America/Vancouver');
        $this->assertTrue($d1->gt($d2));
        $d1 = Carbon::create(2000, 1, 1, 12, 0, 0, 'America/Toronto');
        $d2 = Carbon::create(2000, 1, 1, 9, 0, 1, 'America/Vancouver');
        $this->assertFalse($d1->gt($d2));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->gte(Carbon::createFromDate(1999, 12, 31)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->gte(Carbon::createFromDate(2000, 1, 1)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->lt(Carbon::createFromDate(2000, 1, 2)));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->lt(Carbon::createFromDate(1999, 12, 31)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->lte(Carbon::createFromDate(2000, 1, 2)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 1)->lte(Carbon::createFromDate(2000, 1, 1)));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->lte(Carbon::createFromDate(1999, 12, 31)));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 15)->between(Carbon::createFromDate(2000, 1, 1), Carbon::createFromDate(2000, 1, 31), true));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 15)->between(Carbon::createFromDate(2000, 1, 1), Carbon::createFromDate(2000, 1, 31), false));
        $this->assertFalse(Carbon::createFromDate(1999, 12, 31)->between(Carbon::createFromDate(2000, 1, 1), Carbon::createFromDate(2000, 1, 31), true));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->between(Carbon::createFromDate(2000, 1, 1), Carbon::createFromDate(2000, 1, 31), false));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 15)->between(Carbon::createFromDate(2000, 1, 31), Carbon::createFromDate(2000, 1, 1), true));
        $this->assertTrue(Carbon::createFromDate(2000, 1, 15)->between(Carbon::createFromDate(2000, 1, 31), Carbon::createFromDate(2000, 1, 1), false));
        $this->assertFalse(Carbon::createFromDate(1999, 12, 31)->between(Carbon::createFromDate(2000, 1, 31), Carbon::createFromDate(2000, 1, 1), true));
        $this->assertFalse(Carbon::createFromDate(2000, 1, 1)->between(Carbon::createFromDate(2000, 1, 31), Carbon::createFromDate(2000, 1, 1), false));
        $this->assertTrue(Carbon::now()->min() instanceof Carbon);
        $this->assertTrue(Carbon::create(2012, 1, 1, 0, 0, 0)->min() instanceof Carbon);
        $d1 = Carbon::create(2013, 12, 31, 23, 59, 59);
        $d2 = Carbon::create(2012, 1, 1, 0, 0, 0)->min($d1);
        $this->assertTrue($d2 instanceof Carbon);
        $this->assertTrue(Carbon::now()->max() instanceof Carbon);
        $this->assertTrue(Carbon::create(2099, 12, 31, 23, 59, 59)->max() instanceof Carbon);
        $d1 = Carbon::create(2012, 1, 1, 0, 0, 0);
        $d2 = Carbon::create(2099, 12, 31, 23, 59, 59)->max($d1);
        $this->assertTrue($d2 instanceof Carbon);
        $d1 = Carbon::createFromDate(1987, 4, 23);
        $d2 = Carbon::createFromDate(2014, 9, 26);
        $d3 = Carbon::createFromDate(2014, 4, 23);
        $this->assertFalse($d2->isBirthday($d1));
        $this->assertTrue($d3->isBirthday($d1));
    }

    /**
     * Test konstruktor.
     *
     * @group system
     */
    public function testConstruct()
    {
        $d = new Carbon();
        $now = Carbon::now();
        $p = Carbon::parse();
        $o = new Carbon('first day of January 2008');
        $ps = Carbon::parse('first day of January 2008');
        $this->assertTrue($d instanceof Carbon);
        $this->assertTrue($now instanceof Carbon);
        $this->assertSame($now->tzName, $d->tzName);
        $this->assertSame($now->tzName, $p->tzName);
        $this->assertSame(Config::get('application.timezone'), (new Carbon('now'))->tzName);
        $tz = 'Europe/London';
        $dst = (new \DateTime('now', new \DateTimeZone($tz)))->format('I');
        $dst2 = new Carbon('now', new \DateTimeZone($tz));
        $this->assertSame($tz, $dst2->tzName);
        $this->assertSame(0 + $dst, $dst2->offsetHours);
        $tz = 'Asia/Tokyo';
        $dst = (new \DateTime('now', new \DateTimeZone($tz)))->format('I');
        $dst2 = Carbon::parse('now', $tz);
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
        $d = Carbon::now();
        $d2 = $d->copy();
        $this->assertNotSame($d, $d2);
        $d = Carbon::createFromDate(2000, 1, 1, 'Europe/London');
        $d2 = $d->copy();
        $this->assertSame($d->tzName, $d2->tzName);
        $this->assertSame($d->offset, $d2->offset);
        $micro = 254687;
        $d = Carbon::createFromFormat('Y-m-d H:i:s.u', '2014-02-01 03:45:27.' . $micro);
        $d2 = $d->copy();
        $this->assertSame($micro, $d2->micro);
    }

    /**
     * Test createFromDate.
     *
     * @group system
     */
    public function testCreateFromDate()
    {
        $d = Carbon::createFromDate();
        $this->assertSame($d->timestamp, Carbon::create(null, null, null, null, null, null)->timestamp);
        $d = Carbon::createFromDate(1975, 5, 21);
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::createFromDate(1975);
        $this->assertSame(1975, $d->year);
        $d = Carbon::createFromDate(null, 5);
        $this->assertSame(5, $d->month);
        $d = Carbon::createFromDate(null, null, 21);
        $this->assertSame(21, $d->day);
        $d = Carbon::createFromDate(1975, 5, 21, 'Europe/London');
        $this->assertTrue($d instanceof Carbon);
        $this->assertSame('Europe/London', $d->tzName);
        $d = Carbon::createFromDate(1975, 5, 21, new \DateTimeZone('Europe/London'));
        $this->assertTrue($d instanceof Carbon);
        $this->assertSame('Europe/London', $d->tzName);
    }

    /**
     * Test createFromFormat.
     *
     * @group system
     */
    public function testCreateFromFormat()
    {
        $d = Carbon::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11');
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11', 'Europe/London');
        $this->assertSame('Europe/London', $d->tzName);
        $d = Carbon::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11', new \DateTimeZone('Europe/London'));
        $this->assertSame('Europe/London', $d->tzName);
        $d = Carbon::createFromFormat('Y-m-d H:i:s.u', '1975-05-21 22:32:11.254687');
        $this->assertSame(254687, $d->micro);
    }

    /**
     * Test createFromTime.
     *
     * @group system
     */
    public function testCreateFromTime()
    {
        $d = Carbon::createFromTime();
        $this->assertSame($d->timestamp, Carbon::create(null, null, null, null, null, null)->timestamp);
        $d = Carbon::createFromTime(23, 5, 21);
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::createFromTime(22);
        $this->assertSame(22, $d->hour);
        $this->assertSame(0, $d->minute);
        $this->assertSame(0, $d->second);
        $d = Carbon::createFromTime(null, 5);
        $this->assertSame(5, $d->minute);
        $d = Carbon::createFromTime(null, null, 21);
        $this->assertSame(21, $d->second);
        $d = Carbon::createFromTime(12, 0, 0, new \DateTimeZone('Europe/London'));
        $this->assertSame('Europe/London', $d->tzName);
        $d = Carbon::createFromTime(12, 0, 0, 'Europe/London');
        $this->assertSame('Europe/London', $d->tzName);
    }

    /**
     * Test createFromTimestamp.
     *
     * @group system
     */
    public function testCreateFromTimestamp()
    {
        $d = Carbon::createFromTimestamp(Carbon::create(1975, 5, 21, 22, 32, 5)->timestamp);
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::createFromTimestamp(0, new \DateTimeZone('UTC'));
        $this->assertSame('UTC', $d->tzName);
        $d = Carbon::createFromTimestamp(0, 'UTC');
        $this->assertSame(0, $d->offset);
        $this->assertSame('UTC', $d->tzName);
        $d = Carbon::createFromTimestampUTC(0);
        $this->assertSame(0, $d->offset);
    }

    /**
     * Test buat instance.
     *
     * @group system
     */
    public function testCreateInstance()
    {
        $this->assertTrue(Carbon::create() instanceof Carbon);
        $this->assertSame(Carbon::create()->timestamp, Carbon::now()->timestamp);
        $this->assertSame(2012, Carbon::create(2012)->year);
        $this->assertSame(3, Carbon::create(null, 3)->month);
        $this->assertSame(21, Carbon::create(null, null, 21)->day);
        $d = Carbon::create(null, null, null, 14);
        $this->assertSame(14, $d->hour);
        $this->assertSame(0, $d->minute);
        $this->assertSame(0, $d->second);
        $this->assertSame(58, Carbon::create(null, null, null, null, 58)->minute);
        $this->assertSame(59, Carbon::create(null, null, null, null, null, 59)->second);
        $this->assertSame('Europe/London', Carbon::create(2012, 1, 1, 0, 0, 0, new \DateTimeZone('Europe/London'))->tzName);
    }

    /**
     * Test dayOfWeek.
     *
     * @group system
     */
    public function testDayOfWeek()
    {
        $this->assertTrue(Carbon::create(1980, 8, 7, 12, 11, 9)->startOfWeek() instanceof Carbon);
        $this->assertFalse(Carbon::createFromDate(1975, 12, 5)->nthOfMonth(6, Carbon::MONDAY));
        $this->assertFalse(Carbon::createFromDate(1975, 12, 5)->nthOfMonth(55, Carbon::MONDAY));
        $this->assertFalse(Carbon::createFromDate(1975, 1, 5)->nthOfQuarter(20, Carbon::MONDAY));
        $this->assertFalse(Carbon::createFromDate(1975, 1, 5)->nthOfQuarter(55, Carbon::MONDAY));
    }

    /**
     * Test diff*.
     *
     * @group system
     */
    public function testDiff()
    {
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInYears($d->copy()->addYear()));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-1, $d->diffInYears($d->copy()->subYear(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInYears($d->copy()->subYear()));
        $this->assertSame(1, Carbon::now()->subYear()->diffInYears());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInYears($d->copy()->addYear()->addMonths(7)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(13, $d->diffInMonths($d->copy()->addYear()->addMonth()));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-11, $d->diffInMonths($d->copy()->subYear()->addMonth(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(11, $d->diffInMonths($d->copy()->subYear()->addMonth()));
        $this->assertSame(12, Carbon::now()->subYear()->diffInMonths());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInMonths($d->copy()->addMonth()->addDays(16)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(366, $d->diffInDays($d->copy()->addYear()));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-365, $d->diffInDays($d->copy()->subYear(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(365, $d->diffInDays($d->copy()->subYear()));
        $this->assertSame(7, Carbon::now()->subWeek()->diffInDays());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInDays($d->copy()->addDay()->addHours(13)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(5, $d->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === 1;
        }, $d->copy()->endOfMonth()));
        $d1 = Carbon::createFromDate(2000, 1, 1);
        $d2 = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(5, $d1->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === Carbon::SUNDAY;
        }, $d2));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(5, $d->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === Carbon::SUNDAY;
        }, $d->copy()->startOfMonth()));
        $d1 = Carbon::createFromDate(2000, 1, 31);
        $d2 = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(5, $d1->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === Carbon::SUNDAY;
        }, $d2));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(-5, $d->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === 1;
        }, $d->copy()->startOfMonth(), false));
        $d1 = Carbon::createFromDate(2000, 1, 31);
        $d2 = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-5, $d1->diffInDaysFiltered(function (Carbon $date) {
            return $date->dayOfWeek === Carbon::SUNDAY;
        }, $d2, false));
        $start = Carbon::create(2014, 10, 8, 15, 20, 0);
        $end = $start->copy();
        $this->assertSame(0, $start->diffInDays($end));
        $this->assertSame(0, $start->diffInWeekdays($end));
        $start = Carbon::create(2014, 10, 8, 15, 20, 0);
        $end = $start->copy();
        $this->assertSame(0, $start->diffInDays($end));
        $this->assertSame(0, $start->diffInWeekdays($end));
        $start = Carbon::create(2014, 10, 8, 15, 20, 0);
        $end = $start->copy()->addDay();
        $this->assertSame(1, $start->diffInDays($end));
        $this->assertSame(1, $start->diffInWeekdays($end));
        $start = Carbon::create(2014, 1, 1, 0, 0, 0);
        $start->next(Carbon::SATURDAY);
        $end = $start->copy()->addDay();
        $this->assertSame(1, $start->diffInDays($end));
        $this->assertSame(0, $start->diffInWeekdays($end));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(21, $d->diffInWeekdays($d->copy()->endOfMonth()));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(21, $d->diffInWeekdays($d->copy()->startOfMonth()));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(-21, $d->diffInWeekdays($d->copy()->startOfMonth(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(10, $d->diffInWeekendDays($d->copy()->endOfMonth()));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(10, $d->diffInWeekendDays($d->copy()->startOfMonth()));
        $d = Carbon::createFromDate(2000, 1, 31);
        $this->assertSame(-10, $d->diffInWeekendDays($d->copy()->startOfMonth(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(52, $d->diffInWeeks($d->copy()->addYear()));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-52, $d->diffInWeeks($d->copy()->subYear(), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(52, $d->diffInWeeks($d->copy()->subYear()));
        $this->assertSame(1, Carbon::now()->subWeek()->diffInWeeks());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(0, $d->diffInWeeks($d->copy()->addWeek()->subDay()));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(26, $d->diffInHours($d->copy()->addDay()->addHours(2)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-22, $d->diffInHours($d->copy()->subDay()->addHours(2), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(22, $d->diffInHours($d->copy()->subDay()->addHours(2)));
        Carbon::setNow(Carbon::create(2012, 1, 15));
        $this->assertSame(48, Carbon::now()->subDays(2)->diffInHours());
        Carbon::setNow();
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInHours($d->copy()->addHour()->addMinutes(31)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(62, $d->diffInMinutes($d->copy()->addHour()->addMinutes(2)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1502, $d->diffInMinutes($d->copy()->addHours(25)->addMinutes(2)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-58, $d->diffInMinutes($d->copy()->subHour()->addMinutes(2), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(58, $d->diffInMinutes($d->copy()->subHour()->addMinutes(2)));
        $this->assertSame(60, Carbon::now()->subHour()->diffInMinutes());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInMinutes($d->copy()->addMinute()->addSeconds(31)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(62, $d->diffInSeconds($d->copy()->addMinute()->addSeconds(2)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(7202, $d->diffInSeconds($d->copy()->addHours(2)->addSeconds(2)));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(-58, $d->diffInSeconds($d->copy()->subMinute()->addSeconds(2), false));
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(58, $d->diffInSeconds($d->copy()->subMinute()->addSeconds(2)));
        $this->assertSame(3600, Carbon::now()->subHour()->diffInSeconds());
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertSame(1, $d->diffInSeconds($d->copy()->addSeconds(1.9)));
        $o = Carbon::createFromDate(2000, 1, 1, 'America/Toronto');
        $v = Carbon::createFromDate(2000, 1, 1, 'America/Vancouver');
        $this->assertSame(3 * 60 * 60, $o->diffInSeconds($v));
        $d = Carbon::now('America/Vancouver');
        $this->assertSame(0, $d->diffInSeconds());
        $d = Carbon::now();
        $this->assertSame('1 second ago', $d->diffForHumans());
        $d = Carbon::now('America/Vancouver');
        $this->assertSame('1 second ago', $d->diffForHumans());
        $d = Carbon::now()->subSeconds(2);
        $this->assertSame('2 seconds ago', $d->diffForHumans());
        $d = Carbon::now()->subSeconds(59);
        $this->assertSame('59 seconds ago', $d->diffForHumans());
        $d = Carbon::now()->subMinute();
        $this->assertSame('1 minute ago', $d->diffForHumans());
        $d = Carbon::now()->subMinutes(2);
        $this->assertSame('2 minutes ago', $d->diffForHumans());
        $d = Carbon::now()->subMinutes(59);
        $this->assertSame('59 minutes ago', $d->diffForHumans());
        $d = Carbon::now()->subHour();
        $this->assertSame('1 hour ago', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 15));
        $d = Carbon::now()->subHours(2);
        $this->assertSame('2 hours ago', $d->diffForHumans());
        Carbon::setNow();
        Carbon::setNow(Carbon::create(2012, 1, 15));
        $d = Carbon::now()->subHours(23);
        $this->assertSame('23 hours ago', $d->diffForHumans());
        Carbon::setNow();
        $d = Carbon::now()->subDay();
        $this->assertSame('1 day ago', $d->diffForHumans());
        $d = Carbon::now()->subDays(2);
        $this->assertSame('2 days ago', $d->diffForHumans());
        $d = Carbon::now()->subDays(6);
        $this->assertSame('6 days ago', $d->diffForHumans());
        $d = Carbon::now()->subWeek();
        $this->assertSame('1 week ago', $d->diffForHumans());
        $d = Carbon::now()->subWeeks(2);
        $this->assertSame('2 weeks ago', $d->diffForHumans());
        $d = Carbon::now()->subWeeks(3);
        $this->assertSame('3 weeks ago', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->subWeeks(4);
        $this->assertSame('4 weeks ago', $d->diffForHumans());
        $d = Carbon::now()->subMonth();
        $this->assertSame('1 month ago', $d->diffForHumans());
        Carbon::setNow();
        $d = Carbon::now()->subMonths(2);
        $this->assertSame('2 months ago', $d->diffForHumans());
        $d = Carbon::now()->subMonths(11);
        $this->assertSame('11 months ago', $d->diffForHumans());
        $d = Carbon::now()->subYear();
        $this->assertSame('1 year ago', $d->diffForHumans());
        $d = Carbon::now()->subYears(2);
        $this->assertSame('2 years ago', $d->diffForHumans());
        $d = Carbon::now()->addSecond();
        $this->assertSame('1 second from now', $d->diffForHumans());
        $d = Carbon::now()->addSeconds(2);
        $this->assertSame('2 seconds from now', $d->diffForHumans());
        $d = Carbon::now()->addSeconds(59);
        $this->assertSame('59 seconds from now', $d->diffForHumans());
        $d = Carbon::now()->addMinute();
        $this->assertSame('1 minute from now', $d->diffForHumans());
        $d = Carbon::now()->addMinutes(2);
        $this->assertSame('2 minutes from now', $d->diffForHumans());
        $d = Carbon::now()->addMinutes(59);
        $this->assertSame('59 minutes from now', $d->diffForHumans());
        $d = Carbon::now()->addHour();
        $this->assertSame('1 hour from now', $d->diffForHumans());
        $d = Carbon::now()->addHours(2);
        $this->assertSame('2 hours from now', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addHours(23);
        $this->assertSame('23 hours from now', $d->diffForHumans());
        Carbon::setNow();
        $d = Carbon::now()->addDay();
        $this->assertSame('1 day from now', $d->diffForHumans());
        $d = Carbon::now()->addDays(2);
        $this->assertSame('2 days from now', $d->diffForHumans());
        $d = Carbon::now()->addDays(6);
        $this->assertSame('6 days from now', $d->diffForHumans());
        $d = Carbon::now()->addWeek();
        $this->assertSame('1 week from now', $d->diffForHumans());
        $d = Carbon::now()->addWeeks(2);
        $this->assertSame('2 weeks from now', $d->diffForHumans());
        $d = Carbon::now()->addWeeks(3);
        $this->assertSame('3 weeks from now', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addWeeks(4);
        $this->assertSame('4 weeks from now', $d->diffForHumans());
        $d = Carbon::now()->addMonth();
        $this->assertSame('1 month from now', $d->diffForHumans());
        Carbon::setNow();
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addMonths(2);
        $this->assertSame('2 months from now', $d->diffForHumans());
        Carbon::setNow();
        $d = Carbon::now()->addMonths(11);
        $this->assertSame('11 months from now', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addYear();
        $this->assertSame('1 year from now', $d->diffForHumans());
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addYears(2);
        $this->assertSame('2 years from now', $d->diffForHumans());
        Carbon::setNow();
        $d = Carbon::now()->addSecond();
        $this->assertSame('1 second before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addSeconds(2);
        $this->assertSame('2 seconds before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addSeconds(59);
        $this->assertSame('59 seconds before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addMinute();
        $this->assertSame('1 minute before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addMinutes(2);
        $this->assertSame('2 minutes before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addMinutes(59);
        $this->assertSame('59 minutes before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addHour();
        $this->assertSame('1 hour before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addHours(2);
        $this->assertSame('2 hours before', Carbon::now()->diffForHumans($d));
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addHours(23);
        $this->assertSame('23 hours before', Carbon::now()->diffForHumans($d));
        Carbon::setNow();
        $d = Carbon::now()->addDay();
        $this->assertSame('1 day before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addDays(2);
        $this->assertSame('2 days before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addDays(6);
        $this->assertSame('6 days before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addWeek();
        $this->assertSame('1 week before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addWeeks(2);
        $this->assertSame('2 weeks before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addWeeks(3);
        $this->assertSame('3 weeks before', Carbon::now()->diffForHumans($d));
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addWeeks(4);
        $this->assertSame('4 weeks before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addMonth();
        $this->assertSame('1 month before', Carbon::now()->diffForHumans($d));
        Carbon::setNow();
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->addMonths(2);
        $this->assertSame('2 months before', Carbon::now()->diffForHumans($d));
        Carbon::setNow();
        $d = Carbon::now()->addMonths(11);
        $this->assertSame('11 months before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addYear();
        $this->assertSame('1 year before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->addYears(2);
        $this->assertSame('2 years before', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subSecond();
        $this->assertSame('1 second after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subSeconds(2);
        $this->assertSame('2 seconds after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subSeconds(59);
        $this->assertSame('59 seconds after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subMinute();
        $this->assertSame('1 minute after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subMinutes(2);
        $this->assertSame('2 minutes after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subMinutes(59);
        $this->assertSame('59 minutes after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subHour();
        $this->assertSame('1 hour after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subHours(2);
        $this->assertSame('2 hours after', Carbon::now()->diffForHumans($d));
        Carbon::setNow(Carbon::create(2012, 1, 15));
        $d = Carbon::now()->subHours(23);
        $this->assertSame('23 hours after', Carbon::now()->diffForHumans($d));
        Carbon::setNow();
        $d = Carbon::now()->subDay();
        $this->assertSame('1 day after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subDays(2);
        $this->assertSame('2 days after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subDays(6);
        $this->assertSame('6 days after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subWeek();
        $this->assertSame('1 week after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subWeeks(2);
        $this->assertSame('2 weeks after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subWeeks(3);
        $this->assertSame('3 weeks after', Carbon::now()->diffForHumans($d));
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->subWeeks(4);
        $this->assertSame('4 weeks after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subMonth();
        $this->assertSame('1 month after', Carbon::now()->diffForHumans($d));
        Carbon::setNow();
        $d = Carbon::now()->subMonths(2);
        $this->assertSame('2 months after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subMonths(11);
        $this->assertSame('11 months after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subYear();
        $this->assertSame('1 year after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subYears(2);
        $this->assertSame('2 years after', Carbon::now()->diffForHumans($d));
        $d = Carbon::now()->subSeconds(59);
        $this->assertSame('59 seconds', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addSeconds(59);
        $this->assertSame('59 seconds', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->subMinutes(30);
        $this->assertSame('30 minutes', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addMinutes(30);
        $this->assertSame('30 minutes', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->subHours(3);
        $this->assertSame('3 hours', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addHours(3);
        $this->assertSame('3 hours', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->subDays(2);
        $this->assertSame('2 days', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addDays(2);
        $this->assertSame('2 days', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->subWeeks(2);
        $this->assertSame('2 weeks', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addWeeks(2);
        $this->assertSame('2 weeks', Carbon::now()->diffForHumans($d, true));
        Carbon::setNow(Carbon::create(2012, 1, 1));
        $d = Carbon::now()->subMonths(2);
        $this->assertSame('2 months', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addMonths(2);
        $this->assertSame('2 months', Carbon::now()->diffForHumans($d, true));
        Carbon::setNow();
        $d = Carbon::now()->subYears(1);
        $this->assertSame('1 year', Carbon::now()->diffForHumans($d, true));
        $d = Carbon::now()->addYears(1);
        $this->assertSame('1 year', Carbon::now()->diffForHumans($d, true));
        $feb15 = Carbon::parse('2015-02-15');
        $mar15 = Carbon::parse('2015-03-15');
        $this->assertSame('4 weeks after', $mar15->diffForHumans($feb15));
    }

    /**
     * Test setter dinamis.
     *
     * @group system
     */
    public function testDynamicSetter()
    {
        $d = Carbon::now();
        $this->assertTrue($d->year(1995) instanceof Carbon);
        $this->assertSame(1995, $d->year);
        $d = Carbon::now();
        $this->assertTrue($d->month(3) instanceof Carbon);
        $this->assertSame(3, $d->month);
        $d = Carbon::createFromDate(2012, 8, 21);
        $this->assertTrue($d->month(13) instanceof Carbon);
        $this->assertSame(1, $d->month);
        $d = Carbon::now();
        $this->assertTrue($d->day(2) instanceof Carbon);
        $this->assertSame(2, $d->day);
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertTrue($d->day(32) instanceof Carbon);
        $this->assertSame(1, $d->day);
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertTrue($d->setDate(1995, 13, 32) instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->hour(2) instanceof Carbon);
        $this->assertSame(2, $d->hour);
        $d = Carbon::now();
        $this->assertTrue($d->hour(25) instanceof Carbon);
        $this->assertSame(1, $d->hour);
        $d = Carbon::now();
        $this->assertTrue($d->minute(2) instanceof Carbon);
        $this->assertSame(2, $d->minute);
        $d = Carbon::now();
        $this->assertTrue($d->minute(61) instanceof Carbon);
        $this->assertSame(1, $d->minute);
        $d = Carbon::now();
        $this->assertTrue($d->second(2) instanceof Carbon);
        $this->assertSame(2, $d->second);
        $d = Carbon::now();
        $this->assertTrue($d->second(62) instanceof Carbon);
        $this->assertSame(2, $d->second);
        $d = Carbon::createFromDate(2000, 1, 1);
        $this->assertTrue($d->setTime(25, 61, 61) instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->timestamp(10) instanceof Carbon);
        $this->assertSame(10, $d->timestamp);
    }

    /**
     * Test getter.
     *
     * @group system
     */
    public function testGetter()
    {
        $this->assertFalse(isset(Carbon::create(1234, 5, 6, 7, 8, 9)->asdfghjkl));
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(1234, $d->year);
        $d = Carbon::createFromDate(2012, 12, 31);
        $this->assertSame(2013, $d->yearIso);
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(5, $d->month);
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(6, $d->day);
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(7, $d->hour);
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(8, $d->minute);
        $d = Carbon::create(1234, 5, 6, 7, 8, 9);
        $this->assertSame(9, $d->second);
        $micro = 345678;
        $d = Carbon::parse('2014-01-05 12:34:11.' . $micro);
        $this->assertSame($micro, $d->micro);
        $d = Carbon::create(2012, 5, 7, 7, 8, 9);
        $this->assertSame(Carbon::MONDAY, $d->dayOfWeek);
        $d = Carbon::createFromDate(2012, 5, 7);
        $this->assertSame(127, $d->dayOfYear);
        $d = Carbon::createFromDate(2012, 5, 7);
        $this->assertSame(31, $d->daysInMonth);
        $d = Carbon::create();
        $d->setTimezone('GMT');
        $this->assertSame(0, $d->setDateTime(1970, 1, 1, 0, 0, 0)->timestamp);
        $d = Carbon::now();
        $this->assertSame(0, $d->age);
        $d = Carbon::createFromDate(1975, 5, 21);
        $age = intval(substr(date('Ymd') - date('Ymd', $d->timestamp), 0, -4));
        $this->assertSame($age, $d->age);
        $d = Carbon::createFromDate(2012, 1, 1);
        $this->assertSame(1, $d->quarter);
        $d = Carbon::createFromDate(2012, 3, 31);
        $this->assertSame(1, $d->quarter);
        $d = Carbon::createFromDate(2012, 4, 1);
        $this->assertSame(2, $d->quarter);
        $d = Carbon::createFromDate(2012, 7, 1);
        $this->assertSame(3, $d->quarter);
        $d = Carbon::createFromDate(2012, 10, 1);
        $this->assertSame(4, $d->quarter);
        $d = Carbon::createFromDate(2012, 12, 31);
        $this->assertSame(4, $d->quarter);
        $this->assertFalse(Carbon::createFromDate(2013, 1, 1, 'America/Toronto')->utc);
        $this->assertFalse(Carbon::createFromDate(2013, 1, 1, 'Europe/Paris')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Atlantic/Reykjavik')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Europe/Lisbon')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Africa/Casablanca')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Africa/Dakar')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Europe/Dublin')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'Europe/London')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'UTC')->utc);
        $this->assertTrue(Carbon::createFromDate(2013, 1, 1, 'GMT')->utc);
        $this->assertFalse(Carbon::createFromDate(2012, 1, 1, 'America/Toronto')->dst);
        $this->assertTrue(Carbon::createFromDate(2012, 7, 1, 'America/Toronto')->dst);
        $this->assertSame(-18000, Carbon::createFromDate(2012, 1, 1, 'America/Toronto')->offset);
        $this->assertSame(-14400, Carbon::createFromDate(2012, 6, 1, 'America/Toronto')->offset);
        $this->assertSame(0, Carbon::createFromDate(2012, 6, 1, 'GMT')->offset);
        $this->assertSame(-5, Carbon::createFromDate(2012, 1, 1, 'America/Toronto')->offsetHours);
        $this->assertSame(-4, Carbon::createFromDate(2012, 6, 1, 'America/Toronto')->offsetHours);
        $this->assertSame(0, Carbon::createFromDate(2012, 6, 1, 'GMT')->offsetHours);
        $this->assertTrue(Carbon::createFromDate(2012, 1, 1)->isLeapYear());
        $this->assertFalse(Carbon::createFromDate(2011, 1, 1)->isLeapYear());
        $this->assertSame(5, Carbon::createFromDate(2012, 9, 30)->weekOfMonth);
        $this->assertSame(4, Carbon::createFromDate(2012, 9, 28)->weekOfMonth);
        $this->assertSame(3, Carbon::createFromDate(2012, 9, 20)->weekOfMonth);
        $this->assertSame(2, Carbon::createFromDate(2012, 9, 8)->weekOfMonth);
        $this->assertSame(1, Carbon::createFromDate(2012, 9, 1)->weekOfMonth);
        $this->assertSame(52, Carbon::createFromDate(2012, 1, 1)->weekOfYear);
        $this->assertSame(1, Carbon::createFromDate(2012, 1, 2)->weekOfYear);
        $this->assertSame(52, Carbon::createFromDate(2012, 12, 30)->weekOfYear);
        $this->assertSame(1, Carbon::createFromDate(2012, 12, 31)->weekOfYear);
        $d = Carbon::createFromDate(2000, 1, 1, 'America/Toronto');
        $this->assertSame('America/Toronto', $d->timezone->getName());
        $d = Carbon::createFromDate(2000, 1, 1, 'America/Toronto');
        $this->assertSame('America/Toronto', $d->tz->getName());
        $d = Carbon::createFromDate(2000, 1, 1, 'America/Toronto');
        $this->assertSame('America/Toronto', $d->timezoneName);
        $d = Carbon::createFromDate(2000, 1, 1, 'America/Toronto');
        $this->assertSame('America/Toronto', $d->tzName);
    }

    /**
     * Test instance.
     *
     * @group system
     */
    public function testInstance()
    {
        $d = Carbon::instance(\DateTime::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11'));
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::instance(\DateTime::createFromFormat('Y-m-d H:i:s', '1975-05-21 22:32:11')->setTimezone(new \DateTimeZone('America/Vancouver')));
        $this->assertSame('America/Vancouver', $d->tzName);
        $micro = 254687;
        $datetime = \DateTime::createFromFormat('Y-m-d H:i:s.u', '2014-02-01 03:45:27.' . $micro);
        $carbon = Carbon::instance($datetime);
        $this->assertSame($micro, $carbon->micro);
    }

    /**
     * Test is*.
     *
     * @group system
     */
    public function testIs()
    {
        $this->assertTrue(Carbon::createFromDate(2012, 1, 2)->isWeekday());
        $this->assertFalse(Carbon::createFromDate(2012, 1, 1)->isWeekday());
        $this->assertTrue(Carbon::createFromDate(2012, 1, 1)->isWeekend());
        $this->assertFalse(Carbon::createFromDate(2012, 1, 2)->isWeekend());
        $this->assertTrue(Carbon::now()->subDay()->isYesterday());
        $this->assertFalse(Carbon::now()->endOfDay()->isYesterday());
        $this->assertFalse(Carbon::now()->subDays(2)->startOfDay()->isYesterday());
        $this->assertTrue(Carbon::now()->isToday());
        $this->assertFalse(Carbon::now()->subDay()->endOfDay()->isToday());
        $this->assertFalse(Carbon::now()->addDay()->startOfDay()->isToday());
        $this->assertTrue(Carbon::now('Asia/Tokyo')->isToday());
        $this->assertTrue(Carbon::now()->addDay()->isTomorrow());
        $this->assertFalse(Carbon::now()->endOfDay()->isTomorrow());
        $this->assertFalse(Carbon::now()->addDays(2)->startOfDay()->isTomorrow());
        $this->assertTrue(Carbon::now()->addSecond()->isFuture());
        $this->assertFalse(Carbon::now()->isFuture());
        $this->assertFalse(Carbon::now()->subSecond()->isFuture());
        $this->assertTrue(Carbon::now()->subSecond()->isPast());
        $this->assertFalse(Carbon::now()->addSecond()->isPast());
        $this->assertTrue(Carbon::createFromDate(2016, 1, 1)->isLeapYear());
        $this->assertFalse(Carbon::createFromDate(2014, 1, 1)->isLeapYear());
        $now = Carbon::createFromDate(2012, 1, 2);
        $this->assertTrue($now->isSameDay(Carbon::createFromDate(2012, 1, 2)));
        $now = Carbon::createFromDate(2012, 1, 2);
        $this->assertFalse($now->isSameDay(Carbon::createFromDate(2012, 1, 3)));
    }

    /**
     * Test property ada.
     *
     * @group system
     */
    public function testPropertieExists()
    {
        $props = [
            'year', 'month', 'day', 'hour', 'minute', 'second', 'dayOfWeek', 'dayOfYear', 'daysInMonth',
            'timestamp', 'age', 'quarter', 'dst', 'offset', 'offsetHours', 'timezone', 'timezoneName', 'tz', 'tzName',
        ];

        foreach ($props as $prop) {
            $this->assertTrue(isset(Carbon::create(1234, 5, 6, 7, 8, 9)->{$prop}));
        }

        $this->assertFalse(isset(Carbon::create(1234, 5, 6, 7, 8, 9)->asdfghjkl));
    }

    /**
     * Test static helper.
     *
     * @group system
     */
    public function testStaticHelper()
    {
        $d = Carbon::now();
        $this->assertSame(time(), $d->timestamp);
        $d = Carbon::now('Europe/London');
        $this->assertSame(time(), $d->timestamp);
        $this->assertSame('Europe/London', $d->tzName);
        $d = Carbon::today();
        $this->assertSame(date('Y-m-d 00:00:00'), $d->toDateTimeString());
        $d = Carbon::today('Europe/London');
        $d2 = new \DateTime('now', new \DateTimeZone('Europe/London'));
        $this->assertSame($d2->format('Y-m-d 00:00:00'), $d->toDateTimeString());
        $d = Carbon::tomorrow();
        $d2 = new \DateTime('tomorrow');
        $this->assertSame($d2->format('Y-m-d 00:00:00'), $d->toDateTimeString());
        $d = Carbon::tomorrow('Europe/London');
        $d2 = new \DateTime('tomorrow', new \DateTimeZone('Europe/London'));
        $this->assertSame($d2->format('Y-m-d 00:00:00'), $d->toDateTimeString());
        $d = Carbon::yesterday();
        $d2 = new \DateTime('yesterday');
        $this->assertSame($d2->format('Y-m-d 00:00:00'), $d->toDateTimeString());
        $d = Carbon::yesterday('Europe/London');
        $d2 = new \DateTime('yesterday', new \DateTimeZone('Europe/London'));
        $this->assertSame($d2->format('Y-m-d 00:00:00'), $d->toDateTimeString());
        $this->assertLessThanOrEqual(-2147483647, Carbon::minValue()->getTimestamp());
        $this->assertGreaterThanOrEqual(2147483647, Carbon::maxValue()->getTimestamp());
    }

    /**
     * Test waktu relatif.
     *
     * @group system
     */
    public function testRelativeTime()
    {
        $d = Carbon::today()->addSeconds(30);
        $this->assertSame(30, $d->secondsSinceMidnight());
        $d = Carbon::today()->addDays(1);
        $this->assertSame(0, $d->secondsSinceMidnight());
        $d = Carbon::today()->addDays(1)->addSeconds(120);
        $this->assertSame(120, $d->secondsSinceMidnight());
        $d = Carbon::today()->addMonths(3)->addSeconds(42);
        $this->assertSame(42, $d->secondsSinceMidnight());
        $d = Carbon::today()->endOfDay();
        $this->assertSame(0, $d->secondsUntilEndOfDay());
        $d = Carbon::today()->endOfDay()->subSeconds(60);
        $this->assertSame(60, $d->secondsUntilEndOfDay());
        $d = Carbon::create(2014, 10, 24, 12, 34, 56);
        $this->assertSame(41103, $d->secondsUntilEndOfDay());
        $d = Carbon::create(2014, 10, 24, 0, 0, 0);
        $this->assertSame(86399, $d->secondsUntilEndOfDay());
    }

    /**
     * Test setter.
     *
     * @group system
     */
    public function testSetter()
    {
        $d = Carbon::now();
        $d->year = 1995;
        $this->assertSame(1995, $d->year);
        $d = Carbon::now();
        $d->month = 3;
        $this->assertSame(3, $d->month);
        $d = Carbon::now();
        $d->month = 13;
        $this->assertSame(1, $d->month);
        $d = Carbon::now();
        $d->day = 2;
        $this->assertSame(2, $d->day);
        $d = Carbon::createFromDate(2012, 8, 5);
        $d->day = 32;
        $this->assertSame(1, $d->day);
        $d = Carbon::now();
        $d->hour = 2;
        $this->assertSame(2, $d->hour);
        $d = Carbon::now();
        $d->hour = 25;
        $this->assertSame(1, $d->hour);
        $d = Carbon::now();
        $d->minute = 2;
        $this->assertSame(2, $d->minute);
        $d = Carbon::now();
        $d->minute = 65;
        $this->assertSame(5, $d->minute);
        $d = Carbon::now();
        $d->second = 2;
        $this->assertSame(2, $d->second);
        $d = Carbon::now();
        $d->setTime(1, 1, 1);
        $this->assertSame(1, $d->second);
        $d->setTime(1, 1);
        $this->assertSame(0, $d->second);
        $d = Carbon::now();
        $d->setTime(2, 2, 2)->setTime(1, 1, 1);
        $this->assertTrue($d instanceof Carbon);
        $this->assertSame(1, $d->second);
        $d->setTime(2, 2, 2)->setTime(1, 1);
        $this->assertTrue($d instanceof Carbon);
        $this->assertSame(0, $d->second);
        $d = Carbon::now();
        $d->setTime(1, 1);
        $this->assertSame(0, $d->second);
        $d = Carbon::now();
        $d->setDateTime($d->year, $d->month, $d->day, 1, 1, 1);
        $this->assertSame(1, $d->second);
        $d = Carbon::now();
        $d->setDateTime($d->year, $d->month, $d->day, 1, 1);
        $this->assertSame(0, $d->second);
        $d = Carbon::now();
        $d->setDateTime(2013, 9, 24, 17, 4, 29);
        $this->assertTrue($d instanceof Carbon);
        $d->setDateTime(2014, 10, 25, 18, 5, 30);
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $d->second = 65;
        $this->assertSame(5, $d->second);
        $d = Carbon::now();
        $d->timestamp = 10;
        $this->assertSame(10, $d->timestamp);
        $d->setTimestamp(11);
        $this->assertSame(11, $d->timestamp);
        $d = Carbon::now();
        $d->setTimezone('America/Toronto');
        $this->assertSame('America/Toronto', $d->tzName);
        $d = Carbon::now();
        $d->timezone = 'America/Toronto';
        $this->assertSame('America/Toronto', $d->tzName);
        $d->timezone('America/Vancouver');
        $this->assertSame('America/Vancouver', $d->tzName);
        $d = Carbon::now();
        $d->tz = 'America/Toronto';
        $this->assertSame('America/Toronto', $d->tzName);
        $d->tz('America/Vancouver');
        $this->assertSame('America/Vancouver', $d->tzName);
        $d = Carbon::now();
        $d->setTimezone(new \DateTimeZone('America/Toronto'));
        $this->assertSame('America/Toronto', $d->tzName);
        $d = Carbon::now();
        $d->timezone = new \DateTimeZone('America/Toronto');
        $this->assertSame('America/Toronto', $d->tzName);
        $d->timezone(new \DateTimeZone('America/Vancouver'));
        $this->assertSame('America/Vancouver', $d->tzName);
        $d = Carbon::now();
        $d->tz = new \DateTimeZone('America/Toronto');
        $this->assertSame('America/Toronto', $d->tzName);
        $d->tz(new \DateTimeZone('America/Vancouver'));
        $this->assertSame('America/Vancouver', $d->tzName);
    }

    /**
     * Test startOf & endOf.
     *
     * @group system
     */
    public function testStartOfEndOf()
    {
        $d = Carbon::now();
        $this->assertTrue($d->startOfDay() instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->endOfDay() instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->startOfMonth() instanceof Carbon);
        $d = Carbon::now()->startOfMonth();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 31, 2, 3, 4)->startOfMonth();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->startOfYear() instanceof Carbon);
        $d = Carbon::now()->startOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->startOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 12, 31, 23, 59, 59)->startOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->endOfMonth() instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 2, 3, 4)->endOfMonth();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 31, 2, 3, 4)->endOfMonth();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->endOfYear() instanceof Carbon);
        $d = Carbon::now()->endOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->endOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 12, 31, 23, 59, 59)->endOfYear();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->startOfDecade() instanceof Carbon);
        $d = Carbon::now()->startOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->startOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2009, 12, 31, 23, 59, 59)->startOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->endOfDecade() instanceof Carbon);
        $d = Carbon::now()->endOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->endOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2009, 12, 31, 23, 59, 59)->endOfDecade();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->startOfCentury() instanceof Carbon);
        $d = Carbon::now()->startOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->startOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2009, 12, 31, 23, 59, 59)->startOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now();
        $this->assertTrue($d->endOfCentury() instanceof Carbon);
        $d = Carbon::now()->endOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2000, 1, 1, 1, 1, 1)->endOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::create(2099, 12, 31, 23, 59, 59)->endOfCentury();
        $this->assertTrue($d instanceof Carbon);
        $d = Carbon::now()->average();
        $this->assertTrue($d instanceof Carbon);
        $d1 = Carbon::create(2000, 1, 31, 2, 3, 4);
        $d2 = Carbon::create(2000, 1, 31, 2, 3, 4)->average($d1);
        $this->assertTrue($d instanceof Carbon);
        $d1 = Carbon::create(2000, 1, 1, 1, 1, 1);
        $d2 = Carbon::create(2009, 12, 31, 23, 59, 59)->average($d1);
        $this->assertTrue($d instanceof Carbon);
        $d1 = Carbon::create(2009, 12, 31, 23, 59, 59);
        $d2 = Carbon::create(2000, 1, 1, 1, 1, 1)->average($d1);
        $this->assertTrue($d instanceof Carbon);
    }

    /**
     * Test konversi ke string.
     *
     * @group system
     */
    public function testToString()
    {
        $d = Carbon::now();
        $this->assertSame(Carbon::now()->toDateTimeString(), '' . $d);
        Carbon::setToStringFormat('jS \o\f F, Y g:i:s a');
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('25th of December, 1975 2:15:16 pm', '' . $d);
        $d = Carbon::now();
        Carbon::setToStringFormat('123');
        Carbon::resetToStringFormat();
        $this->assertSame($d->toDateTimeString(), '' . $d);
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25', $d->toDateString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Dec 25, 1975', $d->toFormattedDateString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('14:15:16', $d->toTimeString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25 14:15:16', $d->toDateTimeString());
        $d = Carbon::create(2000, 5, 2, 4, 3, 4);
        $this->assertSame('2000-05-02 04:03:04', $d->toDateTimeString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, Dec 25, 1975 2:15 PM', $d->toDayDateTimeString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25T14:15:16+00:00', $d->toAtomString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $cookie = (\DateTime::COOKIE === 'l, d-M-y H:i:s T') ? 'Thursday, 25-Dec-75 14:15:16 UTC' : 'Thursday, 25-Dec-1975 14:15:16 UTC';
        $this->assertSame($cookie, $d->toCOOKIEString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25T14:15:16+00:00', $d->toIso8601String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, 25 Dec 75 14:15:16 +0000', $d->toRfc822String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thursday, 25-Dec-75 14:15:16 UTC', $d->toRfc850String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, 25 Dec 75 14:15:16 +0000', $d->toRfc1036String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, 25 Dec 1975 14:15:16 +0000', $d->toRfc1123String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, 25 Dec 1975 14:15:16 +0000', $d->toRfc2822String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25T14:15:16+00:00', $d->toRfc3339String());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('Thu, 25 Dec 1975 14:15:16 +0000', $d->toRssString());
        $d = Carbon::create(1975, 12, 25, 14, 15, 16);
        $this->assertSame('1975-12-25T14:15:16+00:00', $d->toW3cString());
    }

    /**
     * Test lain-lain.
     *
     * @group system
     */
    public function testOther()
    {
        Carbon::setNow();
        $this->assertFalse(Carbon::hasTestNow());
        $this->assertNull(Carbon::getTestNow());
        $other = Carbon::yesterday();
        Carbon::setNow($other);
        $this->assertTrue(Carbon::hasTestNow());
        $this->assertSame($other, Carbon::getTestNow());
        $other = Carbon::yesterday();
        Carbon::setNow($other);
        $this->assertEquals($other, new Carbon());
        $this->assertEquals($other, new Carbon(null));
        $this->assertEquals($other, new Carbon(''));
        $this->assertEquals($other, new Carbon('now'));
        $other = Carbon::yesterday();
        Carbon::setNow($other);
        $this->assertEquals($other, Carbon::now());
        $other = Carbon::yesterday();
        Carbon::setNow($other);
        $this->assertEquals($other, Carbon::parse());
        $this->assertEquals($other, Carbon::parse(null));
        $this->assertEquals($other, Carbon::parse(''));
        $this->assertEquals($other, Carbon::parse('now'));
        $other = Carbon::parse('2013-09-01 05:15:05');
        Carbon::setNow($other);
        $this->assertSame('2013-09-01 05:10:05', Carbon::parse('5 minutes ago')->toDateTimeString());
        $this->assertSame('2013-08-25 05:15:05', Carbon::parse('1 week ago')->toDateTimeString());
        $this->assertSame('2013-09-02 00:00:00', Carbon::parse('tomorrow')->toDateTimeString());
        $this->assertSame('2013-08-31 00:00:00', Carbon::parse('yesterday')->toDateTimeString());
        $this->assertSame('2013-09-02 05:15:05', Carbon::parse('+1 day')->toDateTimeString());
        $this->assertSame('2013-08-31 05:15:05', Carbon::parse('-1 day')->toDateTimeString());
        $this->assertSame('2013-09-02 00:00:00', Carbon::parse('next monday')->toDateTimeString());
        $this->assertSame('2013-09-03 00:00:00', Carbon::parse('next tuesday')->toDateTimeString());
        $this->assertSame('2013-09-04 00:00:00', Carbon::parse('next wednesday')->toDateTimeString());
        $this->assertSame('2013-09-05 00:00:00', Carbon::parse('next thursday')->toDateTimeString());
        $this->assertSame('2013-09-06 00:00:00', Carbon::parse('next friday')->toDateTimeString());
        $this->assertSame('2013-09-07 00:00:00', Carbon::parse('next saturday')->toDateTimeString());
        $this->assertSame('2013-09-08 00:00:00', Carbon::parse('next sunday')->toDateTimeString());
        $this->assertSame('2013-08-26 00:00:00', Carbon::parse('last monday')->toDateTimeString());
        $this->assertSame('2013-08-27 00:00:00', Carbon::parse('last tuesday')->toDateTimeString());
        $this->assertSame('2013-08-28 00:00:00', Carbon::parse('last wednesday')->toDateTimeString());
        $this->assertSame('2013-08-29 00:00:00', Carbon::parse('last thursday')->toDateTimeString());
        $this->assertSame('2013-08-30 00:00:00', Carbon::parse('last friday')->toDateTimeString());
        $this->assertSame('2013-08-31 00:00:00', Carbon::parse('last saturday')->toDateTimeString());
        $this->assertSame('2013-08-25 00:00:00', Carbon::parse('last sunday')->toDateTimeString());
        $this->assertSame('2013-09-02 00:00:00', Carbon::parse('this monday')->toDateTimeString());
        $this->assertSame('2013-09-03 00:00:00', Carbon::parse('this tuesday')->toDateTimeString());
        $this->assertSame('2013-09-04 00:00:00', Carbon::parse('this wednesday')->toDateTimeString());
        $this->assertSame('2013-09-05 00:00:00', Carbon::parse('this thursday')->toDateTimeString());
        $this->assertSame('2013-09-06 00:00:00', Carbon::parse('this friday')->toDateTimeString());
        $this->assertSame('2013-09-07 00:00:00', Carbon::parse('this saturday')->toDateTimeString());
        $this->assertSame('2013-09-01 00:00:00', Carbon::parse('this sunday')->toDateTimeString());
        $this->assertSame('2013-10-01 05:15:05', Carbon::parse('first day of next month')->toDateTimeString());
        $this->assertSame('2013-09-30 05:15:05', Carbon::parse('last day of this month')->toDateTimeString());
        $other = Carbon::parse('2013-09-01 05:15:05');
        Carbon::setNow($other);
        $this->assertSame('2000-01-03 00:00:00', Carbon::parse('2000-1-3')->toDateTimeString());
        $this->assertSame('2000-10-10 00:00:00', Carbon::parse('2000-10-10')->toDateTimeString());
        $other = Carbon::parse('2013-07-01 12:00:00', 'America/New_York');
        Carbon::setNow($other);
        $this->assertSame('2013-07-01T12:00:00-04:00', Carbon::parse('now')->toIso8601String());
        $this->assertSame('2013-07-01T11:00:00-05:00', Carbon::parse('now', 'America/Mexico_City')->toIso8601String());
        $this->assertSame('2013-07-01T09:00:00-07:00', Carbon::parse('now', 'America/Vancouver')->toIso8601String());
    }
}
