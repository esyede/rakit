<?php

namespace System;

defined('DS') or exit('No direct access.');

class Carbon extends \DateTime
{
    /** @var int */
    const SUNDAY = 0;

    /** @var int */
    const MONDAY = 1;

    /** @var int */
    const TUESDAY = 2;

    /** @var int */
    const WEDNESDAY = 3;

    /** @var int */
    const THURSDAY = 4;

    /** @var int */
    const FRIDAY = 5;

    /** @var int */
    const SATURDAY = 6;

    /** @var \DateTime|null */
    protected static $now;

    /** @var string */
    protected static $format = 'Y-m-d H:i:s';

    /** @var string[] */
    protected static $relatives = ['this', 'next', 'last', 'tomorrow', 'yesterday', '+', '-', 'first', 'last', 'ago'];

    /** @var string[] */
    protected static $days = [
        self::SUNDAY => 'Sunday',
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
    ];

    /**
     * Create a new Carbon instance.
     *
     * @param string|null $time
     * @param string|null $tz
     */
    public function __construct($time = null, $tz = null)
    {
        if (static::hasTestNow() && (empty($time) || $time === 'now' || static::hasRelativeKeywords($time))) {
            $test = clone static::getTestNow();

            if (static::hasRelativeKeywords($time)) {
                $test->modify($time);
            }

            if ($tz !== null && $tz !== static::getTestNow()->tz) {
                $test->setTimezone($tz);
            } else {
                $tz = $test->tz;
            }

            $time = $test->toDateTimeString();
        }

        parent::__construct($time ?: 'now', static::safeCreateDateTimeZone($tz));
    }

    /**
     * Safely create a DateTimeZone instance.
     *
     * @param string|null $tz
     *
     * @return \DateTimeZone
     */
    protected static function safeCreateDateTimeZone($tz)
    {
        if ($tz === null) {
            return new \DateTimeZone(Config::get('application.timezone'));
        }

        if ($tz instanceof \DateTimeZone) {
            return $tz;
        }

        try {
            $tz = new \DateTimeZone($tz);

            if (false === $tz) {
                throw new \Exception('Unknown or bad timezone');
            }

            return $tz;
        } catch (\Throwable $e) {
            throw new \Exception($e->getMessage());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Create a Carbon instance from a DateTime instance.
     *
     * @param \DateTime $dt
     *
     * @return static
     */
    public static function instance(\DateTime $dt)
    {
        return new static($dt->format('Y-m-d H:i:s.u'), $dt->getTimeZone());
    }

    /**
     * Parse a string into a Carbon instance.
     *
     * @param string|null $time
     * @param string|null $tz
     *
     * @return static
     */
    public static function parse($time = null, $tz = null)
    {
        return new static($time, $tz);
    }

    /**
     * Get the current Carbon instance.
     *
     * @param string|null $tz
     *
     * @return static
     */
    public static function now($tz = null)
    {
        return new static(null, $tz);
    }

    /**
     * Get the current Carbon instance.
     *
     * @param string|null $tz
     *
     * @return static
     */
    public static function today($tz = null)
    {
        return static::now($tz)->startOfDay();
    }

    /**
     * Get the tomorrow Carbon instance.
     *
     * @param string|null $tz
     *
     * @return static
     */
    public static function tomorrow($tz = null)
    {
        return static::today($tz)->addDay();
    }

    /**
     * Get the yesterday Carbon instance.
     *
     * @param string|null $tz
     *
     * @return static
     */
    public static function yesterday($tz = null)
    {
        return static::today($tz)->subDay();
    }

    /**
     * Get the maximum Carbon instance.
     *
     * @return static
     */
    public static function maxValue()
    {
        return static::createFromTimestamp(PHP_INT_MAX);
    }

    /**
     * Get the minimum Carbon instance.
     *
     * @return static
     */
    public static function minValue()
    {
        return static::createFromTimestamp(~PHP_INT_MAX);
    }

    /**
     * Create a Carbon instance from the given date and time components.
     *
     * @param int|null $year
     * @param int|null $month
     * @param int|null $day
     * @param int|null $hour
     * @param int|null $minute
     * @param int|null $second
     * @param string|null $tz
     *
     * @return static
     */
    public static function create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
    {
        $dt = new \DateTime();
        $year = ($year === null) ? $dt->format('Y') : $year;
        $month = ($month === null) ? $dt->format('n') : $month;
        $day = ($day === null) ? $dt->format('j') : $day;

        if ($hour === null) {
            $hour = $dt->format('G');
            $minute = ($minute === null) ? $dt->format('i') : $minute;
            $second = ($second === null) ? $dt->format('s') : $second;
        } else {
            $minute = ($minute === null) ? 0 : $minute;
            $second = ($second === null) ? 0 : $second;
        }

        $dt = sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second);
        return static::createFromFormat('Y-n-j G:i:s', $dt, $tz);
    }

    /**
     * Create a Carbon instance from a date.
     *
     * @param int|null $year
     * @param int|null $month
     * @param int|null $day
     * @param string|null $tz
     *
     * @return static
     */
    public static function createFromDate($year = null, $month = null, $day = null, $tz = null)
    {
        return static::create($year, $month, $day, null, null, null, $tz);
    }

    /**
     * Create a Carbon instance from a time.
     *
     * @param int|null $hour
     * @param int|null $minute
     * @param int|null $second
     * @param string|null $tz
     *
     * @return static
     */
    public static function createFromTime($hour = null, $minute = null, $second = null, $tz = null)
    {
        return static::create(null, null, null, $hour, $minute, $second, $tz);
    }

    /**
     * Create a Carbon instance from a format string and time.
     *
     * @param string $format
     * @param string $time
     * @param string|null $tz
     *
     * @return static
     */
    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $time, $tz = null)
    {
        $dt = ($tz !== null)
            ? parent::createFromFormat($format, $time, static::safeCreateDateTimeZone($tz))
            : parent::createFromFormat($format, $time);

        if ($dt instanceof \DateTime) {
            return static::instance($dt);
        }

        $last = static::getLastErrors();
        throw new \Exception(implode(PHP_EOL, $last['errors']));
    }

