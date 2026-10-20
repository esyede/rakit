<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Hook;
use System\Log;
use System\Schedule as Scheduler;

class Schedule extends Command
{
    /**
     * Run the events due this minute. Called by cron every minute:
     * '* * * * * cd /path/to/app && php rakit schedule:run >> /dev/null 2>&1'.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $this->collect();
        $due = Scheduler::due();

        if (empty($due)) {
            echo $this->info('No scheduled events are due.');
            return;
        }

        foreach ($due as $event) {
            echo $this->info('Running: '.$event->summary());

            try {
                $result = $event->run();
            } catch (\Throwable $e) {
                $this->failed($event, $e);
                continue;
            } catch (\Exception $e) {
                $this->failed($event, $e);
                continue;
            }

            if (is_null($result)) {
                echo $this->warning('Skipped, still running: '.$event->summary());
            } elseif (false === $result) {
                Log::error('Scheduled event failed: '.$event->summary());
                echo $this->error('Failed: '.$event->summary());
            }
        }
    }

    /**
     * Dispatch 'schedule:list' to lists() below (for php 5.4 compat).
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        if ('list' === strtolower($method)) {
            return call_user_func_array([$this, 'lists'], $parameters);
        }

        throw new \Exception(sprintf('Command method is not callable: %s', $method));
    }

    /**
     * List every scheduled event.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function lists(array $arguments = [])
    {
        $this->collect();

        if (empty(Scheduler::$events)) {
            echo $this->info('No events are scheduled.');
            return;
        }

        $lines = str_pad('Expression', 18).' | '.str_pad('Timezone', 16).' | Event'.PHP_EOL;
        $lines .= str_repeat('-', 18).'-+-'.str_repeat('-', 16).'-+-'.str_repeat('-', 30).PHP_EOL;

        foreach (Scheduler::$events as $event) {
            $timezone = $event->timezone ?: date_default_timezone_get();
            $lines .= str_pad($event->expression, 18).' | '.str_pad($timezone, 16).' | '.$event->summary().PHP_EOL;
        }

        echo $this->info($lines);
    }

    /**
     * Let the application and packages define their events, once per process.
     */
    protected function collect()
    {
        if (empty(Scheduler::$events)) {
            Hook::fire('rakit.schedule');
        }
    }

    /**
     * Report an event that threw, so the next events still run.
     *
     * @param \System\Schedule\Event $event
     * @param \Throwable|\Exception  $e
     */
    protected function failed($event, $e)
    {
        Log::error('Scheduled event failed: '.$event->summary(), ['exception' => $e]);
        echo $this->error('Failed: '.$event->summary().' ('.$e->getMessage().')');
    }
}
