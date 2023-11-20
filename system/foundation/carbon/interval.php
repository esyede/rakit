<?php

namespace System\Foundation\Carbon;

use Symfony\Component\Translation\TranslatorInterface;

class Interval extends \DateInterval
{
    const PERIOD_PREFIX = 'P';
    const PERIOD_YEARS = 'Y';
    const PERIOD_MONTHS = 'M';
    const PERIOD_DAYS = 'D';
    const PERIOD_TIME_PREFIX = 'T';
    const PERIOD_HOURS = 'H';
    const PERIOD_MINUTES = 'M';
    const PERIOD_SECONDS = 'S';
    const PHP_DAYS_FALSE = -99999;

    protected static $translator;
    protected static $cascadeFactors;
    protected static $macros = [];

    private static $flipCascadeFactors;

    public static function getCascadeFactors()
    {
        return static::$cascadeFactors ?: [
            'minutes' => [60, 'seconds'],
            'hours' => [60, 'minutes'],
            'dayz' => [24, 'hours'],
            'months' => [28, 'dayz'],
            'years' => [12, 'months'],
        ];
    }

    private static function standardizeUnit($unit)
    {
        $unit = rtrim($unit, 'sz') . 's';
        return ($unit === 'days') ? 'dayz' : $unit;
    }

    private static function getFlipCascadeFactors()
    {
        if (!self::$flipCascadeFactors) {
            self::$flipCascadeFactors = [];

            foreach (static::getCascadeFactors() as $to => $tuple) {
                list($factor, $from) = $tuple;
                self::$flipCascadeFactors[self::standardizeUnit($from)] = [self::standardizeUnit($to), $factor];
            }
        }

        return self::$flipCascadeFactors;
    }

    public static function setCascadeFactors(array $cascadeFactors)
    {
        self::$flipCascadeFactors = null;
        static::$cascadeFactors = $cascadeFactors;
    }

    private static function wasCreatedFromDiff(\DateInterval $interval)
    {
        return $interval->days !== false && $interval->days !== static::PHP_DAYS_FALSE;
    }

    public function __construct($years = 1, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null)
    {
        $spec = $years;

        if (!is_string($spec) || floatval($years) || preg_match('/^[0-9.]/', $years)) {
            $spec = static::PERIOD_PREFIX;
            $specDays = 0;
            $spec .= ($years > 0) ? $years . static::PERIOD_YEARS : '';
            $spec .= ($months > 0) ? $months . static::PERIOD_MONTHS : '';
            $specDays += ($weeks > 0) ? $weeks * static::getDaysPerWeek() : 0;
            $specDays += ($days > 0) ? $days : 0;
            $spec .= ($specDays > 0) ? $specDays . static::PERIOD_DAYS : '';

            if ($hours > 0 || $minutes > 0 || $seconds > 0) {
                $spec .= static::PERIOD_TIME_PREFIX;
                $spec .= ($hours > 0) ? $hours . static::PERIOD_HOURS : '';
                $spec .= ($minutes > 0) ? $minutes . static::PERIOD_MINUTES : '';
                $spec .= ($seconds > 0) ? $seconds . static::PERIOD_SECONDS : '';
            }

            if ($spec === static::PERIOD_PREFIX) {
                $spec .= '0' . static::PERIOD_YEARS;
            }
        }

        parent::__construct($spec);
    }

    public static function getFactor($source, $target)
    {
        $source = self::standardizeUnit($source);
        $target = self::standardizeUnit($target);
        $factors = static::getFlipCascadeFactors();

        if (isset($factors[$source])) {
            list($to, $factor) = $factors[$source];

            if ($to === $target) {
                return $factor;
            }
        }

        return null;
    }

    public static function getDaysPerWeek()
    {
        return static::getFactor('dayz', 'weeks') ?: 7;
    }

    public static function getHoursPerDay()
    {
        return static::getFactor('hours', 'dayz') ?: 24;
    }

    public static function getMinutesPerHours()
    {
        return static::getFactor('minutes', 'hours') ?: 60;
    }

    public static function getSecondsPerMinutes()
    {
        return static::getFactor('seconds', 'minutes') ?: 60;
    }