    /**
     * Create a Carbon instance from a timestamp.
     *
     * @param int $timestamp
     * @param string|null $tz
     *
     * @return static
     */
    #[\ReturnTypeWillChange]
    public static function createFromTimestamp($timestamp, $tz = null)
    {
        return static::now($tz)->setTimestamp($timestamp);
    }

    /**
     * Create a Carbon instance from a timestamp in UTC.
     *
     * @param int $timestamp
     *
     * @return static
     */
    public static function createFromTimestampUTC($timestamp)
    {
        return static::createFromTimestamp($timestamp, 'UTC');
    }

    /**
     * Copy the Carbon instance.
     *
     * @return static
     */
    public function copy()
    {
        return static::instance($this);
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function __get($name)
    {
        $formats = [
            'year' => 'Y', 'yearIso' => 'o', 'month' => 'n', 'day' => 'j',
            'hour' => 'G', 'minute' => 'i', 'second' => 's', 'micro' => 'u',
            'dayOfWeek' => 'w', 'dayOfYear' => 'z', 'weekOfYear' => 'W',
            'daysInMonth' => 't', 'timestamp' => 'U',
        ];

        switch (true) {
            case array_key_exists($name, $formats):              return (int) ($this->format($formats[$name]));
            case $name === 'weekOfMonth':                        return (int) (ceil($this->day / 7));
            case $name === 'age':                                return (int) ($this->diffInYears());
            case $name === 'quarter':                            return (int) (ceil($this->month / 3));
            case $name === 'offset':                             return $this->getOffset();
            case $name === 'offsetHours':                        return $this->getOffset() / 60 / 60;
            case $name === 'dst':                                return $this->format('I') === '1';
            case $name === 'utc':                                return $this->offset === 0;
            case $name === 'timezone' || $name === 'tz':         return $this->getTimezone();
            case $name === 'timezoneName' || $name === 'tzName': return $this->getTimezone()->getName();
            default:                                             throw new \Exception(sprintf("Unknown getter '%s'", $name));
        }
    }

    /**
     * Check if a property is set.
     *
     * @param string $name
     *
     * @return bool
     */
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

    /**
     * Set the value of a property.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'tz':
            case 'timezone':  $this->setTimezone($value);
                break;
            case 'timestamp': parent::setTimestamp($value); break;
            case 'year':      $this->setDate($value, $this->month, $this->day); break;
            case 'month':     $this->setDate($this->year, $value, $this->day); break;
            case 'day':       $this->setDate($this->year, $this->month, $value); break;
            case 'hour':      $this->setTime($value, $this->minute, $this->second); break;
            case 'minute':    $this->setTime($this->hour, $value, $this->second); break;
            case 'second':    $this->setTime($this->hour, $this->minute, $value); break;
            default:          throw new \Exception(sprintf("Unknown setter '%s'", $name));
        }
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function year($value)
    {
        $this->year = $value;
        return $this;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function month($value)
    {
        $this->month = $value;
        return $this;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function day($value)
    {
        $this->day = $value;
        return $this;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function hour($value)
    {
        $this->hour = $value;
        return $this;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function minute($value)
    {
        $this->minute = $value;
        return $this;
    }

    /**
     * Get the value of a property.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function second($value)
    {
        $this->second = $value;
        return $this;
    }

    /**
     * Set the date and time of this instance.
     *
     * @param int $year
     * @param int $month
     * @param int $day
     * @param int $hour
     * @param int $minute
     * @param int $second
     *
     * @return $this
     */
    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0)
    {
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }

