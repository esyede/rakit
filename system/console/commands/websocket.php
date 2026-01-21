<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Websocket\Server;
use System\Websocket\Client;

class Websocket extends Command
{
    /**
     * Jalankan websocket server.
     *
     * @param array $arguments
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $server = new Server('tcp://127.0.0.1:' . get_cli_option('port', 6001));
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

    public function start(Server $server)
    {
        $this->debug('WebSocket server started');
    }

    public function crash(Server $server)
    {
        $this->debug('WebSocket server crashes!');

        if ($error = socket_last_error()) {
            $this->debug(socket_strerror($error), true);
            socket_clear_error();
        }

        if ($error = error_get_last()) {
            $this->debug($error['message'], true);
        }
    }

    public function stop(Server $server)
    {
        $this->debug('WebSocket server stopped');
    }

    public function connect(Client $client)
    {
        $this->debug('Client #' . $client->id() . ' connected');
        $this->broadcast($client->server(), sprintf('Client with ID %s joined', $client->id()));
    }

    public function disconnect(Client $client)
    {
        $this->debug('Client #' . $client->id() . ' disconnected');

        if ($error = socket_last_error()) {
            $this->debug(socket_strerror($error), true);
            socket_clear_error();
        }

        $this->broadcast($client->server(), sprintf('Client with ID %s left', $client->id()));
    }

    public function idle(Client $client)
    {
        $this->debug('Client #' . $client->id() . ' idles');
    }

    public function receive(Client $client, $opcode, $data)
    {
        if (intval($opcode) !== Server::TEXT) {
            $this->debug(sprintf('Client #%s sent a message with ignored opcode %s.', $client->id(), $opcode));
            return;
        }

        $this->debug(sprintf('Client #%s sent a message: %s', $client->id(), $data));
        $message = json_encode(['author' => $client->id(), 'message' => trim($data)]);
        $this->debug('Broadcast message to all clients: ' . $message);
        $this->broadcast($client->server(), $message);
    }

    public function send(Client $client, $opcode, $data)
    {
        $this->debug(sprintf('Client #%s will receive a message: %s', $client->id(), $data));
    }

    private function debug($message, $error = false)
    {
        $memory = human_filesize(memory_get_usage(true));
        return $error ? $this->error($message) : $this->info(now() . ' | ' . $memory . ' | ' . $message);
    }
}
