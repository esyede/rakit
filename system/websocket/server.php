<?php

namespace System\Websocket;

defined('DS') or exit('No direct access.');

use System\Str;
use System\Log;
use System\Config;
use System\Console\Color;
use System\Carbon;

class Server
{
    const TEXT = 1;
    const BINARY = 2;
    const CLOSE = 8;
    const PING = 9;
    const PONG = 10;
    const MAGIC = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';

    protected $config = [];
    protected $master;
    protected $sockets = [];
    protected $users = [];
    protected $pendings = [];
    protected $events = [];

    public function __construct($address)
    {
        $this->config = Config::get('websocket');
        $dsn = str_replace('tcp://', '', $address);
        $dsn = (strpos($dsn, '://') === false) ? 'tcp://' . $dsn : $dsn;
        $context = stream_context_create(['socket' => ['so_reuseaddr' => true]]);
        $this->master = @stream_socket_server($dsn, $errno, $errstr, STREAM_SERVER_BIND | STREAM_SERVER_LISTEN, $context);

        if (!$this->master) {
            $this->stderr('Failed: stream_socket_server() - ' . $errstr . ' (' . $errno . ')');
            die('Failed: stream_socket_server() - ' . $errstr);
        }

        $this->sockets['master'] = $this->master;
        register_shutdown_function([$this, 'shutdown']);
    }

    protected function process($user, $message)
    {
        // Override in the subclass
    }

    protected function connected($user)
    {
        if (isset($this->events['connect']) && is_callable($function = $this->events['connect'])) {
            $function($user);
        }
    }

    protected function closed($user)
    {
        if (isset($this->events['disconnect']) && is_callable($function = $this->events['disconnect'])) {
            $function($user);
        }
    }

    protected function connecting($user)
    {
        // Override this if needed
        // $this->stdout(sprintf('Client #%s is connecting', $user->id()));
    }

    protected function tick()
    {
        // Override this for periodic tasks
        // $this->stdout('Tick');
    }

    protected function pendings()
    {
        foreach ($this->pendings as $key => $connection) {
            $found = false;

            foreach ($this->users as $user) {
                if ($connection['user']->socket == $user->socket) {
                    $found = true;

                    if ($user->handshake) {
                        unset($this->pendings[$key]);
                        $this->send($user, $connection['message']);
                    }
                }
            }

            if (!$found) {
                unset($this->pendings[$key]);
            }
        }
    }

    protected function communicate($socket, $data)
    {
        $length = strlen($data);
        $written = @fwrite($socket, $data, $length);
        return ($written !== false && $written === $length) ? $written : false;
    }

    public function run()
    {
        if (isset($this->events['start']) && is_callable($function = $this->events['start'])) {
            $function($this);
        }

        while (true) {
            if (empty($this->sockets)) {
                $this->sockets['master'] = $this->master;
            }

            $read = array_values($this->sockets);
            $write = $except = null;
            $this->pendings();
            $this->tick();
            $count = @stream_select($read, $write, $except, 5);

            if ($count === false) {
                $this->stderr('stream_select() failed');
                break;
            }

            foreach ($read as $socket) {
                if ($socket === $this->master) {
                    $client = @stream_socket_accept($this->master, 5);

                    if ($client === false) {
                        $this->stderr('Failed: stream_socket_accept()');
                        continue;
                    } else {
                        $this->connect($client);
                        $this->stdout('Client connected. ' . (int) $client);
                    }
                } else {
                    $this->stdout('Reading from socket ' . (int) $socket);
                    $buffer = @fread($socket, $this->config['max_buffer_size']);

                    if ($buffer === false || $buffer === '') {
                        if (feof($socket)) {
                            $this->disconnect($socket);
                            $this->stderr('Client disconnected. TCP connection lost: ' . (int) $socket);
                        } else {
                            $error = error_get_last();
                            $this->stderr('Socket error: ' . ($error['message'] ?? 'Unknown error'));
                            $this->disconnect($socket, true);
                        }
                    } else {
                        $bytes = strlen($buffer);
                        $this->stdout('Received ' . $bytes . ' bytes from socket ' . (int) $socket);
                        $user = $this->find($socket);

                        if (!$user->handshake) {
                            if (strpos(str_replace(CR, '', $buffer), LF . LF) === false) {
                                $this->stdout('Handshake buffer incomplete for socket ' . (int) $socket);
                                continue;
                            }

                            $this->handshake($user, $buffer);
                        } else {
                            $this->split_packet($bytes, $buffer, $user);
                        }
                    }
                }
            }

            if (!$count) {
                foreach ($this->sockets as $id => $socket) {
                    if (
                        $socket !== $this->master
                        && isset($this->users[$id])
                        && isset($this->events['idle'])
                        && is_callable($function = $this->events['idle'])
                    ) {
                        $client = $this->users[$id];

                        if (time() - $client->last_activity() > $this->config['ping_timeout']) {
                            $this->disconnect($socket);
                            continue;
                        }

                        $function($client);
                    }
                }
            }
        }
    }

