# Email

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Configuration](#configuration)
-   [Sending Email](#sending-email)
    -   [Set Recipient](#set-recipient)
    -   [Set CC and BCC](#set-cc-and-bcc)
    -   [Set Body](#set-body)
    -   [Alt Body](#alt-body)
    -   [Set Subject](#set-subject)
    -   [Priority](#priority)
    -   [Attachments](#attachments)
    -   [Ready to Send](#ready-to-send)
-   [Custom Driver](#custom-driver)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `Email` component is provided to help you send emails to clients.
The email component supports several basic features such as multi-protocol (mail, sendmail, and SMTP);
TLS and SSL encryption for SMTP; multi-recipients; CC and BCC; HTML or plain-text emails;
attachments as well as email priority.

<a id="configuration"></a>

## Configuration

It is very easy to configure this component as the default configuration has been provided that you can find in the file `application/config/email.php`.

In the default state, rakit is configured using the `'mail'` driver which means it
will use the [mail()](https://www.php.net/manual/en/function.mail.php) function for
its transmission. However, of course you may change it as needed:

```php
'driver' => 'sendmail',
```

> Open and read the file `application/config/email.php` so that you have an idea
> about what preferences you can change.

<a id="sending-email"></a>

## Sending Email

After finishing reading the configuration file, let's look at a simple email sending example:

```php
$email = Email::from('admin@site.com')
    ->to('eka@site.com')
    ->cc('farida@site.com')
    ->bcc('nando@site.com')
    ->body('PDF document regarding the monthly financial report')
    ->alt_body('Monthly report')
    ->subject('Monthly Report')
    ->priority(Email::HIGH)
    ->attach(path('storage').'monthly_report.pdf');

try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email failed to send: '.$e->getMessage();
}
```

<a id="set-recipient"></a>

### Set Recipient

The `to()` method is used to set the email recipient:

```php
$email->to('eka@site.com');
```

You can also add the email recipient's name:

```php
$email->to('eka@site.com', 'Eka Ramadhan');
```

In addition, you can also add multiple recipients at once:

```php
$email->to('eka@site.com', 'Eka Ramadhan');
$email->to('budi@site.com');
$email->to('dewi@site.com');

// or via array like this:

$email->to([
    'eka@site.com' => 'Eka Ramadhan',
    'budi@site.com',
    'dewi@site.com',
]);
```

<a id="set-cc-and-bcc"></a>

### Set CC and BCC

The writing for CC and BCC is exactly the same as setting the recipient above:

```php
$email->cc('farida@site.com');
$email->cc('putri@site.com', 'Putri Anggraini');

$email->bcc('hilman@site.com');
$email->bcc('rachel@site.com', 'Rachel Putri Toar');
```

<a id="set-body"></a>

### Set Body

There are 2 options for setting your email body, namely HTML and plain-text:

#### 1. Plain Text

Use this option if you know your user's email client is too old to render emails containing only text:

```php
$email->body('PDF document regarding the monthly financial report');
```

#### 2. HTML Body

Use this option if you know your user's email client can render emails containing HTML tags.
This option is preferred because you can make the email appearance beautiful
with the help of HTML tags and CSS:

```php
$email->html_body('<b>PDF document regarding the monthly financial report</b>');
```

You can also utilize the `Markdown` component if needed:

```php
$markdown = Markdown::render(path('base').'README.md');

$email->html_body($markdown);
```

In addition to markdown, you can also utilize the `View` component like this:

```php
$data = ['registered_at' => now()];
$view = View::make('emails.registration_success', $data)->render();

$email->html_body($view);
```

Nice isn't it?

<a id="alt-body"></a>

### Alt Body

Alt-Body or alternative body contains a short summary of your email body content.
Although it is optional (not mandatory), you can still add it
if indeed needed.

```php
$email->alt_body('Monthly report');
```

> When using `html_body()`, you don't have to add alt-body
> because it will be added automatically by rakit.

<a id="set-subject"></a>

### Set Subject

To add the email subject or title, use the `subject()` method like this:

```php
$email->subject('Monthly Report');
```

<a id="priority"></a>

### Priority

You can also set the email priority:

```php
$email->priority(Email::HIGH);
```

Email priority constants must follow this table:

| Constant        | Value                |
| ---------------- | -------------------- |
| `Email::LOWEST`  | 1 (Lowest)           |
| `Email::LOW`     | 2 (Low)              |
| `Email::NORMAL`  | 3 (Normal) - default |
| `Email::HIGH`    | 4 (High)             |
| `Email::HIGHEST` | 5 (Highest)          |

<a id="attachments"></a>

### Attachments

There are two ways to add attachments to email, namely:

#### 1. File Attachment:

```php
$file = path('storage').'monthly_report.pdf';

$email->attach($file);
```

You can also add attachments inline by passing `TRUE` to the second parameter and `cid:<tag_id_html>` to the third parameter as its attribute pointer.

```php
$file = path('storage').'monthly_report.pdf';

$email->attach($file, true, 'cid:my_content_id');
```

#### 2. String Attachment:

```php
$contents = Storage::get(path('storage').'monthly_report.pdf');

$email->string_attach($contents, 'monthly_report.pdf');
```

By default, images in HTML will be loaded automatically, but only if the file is
located in local storage. Look at this example to understand the difference:

```php
// This will be loaded automatically
<img src="<?php echo asset('images/kitty.png'); ?>" />

// This will not be loaded
<img src="https://other-site.com/images/kitty.jpg" />
```

<a id="ready-to-send"></a>

### Ready to Send

After all data is arranged, the last step that needs to be done is
sending your email:

```php
try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email failed to send: '.$e->getMessage();
}
```

> In the example above we wrapped the execution of the `send()` method in a try-catch block
> so that when an error occurs while sending the email, your application will continue to run.

<a id="custom-driver"></a>

## Custom Driver

You can also register other email drivers if the 3 built-in rakit drivers do not
suit your needs.

Create a new driver class:

```php
// application/libraries/mydriver.php

class Mydriver extends Mailer
{
    protected function transmit()
    {
        // Your email sending logic ..
    }
}
```

Register your new driver to rakit:

```php
// application/boot.php

$config = [
    // ..
];

Email::extend('mydriver', function () use ($config) {
    return new Mydriver($config);
});
```

Then just change the default driver configuration to the driver you just created:

```php
Config::set('email.driver', 'mydriver');
```
