<?php

namespace System;

defined('DS') or exit('No direct access.');

class Date extends \DateTime
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    protected static $test_now;
    protected static $format = 'Y-m-d H:i:s';
    protected static $relatives = ['this', 'next', 'last', 'tomorrow', 'yesterday', '+', '-', 'first', 'last', 'ago'];
    protected static $days = [
        self::SUNDAY => 'Sunday',
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
    ];

    public function __construct($time = null, $tz = null)
    {
        if (static::hasTestNow() && (empty($time) || $time === 'now' || static::hasRelativeKeywords($time))) {
            $test = clone static::getTestNow();

            if (static::hasRelativeKeywords($time)) {
                $test->modify($time);
            }

            if ($tz !== null && $tz != static::getTestNow()->tz) {
                $test->setTimezone($tz);
            } else {
                $tz = $test->tz;
            }

            $time = $test->toDateTimeString();
        }

        parent::__construct($time, static::safeCreateDateTimeZone($tz));
    }

    protected static function safeCreateDateTimeZone($tz)
    {
        if ($tz === null) {
            return new \DateTimeZone(Config::get('application.timezone'));
        }

        if ($tz instanceof \DateTimeZone) {
            return $tz;
        }

        try {
            $zone = timezone_open((string) $tz);

            if (false === $tz) {
                throw new \Exception('Unknown or bad timezone (' . $tz . ')');
            }

            return $zone;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    public static function instance(\DateTime $dt)
    {
        return new static($dt->format('Y-m-d H:i:s.u'), $dt->getTimeZone());
    }

    public static function parse($time = null, $tz = null)
    {
        return new static($time, $tz);
    }

    public static function now($tz = null)
    {
        return new static(null, $tz);
    }

    public static function today($tz = null)
    {
        return static::now($tz)->startOfDay();
    }

    public static function tomorrow($tz = null)
    {
        return static::today($tz)->addDay();
    }

    public static function yesterday($tz = null)
    {
        return static::today($tz)->subDay();
    }

    public static function maxValue()
    {
        return static::createFromTimestamp(PHP_INT_MAX);
    }

    public static function minValue()
    {
        return static::createFromTimestamp(~PHP_INT_MAX);
    }

    public static function create(
        $year = null,
        $month = null,
        $day = null,
        $hour = null,
        $minute = null,
        $second = null,
        $tz = null
    ) {
        $year = ($year === null) ? date('Y') : $year;
        $month = ($month === null) ? date('n') : $month;
        $day = ($day === null) ? date('j') : $day;

        if ($hour === null) {
            $hour = date('G');
            $minute = ($minute === null) ? date('i') : $minute;
            $second = ($second === null) ? date('s') : $second;
        } else {
            $minute = ($minute === null) ? 0 : $minute;
            $second = ($second === null) ? 0 : $second;
        }

        $dt = sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second);
        return static::createFromFormat('Y-n-j G:i:s', $dt, $tz);
    }

    public static function createFromDate($year = null, $month = null, $day = null, $tz = null)
    {
        return static::create($year, $month, $day, null, null, null, $tz);
    }

    public static function createFromTime($hour = null, $minute = null, $second = null, $tz = null)
    {
        return static::create(null, null, null, $hour, $minute, $second, $tz);
    }

    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $time, $tz = null)
    {
        if ($tz !== null) {
            $dt = parent::createFromFormat($format, $time, static::safeCreateDateTimeZone($tz));
        } else {
            $dt = parent::createFromFormat($format, $time);
        }

        if ($dt instanceof \DateTime) {
            return static::instance($dt);
        }

        $errors = static::getLastErrors();
        throw new \Exception(implode(PHP_EOL, $errors['errors']));
    }

    public static function createFromTimestamp($timestamp, $tz = null)
    {
        return static::now($tz)->setTimestamp($timestamp);
    }

    public static function createFromTimestampUTC($timestamp)
    {
        return new static('@' . $timestamp);
    }

    public function copy()
    {
        return static::instance($this);
    }

    public function __get($name)
    {
        $formats = [
            'year' => 'Y',
            'yearIso' => 'o',
            'month' => 'n',
            'day' => 'j',
            'hour' => 'G',
            'minute' => 'i',
            'second' => 's',
            'micro' => 'u',
            'dayOfWeek' => 'w',
            'dayOfYear' => 'z',
            'weekOfYear' => 'W',
            'daysInMonth' => 't',
            'timestamp' => 'U',
        ];

        switch (true) {
            case array_key_exists($name, $formats):
                return (int) $this->format($formats[$name]);

            case $name === 'weekOfMonth':
                return (int) ceil($this->day / 7);

            case $name === 'age':
                return (int) $this->diffInYears();

            case $name === 'quarter':
                return (int) ceil($this->month / 3);

            case $name === 'offset':
                return $this->getOffset();

            case $name === 'offsetHours':
                return $this->getOffset() / 60 / 60;

            case $name === 'dst':
                return $this->format('I') == '1';

            case $name === 'local':
                return $this->offset == $this->copy()->setTimezone(date_default_timezone_get())->offset;

            case $name === 'utc':
                return $this->offset == 0;

            case $name === 'timezone' || $name === 'tz':
                return $this->getTimezone();

            case $name === 'timezoneName' || $name === 'tzName':
                return $this->getTimezone()->getName();

            default:
                throw new \Exception(sprintf("Unknown getter '%s'", $name));
        }
    }

    public function __isset($name)
    {
        try {
            $this->__get($name);
        } catch (\Throwable $e) {
            return false;
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    public function __set($name, $value)
    {
        switch ($name) {
            case 'year':
                $this->setDate($value, $this->month, $this->day);
                break;

            case 'month':
                $this->setDate($this->year, $value, $this->day);
                break;

            case 'day':
                $this->setDate($this->year, $this->month, $value);
                break;

            case 'hour':
                $this->setTime($value, $this->minute, $this->second);
                break;

            case 'minute':
                $this->setTime($this->hour, $value, $this->second);
                break;

            case 'second':
                $this->setTime($this->hour, $this->minute, $value);
                break;

            case 'timestamp':
                parent::setTimestamp($value);
                break;

            case 'timezone':
            case 'tz':
                $this->setTimezone($value);
                break;

            default:
                throw new \Exception(sprintf("Unknown setter '%s'", $name));
        }
    }

    public function year($value)
    {
        $this->year = $value;
        return $this;
    }

    public function month($value)
    {
        $this->month = $value;
        return $this;
    }

    public function day($value)
    {
        $this->day = $value;
        return $this;
    }

    public function hour($value)
    {
        $this->hour = $value;
        return $this;
    }

    public function minute($value)
    {
        $this->minute = $value;
        return $this;
    }

    public function second($value)
    {
        $this->second = $value;
        return $this;
    }

    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0)
    {
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }

    public function timestamp($value)
    {
        $this->timestamp = $value;
        return $this;
    }

    public function timezone($value)
    {
        return $this->setTimezone($value);
    }

    public function tz($value)
    {
        return $this->setTimezone($value);
    }

    #[\ReturnTypeWillChange]
    public function setTimezone($value)
    {
        parent::setTimezone(static::safeCreateDateTimeZone($value));
        return $this;
    }

    public static function setTestNow(Date $test_now = null)
    {
        static::$test_now = $test_now;
    }

    public static function getTestNow()
    {
        return static::$test_now;
    }

    public static function hasTestNow()
    {
        return static::getTestNow() !== null;
    }

    public static function hasRelativeKeywords($time)
    {
        if (preg_match('/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/', $time) !== 1) {
            foreach (static::$relatives as $keyword) {
                if (stripos($time, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    public static function resetToStringFormat()
    {
        static::setToStringFormat('Y-m-d H:i:s');
    }

    public static function setToStringFormat($format)
    {
        static::$format = $format;
    }

    public function __toString()
    {
        return $this->format(static::$format);
    }

    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    public function toFormattedDateString()
    {
        return $this->format('M j, Y');
    }

    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    public function toDayDateTimeString()
    {
        return $this->format('D, M j, Y g:i A');
    }

    public function toAtomString()
    {
        return $this->format(static::ATOM);
    }

    public function toCookieString()
    {
        return $this->format(static::COOKIE);
    }

    public function toIso8601String()
    {
        return $this->format('Y-m-d\TH:i:s');
    }

    public function toRfc822String()
    {
        return $this->format(static::RFC822);
    }

    public function toRfc850String()
    {
        return $this->format(static::RFC850);
    }

    public function toRfc1036String()
    {
        return $this->format(static::RFC1036);
    }

    public function toRfc1123String()
    {
        return $this->format(static::RFC1123);
    }

    public function toRfc2822String()
    {
        return $this->format(static::RFC2822);
    }

    public function toRfc3339String()
    {
        return $this->format(static::RFC3339);
    }

    public function toRssString()
    {
        return $this->format(static::RSS);
    }

    public function toW3cString()
    {
        return $this->format(static::W3C);
    }

    public function eq(Date $dt)
    {
        return $this == $dt;
    }

    public function ne(Date $dt)
    {
        return !$this->eq($dt);
    }

    public function gt(Date $dt)
    {
        return $this > $dt;
    }

    public function gte(Date $dt)
    {
        return $this >= $dt;
    }

    public function lt(Date $dt)
    {
        return $this < $dt;
    }

    public function lte(Date $dt)
    {
        return $this <= $dt;
    }

    public function between(Date $dt1, Date $dt2, $equal = true)
    {
        if ($dt1->gt($dt2)) {
            $temp = $dt1;
            $dt1 = $dt2;
            $dt2 = $temp;
        }

        return $equal ? ($this->gte($dt1) && $this->lte($dt2)) : ($this->gt($dt1) && $this->lt($dt2));
    }

    public function min(Date $dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->lt($dt) ? $this : $dt;
    }

    public function max(Date $dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->gt($dt) ? $this : $dt;
    }

    public function isWeekday()
    {
        return ($this->dayOfWeek != static::SUNDAY && $this->dayOfWeek != static::SATURDAY);
    }

    public function isWeekend()
    {
        return !$this->isWeekDay();
    }

    public function isYesterday()
    {
        return $this->toDateString() === static::yesterday($this->tz)->toDateString();
    }

    public function isToday()
    {
        return $this->toDateString() === static::now($this->tz)->toDateString();
    }

    public function isTomorrow()
    {
        return $this->toDateString() === static::tomorrow($this->tz)->toDateString();
    }

    public function isFuture()
    {
        return $this->gt(static::now($this->tz));
    }

    public function isPast()
    {
        return $this->lt(static::now($this->tz));
    }

    public function isLeapYear()
    {
        return $this->format('L') == '1';
    }

    public function isSameDay(Date $dt)
    {
        return $this->toDateString() === $dt->toDateString();
    }

    public function addYears($value)
    {
        return $this->modify((int) $value . ' year');
    }

    public function addYear()
    {
        return $this->addYears(1);
    }

    public function subYear()
    {
        return $this->addYears(-1);
    }

    public function subYears($value)
    {
        return $this->addYears(-1 * $value);
    }

    public function addMonths($value)
    {
        return $this->modify((int) $value . ' month');
    }

    public function addMonth()
    {
        return $this->addMonths(1);
    }

    public function subMonth()
    {
        return $this->addMonths(-1);
    }

    public function subMonths($value)
    {
        return $this->addMonths(-1 * $value);
    }

    public function addMonthsNoOverflow($value)
    {
        $date = $this->copy()->addMonths($value);

        if ($date->day != $this->day) {
            $date->day(1)->subMonth()->day($date->daysInMonth);
        }

        return $date;
    }

    public function addMonthNoOverflow()
    {
        return $this->addMonthsNoOverflow(1);
    }

    public function subMonthNoOverflow()
    {
        return $this->addMonthsNoOverflow(-1);
    }

    public function subMonthsNoOverflow($value)
    {
        return $this->addMonthsNoOverflow(-1 * $value);
    }

    public function addDays($value)
    {
        return $this->modify((int) $value . ' day');
    }

    public function addDay()
    {
        return $this->addDays(1);
    }

    public function subDay()
    {
        return $this->addDays(-1);
    }

    public function subDays($value)
    {
        return $this->addDays(-1 * $value);
    }

    public function addWeekdays($value)
    {
        return $this->modify((int) $value . ' weekday');
    }

    public function addWeekday()
    {
        return $this->addWeekdays(1);
    }

    public function subWeekday()
    {
        return $this->addWeekdays(-1);
    }

    public function subWeekdays($value)
    {
        return $this->addWeekdays(-1 * $value);
    }

    public function addWeeks($value)
    {
        return $this->modify((int) $value . ' week');
    }

    public function addWeek()
    {
        return $this->addWeeks(1);
    }

    public function subWeek()
    {
        return $this->addWeeks(-1);
    }

    public function subWeeks($value)
    {
        return $this->addWeeks(-1 * $value);
    }

    public function addHours($value)
    {
        return $this->modify((int) $value . ' hour');
    }

    public function addHour()
    {
        return $this->addHours(1);
    }

    public function subHour()
    {
        return $this->addHours(-1);
    }

    public function subHours($value)
    {
        return $this->addHours(-1 * $value);
    }

    public function addMinutes($value)
    {
        return $this->modify((int) $value . ' minute');
    }

    public function addMinute()
    {
        return $this->addMinutes(1);
    }

    public function subMinute()
    {
        return $this->addMinutes(-1);
    }

    public function subMinutes($value)
    {
        return $this->addMinutes(-1 * $value);
    }

    public function addSeconds($value)
    {
        return $this->modify((int) $value . ' second');
    }

    public function addSecond()
    {
        return $this->addSeconds(1);
    }

    public function subSecond()
    {
        return $this->addSeconds(-1);
    }

    public function subSeconds($value)
    {
        return $this->addSeconds(-1 * $value);
    }

    public function diffInYears(Date $dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return (int) $this->diff($dt, $abs)->format('%r%y');
    }

    public function diffInMonths(Date $dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->diffInYears($dt, $abs) * 12 + $this->diff($dt, $abs)->format('%r%m');
    }

    public function diffInWeeks(Date $dt = null, $abs = true)
    {
        return (int) ($this->diffInDays($dt, $abs) / 7);
    }

    public function diffInDays(Date $dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return (int) $this->diff($dt, $abs)->format('%r%a');
    }

    public function diffInDaysFiltered(\Closure $callback, Date $dt = null, $abs = true)
    {
        $start = $this;
        $end = ($dt === null) ? static::now($this->tz) : $dt;
        $inverse = false;

        if ($end < $start) {
            $start = $end;
            $end = $this;
            $inverse = true;
        }

        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
        $days = array_filter(iterator_to_array($period), function (\DateTime $date) use ($callback) {
            return call_user_func($callback, Date::instance($date));
        });

        $diff = count($days);
        return ($inverse && !$abs) ? -$diff : $diff;
    }

    public function diffInWeekdays(Date $dt = null, $abs = true)
    {
        return $this->diffInDaysFiltered(function (Date $date) {
            return $date->isWeekday();
        }, $dt, $abs);
    }

    public function diffInWeekendDays(Date $dt = null, $abs = true)
    {
        return $this->diffInDaysFiltered(function (Date $date) {
            return $date->isWeekend();
        }, $dt, $abs);
    }

    public function diffInHours(Date $dt = null, $abs = true)
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 60 / 60);
    }

    public function diffInMinutes(Date $dt = null, $abs = true)
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 60);
    }

    public function diffInSeconds(Date $dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        $value = $dt->getTimestamp() - $this->getTimestamp();
        return $abs ? abs($value) : $value;
    }

    public function secondsSinceMidnight()
    {
        return $this->diffInSeconds($this->copy()->startOfDay());
    }

    public function secondsUntilEndOfDay()
    {
        return $this->diffInSeconds($this->copy()->endOfDay());
    }

    public function diffForHumans(Date $other = null, $absolute = false)
    {
        $isNow = $other === null;

        if ($isNow) {
            $other = static::now($this->tz);
        }

        $diffInterval = $this->diff($other);

        switch (true) {
            case ($diffInterval->y > 0):
                $unit = 'year';
                $delta = $diffInterval->y;
                break;

            case ($diffInterval->m > 0):
                $unit = 'month';
                $delta = $diffInterval->m;
                break;

            case ($diffInterval->d > 0):
                $unit = 'day';
                $delta = $diffInterval->d;
                if ($delta >= 7) {
                    $unit = 'week';
                    $delta = floor($delta / 7);
                }
                break;

            case ($diffInterval->h > 0):
                $unit = 'hour';
                $delta = $diffInterval->h;
                break;

            case ($diffInterval->i > 0):
                $unit = 'minute';
                $delta = $diffInterval->i;
                break;

            default:
                $delta = $diffInterval->s;
                $unit = 'second';
                break;
        }

        if ($delta == 0) {
            $delta = 1;
        }

        $txt = $delta . ' ' . $unit;
        $txt .= $delta == 1 ? '' : 's';

        if ($absolute) {
            return $txt;
        }

        $isFuture = $diffInterval->invert === 1;

        if ($isNow) {
            return $txt . ($isFuture ? ' from now' : 'ago');
        }

        return $txt . ($isFuture ? ' after' : ' before');
    }

    public function startOfDay()
    {
        return $this->hour(0)->minute(0)->second(0);
    }

    public function endOfDay()
    {
        return $this->hour(23)->minute(59)->second(59);
    }

    public function startOfMonth()
    {
        return $this->startOfDay()->day(1);
    }

    public function endOfMonth()
    {
        return $this->day($this->daysInMonth)->endOfDay();
    }

    public function startOfYear()
    {
        return $this->month(1)->startOfMonth();
    }

    public function endOfYear()
    {
        return $this->month(12)->endOfMonth();
    }

    public function startOfDecade()
    {
        return $this->startOfYear()->year($this->year - $this->year % 10);
    }

    public function endOfDecade()
    {
        return $this->endOfYear()->year($this->year - $this->year % 10 + 10 - 1);
    }

    public function startOfCentury()
    {
        return $this->startOfYear()->year($this->year - $this->year % 100);
    }

    public function endOfCentury()
    {
        return $this->endOfYear()->year($this->year - $this->year % 100 + 100 - 1);
    }

    public function startOfWeek()
    {
        if ($this->dayOfWeek != static::MONDAY) {
            $this->previous(static::MONDAY);
        }

        return $this->startOfDay();
    }

    public function endOfWeek()
    {
        if ($this->dayOfWeek != static::SUNDAY) {
            $this->next(static::SUNDAY);
        }

        return $this->endOfDay();
    }

    public function next($dayOfWeek = null)
    {
        if ($dayOfWeek === null) {
            $dayOfWeek = $this->dayOfWeek;
        }

        return $this->startOfDay()->modify('next ' . static::$days[$dayOfWeek]);
    }

    public function previous($dayOfWeek = null)
    {
        if ($dayOfWeek === null) {
            $dayOfWeek = $this->dayOfWeek;
        }

        return $this->startOfDay()->modify('last ' . static::$days[$dayOfWeek]);
    }

    public function firstOfMonth($dayOfWeek = null)
    {
        $this->startOfDay();

        if ($dayOfWeek === null) {
            return $this->day(1);
        }

        return $this->modify('first ' . static::$days[$dayOfWeek] . ' of ' . $this->format('F') . ' ' . $this->year);
    }

    public function lastOfMonth($dayOfWeek = null)
    {
        $this->startOfDay();

        if ($dayOfWeek === null) {
            return $this->day($this->daysInMonth);
        }

        return $this->modify('last ' . static::$days[$dayOfWeek] . ' of ' . $this->format('F') . ' ' . $this->year);
    }

    public function nthOfMonth($nth, $dayOfWeek)
    {
        $dt = $this->copy()->firstOfMonth();
        $check = $dt->format('Y-m');
        $dt->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);

        return ($dt->format('Y-m') === $check) ? $this->modify($dt) : false;
    }

    public function firstOfQuarter($dayOfWeek = null)
    {
        return $this->day(1)->month($this->quarter * 3 - 2)->firstOfMonth($dayOfWeek);
    }

    public function lastOfQuarter($dayOfWeek = null)
    {
        return $this->day(1)->month($this->quarter * 3)->lastOfMonth($dayOfWeek);
    }

    public function nthOfQuarter($nth, $dayOfWeek)
    {
        $dt = $this->copy()->day(1)->month($this->quarter * 3);
        $last_month = $dt->month;
        $year = $dt->year;
        $dt->firstOfQuarter()->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);

        return ($last_month < $dt->month || $year !== $dt->year) ? false : $this->modify($dt);
    }

    public function firstOfYear($dayOfWeek = null)
    {
        return $this->month(1)->firstOfMonth($dayOfWeek);
    }

    public function lastOfYear($dayOfWeek = null)
    {
        return $this->month(12)->lastOfMonth($dayOfWeek);
    }

    public function nthOfYear($nth, $dayOfWeek)
    {
        $dt = $this->copy()->firstOfYear()->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);
        return ($this->year == $dt->year) ? $this->modify($dt) : false;
    }

    public function average(Date $dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->addSeconds((int) ($this->diffInSeconds($dt, false) / 2));
    }

    public function isBirthday(Date $dt)
    {
        return $this->format('md') === $dt->format('md');
    }
}
