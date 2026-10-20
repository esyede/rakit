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
    /**
     * Data Source Name (DSN) for the websocket server.
     *
     * @var string
     */
    private $dsn;

    /**
     * Configuration for the websocket server.
     *
     * @var array
     */
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
        $this->dsn = 'tcp://'.get_cli_option('host', '127.0.0.1').':'.get_cli_option('port', 6001);
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
        $clients = array_filter($server->clients(), function ($client) {
            return ! Server::is_publisher($client->uri());
        });
        $users = array_map(function ($client) {
            return [
                'id' => $client->id(),
                'name' => optional($client->user)->name ?: 'Guest',
                'connected_at' => Carbon::now()->timestamp,
            ];
        }, $clients);
        $message = json_encode(['type' => 'presence', 'users' => array_values($users)]);
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
     * Send a message to a specific client.
     * The sender and target must share a private or presence channel.
     *
     * @param Client $sender
     * @param string $targetId
     * @param string $message
     *
     * @return bool
     */
    private function private_message(Client $sender, $targetId, $message)
    {
        $clients = $sender->server()->clients();

        foreach ($clients as $client) {
            if ($client->id() === $targetId) {
                $shared = array_filter(array_intersect($sender->channels, $client->channels), function ($channel) {
                    return Server::is_private($channel);
                });

                if (empty($shared)) {
                    return false;
                }

                $client->send(Server::TEXT, $message);
                return true;
            }
        }

        return false;
    }

    /**
     * Send an error message back to the client.
     *
     * @param Client $client
     * @param string $message
     *
     * @return void
     */
    private function reject(Client $client, $message)
    {
        $client->send(Server::TEXT, json_encode(['type' => 'error', 'message' => $message]));

        if ($this->logging()) {
            $this->log(sprintf('Client #%s rejected: %s', $client->id(), $message));
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
        $this->log('WebSocket server started at: '.$this->dsn);
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

        if ($error = error_get_last()) {
            $this->log('PHP error: '.$error['message'], true);
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

    /**
     * Handle the connect event.
     *
     * @param Client $client
     *
     * @return void
     */
    public function connect(Client $client)
    {
        if (Server::is_publisher($client->uri())) {
            return;
        }

        if ($session = Cookie::get(Config::get('session.cookie'))) {
            Session::load();
            Session::instance()->load($session);
            $client->user = Auth::user();
        }

        $client->send(Server::TEXT, json_encode(['type' => 'connected', 'client_id' => $client->id()]));
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
        if (Server::is_publisher($client->uri())) {
            return;
        }

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
        if ((int) $opcode !== Server::TEXT) {
            if ((int) $opcode === Server::PING) {
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

            if (isset($parsed['event']) && 'publish' === $parsed['event']) {
                return $this->publish($client, $parsed);
            }

            if (isset($parsed['command'])) {
                if ($parsed['command'] === 'broadcast' && isset($parsed['message'])) {
                    if (! $this->client_broadcast($client, $parsed['message'])) {
                        return;
                    }

                    if ($this->logging()) {
                        $this->log("Command broadcast executed: {$parsed['message']}");
                    }

                    return;
                } elseif ($parsed['command'] === 'presence') {
                    $this->presence($client->server());

                    if ($this->logging()) {
                        $this->log('Command presence executed');
                    }

                    return;
                } elseif (
                    $parsed['command'] === 'broadcast_to_channel'
                    && isset($parsed['channel'])
                    && isset($parsed['message'])
                ) {
                    if (! in_array($parsed['channel'], $client->channels, true)) {
                        return $this->reject($client, 'Not subscribed to this channel');
                    }

                    $this->broadcast_to_channel($client->server(), $parsed['channel'], $parsed['message']);

                    if ($this->logging()) {
                        $this->log(
                            "Command broadcast_to_channel executed to {$parsed['channel']}: {$parsed['message']}"
                        );
                    }

                    return;
                } elseif (
                    $parsed['command'] === 'private_message'
                    && isset($parsed['to'])
                    && isset($parsed['message'])
                ) {
                    if (! $this->private_message($client, $parsed['to'], $parsed['message'])) {
                        return $this->reject($client, 'No shared private channel with the target client');
                    }

                    if ($this->logging()) {
                        $this->log("Command private_message executed to {$parsed['to']}: {$parsed['message']}");
                    }

                    return;
                }

                return $this->reject($client, 'Unknown or invalid command');
            }

            if (isset($parsed['event'])) {
                if ($parsed['event'] === 'subscribe' && isset($parsed['channel'])) {
                    $auth = isset($parsed['auth']) ? $parsed['auth'] : null;

                    if (! Server::authorize($client->id(), $parsed['channel'], $auth)) {
                        return $this->reject($client, 'Unauthorized channel subscription');
                    }

                    if (! in_array($parsed['channel'], $client->channels, true)) {
                        $client->channels[] = $parsed['channel'];
                    }
                } elseif (
                    $parsed['event'] === 'message'
                    && isset($parsed['channel'])
                    && isset($parsed['data'])
                ) {
                    if (! in_array($parsed['channel'], $client->channels, true)) {
                        return $this->reject($client, 'Not subscribed to this channel');
                    }

                    $message = json_encode([
                        'channel' => $parsed['channel'],
                        'data' => $parsed['data'],
                        'client_id' => $client->id(),
                    ]);
                    $this->broadcast_to_channel($client->server(), $parsed['channel'], $message);
                }
            } elseif (isset($parsed['to']) && isset($parsed['message'])) {
                $message = json_encode(['client_id' => $client->id(), 'message' => trim($parsed['message'])]);

                if (! $this->private_message($client, $parsed['to'], $message)) {
                    $this->reject($client, 'No shared private channel with the target client');
                }
            } else {
                $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
                $this->client_broadcast($client, $message);
            }
        } catch (\Throwable $e) {
            $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
            $this->client_broadcast($client, $message);
        } catch (\Exception $e) {
            $message = json_encode(['client_id' => $client->id(), 'message' => trim($data)]);
            $this->client_broadcast($client, $message);
        }
    }

    /**
     * Broadcast a client-originated message to everyone, if the
     * "client_broadcast" config allows it. Otherwise reject it.
     * Server-originated broadcasts (connect/disconnect notices, presence) use broadcast().
     *
     * @param Client $client
     * @param string $message
     *
     * @return bool
     */
    /**
     * Deliver a broadcast the application signed (System\Broadcast) to its channels'
     * subscribers, as {"channel": ..., "event": ..., "data": ...}.
     *
     * @param Client $client
     * @param array  $parsed
     */
    private function publish(Client $client, array $parsed)
    {
        $time = isset($parsed['time']) ? (int) $parsed['time'] : 0;
        $payload = isset($parsed['payload']) && is_string($parsed['payload']) ? $parsed['payload'] : '';
        $signature = isset($parsed['signature']) ? $parsed['signature'] : null;

        if (abs(time() - $time) > 60 || ! \System\Crypter::equals(Server::publish_signature($time, $payload), $signature)) {
            return $this->reject($client, 'Invalid broadcast signature');
        }

        $message = json_decode($payload, true);

        if (! is_array($message) || ! isset($message['channels'], $message['event']) || ! is_array($message['channels'])) {
            return $this->reject($client, 'Invalid broadcast');
        }

        foreach ($message['channels'] as $channel) {
            $this->broadcast_to_channel($client->server(), (string) $channel, json_encode([
                'channel' => (string) $channel,
                'event' => (string) $message['event'],
                'data' => isset($message['data']) ? $message['data'] : null,
            ]));
        }

        if ($this->logging()) {
            $this->log(sprintf('Broadcast %s to %s', $message['event'], implode(', ', $message['channels'])));
        }
    }

    private function client_broadcast(Client $client, $message)
    {
        if (! Config::get('websocket.client_broadcast', false)) {
            $this->reject($client, 'Client broadcasting is disabled');
            return false;
        }

        $this->broadcast($client->server(), $message);
        return true;
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
            echo $is_error
                ? $this->error('['.Carbon::now().'] '.$message)
                : $this->info('['.Carbon::now().'] '.$message);
            flush();
            ob_get_contents() && ob_flush();
        }
    }

    /**
     * Whether logging is switched on.
     *
     * @return bool
     */
    private function logging()
    {
        return is_array($this->config) && isset($this->config['logging_enabled']) && $this->config['logging_enabled'];
    }
}
