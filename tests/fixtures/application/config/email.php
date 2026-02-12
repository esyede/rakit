<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Mail Driver
    |--------------------------------------------------------------------------
    |
    | Rakit implements email sending based on driver.
    | Of course, some built-in drivers are also provided so that
    | you can send email easily and simply.
    |
    | Available drivers: 'mail', 'smtp', 'sendmail' or 'log' (testing).
    |
    */

    'driver' => 'mail',

    /*
    |--------------------------------------------------------------------------
    | SMTP
    |--------------------------------------------------------------------------
    |
    | SMTP settings. Used when you choose 'smtp' as the default email driver.
    |
    | Available login methods: LOGIN (default), PLAIN, CRAM-MD5
    |
    */

    'smtp' => [
        'method' => 'LOGIN', // LOGIN, PLAIN, CRAM-MD5
        'host' => '',
        'port' => 465,
        'username' => '',
        'password' => '',
        'timeout' => 5,
        'starttls' => true,
        'options' => [
            // ..
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sendmail
    |--------------------------------------------------------------------------
    |
    | Path to the sendmail binary.
    |
    */

    'sendmail_binary' => '/usr/sbin/sendmail',

    /*
    |--------------------------------------------------------------------------
    | Mail Type
    |--------------------------------------------------------------------------
    |
    | Choose whether email should be sent as HTML or plain text.
    | Set to NULL for automatic detection.
    |
    */

    'as_html' => null,

    /*
    |--------------------------------------------------------------------------
    | Character Encoding
    |--------------------------------------------------------------------------
    |
    | Default character encoding for email.
    |
    | Available options: '8bit', 'base64', 'quoted-printable'.
    |
    */

    'encoding' => '8bit',

    /*
    |--------------------------------------------------------------------------
    | Encode Headers
    |--------------------------------------------------------------------------
    |
    | Do the subject and recipient names also need to be encoded?
    |
    */

    'encode_headers' => true,

    /*
    |--------------------------------------------------------------------------
    | Priority
    |--------------------------------------------------------------------------
    |
    | Set the email priority.
    |
    | Available options: LOWEST, LOW, NORMAL, HIGH, HIGHEST
    |
    */

    'priority' => System\Email::NORMAL,

    /*
    |--------------------------------------------------------------------------
    | Default Sender
    |--------------------------------------------------------------------------
    |
    | Default sender information.
    |
    */

    'from' => [
        'email' => 'noreply@example.com',
        'name' => 'Support Division',
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Validate email addresses?
    |
    */

    'validate' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-attach
    |--------------------------------------------------------------------------
    |
    | Automatically attach files inline?
    |
    */

    'attachify' => true,

    /*
    |--------------------------------------------------------------------------
    | Auto-generate Alt Body
    |--------------------------------------------------------------------------
    |
    | Automatically generate alt-body from html body?
    |
    */

    'alternatify' => true,

    /*
    |--------------------------------------------------------------------------
    | Force Mixed
    |--------------------------------------------------------------------------
    |
    | Force content-type multipart/related to multipart/mixed?
    |
    */

    'force_mixed' => false,

    /*
    |--------------------------------------------------------------------------
    | Wordwrap
    |--------------------------------------------------------------------------
    |
    | Size of wordwrap (sentence breaking). Set to NULL, 0 or FALSE to disable
    | the wordwrap feature.
    |
    */

    'wordwrap' => 76,

    /*
    |--------------------------------------------------------------------------
    | Newline
    |--------------------------------------------------------------------------
    |
    | The newline character used for email headers and body.
    |
    */

    'newline' => CRLF,

    /*
    |--------------------------------------------------------------------------
    | Return Path
    |--------------------------------------------------------------------------
    |
    | Default return path for your email.
    |
    */

    'return_path' => false,

    /*
    |--------------------------------------------------------------------------
    | Strip Comments
    |--------------------------------------------------------------------------
    |
    | Strip HTML comments from email body?
    |
    */

    'strip_comments' => true,

    /*
    |--------------------------------------------------------------------------
    | Replace Protocol
    |--------------------------------------------------------------------------
    |
    | When relative URI protocol ('//fooobar') is used in email body,
    | you can specify what you want to replace it with.
    |
    | Options are: 'http://', 'https://' or FALSE.
    |
    */

    'protocol_replacement' => false,
];
