# Notifications

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Creating Notifications](#creating-notifications)
-   [Sending Notifications](#sending-notifications)
-   [Database Notifications](#database-notifications)
-   [Custom Channels](#custom-channels)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

A notification is a short message to a user, such as "your invoice was paid", sent on one or
more channels: by **mail**, stored in the **database** to show in the application, or on a
channel of your own. Each channel gets the notification in its own form.

<a id="creating-notifications"></a>

## Creating Notifications

Extend `Notification` in `application/libraries/` (a name without underscores keeps it out of a
sub folder, see [Mailables](/docs/email#mailables)). `via()` picks the channels; `to_mail()`
returns a [mailable](/docs/email#mailables) and `to_array()` the data stored in the database:

```php
// application/libraries/invoicepaid.php
class InvoicePaid extends Notification
{
    protected $invoice;

    public function __construct($invoice)
    {
        $this->invoice = $invoice;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function to_mail($notifiable)
    {
        return new InvoiceMail($this->invoice);
    }

    public function to_array($notifiable)
    {
        return ['invoice' => $this->invoice->number, 'amount' => $this->invoice->amount];
    }
}
```

<a id="sending-notifications"></a>

## Sending Notifications

Add the `Notifiable` trait to the model that receives notifications, then `notify()` it, or send
to several at once:

```php
class User extends Facile
{
    use \System\Notifiable;
}

$user->notify(new InvoicePaid($invoice));

Notification::send(User::where('role', '=', 'admin')->get(), new InvoicePaid($invoice));
```

The mail goes to the mailable's own recipients, or else to the user's `email` attribute. Define
`route_notification_for_mail()` on the model to send elsewhere:

```php
public function route_notification_for_mail()
{
    return $this->billing_email;
}
```

<a id="database-notifications"></a>

## Database Notifications

The `database` channel stores each notification in a `notifications` table: run
`php rakit migrate` for the migration that ships in `application/migrations/`. The model reads
its notifications back as query builders, newest first:

```php
foreach ($user->unread_notifications()->get() as $notification) {
    $data = json_decode($notification->data, true);
    echo $data['invoice'];
}

$user->notifications()->paginate(20);
$user->mark_notifications_as_read();               // all of them
$user->mark_notifications_as_read([$id1, $id2]);   // some
```

Each row holds the notification `type` (its class), the `data` as JSON, `read_at` and
`created_at`. Change the table with `Notification::$database_table`.

<a id="custom-channels"></a>

## Custom Channels

Register a channel with a closure that receives the notifiable and the notification, then list
its name in `via()`:

```php
// application/boot.php
Notification::channel('sms', function ($notifiable, $notification) {
    Curl::post('https://sms.example.com/send', [], [
        'to' => $notifiable->phone,
        'text' => $notification->to_sms($notifiable),
    ]);
});
```

A notification sends on its channels as soon as it is sent. To keep a slow channel out of the
request, queue its mailable inside `to_mail()` instead and return `null`, or deliver from a
[job](/docs/job).

The built-in `broadcast` channel pushes a notification to the browser in real time, see
[Broadcasting](/docs/broadcasting#notifications).
