# Email

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

- [Basic Knowledge](#konfigurasi)
- [Configuration](#konfigurasi)
- [Sending Email](#mengirim-email)
    - [Set Recipient](#set-penerima)
    - [Set CC dan BCC](#set-cc-dan-bcc)
    - [Set Body](#set-body)
    - [Alt Body](#alt-body)
    - [Set Subject](#set-subyek)
    - [Priority](#prioritas)
    - [Attachments](#lampiran)
    - [Ready To Send](#siap-kirim)
- [Custom Drivers](#driver-kustom)

<!-- /MarkdownTOC -->

<a id="pengetahuan-dasar"></a>
## Basic Knowledge

This `Email` component is provided to help with your job of sending emails to clients.
The email component supports some basic features such as multi-protocol (mail, sendmail, and SMTP);
TLS and SSL encryption for SMTP; multi-recipient; CC and BCC; HTML or plain-text emails;
attachments and email priority.




<a id="konfigurasi"></a>
## Configuration

It is very easy to configure this component because the configuration is already provided
by default which you can find in the `application/config/email.php` file.


In the default state, rakit is configured using the `'mail'` driver which means it
will use the [mail()](https://www.php.net/manual/en/function.mail.php) function to
transmit your emails. But of course you can change it as needed:


```php
'driver' => 'sendmail',
```

> Read the `application/config/email.php` file so you have an idea of what preferences you can change.



<a id="mengirim-email"></a>
## Sending Email

Now, that we're done configuring, let's look at an example of sending a simple email:


```php
$email = Email::from('admin@situs.com')
    ->to('eka@situs.com')
    ->cc('farida@situs.com')
    ->bcc('nando@situs.com')
    ->body('PDF of monthly financial report')
    ->alt_body('Report of this month')
    ->subject('Monthly Report')
    ->priority(Email::HIGH)
    ->attach(path('storage').'monthly_report.pdf');

try {
    $email->send();
} catch (\Exception $e) {
    echo 'Failed to send email: '.$e->getMessage();
}
```


<a id="set-penerima"></a>
### Set Recipient

The `to()` method used to set email recipients:


```php
$email->to('eka@situs.com');
```

You can also add the name of the email recipient:


```php
$email->to('eka@situs.com', 'Eka Ramadhan');
```

Also, you can add multiple recipients at once:


```php
$email->to('eka@situs.com', 'Eka Ramadhan');
$email->to('budi@situs.com');
$email->to('dewi@situs.com');

// or with array like this:

$email->to([
    'eka@situs.com' => 'Eka Ramadhan',
    'budi@situs.com',
    'dewi@situs.com',
]);
```


<a id="set-cc-dan-bcc"></a>
### Set CC dan BCC

To write a CC and BCC is exactly the same as above:


```php
$email->cc('farida@situs.com');
$email->cc('putri@situs.com', 'Putri Anggraini');

$email->bcc('hilman@situs.com');
$email->bcc('rachel@situs.com', 'Rachel Putri Toar');
```


<a id="set-body"></a>
### Set Body

There are 2 options for setting the body of your email, namely HTML and plain-text:


#### 1. Plain Text

Use this option if you know your user's email client can only render a text-only emails:


```php
$email->body('PDF of monthly financial report');
```


#### 2. HTML Body

Use this option if you know your user's email client can render emails containing HTML tags.
This option is preferred since you can create beautiful emails with the help of HTML and CSS:


```php
$email->html_body('<b>PDF of monthly financial report</b>');
```

You can also take advantage of the `Markdown` component if needed:


```php
$markdown = Markdown::render(path('base').'README.md');

$email->html_body($markdown);
```

Apart from markdown, you can also take advantage of the `View` component as follows:


```php
$data = ['registered_at' => now()];
$view = View::make('emails.registration_success', $data)->render();

$email->html_body($view);
```

Fun isn't it?



<a id="alt-body"></a>
### Alt Body

Alt-Body or alternative body contains a short summary of the body of your email.
Even though it isn't mandatory, you can still add it if it is necessary:


```php
$email->alt_body('Report of this month');
```

> When using `html_body()`, you don't even have to add alt-body
  because it's added automatically by rakit.



<a id="set-subyek"></a>
### Set Subject

Untuk menambahkan subyek atau judul email, gunakan method `subject()` seperti berikut:

```php
$email->subject('Monthly Report');
```



<a id="prioritas"></a>
### Priority

You can also set email priority:


```php
$email->priority(Email::HIGH);
```

Email priority constants should follow the following table:


| Constants         | Value                |
| ----------------- | -------------------- |
| `Email::LOWEST`   | 1 (Lowest)           |
| `Email::LOW`      | 2 (Low)              |
| `Email::NORMAL`   | 3 (Normal) - default |
| `Email::HIGH`     | 4 (High)             |
| `Email::HIGHEST`  | 5 (Highest)          |


<a id="lampiran"></a>
### Attachments

There are two ways to add an attachment to an email:


#### 1. File Attachment:

```php
$file = path('storage').'monthly_report.pdf';

$email->attach($file);
```

You can also add inline attachments by passing `TRUE` to the second parameter
and `cid:<html_tag_id>` to the third parameter as attribute's pointer.


```php
$file = path('storage').'monthly_report.pdf';

$email->attach($file, true, 'cid:my_content_id');
```


#### 2. String Attachment:

```php
$contents = Storage::get(path('storage').'monthly_report.pdf');

$email->string_attach($contents, 'monthly_report.pdf');
```

By default, images inside the html will only be loaded automatically if the files are
in your local storage. Take a look at the following examples to understand the difference:


```php
// This will be loaded automatically
<img src="<?php echo asset('images/kitty.png'); ?>" />

// This will not be loaded
<img src="https://situs-lain.com/images/kitty.jpg" />
```


<a id="siap-kirim"></a>
### Ready To Send

After all the data has been supplied, the last step that needs to be done is
send your e-mail:


```php
try {
    $email->send();
} catch (\Exception $e) {
    echo 'Failed to send email: '.$e->getMessage();
}
```

> In the above example we wrap the execution of the `send()` method into a try-catch block
  so that when error occurs, your application will continue to run.



<a id="driver-kustom"></a>
## Custom Drivers

You can also register another e-mail driver if the built-in drivers doesn't suit your needs.


Create your new driver class:


```php
// application/libraries/mydriver.php

class Mydriver extends Mailer
{
    protected function transmit()
    {
        // Logic for sending your email..

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

Then change the default configuration to the driver you just created:


```php
Config::set('email.driver', 'mydriver');
```
