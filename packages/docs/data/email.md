# Email

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Sending Email](#sending-email)
    -   [Recipients](#recipients)
    -   [Subject and Body](#subject-and-body)
    -   [Priority](#priority)
    -   [Attachments](#attachments)
    -   [Custom Headers](#custom-headers)
    -   [Sending](#sending)
-   [Mailables](#mailables)
-   [Configuration](#configuration)
-   [Custom Driver](#custom-driver)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

The `Email` class sends mail through PHP's `mail()`, `sendmail` or SMTP (with TLS or SSL), supporting multiple recipients, CC and BCC, HTML or plain-text bodies, attachments and priorities. A `log` driver writes messages to the log instead of sending them, which is handy for testing.

<a id="sending-email"></a>

## Sending Email

Build the message with chained calls, then call `send()`:

```php
$email = Email::from('admin@site.com', 'Admin')
    ->to('eka@site.com')
    ->cc('farida@site.com')
    ->bcc('nando@site.com')
    ->subject('Monthly Report')
    ->html_body(View::make('emails.report', $data)->render())
    ->priority(Email::HIGH)
    ->attach(path('storage').'monthly_report.pdf');

try {
    $email->send();
} catch (\Exception $e) {
    echo 'Email failed to send: '.$e->getMessage();
}
```

Static calls go to the default driver. Use `Email::driver('smtp')` to get another one.

<a id="set-recipient"></a>
<a id="set-cc-and-bcc"></a>
<a id="recipients"></a>

### Recipients

`to()`, `cc()`, `bcc()` and `replyto()` accept an address with an optional name, or an array. Each call adds to the list:

```php
$email->to('eka@site.com', 'Eka Ramadhan');
$email->to('budi@site.com');

$email->cc([
    'putri@site.com' => 'Putri Anggraini',
    'farida@site.com',
]);

$email->bcc('hilman@site.com');
$email->replyto('support@site.com', 'Support');
```

`from()` sets the sender (overriding `from` in the config), and `return_path()` sets the Return-Path address (the sender is used when none is set).

<a id="set-body"></a>
<a id="alt-body"></a>
<a id="set-subject"></a>
<a id="subject-and-body"></a>

### Subject and Body

```php
$email->subject('Monthly Report');

$email->body('Plain text body');                // Plain text
$email->html_body('<b>HTML body</b>');          // HTML
$email->alt_body('Plain text alternative');     // Optional plain-text version of an HTML mail
```

`html_body()` generates the alt body for you (the `alternatify` option), so `alt_body()` is only needed to override it. Any HTML string works, for example a rendered view or [Markdown](/docs/markdown):

```php
$email->html_body(Markdown::render(path('base').'README.md'));
```

`html_body($html, $alternatify, $attachify)` can also override the `alternatify` and `attachify` options for one message.

<a id="priority"></a>

### Priority

```php
$email->priority(Email::HIGH);
```

| Constant         | Value                |
| ---------------- | -------------------- |
| `Email::HIGHEST` | 1 (Highest)          |
| `Email::HIGH`    | 2 (High)             |
| `Email::NORMAL`  | 3 (Normal) - default |
| `Email::LOW`     | 4 (Low)              |
| `Email::LOWEST`  | 5 (Lowest)           |

Any other value throws an exception.

<a id="attachments"></a>

### Attachments

Attach a file, or attach string contents under a file name:

```php
$email->attach(path('storage').'monthly_report.pdf');

$contents = Storage::get(path('storage').'monthly_report.pdf');
$email->string_attach($contents, 'monthly_report.pdf');
```

To embed a file inline, pass `true` and a content ID, then reference it from the HTML as `src="cid:logo"`:

```php
$email->attach(path('assets').'images/logo.png', true, 'cid:logo');
```

The full signatures are `attach($file, $inline = false, $cid = null, $mime = null, $name = null)` and `string_attach($contents, $filename, $cid = null, $inline = false, $mime = null)`. A missing or empty file throws an exception.

When the `attachify` option is on (it is off by default), `html_body()` embeds images automatically: every `src` / `background` that is a local file **under the assets directory** is attached inline. URLs (including `asset()` URLs), `cid:` and `data:` sources are left alone.

```html
<img src="images/kitty.png" />                      <!-- attached (assets/images/kitty.png) -->
<img src="https://other-site.com/images/kitty.jpg" /> <!-- left as is -->
```

<a id="custom-headers"></a>

### Custom Headers

```php
$email->header('X-Campaign', 'october');
$email->header(['X-Campaign' => 'october', 'X-Source' => 'app']);
```

<a id="ready-to-send"></a>
<a id="sending"></a>

### Sending

`send()` returns `TRUE`, or `FALSE` if the transport refused the message. It throws an exception when there are no recipients, no sender, or (with the `validate` option on) an invalid address; `get_invalid_addresses()` then lists the offending addresses. Pass `false` to skip validation once: `$email->send(false)`.

After every send, the message is reset: recipients, subject, body, attachments and custom headers are cleared, and the sender, return path and priority go back to their config values. So set everything again for each message (you can also call `reset()` yourself):

```php
foreach ($subscribers as $subscriber) {
    Email::to($subscriber->email)
        ->subject('Monthly Report')
        ->html_body($view)
        ->send();
}
```

Two [hooks](/docs/hooks#framework-events) surround the sending. A `rakit.mail.sending` listener that returns `FALSE` cancels it, in which case `rakit.mail.sent` is not fired and `send()` returns `FALSE`:

```php
Hook::listen('rakit.mail.sending', function ($message) {
    // return false to cancel
});

Hook::listen('rakit.mail.sent', function ($message) {
    // The message is on its way
});
```

<a id="mailables"></a>

## Mailables

A mailable is a class that builds one kind of message: its subject, view and data. Put it in
`application/libraries/`, with a name without underscores so it needs no sub folder (the
autoloader maps `Invoice_Mail` to `libraries/invoice/mail.php`, but `InvoiceMail` to
`libraries/invoicemail.php`):

```php
// application/libraries/invoicemail.php
class InvoiceMail extends Mailable
{
    protected $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function build()
    {
        $this->subject('Invoice '.$this->invoice->number)
            ->view('emails.invoice', ['invoice' => $this->invoice])
            ->attach(path('storage').'invoices'.DS.$this->invoice->number.'.pdf');
    }
}
```

Give it its recipients, then send it now, or queue it so the request does not wait for the mail
server (a [job worker](/docs/job#running-worker) sends it):

```php
(new InvoiceMail($invoice))->to($user->email, $user->name)->send();

(new InvoiceMail($invoice))->to($user->email)->cc('billing@example.com')->queue();
(new InvoiceMail($invoice))->to($user->email)->queue('emails'); // on another queue
```

`to()`, `cc()`, `bcc()` and `reply_to()` take an address and a name, a list of addresses, or
`[address => name]` pairs. `from()` overrides the configured sender, `with()` adds view data, and
`mailer('smtp')` picks another driver. `render()` returns the HTML, handy to preview the mail in a
route. A queued mailable is serialized into the job, so keep its properties serializable (no
closures).

To send a message to a user on several channels at once (mail, database), see
[Notifications](/docs/notifications).

<a id="configuration"></a>

## Configuration

Settings live in `application/config/email.php`. `driver` is one of `mail` (default), `smtp`, `sendmail` or `log`:

```php
'driver' => 'smtp',
```

The SMTP default port `587` uses STARTTLS. For implicit TLS on port `465`, turn `starttls` off and prefix the host with `ssl://`:

```php
'smtp' => [
    'host' => 'ssl://smtp.site.com',
    'port' => 465,
    'starttls' => false,
    // ..
],
```

Other SMTP keys are `method` (`LOGIN`, `PLAIN` or `CRAM-MD5`), `username`, `password`, `timeout`, `keep_alive` and `options`. The remaining options:

| Option                 | Default                 | Description |
| ---------------------- | ----------------------- | ----------- |
| `sendmail_binary`      | `/usr/sbin/sendmail`    | Path used by the `sendmail` driver |
| `from`                 | `['email' => ..., 'name' => ...]` | Default sender |
| `priority`             | `Email::NORMAL`         | Default priority |
| `as_html`              | `null`                  | Send as HTML (`true`), plain text (`false`) or auto-detect (`null`) |
| `encoding`             | `'8bit'`                | `'8bit'`, `'base64'` or `'quoted-printable'` |
| `encode_headers`       | `true`                  | Encode the subject and names |
| `validate`             | `true`                  | Validate addresses before sending |
| `attachify`            | `false`                 | Embed local asset images of HTML bodies inline |
| `alternatify`          | `true`                  | Generate the alt body from the HTML body |
| `force_mixed`          | `false`                 | Use `multipart/mixed` instead of `multipart/related` |
| `wordwrap`             | `76`                    | Line length; `null`, `0` or `false` disables wrapping |
| `newline`              | `CRLF`                  | Line ending for headers and body |
| `return_path`          | `false`                 | Default Return-Path |
| `strip_comments`       | `true`                  | Remove HTML comments from HTML bodies |
| `protocol_replacement` | `false`                 | Scheme (e.g. `'https://'`) given to protocol-relative `//` image URLs when `attachify` runs |

<a id="custom-driver"></a>

## Custom Driver

Extend `System\Email\Drivers\Driver` and implement `transmit()`. The protected `build()` method returns the prepared `header` and `body`:

```php
// application/libraries/mydriver.php

class Mydriver extends \System\Email\Drivers\Driver
{
    protected function transmit()
    {
        $message = $this->build();
        // Send $message['header'] and $message['body'] ...

        return true;
    }
}
```

Register it with `Email::extend()`, for example in `application/boot.php`, and select it in the config:

```php
Email::extend('mydriver', function () {
    return new Mydriver(Config::get('email'));
});

Config::set('email.driver', 'mydriver');
```

Resolved drivers are cached; `Email::reset()` clears that cache.
