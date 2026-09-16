<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Default Log Channel
    |--------------------------------------------------------------------------
    |
    | The channel used to write log entries, including the errors caught by
    | the debugger. It must be one of the channels defined below.
    |
    | When running inside a container (e.g. Docker), use the "stderr" channel
    | so the entries show up in "docker logs" instead of a file inside it.
    |
    */

    'default' => 'daily',

    /*
    |--------------------------------------------------------------------------
    | Log Channels
    |--------------------------------------------------------------------------
    |
    | Available drivers: 'daily', 'single', 'stream', 'syslog', 'errorlog',
    |                    'stack', 'null', 'custom'.
    |
    | Every channel also accepts these options:
    |
    |   - level:  The minimum level written: 'debug', 'info', 'notice',
    |             'warning', 'error', 'critical', 'alert' or 'emergency'.
    |   - format: 'line' for human-readable lines, or 'json' for one JSON
    |             object per line. Prefer 'json' for log collectors: they
    |             can read every field, and a message containing line
    |             breaks still stays a single entry.
    |
    | Calling Log::channel('name') writes the next entries to that channel.
    | When no such channel is defined, the default channel is used and the
    | name becomes the log name, e.g. the file name of the "daily" driver.
    |
    | Your own drivers can be registered using Log::extend(), or referenced
    | by the "custom" driver: ['driver' => 'custom', 'via' => 'MyLogDriver']
    |
    */

    'channels' => [
        // Write the entries into several channels at once.
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
            'ignore_exceptions' => false,
        ],

        // One file per day: storage/logs/{name}_{Y-m-d}.log.php
        // Set "days" to delete files older than that, zero keeps all of them.
        'daily' => [
            'driver' => 'daily',
            'directory' => path('storage').'logs',
            'days' => 0,
            'permission' => null,
            'level' => 'debug',
            'format' => 'line',
        ],

        // A single file, storage/logs/{name}.log.php unless "path" is set.
        'single' => [
            'driver' => 'single',
            'path' => null,
            'permission' => null,
            'level' => 'debug',
            'format' => 'line',
        ],

        // Any writable stream. Avoid php://stdout under PHP-FPM, there it is
        // the HTTP response body. PHP-FPM only forwards stderr to the container
        // when "catch_workers_output = yes" (the official Docker image does so).
        'stderr' => [
            'driver' => 'stream',
            'stream' => 'php://stderr',
            'level' => 'debug',
            'format' => 'json',
        ],

        // The "ident" option defaults to the log name.
        'syslog' => [
            'driver' => 'syslog',
            'facility' => LOG_USER,
            'level' => 'debug',
            'format' => 'line',
        ],

        // Uses error_log(), "type" 0 follows the "error_log" ini setting
        // while 4 sends the entry to the SAPI logger (e.g. the web server log).
        'errorlog' => [
            'driver' => 'errorlog',
            'type' => 0,
            'level' => 'debug',
            'format' => 'line',
        ],

        // Discard every entry.
        'null' => [
            'driver' => 'null',
        ],
    ],
];
