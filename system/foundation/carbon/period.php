<?php

namespace System\Foundation\Carbon;

class Period implements \Iterator, \Countable
{
    const RECURRENCES_FILTER = '\System\Foundation\Carbon\Period::filterRecurrences';
    const END_DATE_FILTER = '\System\Foundation\Carbon\Period::filterEndDate';
    const END_ITERATION = '\System\Foundation\Carbon\Period::endIteration';
    const EXCLUDE_START_DATE = 1;
    const EXCLUDE_END_DATE = 2;
    const NEXT_MAX_ATTEMPTS = 1000;

    protected static $macros = [];

    protected $dateInterval;
    protected $isDefaultInterval;
    protected $filters = [];
    protected $startDate;
    protected $endDate;
    protected $recurrences;
    protected $options;
    protected $key;
    protected $current;
    protected $timezone;
    protected $validationResult;

    public static function create()
    {
        return static::createFromArray(func_get_args());
    }

    public static function createFromArray(array $params)
    {
        return (new \ReflectionClass('\System\Foundation\Carbon\Period'))->newInstanceArgs($params);
    }

    public static function createFromIso($iso, $options = null)
    {
        $params = static::parseIso8601($iso);
        $instance = static::createFromArray($params);

        if ($options !== null) {
            $instance->setOptions($options);
        }

        return $instance;
    }

    protected static function intervalHasTime(\DateInterval $interval)
    {
        return $interval->h
            || $interval->i
            || $interval->s
            || array_key_exists('f', get_object_vars($interval))
            && $interval->f;
    }

    protected static function isCarbonPredicateMethod($callable)
    {
        return is_string($callable)
            && substr($callable, 0, 2) === 'is'
            && (method_exists('\System\Foundation\Carbon\Carbon', $callable) || Carbon::hasMacro($callable));
    }

    protected static function isIso8601($var)
    {
        if (!is_string($var)) {
            return false;
        }

        preg_match("/\b[a-z]+(?:[_-][a-z]+)*/[a-z]+(?:[_-][a-z]+)*\b|(/)/i", $var, $match);
        return isset($match[1]);
    }

    protected static function parseIso8601($iso)
    {
        $result = [];
        $interval = null;
        $start = null;
        $end = null;

        foreach (explode('/', $iso) as $key => $part) {
            if ($key === 0 && preg_match('/^R([0-9]*)$/', $part, $match)) {
                $parsed = strlen($match[1]) ? (int) $match[1] : null;
            } elseif ($interval === null && $parsed = Interval::make($part)) {
                $interval = $part;
            } elseif ($start === null && $parsed = Carbon::make($part)) {
                $start = $part;
            } elseif ($end === null && $parsed = Carbon::make(static::addMissingParts($start, $part))) {
                $end = $part;
            } else {
                throw new \Exception(sprintf('Invalid ISO 8601 specification: %s', $iso));
            }

            $result[] = $parsed;
        }

        return $result;
    }