    protected function connect($socket)
    {
        $user = new Client(Str::random(8), $socket);
        $user->of($this);
        $this->users[$user->id] = $user;
        $this->sockets[$user->id] = $socket;
        $this->connecting($user);
    }

    protected function disconnect($socket, $close = true, $errno = null)
    {
        $disconnected = $this->find($socket);

        if ($disconnected !== null) {
            unset($this->users[$disconnected->id]);

            if (array_key_exists($disconnected->id, $this->sockets)) {
                unset($this->sockets[$disconnected->id]);
            }

            if ($close) {
                $this->stdout('Client disconnected. ' . (int) $disconnected->socket);
                $this->closed($disconnected);
                @fclose($disconnected->socket);
            } else {
                $message = $this->frame('', $disconnected, 'close');
                $this->communicate($disconnected->socket, $message);
            }
        }
    }

    protected function handshake($user, $buffer)
    {
        $this->stdout('Handshake started for client ' . $user->id());
        $lines = explode(LF, $buffer);
        $headers = [];

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                $header = explode(':', $line, 2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
            } elseif (stripos($line, 'get ') !== false) {
                preg_match('/GET (.*) HTTP/i', $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }

        if (isset($headers['get'])) {
            $user->uri = $headers['get'];
        } else {
            $response = 'HTTP/1.1 405 Method Not Allowed' . CRLF . CRLF;
        }

        if (!isset($headers['host']) || !$this->check_host($headers['host'])) {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['upgrade']) || strtolower($headers['upgrade']) != 'websocket') {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['connection']) || strpos(strtolower($headers['connection']), 'upgrade') === false) {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['sec-websocket-key'])) {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['sec-websocket-version']) || intval(strtolower($headers['sec-websocket-version'])) !== 13) {
            $response = 'HTTP/1.1 426 Upgrade Required' . CRLF . 'Sec-WebSocketVersion: 13';
        }

        if (
            ($this->config['origin_required'] && !isset($headers['origin']))
            || ($this->config['origin_required'] && !$this->check_origin($headers['origin']))
        ) {
            $response = 'HTTP/1.1 403 Forbidden';
        }

        if (
            ($this->config['protocol_required'] && !isset($headers['sec-websocket-protocol']))
            || ($this->config['protocol_required'] && !$this->check_protocol($headers['sec-websocket-protocol']))
        ) {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (
            ($this->config['extensions_required'] && !isset($headers['sec-websocket-extensions']))
            || ($this->config['extensions_required'] && !$this->check_extensions($headers['sec-websocket-extensions']))
        ) {
            $response = 'HTTP/1.1 400 Bad Request';
        }

        if (isset($response)) {
            $this->communicate($user->socket, $response);
            $this->disconnect($user->socket);
            return;
        }

        $user->headers = $headers;
        $user->handshake = $buffer;

        $hash = sha1($headers['sec-websocket-key'] . static::MAGIC);
        $token = '';

        for ($i = 0; $i < 20; $i++) {
            $token .= chr(hexdec(substr($hash, $i * 2, 2)));
        }

        $token = base64_encode($token) . CRLF;
        $protocol = (isset($headers['sec-websocket-protocol'])) ? $this->protocol($headers['sec-websocket-protocol']) : '';
        $extensions = (isset($headers['sec-websocket-extensions'])) ? $this->extensions($headers['sec-websocket-extensions']) : '';
        $response = 'HTTP/1.1 101 Switching Protocols' . CRLF . 'Upgrade: websocket' . CRLF . 'Connection: Upgrade' . CRLF .
            'Sec-WebSocket-Accept: ' . $token . $protocol . $extensions . CRLF;

        $this->communicate($user->socket, $response);
        $this->stdout('Handshake completed for client ' . $user->id());
        $this->connected($user);
    }

    protected function check_origin($origin)
    {
        return empty($this->config['allowed_origins']) ? true : in_array($origin, $this->config['allowed_origins']);
    }

    protected function check_host($host)
    {
        return empty($this->config['allowed_hosts']) ? true : in_array($host, $this->config['allowed_hosts']);
    }

    protected function check_protocol($protocol)
    {
        $protocol = explode(',', $protocol);
        $protocols = array_map('trim', $protocol);
        $intersect = array_intersect($protocols, $this->config['supported_protocols']);
        return empty($this->config['supported_protocols']) ? true : !empty($intersect);
    }

    protected function check_extensions($extensions)
    {
        $extensions = explode(',', $extensions);
        $extensions = array_map('trim', $extensions);
        $intersect = array_intersect($extensions, $this->config['supported_extensions']);
        return empty($this->config['supported_extensions']) ? true : !empty($intersect);
    }

    protected function protocol($protocol)
    {
        $protocol = explode(',', $protocol);
        $protocols = array_map('trim', $protocol);

        foreach ($protocols as $protocol) {
            if (in_array($protocol, $this->config['supported_protocols'])) {
                return 'Sec-WebSocket-Protocol: ' . $protocol . CRLF;
            }
        }

        return '';
    }

    protected function extensions($extensions)
    {
        $extensions = explode(',', $extensions);
        $extensions = array_map('trim', $extensions);

        foreach ($extensions as $extension) {
            if (in_array($extension, $this->config['supported_extensions'])) {
                return 'Sec-WebSocket-Extensions: ' . $extension . CRLF;
            }
        }

        return '';
    }

    protected function find($socket)
    {
        foreach ($this->users as $user) {
            if ($user->socket == $socket) {
                return $user;
            }
        }

        return null;
    }

    public function stdout($message)
    {
        if ($this->config['logging_enabled']) {
            if ($this->config['logging_output'] === 'file') {
                Log::info($message);
            } else {
                echo Color::green('[' . Carbon::now() . '] ' . $message);
            }
        }
    }

    public function stderr($message)
    {
        if ($this->config['logging_enabled']) {
            if ($this->config['logging_output'] === 'file') {
                Log::error($message);
            } else {
                echo Color::red('[' . Carbon::now() . '] ' . $message);
            }
        }
    }

    protected function send($user, $message)
    {
        if ($user->handshake) {
            $message = $this->frame($message, $user);
            $result = $this->communicate($user->socket, $message);

            if (isset($this->events['send']) && is_callable($function = $this->events['send'])) {
                $function($user, Server::TEXT, $message);
            }
        } else {
            $this->pendings[] = ['user' => $user, 'message' => $message];
        }
    }

    public function frame($message, $user, $type = 'text', $continue = false)
    {
        switch ($type) {
            case 'continuous': $b1 = 0; break;
            case 'text':       $b1 = $user->continuous ? 0 : 1; break;
            case 'binary':     $b1 = $user->continuous ? 0 : 2; break;
            case 'close':      $b1 = 8; break;
            case 'ping':       $b1 = 9; break;
            case 'pong':       $b1 = 10; break;
        }

        if ($continue) {
            $user->continuous = true;
        } else {
            $b1 += 128;
            $user->continuous = false;
        }

        $length = strlen($message);
        $field = '';

        if ($length < 126) {
            $b2 = $length;
        } elseif ($length < 65536) {
            $b2 = 126;
            $hex = dechex($length);

            if (strlen($hex) % 2 == 1) {
                $hex = '0' . $hex;
            }

            $n = strlen($hex) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $field = chr(hexdec(substr($hex, $i, 2))) . $field;
            }

            while (strlen($field) < 2) {
                $field = chr(0) . $field;
            }
        } else {
            $b2 = 127;
            $hex = dechex($length);

            if (strlen($hex) % 2 == 1) {
                $hex = '0' . $hex;
            }

            $n = strlen($hex) - 2;

            for ($i = $n; $i >= 0; $i = $i - 2) {
                $field = chr(hexdec(substr($hex, $i, 2))) . $field;
            }

            while (strlen($field) < 8) {
                $field = chr(0) . $field;
            }
        }

        return chr($b1) . chr($b2) . $field . $message;
    }

    protected function split_packet($length, $packet, $user)
    {
        if ($user->busy) {
            $packet = $user->buffer . $packet;
            $user->busy = false;
            $length = strlen($packet);
        }

        $full = $packet;
        $pos = 0;
        $index = 1;

        while ($pos < $length) {
            $headers = $this->extract_headers($packet);
            $size = $headers['length'] + $this->calc_offset($headers);
            $frame = substr($full, $pos, $size);

            if (($message = $this->deframe($frame, $user, $headers)) !== false) {
                if ($user->disconnecting) {
                    $this->disconnect($user->socket);
                } else {
                    if ((preg_match('//u', $message)) || ($headers['opcode'] == 2)) {
                        $this->process($user, $message);

                        if (isset($this->events['receive']) && is_callable($function = $this->events['receive'])) {
                            $function($user, $headers['opcode'], $message);
                        }
                    } else {
                        $this->stderr('Not UTF-8' . PHP_EOL);
                    }
                }
            }

            $pos += $size;
            $packet = substr($full, $pos);
            $index++;
        }
    }

    protected function calc_offset($headers)
    {
        $offset = 2;

        if ($headers['hasmask']) {
            $offset += 4;
        }

        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }

        return $offset;
    }

    protected function deframe($message, &$user)
    {
        $headers = $this->extract_headers($message);
        $pong = false;
        $close = false;

        switch ($headers['opcode']) {
            case 0:
            case 1:
            case 2:
            case 10: break;
            case 8:  $user->disconnecting = true; return '';
            case 9:  $pong = true;
            default: $close = true; break;
        }

        if ($this->check_rsv_bits($headers, $user)) {
            return false;
        }

        if ($close) {
            return false;
        }

        $payload = $user->message . $this->extract_payload($message, $headers);

        if ($pong) {
            $reply = $this->frame($payload, $user, 'pong');
            $this->communicate($user->socket, $reply);
            return false;
        }

        if ($headers['length'] > strlen($this->apply_mask($headers, $payload))) {
            $user->busy = true;
            $user->buffer = $message;
            return false;
        }

        $payload = $this->apply_mask($headers, $payload);

        if ($headers['fin']) {
            $user->message = '';
            return $payload;
        }

        $user->message = $payload;
        return false;
    }

    protected function extract_headers($message)
    {
        $header = [
            'fin' => ord($message[0]) & 128, 'rsv1' => ord($message[0]) & 64,
            'rsv2' => ord($message[0]) & 32,  'rsv3' => ord($message[0]) & 16,
            'opcode' => ord($message[0]) & 15,  'hasmask' => ord($message[1]) & 128,
            'length' => 0, 'mask' => '',
        ];

        $header['length'] = (ord($message[1]) >= 128) ? ord($message[1]) - 128 : ord($message[1]);

        if ($header['length'] === 126) {
            if ($header['hasmask']) {
                $header['mask'] = $message[4] . $message[5] . $message[6] . $message[7];
            }

            $header['length'] = ord($message[2]) * 256 + ord($message[3]);
        } elseif ($header['length'] === 127) {
            if ($header['hasmask']) {
                $header['mask'] = $message[10] . $message[11] . $message[12] . $message[13];
            }

            $header['length'] = ord($message[2]) * 65536 * 65536 * 65536 * 256
                + ord($message[3]) * 65536 * 65536 * 65536
                + ord($message[4]) * 65536 * 65536 * 256
                + ord($message[5]) * 65536 * 65536
                + ord($message[6]) * 65536 * 256
                + ord($message[7]) * 65536
                + ord($message[8]) * 256
                + ord($message[9]);
        } elseif ($header['hasmask']) {
            $header['mask'] = $message[2] . $message[3] . $message[4] . $message[5];
        }

        return $header;
    }

    protected function extract_payload($message, $headers)
    {
        $offset = 2;

        if ($headers['hasmask']) {
            $offset += 4;
        }

        if ($headers['length'] > 65535) {
            $offset += 8;
        } elseif ($headers['length'] > 125) {
            $offset += 2;
        }

        return substr($message, $offset);
    }

    protected function apply_mask($headers, $payload)
    {
        $effective = '';

        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        } else {
            return $payload;
        }

        while (strlen($effective) < strlen($payload)) {
            $effective .= $mask;
        }

        while (strlen($effective) > strlen($payload)) {
            $effective = substr($effective, 0, -1);
        }

        return $effective ^ $payload;
    }

    protected function check_rsv_bits($headers, $user)
    {
        return (bool) (($headers['rsv1'] + $headers['rsv2'] + $headers['rsv3']) > 0);
    }

    public function clients($uri = null)
    {
        return array_filter($this->users, function ($val) use ($uri) {
            return $uri ? ($val->uri() === $uri) : true;
        });
    }

    public function on($event, $function)
    {
        $this->events[$event] = $function;
        return $this;
    }

    public function kill()
    {
        die;
    }

    public function shutdown()
    {
        foreach ($this->sockets as $socket) {
            if ($socket !== $this->master && is_resource($socket)) {
                /** @disregard */
                @fclose($socket);
            } else {
                $socket = null;
            }
        }

        if (is_resource($this->master)) {
            /** @disregard */
            @fclose($this->master);
        } else {
            $this->master = null;
        }

        if (isset($this->events['stop']) && is_callable($function = $this->events['stop'])) {
            $function($this);
        }
    }
}
