<?php

namespace System\Schedule;

defined('DS') or exit('No direct access.');

use System\Request;

class Event
{
    /**
     * What runs: 'call', 'command' or 'exec'.
     *
     * @var string
     */
    public $type;

    /**
     * The closure, console command or shell command.
     *
     * @var \Closure|string
     */
    public $target;

    /**
     * The closure's arguments.
     *
     * @var array
     */
    public $parameters = [];

    /**
     * The cron expression: minute, hour, day of month, month, day of week.
     *
     * @var string
     */
    public $expression = '* * * * *';

    /**
     * The timezone the expression is read in, NULL for the application's.
     *
     * @var string|null
     */
    public $timezone;

    /**
     * The name shown by schedule:list, and the lock name of a closure.
     *
     * @var string|null
     */
    public $description;

    /**
     * Whether a run is skipped while the previous one is still going.
     *
     * @var bool
     */
    public $without_overlapping = false;

    /**
     * Whether only one server runs the event each time it is due.
     *
     * @var bool
     */
    public $on_one_server = false;

    /**
     * Conditions that must all pass.
     *
     * @var \Closure[]
     */
    protected $filters = [];

    /**
     * Conditions that skip the run when any passes.
     *
     * @var \Closure[]
     */
    protected $rejects = [];

    /**
     * Constructor.
     *
     * @param string          $type
     * @param \Closure|string $target
     * @param array           $parameters
     */
    public function __construct($type, $target, array $parameters = [])
    {
        $this->type = $type;
        $this->target = $target;
        $this->parameters = $parameters;
    }

    /**
     * Run on a cron expression: 'minute hour day-of-month month day-of-week'. Each
     * field takes '*', a number, a range '1-5', a list '1,15' and a step '*\/10'.
     *
     * @param string $expression
     *
     * @return $this
     */
    public function cron($expression)
    {
        $fields = preg_split('/\s+/', trim((string) $expression));

        if (5 !== count($fields)) {
            throw new \InvalidArgumentException(sprintf('A cron expression has five fields: %s', $expression));
        }

        foreach ($fields as $field) {
            if (! preg_match('/^(?:\*|\d+(?:-\d+)?)(?:\/\d+)?(?:,(?:\*|\d+(?:-\d+)?)(?:\/\d+)?)*$/', $field)) {
                throw new \InvalidArgumentException(sprintf('Invalid cron expression: %s', $expression));
            }
        }

        $this->expression = implode(' ', $fields);
        return $this;
    }

    /**
     * Run every minute.
     *
     * @return $this
     */
    public function every_minute()
    {
        return $this->splice(1, '*');
    }

    /**
     * Run every five minutes.
     *
     * @return $this
     */
    public function every_five_minutes()
    {
        return $this->splice(1, '*/5');
    }

    /**
     * Run every ten minutes.
     *
     * @return $this
     */
    public function every_ten_minutes()
    {
        return $this->splice(1, '*/10');
    }

    /**
     * Run every fifteen minutes.
     *
     * @return $this
     */
    public function every_fifteen_minutes()
    {
        return $this->splice(1, '*/15');
    }

    /**
     * Run every thirty minutes.
     *
     * @return $this
     */
    public function every_thirty_minutes()
    {
        return $this->splice(1, '0,30');
    }

    /**
     * Run hourly, on the hour.
     *
     * @return $this
     */
    public function hourly()
    {
        return $this->splice(1, 0);
    }

    /**
     * Run hourly, at the given minute.
     *
     * @param int $minute
     *
     * @return $this
     */
    public function hourly_at($minute)
    {
        return $this->splice(1, (int) $minute);
    }

    /**
     * Run daily at midnight.
     *
     * @return $this
     */
    public function daily()
    {
        return $this->splice(1, 0)->splice(2, 0);
    }

    /**
     * Run daily at a time, '13:00'.
     *
     * @param string $time
     *
     * @return $this
     */
    public function daily_at($time)
    {
        if (! preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', (string) $time, $matches)) {
            throw new \InvalidArgumentException(sprintf("A time is written 'HH:MM': %s", $time));
        }

        return $this->splice(2, (int) $matches[1])->splice(1, (int) $matches[2]);
    }

