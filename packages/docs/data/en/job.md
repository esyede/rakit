# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Setup](#setup)
-   [Creating Job Classes](#creating-job-classes)
-   [Dispatching Jobs](#dispatching-jobs)
-   [Running Workers](#running-workers)
-   [Queue Priority](#queue-priority)
-   [Without Overlapping](#without-overlapping)
-   [Scheduled Jobs](#scheduled-jobs)
-   [Supervisor Configuration](#supervisor-configuration)
-   [Event-Based (Legacy)](#event-based-legacy)
-   [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Job System is a component for queuing and running tasks asynchronously (background processing). This system uses **auto-discovery** so you just need to create a Job class and it's ready to run without manual registration.

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
    'driver' => 'database', // file, database, redis, memcached
    'table' => 'rakit_jobs',
    'failed_table' => 'rakit_failed_jobs',
    'max_job' => 100,
    'max_retries' => 3,
    'sleep_ms' => 1000,
    'logging' => true,
];
```

<a id="creating-job-classes"></a>

## Creating Job Classes

Create files in `application/jobs/` folder. All classes must extend `System\Job\Jobable`.

### Example: Send Email Job

File: `application/jobs/SendEmail.php`

```php
<?php

use System\Job\Jobable;
use System\Mail;

class SendEmail extends Jobable
{
    public function run()
    {
        $to = $this->get('to');
        $subject = $this->get('subject');
        $view = $this->get('view', 'emails.notification');
        $data = $this->get('data', array());

        Mail::send($view, $data, function($mail) use ($to, $subject) {
            $mail->to($to)->subject($subject);
        });
    }
}
```

### Example: Process Upload Job

File: `application/jobs/ProcessUpload.php`

```php
<?php

use System\Job\Jobable;
use System\Log;

class ProcessUpload extends Jobable
{
    public function run()
    {
        $file = $this->get('file');
        $userId = $this->get('user_id');

        // Process file (resize, optimize, etc)
        $this->processImage($file);

        Log::info('File processed: ' . $file);
    }

    protected function processImage($file)
    {
        // Image processing logic
    }
}
```

### Example: Generate Report Job

File: `application/jobs/GenerateReport.php`

```php
<?php

use System\Job\Jobable;
use System\Database as DB;

class GenerateReport extends Jobable
{
    public function run()
    {
        $type = $this->get('type', 'daily');
        $date = $this->get('date', date('Y-m-d'));

        $data = DB::table('orders')
            ->where('date', $date)
            ->get();

        $this->generatePDF($data, $type);
    }

    protected function generatePDF($data, $type)
    {
        // PDF generation logic
    }
}
```

<a id="dispatching-jobs"></a>

## Dispatching Jobs

### Method 1: Via Job Class (Recommended)

```php
// Simple dispatch
SendEmail::dispatch(array(
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'view' => 'emails.welcome',
    'data' => array('name' => 'John')
));
```

### Method 2: Via Job Facade

```php
use System\Job;

Job::dispatch('send-email', [
    'to' => 'user@example.com',
    'subject' => 'Welcome!'
]);
```

### Usage in Controllers

```php
class UserController extends BaseController
{
    public function action_register()
    {
        $user = new User;
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->save();

        // Send welcome email
        SendEmail::dispatch(array(
            'to' => $user->email,
            'subject' => 'Welcome!',
            'view' => 'emails.welcome',
            'data' => array('user' => $user)
        ));

        return Redirect::to('login');
    }

    public function action_upload()
    {
        $file = Input::file('photo');
        $path = storage_path('uploads/' . time() . '.jpg');

        move_uploaded_file($file['tmp_name'], $path);

        // Process in background
        ProcessUpload::dispatch(array(
            'file' => $path,
            'user_id' => Auth::user()->id
        ));

        return Response::json(array('message' => 'Processing...'));
    }
}
```

<a id="running-workers"></a>

## Running Workers

### Run All Queues

```bash
php rakit job:runall
```

### Run Specific Queues

```bash
php rakit job:runall --queue=default,high
```

### With Retry & Sleep

```bash
php rakit job:runall --queue=high --retries=3 --sleep=1000
```

### Run Specific Job

```bash
php rakit job:run send-email
```

<a id="queue-priority"></a>

## Queue Priority

Use `on_queue()` method to specify queue priority:

```php
// High priority (critical)
SendEmail::dispatch($data)->on_queue('high');

// Default priority (normal)
SendEmail::dispatch($data)->on_queue('default');

// Low priority (background tasks)
SendEmail::dispatch($data)->on_queue('low');
```

**Recommendations:**
- **high**: Email verification, password reset, payment
- **default**: Regular notifications, reports
- **low**: Cleanup, analytics, maintenance

<a id="without-overlapping"></a>

## Without Overlapping

Use `without_overlapping()` to prevent duplicate jobs from running concurrently:

```php
// Prevent duplicate report generation
GenerateReport::dispatch(array(
    'type' => 'monthly',
    'month' => date('Y-m')
))->without_overlapping();
```

Useful for jobs that should only run once at a time, like generating reports or syncing data.

<a id="scheduled-jobs"></a>

## Scheduled Jobs

### Schedule for Specific Time

```php
// Send email tomorrow at 10 AM
$tomorrow = date('Y-m-d 10:00:00', strtotime('+1 day'));

SendEmail::dispatch($data, $tomorrow);

// Or using dispatch_at() method
SendEmail::dispatch_at('2024-12-31 10:00:00', $data);
```

### Combine with Queue & Overlapping

```php
// Schedule cleanup tonight at 23:00
$tonight = date('Y-m-d 23:00:00');

CleanupFiles::dispatch(array(
    'path' => storage_path('temp'),
    'days' => 7
), $tonight)->on_queue('low')->without_overlapping();
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

<a id="event-based-legacy"></a>

## Event-Based (Legacy)

Job system is still compatible with the legacy event-based approach:

```php
// Register event listener
Event::listen('rakit.jobs.run: send-notification', function($payload) {
    Mail::send('emails.notification', $payload, function($mail) use ($payload) {
        $mail->to($payload['to'])->subject($payload['subject']);
    });
});

// Dispatch job
Job::add('send-notification', array(
    'to' => 'user@example.com',
    'subject' => 'Hello'
));

// Or via facade
Job::dispatch('send-notification', $data);

// Run job
Job::run('send-notification');
```

**Note:** The new class-based approach is recommended as it's cleaner and more organized.

<a id="best-practices"></a>

## Best Practices

### 1. Keep Jobs Simple

```php
// ✅ GOOD - Simple & focused
class SendEmail extends Jobable
{
    public function run()
    {
        Mail::send(...);
    }
}

// ❌ BAD - Too complex
class DoEverything extends Jobable
{
    public function run()
    {
        $this->sendEmail();
        $this->updateDatabase();
        $this->callApi();
        $this->generateReport();
    }
}
```

### 2. Small Payload

```php
// ✅ GOOD - Send ID only
ProcessOrder::dispatch(array('order_id' => $orderId));

// ❌ BAD - Send large object
ProcessOrder::dispatch(array('order' => $orderObject));
```

### 3. Handle Errors

```php
public function run()
{
    try {
        // Process job
        $this->processData();
    } catch (\Exception $e) {
        Log::error('Job failed: ' . $e->getMessage());

        // Notify admin
        SendEmail::dispatch(array(
            'to' => 'admin@example.com',
            'subject' => 'Job Failed',
            'view' => 'emails.job-failed',
            'data' => array('error' => $e->getMessage())
        ))->on_queue('high');

        throw $e; // Re-throw for retry
    }
}
```

### 4. Use Priority Wisely

```php
// Critical jobs
SendVerificationEmail::dispatch($data)->on_queue('high');
ProcessPayment::dispatch($data)->on_queue('high');

// Normal jobs
SendNotification::dispatch($data)->on_queue('default');

// Background tasks
CleanupFiles::dispatch($data)->on_queue('low');
UpdateStatistics::dispatch($data)->on_queue('low');
```

### 5. Prevent Duplicate for Important Jobs

```php
// Report generation - only one should run
GenerateMonthlyReport::dispatch($data)->without_overlapping();

// Data sync - prevent concurrent sync
SyncExternalData::dispatch($data)->without_overlapping();
```
