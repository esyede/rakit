<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Date extends \DateTime
{
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    public function __construct($time = null, $timezone = null)
    {
        $timezone = $timezone ? $timezone : Config::get('application.timezone', 'UTC');
        $timezone = self::safeCreateDateTimeZone($timezone);

        parent::__construct($time, $timezone);
    }

    public static function instance(\DateTime $date)
    {
        return new self($date->format('Y-m-d H:i:s'), $date->getTimeZone());
    }

    public static function now($timezone = null)
    {
        if (null !== $timezone) {
            return new self(null, self::safeCreateDateTimeZone($timezone));
        }

        return new self();
    }

    public static function create(
        $year = null,
        $month = null,
        $day = null,
        $hour = null,
        $minute = null,
        $second = null,
        $timezone = null
    ) {
        $year = (null === $year) ? date('Y') : $year;
        $month = (null === $month) ? date('n') : $month;
        $day = (null === $day) ? date('j') : $day;

        if (null === $hour) {
            $hour = date('G');
            $minute = (null === $minute) ? date('i') : $minute;
            $second = (null === $second) ? date('s') : $second;
        } else {
            $minute = (null === $minute) ? 0 : $minute;
            $second = (null === $second) ? 0 : $second;
        }

        return self::createFromFormat(
            'Y-n-j G:i:s',
            sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second),
            $timezone
        );
    }

    public static function createFromDate($year = null, $month = null, $day = null, $timezone = null)
    {
        return self::create($year, $month, $day, null, null, null, $timezone);
    }

    public static function createFromTime($hour = null, $minute = null, $second = null, $timezone = null)
    {
        return self::create(null, null, null, $hour, $minute, $second, $timezone);
    }

    public static function createFromFormat($format, $time, $object = null)
    {
        if (null !== $object) {
            $date = parent::createFromFormat($format, $time, self::safeCreateDateTimeZone($object));
        } else {
            $date = parent::createFromFormat($format, $time);
        }

        if ($date instanceof \DateTime) {
            return self::instance($date);
        }

        $errors = \DateTime::getLastErrors();
        throw new \Exception(implode(PHP_EOL, $errors['errors']));
    }

    public static function createFromTimestamp($timestamp, $timezone = null)
    {
        return self::now($timezone)->setTimestamp($timestamp);
    }

    public static function createFromTimestampUTC($timestamp)
    {
        return new self('@'.$timestamp);
    }

    public function copy()
    {
        return self::instance($this);
    }

    public function __get($name)
    {
        switch ($name) {
            case 'year':         return (int) $this->format('Y');
            case 'month':        return (int) $this->format('n');
            case 'day':          return (int) $this->format('j');
            case 'hour':         return (int) $this->format('G');
            case 'minute':       return (int) $this->format('i');
            case 'second':       return (int) $this->format('s');
            case 'dayOfWeek':    return (int) $this->format('w');
            case 'dayOfYear':    return (int) $this->format('z');
            case 'weekOfYear':   return (int) $this->format('W');
            case 'daysInMonth':  return (int) $this->format('t');
            case 'timestamp':    return (int) $this->format('U');
            case 'age':          return (int) $this->diffInYears();
            case 'quarter':      return (int) (($this->month - 1) / 3) + 1;
            case 'offset':       return $this->getOffset();
            case 'offsetHours':  return $this->getOffset() / 3600;
            case 'dst':          return '1' === $this->format('I');
            case 'timezone':     return $this->getTimezone();
            case 'timezoneName': return $this->getTimezone()->getName();
            case 'tz':           return $this->timezone;
            case 'tzName':       return $this->timezoneName;
            default:             throw new \Exception(sprintf('Unknown date getter: %s', $name));
        }
    }

    public function __set($name, $value)
    {
        $handled = true;

        switch ($name) {
            case 'year':      parent::setDate($value, $this->month, $this->day); break;
            case 'month':     parent::setDate($this->year, $value, $this->day); break;
            case 'day':       parent::setDate($this->year, $this->month, $value); break;
            case 'hour':      parent::setTime($value, $this->minute, $this->second); break;
            case 'minute':    parent::setTime($this->hour, $value, $this->second); break;
            case 'second':    parent::setTime($this->hour, $this->minute, $value); break;
            case 'timestamp': parent::setTimestamp($value); break;
            case 'timezone':  $this->setTimezone($value); break;
            case 'tz':        $this->setTimezone($value); break;
            default:          $handled = false; break;
        }

        if (! $handled) {
            throw new \Exception(sprintf('Unknown date setter: %s', $name));
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

    public function setDate($year, $month, $day)
    {
        return $this->year($year)->month($month)->day($day);
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

    public function setTime($hour, $minute, $second = null, $microseconds = null)
    {
        return $this->hour($hour)->minute($minute)->second(is_null($second) ? 0 : $second);
    }

    public function setDateTime($year, $month, $day, $hour, $minute, $second)
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

    public function setTimezone($value)
    {
        parent::setTimezone(self::safeCreateDateTimeZone($value));

        return $this;
    }

    public function __toString()
    {
        return $this->toDateTimeString();
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
        return $this->format(\DateTime::ATOM);
    }

    public function toCookieString()
    {
        return $this->format(\DateTime::COOKIE);
    }

    public function toIso8601String()
    {
        return $this->format(\DateTime::ISO8601);
    }

    public function toRfc822String()
    {
        return $this->format(\DateTime::RFC822);
    }

    public function toRfc850String()
    {
        return $this->format(\DateTime::RFC850);
    }

    public function toRfc1036String()
    {
        return $this->format(\DateTime::RFC1036);
    }

    public function toRfc1123String()
    {
        return $this->format(\DateTime::RFC1123);
    }

    public function toRfc2822String()
    {
        return $this->format(\DateTime::RFC2822);
    }

    public function toRfc3339String()
    {
        return $this->format(\DateTime::RFC3339);
    }

    public function toRssString()
    {
        return $this->format(\DateTime::RSS);
    }

    public function toW3cString()
    {
        return $this->format(\DateTime::W3C);
    }

    public function eq(Date $date)
    {
        return $this === $date;
    }

    public function ne(Date $date)
    {
        return ! $this->eq($date);
    }

    public function gt(Date $date)
    {
        return $this > $date;
    }

    public function gte(Date $date)
    {
        return $this >= $date;
    }

    public function lt(Date $date)
    {
        return $this < $date;
    }

    public function lte(Date $date)
    {
        return $this <= $date;
    }

    public function isWeekday()
    {
        return self::SUNDAY !== (int) $this->dayOfWeek && self::SATURDAY !== (int) $this->dayOfWeek;
    }

    public function isWeekend()
    {
        return ! $this->isWeekDay();
    }

    public function isYesterday()
    {
        return $this->toDateString() === self::now($this->tz)->subDay()->toDateString();
    }

    public function isToday()
    {
        return $this->toDateString() === self::now($this->tz)->toDateString();
    }

    public function isTomorrow()
    {
        return $this->toDateString() === self::now($this->tz)->addDay()->toDateString();
    }

    public function isFuture()
    {
        return $this->gt(self::now($this->tz));
    }

    public function isPast()
    {
        return ! $this->isFuture();
    }

    public function isLeapYear()
    {
        return '1' === $this->format('L');
    }

    public function addYears($value)
    {
        $interval = new \DateInterval(sprintf('P%dY', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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
        $interval = new \DateInterval(sprintf('P%dM', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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

    public function addDays($value)
    {
        $interval = new \DateInterval(sprintf('P%dD', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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
        $absolute = abs($value);
        $direction = ($value < 0) ? -1 : 1;

        while ($absolute > 0) {
            $this->addDays($direction);

            while ($this->isWeekend()) {
                $this->addDays($direction);
            }

            --$absolute;
        }

        return $this;
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
        $interval = new \DateInterval(sprintf('P%dW', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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
        $interval = new \DateInterval(sprintf('PT%dH', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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
        $interval = new \DateInterval(sprintf('PT%dM', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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
        $interval = new \DateInterval(sprintf('PT%dS', abs($value)));

        if ($value >= 0) {
            $this->add($interval);
        } else {
            $this->sub($interval);
        }

        return $this;
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

    public function diffInYears(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;
        $sign = $absolute ? '' : '%r';

        return (int) ($this->diff($date)->format($sign.'%y'));
    }

    public function diffInMonths(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;
        list($sign, $years, $months) = explode(':', $this->diff($date)->format('%r:%y:%m'));
        $value = ($years * 12) + $months;

        if ('-' === $sign && ! $absolute) {
            $value = $value * -1;
        }

        return $value;
    }

    public function diffInDays(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;
        $sign = ($absolute) ? '' : '%r';

        return (int) ($this->diff($date)->format($sign.'%a'));
    }

    public function diffInHours(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;

        return (int) ($this->diffInMinutes($date, $absolute) / 60);
    }

    public function diffInMinutes(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;

        return (int) ($this->diffInSeconds($date, $absolute) / 60);
    }

    public function diffInSeconds(Date $date = null, $absolute = true)
    {
        $date = is_null($date) ? Date::now($this->tz) : $date;
        $string = $this->diff($date)->format('%r:%a:%h:%i:%s');

        list($sign, $days, $hours, $minutes, $seconds) = explode(':', $string);

        $value = ($days * 86400) + ($hours * 3600) + ($minutes * 60) + $seconds;

        if ('-' === $sign && ! $absolute) {
            $value = $value * -1;
        }

        return (int) $value;
    }

    public function diffForHumans(Date $other = null)
    {
        $str = '';
        $isNow = (null === $other);

        if ($isNow) {
            $other = self::now();
        }

        $isFuture = $this->gt($other);
        $delta = abs($other->diffInSeconds($this));

        // NOTE: Operasi pecahan di PHP tanpa bantuan BCMath kurang bisa diandalkan
        // Jadi jumlah hari saya bulatkan sesuai aturan berikut:
        // 1 bulan selalu berisi 30 hari
        // 1 tahun selalu berisi 365 hari.

        $units = ['second' => 60, 'minute' => 60, 'hour' => 24, 'day' => 30, 'month' => 12];
        $unit = 'year';

        foreach ($units as $key => $value) {
            if ($delta < $value) {
                $unit = $key;
                break;
            }

            $delta = floor($delta / $value);
        }

        $delta = (0 === $delta) ? 1 : $delta;
        $str = $delta.' '.Lang::line('date.'.$unit)->get(); // 1 year(s)

        if ($isNow) {
            return Lang::line('date.'.($isFuture ? 'from_now' : 'ago'))->get();
        } elseif ($isFuture) {
            return $str.' '.Lang::line('date.after')->get();
        }
        return $str.' '.Lang::line('date.before')->get();
    }

    protected static function safeCreateDateTimeZone($object)
    {
        if ($object instanceof \DateTimeZone) {
            return $object;
        }

        try {
            $timezone = timezone_open((string) $object);
            return $timezone;
        } catch (\Throwable $e) {
            throw \Exception(sprintf('Unknown or invalid timezone: %s', $object));
        } catch (\Exception $e) {
            throw \Exception(sprintf('Unknown or invalid timezone: %s', $object));
        }
    }
}
