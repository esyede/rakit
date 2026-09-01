<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | The name of your application.
    |
    */

    'name' => 'Rakit',

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | The URL used to access your application, without a trailing slash.
    | If not provided, we will attempt to guess it.
    |
    */

    'url' => '',

    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    |
    | If you want to include "index.php" in your URL, leave this option alone.
    | However, if you are using mod_rewrite to prettify your URLs,
    | simply set this option to an empty string.
    |
    */

    'index' => 'index.php',

    /*
    |--------------------------------------------------------------------------
    | Character Encoding
    |--------------------------------------------------------------------------
    |
    | Default character encoding used by your application. This encoding will
    | be used by the Str, HTML, Form, and other classes that need to know the
    | character encoding used by your application.
    |
    */

    'encoding' => 'UTF-8',

    /*
    |--------------------------------------------------------------------------
    | Application Language
    |--------------------------------------------------------------------------
    |
    | Default language used by your application. This language will be used by
    | the Lang class as the default language for language switching features.
    |
    */

    'language' => 'id',

    /*
    |--------------------------------------------------------------------------
    | Supported Languages
    |--------------------------------------------------------------------------
    |
    | Languages supported by your application. If the request URI starts with
    | one of the values in this list, the default language will be automatically
    | set to that language.
    |
    */

    'languages' => [],

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Default timezone used by your application. This timezone will be used by
    | the Date library.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Download Chunk Size
    |--------------------------------------------------------------------------
    |
    | Chunk size (in Mega Bytes) of each binary file chunk that should be sent
    | to the browser when you send a file to the user.
    |
    | This option is used by Response::download().
    |
    */

    'chunk_size' => 4,

    /*
    |--------------------------------------------------------------------------
    | Trusted Hosts
    |--------------------------------------------------------------------------
    |
    | Host names this application answers to. The Host header comes from the
    | client, and when 'url' above is left empty it is what generated URLs are
    | built from — so a request carrying someone else's host would put that host
    | into, say, a password reset link. Filling this in refuses those requests.
    |
    | A name may start with '*.' to cover its subdomains as well as itself.
    | Leave it empty to accept any host, which is fine in development.
    |
    */

    'trusted_hosts' => [
        // 'example.test',
        // '*.example.test',
    ],

    /*
    |--------------------------------------------------------------------------
    | Trusted Proxies
    |--------------------------------------------------------------------------
    |
    | IP addresses of the reverse proxies that sit in front of the application,
    | for example a load balancer or Cloudflare. Only when this list is filled
    | in are the headers those proxies add (X-Forwarded-For, CF-Connecting-IP,
    | and friends) read; until then the peer address is used as-is, because a
    | client can send those headers itself.
    |
    | Leave it empty when the application is reached directly.
    |
    */

    'trusted_proxies' => [
        // '192.0.2.1',
    ],

    /*
    |--------------------------------------------------------------------------
    | CSRF Exceptions
    |--------------------------------------------------------------------------
    |
    | URI patterns that are exempt from the CSRF token check. Use this for
    | webhooks and other server-to-server endpoints that cannot read the
    | session token. Patterns support '*' as a wildcard, e.g. 'api/webhook'
    | matches only that path while 'api/*' matches every path below 'api/'.
    |
    | Routes listed here skip the token check entirely, so only add endpoints
    | that really cannot carry a token.
    |
    */

    'csrf_except' => [
        // 'api/webhook',
        // 'api/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Composer Autoload
    |--------------------------------------------------------------------------
    |
    | Location of composer's autoload file. If you are using composer in your
    | application, set this option to the path where the "autoload.php" file is.
    |
    | If path is not found, your application will still run, but
    | libraries installed via composer will not be recognized by rakit.
    |
    */

    'composer_autoload' => path('base') . 'vendor/autoload.php',
];
