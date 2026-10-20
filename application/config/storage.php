<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Default Disk
    |--------------------------------------------------------------------------
    |
    | The disk Storage::disk() returns when no name is given.
    |
    */

    'default' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Disks
    |--------------------------------------------------------------------------
    |
    | Each disk is a place to keep files, used through Storage::disk('name').
    | Drivers: 'local' (a directory) and 's3' (Amazon S3, and S3-compatible
    | services: MinIO, Cloudflare R2, DigitalOcean Spaces, Wasabi...).
    |
    | For S3-compatible services set 'endpoint' (e.g. 'http://127.0.0.1:9000'
    | for MinIO, 'https://<account>.r2.cloudflarestorage.com' for R2) and,
    | for MinIO, 'path_style' => true. 'root' keeps the disk under a prefix,
    | 'url' is the public base URL (a CDN) used by url().
    |
    | Keep the keys out of version control on shared repositories.
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => path('storage').'app',
            'url' => null,
        ],

        's3' => [
            'driver' => 's3',
            'key' => '',
            'secret' => '',
            'region' => 'ap-southeast-1',
            'bucket' => '',
            'endpoint' => null,
            'path_style' => false,
            'url' => null,
            'root' => '',
        ],
    ],
];
