<?php

namespace System;

defined('DS') or exit('No direct access.');

use System\Websocket\Server;

class Broadcast
{
    /**
     * The channel authorizations: pattern => closure($user, ...$parameters).
     *
     * @var array
     */
    protected static $channels = [];

    /**
     * The broadcasts sent while faking, NULL when sending for real.
     *
     * @var array|null
     */
    protected static $fakes;

    /**
     * Send an event to the subscribers of one or more channels, now.
     *
     *     Broadcast::event('private-orders.'.$order->id, 'OrderShipped', ['id' => $order->id]);
     *
     * Subscribers receive {"channel": "...", "event": "OrderShipped", "data": {...}}.
     *
     * @param string|array $channels
     * @param string       $event
     * @param mixed        $data     JSON-encodable
     *
     * @return bool
     */
    public static function event($channels, $event, $data = [])
    {
        $channels = array_values(array_map('strval', (array) $channels));
        $broadcast = ['channels' => $channels, 'event' => (string) $event, 'data' => $data];

        if (is_array(static::$fakes)) {
            static::$fakes[] = $broadcast;
            return true;
        }

        $config = array_merge(
            ['driver' => 'websocket', 'host' => '127.0.0.1', 'port' => 6001, 'timeout' => 3],
            (array) Config::get('websocket.broadcast', [])
        );

        switch ($config['driver']) {
            case 'websocket':
                return static::publish($config, json_encode($broadcast));

            case 'log':
                Log::info(sprintf('Broadcast %s to %s', $broadcast['event'], implode(', ', $channels)), $broadcast);
                return true;

            case 'null':
                return true;
        }

        throw new \Exception(sprintf('Unsupported broadcast driver: %s', $config['driver']));
    }

    /**
     * Send an event from a job, so the request does not wait for the WebSocket server.
     *
     * @param string|array $channels
     * @param string       $event
     * @param mixed        $data
     * @param string       $queue
     *
     * @return \System\Job\Pending
     */
    public static function queue($channels, $event, $data = [], $queue = 'default')
    {
        $data = ['channels' => (array) $channels, 'event' => (string) $event, 'data' => $data];
        return Job::dispatch('rakit-broadcast', $data)->on_queue($queue);
    }

    /**
     * Send a broadcast a queued job carries. Registered as the 'rakit-broadcast' job.
     *
     * @param array $payload
     */
    public static function deliver(array $payload)
    {
        static::event($payload['channels'], $payload['event'], $payload['data']);
    }

    /**
     * Authorize a private or presence channel: who may subscribe to it. The closure gets
     * the logged in user and the {parameters} of the pattern, and returns TRUE to allow.
     *
     *     Broadcast::channel('private-orders.{id}', function ($user, $id) {
     *         return (int) $user->id === (int) Order::find($id)->user_id;
     *     });
     *
     * @param string   $pattern
     * @param \Closure $callback
     */
    public static function channel($pattern, \Closure $callback)
    {
        static::$channels[(string) $pattern] = $callback;
    }

    /**
     * Get the subscription signature for a client, or NULL when the logged in user may
     * not join the channel (or nobody is logged in). Public channels need no signature.
     *
     * @param string $client_id
     * @param string $channel
     *
     * @return string|null
     */
    public static function auth($client_id, $channel)
    {
        $user = Auth::user();

        if (! is_string($client_id) || '' === $client_id || ! is_string($channel) || ! Server::is_private($channel) || ! $user) {
            return null;
        }

        foreach (static::$channels as $pattern => $callback) {
            $regex = '#^'.preg_replace('#\\\\\{[a-zA-Z_][a-zA-Z0-9_]*\\\\\}#', '([^.]+)', preg_quote($pattern, '#')).'$#';

            if (preg_match($regex, $channel, $matches)) {
                array_shift($matches);
                $allowed = call_user_func_array($callback, array_merge([$user], $matches));
                return $allowed ? Server::sign($client_id, $channel) : null;
            }
        }

        return null;
    }

    /**
     * Register the route browsers ask for a subscription signature:
     * POST broadcasting/auth with client_id and channel, answered {"auth": "..."} or 403.
     *
     * @param string $middleware the 'before' middleware of the route
     */
    public static function routes($middleware = 'auth')
    {
        Routing\Route::post('broadcasting/auth', ['before' => $middleware, function () {
            $auth = Broadcast::auth(Input::get('client_id'), Input::get('channel'));
            return is_null($auth) ? Response::json(['message' => 'Forbidden'], 403) : Response::json(['auth' => $auth]);
        }]);
    }

