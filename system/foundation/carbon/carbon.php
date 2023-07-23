<?php

namespace System\Foundation\Carbon;

use Symfony\Component\Translation\TranslatorInterface;

class Carbon extends \DateTime implements \JsonSerializable
{
    const NO_ZERO_DIFF = 01;
    const JUST_NOW = 02;
    const ONE_DAY_WORDS = 04;
    const TWO_DAY_WORDS = 010;
    const DIFF_RELATIVE_TO_NOW = 'relative-to-now';
    const DIFF_RELATIVE_TO_OTHER = 'relative-to-other';
    const SUNDAY = 0;
    const MONDAY = 1;
    const TUESDAY = 2;
    const WEDNESDAY = 3;
    const THURSDAY = 4;
    const FRIDAY = 5;
    const SATURDAY = 6;

    protected static $toStringFormat = 'Y-m-d H:i:s';
    protected static $weekStartsAt = self::MONDAY;
    protected static $weekEndsAt = self::SUNDAY;
    protected static $weekendDays = [self::SATURDAY, self::SUNDAY];
    protected static $days = [
        self::SUNDAY => 'Sunday',
        self::MONDAY => 'Monday',
        self::TUESDAY => 'Tuesday',
        self::WEDNESDAY => 'Wednesday',
        self::THURSDAY => 'Thursday',
        self::FRIDAY => 'Friday',
        self::SATURDAY => 'Saturday',
    ];

    protected static $midDayAt = 12;
    protected static $regexFormats = [
        'd' => '(3[01]|[12][0-9]|0[1-9])',
        'D' => '([a-zA-Z]{3})',
        'j' => '([123][0-9]|[1-9])',
        'l' => '([a-zA-Z]{2,})',
        'N' => '([1-7])',
        'S' => '([a-zA-Z]{2})',
        'w' => '([0-6])',
        'z' => '(36[0-5]|3[0-5][0-9]|[12][0-9]{2}|[1-9]?[0-9])',
        'W' => '(5[012]|[1-4][0-9]|[1-9])',
        'F' => '([a-zA-Z]{2,})',
        'm' => '(1[012]|0[1-9])',
        'M' => '([a-zA-Z]{3})',
        'n' => '(1[012]|[1-9])',
        't' => '(2[89]|3[01])',
        'L' => '(0|1)',
        'o' => '([1-9][0-9]{0,4})',
        'Y' => '([1-9]?[0-9]{4})',
        'y' => '([0-9]{2})',
        'a' => '(am|pm)',
        'A' => '(AM|PM)',
        'B' => '([0-9]{3})',
        'g' => '(1[012]|[1-9])',
        'G' => '(2[0-3]|1?[0-9])',
        'h' => '(1[012]|0[1-9])',
        'H' => '(2[0-3]|[01][0-9])',
        'i' => '([0-5][0-9])',
        's' => '([0-5][0-9])',
        'u' => '([0-9]{1,6})',
        'v' => '([0-9]{1,3})',
        'e' => '([a-zA-Z]{1,5})|([a-zA-Z]*\/[a-zA-Z]*)',
        'I' => '(0|1)',
        'O' => '([\+\-](1[012]|0[0-9])[0134][05])',
        'P' => '([\+\-](1[012]|0[0-9]):[0134][05])',
        'T' => '([a-zA-Z]{1,5})',
        'Z' => '(-?[1-5]?[0-9]{1,4})',
        'U' => '([0-9]*)',
        'c' => '(([1-9]?[0-9]{4})\-(1[012]|0[1-9])\-(3[01]|[12][0-9]|0[1-9])T(2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9])[\+\-](1[012]|0[0-9]):([0134][05]))',
        'r' => '(([a-zA-Z]{3}), ([123][0-9]|[1-9]) ([a-zA-Z]{3}) ([1-9]?[0-9]{4}) (2[0-3]|[01][0-9]):([0-5][0-9]):([0-5][0-9]) [\+\-](1[012]|0[0-9])([0134][05]))',
    ];

    protected static $testNow;
    protected static $translator;
    protected static $lastErrors;
    protected static $serializer;
    protected static $localMacros = [];
    protected static $utf8 = false;
    protected static $microsecondsFallback = true;
    protected static $monthsOverflow = true;
    protected static $yearsOverflow = true;
    protected static $compareYearWithMonth = false;
    protected static $humanDiffOptions = self::NO_ZERO_DIFF;

    public static function setHumanDiffOptions($options)
    {
        static::$humanDiffOptions = $options;
    }

    public static function enableHumanDiffOption($options)
    {
        static::$humanDiffOptions = static::getHumanDiffOptions() | $options;
    }

    public static function disableHumanDiffOption($options)
    {
        static::$humanDiffOptions = static::getHumanDiffOptions() & ~$options;
    }

    public static function getHumanDiffOptions()
    {
        return static::$humanDiffOptions;
    }

    public static function useMicrosecondsFallback($active = true)
    {
        static::$microsecondsFallback = $active;
    }

    public static function isMicrosecondsFallbackEnabled()
    {
        return static::$microsecondsFallback;
    }

    public static function useMonthsOverflow($active = true)
    {
        static::$monthsOverflow = $active;
    }

    public static function resetMonthsOverflow()
    {
        static::$monthsOverflow = true;
    }

    public static function shouldOverflowMonths()
    {
        return static::$monthsOverflow;
    }

    public static function useYearsOverflow($active = true)
    {
        static::$yearsOverflow = $active;
    }

    public static function resetYearsOverflow()
    {
        static::$yearsOverflow = true;
    }

    public static function shouldOverflowYears()
    {
        return static::$yearsOverflow;
    }

    public static function compareYearWithMonth($active = true)
    {
        static::$compareYearWithMonth = $active;
    }

    public static function shouldCompareYearWithMonth()
    {
        return static::$compareYearWithMonth;
    }

    protected static function safeCreateDateTimeZone($object)
    {
        if ($object === null) {
            return new \DateTimeZone(date_default_timezone_get());
        }

        if ($object instanceof \DateTimeZone) {
            return $object;
        }

        if (is_numeric($object)) {
            $tzName = timezone_name_from_abbr('', $object * 3600, true);

            if ($tzName === false) {
                throw new \Exception(sprintf('Unknown or bad timezone: %s', $object));
            }

            $object = $tzName;
        }

        $tz = @timezone_open($object = (string) $object);

        if ($tz !== false) {
            return $tz;
        }

        if (strpos($object, ':') !== false) {
            try {
                return static::createFromFormat('O', $object)->getTimezone();
            } catch (\Throwable $e) {
                // skip
            } catch (\Exception $e) {
                // skip
            }
        }

        throw new \Exception(sprintf('Unknown or bad timezone: %s', $object));
    }

    public function __construct($time = null, $tz = null)
    {
        $isNow = empty($time) || $time === 'now';

        if (static::hasTestNow() && ($isNow || static::hasRelativeKeywords($time))) {
            $clone = clone static::getTestNow();

            if ($tz !== null && $tz !== static::getTestNow()->getTimezone()) {
                $clone->setTimezone($tz);
            } else {
                $tz = $clone->getTimezone();
            }

            if (static::hasRelativeKeywords($time)) {
                $clone->modify($time);
            }

            $time = $clone->format('Y-m-d H:i:s.u');
        }

        $timezone = static::safeCreateDateTimeZone($tz);

        if (
            $isNow
            && !isset($clone)
            && static::isMicrosecondsFallbackEnabled()
            && (version_compare(PHP_VERSION, '7.1.0-dev', '<') || version_compare(PHP_VERSION, '7.1.3-dev', '>=')
                && version_compare(PHP_VERSION, '7.1.4-dev', '<'))
        ) {

            list($microTime, $timeStamp) = explode(' ', microtime());
            $dateTime = new \DateTime('now', $timezone);
            $dateTime->setTimestamp($timeStamp);
            $time = $dateTime->format('Y-m-d H:i:s') . substr($microTime, 1, 7);
        }

        if (strpos((string) .1, '.') === false) {
            $locale = setlocale(LC_NUMERIC, '0');
            setlocale(LC_NUMERIC, 'C');
        }

        parent::__construct($time, $timezone);

        if (isset($locale)) {
            setlocale(LC_NUMERIC, $locale);
        }

        static::setLastErrors(parent::getLastErrors());
    }

    public static function instance($date)
    {
        if ($date instanceof static) {
            return clone $date;
        }

        static::expectDateTime($date);
        return new static($date->format('Y-m-d H:i:s.u'), $date->getTimezone());
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
        return static::parse('today', $tz);
    }