    protected static function addMissingParts($source, $target)
    {
        $pattern = '/' . preg_replace('/[0-9]+/', '[0-9]+', preg_quote($target, '/')) . '$/';
        $result = preg_replace($pattern, $target, $source, 1, $count);
        return $count ? $result : $target;
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

    public static function __callStatic($method, $parameters)
    {
        return call_user_func_array([new static, $method], $parameters);
    }

    public function __construct()
    {
        $arguments = func_get_args();

        if (count($arguments) && static::isIso8601($iso = $arguments[0])) {
            array_splice($arguments, 0, 1, static::parseIso8601($iso));
        }

        foreach ($arguments as $argument) {
            if ($this->dateInterval === null && $parsed = Interval::make($argument)) {
                $this->setDateInterval($parsed);
            } elseif ($this->startDate === null && $parsed = Carbon::make($argument)) {
                $this->setStartDate($parsed);
            } elseif ($this->endDate === null && $parsed = Carbon::make($argument)) {
                $this->setEndDate($parsed);
            } elseif ($this->recurrences === null && $this->endDate === null && is_numeric($argument)) {
                $this->setRecurrences($argument);
            } elseif ($this->options === null && (is_int($argument) || $argument === null)) {
                $this->setOptions($argument);
            } else {
                throw new \Exception('Invalid constructor parameters.');
            }
        }

        if ($this->startDate === null) {
            $this->setStartDate(Carbon::now());
        }

        if ($this->dateInterval === null) {
            $this->setDateInterval(Interval::day());
            $this->isDefaultInterval = true;
        }

        if ($this->options === null) {
            $this->setOptions(0);
        }
    }

    public function setDateInterval($interval)
    {
        if (!$interval = Interval::make($interval)) {
            throw new \Exception('Invalid interval.');
        }

        if ($interval->spec() === 'PT0S') {
            throw new \Exception('Empty interval is not accepted.');
        }

        $this->dateInterval = $interval;
        $this->isDefaultInterval = false;
        $this->handleChangedParameters();

        return $this;
    }

    public function invertDateInterval()
    {
        $interval = $this->dateInterval->invert();
        return $this->setDateInterval($interval);
    }

    public function setDates($start, $end)
    {
        $this->setStartDate($start);
        $this->setEndDate($end);

        return $this;
    }

    public function setOptions($options)
    {
        if (!is_int($options) && !is_null($options)) {
            throw new \Exception('Invalid options.');
        }

        $this->options = $options ?: 0;
        $this->handleChangedParameters();

        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function toggleOptions($options, $state = null)
    {
        if ($state === null) {
            $state = ($this->options & $options) !== $options;
        }

        return $this->setOptions($state ? ($this->options | $options) : ($this->options & ~$options));
    }

    public function excludeStartDate($state = true)
    {
        return $this->toggleOptions(static::EXCLUDE_START_DATE, $state);
    }

    public function excludeEndDate($state = true)
    {
        return $this->toggleOptions(static::EXCLUDE_END_DATE, $state);
    }

    public function getDateInterval()
    {
        return $this->dateInterval->copy();
    }

    public function getStartDate()
    {
        return $this->startDate->copy();
    }

    public function getEndDate()
    {
        if ($this->endDate) {
            return $this->endDate->copy();
        }
    }

    public function getRecurrences()
    {
        return $this->recurrences;
    }

    public function isStartExcluded()
    {
        return ($this->options & static::EXCLUDE_START_DATE) !== 0;
    }

    public function isEndExcluded()
    {
        return ($this->options & static::EXCLUDE_END_DATE) !== 0;
    }

    public function addFilter($callback, $name = null)
    {
        $tuple = $this->createFilterTuple(func_get_args());
        $this->filters[] = $tuple;
        $this->handleChangedParameters();

        return $this;
    }

    public function prependFilter($callback, $name = null)
    {
        array_unshift($this->filters, $this->createFilterTuple(func_get_args()));
        $this->handleChangedParameters();
        return $this;
    }

    protected function createFilterTuple(array $parameters)
    {
        $method = array_shift($parameters);

        if (!$this->isCarbonPredicateMethod($method)) {
            return [$method, array_shift($parameters)];
        }

        return [function ($date) use ($method, $parameters) {
            return call_user_func_array([$date, $method], $parameters);
        }, $method];
    }

    public function removeFilter($filter)
    {
        $key = is_callable($filter) ? 0 : 1;
        $this->filters = array_values(array_filter($this->filters, function ($tuple) use ($key, $filter) {
            return $tuple[$key] !== $filter;
        }));

        $this->updateInternalState();
        $this->handleChangedParameters();
        return $this;
    }

    public function hasFilter($filter)
    {
        $key = is_callable($filter) ? 0 : 1;

        foreach ($this->filters as $tuple) {
            if ($tuple[$key] === $filter) {
                return true;
            }
        }

        return false;
    }

    public function getFilters()
    {
        return $this->filters;
    }

    public function setFilters(array $filters)
    {
        $this->filters = $filters;
        $this->updateInternalState();
        $this->handleChangedParameters();

        return $this;
    }

    public function resetFilters()
    {
        $this->filters = [];

        if ($this->endDate !== null) {
            $this->filters[] = [static::END_DATE_FILTER, null];
        }

        if ($this->recurrences !== null) {
            $this->filters[] = [static::RECURRENCES_FILTER, null];
        }

        $this->handleChangedParameters();
        return $this;
    }

    protected function updateInternalState()
    {
        if (!$this->hasFilter(static::END_DATE_FILTER)) {
            $this->endDate = null;
        }

        if (!$this->hasFilter(static::RECURRENCES_FILTER)) {
            $this->recurrences = null;
        }
    }

    public function setRecurrences($recurrences)
    {
        if (!is_numeric($recurrences) && !is_null($recurrences) || $recurrences < 0) {
            throw new \Exception('Invalid number of recurrences.');
        }

        if ($recurrences === null) {
            return $this->removeFilter(static::RECURRENCES_FILTER);
        }

        $this->recurrences = (int) $recurrences;

        if (!$this->hasFilter(static::RECURRENCES_FILTER)) {
            return $this->addFilter(static::RECURRENCES_FILTER);
        }

        $this->handleChangedParameters();
        return $this;
    }

    protected function filterRecurrences($current, $key)
    {
        return ($key < $this->recurrences) ? true : static::END_ITERATION;
    }

    public function setStartDate($date, $inclusive = null)
    {
        if (!$date = Carbon::make($date)) {
            throw new \Exception('Invalid start date.');
        }

        $this->startDate = $date;

        if ($inclusive !== null) {
            $this->toggleOptions(static::EXCLUDE_START_DATE, !$inclusive);
        }

        return $this;
    }

    public function setEndDate($date, $inclusive = null)
    {
        if (!is_null($date) && !$date = Carbon::make($date)) {
            throw new \Exception('Invalid end date.');
        }

        if (!$date) {
            return $this->removeFilter(static::END_DATE_FILTER);
        }

        $this->endDate = $date;

        if ($inclusive !== null) {
            $this->toggleOptions(static::EXCLUDE_END_DATE, !$inclusive);
        }

        if (!$this->hasFilter(static::END_DATE_FILTER)) {
            return $this->addFilter(static::END_DATE_FILTER);
        }

        $this->handleChangedParameters();
        return $this;
    }

    protected function filterEndDate($current)
    {
        if (!$this->isEndExcluded() && $current == $this->endDate) {
            return true;
        }

        if ($this->dateInterval->invert ? ($current > $this->endDate) : ($current < $this->endDate)) {
            return true;
        }

        return static::END_ITERATION;
    }

    protected function endIteration()
    {
        return static::END_ITERATION;
    }

    protected function handleChangedParameters()
    {
        $this->validationResult = null;
    }

    protected function validateCurrentDate()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        if ($this->validationResult !== null) {
            return $this->validationResult;
        }

        return $this->validationResult = $this->checkFilters();
    }

    protected function checkFilters()
    {
        $current = $this->prepareForReturn($this->current);

        foreach ($this->filters as $tuple) {
            $result = call_user_func($tuple[0], $current->copy(), $this->key, $this);

            if ($result === static::END_ITERATION) {
                return static::END_ITERATION;
            }

            if (!$result) {
                return false;
            }
        }

        return true;
    }

    protected function prepareForReturn(Carbon $date)
    {
        $date = $date->copy();

        if ($this->timezone) {
            $date->setTimezone($this->timezone);
        }

        return $date;
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return $this->validateCurrentDate() === true;
    }

    public function key()
    {
        if ($this->valid()) {
            return $this->key;
        }
    }

    public function current()
    {
        if ($this->valid()) {
            return $this->prepareForReturn($this->current);
        }
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        if ($this->current === null) {
            $this->rewind();
        }

        if ($this->validationResult !== static::END_ITERATION) {
            $this->key++;
            $this->incrementCurrentDateUntilValid();
        }
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        $this->key = 0;
        $this->current = $this->startDate->copy();
        $this->timezone = static::intervalHasTime($this->dateInterval) ? $this->current->getTimezone() : null;

        if ($this->timezone) {
            $this->current->setTimezone('UTC');
        }

        $this->validationResult = null;

        if ($this->isStartExcluded() || $this->validateCurrentDate() === false) {
            $this->incrementCurrentDateUntilValid();
        }
    }

    public function skip($count = 1)
    {
        for ($i = $count; $this->valid() && $i > 0; $i--) {
            $this->next();
        }

        return $this->valid();
    }

    protected function incrementCurrentDateUntilValid()
    {
        $attempts = 0;

        do {
            $this->current->add($this->dateInterval);
            $this->validationResult = null;

            if (++$attempts > static::NEXT_MAX_ATTEMPTS) {
                throw new \Exception('Could not find next valid date.');
            }
        } while ($this->validateCurrentDate() === false);
    }

    public function toIso8601String()
    {
        $parts = [];

        if ($this->recurrences !== null) {
            $parts[] = 'R' . $this->recurrences;
        }

        $parts[] = $this->startDate->toIso8601String();
        $parts[] = $this->dateInterval->spec();

        if ($this->endDate !== null) {
            $parts[] = $this->endDate->toIso8601String();
        }

        return implode('/', $parts);
    }

    public function toString()
    {
        $parts = [];
        $translator = Carbon::getTranslator();
        $format = (!$this->startDate->isStartOfDay() || $this->endDate && !$this->endDate->isStartOfDay())
            ? 'Y-m-d H:i:s'
            : 'Y-m-d';

        if ($this->recurrences !== null) {
            $parts[] = $translator->transChoice('period_recurrences', $this->recurrences, [':count' => $this->recurrences]);
        }

        $parts[] = $translator->trans('period_interval', [':interval' => $this->dateInterval->forHumans()]);
        $parts[] = $translator->trans('period_start_date', [':date' => $this->startDate->format($format)]);

        if ($this->endDate !== null) {
            $parts[] = $translator->trans('period_end_date', [':date' => $this->endDate->format($format)]);
        }

        $result = implode(' ', $parts);
        return mb_strtoupper(mb_substr($result, 0, 1)) . mb_substr($result, 1);
    }

    public function spec()
    {
        return $this->toIso8601String();
    }

    public function toArray()
    {
        $state = [$this->key, $this->current ? $this->current->copy() : null, $this->validationResult];
        $result = iterator_to_array($this);
        list($this->key, $this->current, $this->validationResult) = $state;

        return $result;
    }

    #[\ReturnTypeWillChange]
    public function count()
    {
        return count($this->toArray());
    }

    public function first()
    {
        if ($array = $this->toArray()) {
            return $array[0];
        }
    }

    public function last()
    {
        if ($array = $this->toArray()) {
            return $array[count($array) - 1];
        }
    }

    protected function callMacro($name, $parameters)
    {
        $macro = static::$macros[$name];
        $reflectionParameters = (new \ReflectionFunction($macro))->getParameters();
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

    public function __toString()
    {
        return $this->toString();
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->callMacro($method, $parameters);
        }

        $first = (count($parameters) >= 1) ? $parameters[0] : null;
        $second = (count($parameters) >= 2) ? $parameters[1] : null;

        switch ($method) {
            case 'start':
            case 'since':
                return $this->setStartDate($first, $second);

            case 'sinceNow':
                return $this->setStartDate(new Carbon, $first);

            case 'end':
            case 'until':
                return $this->setEndDate($first, $second);

            case 'untilNow':
                return $this->setEndDate(new Carbon, $first);

            case 'dates':
            case 'between':
                return $this->setDates($first, $second);

            case 'recurrences':
            case 'times':
                return $this->setRecurrences($first);

            case 'options':
                return $this->setOptions($first);

            case 'toggle':
                return $this->toggleOptions($first, $second);

            case 'filter':
            case 'push':
                return $this->addFilter($first, $second);

            case 'prepend':
                return $this->prependFilter($first, $second);

            case 'filters':
                return $this->setFilters($first ?: []);

            case 'interval':
            case 'each':
            case 'every':
            case 'step':
            case 'stepBy':
                return $this->setDateInterval($first);

            case 'invert':
                return $this->invertDateInterval();

            case 'years':
            case 'year':
            case 'months':
            case 'month':
            case 'weeks':
            case 'week':
            case 'days':
            case 'dayz':
            case 'day':
            case 'hours':
            case 'hour':
            case 'minutes':
            case 'minute':
            case 'seconds':
            case 'second':
                return $this->setDateInterval(call_user_func(
                    [$this->isDefaultInterval ? new Interval('PT0S') : $this->dateInterval, $method],
                    (count($parameters) === 0) ? 1 : $first
                ));
        }

        throw new \Exception(sprintf('Call to undefined method: %s', $method));
    }
}
