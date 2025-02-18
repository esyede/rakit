<?php

namespace System\Console\Commands;

defined('DS') or exit('No direct access.');

use System\Websocket\Server;
use System\Websocket\Agent;

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
        $agents = $server->agents();

        foreach ($agents as $agent) {
            $agent->send(Server::TEXT, $message);
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

    public function connect(Agent $agent)
    {
        $this->debug('Agent #' . $agent->id() . ' connected');
        $this->broadcast($agent->server(), sprintf('Client with ID %s joined', $agent->id()));
    }

    public function disconnect(Agent $agent)
    {
        $this->debug('Agent #' . $agent->id() . ' disconnected');

        if ($error = socket_last_error()) {
            $this->debug(socket_strerror($error), true);
            socket_clear_error();
        }

        $this->broadcast($agent->server(), sprintf('Client with ID %s left', $agent->id()));
    }

    public function idle(Agent $agent)
    {
        $this->debug('Agent #' . $agent->id() . ' idles');
    }

    public function receive(Agent $agent, $opcode, $data)
    {
        if (intval($opcode) !== Server::TEXT) {
            $this->debug(sprintf('Agent #%s sent a message with ignored opcode %s.', $agent->id(), $opcode));
            return;
        }

        $this->debug(sprintf('Agent #%s sent a message: %s', $agent->id(), $data));
        $message = json_encode(['author' => $agent->id(), 'message' => trim($data)]);
        $this->debug('Broadcast message to all clients: ' . $message);
        $this->broadcast($agent->server(), $message);
    }

    public function send(Agent $agent, $opcode, $data)
    {
        $this->debug(sprintf('Agent #%s will receive a message: %s', $agent->id(), $data));
    }

    private function debug($message, $error = false)
    {
        $memory = human_filesize(memory_get_usage(true));
        echo $error ? $this->error($message) : $this->info(now() . ' | ' . $memory . ' | ' . $message);
    }
}
