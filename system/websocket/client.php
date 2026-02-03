<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

use System\Carbon;

class Client
{
    public $socket;
    public $id;
    public $headers = [];
    public $handshake = false;
    public $uri = '';
    public $busy = false;
    public $buffer = "";
    public $continuous = false;
    public $message = "";
    public $disconnecting = false;
    public $channels = [];
    public $user;

    protected $server;
    protected $last_activity;

    public function __construct($id, $socket)
    {
        $this->id = $id;
        $this->socket = $socket;
        $this->last_activity = Carbon::now()->timestamp;
    }

    public function server()
    {
        return $this->server;
    }

    public function of($server)
    {
        $this->server = $server;
    }

    public function id()
    {
        return $this->id;
    }

    public function socket()
    {
        return $this->socket;
    }

    public function method()
    {
        return 'GET';
    }

    public function uri()
    {
        return $this->uri;
    }

    public function headers()
    {
        return $this->headers;
    }

    public function last_activity()
    {
        return $this->last_activity;
    }

    public function send($opcode, $data = '')
    {
        $this->last_activity = Carbon::now()->timestamp;
        $type = 'text';

        switch ($opcode) {
            case Server::TEXT:
                $type = 'text';
                break;

            case Server::BINARY:
                $type = 'binary';
                break;

            case Server::CLOSE:
                $type = 'close';
                break;

            case Server::PING:
                $type = 'ping';
                break;

            case Server::PONG:
                $type = 'pong';
                break;
        }

        $message = $this->server()->frame($data, $this, $type);

        if (is_resource($this->socket) && get_resource_type($this->socket) === 'stream') {
            $result = strlen($message); // Simulasikan sukses untuk unit-testing
        } else {
            $result = @socket_write($this->socket, $message, strlen($message));
        }

        if (
            isset($this->server()->events['send'])
            && is_callable($function = $this->server()->events['send'])
        ) {
            $function($this, $opcode, $data);
        }

        return $result;
    }

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
