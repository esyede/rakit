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
     * Run the websocket server.
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

    /**
     * Broadcast a message to all subscribed clients.
     *
     * @param Server $server
     * @param string $message
     *
     * @return void
     */
    private function broadcast(Server $server, $message)
    {
        $clients = $server->clients();

        foreach ($clients as $client) {
            $client->send(Server::TEXT, $message);
        }
    }

    /**
     * Broadcast a presence to all subscribed clients.
     *
     * @param Server $server
     *
     * @return void
     */
    private function presence(Server $server)
    {
        $clients = $server->clients();
        $users = array_map(function ($client) {
            return ['id' => $client->id(), 'name' => optional($client->user)->name ?: 'Guest', 'connected_at' => Carbon::now()->timestamp];
        }, $clients);
        $message = json_encode(['type' => 'presence', 'users' => $users]);
        $this->broadcast($server, $message);
    }

    /**
     * Broadcast a message to all clients in a channel.
     *
     * @param Server $server
     * @param string $channel
     * @param string $message
     *
     * @return void
     */
    private function broadcast_to_channel(Server $server, $channel, $message)
    {
        $clients = $server->clients();

        foreach ($clients as $client) {
            if (in_array($channel, $client->channels)) {
                $client->send(Server::TEXT, $message);
            }
        }
    }

    /**
     * Broadcast a message to a specific client.
     *
     * @param Server $server
     * @param string $targetId
     * @param string $message
     *
     * @return void
     */
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

    /**
     * Handle the start event.
     *
     * @param Server $server
     *
     * @return void
     */
    public function start(Server $server)
    {
        $this->log('WebSocket server started at: ' . $this->dsn);
        $this->log('Press Ctrl-C to quit.');
    }

    /**
     * Handle the crash event.
     *
     * @param Server $server
     *
     * @return void
     */
    public function crash(Server $server)
    {
        $this->log('WebSocket server crashed!', true);

        // Note: the server runs on stream_socket_server(), so the ext-sockets
        // error functions that used to be called here reported nothing at best,
        // and were a fatal 'undefined function' whenever ext-sockets was not
        // installed - on every crash and every disconnect.
        if ($error = error_get_last()) {
            $this->log('PHP error: ' . $error['message'], true);
        }
    }

    /**
     * Handle the stop event.
     *
     * @param Server $server
     *
     * @return void
     */
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

    /**
     * Handle the disconnect event.
     *
     * @param Client $client
     *
     * @return void
     */
    public function disconnect(Client $client)
    {
        $this->broadcast($client->server(), sprintf('Client #%s disconnected', $client->id()));
        $this->presence($client->server());
    }

    /**
     * Handle the idle event.
     *
     * @param Client $client
     *
     * @return void
     */
    public function idle(Client $client)
    {
        // $this->log(sprintf('Client #%s is idle', $client->id()));
    }

    /**
     * Handle the receive event.
     *
     * @param Client $client
     * @param int    $opcode
     * @param string $data
     *
     * @return void
     */
    public function receive(Client $client, $opcode, $data)
    {
        if (intval($opcode) !== Server::TEXT) {
            if (intval($opcode) === Server::PING) {
                // Note: deframe() already answers a ping before this event ever
                // fires, so this is only a safety net - but it used to reach for
                // socket_write(), which cannot write to the stream resource the
                // client actually holds.
                $client->send(Server::PONG);
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

                    if ($this->logging()) {
                        $this->log("Command broadcast executed: {$parsed['message']}");
                    }

                    return;
                } elseif ($parsed['command'] === 'disconnect' && isset($parsed['client_id'])) {
                    $clients = $client->server()->clients();

                    // Note: this loop used to reuse the $client parameter as its
                    // own variable, leaving the caller's client pointing at
                    // whatever the loop stopped on.
                    foreach ($clients as $target) {
                        if ($target->id() == $parsed['client_id']) {
                            $target->close();

                            if ($this->logging()) {
                                $this->log("Command disconnect executed for client {$parsed['client_id']}");
                            }

                            return;
                        }
                    }

                    if ($this->logging()) {
                        $this->log("Command disconnect failed: client {$parsed['client_id']} not found");
                    }

                    return;
                } elseif ($parsed['command'] == 'presence') {
                    $this->presence($client->server());

                    if ($this->logging()) {
                        $this->log('Command presence executed');
                    }

                    return;
                } elseif ($parsed['command'] == 'broadcast_to_channel' && isset($parsed['channel']) && isset($parsed['message'])) {
                    $this->broadcast_to_channel($client->server(), $parsed['channel'], $parsed['message']);

                    if ($this->logging()) {
                        $this->log("Command broadcast_to_channel executed to {$parsed['channel']}: {$parsed['message']}");
                    }

                    return;
                } elseif ($parsed['command'] == 'private_message' && isset($parsed['to']) && isset($parsed['message'])) {
                    $this->private_message($client->server(), $parsed['to'], $parsed['message']);

                    if ($this->logging()) {
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

    /**
     * Handle the send event.
     *
     * @param Client $client
     * @param int    $opcode
     * @param string $data
     *
     * @return void
     */
    public function send(Client $client, $opcode, $data)
    {
        $this->log(sprintf('Sent to client #%s: %s', $client->id(), $data));
    }

    /**
     * Log the operation to stdout or file.
     *
     * @param string $message
     * @param bool   $is_error
     *
     * @return void
     */
    private function log($message, $is_error = false)
    {
        if ($this->logging()) {
            echo $is_error ? $this->error('[' . Carbon::now() . '] ' . $message) : $this->info('[' . Carbon::now() . '] ' . $message);
            flush();
            ob_get_contents() && ob_flush();
        }
    }

    /**
     * Whether logging is switched on.
     *
     * Note: the handlers are wired as callbacks and may fire before run() has
     * filled $config, so reaching straight into the array was an undefined index.
     *
     * @return bool
     */
    private function logging()
    {
        return is_array($this->config)
            && isset($this->config['logging_enabled'])
            && $this->config['logging_enabled'];
    }
}