    public static function tomorrow($tz = null)
    {
        return static::parse('tomorrow', $tz);
    }

    public static function yesterday($tz = null)
    {
        return static::parse('yesterday', $tz);
    }

    public static function maxValue()
    {
        if (PHP_INT_SIZE === 4) {
            return static::createFromTimestamp(PHP_INT_MAX);
        }

        return static::create(9999, 12, 31, 23, 59, 59);
    }

    public static function minValue()
    {
        if (PHP_INT_SIZE === 4) {
            return static::createFromTimestamp(~PHP_INT_MAX);
        }

        return static::create(1, 1, 1, 0, 0, 0);
    }

    public static function create($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
    {
        $now = static::hasTestNow() ? static::getTestNow() : static::now($tz);
        $defaults = array_combine(
            ['year', 'month', 'day', 'hour', 'minute', 'second'],
            explode('-', $now->format('Y-n-j-G-i-s'))
        );

        $year = ($year === null) ? $defaults['year'] : $year;
        $month = ($month === null) ? $defaults['month'] : $month;
        $day = ($day === null) ? $defaults['day'] : $day;

        if ($hour === null) {
            $hour = $defaults['hour'];
            $minute = ($minute === null) ? $defaults['minute'] : $minute;
            $second = ($second === null) ? $defaults['second'] : $second;
        } else {
            $minute = ($minute === null) ? 0 : $minute;
            $second = ($second === null) ? 0 : $second;
        }

        $fixYear = null;

        if ($year < 0) {
            $fixYear = $year;
            $year = 0;
        } elseif ($year > 9999) {
            $fixYear = $year - 9999;
            $year = 9999;
        }

        $instance = static::createFromFormat(
            '!Y-n-j G:i:s',
            sprintf('%s-%s-%s %s:%02s:%02s', $year, $month, $day, $hour, $minute, $second),
            $tz
        );

        if ($fixYear !== null) {
            $instance->addYears($fixYear);
        }

        return $instance;
    }

    public static function createSafe($year = null, $month = null, $day = null, $hour = null, $minute = null, $second = null, $tz = null)
    {
        $fields = [
            'year' => [0, 9999],
            'month' => [0, 12],
            'day' => [0, 31],
            'hour' => [0, 24],
            'minute' => [0, 59],
            'second' => [0, 59],
        ];

        foreach ($fields as $field => $range) {
            if ($$field !== null && (!is_int($$field) || $$field < $range[0] || $$field > $range[1])) {
                throw new \Exception($field, $$field);
            }
        }

        $instance = static::create($year, $month, $day, $hour, $minute, $second, $tz);

        foreach (array_reverse($fields) as $field => $range) {
            if ($$field !== null && (!is_int($$field) || $$field !== $instance->$field)) {
                throw new \Exception($field, $$field);
            }
        }

        return $instance;
    }

    public static function createFromDate($year = null, $month = null, $day = null, $tz = null)
    {
        return static::create($year, $month, $day, null, null, null, $tz);
    }

    public static function createMidnightDate($year = null, $month = null, $day = null, $tz = null)
    {
        return static::create($year, $month, $day, 0, 0, 0, $tz);
    }

    public static function createFromTime($hour = null, $minute = null, $second = null, $tz = null)
    {
        return static::create(null, null, null, $hour, $minute, $second, $tz);
    }

    public static function createFromTimeString($time, $tz = null)
    {
        return static::today($tz)->setTimeFromTimeString($time);
    }

    private static function createFromFormatAndTimezone($format, $time, $tz)
    {
        return ($tz !== null)
            ? parent::createFromFormat($format, $time, static::safeCreateDateTimeZone($tz))
            : parent::createFromFormat($format, $time);
    }

    #[\ReturnTypeWillChange]
    public static function createFromFormat($format, $time, $tz = null)
    {

        $date = self::createFromFormatAndTimezone($format, $time, $tz);
        $lastErrors = parent::getLastErrors();

        if (($mock = static::getTestNow()) && (($date instanceof \DateTime) || ($date instanceof \DateTimeInterface))) {
            $nonIgnored = preg_replace("/^.*(?<!\\\\)(\\\\{2})*!/s", '', $format);

            if ($tz === null && !preg_match("/(?<!\\\\)(\\\\{2})*[eOPT]/", $nonIgnored)) {
                $tz = $mock->getTimezone();
            }

            if (!preg_match("/(?<!\\\\)(\\\\{2})*[!|]/", $format)) {
                $format = 'Y-m-d H:i:s.u ' . $format;
                $time = $mock->format('Y-m-d H:i:s.u') . ' ' . $time;
            }

            $date = self::createFromFormatAndTimezone($format, $time, $tz);
        }

        if (($date instanceof \DateTime) || ($date instanceof \DateTimeInterface)) {
            $instance = static::instance($date);
            $instance::setLastErrors($lastErrors);
            return $instance;
        }

        throw new \Exception(implode(PHP_EOL, $lastErrors['errors']));
    }

    private static function setLastErrors(array $errors)
    {
        static::$lastErrors = $errors;
    }

    #[\ReturnTypeWillChange]
    public static function getLastErrors()
    {
        return static::$lastErrors;
    }

    public static function createFromTimestamp($timestamp, $tz = null)
    {
        return static::today($tz)->setTimestamp($timestamp);
    }

    public static function createFromTimestampMs($timestamp, $tz = null)
    {
        return static::createFromFormat('U.u', sprintf('%F', $timestamp / 1000))->setTimezone($tz);
    }

    public static function createFromTimestampUTC($timestamp)
    {
        return new static('@' . $timestamp);
    }

    public static function make($var)
    {
        if (($var instanceof \DateTime) || ($var instanceof \DateTimeInterface)) {
            return static::instance($var);
        }

        if (is_string($var)) {
            $var = trim($var);
            $first = substr($var, 0, 1);

            if (is_string($var) && $first !== 'P' && $first !== 'R' && preg_match('/[a-z0-9]/i', $var)) {
                return static::parse($var);
            }
        }
    }

    public function copy()
    {
        return clone $this;
    }

    public function nowWithSameTz()
    {
        return static::now($this->getTimezone());
    }

    protected static function expectDateTime($date, $other = [])
    {
        $message = 'Expected ';
        $other = (array) $other;

        foreach ($other as $expect) {
            $message .= $expect . ', ';
        }

        if (!($date instanceof \DateTime) && !($date instanceof \DateTimeInterface)) {
            throw new \Exception(sprintf(
                '%sDateTime or DateTimeInterface, %s given',
                $message,
                is_object($date) ? get_class($date) : gettype($date)
            ));
        }
    }

    protected function resolveCarbon($date = null)
    {
        if (!$date) {
            return $this->nowWithSameTz();
        }

        if (is_string($date)) {
            return static::parse($date, $this->getTimezone());
        }

        static::expectDateTime($date, ['null', 'string']);
        return ($date instanceof self) ? $date : static::instance($date);
    }

    public function __get($name)
    {
        static $formats = [
            'year' => 'Y',
            'yearIso' => 'o',
            'month' => 'n',
            'day' => 'j',
            'hour' => 'G',
            'minute' => 'i',
            'second' => 's',
            'micro' => 'u',
            'dayOfWeek' => 'w',
            'dayOfWeekIso' => 'N',
            'dayOfYear' => 'z',
            'weekOfYear' => 'W',
            'daysInMonth' => 't',
            'timestamp' => 'U',
            'englishDayOfWeek' => 'l',
            'shortEnglishDayOfWeek' => 'D',
            'englishMonth' => 'F',
            'shortEnglishMonth' => 'M',
            'localeDayOfWeek' => '%A',
            'shortLocaleDayOfWeek' => '%a',
            'localeMonth' => '%B',
            'shortLocaleMonth' => '%b',
        ];

        switch (true) {
            case isset($formats[$name]):
                $format = $formats[$name];
                $method = (substr($format, 0, 1) === '%') ? 'formatLocalized' : 'format';
                $value = $this->$method($format);
                return is_numeric($value) ? (int) $value : $value;

            case ($name === 'weekOfMonth'):
                return (int) ceil($this->day / 7);

            case ($name === 'weekNumberInMonth'):
                return (int) ceil(($this->day + $this->copy()->startOfMonth()->dayOfWeek - 1) / 7);

            case ($name === 'age'):
                return $this->diffInYears();

            case ($name === 'quarter'):
                return (int) ceil($this->month / 3);

            case ($name === 'offset'):
                return $this->getOffset();

            case ($name === 'offsetHours'):
                return $this->getOffset() / 3600;

            case ($name === 'dst'):
                return $this->format('I') === '1';

            case ($name === 'local'):
                return $this->getOffset() === $this->copy()->setTimezone(date_default_timezone_get())->getOffset();

            case ($name === 'utc'):
                return $this->getOffset() === 0;

            case ($name === 'timezone' || $name === 'tz'):
                return $this->getTimezone();

            case ($name === 'timezoneName' || $name === 'tzName'):
                return $this->getTimezone()->getName();

            default:
                throw new \Exception(sprintf('Unknown getter: %s', $name));
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
            case 'month':
            case 'day':
            case 'hour':
            case 'minute':
            case 'second':
                list($year, $month, $day, $hour, $minute, $second) = explode('-', $this->format('Y-n-j-G-i-s'));
                $$name = $value;
                $this->setDateTime($year, $month, $day, $hour, $minute, $second);
                break;

            case 'timestamp':
                parent::setTimestamp($value);
                break;

            case 'timezone':
            case 'tz':
                $this->setTimezone($value);
                break;

            default:
                throw new \Exception(sprintf('Unknown setter: %s', $name));
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

    #[\ReturnTypeWillChange]
    public function setDate($year, $month, $day)
    {
        $this->modify('+0 day');
        return parent::setDate($year, $month, $day);
    }

    public function setDateTime($year, $month, $day, $hour, $minute, $second = 0)
    {
        return $this->setDate($year, $month, $day)->setTime($hour, $minute, $second);
    }

    public function setTimeFromTimeString($time)
    {
        if (strpos($time, ':') === false) {
            $time .= ':0';
        }

        return $this->modify($time);
    }

    public function timestamp($value)
    {
        return $this->setTimestamp($value);
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
        $this->getTimestamp();
        return $this;
    }

    public function setDateFrom($date)
    {
        $date = static::instance($date);
        $this->setDate($date->year, $date->month, $date->day);
        return $this;
    }

    public function setTimeFrom($date)
    {
        $date = static::instance($date);
        $this->setTime($date->hour, $date->minute, $date->second);
        return $this;
    }

    public static function getDays()
    {
        return static::$days;
    }

    public static function getWeekStartsAt()
    {
        return static::$weekStartsAt;
    }

    public static function setWeekStartsAt($day)
    {
        if ($day > static::SATURDAY || $day < static::SUNDAY) {
            throw new \Exception('Day of a week should be greater than or equal to 0 and less than or equal to 6.');
        }

        static::$weekStartsAt = $day;
    }

    public static function getWeekEndsAt()
    {
        return static::$weekEndsAt;
    }

    public static function setWeekEndsAt($day)
    {
        if ($day > static::SATURDAY || $day < static::SUNDAY) {
            throw new \Exception('Day of a week should be greater than or equal to 0 and less than or equal to 6.');
        }

        static::$weekEndsAt = $day;
    }

    public static function getWeekendDays()
    {
        return static::$weekendDays;
    }

    public static function setWeekendDays($days)
    {
        static::$weekendDays = $days;
    }

    public static function getMidDayAt()
    {
        return static::$midDayAt;
    }

    public static function setMidDayAt($hour)
    {
        static::$midDayAt = $hour;
    }

    public static function setTestNow($testNow = null)
    {
        static::$testNow = is_string($testNow) ? static::parse($testNow) : $testNow;
    }

    public static function getTestNow()
    {
        return static::$testNow;
    }

    public static function hasTestNow()
    {
        return static::getTestNow() !== null;
    }

    public static function hasRelativeKeywords($time)
    {
        if (strtotime($time) === false) {
            return false;
        }

        $date1 = new \DateTime('2000-01-01T00:00:00Z');
        $date1->modify($time);
        $date2 = new \DateTime('2001-12-25T00:00:00Z');
        $date2->modify($time);

        return $date1 != $date2;
    }

    protected static function translator()
    {
        if (static::$translator === null) {
            static::$translator = Translator::get();
        }

        return static::$translator;
    }

    public static function getTranslator()
    {
        return static::translator();
    }

    public static function setTranslator(TranslatorInterface $translator)
    {
        static::$translator = $translator;
    }

    public static function getLocale()
    {
        return static::translator()->getLocale();
    }

    public static function setLocale($locale)
    {
        return static::translator()->setLocale($locale) !== false;
    }

    public static function executeWithLocale($locale, $func)
    {
        $currentLocale = static::getLocale();
        $result = call_user_func($func, static::setLocale($locale) ? static::getLocale() : false, static::translator());
        static::setLocale($currentLocale);

        return $result;
    }

    public static function localeHasShortUnits($locale)
    {
        return static::executeWithLocale($locale, function ($newLocale, TranslatorInterface $translator) {
            return $newLocale &&
                (
                    ($y = $translator->trans('y')) !== 'y' &&
                    $y !== $translator->trans('year')
                ) || (
                    ($y = $translator->trans('d')) !== 'd' &&
                    $y !== $translator->trans('day')
                ) || (
                    ($y = $translator->trans('h')) !== 'h' &&
                    $y !== $translator->trans('hour')
                );
        });
    }

    public static function localeHasDiffSyntax($locale)
    {
        return static::executeWithLocale($locale, function ($newLocale, TranslatorInterface $translator) {
            return $newLocale &&
                $translator->trans('ago') !== 'ago' &&
                $translator->trans('from_now') !== 'from_now' &&
                $translator->trans('before') !== 'before' &&
                $translator->trans('after') !== 'after';
        });
    }

    public static function localeHasDiffOneDayWords($locale)
    {
        return static::executeWithLocale($locale, function ($newLocale, TranslatorInterface $translator) {
            return $newLocale &&
                $translator->trans('diff_now') !== 'diff_now' &&
                $translator->trans('diff_yesterday') !== 'diff_yesterday' &&
                $translator->trans('diff_tomorrow') !== 'diff_tomorrow';
        });
    }

    public static function localeHasDiffTwoDayWords($locale)
    {
        return static::executeWithLocale($locale, function ($newLocale, TranslatorInterface $translator) {
            return $newLocale &&
                $translator->trans('diff_before_yesterday') !== 'diff_before_yesterday' &&
                $translator->trans('diff_after_tomorrow') !== 'diff_after_tomorrow';
        });
    }

    public static function localeHasPeriodSyntax($locale)
    {
        return static::executeWithLocale($locale, function ($newLocale, TranslatorInterface $translator) {
            return $newLocale &&
                $translator->trans('period_recurrences') !== 'period_recurrences' &&
                $translator->trans('period_interval') !== 'period_interval' &&
                $translator->trans('period_start_date') !== 'period_start_date' &&
                $translator->trans('period_end_date') !== 'period_end_date';
        });
    }

    public static function getAvailableLocales()
    {
        $translator = static::translator();
        $locales = [];

        if ($translator instanceof Translator) {
            // languages
            $files = glob(__DIR__ . '/Lang/*.php');

            foreach ($files as $file) {
                $locales[] = substr($file, strrpos($file, '/') + 1, -4);
            }

            $locales = array_unique(array_merge($locales, array_keys($translator->getMessages())));
        }

        return $locales;
    }

    public static function setUtf8($utf8)
    {
        static::$utf8 = $utf8;
    }

    public function formatLocalized($format)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $format = preg_replace('#(?<!%)((?:%%)*)%e#', '\1%#d', $format);
        }

        $formatted = static::strftime($format, strtotime($this->toDateTimeString()));
        return static::$utf8 ? utf8_encode($formatted) : $formatted;
    }

    public static function strftime($format, $timestamp = null, $locale = null)
    {
        if (PHP_VERSION_ID < 80100) {
            return strftime($format, $timestamp, $locale);
        }

        if ($timestamp instanceof \DateTimeInterface) {
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        } else {
            $timestamp = is_int($timestamp) ? '@' . $timestamp : (string) $timestamp;

            try {
                $timestamp = new \DateTime($timestamp);
            } catch (\Throwable $e) {
                throw new \Exception('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.', 0, $e);
            } catch (\Exception $e) {
                throw new \Exception('$timestamp argument is neither a valid UNIX timestamp, a valid date-time string or a DateTime object.', 0, $e);
            }
        }

        if (empty($locale)) {
            $locale = setlocale(LC_TIME, '0');
        }

        $locale = preg_replace('/[^\w-].*$/', '', $locale);
        $formats = ['%a' => 'EEE', '%A' => 'EEEE', '%b' => 'MMM', '%B' => 'MMMM', '%h' => 'MMM'];
        $formatter = function ($timestamp, $format) use ($formats, $locale) {
            $tz = $timestamp->getTimezone();
            $dateType = \IntlDateFormatter::FULL;
            $timeType = \IntlDateFormatter::FULL;
            $pattern = '';

            switch ($format) {
                case '%c':
                    $dateType = \IntlDateFormatter::LONG;
                    $timeType = \IntlDateFormatter::SHORT;
                    break;

                case '%x':
                    $dateType = \IntlDateFormatter::SHORT;
                    $timeType = \IntlDateFormatter::NONE;
                    break;

                case '%X':
                    $dateType = \IntlDateFormatter::NONE;
                    $timeType = \IntlDateFormatter::MEDIUM;
                    break;

                default:
                    $pattern = $formats[$format];
            }

            $calendar = \IntlGregorianCalendar::createInstance();
            $calendar->setGregorianChange(PHP_INT_MIN);

            return (new \IntlDateFormatter($locale, $dateType, $timeType, $tz, $calendar, $pattern))->format($timestamp);
        };

        $translations = [
            '%a' => $formatter,
            '%A' => $formatter,
            '%d' => 'd',
            '%e' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('j'));
            },
            '%j' => function ($timestamp) {
                return sprintf('%03d', $timestamp->format('z') + 1);
            },
            '%u' => 'N',
            '%w' => 'w',
            '%U' => function ($timestamp) {
                $day = new \DateTime(sprintf('%d-01 Sunday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%V' => 'W',
            '%W' => function ($timestamp) {
                $day = new \DateTime(sprintf('%d-01 Monday', $timestamp->format('Y')));
                return sprintf('%02u', 1 + ($timestamp->format('z') - $day->format('z')) / 7);
            },
            '%b' => $formatter,
            '%B' => $formatter,
            '%h' => $formatter,
            '%m' => 'm',
            '%C' => function ($timestamp) {
                return floor($timestamp->format('Y') / 100);
            },
            '%g' => function ($timestamp) {
                return substr($timestamp->format('o'), -2);
            },
            '%G' => 'o',
            '%y' => 'y',
            '%Y' => 'Y',
            '%H' => 'H',
            '%k' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('G'));
            },
            '%I' => 'h',
            '%l' => function ($timestamp) {
                return sprintf('% 2u', $timestamp->format('g'));
            },
            '%M' => 'i',
            '%p' => 'A',
            '%P' => 'a',
            '%r' => 'h:i:s A',
            '%R' => 'H:i',
            '%S' => 's',
            '%T' => 'H:i:s',
            '%X' => $formatter,
            '%z' => 'O',
            '%Z' => 'T',
            '%c' => $formatter,
            '%D' => 'm/d/Y',
            '%F' => 'Y-m-d',
            '%s' => 'U',
            '%x' => $formatter,
        ];

        $out = preg_replace_callback('/(?<!%)%([_#-]?)([a-zA-Z])/', function ($match) use ($translations, $timestamp) {
            $prefix = $match[1];
            $char = $match[2];
            $pattern = '%' . $char;

            if ($pattern == '%n') {
                return "\n";
            } elseif ($pattern == '%t') {
                return "\t";
            }

            if (!isset($translations[$pattern])) {
                throw new \Exception(sprintf('Format "%s" is unknown in time format', $pattern));
            }

            $replace = $translations[$pattern];

            if (is_string($replace)) {
                $result = $timestamp->format($replace);
            } else {
                $result = $replace($timestamp, $pattern);
            }

            switch ($prefix) {
                case '_':
                    return preg_replace('/\G0(?=.)/', ' ', $result);
                case '#':
                case '-':
                    return preg_replace('/^0+(?=.)/', '', $result);
            }

            return $result;
        }, $format);
        return str_replace('%%', '%', $out);
    }

