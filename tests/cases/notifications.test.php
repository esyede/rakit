<?php

defined('DS') or exit('No direct access.');

use System\Config;
use System\Database;
use System\Database\Schema;
use System\Email;
use System\Email\Mailable;
use System\Notification;

class NotificationsTest extends \PHPUnit_Framework_TestCase
{
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
        $this->backup = ['connection' => Config::get('database.default'), 'email' => Config::get('email.driver')];

        Config::set('database.default', 'sqlite');
        Database::$connections = [];
        NotificationsTestDriver::$sent = [];

        Email::extend('notifications-test', function () {
            return new NotificationsTestDriver(Config::get('email'));
        });
        Config::set('email.driver', 'notifications-test');
        Email::reset();

        Schema::create('notifications', function ($table) {
            $table->string('id', 36)->primary();
            $table->string('type', 191);
            $table->string('notifiable_type', 191);
            $table->string('notifiable_id', 36);
            $table->longtext('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('notify_users', function ($table) {
            $table->increments('id');
            $table->string('email');
        });

        Database::table('notify_users')->insert(['email' => 'budi@example.com']);

        $views = path('app').'views'.DS;
        file_put_contents($views.'notifytest.blade.php', 'Hello {{ $name }}, invoice {{ $number }}');
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Schema::drop_if_exists('notifications');
        Schema::drop_if_exists('notify_users');

        $template = path('app').'views'.DS.'notifytest.blade.php';
        is_file($template) && unlink($template);
        is_file($compiled = \System\Blade::compiled($template)) && unlink($compiled);

        Config::set('email.driver', $this->backup['email']);
        Config::set('database.default', $this->backup['connection']);
        Email::reset();
        Database::$connections = [];
    }

    /**
     * A mailable builds once, renders its view and sends with its recipients.
     *
     * @group system
     */
    public function testMailableSends()
    {
        $mail = (new NotifyInvoiceMail('Budi', 'INV-7'))->to('budi@example.com', 'Budi')->cc(['a@x.co', 'b@x.co']);

        $this->assertEquals('Hello Budi, invoice INV-7', trim($mail->render()));
        $this->assertTrue($mail->send());

        $this->assertCount(1, NotificationsTestDriver::$sent);
        $sent = NotificationsTestDriver::$sent[0];
        $this->assertEquals('Invoice INV-7', $sent['subject']);
        $this->assertContains('budi@example.com', $sent['to']);
        $this->assertContains('b@x.co', $sent['cc']);
        $this->assertContains('Hello Budi', $sent['body']);
        $this->assertSame(1, NotifyInvoiceMail::$builds);

        // The shared driver starts clean: the next message has its own recipients only.
        (new NotifyInvoiceMail('Sari', 'INV-8'))->to('sari@example.com')->send();
        $this->assertNotContains('budi@example.com', NotificationsTestDriver::$sent[1]['to']);
        $this->assertEmpty(NotificationsTestDriver::$sent[1]['cc']);
    }

    /**
     * A queued mailable is sent by the job worker.
     *
     * @group system
     */
    public function testQueuedMailable()
    {
        $payload = ['mailable' => serialize((new NotifyInvoiceMail('Budi', 'INV-9'))->to('budi@example.com'))];

        Mailable::deliver($payload);

        $this->assertCount(1, NotificationsTestDriver::$sent);
        $this->assertEquals('Invoice INV-9', NotificationsTestDriver::$sent[0]['subject']);

        $this->setExpectedException('Exception', 'does not hold a mailable');
        Mailable::deliver(['mailable' => serialize(new \stdClass())]);
    }

    /**
     * A notification goes out by mail to the notifiable's address and into the database.
     *
     * @group system
     */
    public function testNotificationChannels()
    {
        $user = NotifyUser::find(1);
        $user->notify(new NotifyInvoicePaid('INV-7'));

        $this->assertCount(1, NotificationsTestDriver::$sent);
        $this->assertContains('budi@example.com', NotificationsTestDriver::$sent[0]['to']);

        $stored = $user->notifications()->get();
        $this->assertCount(1, $stored);
        $this->assertEquals('NotifyInvoicePaid', $stored[0]->type);
        $this->assertEquals(['number' => 'INV-7'], json_decode($stored[0]->data, true));
        $this->assertCount(1, $user->unread_notifications()->get());

        $this->assertEquals(1, $user->mark_notifications_as_read());
        $this->assertCount(0, $user->unread_notifications()->get());
    }

    /**
     * Custom channels, and an error for unknown ones.
     *
     * @group system
     */
    public function testCustomChannels()
    {
        $seen = [];

        Notification::channel('notify-test', function ($notifiable, $notification) use (&$seen) {
            $seen[] = [$notifiable->email, $notification->number];
        });

        Notification::send(NotifyUser::all(), new NotifyCustom('X-1', ['notify-test']));
        $this->assertEquals([['budi@example.com', 'X-1']], $seen);

        $this->setExpectedException('Exception', 'Undefined notification channel');
        NotifyUser::find(1)->notify(new NotifyCustom('X-2', ['nowhere']));
    }
}

class NotificationsTestDriver extends \System\Email\Drivers\Driver
{
    public static $sent = [];

    protected function transmit()
    {
        static::$sent[] = [
            'to' => static::format($this->to),
            'cc' => static::format($this->cc),
            'subject' => $this->subject,
            'body' => $this->body,
        ];

        return true;
    }
}

class NotifyInvoiceMail extends Mailable
{
    public static $builds = 0;

    protected $name;

    protected $number;

    public function __construct($name, $number)
    {
        $this->name = $name;
        $this->number = $number;
    }

    public function build()
    {
        ++static::$builds;

        $this->subject('Invoice '.$this->number)
            ->view('notifytest', ['name' => $this->name])
            ->with('number', $this->number);
    }
}

class NotifyUser extends \System\Database\Facile\Model
{
    use \System\Notifiable;

    public static $table = 'notify_users';
    public static $timestamps = false;
}

class NotifyInvoicePaid extends Notification
{
    public $number;

    public function __construct($number)
    {
        $this->number = $number;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function to_mail($notifiable)
    {
        return new NotifyInvoiceMail('there', $this->number);
    }

    public function to_array($notifiable)
    {
        return ['number' => $this->number];
    }
}

class NotifyCustom extends Notification
{
    public $number;

    protected $channels;

    public function __construct($number, array $channels)
    {
        $this->number = $number;
        $this->channels = $channels;
    }

    public function via($notifiable)
    {
        return $this->channels;
    }
}
