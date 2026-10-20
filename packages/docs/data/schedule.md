# Task Scheduling

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Running the Scheduler](#running-the-scheduler)
-   [Defining Events](#defining-events)
-   [Frequencies](#frequencies)
-   [Conditions](#conditions)
-   [Preventing Overlaps](#preventing-overlaps)
-   [Listing Events](#listing-events)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Instead of one cron entry per task, add a single entry that runs `php rakit schedule:run` every
minute, and define the tasks in PHP. Each run executes the events due that minute, one after the
other. An event that fails is logged, and the next ones still run.

<a id="running-the-scheduler"></a>

## Running the Scheduler

Add this line to the server's crontab (`crontab -e`), with the path to your application:

```bash
* * * * * cd /path/to/app && php rakit schedule:run >> /dev/null 2>&1
```

During development, run `php rakit schedule:run` by hand. The `--env=` option is passed on to
scheduled console commands.

<a id="defining-events"></a>

## Defining Events

Define events in a `rakit.schedule` listener in `application/hooks.php`. The scheduler fires it
when it runs, so web requests never build the list. Packages can listen to it as well.

```php
Hook::listen('rakit.schedule', function () {
    // A closure, run inside the scheduler.
    Schedule::call(function () {
        DB::table('password_resets')->where('created_at', '<', now()->subDays(1))->delete();
    })->daily()->name('prune password resets');

    // A console command, run as 'php rakit <command>' in its own process.
    Schedule::command('job:runall --queue=emails')->every_five_minutes();

    // A shell command.
    Schedule::exec('/usr/local/bin/backup.sh')->daily_at('02:30');
});
```

A closure may take arguments: `Schedule::call($callback, [$a, $b])`.

<a id="frequencies"></a>

## Frequencies

| Method | Runs |
| --- | --- |
| `cron('*/5 9-17 * * 1-5')` | on a cron expression |
| `every_minute()` | every minute (the default) |
| `every_five_minutes()`, `every_ten_minutes()`, `every_fifteen_minutes()`, `every_thirty_minutes()` | every 5, 10, 15 or 30 minutes |
| `hourly()` / `hourly_at(17)` | every hour on the hour / at minute 17 |
| `daily()` / `daily_at('13:00')` | every day at midnight / at 13:00 |
| `twice_daily(1, 13)` | at 01:00 and 13:00 |
| `weekly()` / `weekly_on(1, '8:00')` | Sunday at midnight / Monday at 08:00 |
| `monthly()` / `monthly_on(4, '15:00')` | the 1st at midnight / the 4th at 15:00 |
| `yearly()` | January 1st at midnight |
| `weekdays()`, `weekends()`, `days(1, 3)` | only on those days (0 = Sunday .. 6 = Saturday) |

The methods change parts of the expression, so they combine:

```php
Schedule::command('report:send')->weekdays()->hourly_at(15); // 15 * * * 1-5
```

A cron field takes `*`, a number, a range (`1-5`), a list (`1,15`) and a step (`*/10`, `0-30/10`,
`5/15`). Names such as `MON` are not supported. As in cron, when both the day of the month and the
day of the week are restricted, an event runs on either.

Expressions are read in the application timezone (`application.timezone`). Use `timezone()` for
another one:

```php
Schedule::command('report:send')->daily_at('08:00')->timezone('Asia/Jakarta');
```

<a id="conditions"></a>

## Conditions

`when()` runs the event only when its callback returns `true`; `skip()` skips it when its callback
does. Both are checked every time the event is due:

```php
Schedule::command('sitemap:build')->daily()
    ->when(function () {
        return Config::get('application.sitemap');
    })
    ->skip(function () {
        return is_file(path('storage').'maintenance');
    });
```

<a id="preventing-overlaps"></a>

## Preventing Overlaps

An event due every minute that sometimes takes longer would start again while still running.
`without_overlapping()` skips a run while the previous one still holds its lock:

```php
Schedule::command('job:runall')->every_minute()->without_overlapping();
```

The lock is a file lock in `storage/console`, released when the run ends, also when it crashes.
It covers one server. A scheduled closure needs a `name()` to be locked, because a closure has no
identity of its own across runs.

> The lock needs a filesystem that enforces `flock()`. Local disks do, and so do Docker bind
> mounts on Linux; Docker Desktop bind mounts on macOS and Windows, and some network mounts, accept
> every lock, so overlaps are not prevented there. Keep `storage/` on a local disk in production.

When `schedule:run` runs on several servers, `on_one_server()` lets only the first server that
claims the minute run the event (a closure needs a `name()` here too). It needs a cache driver the
servers share (`database`, `redis` or `memcached`):

```php
Schedule::command('report:send')->daily_at('08:00')->on_one_server();
```

<a id="listing-events"></a>

## Listing Events

```bash
php rakit schedule:list
```

```
Expression         | Timezone         | Event
-------------------+------------------+-------------------------------
0 0 * * *          | UTC              | prune password resets
*/5 * * * *        | UTC              | job:runall --queue=emails
```