    /**
     * Set the timestamp of this instance.
     *
     * @param int $value
     *
     * @return $this
     */
    public function timestamp($value)
    {
        $this->timestamp = $value;
        return $this;
    }

    /**
     * Set the timezone of this instance.
     *
     * @param string $value
     *
     * @return $this
     */
    public function timezone($value)
    {
        return $this->setTimezone($value);
    }

    /**
     * Set the timezone of this instance.
     *
     * @param string $value
     *
     * @return $this
     */
    public function tz($value)
    {
        return $this->timezone($value);
    }

    /**
     * Set the timezone of this instance.
     *
     * @param string $value
     *
     * @return $this
     */
    #[\ReturnTypeWillChange]
    public function setTimezone($value)
    {
        parent::setTimezone(static::safeCreateDateTimeZone($value));
        return $this;
    }

    /**
     * Set the now timestamp for testing purposes.
     *
     * @param int|null $now
     */
    public static function setNow($now = null)
    {
        static::$now = $now;
    }

    /**
     * Get the current test now timestamp.
     *
     * @return int|null
     */
    public static function getTestNow()
    {
        return static::$now;
    }

    /**
     * Check if a test now timestamp is set.
     *
     * @return bool
     */
     public static function hasTestNow()
    {
        return static::getTestNow() !== null;
    }

