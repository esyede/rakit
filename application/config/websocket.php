<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Max Buffer Size
    |--------------------------------------------------------------------------
    |
    | Maximum buffer size for receiving data from socket. This value
    | determines how much data can be read in a single socket_recv operation.
    | If the message is larger than this size, it will be split or handled
    | in multiple parts. The default value is 2048 bytes.
    |
    */

    'max_buffer_size' => 2048,

    /*
    |--------------------------------------------------------------------------
    | Maximum Payload Size
    |--------------------------------------------------------------------------
    |
    | Largest message, in bytes, a client may send. A frame announcing more than
    | this is refused and the connection closed, so one client cannot make the
    | server buffer until it runs out of memory. Raise it if your application
    | legitimately sends larger messages.
    |
    */

    'max_payload_size' => 10485760,

    /*
    |--------------------------------------------------------------------------
    | Require Origin
    |--------------------------------------------------------------------------
    |
    | Determines whether the Origin header is required in the WebSocket handshake.
    | If enabled, connections without a valid Origin header will be rejected.
    | This is useful for security, especially in production environments.
    |
    | Enabled by default. Browsers always send an Origin header. With
    | "allowed_origins" below empty, only pages on the socket's own host are
    | accepted. Non-browser clients must send an Origin header.
    |
    */

    'origin_required' => true,

    /*
    |--------------------------------------------------------------------------
    | Require Protocol
    |--------------------------------------------------------------------------
    |
    | Determines whether the Sec-WebSocket-Protocol header is required.
    | If enabled, connections without a supported protocol will be rejected.
    | The protocol is used for subprotocols like chat or binary.
    |
    */

    'protocol_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Require Extensions
    |--------------------------------------------------------------------------
    |
    | Determines whether the Sec-WebSocket-Extensions header is required.
    | If enabled, connections without a supported extension will be rejected.
    | Extensions can be used for compression or other features.
    |
    */

    'extensions_required' => false,

    /*
    |--------------------------------------------------------------------------
    | Allowed Origins
    |--------------------------------------------------------------------------
    |
    | List of allowed origins (domains) for WebSocket connections.
    | If origin_required is enabled, only origins in this list are accepted.
    | Leave empty to accept the socket's own host only (any port), e.g. a page
    | on https://example.com connecting to ws://example.com:9000.
    |
    */

    'allowed_origins' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Hosts
    |--------------------------------------------------------------------------
    |
    | List of allowed hosts for WebSocket connections.
    | If host_required is enabled, only hosts in this list are accepted.
    | Leave empty to allow all hosts (not recommended for production).
    |
    */

    'allowed_hosts' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Protocols
    |--------------------------------------------------------------------------
    |
    | List of allowed protocols for WebSocket connections.
    | If protocol_required is enabled, only protocols in this list are accepted.
    | Leave empty to allow all protocols (not recommended for production).
    |
    */

    'supported_protocols' => [],

    /*
    |--------------------------------------------------------------------------
    | Allowed Extensions
    |--------------------------------------------------------------------------
    |
    | List of allowed extensions for WebSocket connections.
    | If extensions_required is enabled, only extensions in this list are accepted.
    | Leave empty to allow all extensions (not recommended for production).
    |
    */

    'supported_extensions' => [],

    /*
    |--------------------------------------------------------------------------
    | Client Broadcast
    |--------------------------------------------------------------------------
    |
    | Whether clients may broadcast to every connected client, via the
    | "broadcast" command or by sending plain-text / unrecognised messages.
    | Disabled by default: clients can only talk inside channels they joined
    | ("broadcast_to_channel", channel "message" events) or to a client sharing
    | a private/presence channel. Server-side broadcasts are not affected.
    |
    */

    'client_broadcast' => false,

    /*
    |--------------------------------------------------------------------------
    | Ping Timeout
    |--------------------------------------------------------------------------
    |
    | Time in seconds to wait for activity from the client before
    | considering the connection idle and disconnecting. Ping is used to
    | keep the connection alive. A value of 0 disables the timeout.
    |
    */

    'ping_timeout' => 0,

    /*
    |--------------------------------------------------------------------------
    | Enable Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging for WebSocket server.
    |
    */

    'logging_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Logging Output
    |--------------------------------------------------------------------------
    |
    | Specify the output location for logging: 'file' to write through the
    | Log class (see config/log.php), or 'stdout' to output to the console.
    |
    | Available options: 'file', 'stdout'
    |
    */

    'logging_output' => 'stdout',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | Where Broadcast::event() sends events. 'websocket' pushes them to the
    | server of "php rakit websocket:run" at host:port (signed with the app
    | key, so only this application can broadcast), 'log' writes them to the
    | log, 'null' drops them. 'timeout' is in seconds.
    |
    */

    'broadcast' => [
        'driver' => 'websocket',
        'host' => '127.0.0.1',
        'port' => 6001,
        'timeout' => 3,
    ],
];