    public static function resetToStringFormat()
    {
        static::setToStringFormat('Y-m-d H:i:s');
    }

    public static function setToStringFormat($format)
    {
        static::$toStringFormat = $format;
    }

    public function __toString()
    {
        $format = static::$toStringFormat;
        return $this->format(($format instanceof \Closure) ? $format($this) : $format);
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

    public function toDateTimeLocalString()
    {
        return $this->format('Y-m-d\TH:i:s');
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
        return $this->toAtomString();
    }

    public function toRfc822String()
    {
        return $this->format(static::RFC822);
    }

    public function toIso8601ZuluString()
    {
        return $this->copy()->setTimezone('UTC')->format('Y-m-d\TH:i:s\Z');
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

    public function toRfc7231String()
    {
        return $this->copy()->setTimezone('GMT')->format('D, d M Y H:i:s \G\M\T');
    }

    public function toArray()
    {
        return [
            'year' => $this->year,
            'month' => $this->month,
            'day' => $this->day,
            'dayOfWeek' => $this->dayOfWeek,
            'dayOfYear' => $this->dayOfYear,
            'hour' => $this->hour,
            'minute' => $this->minute,
            'second' => $this->second,
            'micro' => $this->micro,
            'timestamp' => $this->timestamp,
            'formatted' => $this->format('Y-m-d H:i:s'),
            'timezone' => $this->timezone,
        ];
    }

    public function toObject()
    {
        return (object) $this->toArray();
    }

    public function toString()
    {
        return $this->format('D M j Y H:i:s \G\M\TO');
    }

    public function toISOString($keepOffset = false)
    {
        if ($this->year === 0) {
            return null;
        }

        $year = ($this->year < 0 || $this->year > 9999)
            ? (($this->year < 0) ? '-' : '+') . str_pad(abs($this->year), 6, '0', STR_PAD_LEFT)
            : str_pad($this->year, 4, '0', STR_PAD_LEFT);

        $tz = $keepOffset ? $this->format('P') : 'Z';
        $date = $keepOffset ? $this : $this->copy()->setTimezone('UTC');

        return $year . $date->format('-m-d\TH:i:s.u') . $tz;
    }

    public function toJSON()
    {
        return $this->toISOString();
    }

    public function toDateTime()
    {
        return new \DateTime($this->format('Y-m-d H:i:s.u'), $this->getTimezone());
    }

    public function toDate()
    {
        return $this->toDateTime();
    }

    public function eq($date)
    {
        return $this == $date;
    }

    public function equalTo($date)
    {
        return $this->eq($date);
    }

    public function ne($date)
    {
        return !$this->eq($date);
    }

    public function notEqualTo($date)
    {
        return $this->ne($date);
    }

    public function gt($date)
    {
        return $this > $date;
    }

    public function greaterThan($date)
    {
        return $this->gt($date);
    }

    public function isAfter($date)
    {
        return $this->gt($date);
    }

    public function gte($date)
    {
        return $this >= $date;
    }

    public function greaterThanOrEqualTo($date)
    {
        return $this->gte($date);
    }

    public function lt($date)
    {
        return $this < $date;
    }

    public function lessThan($date)
    {
        return $this->lt($date);
    }

    public function isBefore($date)
    {
        return $this->lt($date);
    }

    public function lte($date)
    {
        return $this <= $date;
    }

    public function lessThanOrEqualTo($date)
    {
        return $this->lte($date);
    }

    public function between($date1, $date2, $equal = true)
    {
        if ($date1->gt($date2)) {
            $temp = $date1;
            $date1 = $date2;
            $date2 = $temp;
        }

        return $equal ? ($this->gte($date1) && $this->lte($date2)) : ($this->gt($date1) && $this->lt($date2));
    }

    protected function floatDiffInSeconds($date)
    {
        $date = $this->resolveCarbon($date);
        return abs($this->diffInRealSeconds($date, false) + ($date->micro - $this->micro) / 1000000);
    }

    public function isBetween($date1, $date2, $equal = true)
    {
        return $this->between($date1, $date2, $equal);
    }

    public function closest($date1, $date2)
    {
        return ($this->floatDiffInSeconds($date1) < $this->floatDiffInSeconds($date2)) ? $date1 : $date2;
    }

    public function farthest($date1, $date2)
    {
        return ($this->floatDiffInSeconds($date1) > $this->floatDiffInSeconds($date2)) ? $date1 : $date2;
    }

    public function min($date = null)
    {
        $date = $this->resolveCarbon($date);
        return $this->lt($date) ? $this : $date;
    }

    public function minimum($date = null)
    {
        return $this->min($date);
    }

    public function max($date = null)
    {
        $date = $this->resolveCarbon($date);
        return $this->gt($date) ? $this : $date;
    }

    public function maximum($date = null)
    {
        return $this->max($date);
    }

    public function isWeekday()
    {
        return !$this->isWeekend();
    }

    public function isWeekend()
    {
        return in_array($this->dayOfWeek, static::$weekendDays);
    }

    public function isYesterday()
    {
        return $this->toDateString() === static::yesterday($this->getTimezone())->toDateString();
    }

    public function isToday()
    {
        return $this->toDateString() === $this->nowWithSameTz()->toDateString();
    }

    public function isTomorrow()
    {
        return $this->toDateString() === static::tomorrow($this->getTimezone())->toDateString();
    }

    public function isNextWeek()
    {
        return $this->weekOfYear === $this->nowWithSameTz()->addWeek()->weekOfYear;
    }

    public function isLastWeek()
    {
        return $this->weekOfYear === $this->nowWithSameTz()->subWeek()->weekOfYear;
    }

    public function isNextQuarter()
    {
        return $this->quarter === $this->nowWithSameTz()->addQuarter()->quarter;
    }

    public function isLastQuarter()
    {
        return $this->quarter === $this->nowWithSameTz()->subQuarter()->quarter;
    }

    public function isNextMonth()
    {
        return $this->month === $this->nowWithSameTz()->addMonthNoOverflow()->month;
    }

    public function isLastMonth()
    {
        return $this->month === $this->nowWithSameTz()->subMonthNoOverflow()->month;
    }

    public function isNextYear()
    {
        return $this->year === $this->nowWithSameTz()->addYear()->year;
    }

    public function isLastYear()
    {
        return $this->year === $this->nowWithSameTz()->subYear()->year;
    }

    public function isFuture()
    {
        return $this->gt($this->nowWithSameTz());
    }

    public function isPast()
    {
        return $this->lt($this->nowWithSameTz());
    }

    public function isLeapYear()
    {
        return $this->format('L') === '1';
    }

    public function isLongYear()
    {
        return static::create($this->year, 12, 28, 0, 0, 0, $this->tz)->weekOfYear === 53;
    }

    public function isSameAs($format, $date = null)
    {
        $date = $date ?: static::now($this->tz);
        static::expectDateTime($date, 'null');
        return $this->format($format) === $date->format($format);
    }

    public function isCurrentYear()
    {
        return $this->isSameYear();
    }

    public function isSameYear($date = null)
    {
        return $this->isSameAs('Y', $date);
    }

    public function isCurrentQuarter()
    {
        return $this->isSameQuarter();
    }

    public function isSameQuarter($date = null, $ofSameYear = null)
    {
        $date = $date ? static::instance($date) : static::now($this->tz);
        static::expectDateTime($date, 'null');
        $ofSameYear = is_null($ofSameYear) ? static::shouldCompareYearWithMonth() : $ofSameYear;
        return $this->quarter === $date->quarter && (!$ofSameYear || $this->isSameYear($date));
    }

    public function isCurrentMonth($ofSameYear = null)
    {
        return $this->isSameMonth(null, $ofSameYear);
    }

    public function isSameMonth($date = null, $ofSameYear = null)
    {
        $ofSameYear = is_null($ofSameYear) ? static::shouldCompareYearWithMonth() : $ofSameYear;
        return $this->isSameAs($ofSameYear ? 'Y-m' : 'm', $date);
    }

    public function isCurrentDay()
    {
        return $this->isSameDay();
    }

    public function isSameDay($date = null)
    {
        return $this->isSameAs('Y-m-d', $date);
    }

    public function isCurrentHour()
    {
        return $this->isSameHour();
    }

    public function isSameHour($date = null)
    {
        return $this->isSameAs('Y-m-d H', $date);
    }

    public function isCurrentMinute()
    {
        return $this->isSameMinute();
    }

    public function isSameMinute($date = null)
    {
        return $this->isSameAs('Y-m-d H:i', $date);
    }

    public function isCurrentSecond()
    {
        return $this->isSameSecond();
    }

    public function isSameSecond($date = null)
    {
        return $this->isSameAs('Y-m-d H:i:s', $date);
    }

    public function isDayOfWeek($dayOfWeek)
    {
        return $this->dayOfWeek === $dayOfWeek;
    }

    public function isSunday()
    {
        return $this->dayOfWeek === static::SUNDAY;
    }

    public function isMonday()
    {
        return $this->dayOfWeek === static::MONDAY;
    }

    public function isTuesday()
    {
        return $this->dayOfWeek === static::TUESDAY;
    }

    public function isWednesday()
    {
        return $this->dayOfWeek === static::WEDNESDAY;
    }

    public function isThursday()
    {
        return $this->dayOfWeek === static::THURSDAY;
    }

    public function isFriday()
    {
        return $this->dayOfWeek === static::FRIDAY;
    }

    public function isSaturday()
    {
        return $this->dayOfWeek === static::SATURDAY;
    }

    public function isBirthday($date = null)
    {
        return $this->isSameAs('md', $date);
    }

    public function isLastOfMonth()
    {
        return $this->day === $this->daysInMonth;
    }

    public function isStartOfDay($checkMicroseconds = false)
    {
        return $checkMicroseconds
            ? $this->format('H:i:s.u') === '00:00:00.000000'
            : $this->format('H:i:s') === '00:00:00';
    }

    public function isEndOfDay($checkMicroseconds = false)
    {
        return $checkMicroseconds
            ? $this->format('H:i:s.u') === '23:59:59.999999'
            : $this->format('H:i:s') === '23:59:59';
    }

    public function isMidnight()
    {
        return $this->isStartOfDay();
    }

    public function isMidday()
    {
        return $this->format('G:i:s') === static::$midDayAt . ':00:00';
    }

    public static function hasFormat($date, $format)
    {
        try {
            static::createFromFormat($format, $date);
            $regex = strtr(preg_quote($format, '/'), static::$regexFormats);
            return (bool) preg_match('/^' . $regex . '$/', $date);
        } catch (\Throwable $e) {
            // skip
        } catch (\Exception $e) {
            // skip
        }

        return false;
    }

    public function addCenturies($value)
    {
        return $this->addYears(100 * $value);
    }

    public function addCentury($value = 1)
    {
        return $this->addCenturies($value);
    }

    public function subCenturies($value)
    {
        return $this->addCenturies(-1 * $value);
    }

    public function subCentury($value = 1)
    {
        return $this->subCenturies($value);
    }

    public function addYears($value)
    {
        return $this->shouldOverflowYears()
            ? $this->addYearsWithOverflow($value)
            : $this->addYearsNoOverflow($value);
    }

    public function addYear($value = 1)
    {
        return $this->addYears($value);
    }

    public function addYearsNoOverflow($value)
    {
        return $this->addMonthsNoOverflow($value * 12);
    }

    public function addYearNoOverflow($value = 1)
    {
        return $this->addYearsNoOverflow($value);
    }

    public function addYearsWithOverflow($value)
    {
        return $this->modify(((int) $value) . ' year');
    }

    public function addYearWithOverflow($value = 1)
    {
        return $this->addYearsWithOverflow($value);
    }

    public function subYears($value)
    {
        return $this->addYears(-1 * $value);
    }

    public function subYear($value = 1)
    {
        return $this->subYears($value);
    }

    public function subYearsNoOverflow($value)
    {
        return $this->subMonthsNoOverflow($value * 12);
    }

    public function subYearNoOverflow($value = 1)
    {
        return $this->subYearsNoOverflow($value);
    }

    public function subYearsWithOverflow($value)
    {
        return $this->subMonthsWithOverflow($value * 12);
    }

    public function subYearWithOverflow($value = 1)
    {
        return $this->subYearsWithOverflow($value);
    }

    public function addQuarters($value)
    {
        return $this->addMonths(3 * $value);
    }

    public function addQuarter($value = 1)
    {
        return $this->addQuarters($value);
    }

    public function subQuarters($value)
    {
        return $this->addQuarters(-1 * $value);
    }

    public function subQuarter($value = 1)
    {
        return $this->subQuarters($value);
    }

    public function addMonths($value)
    {
        return static::shouldOverflowMonths()
            ? $this->addMonthsWithOverflow($value)
            : $this->addMonthsNoOverflow($value);
    }

    public function addMonth($value = 1)
    {
        return $this->addMonths($value);
    }

    public function subMonths($value)
    {
        return $this->addMonths(-1 * $value);
    }

    public function subMonth($value = 1)
    {
        return $this->subMonths($value);
    }

    public function addMonthsWithOverflow($value)
    {
        return $this->modify((int) $value . ' month');
    }

    public function addMonthWithOverflow($value = 1)
    {
        return $this->addMonthsWithOverflow($value);
    }

    public function subMonthsWithOverflow($value)
    {
        return $this->addMonthsWithOverflow(-1 * $value);
    }

    public function subMonthWithOverflow($value = 1)
    {
        return $this->subMonthsWithOverflow($value);
    }

    public function addMonthsNoOverflow($value)
    {
        $day = $this->day;
        $this->modify(((int) $value) . ' month');

        if ($day !== $this->day) {
            $this->modify('last day of previous month');
        }

        return $this;
    }

    public function addMonthNoOverflow($value = 1)
    {
        return $this->addMonthsNoOverflow($value);
    }

    public function subMonthsNoOverflow($value)
    {
        return $this->addMonthsNoOverflow(-1 * $value);
    }

    public function subMonthNoOverflow($value = 1)
    {
        return $this->subMonthsNoOverflow($value);
    }

    public function addDays($value)
    {
        return $this->modify((int) $value . ' day');
    }

    public function addDay($value = 1)
    {
        return $this->addDays($value);
    }

    public function subDays($value)
    {
        return $this->addDays(-1 * $value);
    }

    public function subDay($value = 1)
    {
        return $this->subDays($value);
    }

    public function addWeekdays($value)
    {
        $time = $this->toTimeString();
        $this->modify(((int) $value) . ' weekday');

        return $this->setTimeFromTimeString($time);
    }

    public function addWeekday($value = 1)
    {
        return $this->addWeekdays($value);
    }

    public function subWeekdays($value)
    {
        return $this->addWeekdays(-1 * $value);
    }

    public function subWeekday($value = 1)
    {
        return $this->subWeekdays($value);
    }

    public function addWeeks($value)
    {
        return $this->modify(((int) $value) . ' week');
    }

    public function addWeek($value = 1)
    {
        return $this->addWeeks($value);
    }

    public function subWeeks($value)
    {
        return $this->addWeeks(-1 * $value);
    }

    public function subWeek($value = 1)
    {
        return $this->subWeeks($value);
    }

    public function addHours($value)
    {
        return $this->modify(((int) $value) . ' hour');
    }

    public function addRealHours($value)
    {
        return $this->addRealMinutes($value * 60);
    }

    public function addHour($value = 1)
    {
        return $this->addHours($value);
    }

    public function addRealHour($value = 1)
    {
        return $this->addRealHours($value);
    }

    public function subHours($value)
    {
        return $this->addHours(-1 * $value);
    }

    public function subRealHours($value)
    {
        return $this->addRealHours(-1 * $value);
    }

    public function subHour($value = 1)
    {
        return $this->subHours($value);
    }

    public function subRealHour($value = 1)
    {
        return $this->subRealHours($value);
    }

    public function addMinutes($value)
    {
        return $this->modify(((int) $value) . ' minute');
    }

    public function addRealMinutes($value)
    {
        return $this->addRealSeconds($value * 60);
    }

    public function addMinute($value = 1)
    {
        return $this->addMinutes($value);
    }

    public function addRealMinute($value = 1)
    {
        return $this->addRealMinutes($value);
    }

    public function subMinute($value = 1)
    {
        return $this->subMinutes($value);
    }

    public function subRealMinute($value = 1)
    {
        return $this->addRealMinutes(-1 * $value);
    }

    public function subMinutes($value)
    {
        return $this->addMinutes(-1 * $value);
    }

    public function subRealMinutes($value = 1)
    {
        return $this->subRealMinute($value);
    }

    public function addSeconds($value)
    {
        return $this->modify(((int) $value) . ' second');
    }

    public function addRealSeconds($value)
    {
        return $this->setTimestamp($this->getTimestamp() + $value);
    }

    public function addSecond($value = 1)
    {
        return $this->addSeconds($value);
    }

    public function addRealSecond($value = 1)
    {
        return $this->addRealSeconds($value);
    }

    public function subSeconds($value)
    {
        return $this->addSeconds(-1 * $value);
    }

    public function subRealSeconds($value)
    {
        return $this->addRealSeconds(-1 * $value);
    }

    public function subSecond($value = 1)
    {
        return $this->subSeconds($value);
    }

    public function subRealSecond($value = 1)
    {
        return $this->subRealSeconds($value);
    }

    protected static function fixDiffInterval(\DateInterval $diff, $absolute, $trimMicroseconds)
    {
        $diff = Interval::instance($diff, $trimMicroseconds);

        if (version_compare(PHP_VERSION, '7.1.0-dev', '<')) {
            return $diff;
        }

        if ($diff->f > 0 && $diff->y === -1 && $diff->m === 11 && $diff->d >= 27 && $diff->h === 23 && $diff->i === 59 && $diff->s === 59) {
            $diff->y = 0;
            $diff->m = 0;
            $diff->d = 0;
            $diff->h = 0;
            $diff->i = 0;
            $diff->s = 0;
            $diff->f = (1000000 - round($diff->f * 1000000)) / 1000000;
            $diff->invert();
        } elseif ($diff->f < 0) {
            if ($diff->s !== 0 || $diff->i !== 0 || $diff->h !== 0 || $diff->d !== 0 || $diff->m !== 0 || $diff->y !== 0) {
                $diff->f = (round($diff->f * 1000000) + 1000000) / 1000000;
                $diff->s--;

                if ($diff->s < 0) {
                    $diff->s += 60;
                    $diff->i--;

                    if ($diff->i < 0) {
                        $diff->i += 60;
                        $diff->h--;

                        if ($diff->h < 0) {
                            $diff->h += 24;
                            $diff->d--;

                            if ($diff->d < 0) {
                                $diff->d += 30;
                                $diff->m--;

                                if ($diff->m < 0) {
                                    $diff->m += 12;
                                    $diff->y--;
                                }
                            }
                        }
                    }
                }
            } else {
                $diff->f *= -1;
                $diff->invert();
            }
        }

        if ($absolute && $diff->invert) {
            $diff->invert();
        }

        return $diff;
    }

    public function diffAsInterval($date = null, $absolute = true, $trimMicroseconds = true)
    {
        $from = $this;
        $to = $this->resolveCarbon($date);

        if ($trimMicroseconds) {
            $from = $from->copy()->startOfSecond();
            $to = $to->copy()->startOfSecond();
        }

        return static::fixDiffInterval($from->diff($to, $absolute), $absolute, $trimMicroseconds);
    }

    public function diffInYears($date = null, $absolute = true)
    {
        return (int) $this->diff($this->resolveCarbon($date), $absolute)->format('%r%y');
    }

    public function diffInMonths($date = null, $absolute = true)
    {
        $date = $this->resolveCarbon($date);
        return $this->diffInYears($date, $absolute) * 12 + (int) $this->diff($date, $absolute)->format('%r%m');
    }

    public function diffInWeeks($date = null, $absolute = true)
    {
        return (int) ($this->diffInDays($date, $absolute) / 7);
    }

    public function diffInDays($date = null, $absolute = true)
    {
        return (int) $this->diff($this->resolveCarbon($date), $absolute)->format('%r%a');
    }

    public function diffInDaysFiltered(\Closure $callback, $date = null, $absolute = true)
    {
        return $this->diffFiltered(Interval::day(), $callback, $date, $absolute);
    }

    public function diffInHoursFiltered(\Closure $callback, $date = null, $absolute = true)
    {
        return $this->diffFiltered(Interval::hour(), $callback, $date, $absolute);
    }

    public function diffFiltered(Interval $ci, \Closure $callback, $date = null, $absolute = true)
    {
        $start = $this;
        $end = $this->resolveCarbon($date);
        $inverse = false;

        if ($end < $start) {
            $start = $end;
            $end = $this;
            $inverse = true;
        }

        $period = new \DatePeriod($start, $ci, $end);
        $values = array_filter(iterator_to_array($period), function ($date) use ($callback) {
            return call_user_func($callback, Carbon::instance($date));
        });

        $diff = count($values);
        return ($inverse && !$absolute) ? -$diff : $diff;
    }

    public function diffInWeekdays($date = null, $absolute = true)
    {
        return $this->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekday();
        }, $date, $absolute);
    }

