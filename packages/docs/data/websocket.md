# WebSocket

<!-- MarkdownTOC autolink="true" autoanchor="true" levels="2,3" bracket="round" lowercase="only_ascii" -->

-   [Introduction](#introduction)
-   [Running the Server](#running-the-server)
-   [Connecting a Client](#connecting-a-client)
-   [Message Format](#message-format)
    -   [Broadcasting](#broadcasting)
    -   [Private Messages](#private-messages)
    -   [Channels](#channels)
    -   [Private Channels](#private-channels)
    -   [Commands](#commands)
-   [Presence](#presence)
-   [Server Events](#server-events)
-   [Configuration](#configuration)

<!-- /MarkdownTOC -->

<a id="introduction"></a>

## Introduction

Rakit includes a WebSocket server for real-time messaging between browsers: broadcasting, private messages, channels and an online-users (presence) list. It is part of the core; there is nothing to install.

To push events from the application (requests, jobs) to browsers, see [Broadcasting](/docs/broadcasting).

<a id="running-the-server"></a>

## Running the Server

```bash
php rakit websocket:run
```

The server listens on `127.0.0.1:6001` by default. Change it with `--host` and `--port`:

```bash
php rakit websocket:run --host=0.0.0.0 --port=8080
```

<a id="connecting-a-client"></a>

## Connecting a Client

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001');

ws.onmessage = (event) => {
    console.log('Received:', event.data);
};
```

Right after connecting, the client receives its own ID, which it needs for [private channels](#private-channels):

```json
{ "type": "connected", "client_id": "..." }
```

If the browser sends the application's session cookie, the authenticated user is attached to the connection and shown in [presence](#presence).

Rejected messages are answered with `{"type": "error", "message": "..."}`.

> Not every message is JSON: the server's `Client #id connected` / `disconnected` notices (and, when `client_broadcast` is on, the `broadcast` command's message) are plain text.

<a id="message-format"></a>

## Message Format

Messages are JSON strings. A message containing a `ping` key is ignored (useful as a keep-alive).

<a id="broadcasting"></a>

### Broadcasting

Clients cannot broadcast to everyone by default: plain text, or any message that is not one of the formats below, is rejected with an error. Talk through [channels](#channels) or [private messages](#private-messages) instead. Only the server broadcasts globally (connect/disconnect notices and [presence](#presence)).

Set `client_broadcast` to `true` in `config/websocket.php` to restore the old behaviour, where such text is sent to every client as `{"client_id": "...", "message": "<the raw text>"}`:

```javascript
ws.send('Hello everyone!'); // only when client_broadcast is true
```

<a id="private-messages"></a>

### Private Messages

Send `to` and `message` to reach one client. The recipient gets `{"client_id": "<sender>", "message": "..."}`. Both clients must be subscribed to the same [private or presence channel](#private-channels), otherwise the message is rejected:

```javascript
ws.send(JSON.stringify({ to: 'client_id', message: 'Hello!' }));
```

<a id="channels"></a>

### Channels

Subscribe to a channel, then send to it. Only subscribers can send to a channel. Subscribers receive `{"channel": "...", "data": ..., "client_id": "<sender>"}`:

```javascript
ws.send(JSON.stringify({ event: 'subscribe', channel: 'chat' }));
ws.send(JSON.stringify({ event: 'message', channel: 'chat', data: 'Hello channel!' }));
```

<a id="private-channels"></a>

### Private Channels

Channels whose name starts with `private-` or `presence-` need an `auth` signature when subscribing; channels without these prefixes stay public. The signature is `hash_hmac('sha256', $client_id.':'.$channel, RAKIT_KEY)`, issued by your application from an authenticated route with `Websocket\Server::sign()`:

```php
use System\Websocket\Server;

Route::post('websocket/auth', ['before' => 'auth|csrf', function () {
    $channel = Input::get('channel');

    // Check the user may join this channel here.
    if ($channel !== 'private-user.'.Auth::user()->id) {
        return Response::error(403);
    }

    return Response::json(['auth' => Server::sign(Input::get('client_id'), $channel)]);
}]);
```

The client sends its `client_id` (from the `connected` message) to that route, then subscribes with the returned signature:

```javascript
ws.send(JSON.stringify({ event: 'subscribe', channel: 'private-user.1', auth: auth }));
```

A missing or wrong signature is rejected and the client is not subscribed.

<a id="commands"></a>

### Commands

Messages with a `command` key control the server. Their `message` is delivered as-is, without the `client_id` wrapper:

| Command                | Fields                  | Effect                                   |
| ---------------------- | ----------------------- | ---------------------------------------- |
| `broadcast`            | `message`               | Send `message` to every client (rejected unless `client_broadcast` is `true`) |
| `broadcast_to_channel` | `channel`, `message`    | Send `message` to a channel's subscribers (sender must be subscribed) |
| `private_message`      | `to`, `message`         | Send `message` to one client (needs a shared private channel) |
| `presence`             | —                       | Re-broadcast the presence list           |

```javascript
ws.send(JSON.stringify({ command: 'broadcast_to_channel', channel: 'news', message: 'Hello subscribers' }));
```

> Unknown commands are rejected. Clients cannot disconnect other clients; a client closes only its own connection (`ws.close()`). `broadcast` and plain-text messages are rejected unless `client_broadcast` is on; keep it off when untrusted clients can connect.

<a id="presence"></a>

## Presence

On every connect and disconnect (and on the `presence` command) all clients receive the list of connected users. `name` is the authenticated user's name, or `Guest`:

```json
{
    "type": "presence",
    "users": [
        { "id": "client_id", "name": "User Name", "connected_at": 1234567890 }
    ]
}
```

<a id="server-events"></a>

## Server Events

`websocket:run` handles these server events: `start`, `stop`, `connect`, `disconnect`, `idle` (no socket activity), `receive`, `send` and `crash`. With `logging_enabled` on, each one is printed to the console.

<a id="configuration"></a>

## Configuration

Options live in `application/config/websocket.php`:

| Option                  | Default    | Description |
| ----------------------- | ---------- | ----------- |
| `max_buffer_size`       | `2048`     | Bytes read from a socket at once |
| `max_payload_size`      | `10485760` | Largest message a client may send; bigger frames close the connection |
| `origin_required`       | `true`     | Reject handshakes without an allowed `Origin` header |
| `allowed_origins`       | `[]`       | Accepted origins, e.g. `['https://example.com']`; empty accepts the socket's own host only (any port) |
| `allowed_hosts`         | `[]`       | Accepted `Host` headers; empty allows all |
| `protocol_required`     | `false`    | Require a supported `Sec-WebSocket-Protocol` |
| `supported_protocols`   | `[]`       | Accepted subprotocols; empty allows all |
| `extensions_required`   | `false`    | Require a supported `Sec-WebSocket-Extensions` |
| `supported_extensions`  | `[]`       | Accepted extensions; empty allows all |
| `client_broadcast`      | `false`    | Let clients broadcast to everyone (`broadcast` command, plain text). Off: rejected with an error |
| `ping_timeout`          | `0`        | Seconds of inactivity before an idle client is disconnected; `0` disables |
| `logging_enabled`       | `true`     | Enable logging |
| `logging_output`        | `'stdout'` | Where the server's own log lines go: `'file'` (through `Log`) or `'stdout'` |

> With `origin_required` on, non-browser clients must send an `Origin` header. With `allowed_origins` empty, only an origin on the same host as the socket is accepted, so a page served from another domain must be listed.

For a secure connection (`wss://`), put the server behind a TLS-terminating reverse proxy.
