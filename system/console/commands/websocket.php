<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Config;
use System\Carbon;
use System\Cookie;
use System\Session;
use System\Websocket\Server;
use System\Websocket\Client;

class Websocket extends Command
{
    private $dsn;
    private $config;

    /**
     * Serve websocket server.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $this->config = Config::get('websocket');
        $this->dsn = 'tcp://' . get_cli_option('host', '127.0.0.1') . ':' . get_cli_option('port', 6001);
        $server = new Server($this->dsn);
        $server->on('start', [$this, 'start'])
            ->on('stop', [$this, 'stop'])
            ->on('connect', [$this, 'connect'])
            ->on('disconnect', [$this, 'disconnect'])
            ->on('idle', [$this, 'idle'])
            ->on('receive', [$this, 'receive'])
            ->on('send', [$this, 'send'])
            ->on('crash', [$this, 'crash'])
            ->run();
    }

    private function broadcast(Server $server, $message)
    {
        $clients = $server->clients();

        foreach ($clients as $client) {
            $client->send(Server::TEXT, $message);
        }
    }

    private function presence(Server $server)
    {
        $clients = $server->clients();
        $users = array_map(function ($client) {
            return [
                'id' => $client->id(),
                'name' => optional($client->user)->name ?: 'Guest',
                'connected_at' => Carbon::now()->timestamp,
            ];
        }, $clients);
        $message = json_encode(['type' => 'presence', 'users' => $users]);
        $this->broadcast($server, $message);
    }

    private function broadcast_to_channel(Server $server, $channel, $message)
    {
        $clients = $server->clients();

        foreach ($clients as $client) {
            if (in_array($channel, $client->channels)) {
                $client->send(Server::TEXT, $message);
            }
        }
    }

    private function private_message(Server $server, $targetId, $message)
    {
        $clients = $server->clients();

        foreach ($clients as $client) {
            if ($client->id() === $targetId) {
                $client->send(Server::TEXT, $message);
                break;
            }
        }
    }

    public function start(Server $server)
    {
        $this->log('WebSocket server started at: ' . $this->dsn);
        $this->log('Press Ctrl-C to quit.');
    }

    public function crash(Server $server)
    {
        $this->log('WebSocket server crashed!', true);

        if ($error = socket_last_error()) {
            $this->log('Socket error: ' . socket_strerror($error), true);
            socket_clear_error();
        }

        if ($error = error_get_last()) {
            $this->log('PHP error: ' . $error['message'], true);
        }
    }

    public function stop(Server $server)
    {
        $this->log('WebSocket server stopped');
    }

    public function connect(Client $client)
    {
        if ($session = Cookie::get(Config::get('session.cookie'))) {
            Session::load();
            Session::instance()->load($session);
            $client->user = Auth::user();
        }

        $this->broadcast($client->server(), sprintf('Client #%s connected', $client->id()));
        $this->presence($client->server());
    }

    public function disconnect(Client $client)
    {
        if ($error = socket_last_error()) {
            $this->log(socket_strerror($error), true);
            socket_clear_error();
        }

        $this->broadcast($client->server(), sprintf('Client #%s disconnected', $client->id()));
        $this->presence($client->server());
    }

    public function idle(Client $client)
    {
        // $this->log(sprintf('Client #%s is idle', $client->id()));
    }

    public function receive(Client $client, $opcode, $data)
    {
        if (intval($opcode) !== Server::TEXT) {
            if (intval($opcode) === Server::PING) {
                $pong = $client->server()->frame('', $client, 'pong');
                @socket_write($client->socket, $pong, strlen($pong));
            } else {
                $this->log(sprintf('Client #%s sent a message with ignored opcode %s.', $client->id(), $opcode));
            }

            return;
        }

        try {
            $parsed = json_decode($data, true);

            if (isset($parsed['ping'])) {
                return;
            }

            if (isset($parsed['command'])) {
                if ($parsed['command'] === 'broadcast' && isset($parsed['message'])) {
                    $this->broadcast($client->server(), $parsed['message']);

                    if ($this->config['logging_enabled']) {
                        $this->log("Command broadcast executed: {$parsed['message']}");
                    }

                    return;
                } elseif ($parsed['command'] === 'disconnect' && isset($parsed['client_id'])) {
                    $clients = $client->server()->clients();

                    foreach ($clients as $client) {
                        if ($client->id() == $parsed['client_id']) {
                            $client->close();

                            if ($this->config['logging_enabled']) {
                                $this->log("Command disconnect executed for client {$parsed['client_id']}");
                            }

                            return;
                        }
                    }

                    if ($this->config['logging_enabled']) {
                        $this->log("Command disconnect failed: client {$parsed['client_id']} not found");
                    }

                    return;
                } elseif ($parsed['command'] == 'presence') {
                    $this->presence($client->server());

                    if ($this->config['logging_enabled']) {
                        $this->log("Command presence executed");
                    }

                    return;
                } elseif ($parsed['command'] == 'broadcast_to_channel' && isset($parsed['channel']) && isset($parsed['message'])) {
                    $this->broadcast_to_channel($client->server(), $parsed['channel'], $parsed['message']);

                    if ($this->config['logging_enabled']) {
                        $this->log("Command broadcast_to_channel executed to {$parsed['channel']}: {$parsed['message']}");
                    }

                    return;
                } elseif ($parsed['command'] == 'private_message' && isset($parsed['to']) && isset($parsed['message'])) {
                    $this->private_message($client->server(), $parsed['to'], $parsed['message']);

                    if ($this->config['logging_enabled']) {
                        $this->log("Command private_message executed to {$parsed['to']}: {$parsed['message']}");
                    }

                    return;
                }
            }

            if (isset($parsed['event'])) {
                if ($parsed['event'] == 'subscribe' && isset($parsed['channel'])) {
                    $client->channels[] = $parsed['channel'];
                } elseif ($parsed['event'] == 'message' && isset($parsed['channel']) && isset($parsed['data'])) {
                    $message = json_encode(['channel' => $parsed['channel'], 'data' => $parsed['data'], 'client_id' => $client->id()]);
                    $this->broadcast_to_channel($client->server(), $parsed['channel'], $message);
                }
            } elseif (isset($parsed['to']) && isset($parsed['message'])) {
                $message = json_encode(['client_id' => $client->id(), 'message' => trim($parsed['message'])]);
                $this->private_message($client->server(), $parsed['to'], $message);
            } else {
                $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
                $this->broadcast($client->server(), $message);
            }
        } catch (\Throwable $e) {
            $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
            $this->broadcast($client->server(), $message);
        } catch (\Exception $e) {
            $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
            $this->broadcast($client->server(), $message);
        }
    }

    public function send(Client $client, $opcode, $data)
    {
        $this->log(sprintf('Sent to client #%s: %s', $client->id(), $data));
    }

    private function log($message, $is_error = false)
    {
        if ($this->config['logging_enabled']) {
            echo $is_error
                ? $this->error('[' . Carbon::now() . '] ' . $message)
                : $this->info('[' . Carbon::now() . '] ' . $message);
            flush();
            ob_flush();
        }
    }
}
