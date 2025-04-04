<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

class Agent
{
    protected $server;
    protected $id;
    protected $socket;
    protected $flag;
    protected $verb;
    protected $uri;
    protected $headers;

    /**
     * Konstruktor.
     *
     * @param Server   $server
     * @param resource $socket
     * @param string   $verb
     * @param string   $uri
     * @param array    $headers
     */
    public function __construct($server, $socket, $verb, $uri, array $headers)
    {
        $this->server = $server;
        $this->id = stream_socket_get_name($socket, true);
        $this->socket = $socket;
        $this->verb = $verb;
        $this->uri = $uri;
        $this->headers = $headers;

        if (
            isset($this->server()->events()['connect'])
            && is_callable($function = $this->server()->events()['connect'])
        ) {
            $function($this);
        }
    }

    /**
     * Mereturn instance server.
     *
     * @return Server
     */
    public function server()
    {
        return $this->server;
    }

    /**
     * Mereturn socket ID.
     *
     * @return string
     */
    public function id()
    {
        return $this->id;
    }

    /**
     * Mereturn socket.
     *
     * @return resource
     */
    public function socket()
    {
        return $this->socket;
    }

    /**
     * Mereturn request method.
     *
     * @return string
     */
    public function verb()
    {
        return $this->verb;
    }

    /**
     * Mereturn request URI.
     *
     * @return string
     */
    public function uri()
    {
        return $this->uri;
    }

    /**
     * Mereturn socket header.
     *
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }

    /**
     * Siapkan dan kirim payload.
     *
     * @param int    $opcode
     * @param string $data
     *
     * @return string|false
     */
    public function send($opcode, $data = '')
    {
        $mask = Server::FINALE | $opcode & Server::OPCODE;
        $length = strlen($data);
        $buffer = pack('CC', $mask, $length);

        if ($length > 0xffff) {
            $buffer = pack('CCNN', $mask, 0x7f, $length);
        } elseif ($length > 0x7d) {
            $buffer = pack('CCn', $mask, 0x7e, $length);
        }

        $buffer .= $data;
        if (is_bool($this->server()->write($this->socket, $buffer))) {
            return false;
        }

        if (
            !in_array($opcode, [Server::PONG, Server::CLOSE])
            && isset($this->server()->events()['send'])
            && is_callable($function = $this->server()->events()['send'])
        ) {
            $function($this, $opcode, $data);
        }

        return $data;
    }

    /**
     * Ambil dan proses payload.
     *
     * @return bool|null
     */
    public function fetch()
    {
        if (is_bool($buffer = $this->server()->read($this->socket))) {
            return false;
        }

        while ($buffer) {
            $opcode = ord($buffer[0]) & Server::OPCODE;
            $length = ord($buffer[1]) & Server::LENGTH;
            $position = 2;

            if ($length === 0x7e) {
                $length = ord($buffer[2]) * 256 + ord($buffer[3]);
                $position += 2;
            } elseif ($length === 0x7f) {
                for ($i = 0, $length = 0; $i < 8; ++$i) {
                    $length = $length * 256 + ord($buffer[$i + 2]);
                }

                $position += 8;
            }

            for ($i = 0, $mask = []; $i < 4; ++$i) {
                $mask[$i] = ord($buffer[$position + $i]);
            }

            $position += 4;

            if (strlen($buffer) < $length + $position) {
                return false;
            }

            for ($i = 0, $data = ''; $i < $length; ++$i) {
                $data .= chr(ord($buffer[$position + $i]) ^ $mask[$i % 4]);
            }

            switch ($opcode & Server::OPCODE) {
                case Server::PING:
                    $this->send(Server::PONG);
                    break;

                case Server::CLOSE:
                    $this->server()->close($this->socket);
                    break;

                case Server::TEXT:
                    $data = trim($data);

                case Server::BINARY:
                    if (
                        isset($this->server()->events()['receive'])
                        && is_callable($function = $this->server()->events()['receive'])
                    ) {
                        $function($this, $opcode, $data);
                    }
                    break;
            }

            $buffer = substr($buffer, $length + $position);
        }
    }

    /**
     * Destruktor.
     */
    public function __destruct()
    {
        if (
            isset($this->server()->events()['disconnect'])
            && is_callable($function = $this->server()->events()['disconnect'])
        ) {
            $function($this);
        }
    }
}