    public function diffInWeekendDays($date = null, $absolute = true)
    {
        return $this->diffInDaysFiltered(function (Carbon $date) {
            return $date->isWeekend();
        }, $date, $absolute);
    }

    public function diffInHours($date = null, $absolute = true)
    {
        return (int) ($this->diffInSeconds($date, $absolute) / 3600);
    }

    public function diffInRealHours($date = null, $absolute = true)
    {
        return (int) ($this->diffInRealSeconds($date, $absolute) / 3600);
    }

    public function diffInMinutes($date = null, $absolute = true)
    {
        return (int) ($this->diffInSeconds($date, $absolute) / 60);
    }

    public function diffInRealMinutes($date = null, $absolute = true)
    {
        return (int) ($this->diffInRealSeconds($date, $absolute) / 60);
    }

    public function diffInSeconds($date = null, $absolute = true)
    {
        $diff = $this->diff($this->resolveCarbon($date));

        if (!$diff->days && version_compare(PHP_VERSION, '5.4.0-dev', '>=')) {
            $diff = static::fixDiffInterval($diff, $absolute, false);
        }

        $value = $diff->days * 24 * 3600 + $diff->h * 3600 + $diff->i * 60 + $diff->s;
        return ($absolute || !$diff->invert) ? $value : -$value;
    }

