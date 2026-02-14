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

Job System is a component for queuing and running tasks asynchronously (background processing).
This system uses **auto-discovery** so you only need to create a Job class and it can be run directly without manual registration.

The job system supports several drivers: **file**, **database**, **redis**, and **memcached**.

<a id="setup"></a>

## Setup

### 1. Create Jobs Table

```bash
php rakit job:table
php rakit migrate
```

### 2. Configuration (Optional)

Edit `application/config/job.php`:

```php
return [
    'driver' => 'database',        // Default driver: file, database, redis, memcached
    'table' => 'rakit_jobs',       // Table for storing jobs (database driver)
    'failed_table' => 'rakit_failed_jobs',  // Table for failed jobs
    'max_job' => 100,              // Maximum jobs processed per batch
    'max_retries' => 3,            // Maximum retries for failed jobs
    'sleep_ms' => 1000,            // Sleep time between polling (milliseconds)
    'logging' => true,             // Enable/disable logging
];
```

**Note:** If using **redis** or **memcached** driver, ensure the connection configuration is set in `application/config/cache.php`.

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

        Mail::send('emails.notification', $data, function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });
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
        $userId = $this->get('user_id');

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

        $this->generate_pdf($data, $type);
    }

    protected function generate_pdf($data, $type)
    {
        // PDF generation logic
        $filename = $type . '_report_' . $date . '.pdf';
        // ... generate PDF ...
    }
}
```

### Available Methods in Jobable

When creating a Job class, you can use the following methods:

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
        $userId = $this->get('user_id');
        $email = $this->get('email', 'default@example.com');

        // Retrieve all data
        $allData = $this->data();

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

> **Note:** This method can still be used, but dispatching via Job class (method 1) is more recommended.

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
        $path = storage_path('uploads/' . time() . '.jpg');

        move_uploaded_file($file['tmp_name'], $path);

        // Process in background
        Upload_Job::dispatch([
            'file' => $path,
            'user_id' => Auth::id(),
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

This command will run all jobs from all queues continuously (infinite loop).
The worker will continuously monitor and process new incoming jobs.

### Run Specific Queues

```bash
php rakit job:runall --queue=default,high
```

You can limit the worker to only process specific queues with the `--queue` parameter.
Separate with commas for multiple queues.

### With Retry & Sleep

```bash
php rakit job:runall --queue=high --retries=3 --sleep=1000
```

Available parameters:
- `--queue=name1,name2` - Which queues to process (default: all)
- `--retries=N` - How many times to retry failed jobs (default: 1)
- `--sleep=N` - Sleep time between polling in milliseconds (default: 0)

### Run Specific Job

```bash
php rakit job:run send-email
```

This command will run a specific job based on name. Useful for testing or manual execution.

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

Use the `on_queue()` method to specify queue priority (which queue to use):

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

Use `without_overlapping()` to prevent duplicate jobs from running simultaneously:

```php
// Prevent duplicate report generation
Reporting_Job::dispatch([
    'type' => 'monthly',
    'date' => date('Y-m-d'),
])->without_overlapping();
```

Useful for jobs that should only run once, such as generating reports or syncing data.

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

By default, jobs will use the driver set in the configuration.
However, you can choose a different driver for a specific job using the `via()` method:

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

The `via()` method is useful when you want to:
-   Use a different driver for specific jobs
-   Test with different drivers
-   Separate jobs based on driver (e.g.: critical jobs in redis, normal jobs in database)

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

For production, use Supervisor to run workers automatically:

File: `/etc/supervisor/conf.d/rakit-worker.conf`

```ini
; High Priority Queue (2 workers)
[program:rakit-high]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/myapp/rakit job:runall --queue=high --retries=3
autostart=true
autorestart=true
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

You can remove jobs from the queue using the `forget()` method:

```php
// Remove specific job from default queue
Job::forget('send-email');

// Remove specific job from specific queue
Job::forget('send-email', 'high');

// Remove job from all queues
Job::forget('send-email', null);
```

This method is useful when:
- Job is no longer needed
- Job encounters an error and needs manual removal
- Cleanup stuck jobs

### Usage Example

```php
// Remove all pending notifications
if (Job::driver()->has_overlapping('send-notification', 'default')) {
    Job::forget('send-notification', 'default');
    echo 'Pending notifications cleared';
}
```

> **Note:** The `forget()` method only removes unprocessed jobs.
> Jobs that are currently running will not be stopped.

<a id="event-based-old-way"></a>

## Event-Based (Old Way)

The job system is still compatible with the old event-based approach. However, this method is **not recommended** for new projects.

```php
// Register event listener (in bootstrap/start.php or routes.php)
Event::listen('rakit.jobs.run: send-notification', function ($payload) {
    Mail::send('emails.notification', $payload, function ($mail) use ($payload) {
        $mail->to($payload['to'])->subject($payload['subject']);
    });
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

If you are still using event-based, here's how to migrate:

```php
// Old way (event-based)
Event::listen('rakit.jobs.run: send-notification', function ($payload) {
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

        Mail::send('emails.notification', $this->data(), function ($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });
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
        Mail::send(...);
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
Ordering_Job::dispatch(['order_id' => $orderId]);

// BAD - Send large object
Ordering_Job::dispatch(['order' => $orderObject]);
```

**Reason:**
-   Payload is stored as a serialized string
-   Large objects will increase database/file size
-   Better to fetch data inside the job using ID

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
UpdateStatistics::dispatch($data)->on_queue('low');
```

### 5. Prevent Duplicates for Important Jobs

```php
// Report generation - only one should run
GenerateMonthlyReport::dispatch($data)->without_overlapping();

// Data sync - prevent concurrent sync
SyncExternalData::dispatch($data)->without_overlapping();
```

### 6. Use the Right Driver

```php
// Critical jobs - use redis (fast, in-memory)
Pay_Job::dispatch($data)->via('redis')->on_queue('high');

// Normal jobs - use database (persistent, reliable)
Mailing_Job::dispatch($data)->via('database');

// Development/testing - use file (simple, no setup)
TestJob::dispatch($data)->via('file');

// High throughput - use memcached (fast, distributed)
LogEvent::dispatch($data)->via('memcached')->on_queue('low');
```

### 7. Monitoring and Logging

```php
public function run()
{
    $startTime = microtime(true);

    try {
        Log::info('Job started: ' . static::name(), $this->data());

        // Process job
        $this->processData();

        $duration = microtime(true) - $startTime;
        Log::info('Job completed: ' . static::name() . ' in ' . $duration . 's');
    } catch (\Exception $e) {
        Log::error('Job failed: ' . static::name() . ' - ' . $e->getMessage());
        throw $e;
    }
}
```

> **Tip:** Enable `'logging' => true` in job configuration for auto-logging.