    /**
     * Record broadcasts instead of sending them, for the assert_*() methods. FALSE sends
     * for real again.
     *
     * @param bool $fake
     */
    public static function fake($fake = true)
    {
        static::$fakes = $fake ? [] : null;
    }

    /**
     * Get the broadcasts sent while faking: arrays of channels, event, data.
     *
     * @param string|null $event
     *
     * @return array
     */
    public static function recorded($event = null)
    {
        return array_values(array_filter((array) static::$fakes, function ($broadcast) use ($event) {
            return is_null($event) || $broadcast['event'] === $event;
        }));
    }

    /**
     * Assert that an event was broadcast, optionally one the closure accepts.
     *
     * @param string        $event
     * @param \Closure|null $filter receives the broadcast array
     */
    public static function assert_broadcast($event, $filter = null)
    {
        $found = array_filter(static::recorded($event), function ($broadcast) use ($filter) {
            return ! ($filter instanceof \Closure) || $filter($broadcast);
        });

        static::check(count($found) > 0, sprintf('The [%s] event was not broadcast.', $event));
    }

    /**
     * Assert that an event was not broadcast.
     *
     * @param string $event
     */
    public static function assert_not_broadcast($event)
    {
        static::check(0 === count(static::recorded($event)), sprintf('The [%s] event was broadcast.', $event));
    }

    /**
     * Assert that nothing was broadcast.
     */
    public static function assert_nothing_broadcast()
    {
        static::check(0 === count(static::recorded()), 'Events were broadcast.');
    }

    /**
     * Fail a test: through PHPUnit when it runs, else with an exception.
     *
     * @param bool   $condition
     * @param string $message
     */
    protected static function check($condition, $message)
    {
        if (class_exists('PHPUnit\Framework\Assert')) {
            \PHPUnit\Framework\Assert::assertTrue((bool) $condition, $message);
        } elseif (class_exists('PHPUnit_Framework_Assert')) {
            \PHPUnit_Framework_Assert::assertTrue((bool) $condition, $message);
        } elseif (! $condition) {
            throw new \Exception($message);
        }
    }

    /**
     * Open a WebSocket connection to the server, send one signed message, and close.
     *
     * @param array  $config
     * @param string $payload
     *
     * @return bool
     */
    protected static function publish(array $config, $payload)
    {
        $address = $config['host'].':'.(int) $config['port'];
        $socket = @stream_socket_client('tcp://'.$address, $errno, $error, (float) $config['timeout']);

        if (! $socket) {
            throw new \RuntimeException(sprintf('Unable to reach the WebSocket server at %s: %s', $address, $error));
        }

        stream_set_timeout($socket, (int) ceil($config['timeout']));

        fwrite($socket, 'GET '.Server::PUBLISHER.' HTTP/1.1'.CRLF
            .'Host: '.$address.CRLF
            .'Upgrade: websocket'.CRLF
            .'Connection: Upgrade'.CRLF
            .'Sec-WebSocket-Key: '.base64_encode(Str::random(16)).CRLF
            .'Sec-WebSocket-Version: 13'.CRLF.CRLF);

        $response = '';

        while (false === strpos($response, CRLF.CRLF) && ! feof($socket) && strlen($response) < 8192) {
            $line = fgets($socket, 1024);

            if (false === $line) {
                break;
            }

            $response .= $line;
        }

        if (! preg_match('#^HTTP/1\.1 101 #', $response)) {
            fclose($socket);
            throw new \RuntimeException(
                sprintf('The WebSocket server at %s refused the connection: %s', $address, strtok($response, CRLF))
            );
        }

        $time = time();
        fwrite($socket, static::frame(json_encode([
            'event' => 'publish',
            'payload' => $payload,
            'time' => $time,
            'signature' => Server::publish_signature($time, $payload),
        ]), 0x1));
        fwrite($socket, static::frame('', 0x8));
        fclose($socket);

        return true;
    }

    /**
     * Build a masked client frame.
     *
     * @param string $data
     * @param int    $opcode
     *
     * @return string
     */
    protected static function frame($data, $opcode)
    {
        $length = strlen($data);
        $head = chr(0x80 | $opcode);

        if ($length < 126) {
            $head .= chr(0x80 | $length);
        } elseif ($length < 65536) {
            $head .= chr(0x80 | 126).pack('n', $length);
        } else {
            $head .= chr(0x80 | 127).pack('NN', 0, $length);
        }

        $mask = Str::random(4);
        $masked = '';

        for ($i = 0; $i < $length; ++$i) {
            $masked .= $data[$i] ^ $mask[$i % 4];
        }

        return $head.$mask.$masked;
    }
}
