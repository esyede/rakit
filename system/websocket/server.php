<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

class Server
{
    const TEXT = 1;
    const BINARY = 2;
    const CLOSE = 8;
    const PING = 9;
    const PONG = 10;
    const OPCODE = 15;
    const FINALE = 128;
    const LENGTH = 127;
    const PACKET = 65536;
    const MAGIC = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    protected $address;
    protected $context;
    protected $wait;
    protected $sockets;
    protected $protocol;
    protected $agents = [];
    protected $events = [];

    /**
     * Konstruktor.
     *
     * @param string   $address
     * @param resource $context
     * @param int      $wait
     */
    public function __construct($address, $context = null, $wait = 60)
    {
        $this->address = $address;
        $this->context = $context ?: stream_context_create();
        $this->wait = $wait;
        $this->events = [];
    }

    /**
     * Alokasikan stream socket.
     *
     * @param resource $socket
     */
    public function allocate($socket)
    {
        if (is_bool($buffer = $this->read($socket))) {
            return;
        }

        $headers = [];
        $verb = null;
        $uri = null;
        $lines = explode("\r\n", trim($buffer));

        foreach ($lines as $line) {
            if (false !== preg_match('/^(\w+)\s(.+)\sHTTP\/[\d.]{1,3}$/', trim($line), $match)) {
                $verb = $match[1];
                $uri = $match[2];
            } else {
                if (false !== preg_match('/^(.+): (.+)/', trim($line), $match)) {
                    $headers[strtr(ucwords(strtolower(strtr($match[1],'-',' '))), ' ', '-')] = $match[2];
                } else {
                    $this->close($socket);
                    return;
                }
            }
        }

        if (empty($headers['Upgrade']) && empty($headers['Sec-Websocket-Key'])) {
            if ($verb && $uri) {
                $this->write($socket, "HTTP/1.1 400 Bad Request\r\nConnection: close\r\n\r\n");
            }

            $this->close($socket);
            return;
        }

        $buffer = "HTTP/1.1 101 Switching Protocols\r\nUpgrade: websocket\r\nConnection: Upgrade\r\n";

        if (isset($headers['Sec-Websocket-Protocol'])) {
            $buffer .= 'Sec-WebSocket-Protocol: ' . $headers['Sec-Websocket-Protocol'] . "\r\n";
        }

        $key  = base64_encode(sha1($headers['Sec-Websocket-Key'] . static::MAGIC, true));
        $buffer .= 'Sec-WebSocket-Accept: ' . $key . "\r\n\r\n";

        if ($this->write($socket,$buffer)) {
            $this->sockets[(int) $socket] = $socket;
            $this->agents[(int) $socket] = new Agent($this, $socket, $verb, $uri, $headers);
        }
    }

    /**
     * Tutup stream socket.
     *
     * @param resource $socket
     */
    public function close($socket)
    {
        if (isset($this->agents[(int) $socket])) {
            unset($this->sockets[(int) $socket], $this->agents[(int) $socket]);
        }

        stream_socket_shutdown($socket, STREAM_SHUT_WR);
        @fclose($socket);
    }

    /**
     * Baca dari stream socket.
     *
     * @param resource $socket
     * @param int      $length
     *
     * @return string|false
     */
    public function read($socket, $length = 0)
    {
        $length = $length ?: static::PACKET;

        if (is_string($buffer = @fread($socket, $length)) && strlen($buffer) && strlen($buffer) < $length) {
            return $buffer;
        }

        if (isset($this->events['crash']) && is_callable($function = $this->events['crash'])) {
            $function($this);
        }

        $this->close($socket);
        return false;
    }

    /**
     * Tulis ke stream socket.
     *
     * @param resource $socket
     * @param string   $buffer
     *
     * @return int|false
     */
    public function write($socket, $buffer)
    {
        for ($i = 0, $bytes = 0; $i < strlen($buffer); $i += $bytes) {
            if (($bytes = @fwrite($socket, substr($buffer, $i))) && @fflush($socket)) {
                continue;
            }

            if (isset($this->events['crash']) && is_callable($function = $this->events['crash'])) {
                $function($this);
            }

            $this->close($socket);
            return false;
        }

        return $bytes;
    }

    /**
     * Mereturn socket agent.
     *
     * @param string $uri
     *
     * @return array
     */
    public function agents($uri = null)
    {
        return array_filter($this->agents, function ($val) use ($uri) {
            return $uri ? ($val->uri() === $uri) : true;
        });
    }

    /**
     * Mereturn event handler.
     *
     * @return array
     */
    public function events()
    {
        return $this->events;
    }

    /**
     * Bind function ke event handler
     *
     * @param string   $event
     * @param callable $function
     *
     * @return object
     */
    public function on($event, $function)
    {
        $this->events[$event] = $function;
        return $this;
    }

    /**
     * Hentikan server.
     */
    public function kill()
    {
        die;
    }

    /**
     * Jalankan server.
     */
    public function run()
    {
        declare(ticks=1);

        if (!extension_loaded('pcntl') || !is_callable('pcntl_signal')) {
            throw new \Exception('Please enable php-pcntl extension to use websocket');
        }

        pcntl_signal(SIGINT, [$this, 'kill']);
        pcntl_signal(SIGTERM, [$this, 'kill']);
        gc_enable();
        $listen = stream_socket_server($this->address, $errno, $error, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $this->context);
        $socket = socket_import_stream($listen);
        register_shutdown_function(function () use ($listen) {
            foreach ($this->sockets as $socket) {
                if ($socket !== $listen) {
                    $this->close($socket);
                }
            }

            $this->close($listen);

            if (isset($this->events['stop']) && is_callable($function = $this->events['stop'])) {
                $function($this);
            }
        });

        if ($error) {
            throw new \Exception($error);
        }

        if (isset($this->events['start']) && is_callable($function = $this->events['start'])) {
            $function($this);
        }

        $this->sockets = [(int) $listen => $listen];
        $wait = $this->wait;

        while (true) {
            $active = $this->sockets;
            $mark = microtime(true);
            $count = @stream_select($active, [], [], (int) $wait, round(1000000 * ($wait - (int) $wait)));

            if (is_bool($count) && $wait) {
                if (isset($this->events['crash']) && is_callable($function = $this->events['crash'])) {
                    $function($this);
                }

                die;
            }

            if ($count) {
                foreach ($active as $socket) {
                    if (!is_resource($socket)) {
                        continue;
                    }

                    if ($socket === $listen) {
                        if ($socket = @stream_socket_accept($listen, 0)) {
                            $this->allocate($socket);
                        } else {
                            if (isset($this->events['crash']) && is_callable($function = $this->events['crash'])) {
                                $function($this);
                            }
                        }
                    } else {
                        if (isset($this->agents[(int) $socket])) {
                            $this->agents[(int) $socket]->fetch();
                        }
                    }
                }

                $wait -= microtime(true) - $mark;

                while ($wait < 0.000001) {
                    $wait += $this->wait;
                    $count = 0;
                }
            }

            if (!$count) {
                $mark = microtime(true);

                foreach ($this->sockets as $id => $socket) {
                    if (!is_resource($socket)) {
                        continue;
                    }
                    if (
                        $socket != $listen
                        && isset($this->agents[$id])
                        && isset($this->events['idle'])
                        && is_callable($function = $this->events['idle'])
                    ) {
                        $function($this->agents[$id]);
                    }
                }

                $wait = $this->wait - microtime(true) + $mark;
            }

            gc_collect_cycles();
        }
    }
}
