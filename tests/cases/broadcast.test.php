<?php

defined('DS') or exit('No direct access.');

use System\Broadcast;
use System\Config;
use System\Routing\Router;
use System\Websocket\Server;

class BroadcastTest extends \PHPUnit_Framework_TestCase
{
    use \System\Testing\Http;

    /**
     * Framework state replaced by the tests.
     *
     * @var array
     */
    protected $backup = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->backup = [
            'driver' => Config::get('session.driver'),
            'foundation' => \System\Request::$foundation,
            'route' => \System\Request::$route,
            'session' => \System\Session::$instance,
        ];

        Config::set('session.driver', 'file');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Broadcast::fake(false);
        unset(Router::$routes['POST']['broadcasting/auth']);
        \System\Auth::$drivers = [];

        Config::set('session.driver', $this->backup['driver']);
        \System\Cookie::flush();
        \System\Session::$instance = $this->backup['session'];
        \System\Request::$route = $this->backup['route'];
        \System\Request::$foundation = $this->backup['foundation'];
        \System\Request::reset_foundation();
    }

    /**
     * A server with clients attached, recording what each is sent.
     *
     * @param array $clients id => channels
     * @param array $sent
     *
     * @return array
     */
    protected function clients(array $clients, array &$sent)
    {
        $server = new Server('tcp://127.0.0.1:0');
        $users = [];

        foreach ($clients as $id => $channels) {
            $client = new \System\Websocket\Client($id, null);
            $client->of($server);
            $client->channels = $channels;
            $users[$id] = $client;
        }

        $property = new \ReflectionProperty('System\Websocket\Server', 'users');
        /** @disregard */
        PHP_VERSION_ID < 80100 && $property->setAccessible(true);
        $property->setValue($server, $users);

        $server->on('send', function ($client, $opcode, $data) use (&$sent) {
            $sent[$client->id()][] = $data;
        });

        return $users;
    }

    /**
     * Make a signed publish message.
     *
     * @param array    $broadcast
     * @param int|null $time
     *
     * @return string
     */
    protected function publish(array $broadcast, $time = null)
    {
        $time = is_null($time) ? time() : $time;
        $payload = json_encode($broadcast);

        return json_encode([
            'event' => 'publish',
            'payload' => $payload,
            'time' => $time,
            'signature' => Server::publish_signature($time, $payload),
        ]);
    }

    /**
     * The server delivers signed broadcasts to the channels' subscribers only.
     *
     * @group system
     */
    public function testServerDeliversSignedBroadcasts()
    {
        $sent = [];
        $users = $this->clients(['app' => [], 'a' => ['orders'], 'b' => ['private-user.1'], 'c' => ['news']], $sent);
        $command = new \System\Console\Commands\Websocket();

        $command->receive($users['app'], Server::TEXT, $this->publish([
            'channels' => ['orders', 'private-user.1'],
            'event' => 'OrderShipped',
            'data' => ['id' => 7],
        ]));

        $this->assertEquals([json_encode(['channel' => 'orders', 'event' => 'OrderShipped', 'data' => ['id' => 7]])], $sent['a']);
        $this->assertEquals('private-user.1', json_decode($sent['b'][0])->channel);
        $this->assertArrayNotHasKey('c', $sent);

        // Forged, tampered and stale messages are refused.
        $sent = [];
        $forged = json_decode($this->publish(['channels' => ['orders'], 'event' => 'X']), true);
        $forged['payload'] = json_encode(['channels' => ['orders'], 'event' => 'Hijacked']);
        $command->receive($users['app'], Server::TEXT, json_encode($forged));
        $command->receive($users['app'], Server::TEXT, $this->publish(['channels' => ['orders'], 'event' => 'Old'], time() - 120));

        $this->assertArrayNotHasKey('a', $sent);
        $this->assertCount(2, $sent['app']);
        $this->assertContains('Invalid broadcast signature', $sent['app'][0]);
    }

    /**
     * The application's connection is not a visitor: no notices, not in presence.
     *
     * @group system
     */
    public function testPublisherIsHidden()
    {
        $sent = [];
        $users = $this->clients(['app' => [], 'a' => []], $sent);
        $users['app']->uri = Server::PUBLISHER;
        $command = new \System\Console\Commands\Websocket();

        $command->connect($users['app']);
        $command->disconnect($users['app']);
        $this->assertEmpty($sent);

        $command->receive($users['a'], Server::TEXT, json_encode(['command' => 'presence']));
        $presence = json_decode($sent['a'][0], true);
        $this->assertEquals(['a'], array_map(function ($user) {
            return $user['id'];
        }, $presence['users']));

        $this->assertTrue(Server::is_publisher('/rakit-broadcast?x=1'));
        $this->assertFalse(Server::is_publisher('/rakit-broadcast/other'));
    }

    /**
     * Channel authorization: patterns with parameters, the logged in user, the auth route.
     *
     * @group system
     */
    public function testChannelAuthorization()
    {
        Broadcast::channel('private-orders.{id}', function ($user, $id) {
            return 5 === (int) $user->id && '42' === $id;
        });

        Broadcast::routes();

        $this->post('broadcasting/auth', ['client_id' => 'abc', 'channel' => 'private-orders.42'])->assert_unauthorized();

        $this->acting_as((object) ['id' => 5, 'name' => 'Sari']);

        $this->post('broadcasting/auth', ['client_id' => 'abc', 'channel' => 'private-orders.42'])
            ->assert_ok()
            ->assert_json(['auth' => Server::sign('abc', 'private-orders.42')]);

        $this->post('broadcasting/auth', ['client_id' => 'abc', 'channel' => 'private-orders.43'])->assert_forbidden();
        $this->post('broadcasting/auth', ['client_id' => 'abc', 'channel' => 'private-unknown.1'])->assert_forbidden();
        $this->post('broadcasting/auth', ['client_id' => 'abc', 'channel' => 'orders'])->assert_forbidden();
    }

    /**
     * fake(), queue() and the notification channel.
     *
     * @group system
     */
    public function testFakeQueueAndNotifications()
    {
        Broadcast::fake();

        Broadcast::event('orders', 'OrderShipped', ['id' => 1]);
        Broadcast::deliver(['channels' => ['news'], 'event' => 'Published', 'data' => []]);

        Broadcast::assert_broadcast('OrderShipped');
        Broadcast::assert_broadcast('OrderShipped', function ($broadcast) {
            return ['orders'] === $broadcast['channels'] && 1 === $broadcast['data']['id'];
        });
        Broadcast::assert_broadcast('Published');
        Broadcast::assert_not_broadcast('Deleted');

        $user = new BroadcastTestUser(['id' => 9], true);
        $user->notify(new BroadcastTestNotification());

        Broadcast::assert_broadcast('BroadcastTestNotification', function ($broadcast) {
            return ['private-broadcasttestuser.9'] === $broadcast['channels'] && ['text' => 'hi'] === $broadcast['data'];
        });

        Broadcast::fake();
        Broadcast::assert_nothing_broadcast();
    }

    /**
     * The websocket driver reports an unreachable server.
     *
     * @group system
     */
    public function testUnreachableServer()
    {
        Config::set('websocket.broadcast', ['driver' => 'websocket', 'host' => '127.0.0.1', 'port' => 1, 'timeout' => 1]);

        try {
            $this->setExpectedException('RuntimeException', 'Unable to reach the WebSocket server at 127.0.0.1:1');
            Broadcast::event('orders', 'X');
        } catch (\Exception $e) {
            Config::set('websocket.broadcast', ['driver' => 'websocket', 'host' => '127.0.0.1', 'port' => 6001, 'timeout' => 3]);
            throw $e;
        }
    }
}

class BroadcastTestUser extends \System\Database\Facile\Model
{
    use \System\Notifiable;
}

class BroadcastTestNotification extends \System\Notification
{
    public function via($notifiable)
    {
        return ['broadcast'];
    }

    public function to_broadcast($notifiable)
    {
        return ['text' => 'hi'];
    }
}