    /**
     * Run twice a day, on the hour.
     *
     * @param int $first
     * @param int $second
     *
     * @return $this
     */
    public function twice_daily($first = 1, $second = 13)
    {
        return $this->splice(1, 0)->splice(2, (int) $first.','.(int) $second);
    }

    /**
     * Run weekly, on Sunday at midnight.
     *
     * @return $this
     */
    public function weekly()
    {
        return $this->splice(1, 0)->splice(2, 0)->splice(5, 0);
    }

    /**
     * Run weekly on a day (0 = Sunday .. 6 = Saturday) at a time.
     *
     * @param int    $day
     * @param string $time
     *
     * @return $this
     */
    public function weekly_on($day, $time = '0:00')
    {
        return $this->daily_at($time)->splice(5, (int) $day);
    }

    /**
     * Run monthly, on the first at midnight.
     *
     * @return $this
     */
    public function monthly()
    {
        return $this->splice(1, 0)->splice(2, 0)->splice(3, 1);
    }

    /**
     * Run monthly on a day of the month at a time.
     *
     * @param int    $day
     * @param string $time
     *
     * @return $this
     */
    public function monthly_on($day = 1, $time = '0:00')
    {
        return $this->daily_at($time)->splice(3, (int) $day);
    }

    /**
     * Run yearly, on January 1st at midnight.
     *
     * @return $this
     */
    public function yearly()
    {
        return $this->splice(1, 0)->splice(2, 0)->splice(3, 1)->splice(4, 1);
    }

    /**
     * Limit to Monday to Friday.
     *
     * @return $this
     */
    public function weekdays()
    {
        return $this->splice(5, '1-5');
    }

    /**
     * Limit to Saturday and Sunday.
     *
     * @return $this
     */
    public function weekends()
    {
        return $this->splice(5, '0,6');
    }

    /**
     * Limit to days of the week (0 = Sunday .. 6 = Saturday).
     *
     * @param array|int $days
     *
     * @return $this
     */
    public function days($days)
    {
        $days = is_array($days) ? $days : func_get_args();
        return $this->splice(5, implode(',', array_map('intval', $days)));
    }

    /**
     * Read the expression in another timezone than the application's.
     *
     * @param string $timezone
     *
     * @return $this
     */
    public function timezone($timezone)
    {
        $this->timezone = $timezone;
        return $this;
    }

    /**
     * Only run when the callback returns TRUE.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function when(\Closure $callback)
    {
        $this->filters[] = $callback;
        return $this;
    }

    /**
     * Skip the run when the callback returns TRUE.
     *
     * @param \Closure $callback
     *
     * @return $this
     */
    public function skip(\Closure $callback)
    {
        $this->rejects[] = $callback;
        return $this;
    }

    /**
     * Skip a run while the previous one is still going, on this server. The lock is
     * a file lock, so a crashed run never leaves it behind.
     *
     * @return $this
     */
    public function without_overlapping()
    {
        $this->without_overlapping = true;
        return $this;
    }

    /**
     * Run the event on one server only, when schedule:run runs on several. Needs a
     * cache driver the servers share (database, redis, memcached): the first server
     * to claim the minute in the cache runs it.
     *
     * @return $this
     */
    public function on_one_server()
    {
        $this->on_one_server = true;
        return $this;
    }

    /**
     * Name the event.
     *
     * @param string $description
     *
     * @return $this
     */
    public function name($description)
    {
        $this->description = (string) $description;
        return $this;
    }

    /**
     * Get the name schedule:list shows.
     *
     * @return string
     */
    public function summary()
    {
        if (! is_null($this->description)) {
            return $this->description;
        }

        return ('call' === $this->type) ? 'Closure' : $this->target;
    }

