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

`Email` sends mail over `mail`, `sendmail` or SMTP, with TLS or SSL, several
recipients, CC and BCC, HTML or plain text, attachments and a priority.

<a id="configuration"></a>

## Configuration

The configuration lives in `application/config/email.php`. The default `'mail'`
driver sends through PHP's [mail()](https://www.php.net/manual/en/function.mail.php);
change it as needed:

```php
'driver' => 'sendmail',
```

For the `'smtp'` driver, the default port `587` is the one that speaks STARTTLS.
A server on the implicit TLS port `465` expects the encryption to be in place before
it says anything, so turn `'starttls'` off and name the host with its scheme:

```php
'smtp' => [
    'host' => 'ssl://smtp.site.com',
    'port' => 465,
    'starttls' => false,
    // ..
],
```

<a id="sending-email"></a>

## Sending Email

A simple example:

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

Or several at once:

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

CC and BCC work exactly like `to()`:

```php
$email->cc('farida@site.com');
$email->cc('putri@site.com', 'Putri Anggraini');

$email->bcc('hilman@site.com');
$email->bcc('rachel@site.com', 'Rachel Putri Toar');
```

<a id="set-body"></a>

### Set Body

The body is either plain text or HTML:

#### 1. Plain Text

For clients too old to render HTML:

```php
$email->body('PDF document regarding the monthly financial report');
```

#### 2. HTML Body

The usual choice, since HTML and CSS give you control over how the mail looks:

```php
$email->html_body('<b>PDF document regarding the monthly financial report</b>');
```

The `Markdown` component works here too:

```php
$markdown = Markdown::render(path('base').'README.md');

$email->html_body($markdown);
```

As does the `View` component:

```php
$data = ['registered_at' => now()];
$view = View::make('emails.registration_success', $data)->render();

$email->html_body($view);
```

<a id="alt-body"></a>

### Alt Body

The alt body is the plain-text version of the message. It is optional.

```php
$email->alt_body('Monthly report');
```

> `html_body()` derives one automatically, so this is only for overriding it.

<a id="set-subject"></a>

### Set Subject

`subject()` sets the subject:

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

| Constant         | Value                |
| ---------------- | -------------------- |
| `Email::HIGHEST` | 1 (Highest)          |
| `Email::HIGH`    | 2 (High)             |
| `Email::NORMAL`  | 3 (Normal) - default |
| `Email::LOW`     | 4 (Low)              |
| `Email::LOWEST`  | 5 (Lowest)           |

<a id="attachments"></a>

### Attachments

Attachments come in two forms:

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

By default (the `'attachify'` option), images in the HTML body passed to `html_body()` will be
attached inline automatically, but only if the `src` is a local file path rather than a URL.
Look at this example to understand the difference:

```php
// This will be attached automatically
<img src="<?php echo path('assets').'images/kitty.png'; ?>" />

// This will not be attached (URLs, including asset() URLs, are left as they are)
<img src="https://other-site.com/images/kitty.jpg" />
```

<a id="ready-to-send"></a>

### Ready to Send

With everything in place, send it:

```php
try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email failed to send: '.$e->getMessage();
}
```

> The try-catch keeps a failed send from taking the request down with it.

`send()` returns `FALSE` when the driver reports that the transport refused the message.

Once the message is on its way, `send()` drops the parts that belong to it: the recipients,
the attachments and the custom headers set with `header()`. The sender, subject and body are
kept, so sending the same email to several people is a matter of setting the next recipient:

```php
$email = Email::from('admin@site.com')
    ->subject('Monthly Report')
    ->html_body($view);

foreach ($subscribers as $subscriber) {
    $email->to($subscriber->email)->send();
}
```

Without that, every recipient of the first email would also receive the second one.

<a id="custom-driver"></a>

## Custom Driver

Beyond the built-in `mail`, `smtp`, `sendmail` and `log` drivers, you can register
your own.

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

Then point the configuration at it:

```php
Config::set('email.driver', 'mydriver');
```