    public static function create($years = 1, $months = null, $weeks = null, $days = null, $hours = null, $minutes = null, $seconds = null)
    {
        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds);
    }

    public function copy()
    {
        $date = new static($this->spec());
        $date->invert = $this->invert;

        return $date;
    }

    public static function __callStatic($name, $args)
    {
        $arg = (count($args) === 0) ? 1 : $args[0];

        switch ($name) {
            case 'years':
            case 'year':
                return new static($arg);

            case 'months':
            case 'month':
                return new static(null, $arg);

            case 'weeks':
            case 'week':
                return new static(null, null, $arg);

            case 'days':
            case 'dayz':
            case 'day':
                return new static(null, null, null, $arg);

            case 'hours':
            case 'hour':
                return new static(null, null, null, null, $arg);

            case 'minutes':
            case 'minute':
                return new static(null, null, null, null, null, $arg);

            case 'seconds':
            case 'second':
                return new static(null, null, null, null, null, null, $arg);
        }

        if (static::hasMacro($name)) {
            return call_user_func_array([new static(0), $name], $args);
        }
    }

    public static function fromString($intervalDefinition)
    {
        if (empty($intervalDefinition)) {
            return new static(0);
        }

        $years = 0;
        $months = 0;
        $weeks = 0;
        $days = 0;
        $hours = 0;
        $minutes = 0;
        $seconds = 0;
        preg_match_all('/(\d+(?:\.\d+)?)\h*([^\d\h]*)/i', $intervalDefinition, $parts, PREG_SET_ORDER);

        while ($match = array_shift($parts)) {
            list($part, $value, $unit) = $match;
            $intValue = intval($value);
            $fraction = floatval($value) - $intValue;

            switch (strtolower($unit)) {
                case 'year':
                case 'years':
                case 'y':
                    $years += $intValue;
                    break;

                case 'month':
                case 'months':
                case 'mo':
                    $months += $intValue;
                    break;

                case 'week':
                case 'weeks':
                case 'w':
                    $weeks += $intValue;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getDaysPerWeek(), 'd'];
                    }
                    break;

                case 'day':
                case 'days':
                case 'd':
                    $days += $intValue;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getHoursPerDay(), 'h'];
                    }
                    break;

                case 'hour':
                case 'hours':
                case 'h':
                    $hours += $intValue;
                    if ($fraction) {
                        $parts[] = [null, $fraction * static::getMinutesPerHours(), 'm'];
                    }
                    break;

                case 'minute':
                case 'minutes':
                case 'm':
                    $minutes += $intValue;
                    if ($fraction) {
                        $seconds += round($fraction * static::getSecondsPerMinutes());
                    }
                    break;

                case 'second':
                case 'seconds':
                case 's':
                    $seconds += $intValue;
                    break;

                default:
                    throw new \Exception(sprintf('Invalid part %s in definition %s', $part, $intervalDefinition));
            }
        }

        return new static($years, $months, $weeks, $days, $hours, $minutes, $seconds);
    }

    public static function instance(\DateInterval $di, $trimMicroseconds = true)
    {
        $microseconds = ($trimMicroseconds || version_compare(PHP_VERSION, '7.1.0-dev', '<')) ? 0 : $di->f;
        $instance = new static(static::getDateIntervalSpec($di));

        if ($microseconds) {
            $instance->f = $microseconds;
        }

        $instance->invert = $di->invert;
        $units = ['y', 'm', 'd', 'h', 'i', 's'];

        foreach ($units as $unit) {
            if ($di->{$unit} < 0) {
                $instance->$unit *= -1;
            }
        }

        return $instance;
    }

    public static function make($var)
    {
        if ($var instanceof \DateInterval) {
            return static::instance($var);
        }

        if (is_string($var)) {
            $var = trim($var);

            if (substr($var, 0, 1) === 'P') {
                return new static($var);
            }

            if (preg_match('/^(?:\h*\d+(?:\.\d+)?\h*[a-z]+)+$/i', $var)) {
                return static::fromString($var);
            }
        }
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

    public function __get($name)
    {
        if (substr($name, 0, 5) === 'total') {
            return $this->total(substr($name, 5));
        }

        switch ($name) {
            case 'years':
                return $this->y;

            case 'months':
                return $this->m;

            case 'dayz':
                return $this->d;

            case 'hours':
                return $this->h;

            case 'minutes':
                return $this->i;

            case 'seconds':
                return $this->s;

            case 'weeks':
                return (int) floor($this->d / static::getDaysPerWeek());

            case 'daysExcludeWeeks':
            case 'dayzExcludeWeeks':
                return $this->d % static::getDaysPerWeek();

            default:
                throw new \Exception(sprintf('Unknown getter: %s', $name));
        }
    }

    public function __set($name, $val)
    {
        switch ($name) {
            case 'years':
                $this->y = $val;
                break;

            case 'months':
                $this->m = $val;
                break;

            case 'weeks':
                $this->d = $val * static::getDaysPerWeek();
                break;

            case 'dayz':
                $this->d = $val;
                break;

            case 'hours':
                $this->h = $val;
                break;

            case 'minutes':
                $this->i = $val;
                break;

            case 'seconds':
                $this->s = $val;
                break;
        }
    }

    public function weeksAndDays($weeks, $days)
    {
        $this->dayz = ($weeks * static::getDaysPerWeek()) + $days;
        return $this;
    }

    public static function macro($name, $macro)
    {
        static::$macros[$name] = $macro;
    }

    public static function resetMacros()
    {
        static::$macros = [];
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
        return isset(static::$macros[$name]);
    }

    protected function callMacro($name, $parameters)
    {
        $macro = static::$macros[$name];
        $reflection = new \ReflectionFunction($macro);
        $reflectionParameters = $reflection->getParameters();
        $expectedCount = count($reflectionParameters);
        $actualCount = count($parameters);

        if ($expectedCount > $actualCount && $reflectionParameters[$expectedCount - 1]->name === 'self') {
            for ($i = $actualCount; $i < $expectedCount - 1; $i++) {
                $parameters[] = $reflectionParameters[$i]->getDefaultValue();
            }

            $parameters[] = $this;
        }

        if (($macro instanceof \Closure) && method_exists($macro, 'bindTo')) {
            $macro = $macro->bindTo($this, get_class($this));
        }

        return call_user_func_array($macro, $parameters);
    }

    public function __call($name, $args)
    {
        if (static::hasMacro($name)) {
            return $this->callMacro($name, $args);
        }

        $arg = (count($args) === 0) ? 1 : $args[0];

        switch ($name) {
            case 'years':
            case 'year':
                $this->years = $arg;
                break;

            case 'months':
            case 'month':
                $this->months = $arg;
                break;

            case 'weeks':
            case 'week':
                $this->dayz = $arg * static::getDaysPerWeek();
                break;

            case 'days':
            case 'dayz':
            case 'day':
                $this->dayz = $arg;
                break;

            case 'hours':
            case 'hour':
                $this->hours = $arg;
                break;

            case 'minutes':
            case 'minute':
                $this->minutes = $arg;
                break;

            case 'seconds':
            case 'second':
                $this->seconds = $arg;
                break;
        }

        return $this;
    }

    public function forHumans($short = false)
    {
        $parts = [];
        $periods = [
            'year' => ['y', $this->years],
            'month' => ['m', $this->months],
            'week' => ['w', $this->weeks],
            'day' => ['d', $this->daysExcludeWeeks],
            'hour' => ['h', $this->hours],
            'minute' => ['min', $this->minutes],
            'second' => ['s', $this->seconds],
        ];

        foreach ($periods as $unit => $options) {
            list($shortUnit, $count) = $options;

            if ($count > 0) {
                $parts[] = static::translator()->transChoice($short ? $shortUnit : $unit, $count, [':count' => $count]);
            }
        }

        return implode(' ', $parts);
    }

    public function __toString()
    {
        return $this->forHumans();
    }

    public function toPeriod()
    {
        return Period::createFromArray(array_merge([$this], func_get_args()));
    }

    public function invert()
    {
        $this->invert = $this->invert ? 0 : 1;
        return $this;
    }

    public function add(\DateInterval $interval)
    {
        $sign = (($this->invert === 1) !== ($interval->invert === 1)) ? -1 : 1;

        if (static::wasCreatedFromDiff($interval)) {
            $this->dayz += $interval->days * $sign;
        } else {
            $this->years += $interval->y * $sign;
            $this->months += $interval->m * $sign;
            $this->dayz += $interval->d * $sign;
            $this->hours += $interval->h * $sign;
            $this->minutes += $interval->i * $sign;
            $this->seconds += $interval->s * $sign;
        }

        if (($this->years || $this->months || $this->dayz || $this->hours || $this->minutes || $this->seconds) &&
            $this->years <= 0 && $this->months <= 0 && $this->dayz <= 0 && $this->hours <= 0 && $this->minutes <= 0 && $this->seconds <= 0
        ) {
            $this->years *= -1;
            $this->months *= -1;
            $this->dayz *= -1;
            $this->hours *= -1;
            $this->minutes *= -1;
            $this->seconds *= -1;
            $this->invert();
        }

        return $this;
    }

    public function times($factor)
    {
        if ($factor < 0) {
            $this->invert = $this->invert ? 0 : 1;
            $factor = -$factor;
        }

        $this->years = (int) round($this->years * $factor);
        $this->months = (int) round($this->months * $factor);
        $this->dayz = (int) round($this->dayz * $factor);
        $this->hours = (int) round($this->hours * $factor);
        $this->minutes = (int) round($this->minutes * $factor);
        $this->seconds = (int) round($this->seconds * $factor);

        return $this;
    }

    public static function getDateIntervalSpec(\DateInterval $interval)
    {
        $date = array_filter([
            static::PERIOD_YEARS => abs($interval->y),
            static::PERIOD_MONTHS => abs($interval->m),
            static::PERIOD_DAYS => abs($interval->d),
        ]);

        $time = array_filter([
            static::PERIOD_HOURS => abs($interval->h),
            static::PERIOD_MINUTES => abs($interval->i),
            static::PERIOD_SECONDS => abs($interval->s),
        ]);

        $specString = static::PERIOD_PREFIX;

        foreach ($date as $key => $value) {
            $specString .= $value . $key;
        }

        if (count($time) > 0) {
            $specString .= static::PERIOD_TIME_PREFIX;

            foreach ($time as $key => $value) {
                $specString .= $value . $key;
            }
        }

        return ($specString === static::PERIOD_PREFIX) ? 'PT0S' : $specString;
    }

    public function spec()
    {
        return static::getDateIntervalSpec($this);
    }

    public static function compareDateIntervals(\DateInterval $a, \DateInterval $b)
    {
        $current = Carbon::now();
        $passed = $current->copy()->add($b);
        $current->add($a);

        if ($current < $passed) {
            return -1;
        }

        if ($current > $passed) {
            return 1;
        }

        return 0;
    }

    public function compare(\DateInterval $interval)
    {
        return static::compareDateIntervals($this, $interval);
    }

    public function cascade()
    {
        foreach (static::getFlipCascadeFactors() as $source => $cascade) {
            list($target, $factor) = $cascade;

            if ($source === 'dayz' && $target === 'weeks') {
                continue;
            }

            $value = $this->$source;
            $this->$source = $modulo = $value % $factor;
            $this->$target += ($value - $modulo) / $factor;
        }

        return $this;
    }

    public function total($unit)
    {
        $realUnit = $unit = strtolower($unit);

        if (in_array($unit, ['days', 'weeks'])) {
            $realUnit = 'dayz';
        } elseif (!in_array($unit, ['seconds', 'minutes', 'hours', 'dayz', 'months', 'years'])) {
            throw new \Exception(sprintf('Unknown unit: %s', $unit));
        }

        $result = 0;
        $cumulativeFactor = 0;
        $unitFound = false;

        foreach (static::getFlipCascadeFactors() as $source => $cascade) {
            list($target, $factor) = $cascade;

            if ($source === $realUnit) {
                $unitFound = true;
                $result += $this->$source;
                $cumulativeFactor = 1;
            }

            if ($factor === false) {
                if ($unitFound) {
                    break;
                }

                $result = 0;
                $cumulativeFactor = 0;
                continue;
            }

            if ($target === $realUnit) {
                $unitFound = true;
            }

            if ($cumulativeFactor) {
                $cumulativeFactor *= $factor;
                $result += $this->$target * $cumulativeFactor;
                continue;
            }

            $result = ($result + $this->$source) / $factor;
        }

        if (isset($target) && !$cumulativeFactor) {
            $result += $this->$target;
        }

        if (!$unitFound) {
            throw new \Exception(sprintf('Unit %s have no configuration to get total from other units.', $unit));
        }

        return ($unit === 'weeks') ? ($result / static::getDaysPerWeek()) : $result;
    }
}