    public function diffInRealSeconds($date = null, $absolute = true)
    {
        $value = $this->resolveCarbon($date)->getTimestamp() - $this->getTimestamp();
        return $absolute ? abs($value) : $value;
    }

    public function diffInMilliseconds($date = null, $absolute = true)
    {
        return (int) ($this->diffInMicroseconds($date, $absolute) / 1000);
    }

    public function diffInRealMilliseconds($date = null, $absolute = true)
    {
        return (int) ($this->diffInRealMicroseconds($date, $absolute) / 1000);
    }

    public function diffInMicroseconds($date = null, $absolute = true)
    {
        $diff = $this->diff($this->resolveCarbon($date));
        $micro = isset($diff->f) ? $diff->f : 0;
        $value = (int) round((((($diff->days * 24) + $diff->h) * 60 + $diff->i) * 60 + ($micro + $diff->s)) * 1000000);

        return ($absolute || !$diff->invert) ? $value : -$value;
    }

    public function diffInRealMicroseconds($date = null, $absolute = true)
    {

        $date = $this->resolveCarbon($date);
        $value = ($date->timestamp - $this->timestamp) * 1000000 + $date->micro - $this->micro;

        return $absolute ? abs($value) : $value;
    }

    public function secondsSinceMidnight()
    {
        return $this->diffInSeconds($this->copy()->startOfDay());
    }

