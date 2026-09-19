# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Setup](#setup)
-   [Creating Job Class](#creating-job-class)
-   [Dispatch Job](#dispatch-job)
-   [Running Worker](#running-worker)
-   [Queue Priority](#queue-priority)
-   [Without Overlapping](#without-overlapping)
-   [Scheduled Jobs](#scheduled-jobs)
-   [Choosing Driver](#choosing-driver)
-   [Supervisor Configuration](#supervisor-configuration)
-   [Removing Job from Queue](#removing-job-from-queue)
-   [Event-Based (Old Way)](#event-based-old-way)
-   [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The job system queues work to be run in the background. Jobs are auto-discovered, so
a class in `application/jobs/` needs no registration.

The job system supports several drivers: **file**, **database**, **redis**, and **memcached**.

<a id="setup"></a>

## Setup

### 1. Create Jobs Table

Only needed for the **database** driver. The migration for the jobs and failed jobs tables
already ships in `application/migrations/`, so just run:

```bash
php rakit migrate
```

### 2. Configuration (Optional)

Edit `application/config/job.php`:

```php
return [
    'driver' => 'file',            // Default driver: file, database, redis, memcached
    'table' => 'jobs',             // Table for storing jobs (database driver)
    'failed_table' => 'failed_jobs',  // Table for failed jobs
    'max_job' => 50,               // Maximum jobs processed per batch
    'max_retries' => 1,            // Maximum attempts before a job is moved to failed jobs
    'sleep_ms' => 0,               // Sleep time between retry attempts (milliseconds)
    'logging' => false,            // Enable/disable logging
    'key' => 'rakit.job',          // Key prefix (redis and memcached drivers)
];
```

**Note:** If using **redis** driver, ensure the connection configuration is set in `application/config/database.php` (the `redis` key).
If using **memcached** driver, set it in `application/config/cache.php` (the `memcached` key).

<a id="creating-job-class"></a>

## Creating Job Class

Create files in the `application/jobs/` folder. All classes must extend `Jobable` class.

### Example: Send Email Job

File: `application/jobs/mailing.php`

```php
class Mailing_Job extends Jobable
{
    public function run()
    {
        $to = $this->get('to');
        $subject = $this->get('subject');
        $view = $this->get('view', 'emails.notification');
        $data = $this->get('data', []);

        Email::from('admin@site.com')
            ->to($to)
            ->subject($subject)
            ->html_body(View::make($view, $data)->render())
            ->send();
    }
}
```

### Example: Process Upload Job

File: `application/jobs/upload.php`

```php
class Upload_Job extends Jobable
{
    public function run()
    {
        $file = $this->get('file');
        $user_id = $this->get('user_id');

        // Process file (resize, optimize, etc)
        $this->process_image($file);

        Log::info('File processed: ' . $file);
    }

    protected function process_image($file)
    {
        // Image processing logic
    }
}
```

### Example: Generate Report Job

File: `application/jobs/reporting.php`

```php
class Reporting_Job extends Jobable
{
    public function run()
    {
        $type = $this->get('type', 'daily');
        $date = $this->get('date', date('Y-m-d'));

        $data = DB::table('orders')
            ->where('date', $date)
            ->get();

        $this->generate_pdf($data, $type, $date);
    }

    protected function generate_pdf($data, $type, $date)
    {
        // PDF generation logic
        $filename = $type . '_report_' . $date . '.pdf';
        // ... generate PDF ...
    }
}
```

### Available Methods in Jobable

A `Jobable` carries these:

-   `run()` - Abstract method that must be implemented, containing the job logic
-   `get($key, $default)` - Retrieve data from payload
-   `data()` - Retrieve all payload data
-   `dispatch($data, $dispatch_at)` - Static method to dispatch job
-   `dispatch_at($dispatch_at, $data)` - Static method for scheduled dispatch
-   `name()` - Static method to get job name

```php
class Sample_Job extends Jobable
{
    public function run()
    {
        // Retrieve data from payload
        $user_id = $this->get('user_id');
        $email = $this->get('email', 'default@example.com');

        // Retrieve all data
        $all_data = $this->data();

        // Job logic here
    }
}
```

<a id="dispatch-job"></a>

## Dispatch Job

### Method 1: Via Job Class (Recommended)

```php
// Simple dispatch
Mailing_Job::dispatch([
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'view' => 'emails.welcome',
    'data' => ['name' => 'John'],
]);
```

### Method 2: Via Job Facade

```php
Job::dispatch('send-email', [
    'to' => 'user@example.com',
    'subject' => 'Welcome!'
])->on_queue('default');
```

> Still supported, but dispatching through the job class is preferred.

### Usage in Controller

```php
class User_Controller extends Controller
{
    public function action_register()
    {
        $user = new User;
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->save();

        // Send welcome email
        Mailing_Job::dispatch([
            'to' => $user->email,
            'subject' => 'Welcome to our site!',
            'view' => 'emails.welcome',
            'data' => ['name' => $user->name],
        ]);

        return Redirect::to('login');
    }

    public function action_upload()
    {
        $file = Input::file('photo');
        $path = path('storage') . 'uploads' . DS . time() . '.jpg';

        move_uploaded_file($file['tmp_name'], $path);

        // Process in background
        Upload_Job::dispatch([
            'file' => $path,
            'user_id' => Auth::user()->id,
        ]);

        return Response::json(['message' => 'Processing...']);
    }
}
```

<a id="running-worker"></a>

## Running Worker

### Run All Queues

```bash
php rakit job:runall
```

It runs the due jobs of every queue, up to `max_job`, then exits. There is no loop, so
run it periodically from cron or a process manager.

### Run Specific Queues

```bash
php rakit job:runall --queue=default,high
```

`--queue` limits the worker to the named queues, separated by commas.

### With Retry & Sleep

```bash
php rakit job:runall --queue=high --retries=3 --sleep=1000
```

Available parameters:
- `--queue=name1,name2` - Which queues to process (default: all)
- `--retries=N` - How many attempts per job before it is marked as failed (default: `max_retries` config)
- `--sleep=N` - Sleep time between retry attempts in milliseconds (default: `sleep_ms` config)

### Run Specific Job

```bash
php rakit job:run send-email
```

Runs the due jobs of one queue, for testing or a manual run.

### Return Value

The `dispatch()` method will return a `System\Job\Pending` instance that can be
chained with other methods like `on_queue()`, `without_overlapping()`, and `via()`.

```php
$pending = Mailing_Job::dispatch([
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
]);

// $pending is an instance of System\Job\Pending
// You can do method chaining
$pending->on_queue('high')->without_overlapping();
```

<a id="queue-priority"></a>

## Queue Priority

`on_queue()` picks the queue a job goes to:

```php
// High priority (critical)
Mailing_Job::dispatch($data)->on_queue('high');

// Default priority (normal)
Mailing_Job::dispatch($data)->on_queue('default');

// Low priority (background tasks)
Mailing_Job::dispatch($data)->on_queue('low');
```

**Recommendations:**
- **high**: Verification emails, password reset, payment
- **default**: Regular notifications, reports
- **low**: Cleanup, analytics, maintenance

<a id="without-overlapping"></a>

## Without Overlapping

`without_overlapping()` keeps two copies of a job from running at once:

```php
// Prevent duplicate report generation
Reporting_Job::dispatch([
    'type' => 'monthly',
    'date' => date('Y-m-d'),
])->without_overlapping();
```

Useful for a report or a sync that must not run twice.

<a id="scheduled-jobs"></a>

## Scheduled Jobs

### Schedule for Specific Time

```php
// Send email tomorrow at 10 AM (string format)
$tomorrow = date('Y-m-d 10:00:00', strtotime('+1 day'));
Mailing_Job::dispatch($data, $tomorrow);

// Using dispatch_at() method
Mailing_Job::dispatch_at('2024-12-31 10:00:00', $data);

// Using timestamp
Mailing_Job::dispatch($data, time() + 3600); // 1 hour from now

// Using DateTime object
$datetime = new DateTime('2024-12-31 10:00:00');
Mailing_Job::dispatch($data, $datetime);

// Using Carbon (if available)
Mailing_Job::dispatch($data, Carbon::now()->addMinutes(30));
```

Supported formats for the `$dispatch_at` parameter:
-   `string` - Date format: `'Y-m-d H:i:s'` (example: `'2024-12-31 10:00:00'`)
-   `int` - Unix timestamp (example: `time() + 3600`)
-   `DateTime` - DateTime object
-   `Carbon` - Carbon object (if available)
-   `null` - Will run now (default)

### Combination with Queue & Overlapping

```php
// Schedule cleanup tonight at 11 PM
$tonight = date('Y-m-d 23:00:00');

Cleanup_Job::dispatch([
    'path' => path('storage') . 'temp',
    'days' => 7,
], $tonight)->on_queue('low')->without_overlapping();
```

<a id="choosing-driver"></a>

## Choosing Driver

Jobs use the driver from the configuration. `via()` picks another one for a single job:

```php
// Use file driver for this job
Mailing_Job::dispatch($data)->via('file');

// Use redis driver for this job
Upload_Job::dispatch($data)->via('redis');

// Use database driver for this job
Reporting_Job::dispatch($data)->via('database');

// Use memcached driver for this job
Cleanup_Job::dispatch($data)->via('memcached');
```

Useful for testing against another driver, or for splitting jobs by driver: the
critical ones in redis, the rest in the database.

### Full Combination

```php
// Dispatch with all options
Mailing_Job::dispatch([
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
], '2024-12-31 10:00:00')
    ->on_queue('high')
    ->without_overlapping()
    ->via('redis');
```

<a id="supervisor-configuration"></a>

## Supervisor Configuration

In production, Supervisor keeps the worker running. `job:runall` exits after each
batch, so `autorestart` is what starts it again, and `startsecs=0` stops Supervisor
from reading that quick exit as a failed start:

File: `/etc/supervisor/conf.d/rakit-worker.conf`

```ini
; High Priority Queue (2 workers)
[program:rakit-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:runall --queue=high --retries=3
autostart=true
autorestart=true
startsecs=0
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/rakit-high.log

; Default Queue (4 workers)
[program:rakit-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:runall --queue=default --retries=3
autostart=true
autorestart=true
startsecs=0
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/var/log/rakit-default.log

; Low Priority Queue (1 worker)
[program:rakit-low]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:runall --queue=low --retries=2
autostart=true
autorestart=true
startsecs=0
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/rakit-low.log
```

Reload Supervisor:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start rakit-high:*
sudo supervisorctl start rakit-default:*
sudo supervisorctl start rakit-low:*
```

Monitor status:

```bash
sudo supervisorctl status
```

<a id="removing-job-from-queue"></a>

## Removing Job from Queue

`forget()` removes a job from the queue:

```php
// Remove specific job from all queues
Job::forget('send-email');

// Remove specific job from specific queue
Job::forget('send-email', 'high');
```

Useful for a job that is no longer needed, one that errored and has to go by hand,
or a stuck one.

### Usage Example

```php
// Remove all pending notifications
if (Job::driver()->has_overlapping('send-notification', 'default')) {
    Job::forget('send-notification', 'default');
    echo 'Pending notifications cleared';
}
```

> **Note:** The `forget()` method only removes unprocessed jobs (and their failed job records).
> Jobs that are currently running will not be stopped.

<a id="event-based-old-way"></a>

## Event-Based (Old Way)

The old event-based approach still works, but it is **not recommended** for new projects.

```php
// Register event listener (in application/boot.php or application/routes.php)
Hook::listen('rakit.jobs.run: send-notification', function ($payload) {
    Email::from('admin@site.com')
        ->to($payload['to'])
        ->subject($payload['subject'])
        ->html_body(View::make('emails.notification', $payload)->render())
        ->send();
});

// Dispatch job with event name
Job::dispatch('send-notification', [
    'to' => 'user@example.com',
    'subject' => 'Hello',
]);
```

### Why Not Recommended?

1. **Not type-safe** - Payload is just an array, prone to typos
2. **Hard to track** - Event listeners scattered across files
3. **Not auto-discoverable** - Manual registration required
4. **Hard to test** - Event-based is harder for unit testing

### Migration to Class-Based

Migrating from event-based:

```php
// Old way (event-based)
Hook::listen('rakit.jobs.run: send-notification', function ($payload) {
    // Send notification email logic here
});

Job::dispatch('send-notification', ['to' => 'user@example.com']);
```

```php
// New way (class-based)
// File: application/jobs/notify.php
class Notify_Job extends Jobable
{
    public function run()
    {
        $to = $this->get('to');
        $subject = $this->get('subject');

        Email::from('admin@site.com')
            ->to($to)
            ->subject($subject)
            ->html_body(View::make('emails.notification', $this->data())->render())
            ->send();
    }
}

// Dispatch
Notify_Job::dispatch(['to' => 'user@example.com', 'subject' => 'Hello']);
```

<a id="best-practices"></a>

## Best Practices

### 1. Keep Jobs Simple

```php
// GOOD - Simple & focused
class Mailing_Job extends Jobable
{
    public function run()
    {
        Email::from('admin@site.com')->to($this->get('to'))->subject('Hello')->send();
    }
}

// BAD - Too complex
class Doall_Job extends Jobable
{
    public function run()
    {
        $this->notifyEmail();
        $this->updateDatabase();
        $this->callApi();
        $this->generateReport();
    }
}
```

### 2. Small Payload

```php
// GOOD - Send ID only
Ordering_Job::dispatch(['order_id' => $order_id]);

// BAD - Send large object
Ordering_Job::dispatch(['order' => $order_object]);
```

The payload is stored serialized, so a large object bloats the queue. Pass an ID and
read the data inside the job.

### 3. Handle Errors

```php
public function run()
{
    try {
        // Process job
        $this->processData();
    } catch (\Exception $e) {
        Log::error('Job failed: ' . $e->getMessage());

        // Notify admin if needed
        Mailing_Job::dispatch([
            'to' => 'admin@example.com',
            'subject' => 'Job Failed',
            'view' => 'emails.job-failed',
            'data' => ['error' => $e->getMessage()],
        ])->on_queue('high');

        // Re-throw for retry (if retries remain)
        throw $e;
    }
}
```

> **Note:** Jobs will automatically be retried according to the `max_retries` configuration if an exception is thrown.

### 4. Use Priority Wisely

```php
// Critical jobs
Verifying_Job::dispatch($data)->on_queue('high');
Pay_Job::dispatch($data)->on_queue('high');

// Normal jobs
Notify_Job::dispatch($data)->on_queue('default');

// Background tasks
Cleanup_Job::dispatch($data)->on_queue('low');
Statistics_Job::dispatch($data)->on_queue('low');
```

### 5. Prevent Duplicates for Important Jobs

```php
// Report generation - only one should run
Reporting_Job::dispatch($data)->without_overlapping();

// Data sync - prevent concurrent sync
Sync_Job::dispatch($data)->without_overlapping();
```

### 6. Use the Right Driver

```php
// Critical jobs - use redis (fast, in-memory)
Pay_Job::dispatch($data)->via('redis')->on_queue('high');

// Normal jobs - use database (persistent, reliable)
Mailing_Job::dispatch($data)->via('database');

// Development/testing - use file (simple, no setup)
Testing_Job::dispatch($data)->via('file');

// High throughput - use memcached (fast, distributed)
Logging_Job::dispatch($data)->via('memcached')->on_queue('low');
```

### 7. Monitoring and Logging

```php
public function run()
{
    $start_time = microtime(true);

    try {
        Log::info('Job started: ' . static::name(), $this->data());

        // Process job
        $this->processData();

        $duration = microtime(true) - $start_time;
        Log::info('Job completed: ' . static::name() . ' in ' . $duration . 's');
    } catch (\Exception $e) {
        Log::error('Job failed: ' . static::name() . ' - ' . $e->getMessage());
        throw $e;
    }
}
```

> **Tip:** Enable `'logging' => true` in job configuration for auto-logging.
