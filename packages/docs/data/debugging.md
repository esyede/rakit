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
`application/config/debugger.php`, which is `true` by default. **Set it to `false`
on production servers.**

> **Note:** the shipped `debugger.php` has `'activate' => true`, which exposes
> stack traces and source paths. It **must** be `false` in production. One way is an
> [environment override](/docs/config#environment-configuration): map your domain to
> an environment in `paths.php`, then override only that key.
>
> ```php
> // paths.php
> $environments = [
>     'local' => ['http://localhost*', 'http://127.0.0.1*', '*.test'],
>     'production' => ['https://example.com*'],
> ];
>
> // application/config/production/debugger.php
> return ['activate' => false];
> ```
>
> Console commands pick the environment from `--env=production` or `RAKIT_ENV`.

With it off, an error shows the generic `500` page
(`application/views/error/500.blade.php`, yours to restyle) and is written to the
configured [log channel](#logging) or [emailed](#configuration) — never a stack
trace revealing source paths.

<a id="debug-bar"></a>

## Debug Bar

The Debug Bar floats in the bottom right corner of the page. It shows the load
time, server configuration, the SQL queries the request ran, and anything you
dumped with the [bd()](/docs/helpers#bd) helper. Turn it off with the `debugbar`
option, or hide single panels with `collectors`.

<a id="error--exception"></a>

## Error & Exception

Instead of PHP's plain error messages, the debugger renders errors, uncaught
exceptions and fatal errors as a readable page: the message in the header, the
offending source line highlighted, and the stack trace below.

<a id="configuration"></a>

## Configuration

All options live in `application/config/debugger.php`:

| Option       | Description                                                                                       |
| ------------ | ------------------------------------------------------------------------------------------------- |
| `activate`   | Show errors and the debug bar. `false` in production.                                             |
| `debugbar`   | Show the debug bar.                                                                               |
| `database`   | Record SQL queries, bindings and timings (see `DB::profile()`). Disable for heavy database work. |
| `strict`     | Stop execution on any error; otherwise notices and warnings only appear in the debug bar.        |
| `scream`     | Disable the `@` operator so suppressed notices and warnings are shown.                           |
| `depth`      | How deep `dd()`, `bd()` and `dump()` show arrays and objects (`0` = everything).                  |
| `length`     | How many characters those helpers show (`0` = everything).                                        |
| `location`   | Also show the file location of the dump call.                                                     |
| `email`      | Address(es) notified of errors, see below.                                                        |
| `editor`     | Editor opened by `file:line` links: a preset (`phpstorm`, `idea`, `vscode`, `vscode-insiders`, `sublime`, `textmate`, `atom`, `macvim`, `emacs`, `netbeans`), a URL template with `%file%` and `%line%`, or `null` to disable links. |
| `collectors` | Turn individual debug bar panels on or off. Omitted keys stay enabled.                            |

#### Error emails:

Set `email` to be notified of errors, separating several addresses with commas:

```php
'email' => 'dev@site.com, ops@site.com',
```

An email is sent for uncaught exceptions and fatal errors in production, in
[workers](/docs/workers) too. While the debugger is active, errors not shown as an
error page (e.g. in console commands) send one as well.

Emails go through the [Email](/docs/email) component and its driver in
`application/config/email.php`. The default `mail` driver needs a working
`sendmail`, which containers rarely have, so use `smtp` there.

At most one email is sent every two days. The file `storage/logs/email-sent`
remembers the last one; delete it to receive the next error right away. If an email
cannot be sent, a warning with the reason is written to the [log](#logging).

To email an error you handled yourself, use `Debugger::notify()`:

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

#### Writing log entries:

The `Log` class has one method per level: `emergency()`, `alert()`, `critical()`,
`error()`, `warning()`, `notice()`, `info()` and `debug()`. `Log::log($level,
$message)` takes the level as a string.

```php
Log::error('Failed to send email to Budi!');
Log::info('Email to Andi sent successfully!');
```

The message must be a string; data goes into the second parameter, the **context
array**, which is appended to the entry. An exception under the `exception` key
also appears in the Debug Bar's exception panel:

```php
Log::info('User data:', ['user' => $user->to_array()]);

Log::error('Payment failed.', ['exception' => $e, 'order' => $order->id]);
```

Every entry also fires the `rakit.log` event with the level, message and context.

#### Log channels:

Channels are defined in `application/config/log.php`. The `default` option picks
the channel used by `Log` and by the debugger:

```php
'default' => 'daily',

'channels' => [
    'daily' => [
        'driver' => 'daily',
        'days' => 14,         // delete files older than 14 days, 0 keeps them all
        'level' => 'warning', // skip debug, info and notice entries
    ],
],
```

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

Every channel accepts a `level` option (the minimum level written) and a `format`
option: `line` for readable lines or `json` for one JSON object per line. Without
`application/config/log.php`, entries go to the daily files.

> A log file whose name ends in `.php` starts with a guard line,
> `<?php defined('DS') or exit('No direct access.');?>`, so a web server that parses
> it as PHP cannot expose it (Rakit does not assume `storage/` is denied). A `path`
> ending in `.log` gets no guard. Compiled views and stored debug bar payloads get
> the same guard.

#### Logging inside a container:

Log files inside a container are hard to reach and vanish with it. Use the `stderr`
channel and entries show up in `docker logs`:

```php
'default' => 'stderr',
```

```json
{"datetime":"2026-09-16T10:43:35.869704+00:00","env":"production","channel":"Rakit","level":"ERROR","message":"Exception occurred","context":{"exception":{"class":"RuntimeException","message":"Something broke","code":0,"file":"/app/application/routes.php:27","trace":["#0 ...","#1 ..."]}}}
```

It uses the `json` format, so log collectors can read every field (including the
stack trace as an array), and a multi-line message stays a single entry.

> Under PHP-FPM, never log to `php://stdout`: there it is the HTTP response body.
> PHP-FPM forwards `php://stderr` only when its pool sets
> `catch_workers_output = yes`, as the official `php:*-fpm` images do.

#### Using a different channel per environment:

Rakit has no `env()` helper. To log to daily files locally but to `stderr` in the
container, either use an [environment config](/docs/config#environment-configuration):
set `'default' => 'stderr'` in `application/config/log.php` and create
`application/config/local/log.php`:

```php
<?php

defined('DS') or exit('No direct access.');

return [
    'default' => 'daily',
];
```

> The merge is shallow: overriding `default` keeps every channel, but an overridden
> `channels` key must list all the channels you need.

For web requests the environment comes from the URL patterns in `paths.php`
(`local` matches `http://localhost*`, `http://127.0.0.1*` and `*.test`), so a
container opened through `http://localhost` is `local` too. Console commands read
the `--env=` option or the `RAKIT_ENV` environment variable.

Or read an environment variable in the config file:

```php
'default' => getenv('LOG_CHANNEL') ?: 'daily',
```

and set `LOG_CHANNEL=stderr` on the container (`ENV` in the `Dockerfile` or
`environment:` in `docker-compose.yml`). PHP-FPM passes it to PHP only when its
pool sets `clear_env = no`, as the official `php:*-fpm` images do.

#### Choosing a channel at runtime:

`Log::channel()` switches the channel for the following entries; `null` goes back
to the default:

```php
Log::channel('stderr');
Log::info('Written to stderr.');
Log::channel(null);
```

A name that is not a configured channel writes through the default channel under
that name. With the `daily` driver that means a separate file:

```php
Log::channel('payment');
Log::info('Invoice #1234 paid.'); // storage/logs/payment_2026-08-20.log.php
Log::channel(null);
```

#### Custom drivers:

A driver extends `System\Log\Drivers\Driver` and implements `write()`. The record
holds the entry's `level`, `message`, `context`, `channel`, `env` and `datetime`.
Throw an exception or return `false` when the entry could not be written:

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

Or reference it from a channel with the `custom` driver (`via` takes a class name
or a closure returning the driver):

```php
'slack' => [
    'driver' => 'custom',
    'via' => 'SlackLogDriver',
    'url' => 'https://hooks.slack.com/services/...',
    'level' => 'error',
],
```

#### When a channel fails:

A failing channel never interrupts your application. The entry and the reason are
written to `storage/logs/rakit.log.php` instead, or to PHP's `error_log()` if that
file is not writable either.
