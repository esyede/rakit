<?php

defined('DS') or exit('No direct access.');

use System\Schedule;
use System\Schedule\Event;

class ScheduleTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Setup.
     */
    public function setUp()
    {
        Schedule::flush();
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schedule::flush();

        foreach ((array) glob(path('storage').'console'.DS.'schedule-*.lock') as $lock) {
            @unlink($lock);
        }
    }

    /**
     * Build a time in UTC.
     *
     * @param string $time
     *
     * @return \DateTime
     */
    protected function utc($time)
    {
        return new \DateTime($time, new \DateTimeZone('UTC'));
    }

    /**
     * The cron fields: numbers, ranges, lists, steps, and the day OR rule.
     *
     * @group system
     */
    public function testCronMatching()
    {
        $monday = $this->utc('2026-10-19 08:30:00'); // a Monday

        $this->assertTrue(Event::matches('* * * * *', $monday));
        $this->assertTrue(Event::matches('30 8 * * *', $monday));
        $this->assertFalse(Event::matches('31 8 * * *', $monday));
        $this->assertTrue(Event::matches('*/15 * * * *', $monday));
        $this->assertFalse(Event::matches('*/20 * * * *', $monday));
        $this->assertTrue(Event::matches('0-30/10 8-9 * * *', $monday));
        $this->assertTrue(Event::matches('5/25 * * * *', $monday));
        $this->assertTrue(Event::matches('0,30 * * * 1-5', $monday));
        $this->assertFalse(Event::matches('30 8 * * 0,6', $monday));
        $this->assertTrue(Event::matches('30 8 * 10 *', $monday));
        $this->assertFalse(Event::matches('30 8 * 11 *', $monday));

        // Day of month and day of week both restricted: either matches.
        $this->assertTrue(Event::matches('30 8 1 * 1', $monday));
        $this->assertFalse(Event::matches('30 8 1 * 2', $monday));

        // 7 is Sunday too.
        $this->assertTrue(Event::matches('0 0 * * 7', $this->utc('2026-10-18 00:00:00')));
    }

    /**
     * The frequency helpers build the expected expressions, and combine.
     *
     * @group system
     */
    public function testFrequencies()
    {
        $event = function () {
            return Schedule::call(function () {
            });
        };

        $this->assertEquals('* * * * *', $event()->every_minute()->expression);
        $this->assertEquals('*/5 * * * *', $event()->every_five_minutes()->expression);
        $this->assertEquals('0 * * * *', $event()->hourly()->expression);
        $this->assertEquals('17 * * * *', $event()->hourly_at(17)->expression);
        $this->assertEquals('0 0 * * *', $event()->daily()->expression);
        $this->assertEquals('30 13 * * *', $event()->daily_at('13:30')->expression);
        $this->assertEquals('0 1,13 * * *', $event()->twice_daily()->expression);
        $this->assertEquals('0 0 * * 0', $event()->weekly()->expression);
        $this->assertEquals('0 8 * * 1', $event()->weekly_on(1, '8:00')->expression);
        $this->assertEquals('0 0 1 * *', $event()->monthly()->expression);
        $this->assertEquals('15 9 4 * *', $event()->monthly_on(4, '09:15')->expression);
        $this->assertEquals('0 0 1 1 *', $event()->yearly()->expression);
        $this->assertEquals('*/10 * * * 1-5', $event()->weekdays()->every_ten_minutes()->expression);
        $this->assertEquals('0 0 * * 0,6', $event()->daily()->weekends()->expression);
        $this->assertEquals('0 0 * * 2,4', $event()->daily()->days(2, 4)->expression);
    }

    /**
     * Invalid expressions and times are refused.
     *
     * @group system
     */
    public function testInvalidExpressions()
    {
        foreach (['* * * *', '* * * * * *', 'a * * * *', '*/ * * * *', '1-- * * * *'] as $expression) {
            try {
                Schedule::call(function () {
                })->cron($expression);
                $this->fail('Accepted: '.$expression);
            } catch (\InvalidArgumentException $e) {
                $this->assertTrue(true);
            }
        }

        $this->setExpectedException('InvalidArgumentException');
        Schedule::call(function () {
        })->daily_at('25:00');
    }

    /**
     * An event's timezone shifts the time it is due at.
     *
     * @group system
     */
    public function testTimezone()
    {
        $event = Schedule::call(function () {
        })->daily_at('08:00')->timezone('Asia/Jakarta');

        $this->assertTrue($event->is_due($this->utc('2026-10-19 01:00:00')));
        $this->assertFalse($event->is_due($this->utc('2026-10-19 08:00:00')));
    }

    /**
     * due() returns the matching events, after when() and skip().
     *
     * @group system
     */
    public function testDueHonoursConditions()
    {
        $now = $this->utc('2026-10-19 08:30:00');
        $open = true;

        $a = Schedule::call(function () {
        })->name('a');
        $b = Schedule::call(function () {
        })->name('b')->daily();
        $c = Schedule::call(function () {
        })->name('c')->when(function () use (&$open) {
            return $open;
        });
        $d = Schedule::call(function () {
        })->name('d')->skip(function () {
            return true;
        });

        $this->assertSame([$a, $c], Schedule::due($now));

        $open = false;
        $this->assertSame([$a], Schedule::due($now));
    }

    /**
     * A closure runs with its arguments.
     *
     * @group system
     */
    public function testClosuresRun()
    {
        $seen = null;

        $event = Schedule::call(function ($value) use (&$seen) {
            $seen = $value;
        }, ['ran']);

        $this->assertTrue($event->run());
        $this->assertEquals('ran', $seen);
        $this->assertEquals('Closure', $event->summary());
    }

    /**
     * An overlapping run is skipped while the lock is held, and the lock is
     * released afterwards, also when the event throws.
     *
     * @group system
     */
    public function testWithoutOverlapping()
    {
        // Some filesystems accept every flock() (Docker Desktop bind mounts, some network
        // mounts); the lock cannot be tested, nor relied on, there.
        $probe = path('storage').'console'.DS.'schedule-probe.lock';
        $a = fopen($probe, 'c');
        $b = fopen($probe, 'c');
        $enforced = flock($a, LOCK_EX | LOCK_NB) && ! flock($b, LOCK_EX | LOCK_NB);
        fclose($a);
        fclose($b);

        if (! $enforced) {
            $this->markTestSkipped('flock() is not enforced on the storage filesystem.');
        }

        $inner = null;
        $runs = 0;

        $event = Schedule::call(function () use (&$inner, &$runs, &$event) {
            ++$runs;
            $inner = $event->run();
        })->name('schedule-test-overlap')->without_overlapping();

        $this->assertTrue($event->run());
        $this->assertNull($inner);
        $this->assertSame(1, $runs);

        $failing = Schedule::call(function () {
            throw new \RuntimeException('boom');
        })->name('schedule-test-throw')->without_overlapping();

        try {
            $failing->run();
            $this->fail('The exception was swallowed');
        } catch (\RuntimeException $e) {
            $this->assertEquals('boom', $e->getMessage());
        }

        $again = Schedule::call(function () {
        })->name('schedule-test-throw')->without_overlapping();
        $this->assertTrue($again->run());
    }

    /**
     * A closure needs a name to be locked.
     *
     * @group system
     */
    public function testUnnamedClosuresCanNotBeLocked()
    {
        $this->setExpectedException('LogicException');
        Schedule::call(function () {
        })->without_overlapping()->run();
    }

    /**
     * schedule:run defines the events through the hook, reports a failing event
     * once, and goes on with the next ones.
     *
     * @group system
     */
    public function testRunCommand()
    {
        $ran = [];

        \System\Hook::listen('rakit.schedule', function () use (&$ran) {
            Schedule::call(function () {
                throw new \RuntimeException('planned');
            })->name('schedule-test-failing');

            Schedule::call(function () use (&$ran) {
                $ran[] = 'after';
            })->name('schedule-test-after');
        });

        $log = \System\Config::get('log');
        \System\Config::set('log.default', 'null');
        \System\Log::$drivers = [];

        ob_start();
        $command = new \System\Console\Commands\Schedule();
        $command->run();
        $output = ob_get_clean();

        \System\Hook::clear('rakit.schedule');
        \System\Config::set('log', $log);
        \System\Log::$drivers = [];

        $this->assertSame(['after'], $ran);
        $this->assertSame(1, substr_count($output, 'Failed: schedule-test-failing'));
        $this->assertContains('planned', $output);
    }

    /**
     * Shell commands report their exit status.
     *
     * @group system
     */
    public function testShellCommands()
    {
        if ('\\' === DS) {
            $this->markTestSkipped('POSIX shell needed.');
        }

        ob_start();
        $this->assertTrue(Schedule::exec('true')->run());
        $this->assertFalse(Schedule::exec('false')->run());
        ob_end_clean();

        $this->assertEquals('true', Schedule::exec('true')->summary());
    }
}