    public function secondsUntilEndOfDay()
    {
        return $this->diffInSeconds($this->copy()->endOfDay());
    }

    public function diffForHumans($other = null, $absolute = false, $short = false, $parts = 1)
    {
        $isNow = $other === null;
        $relativeToNow = $isNow;

        if ($absolute === static::DIFF_RELATIVE_TO_NOW) {
            $absolute = false;
            $relativeToNow = true;
        } elseif ($absolute === static::DIFF_RELATIVE_TO_OTHER) {
            $absolute = false;
            $relativeToNow = false;
        }

        $interval = [];
        $parts = min(6, max(1, (int) $parts));
        $count = 1;
        $unit = $short ? 's' : 'second';

        if ($isNow) {
            $other = $this->nowWithSameTz();
        } elseif (!($other instanceof \DateTime) && !($other instanceof \DateTimeInterface)) {
            $other = static::parse($other);
        }

        $diffInterval = $this->diff($other);
        $diffIntervalArray = [
            ['value' => $diffInterval->y, 'unit' => 'year', 'unitShort' => 'y'],
            ['value' => $diffInterval->m, 'unit' => 'month', 'unitShort' => 'm'],
            ['value' => $diffInterval->d, 'unit' => 'day', 'unitShort' => 'd'],
            ['value' => $diffInterval->h, 'unit' => 'hour', 'unitShort' => 'h'],
            ['value' => $diffInterval->i, 'unit' => 'minute', 'unitShort' => 'min'],
            ['value' => $diffInterval->s, 'unit' => 'second', 'unitShort' => 's'],
        ];

        foreach ($diffIntervalArray as $diffIntervalData) {
            if ($diffIntervalData['value'] > 0) {
                $unit = $short ? $diffIntervalData['unitShort'] : $diffIntervalData['unit'];
                $count = $diffIntervalData['value'];

                if ($diffIntervalData['unit'] === 'day' && $count >= 7) {
                    $unit = $short ? 'w' : 'week';
                    $count = (int) ($count / 7);
                    $interval[] = static::translator()->transChoice($unit, $count, [':count' => $count]);
                    $numOfDaysCount = (int) ($diffIntervalData['value'] - ($count * 7));

                    if ($numOfDaysCount > 0 && count($interval) < $parts) {
                        $unit = $short ? 'd' : 'day';
                        $count = $numOfDaysCount;
                        $interval[] = static::translator()->transChoice($unit, $count, [':count' => $count]);
                    }
                } else {
                    $interval[] = static::translator()->transChoice($unit, $count, [':count' => $count]);
                }
            }

            if (count($interval) >= $parts) {
                break;
            }
        }

        if (count($interval) === 0) {
            if ($isNow && static::getHumanDiffOptions() & self::JUST_NOW) {
                $key = 'diff_now';
                $translation = static::translator()->trans($key);

                if ($translation !== $key) {
                    return $translation;
                }
            }

            $count = (static::getHumanDiffOptions() & self::NO_ZERO_DIFF) ? 1 : 0;
            $unit = $short ? 's' : 'second';
            $interval[] = static::translator()->transChoice($unit, $count, [':count' => $count]);
        }

        $time = implode(' ', $interval);
        unset($diffIntervalArray, $interval);

        if ($absolute) {
            return $time;
        }

        $isFuture = $diffInterval->invert === 1;
        $transId = $relativeToNow ? ($isFuture ? 'from_now' : 'ago') : ($isFuture ? 'after' : 'before');

        if ($parts === 1) {
            if ($isNow && $unit === 'day') {
                if ($count === 1 && static::getHumanDiffOptions() & self::ONE_DAY_WORDS) {
                    $key = $isFuture ? 'diff_tomorrow' : 'diff_yesterday';
                    $translation = static::translator()->trans($key);

                    if ($translation !== $key) {
                        return $translation;
                    }
                }

                if ($count === 2 && static::getHumanDiffOptions() & self::TWO_DAY_WORDS) {
                    $key = $isFuture ? 'diff_after_tomorrow' : 'diff_before_yesterday';
                    $translation = static::translator()->trans($key);

                    if ($translation !== $key) {
                        return $translation;
                    }
                }
            }

            $key = $unit . '_' . $transId;

            if ($key !== static::translator()->transChoice($key, $count)) {
                $time = static::translator()->transChoice($key, $count, [':count' => $count]);
            }
        }

        return static::translator()->trans($transId, [':time' => $time]);
    }

