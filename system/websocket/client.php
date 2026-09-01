<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

use System\Carbon;

class Client
{
    public $id;

    public $user;

    public $socket;

    public $uri = '';

    public $buffer = '';

    public $message = '';

    public $busy = false;

    public $handshake = false;

    public $continuous = false;

    public $disconnecting = false;

    public $channels = [];

    public $headers = [];

    protected $server;

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

        if (
            isset($this->server()->events['send'])
            && is_callable($function = $this->server()->events['send'])
        ) {
            $function($this, $opcode, $data);
        }

        return $result;
    }

    /**
     * Destroy the client instance.
     *
     * @return void
     */
    public function __destruct()
    {
        if (
            isset($this->server()->events['disconnect'])
            && is_callable($function = $this->server()->events['disconnect'])
        ) {
            $function($this);
        }
    }
}
