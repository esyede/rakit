<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Default Driver
    |--------------------------------------------------------------------------
    | Default driver used to store and manage jobs.
    |
    | Available options: file, database, redis, memcached
    |
    */

    'driver' => 'file',

    /*
    |--------------------------------------------------------------------------
    | Jobs Table
    |--------------------------------------------------------------------------
    | Job table name (used to store jobs).
    |
    */

    'table' => 'jobs',

    /*
    |--------------------------------------------------------------------------
    | Failed Jobs Table
    |--------------------------------------------------------------------------
    | Failed job table name (used to store failed jobs).
    |
    */

    'failed_table' => 'failed_jobs',

    /*
    |--------------------------------------------------------------------------
    | Max Job
    |--------------------------------------------------------------------------
    | Maximum number of jobs to be executed at once.
    |
    */

    'max_job' => 50,

    /*
    |--------------------------------------------------------------------------
    | Max Retries
    |--------------------------------------------------------------------------
    | Maximum number of retries for a job before it is considered failed and
    | moved to the failed jobs table.
    |
    */

    'max_retries' => 1,

    /*
    |--------------------------------------------------------------------------
    | Sleep
    |--------------------------------------------------------------------------
    | Sleep duration between job retries (in milliseconds).
    |
    */

    'sleep_ms' => 0,

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    | Log every job execution to the Rakit log file?
    |
    */

    'logging' => false,

    /*
    |--------------------------------------------------------------------------
    | Job Key
    |--------------------------------------------------------------------------
    | Prefix for job keys stored using Redis or Memcached to prevent key name
    | conflicts with other applications on the server.
    |
    */

    'key' => 'rakit.job',
];