    public function from($other = null, $absolute = false, $short = false, $parts = 1)
    {
        if (!$other && !$absolute) {
            $absolute = static::DIFF_RELATIVE_TO_NOW;
        }

        return $this->diffForHumans($other, $absolute, $short, $parts);
    }

    public function since($other = null, $absolute = false, $short = false, $parts = 1)
    {
        return $this->diffForHumans($other, $absolute, $short, $parts);
    }

    public function to($other = null, $absolute = false, $short = false, $parts = 1)
    {
        if (!$other && !$absolute) {
            $absolute = static::DIFF_RELATIVE_TO_NOW;
        }

        return $this->resolveCarbon($other)->diffForHumans($this, $absolute, $short, $parts);
    }

    public function until($other = null, $absolute = false, $short = false, $parts = 1)
    {
        return $this->to($other, $absolute, $short, $parts);
    }

    public function fromNow($absolute = null, $short = false, $parts = 1)
    {
        $other = null;

        if ($absolute instanceof \DateTimeInterface) {
            list($other, $absolute, $short, $parts) = array_pad(func_get_args(), 5, null);
        }

        return $this->from($other, $absolute, $short, $parts);
    }

    public function toNow($absolute = null, $short = false, $parts = 1)
    {
        return $this->to(null, $absolute, $short, $parts);
    }

