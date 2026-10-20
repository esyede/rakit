# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Setup](#setup)
-   [Creating Job Class](#creating-job-class)
-   [Dispatch Job](#dispatch-job)
    -   [Queue Priority](#queue-priority)
    -   [Without Overlapping](#without-overlapping)
    -   [Scheduled Jobs](#scheduled-jobs)
    -   [Choosing Driver](#choosing-driver)
-   [Job Chaining](#job-chaining)
-   [Job Batching](#job-batching)
-   [Running Worker](#running-worker)
    -   [Long-Running Worker](#long-running-worker)
    -   [Several Workers](#several-workers)
-   [Supervisor Configuration](#supervisor-configuration)
-   [Removing Job from Queue](#removing-job-from-queue)
-   [Failed Jobs](#failed-jobs)
-   [Event-Based (Old Way)](#event-based-old-way)
-   [Custom Driver](#custom-driver)
-   [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Jobs let you push slow work (sending mail, processing uploads, building reports) to a queue and run it later in the background. Job classes in `application/jobs/` (or a package's `jobs/` folder) are discovered automatically, so they need no registration. Queues can be stored with the **file**, **database**, **redis** or **memcached** driver.

<a id="setup"></a>

## Setup

The options live in `application/config/job.php`:

```php
return [
    'driver' => 'file',               // file, database, redis or memcached
    'table' => 'jobs',                // Jobs table (database driver)
    'failed_table' => 'failed_jobs',  // Failed jobs table (database driver)
    'max_job' => 50,                  // Jobs processed per run
    'max_retries' => 1,               // Attempts before a job is marked as failed
    'sleep_ms' => 0,                  // Pause between attempts, in milliseconds
    'retry_after' => 90,              // Seconds a worker holds a job it took
    'logging' => false,               // Log job activity
    'key' => 'rakit.job',             // Key prefix (redis and memcached drivers)
];
```

- **database:** the migration for both tables ships in `application/migrations/`; run `php rakit migrate`.
- **redis:** uses the `redis` connection in `application/config/database.php`.
- **memcached:** uses the `memcached` settings in `application/config/cache.php`.

<a id="creating-job-class"></a>

## Creating Job Class

A job is a class named `<File>_Job` that extends `Jobable` and implements `run()`. Read the dispatched data with `get($key, $default)`, or all of it with `data()`:

```php
// application/jobs/mailing.php

class Mailing_Job extends Jobable
{
    public function run()
    {
        $view = $this->get('view', 'emails.notification');

        Email::from('admin@site.com')
            ->to($this->get('to'))
            ->subject($this->get('subject'))
            ->html_body(View::make($view, $this->get('data', []))->render())
            ->send();
    }
}
```

The job's name is the slug of its class name (`Mailing_Job` → `mailing-job`), available as `Mailing_Job::name()`. That name is what `job:run` and `Job::forget()` take.

A job class can set its own attempts, and the seconds to wait between them, over the `max_retries` and `sleep_ms` config:

```php
class Mailing_Job extends Jobable
{
    public static $tries = 5;
    public static $backoff = 10; // seconds

    // ..
}
```

The wait happens in the worker, which runs nothing else meanwhile, so keep `$backoff` short.

<a id="dispatch-job"></a>

## Dispatch Job

Call `dispatch()` on the job class with the data to pass along:

```php
Mailing_Job::dispatch([
    'to' => $user->email,
    'subject' => 'Welcome!',
    'view' => 'emails.welcome',
    'data' => ['name' => $user->name],
]);
```

`dispatch()` returns a `System\Job\Pending` object for chaining the options below. The job is added to the queue when that object is destroyed (at the end of the statement, or at shutdown if you keep it in a variable); call `->dispatch()` on it to add it right away.

`Job::dispatch('send-email', $payload)` dispatches by name and still works, but the class-based form is preferred.

<a id="queue-priority"></a>

### Queue Priority

Jobs go to the `default` queue. `on_queue()` picks another, so workers can process urgent queues separately:

```php
Mailing_Job::dispatch($data)->on_queue('high');
Cleanup_Job::dispatch($data)->on_queue('low');
```

<a id="without-overlapping"></a>

### Without Overlapping

`without_overlapping()` skips the dispatch while another job of the same name, also dispatched with `without_overlapping()`, is still waiting in that queue. It suits reports and syncs that must not run twice:

```php
Reporting_Job::dispatch(['type' => 'monthly'])->without_overlapping();
```

<a id="scheduled-jobs"></a>

### Scheduled Jobs

Pass a run time as the second argument of `dispatch()`, or use `dispatch_at()`. A job is not run before that time:

```php
Mailing_Job::dispatch($data, '2024-12-31 10:00:00');   // Date string
Mailing_Job::dispatch($data, time() + 3600);           // Unix timestamp
Mailing_Job::dispatch($data, new DateTime('tomorrow')); // DateTime
Mailing_Job::dispatch($data, Carbon::now()->addMinutes(30));

Mailing_Job::dispatch_at('2024-12-31 10:00:00', $data);
```

`null` (the default) means now. Any string `Carbon::parse()` understands is accepted.

<a id="choosing-driver"></a>

### Choosing Driver

`via()` stores a single job with a driver other than the configured one:

```php
Upload_Job::dispatch($data)->via('redis');
```

All options can be combined:

```php
Mailing_Job::dispatch($data, '2024-12-31 10:00:00')
    ->on_queue('high')
    ->without_overlapping()
    ->via('redis');
```

<a id="job-chaining"></a>

## Job Chaining

A chain runs jobs one after another: each is queued only when the one before it succeeded, so a
step can rely on the work of the previous one. Pass the jobs as you would dispatch them; each
keeps its own data, queue and driver:

```php
Job::chain([
    Process_Podcast_Job::dispatch(['id' => $podcast->id]),
    Optimize_Podcast_Job::dispatch(['id' => $podcast->id])->on_queue('media'),
    Job::dispatch('publish-podcast', ['id' => $podcast->id]),
]);
```

A job that fails for good (after its attempts) stops the chain and is stored with the
[failed jobs](#failed-jobs). Retrying it with `job:retry` resumes the chain where it stopped.

<a id="job-batching"></a>

## Job Batching

A batch runs jobs side by side, as many at once as there are workers, and follows them as a
whole. It can queue a job when every job succeeded (`then()`), on the first failure
(`on_failure()`), and when every job ran, succeeded or not (`on_finish()`):

```php
$batch = Job::batch([
    Import_Csv_Job::dispatch(['file' => 'part-1.csv']),
    Import_Csv_Job::dispatch(['file' => 'part-2.csv']),
    Import_Csv_Job::dispatch(['file' => 'part-3.csv']),
])->name('import')
    ->then(Job::dispatch('import-done', ['user' => $user->id]))
    ->on_failure(Import_Failed_Job::dispatch(['user' => $user->id]))
    ->on_finish(Job::dispatch('import-cleanup'))
    ->dispatch();

return Redirect::to('imports/'.$batch->id);
```

Follow it later from its ID, e.g. for a progress bar:

```php
$batch = Job::find_batch($id);

$batch->progress();        // 0-100
$batch->processed_jobs();  // and total_jobs, pending_jobs, failed_jobs
$batch->finished();        // every job ran
$batch->has_failures();
$batch->cancel();          // jobs not run yet are dropped by the worker
$batch->cancelled();
$batch->delete();
```

By default the first job that fails for good cancels the batch: the jobs that have not run are
dropped, `on_failure()` and `on_finish()` are queued, `then()` is not. Call `allow_failures()`
to keep running the others. A job of the batch, and its callback jobs, reach it with
`$this->batch()`, e.g. to stop a long loop early:

```php
public function run()
{
    foreach ($this->rows() as $row) {
        if ($this->batch()->cancelled()) {
            return;
        }

        // ..
    }
}
```

The batch's state is kept in the [cache](/docs/cache) for 7 days (`Job\Batch::$minutes`), under
a cache lock so workers finishing jobs at once do not overwrite each other. Workers on several
servers need a shared cache driver (database, redis, memcached); the `file` driver works for
workers on one server. A failed job that `job:retry` runs again successfully is no longer
counted as failed. Callbacks are jobs, not closures: closures cannot travel through a queue.

<a id="running-worker"></a>

## Running Worker

`job:runall` runs the due jobs of every queue (up to `max_job`), then exits. It does not loop, so run it from cron or a process manager:

```bash
php rakit job:runall
php rakit job:runall --queue=high,default --retries=3 --sleep=1000
```

| Option        | Description                                              | Default            |
| ------------- | -------------------------------------------------------- | ------------------ |
| `--queue`     | Comma-separated queues to process                        | all queues         |
| `--retries`   | Attempts per job before it is marked as failed           | `max_retries`      |
| `--sleep`     | Pause between attempts, in milliseconds                  | `sleep_ms`         |

`job:run` runs the due jobs with the given name(s), from any queue — handy for testing:

```bash
php rakit job:run mailing-job
```

<a id="long-running-worker"></a>

### Long-Running Worker

`job:work` keeps running jobs as they become due, waiting `--idle` seconds when the queue is empty,
instead of exiting after one batch. It takes the `job:runall` options, plus limits after which it
exits, for the process manager to start a fresh one:

```bash
php rakit job:work --queue=high,default --max-time=3600
```

| Option        | Description                                      | Default |
| ------------- | ------------------------------------------------ | ------- |
| `--idle`      | Seconds to wait when no job is due               | `3`     |
| `--max-jobs`  | Exit after this many jobs                        | no limit |
| `--max-time`  | Exit after this many seconds                     | no limit |
| `--memory`    | Exit once memory use reaches this many MB        | `128`   |

With the `pcntl` extension, `SIGTERM` and `SIGINT` (`supervisorctl stop`, Ctrl+C) let the current
job finish before the worker exits, so a deploy never cuts a job in half. Restart the workers on
every deploy: a running worker keeps the code it started with. A limit such as `--max-time=3600`
also renews long-lived database connections.

<a id="several-workers"></a>

### Several Workers

Any number of workers can share a queue, on every driver. A worker claims each job before running
it, so a job runs once even when several workers see it. The claim lasts `retry_after` seconds: if
the worker dies, the job runs again after that instead of being lost. Keep `retry_after` longer
than your slowest job, otherwise a job still running can be started a second time.

<a id="supervisor-configuration"></a>

## Supervisor Configuration

In production, let Supervisor keep workers running. `job:work` exits only on a limit or a signal, and `autorestart` starts it again. (With `job:runall`, which exits after each batch, add `startsecs=0` so Supervisor does not treat the quick exit as a failed start.) Add one program per queue:

```ini
; /etc/supervisor/conf.d/rakit-worker.conf

[program:rakit-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:work --queue=high --retries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=120
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/rakit-high.log

[program:rakit-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:work --queue=default --retries=3 --max-time=3600
autostart=true
autorestart=true
stopwaitsecs=120
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/rakit-default.log
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rakit-high:* rakit-default:*
sudo supervisorctl status
```

<a id="removing-job-from-queue"></a>

## Removing Job from Queue

`Job::forget()` removes the waiting jobs of a name, from every queue or from one, along with their failed records. Jobs already running are not stopped:

```php
Job::forget('mailing-job');
Job::forget('mailing-job', 'high');
```

`Job::has_overlapping($name, $queue)` tells whether a job of that name dispatched with `without_overlapping()` is waiting in a queue:

```php
if (Job::has_overlapping('mailing-job', 'default')) {
    Job::forget('mailing-job', 'default');
}
```

<a id="failed-jobs"></a>

## Failed Jobs

A job that throws an exception is retried until its attempts run out. Then it is removed from the queue and recorded as failed: in `failed_table` (database), as a `failed__*.job.php` file (file), or under the `failed` keys (redis, memcached).

Retries are silent; only the final failure fires `rakit.jobs.failed`, once per job, on every driver:

```php
// application/hooks.php
Hook::listen('rakit.jobs.failed', function ($name, $payloads, $exception, $attempts) {
    Log::error('Job '.$name.' failed after '.$attempts.' attempts: '.$exception->getMessage());
});
```

This one event covers every job. To react to a single job, listen to `rakit.jobs.run: [job_name]`.

List the failed jobs, and put them back on their queue once the cause is fixed (file and database
drivers):

```bash
php rakit job:failed
php rakit job:retry 01M5BNT8696D6W2PDECYPV97CY
php rakit job:retry all
```

The same is available as `Job::failures()` and `Job::retry($id)`.

<a id="event-based-old-way"></a>

## Event-Based (Old Way)

Before job classes, jobs were plain hook listeners dispatched by name. This still works but is not recommended: payloads are untyped arrays, listeners are scattered and must be registered by hand, and they are harder to test.

```php
// application/hooks.php
Hook::listen('rakit.jobs.run: send-notification', function ($payload) {
    Email::from('admin@site.com')
        ->to($payload['to'])
        ->subject($payload['subject'])
        ->send();
});

Job::dispatch('send-notification', ['to' => 'user@example.com', 'subject' => 'Hello']);
```

To migrate, move the listener body into the `run()` method of a `Jobable` class (reading `$this->get('to')` instead of `$payload['to']`) and dispatch it with `Notify_Job::dispatch([...])`.

<a id="custom-driver"></a>

## Custom Driver

Register your own driver with `Job::extend()`. It should extend `System\Job\Drivers\Driver` and implement `add()`, `has_overlapping()`, `forget()`, `run()` and `runall()`. To reuse the built-in attempts and failure handling, have `run()` and `runall()` pass the due jobs to `$this->work()`, and implement `claim()`, `complete()` and `bury()`:

```php
Job::extend('mydriver', function () {
    return new Mydriver();
});
```

`Job::driver('mydriver')` returns a driver instance; static calls on `Job` go to the default driver.

<a id="best-practices"></a>

## Best Practices

- **Keep each job focused** on one task; dispatch several jobs rather than one that does everything.
- **Keep payloads small.** They are stored serialized, so pass an ID (`['order_id' => $id]`) and load the record inside the job.
- **Let exceptions propagate.** Retries and failed-job handling only happen when `run()` throws; if you catch to log, re-throw.
- **Turn on `'logging' => true`** in the job config to log job activity automatically.