    /**
     * Check whether the event is due at the given time: the expression matches the
     * minute, and the when() and skip() conditions allow it.
     *
     * @param \DateTime $now
     *
     * @return bool
     */
    public function is_due(\DateTime $now)
    {
        if (! is_null($this->timezone)) {
            $now = clone $now;
            $now->setTimezone(new \DateTimeZone($this->timezone));
        }

        if (! static::matches($this->expression, $now)) {
            return false;
        }

        foreach ($this->filters as $filter) {
            if (! call_user_func($filter)) {
                return false;
            }
        }

        foreach ($this->rejects as $reject) {
            if (call_user_func($reject)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Run the event. TRUE when it succeeded, FALSE when the command exited with an
     * error, NULL when skipped: the previous run still holds the lock, or another
     * server took this run.
     *
     * @return bool|null
     */
    public function run()
    {
        $lock = null;

        if ($this->on_one_server && ! \System\Cache::add('schedule:'.$this->mutex().':'.date('YmdHi'), 1, 60)) {
            return null;
        }

        if ($this->without_overlapping) {
            $lock = @fopen(path('storage').'console'.DS.'schedule-'.$this->mutex().'.lock', 'c');

            if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
                $lock && fclose($lock);
                return null;
            }
        }

        try {
            $result = $this->execute();
        } catch (\Throwable $e) {
            $this->release($lock);
            throw $e;
        } catch (\Exception $e) {
            $this->release($lock);
            throw $e;
        }

        $this->release($lock);

        return $result;
    }

    /**
     * Run the target.
     *
     * @return bool
     */
    protected function execute()
    {
        if ('call' === $this->type) {
            call_user_func_array($this->target, $this->parameters);
            return true;
        }

        $command = $this->target;

        if ('command' === $this->type) {
            $env = Request::env();
            $command = escapeshellarg(defined('PHP_BINARY') && PHP_BINARY ? PHP_BINARY : 'php')
                .' '.escapeshellarg(path('base').'rakit').' '.$command
                .($env ? ' --env='.escapeshellarg($env) : '');
        }

        $status = 0;
        passthru($command, $status);

        return 0 === $status;
    }

    /**
     * Release an overlap lock.
     *
     * @param resource|null $lock
     */
    protected function release($lock)
    {
        if ($lock) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Get the lock name: what runs and when. A closure has no identity across
     * processes, so it needs a name().
     *
     * @return string
     */
    protected function mutex()
    {
        if ('call' === $this->type && is_null($this->description)) {
            throw new \LogicException('A scheduled closure needs a name() to use without_overlapping() or on_one_server().');
        }

        $what = ('call' === $this->type) ? $this->description : $this->target;

        return sha1(path('base').'|'.$this->type.'|'.$what.'|'.$this->expression);
    }

    /**
     * Replace one field of the expression.
     *
     * @param int        $position
     * @param int|string $value
     *
     * @return $this
     */
    protected function splice($position, $value)
    {
        $fields = explode(' ', $this->expression);
        $fields[$position - 1] = (string) $value;

        return $this->cron(implode(' ', $fields));
    }

    /**
     * Check a cron expression against a time. As in cron, when both the day of the
     * month and the day of the week are restricted, matching either is enough.
     *
     * @param string    $expression
     * @param \DateTime $now
     *
     * @return bool
     */
    public static function matches($expression, \DateTime $now)
    {
        list($minute, $hour, $day, $month, $weekday) = explode(' ', $expression);

        $dow = (int) $now->format('w');
        $day_ok = static::field($day, (int) $now->format('j'), 1, 31);
        $dow_ok = static::field($weekday, $dow, 0, 7) || (0 === $dow && static::field($weekday, 7, 0, 7));
        $days = ('*' !== $day && '*' !== $weekday) ? ($day_ok || $dow_ok) : ($day_ok && $dow_ok);

        return $days
            && static::field($minute, (int) $now->format('i'), 0, 59)
            && static::field($hour, (int) $now->format('G'), 0, 23)
            && static::field($month, (int) $now->format('n'), 1, 12);
    }

    /**
     * Check one cron field against a value.
     *
     * @param string $field
     * @param int    $value
     * @param int    $min
     * @param int    $max
     *
     * @return bool
     */
    protected static function field($field, $value, $min, $max)
    {
        foreach (explode(',', $field) as $part) {
            $step = 1;

            if (false !== strpos($part, '/')) {
                list($part, $step) = explode('/', $part, 2);
                $step = max(1, (int) $step);
            }

            if ('*' === $part) {
                list($start, $end) = [$min, $max];
            } elseif (false !== strpos($part, '-')) {
                list($start, $end) = array_map('intval', explode('-', $part, 2));
            } else {
                $start = (int) $part;
                $end = ($step > 1) ? $max : $start;
            }

            if ($value >= $start && $value <= $end && 0 === ($value - $start) % $step) {
                return true;
            }
        }

        return false;
    }
}
