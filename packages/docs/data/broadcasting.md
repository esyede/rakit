# Broadcasting

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Basic Knowledge](#basic-knowledge)
-   [Configuration](#configuration)
-   [Broadcasting Events](#broadcasting-events)
-   [Private Channels](#private-channels)
-   [Receiving in the Browser](#receiving-in-the-browser)
-   [Notifications](#notifications)
-   [Testing](#testing)

<!-- /MarkdownTOC -->

<a id="basic-knowledge"></a>

## Basic Knowledge

Broadcasting pushes server-side events to browsers in real time: an order shipped, a new chat
message, a finished import. The application sends an event to a channel, and every browser
subscribed to that channel on the [WebSocket server](/docs/websocket) receives it at once, with
no polling. Nothing else is needed: no Pusher account, no Redis, no Node.js.

<a id="configuration"></a>

## Configuration

Run the WebSocket server (keep it running with Supervisor, like a [job worker](/docs/job#supervisor-configuration)):

```bash
php rakit websocket:run --host=0.0.0.0 --port=6001
```

Then tell the application where it is, in `application/config/websocket.php`:

```php
'broadcast' => [
    'driver' => 'websocket', // 'log' writes events to the log, 'null' drops them
    'host' => '127.0.0.1',
    'port' => 6001,
    'timeout' => 3,
],
```

The application connects to the server on the `/rakit-broadcast` path and signs each event with
the application key (`RAKIT_KEY`), with a timestamp. The server refuses an unsigned, altered or
older-than-a-minute event, so browsers and other sites cannot broadcast in the application's
name. Requests, jobs and console commands can all broadcast, as long as they share the key with
the server.

<a id="broadcasting-events"></a>

## Broadcasting Events

```php
Broadcast::event('orders', 'OrderShipped', ['id' => $order->id, 'status' => 'shipped']);

// Several channels at once
Broadcast::event(['private-user.'.$order->user_id, 'admin'], 'OrderShipped', $order->to_array());

// From a job: the request does not wait for the WebSocket server
Broadcast::queue('orders', 'OrderShipped', ['id' => $order->id]);
```

Subscribers receive:

```json
{ "channel": "orders", "event": "OrderShipped", "data": { "id": 7, "status": "shipped" } }
```

`Broadcast::event()` throws a `RuntimeException` when the server cannot be reached; use
`Broadcast::queue()` where a broadcast must not fail a request (the job is retried).

<a id="private-channels"></a>

## Private Channels

Channels whose name starts with `private-` or `presence-` need permission to subscribe. Declare
who may join each one, e.g. in `application/boot.php`; `{parameters}` of the pattern are passed
after the logged in user:

```php
Broadcast::channel('private-user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('private-orders.{id}', function ($user, $id) {
    return (int) $user->id === (int) Order::find($id)->user_id;
});

Broadcast::routes(); // POST broadcasting/auth, behind the 'auth' middleware
```

`Broadcast::routes()` registers `POST broadcasting/auth`. A browser sends its `client_id` and the
channel there, and gets back `{"auth": "..."}`, the signature it subscribes with, or a `403`. A
channel without a `Broadcast::channel()` rule is refused. Pass another middleware with
`Broadcast::routes('auth|throttle:60,1')`.

<a id="receiving-in-the-browser"></a>

## Receiving in the Browser

```javascript
const ws = new WebSocket('wss://example.com:6001');

ws.onmessage = async (event) => {
    const message = JSON.parse(event.data);

    if (message.type === 'connected') {
        ws.send(JSON.stringify({ event: 'subscribe', channel: 'orders' }));

        // A private channel: ask the application for the signature first.
        const response = await fetch('/broadcasting/auth', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Csrf-Token': csrfToken },
            body: JSON.stringify({ client_id: message.client_id, channel: 'private-user.1' }),
        });
        const { auth } = await response.json();
        ws.send(JSON.stringify({ event: 'subscribe', channel: 'private-user.1', auth }));
        return;
    }

    if (message.event === 'OrderShipped') {
        showToast('Order #' + message.data.id + ' shipped');
    }
};
```

The page must be served from the same host as the WebSocket server, or be listed in
`allowed_origins` (see [WebSocket configuration](/docs/websocket#configuration)).

<a id="notifications"></a>

## Notifications

[Notifications](/docs/notifications) have a `broadcast` channel. A `User` model #1 receives them
on `private-user.1` (define `route_notification_for_broadcast()` on the model to choose another
channel), with the notification's class as the event name and `to_broadcast()` (or else
`to_array()`) as the data:

```php
class InvoicePaid extends Notification
{
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function to_broadcast($notifiable)
    {
        return ['invoice' => $this->invoice->number];
    }
}
```

<a id="testing"></a>

## Testing

`Broadcast::fake()` records events instead of sending them:

```php
Broadcast::fake();

// .. the code under test

Broadcast::assert_broadcast('OrderShipped');
Broadcast::assert_broadcast('OrderShipped', function ($broadcast) {
    return in_array('orders', $broadcast['channels']) && 7 === $broadcast['data']['id'];
});
Broadcast::assert_not_broadcast('OrderCancelled');
Broadcast::assert_nothing_broadcast();
```

`Broadcast::recorded('OrderShipped')` returns the recorded events. Call `Broadcast::fake(false)`
in `tearDown()`.
