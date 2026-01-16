# Job

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Pengetahuan Dasar](#pengetahuan-dasar)
-   [Setup](#setup)
-   [Membuat Job Class](#membuat-job-class)
-   [Dispatch Job](#dispatch-job)
-   [Menjalankan Worker](#menjalankan-worker)
-   [Queue Priority](#queue-priority)
-   [Without Overlapping](#without-overlapping)
-   [Scheduled Jobs](#scheduled-jobs)
-   [Supervisor Configuration](#supervisor-configuration)
-   [Event-Based (Cara Lama)](#event-based-cara-lama)
-   [Best Practices](#best-practices)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>

## Pengetahuan Dasar

Job System adalah komponen untuk mengantrekan dan menjalankan tugas secara asynchronous (background processing). Sistem ini menggunakan **auto-discovery** sehingga Anda hanya perlu membuat class Job dan langsung bisa dijalankan tanpa perlu registrasi manual.

<a id="setup"></a>

## Setup

### 1. Buat Tabel Jobs

```bash
php rakit job:table
php rakit migrate
```

### 2. Konfigurasi (Opsional)

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

<a id="membuat-job-class"></a>

## Membuat Job Class

Buat file di folder `application/jobs/`. Semua class harus extend `System\Job\Jobable`.

### Contoh: Send Email Job

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

### Contoh: Process Upload Job

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

### Contoh: Generate Report Job

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

<a id="dispatch-job"></a>

## Dispatch Job

### Cara 1: Via Job Class (Recommended)

```php
// Simple dispatch
SendEmail::dispatch([
    'to' => 'user@example.com',
    'subject' => 'Welcome!',
    'view' => 'emails.welcome',
    'data' => array('name' => 'John')
]);
```

### Cara 2: Via Job Facade

```php
use System\Job;

Job::dispatch('send-email', [
    'to' => 'user@example.com',
    'subject' => 'Welcome!'
]);
```

### Penggunaan di Controller

```php
class UserController extends BaseController
{
    public function action_register()
    {
        $user = new User;
        $user->name = Input::get('name');
        $user->email = Input::get('email');
        $user->save();

        // Kirim welcome email
        SendEmail::dispatch(array(
            'to' => $user->email,
            'subject' => 'Selamat Datang!',
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

        // Process di background
        ProcessUpload::dispatch(array(
            'file' => $path,
            'user_id' => Auth::user()->id
        ));

        return Response::json(array('message' => 'Processing...'));
    }
}
```

<a id="menjalankan-worker"></a>

## Menjalankan Worker

### Run All Queues

```bash
php rakit job:runall
```

### Run Specific Queues

```bash
php rakit job:runall --queue=default,high
```

### Dengan Retry & Sleep

```bash
php rakit job:runall --queue=high --retries=3 --sleep=1000
```

### Run Specific Job

```bash
php rakit job:run send-email
```

<a id="queue-priority"></a>

## Queue Priority

Gunakan method `on_queue()` untuk menentukan queue priority:

```php
// High priority (critical)
SendEmail::dispatch($data)->on_queue('high');

// Default priority (normal)
SendEmail::dispatch($data)->on_queue('default');

// Low priority (background tasks)
SendEmail::dispatch($data)->on_queue('low');
```

**Rekomendasi:**
- **high**: Email verifikasi, reset password, payment
- **default**: Notifikasi biasa, laporan
- **low**: Cleanup, analytics, maintenance

<a id="without-overlapping"></a>

## Without Overlapping

Gunakan `without_overlapping()` untuk mencegah job duplikat berjalan bersamaan:

```php
// Prevent duplicate report generation
GenerateReport::dispatch(array(
    'type' => 'monthly',
    'month' => date('Y-m')
))->without_overlapping();
```

Berguna untuk job yang hanya boleh berjalan satu kali, seperti generate report atau sync data.

<a id="scheduled-jobs"></a>

## Scheduled Jobs

### Schedule untuk Waktu Tertentu

```php
// Kirim email besok jam 10 pagi
$tomorrow = date('Y-m-d 10:00:00', strtotime('+1 day'));

SendEmail::dispatch($data, $tomorrow);

// Atau dengan method dispatch_at()
SendEmail::dispatch_at('2024-12-31 10:00:00', $data);
```

### Kombinasi dengan Queue & Overlapping

```php
// Schedule cleanup malam ini jam 23:00
$tonight = date('Y-m-d 23:00:00');

CleanupFiles::dispatch(array(
    'path' => storage_path('temp'),
    'days' => 7
), $tonight)->on_queue('low')->without_overlapping();
```

<a id="supervisor-configuration"></a>

## Supervisor Configuration

Untuk production, gunakan Supervisor untuk menjalankan worker secara otomatis:

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

<a id="event-based-cara-lama"></a>

## Event-Based (Cara Lama)

Job system tetap kompatibel dengan cara lama menggunakan event:

```php
// Daftarkan event listener
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

// Atau via facade
Job::dispatch('send-notification', $data);

// Jalankan job
Job::run('send-notification');
```

**Catatan:** Cara baru (class-based) lebih direkomendasikan karena lebih clean dan organized.

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
// ✅ GOOD - Kirim ID saja
ProcessOrder::dispatch(array('order_id' => $orderId));

// ❌ BAD - Kirim object besar
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

        throw $e; // Re-throw untuk retry
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

### 5. Prevent Duplicate untuk Job Penting

```php
// Report generation - hanya boleh 1 yang jalan
GenerateMonthlyReport::dispatch($data)->without_overlapping();

// Data sync - prevent concurrent sync
SyncExternalData::dispatch($data)->without_overlapping();
```