    public function ago($absolute = null, $short = false, $parts = 1)
    {
        $other = null;

        if ($absolute instanceof \DateTimeInterface) {
            list($other, $absolute, $short, $parts) = array_pad(func_get_args(), 5, null);
        }

        return $this->from($other, $absolute, $short, $parts);
    }

    public function startOfDay()
    {
        return $this->modify('00:00:00.000000');
    }

    public function endOfDay()
    {
        return $this->modify('23:59:59.999999');
    }

    public function startOfMonth()
    {
        return $this->setDate($this->year, $this->month, 1)->startOfDay();
    }

    public function endOfMonth()
    {
        return $this->setDate($this->year, $this->month, $this->daysInMonth)->endOfDay();
    }

    public function startOfQuarter()
    {
        $month = ($this->quarter - 1) * 3 + 1;
        return $this->setDate($this->year, $month, 1)->startOfDay();
    }

    public function endOfQuarter()
    {
        return $this->startOfQuarter()->addMonths(3 - 1)->endOfMonth();
    }

    public function startOfYear()
    {
        return $this->setDate($this->year, 1, 1)->startOfDay();
    }

    public function endOfYear()
    {
        return $this->setDate($this->year, 12, 31)->endOfDay();
    }

    public function startOfDecade()
    {
        $year = $this->year - $this->year % 10;
        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfDecade()
    {
        $year = $this->year - $this->year % 10 + 9;
        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfCentury()
    {
        $year = $this->year - ($this->year - 1) % 100;
        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfCentury()
    {
        $year = $this->year - 1 - ($this->year - 1) % 100 + 100;
        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfMillennium()
    {
        $year = $this->year - ($this->year - 1) % 1000;
        return $this->setDate($year, 1, 1)->startOfDay();
    }

    public function endOfMillennium()
    {
        $year = $this->year - 1 - ($this->year - 1) % 1000 + 1000;
        return $this->setDate($year, 12, 31)->endOfDay();
    }

    public function startOfWeek()
    {
        while ($this->dayOfWeek !== static::$weekStartsAt) {
            $this->subDay();
        }

        return $this->startOfDay();
    }

    public function endOfWeek()
    {
        while ($this->dayOfWeek !== static::$weekEndsAt) {
            $this->addDay();
        }

        return $this->endOfDay();
    }

    public function startOfHour()
    {
        return $this->setTime($this->hour, 0, 0);
    }

    public function endOfHour()
    {
        return $this->modify($this->hour . ':59:59.999999');
    }

    public function startOfMinute()
    {
        return $this->setTime($this->hour, $this->minute, 0);
    }

    public function endOfMinute()
    {
        return $this->modify($this->hour . ':' . $this->minute . ':59.999999');
    }

    public function startOfSecond()
    {
        return $this->modify($this->hour . ':' . $this->minute . ':' . $this->second . '.0');
    }

    public function endOfSecond()
    {
        return $this->modify($this->hour . ':' . $this->minute . ':' . $this->second . '.999999');
    }

    public function midDay()
    {
        return $this->setTime(self::$midDayAt, 0, 0);
    }

    public function next($dayOfWeek = null)
    {
        if ($dayOfWeek === null) {
            $dayOfWeek = $this->dayOfWeek;
        }

        return $this->startOfDay()->modify('next ' . static::$days[$dayOfWeek]);
    }

    private function nextOrPreviousDay($weekday = true, $forward = true)
    {
        $step = $forward ? 1 : -1;

        do {
            $this->addDay($step);
        } while ($weekday ? $this->isWeekend() : $this->isWeekday());

        return $this;
    }

    public function nextWeekday()
    {
        return $this->nextOrPreviousDay();
    }

    public function previousWeekday()
    {
        return $this->nextOrPreviousDay(true, false);
    }

    public function nextWeekendDay()
    {
        return $this->nextOrPreviousDay(false);
    }

    public function previousWeekendDay()
    {
        return $this->nextOrPreviousDay(false, false);
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
        $date = $this->copy()->firstOfMonth();
        $check = $date->format('Y-m');
        $date->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);

        return $date->format('Y-m') === $check ? $this->modify($date) : false;
    }

    public function firstOfQuarter($dayOfWeek = null)
    {
        return $this->setDate($this->year, $this->quarter * 3 - 2, 1)->firstOfMonth($dayOfWeek);
    }

    public function lastOfQuarter($dayOfWeek = null)
    {
        return $this->setDate($this->year, $this->quarter * 3, 1)->lastOfMonth($dayOfWeek);
    }

    public function nthOfQuarter($nth, $dayOfWeek)
    {
        $date = $this->copy()->day(1)->month($this->quarter * 3);
        $lastMonth = $date->month;
        $year = $date->year;
        $date->firstOfQuarter()->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);

        return ($lastMonth < $date->month || $year !== $date->year) ? false : $this->modify($date);
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
        $date = $this->copy()->firstOfYear()->modify('+' . $nth . ' ' . static::$days[$dayOfWeek]);
        return ($this->year === $date->year) ? $this->modify($date) : false;
    }

    public function average($date = null)
    {
        $date = $this->resolveCarbon($date);
        $increment = $this->diffInRealSeconds($date, false) / 2;
        $intIncrement = floor($increment);
        $microIncrement = (int) (($date->micro - $this->micro) / 2 + 1000000 * ($increment - $intIncrement));
        $micro = (int) ($this->micro + $microIncrement);

        while ($micro >= 1000000) {
            $micro -= 1000000;
            $intIncrement++;
        }

        $this->addSeconds($intIncrement);

        if (version_compare(PHP_VERSION, '7.1.8-dev', '>=')) {
            $this->setTime($this->hour, $this->minute, $this->second, $micro);
        }

        return $this;
    }

    public function serialize()
    {
        return serialize($this);
    }

    public static function fromSerialized($value)
    {
        $instance = @unserialize($value);

        if (!($instance instanceof static)) {
            throw new \Exception('Invalid serialized value.');
        }

        return $instance;
    }

    public static function __set_state($array)
    {
        return static::instance(parent::__set_state($array));
    }

    public function jsonSerialize()
    {
        if (static::$serializer) {
            return call_user_func(static::$serializer, $this);
        }

        $carbon = $this;

        return call_user_func(function () use ($carbon) {
            return get_object_vars($carbon);
        });
    }

    public static function serializeUsing($callback)
    {
        static::$serializer = $callback;
    }

    public static function macro($name, $macro)
    {
        static::$localMacros[$name] = $macro;
    }

    public static function resetMacros()
    {
        static::$localMacros = [];
    }

    public static function mixin($mixin)
    {
        $reflection = new \ReflectionClass($mixin);
        $methods = $reflection->getMethods(\ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);

        foreach ($methods as $method) {
            $method->setAccessible(true);
            static::macro($method->name, $method->invoke($mixin));
        }
    }

    public static function hasMacro($name)
    {
        return isset(static::$localMacros[$name]);
    }

    public static function __callStatic($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new \Exception(sprintf('Call to undefined method: %s', $method));
        }

        if ((static::$localMacros[$method] instanceof \Closure) && method_exists('\Closure', 'bind')) {
            return call_user_func_array(\Closure::bind(static::$localMacros[$method], null, get_called_class()), $parameters);
        }

        return call_user_func_array(static::$localMacros[$method], $parameters);
    }

    public function __call($method, $parameters)
    {
        if (!static::hasMacro($method)) {
            throw new \Exception(sprintf('Call to undefined method: %s', $method));
        }

        $macro = static::$localMacros[$method];
        $args = (new \ReflectionFunction($macro))->getParameters();
        $expected = count($args);
        $actual = count($parameters);

        if ($expected > $actual && $args[$expected - 1]->name === 'self') {
            for ($i = $actual; $i < $expected - 1; $i++) {
                $parameters[] = $args[$i]->getDefaultValue();
            }

            $parameters[] = $this;
        }

        if (($macro instanceof \Closure) && method_exists($macro, 'bindTo')) {
            return call_user_func_array($macro->bindTo($this, get_class($this)), $parameters);
        }

        return call_user_func_array($macro, $parameters);
    }

    public function __debugInfo()
    {
        return array_filter(get_object_vars($this), function ($var) {
            return $var;
        });
    }

    public function cast($class)
    {
        if (!method_exists($class, 'instance')) {
            throw new \Exception(sprintf(
                'The %s class does not have the instance() method needed to cast the date.',
                $class
            ));
        }

        return $class::instance($this);
    }
}
