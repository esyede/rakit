# WebSocket

The WebSocket package provides real-time communication capabilities for your Rakit application using WebSocket protocol.

## Installation

The WebSocket package is included in the core Rakit framework. No additional installation is required.

## Configuration

Configure WebSocket in `application/config/websocket.php`:

- `max_buffer_size`: Maximum buffer size for receiving data.
- `origin_required`: Whether Origin header is required.
- `logging_enabled`: Enable logging.
- `logging_output`: Logging output ('file' or 'stdout').

## Running the Server

Start the WebSocket server using the console command:

```bash
php rakit websocket:run
```

The server will run on the configured host and port (default: 127.0.0.1:6001).

## Client Connection

Connect to the server from JavaScript:

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001');
ws.onmessage = (event) => {
    console.log('Received:', JSON.parse(event.data));
};
```

Authenticated users can connect with session cookies for user data in presence.

## Message Formats

### Broadcasting

Send a message to all connected clients:

```javascript
ws.send(JSON.stringify({ message: 'Hello everyone!' }));
```

### Private Messages

Send a message to a specific client:

```javascript
ws.send(JSON.stringify({ to: 'client_id', message: 'Hello!' }));
```

### Channels

Subscribe to a channel:

```javascript
ws.send(JSON.stringify({ event: 'subscribe', channel: 'chat' }));
```

Send to channel:

```javascript
ws.send(JSON.stringify({ event: 'message', channel: 'chat', data: 'Hello channel!' }));
```

### Commands

Control the server via WebSocket messages:

- Broadcast: `{"command": "broadcast", "message": "text"}`
- Disconnect client: `{"command": "disconnect", "client_id": "id"}`
- Update presence: `{"command": "presence"}`
- Broadcast to channel: `{"command": "broadcast_to_channel", "channel": "name", "message": "text"}`
- Private message: `{"command": "private_message", "to": "client_id", "message": "text"}`

## Presence

Presence shows online users. Triggered on connect/disconnect.

Response format:

```json
{
    "type": "presence",
    "users": [
        {
            "id": "client_id",
            "name": "User Name",
            "email": "user@example.com",
            "connected_at": 1234567890
        }
    ]
}
```

## Events

The server fires events:

- `start`: Server started.
- `connect`: Client connected.
- `disconnect`: Client disconnected.
- `receive`: Message received.
- `send`: Message sent.
- `crash`: Server crashed.

## Examples

### Basic Chat

```javascript
const ws = new WebSocket('ws://127.0.0.1:6001');
ws.onopen = () => ws.send(JSON.stringify({ message: 'Hello!' }));
ws.onmessage = (event) => {
    const data = JSON.parse(event.data);
    if (data.type === 'presence') {
        console.log('Online users:', data.users);
    } else {
        console.log('Message:', data);
    }
};
```

### Using Commands

```javascript
// Broadcast via command
ws.send(JSON.stringify({ command: 'broadcast', message: 'Server message' }));
```

## Security

- Enable `origin_required` for production.
- Authenticate users via session for presence data.
- Use HTTPS for secure connections.

## Troubleshooting

- Check logs if `logging_enabled` is true.
- Ensure port is not in use.
- Verify client connection URL.