    /**
     * Check if a time string contains relative keywords.
     *
     * @param string $time
     *
     * @return bool
     */
    public static function hasRelativeKeywords($time)
    {
        $time = (string) $time;

        if (preg_match('/[0-9]{4}-[0-9]{1,2}-[0-9]{1,2}/', $time) !== 1) {
            foreach (static::$relatives as $keyword) {
                if (stripos($time, $keyword) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Reset the string format to the default.
     */
    public static function resetToStringFormat()
    {
        static::setToStringFormat('Y-m-d H:i:s');
    }

    /**
     * Set the string format.
     *
     * @param string $format
     */
    public static function setToStringFormat($format)
    {
        static::$format = $format;
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->format(static::$format);
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toDateString()
    {
        return $this->format('Y-m-d');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toFormattedDateString()
    {
        return $this->format('M j, Y');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toTimeString()
    {
        return $this->format('H:i:s');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toDateTimeString()
    {
        return $this->format('Y-m-d H:i:s');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toDayDateTimeString()
    {
        return $this->format('D, M j, Y g:i A');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toAtomString()
    {
        return $this->format('Y-m-d\TH:i:sP');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toCookieString()
    {
        return $this->format('l, d-M-Y H:i:s T');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toIso8601String()
    {
        return $this->format('c');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc822String()
    {
        return $this->format('D, d M y H:i:s O');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc850String()
    {
        return $this->format('l, d-M-y H:i:s T');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc1036String()
    {
        return $this->format('D, d M y H:i:s O');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc1123String()
    {
        return $this->format('D, d M Y H:i:s O');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc2822String()
    {
        return $this->format('D, d M Y H:i:s O');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRfc3339String()
    {
        return $this->format('Y-m-d\TH:i:sP');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toRssString()
    {
        return $this->format('D, d M Y H:i:s O');
    }

    /**
     * Convert the instance to a string.
     *
     * @return string
     */
    public function toW3cString()
    {
        return $this->format('Y-m-d\TH:i:sP');
    }

    /**
     * Compare two instances for equality.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function eq(Carbon $dt)
    {
        $this->checkComparator($dt);
        return $this == $dt; // '==' intended for value comparison, not object identity
    }

    /**
     * Compare two instances for inequality.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function ne(Carbon $dt)
    {
        return ! $this->eq($dt);
    }

    /**
     * Compare two instances for greater than.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function gt(Carbon $dt)
    {
        $this->checkComparator($dt);
        return $this > $dt;
    }

    /**
     * Compare two instances for greater than or equal.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function gte(Carbon $dt)
    {
        $this->checkComparator($dt);
        return $this >= $dt;
    }

    /**
     * Compare two instances for less than.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function lt(Carbon $dt)
    {
        $this->checkComparator($dt);
        return $this < $dt;
    }

    /**
     * Compare two instances for less than or equal.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function lte(Carbon $dt)
    {
        $this->checkComparator($dt);
        return $this <= $dt;
    }

    /**
     * Check if the instance is between two other instances.
     *
     * @param Carbon $dt1
     * @param Carbon $dt2
     * @param bool   $equal
     *
     * @return bool
     */
    public function between(Carbon $dt1, Carbon $dt2, $equal = true)
    {
        if ($dt1->gt($dt2)) {
            $temp = $dt1;
            $dt1 = $dt2;
            $dt2 = $temp;
        }

        return $equal ? ($this->gte($dt1) && $this->lte($dt2)) : ($this->gt($dt1) && $this->lt($dt2));
    }

    /**
     * Get the minimum of two instances.
     *
     * @param Carbon|null $dt
     *
     * @return Carbon
     */
    public function min($dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->lt($dt) ? $this : $dt;
    }

    /**
     * Get the maximum of two instances.
     *
     * @param Carbon|null $dt
     *
     * @return Carbon
     */
    public function max($dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->gt($dt) ? $this : $dt;
    }

    /**
     * Check if the instance is a weekday.
     *
     * @return bool
     */
    public function isWeekday()
    {
        return ($this->dayOfWeek !== static::SUNDAY && $this->dayOfWeek !== static::SATURDAY);
    }

    /**
     * Check if the instance is a weekend.
     *
     * @return bool
     */
    public function isWeekend()
    {
        return ! $this->isWeekday();
    }

    /**
     * Check if the instance is yesterday.
     *
     * @return bool
     */
    public function isYesterday()
    {
        /* @disregard */
        return $this->toDateString() === static::yesterday($this->tz)->toDateString();
    }

    /**
     * Check if the instance is today.
     *
     * @return bool
     */
    public function isToday()
    {
        return $this->toDateString() === static::now($this->tz)->toDateString();
    }

    /**
     * Check if the instance is tomorrow.
     *
     * @return bool
     */
    public function isTomorrow()
    {
        /* @disregard */
        return $this->toDateString() === static::tomorrow($this->tz)->toDateString();
    }

    /**
     * Check if the instance is in the future.
     *
     * @return bool
     */
    public function isFuture()
    {
        return $this->gt(static::now($this->tz));
    }

    /**
     * Check if the instance is in the past.
     *
     * @return bool
     */
    public function isPast()
    {
        return $this->lt(static::now($this->tz));
    }

    /**
     * Check if the instance is a leap year.
     *
     * @return bool
     */
    public function isLeapYear()
    {
        return $this->format('L') === '1';
    }

    /**
     * Check if the instance is the same day as another instance.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function isSameDay(Carbon $dt)
    {
        return $this->toDateString() === $dt->toDateString();
    }

    /**
     * Add years to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addYears($value)
    {
        return $this->modify((int) $value.' year');
    }

    /**
     * Add a year to the instance.
     *
     * @return Carbon
     */
    public function addYear()
    {
        return $this->addYears(1);
    }

    /**
     * Subtract a year from the instance.
     *
     * @return Carbon
     */
    public function subYear()
    {
        return $this->addYears(-1);
    }

    /**
     * Subtract years from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subYears($value)
    {
        return $this->addYears(-1 * $value);
    }

    /**
     * Add months to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addMonths($value)
    {
        return $this->modify((int) $value.' month');
    }

    /**
     * Add a month to the instance.
     *
     * @return Carbon
     */
    public function addMonth()
    {
        return $this->addMonths(1);
    }

    /**
     * Subtract a month from the instance.
     *
     * @return Carbon
     */
    public function subMonth()
    {
        return $this->addMonths(-1);
    }

    /**
     * Subtract months from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subMonths($value)
    {
        return $this->addMonths(-1 * $value);
    }

    /**
     * Add months to the instance without overflow.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addMonthsNoOverflow($value)
    {
        $date = $this->copy()->addMonths($value);

        /* @disregard */
        if ($date->day !== $this->day) {
            /* @disregard */
            $date->day(1)->subMonth()->day($date->daysInMonth);
        }

        return $date;
    }

    /**
     * Add a month to the instance without overflow.
     *
     * @return Carbon
     */
    public function addMonthNoOverflow()
    {
        return $this->addMonthsNoOverflow(1);
    }

    /**
     * Subtract a month from the instance without overflow.
     *
     * @return Carbon
     */
    public function subMonthNoOverflow()
    {
        return $this->addMonthsNoOverflow(-1);
    }

    /**
     * Subtract months from the instance without overflow.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subMonthsNoOverflow($value)
    {
        return $this->addMonthsNoOverflow(-1 * $value);
    }

    /**
     * Add days to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addDays($value)
    {
        return $this->modify((int) $value.' day');
    }

    /**
     * Add a day to the instance.
     *
     * @return Carbon
     */
    public function addDay()
    {
        return $this->addDays(1);
    }

    /**
     * Subtract a day from the instance.
     *
     * @return Carbon
     */
    public function subDay()
    {
        return $this->addDays(-1);
    }

    /**
     * Subtract days from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subDays($value)
    {
        return $this->addDays(-1 * $value);
    }

    /**
     * Add weekdays to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addWeekdays($value)
    {
        return $this->modify((int) $value.' weekday');
    }

    /**
     * Add a weekday to the instance.
     *
     * @return Carbon
     */
    public function addWeekday()
    {
        return $this->addWeekdays(1);
    }

    /**
     * Subtract a weekday from the instance.
     *
     * @return Carbon
     */
    public function subWeekday()
    {
        return $this->addWeekdays(-1);
    }

    /**
     * Subtract weekdays from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subWeekdays($value)
    {
        return $this->addWeekdays(-1 * $value);
    }

    /**
     * Add weeks to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addWeeks($value)
    {
        return $this->modify((int) $value.' week');
    }

    /**
     * Add a week to the instance.
     *
     * @return Carbon
     */
    public function addWeek()
    {
        return $this->addWeeks(1);
    }

    /**
     * Subtract a week from the instance.
     *
     * @return Carbon
     */
    public function subWeek()
    {
        return $this->addWeeks(-1);
    }

    /**
     * Subtract weeks from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subWeeks($value)
    {
        return $this->addWeeks(-1 * $value);
    }

    /**
     * Add hours to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addHours($value)
    {
        return $this->modify((int) $value.' hour');
    }

    /**
     * Add an hour to the instance.
     *
     * @return Carbon
     */
    public function addHour()
    {
        return $this->addHours(1);
    }

    /**
     * Subtract an hour from the instance.
     *
     * @return Carbon
     */
    public function subHour()
    {
        return $this->addHours(-1);
    }

    /**
     * Subtract hours from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subHours($value)
    {
        return $this->addHours(-1 * $value);
    }

    /**
     * Add minutes to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addMinutes($value)
    {
        return $this->modify((int) $value.' minute');
    }

    /**
     * Add a minute to the instance.
     *
     * @return Carbon
     */
    public function addMinute()
    {
        return $this->addMinutes(1);
    }

    /**
     * Subtract a minute from the instance.
     *
     * @return Carbon
     */
    public function subMinute()
    {
        return $this->addMinutes(-1);
    }

    /**
     * Subtract minutes from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subMinutes($value)
    {
        return $this->addMinutes(-1 * $value);
    }

    /**
     * Add seconds to the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function addSeconds($value)
    {
        return $this->modify((int) $value.' second');
    }

    /**
     * Add a second to the instance.
     *
     * @return Carbon
     */
    public function addSecond()
    {
        return $this->addSeconds(1);
    }

    /**
     * Subtract a second from the instance.
     *
     * @return Carbon
     */
    public function subSecond()
    {
        return $this->addSeconds(-1);
    }

    /**
     * Subtract seconds from the instance.
     *
     * @param int $value
     *
     * @return Carbon
     */
    public function subSeconds($value)
    {
        return $this->addSeconds(-1 * $value);
    }

    /**
     * Get the difference in years between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInYears($dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return (int) ($this->diff($dt, $abs)->format('%r%y'));
    }

    /**
     * Get the difference in months between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInMonths($dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->diffInYears($dt, $abs) * 12 + $this->diff($dt, $abs)->format('%r%m');
    }

    /**
     * Get the difference in weeks between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInWeeks($dt = null, $abs = true)
    {
        return (int) ($this->diffInDays($dt, $abs) / 7);
    }

    /**
     * Get the difference in days between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInDays($dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return (int) ($this->diff($dt, $abs)->format('%r%a'));
    }

    /**
     * Get the difference in days between two instances, filtered by a callback.
     *
     * @param \Closure $callback
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInDaysFiltered(\Closure $callback, $dt = null, $abs = true)
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
            return call_user_func($callback, static::instance($date));
        });

        $diff = count($days);
        return ($inverse && ! $abs) ? -$diff : $diff;
    }

    /**
     * Get the difference in weekdays between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInWeekdays($dt = null, $abs = true)
    {
        return $this->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $dt, $abs);
    }

    /**
     * Get the difference in weekend days between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInWeekendDays($dt = null, $abs = true)
    {
        return $this->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekend();
        }, $dt, $abs);
    }

    /**
     * Get the difference in hours between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInHours($dt = null, $abs = true)
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 3600);
    }

    /**
     * Get the difference in minutes between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInMinutes($dt = null, $abs = true)
    {
        return (int) ($this->diffInSeconds($dt, $abs) / 60);
    }

    /**
     * Get the difference in seconds between two instances.
     *
     * @param Carbon|null $dt
     * @param bool       $abs
     *
     * @return int
     */
    public function diffInSeconds($dt = null, $abs = true)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        $value = $dt->getTimestamp() - $this->getTimestamp();
        return $abs ? abs($value) : $value;
    }

    /**
     * Get the number of seconds since midnight.
     *
     * @return int
     */
    public function secondsSinceMidnight()
    {
        return $this->diffInSeconds($this->copy()->startOfDay());
    }

    /**
     * Get the number of seconds until the end of the day.
     *
     * @return int
     */
    public function secondsUntilEndOfDay()
    {
        return $this->diffInSeconds($this->copy()->endOfDay());
    }

    /**
     * Get a human-readable representation of the difference between two instances.
     *
     * @param Carbon|null $other
     * @param bool       $absolute
     *
     * @return string
     */
    public function diffForHumans($other = null, $absolute = false)
    {
        $now = $other === null;
        $other = $now ? static::now($this->tz) : $other;
        $future = $this->gt($other);
        $delta = abs($this->diffInSeconds($other, false));
        $unit = 'second';
        $divs = [
            'year' => 31536000, // ~365 days
            'month' => 2628000, // ~30.44 days
            'week' => 604800,   // 7 days
            'day' => 86400,     // 24 hours
            'hour' => 3600,     // 60 minutes
            'minute' => 60,     // 60 minutes
            'second' => 1,      // 1 second
        ];

        foreach ($divs as $u => $d) {
            if ($delta >= $d) {
                $unit = $u;
                $delta = round($delta / $d);
                break;
            }
        }

        $delta = ($delta < 1) ? 1 : $delta;
        $str = $delta.' '.Lang::line('carbon.'.$unit.(($delta <= 1) ? '' : 's'))->get();

        if ($absolute) {
            return $str;
        }

        $now = $now ? ($future ? 'from_now' : 'ago') : ($future ? 'after' : 'before');
        return $str.' '.Lang::line('carbon.'.$now)->get();
    }

    /**
     * Set the time to the start of the day.
     *
     * @return Carbon
     */
    public function startOfDay()
    {
        return $this->hour(0)->minute(0)->second(0);
    }

    /**
     * Set the time to the end of the day.
     *
     * @return Carbon
     */
    public function endOfDay()
    {
        return $this->hour(23)->minute(59)->second(59);
    }

    /**
     * Set the day to the start of the month.
     *
     * @return Carbon
     */
    public function startOfMonth()
    {
        return $this->startOfDay()->day(1);
    }

    /**
     * Set the day to the end of the month.
     *
     * @return Carbon
     */
    public function endOfMonth()
    {
        return $this->day($this->daysInMonth)->endOfDay();
    }

    /**
     * Set the month to the start of the year.
     *
     * @return Carbon
     */
    public function startOfYear()
    {
        return $this->month(1)->startOfMonth();
    }

    /**
     * Set the month to the end of the year.
     *
     * @return Carbon
     */
    public function endOfYear()
    {
        return $this->month(12)->endOfMonth();
    }

    /**
     * Set the year to the start of the decade.
     *
     * @return Carbon
     */
    public function startOfDecade()
    {
        return $this->startOfYear()->year($this->year - $this->year % 10);
    }

    /**
     * Set the year to the end of the decade.
     *
     * @return Carbon
     */
    public function endOfDecade()
    {
        return $this->endOfYear()->year($this->year - $this->year % 10 + 10 - 1);
    }

    /**
     * Set the year to the start of the century.
     *
     * @return Carbon
     */
    public function startOfCentury()
    {
        return $this->startOfYear()->year($this->year - $this->year % 100);
    }

    /**
     * Set the year to the end of the century.
     *
     * @return Carbon
     */
    public function endOfCentury()
    {
        return $this->endOfYear()->year($this->year - $this->year % 100 + 100 - 1);
    }

    /**
     * Set the day to the start of the week.
     *
     * @return Carbon
     */
    public function startOfWeek()
    {
        if ($this->dayOfWeek !== static::MONDAY) {
            $this->previous(static::MONDAY);
        }

        return $this->startOfDay();
    }

    /**
     * Set the day to the end of the week.
     *
     * @return Carbon
     */
    public function endOfWeek()
    {
        if ($this->dayOfWeek !== static::SUNDAY) {
            $this->next(static::SUNDAY);
        }

        return $this->endOfDay();
    }

    /**
     * Get the next occurrence of a given day of the week.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function next($dayOfWeek = null)
    {
        $dayOfWeek = ($dayOfWeek === null) ? $this->dayOfWeek : $dayOfWeek;
        return $this->startOfDay()->modify('next '.static::$days[$dayOfWeek]);
    }

    /**
     * Get the previous occurrence of a given day of the week.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function previous($dayOfWeek = null)
    {
        $dayOfWeek = ($dayOfWeek === null) ? $this->dayOfWeek : $dayOfWeek;
        return $this->startOfDay()->modify('last '.static::$days[$dayOfWeek]);
    }

    /**
     * Get the first occurrence of a given day of the week in the current month.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function firstOfMonth($dayOfWeek = null)
    {
        $this->startOfDay();
        return ($dayOfWeek === null)
            ? $this->day(1)
            : $this->modify('first '.static::$days[$dayOfWeek].' of '.$this->format('F').' '.$this->year);
    }

    /**
     * Get the last occurrence of a given day of the week in the current month.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function lastOfMonth($dayOfWeek = null)
    {
        $this->startOfDay();
        return ($dayOfWeek === null)
            ? $this->day($this->daysInMonth)
            : $this->modify('last '.static::$days[$dayOfWeek].' of '.$this->format('F').' '.$this->year);
    }

    /**
     * Get the nth occurrence of a given day of the week in the current month.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return Carbon|false
     */
    public function nthOfMonth($nth, $dayOfWeek)
    {
        $dt = $this->copy()->firstOfMonth();
        $dt2 = $dt->format('Y-m');
        $dt->modify('+'.$nth.' '.static::$days[$dayOfWeek]);

        return ($dt->format('Y-m') === $dt2) ? $this->modify($dt->format('Y-m-d H:i:s')) : false;
    }

    /**
     * Get the first occurrence of a given day of the week in the current quarter.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function firstOfQuarter($dayOfWeek = null)
    {
        return $this->day(1)->month($this->quarter * 3 - 2)->firstOfMonth($dayOfWeek);
    }

    /**
     * Get the last occurrence of a given day of the week in the current quarter.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function lastOfQuarter($dayOfWeek = null)
    {
        return $this->day(1)->month($this->quarter * 3)->lastOfMonth($dayOfWeek);
    }

    /**
     * Get the nth occurrence of a given day of the week in the current quarter.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return Carbon|false
     */
    public function nthOfQuarter($nth, $dayOfWeek)
    {
        $dt = $this->copy()->day(1)->month($this->quarter * 3);
        $lastMonth = $dt->month;
        $year = $dt->year;
        $dt->firstOfQuarter()->modify('+'.$nth.' '.static::$days[$dayOfWeek]);

        return ($lastMonth < $dt->month || $year !== $dt->year) ? false : $this->modify($dt->format('Y-m-d H:i:s'));
    }

    /**
     * Get the first occurrence of a given day of the week in the current year.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function firstOfYear($dayOfWeek = null)
    {
        return $this->month(1)->firstOfMonth($dayOfWeek);
    }

    /**
     * Get the last occurrence of a given day of the week in the current year.
     *
     * @param int|null $dayOfWeek
     *
     * @return Carbon
     */
    public function lastOfYear($dayOfWeek = null)
    {
        return $this->month(12)->lastOfMonth($dayOfWeek);
    }

    /**
     * Get the nth occurrence of a given day of the week in the current year.
     *
     * @param int $nth
     * @param int $dayOfWeek
     *
     * @return Carbon|false
     */
    public function nthOfYear($nth, $dayOfWeek)
    {
        $dt = $this->copy()->firstOfYear()->modify('+'.$nth.' '.static::$days[$dayOfWeek]);
        /* @disregard */
        return ($this->year === $dt->year) ? $this->modify($dt->format('Y-m-d H:i:s')) : false;
    }

    /**
     * Get the average of two dates.
     *
     * @param Carbon|null $dt
     *
     * @return Carbon
     */
    public function average($dt = null)
    {
        $dt = ($dt === null) ? static::now($this->tz) : $dt;
        return $this->addSeconds((int) ($this->diffInSeconds($dt, false) / 2));
    }

    /**
     * Check if the date is a birthday.
     *
     * @param Carbon $dt
     *
     * @return bool
     */
    public function isBirthday(Carbon $dt)
    {
        return $this->format('md') === $dt->format('md');
    }

    /**
     * Check if the date is a birthday.
     *
     * @param
     *
     * @return
     */

    private function checkComparator($dt)
    {
        if ($dt === null || is_bool($dt)) {
            throw new \Exception('Cannot compare with null or boolean value');
        }
    }
}
