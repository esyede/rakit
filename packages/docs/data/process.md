# Processes & Concurrency

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Running Commands](#running-commands)
    -   [Options](#options)
    -   [Streaming Output](#streaming-output)
    -   [Asynchronous Processes](#asynchronous-processes)
-   [Concurrency](#concurrency)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

`Process` runs an external command (git, ffmpeg, a backup script) and gives back its output and
exit code. `Concurrency` runs PHP closures at the same time and collects their results.

<a id="running-commands"></a>

## Running Commands

```php
$result = Process::run(['git', 'log', '-1', '--format=%H']);

if ($result->successful()) {
    $commit = trim($result->output());
}

$result->exit_code();    // 0
$result->error_output(); // stderr
$result->failed();
$result->ensure_successful(); // throws a RuntimeException with the exit code and stderr
```

A command given as an **array** is a program and its arguments: each argument is escaped, so
user input cannot inject shell syntax. A **string** is a shell line, with pipes, redirections and
`&&`; never put user input in one unescaped:

```php
Process::run(['convert', $upload, '-resize', '200x200', $thumbnail]); // safe with any file name
Process::run('ls -la storage | grep sessions');                     // shell line
```

<a id="options"></a>

### Options

```php
Process::run(['php', 'rakit', 'migrate'], [
    'path' => path('base'),             // working directory
    'env' => ['APP_STAGE' => 'deploy'], // added to the current environment
    'input' => $csv,                    // written to stdin
    'timeout' => 300,                   // seconds, 60 by default, 0 for none
]);
```

A command running past its timeout is stopped and a `RuntimeException` is thrown.

<a id="streaming-output"></a>

### Streaming Output

Pass a closure to see output as it arrives, e.g. to print a long build live:

```php
Process::run(['npm', 'run', 'build'], [], function ($type, $chunk) {
    echo ('err' === $type) ? '[stderr] '.$chunk : $chunk;
});
```

<a id="asynchronous-processes"></a>

### Asynchronous Processes

`start()` returns at once; the command runs while PHP does other work:

```php
$export = Process::start(['php', 'rakit', 'export:orders'], ['timeout' => 0]);

while ($export->running()) {
    // ..
}

$export->wait();          // or wait at once
$export->stop();          // SIGTERM, then SIGKILL after 2 seconds
```

Start several and `wait()` each to run commands side by side. A process still running when its
object is destroyed is stopped.

<a id="concurrency"></a>

## Concurrency

`Concurrency::run()` runs closures at the same time and returns their results under the same
keys, after all of them ended:

```php
list($users, $orders, $revenue) = Concurrency::run([
    function () { return User::count(); },
    function () { return Order::where('status', '=', 'paid')->count(); },
    function () { return Curl::get('https://api.example.com/revenue')->body; },
]);
```

On the command line (console commands, job workers, the scheduler) with the `pcntl` extension,
each closure runs in a forked child process; three one-second tasks take one second. Elsewhere
(php-fpm, Apache, no `pcntl`) they run one after another with the same results, so the same code
works everywhere. `Concurrency::forkable()` tells which applies.

-   A result must be serializable (no closures, no open resources).
-   An exception thrown in a closure is thrown by `run()` as a `RuntimeException` once every
    closure ended.
-   A child opens its own database connections: the parent's are never shared or closed by it.
-   A child ends without running shutdown functions or destructors, so work it starts there
    (a job dispatched without `->dispatch()`, a session write) is lost. Return data and act on
    it in the parent instead.
