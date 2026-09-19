# Error & Debugging

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Debug Bar](#debug-bar)
-   [Error & Exception](#error--exception)
-   [Configuration](#configuration)
-   [Logging](#logging)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The debugger is switched on by the `activate` option in
`application/config/debugger.php`, which the default config file sets to `true`.
**Set it to `false` on production servers.** There is no auto-allow for
`127.0.0.1` or `::1`; the host has to be listed explicitly.

With it off, an error shows the generic `500` page
(`application/views/error/500.blade.php`, yours to restyle) and goes to the
configured [log channel](#logging) or an email — never a stack trace carrying
source paths.

<a id="debug-bar"></a>

## Debug Bar

The Debug Bar floats in the bottom right corner of the page, carrying the load time, the server configuration, the SQL queries the request ran, and whatever you dumped with the [bd()](/docs/helpers#bd) helper.



<a id="error--exception"></a>

## Error & Exception

PHP reports an error like this:

```ini
Parse error:  syntax error, unexpected '}' in foo.php on line 22
```

```ini
Notice: Undefined variable: foo in /bar/baz/index.php on line 9
PHP Fatal error: Uncaught Error: Call to a member function lolcat() on null in /bar/baz/qux.php:9
Stack trace:
#0 {main}
thrown in /bar/baz/qux.php on line 9

Fatal error: Uncaught Error: Call to a member function lolcat() on null in /bar/baz/qux.php:9
Stack trace:
#0 {main}
thrown in /bar/baz/qux.php on line 9
```

The debugger renders the same error in a form you can actually read:


The offending source line is highlighted and the message sits in the header. Fatal
errors are caught and shown the same way.

<a id="configuration"></a>

## Configuration

The debugger is configured in `application/config/debugger.php`.

#### Error emails:

Fill in the `email` option to be notified when your application runs into an
error. Separate multiple addresses with commas:

```php
'email' => 'dev@site.com, ops@site.com',
```

An email is sent for uncaught exceptions and fatal errors in production, in
[workers](/docs/workers) as well. While the debugger is active, errors that are
not shown as an error page, such as those of console commands, send one too.

The email goes through the [Email](/docs/email) component, so the driver in
`application/config/email.php` applies. The default `mail` driver needs a
working `sendmail` on the server, which a container rarely has. Use the `smtp`
driver there.

To keep an error storm from flooding your inbox, at most one email is sent every
two days. The file `storage/logs/email-sent` remembers when the last one went
out, delete it to receive the next error right away. When the email can not be
sent, a warning with the reason is written to the [log](#logging) instead.

To send an email yourself for an error you handled, use `Debugger::notify()`:

```php
use System\Foundation\Oops\Debugger;

try {
    $order->pay();
} catch (\Exception $e) {
    Log::error('Payment failed.', ['exception' => $e]);
    Debugger::notify($e);
}
```

<a id="logging"></a>

## Logging

The `Log` class writes entries for debugging, or for anything else worth recording:

#### Writing messages to logs:

There is one method per log level: `emergency()`, `alert()`, `critical()`,
`error()`, `warning()`, `notice()`, `info()`, and `debug()`:

```php
Log::error('Failed to send email to Budi!');

Log::info('Email to Andi sent successfully!');
```

#### Including data in log messages:

The second parameter is a **context array**. Its content is appended to the log
message:

```php
$user = User::find(1);

Log::info('User data:', ['user' => $user->to_array()]);
```

> The message must be a string. Data that needs logging goes into the context
> array, not into the message parameter.

If you put an exception under the `exception` key, it will also show up on the
Debug Bar's exception panel:

```php
try {
    $order->pay();
} catch (\Exception $e) {
    Log::error('Payment failed.', ['exception' => $e, 'order' => $order->id]);
}
```

#### Log channels:

Where log entries end up is decided by the channels in
`application/config/log.php`. The `default` option picks the channel used by the
`Log` class, and by the debugger when it logs an error:

```php
'default' => 'daily',
```

These drivers are available for a channel:

| Driver     | Writes to                                                              |
| ---------- | ---------------------------------------------------------------------- |
| `daily`    | One file per day: `storage/logs/<name>_<date>.log.php` (the default)   |
| `single`   | One file: `storage/logs/<name>.log.php`, or the `path` option          |
| `stream`   | Any writable stream, such as `php://stderr`                            |
| `syslog`   | The system logger                                                      |
| `errorlog` | PHP's `error_log()` function                                           |
| `stack`    | Several channels at once, listed in its `channels` option              |
| `null`     | Nowhere, entries are discarded                                         |
| `custom`   | Your own driver, see _Custom drivers_ below                            |

Every channel also accepts a `level` option, the minimum level it writes, and a
`format` option: `line` for human-readable lines, or `json` for one JSON object
per line.

> A log file whose name ends in `.php` is created with a guard on its first line:
>
> ```php
> <?php defined('DS') or exit('No direct access.');?>
> ```
>
> A log holds whatever reached the application, so a file a web server would parse
> as PHP has to refuse to run on its own. Rakit does not assume the server was
> configured to deny `storage/`. Point a channel's `path` at a `.log` file and no
> guard is written, the file is not executable either way. The same guard goes on
> compiled views and on the debug bar's stored payloads.

```php
'channels' => [
    'daily' => [
        'driver' => 'daily',
        'days' => 14,        // delete files older than 14 days, 0 keeps them all
        'level' => 'warning', // skip debug, info and notice entries
    ],
],
```

> When `application/config/log.php` does not exist, entries keep going into the
> daily files, just like they did before log channels existed.

#### Logging inside a container:

Log files written inside a container are hard to reach and disappear with it.
Use the `stderr` channel, the entries then show up in `docker logs`:

```php
'default' => 'stderr',
```

```json
{"datetime":"2026-09-16T10:43:35.869704+00:00","env":"production","channel":"Rakit","level":"ERROR","message":"Exception occurred","context":{"exception":{"class":"RuntimeException","message":"Something broke","code":0,"file":"/app/application/routes.php:27","trace":["#0 ...","#1 ..."]}}}
```

The channel uses the `json` format, so log collectors can read every field of an
entry, including the stack trace as an array. A message containing line breaks
also stays a single entry, instead of one per line.

> Under PHP-FPM, never log to `php://stdout`: there it is the HTTP response body.
> PHP-FPM only forwards `php://stderr` to the container when its pool sets
> `catch_workers_output = yes`, which the official `php:*-fpm` images already do.

#### Using a different channel per environment:

Rakit has no `env()` helper. To write daily files on your machine but log to
`stderr` inside the container, pick one of these approaches.

Override the option in an environment config folder. Rakit merges
`application/config/<environment>/log.php` over `application/config/log.php`,
so set `'default' => 'stderr'` in the main file and create
`application/config/local/log.php`:

```php
<?php

defined('DS') or exit('No direct access.');

return [
    'default' => 'daily',
];
```

> The merge is shallow: a top-level key in the environment file replaces the
> whole key of the main file. Overriding `default` alone keeps every channel,
> but an overridden `channels` key must list all the channels you need.

For web requests the environment comes from the URL patterns in `paths.php`
(`local` matches `http://localhost*`, `http://127.0.0.1*` and `*.test`). A
container you open through `http://localhost` is therefore `local` as well.
Console commands read the `--env=` option or the `RAKIT_ENV` environment
variable instead.

Or read an environment variable directly in the config file:

```php
'default' => getenv('LOG_CHANNEL') ?: 'daily',
```

Then set `LOG_CHANNEL=stderr` on the container, e.g. with `ENV` in the
`Dockerfile` or `environment:` in `docker-compose.yml`. PHP-FPM passes these
variables to PHP only when its pool sets `clear_env = no`, which the official
`php:*-fpm` images also do.

#### Choosing a channel at runtime:

`Log::channel()` switches the channel for the following entries. Pass `null` to go
back to the default channel:

```php
Log::channel('stderr');
Log::info('Written to stderr.');
Log::channel(null);
```

When the name is not a channel defined in the config file, the entries still go
through the default channel, but under that name. With the `daily` driver this
means a separate file:

```php
Log::channel('payment');
Log::info('Invoice #1234 paid.'); // storage/logs/payment_2026-08-20.log.php
Log::channel(null);
```

#### Custom drivers:

A driver extends `System\Log\Drivers\Driver` and implements `write()`. The record
it receives holds the `level`, `message`, `context`, `channel`, `env` and
`datetime` of the entry. Throw an exception or return `false` when the entry
could not be written:

```php
class SlackLogDriver extends \System\Log\Drivers\Driver
{
    protected function write(array $record)
    {
        $body = Curl::body_json(['text' => $this->format($record, false)]);
        $response = Curl::post($this->config['url'], ['Content-Type' => 'application/json'], $body);

        return 200 === (int) $response->code;
    }
}
```

Register it under a driver name, e.g. in `application/boot.php`:

```php
Log::extend('slack', function (array $config, $channel) {
    return new SlackLogDriver($config, $channel);
});
```

Or reference the class from the channel with the `custom` driver:

```php
'slack' => [
    'driver' => 'custom',
    'via' => 'SlackLogDriver',
    'url' => 'https://hooks.slack.com/services/...',
    'level' => 'error',
],
```

#### When a channel fails:

A failing channel never interrupts your application. When it cannot write an entry,
because of a misconfiguration or a file that is not writable for example, the
entry and the reason are written into `storage/logs/rakit.log.php`, or into
PHP's `error_log()` when that file cannot be written either.
