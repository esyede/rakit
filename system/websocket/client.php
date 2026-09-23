<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

use System\Carbon;

class Client
{
    /**
     * Contains the unique client ID.
     *
     * @var string
     */
    public $id;

    /**
     * Contains the application user bound to this client, if any.
     *
     * @var mixed
     */
    public $user;

    /**
     * Contains the client socket resource.
     *
     * @var resource
     */
    public $socket;

    /**
     * Contains the request URI.
     *
     * @var string
     */
    public $uri = '';

    /**
     * Contains the buffered bytes of an incomplete frame.
     *
     * @var string
     */
    public $buffer = '';

    /**
     * Contains the accumulated payload of a fragmented message.
     *
     * @var string
     */
    public $message = '';

    /**
     * Whether a partial frame is buffered and awaiting the rest.
     *
     * @var bool
     */
    public $busy = false;

    /**
     * Whether the WebSocket handshake has completed.
     *
     * @var bool
     */
    public $handshake = false;

    /**
     * Whether a fragmented message is currently being sent.
     *
     * @var bool
     */
    public $continuous = false;

    /**
     * Whether a close frame has been received.
     *
     * @var bool
     */
    public $disconnecting = false;

    /**
     * Contains the names of the channels this client is subscribed to.
     *
     * @var array
     */
    public $channels = [];

    /**
     * Contains the handshake request headers.
     *
     * @var array
     */
    public $headers = [];

    /**
     * Contains the server instance this client belongs to.
     *
     * @var \System\Websocket\Server
     */
    protected $server;

    /**
     * Contains the Unix timestamp of the last read or write.
     *
     * @var int
     */
    protected $last_activity;

    /**
     * Create a new WebSocket client instance.
     *
     * @param string $id
     * @param resource $socket
     */
    public function __construct($id, $socket)
    {
        $this->id = $id;
        $this->socket = $socket;
        $this->last_activity = Carbon::now()->timestamp;
    }

    /**
     * Get the WebSocket server instance.
     *
     * @return \System\Websocket\Server
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * Set the WebSocket server instance.
     *
     * @param \System\Websocket\Server $server
     *
     * @return void
     */
    public function of($server)
    {
        $this->server = $server;
    }

    /**
     * Get the client ID.
     *
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Get the client socket resource.
     *
     * @return resource
     */
    public function socket()
    {
        return $this->socket;
    }

    /**
     * Get the HTTP method used for the connection.
     *
     * @return string
     */
    public function method()
    {
        return 'GET';
    }

    /**
     * Get the request URI.
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Get the request headers.
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Get the last activity timestamp.
     *
     * @return int
     */
    public function last_activity()
    {
        return $this->last_activity;
    }

    /**
     * Send a message to the client.
     *
     * @param int    $opcode
     * @param string $data
     *
     * @return int|false
     */
    public function send($opcode, $data = '')
    {
        $this->last_activity = Carbon::now()->timestamp;
        $type = 'text';

        switch ($opcode) {
            case Server::TEXT:   $type = 'text';
                break;
            case Server::BINARY: $type = 'binary';
                break;
            case Server::CLOSE:  $type = 'close';
                break;
            case Server::PING:   $type = 'ping';
                break;
            case Server::PONG:   $type = 'pong';
                break;
        }

        $message = $this->server()->frame($data, $this, $type);

        if (is_resource($this->socket) && get_resource_type($this->socket) === 'stream') {
            $result = @fwrite($this->socket, $message, strlen($message));

            if ($result === false || $result !== strlen($message)) {
                $result = false;
            }
        } else {
            $result = strlen($message);
        }

        // Server handlers are protected: reach them through fire(), not directly.
        $this->server()->fire('send', [$this, $opcode, $data]);

        return $result;
    }
}
