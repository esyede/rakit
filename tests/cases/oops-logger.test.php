<?php

defined('DS') or exit('No direct access.');

use System\Log;
use System\Hook;
use System\Email;
use System\Config;
use System\Foundation\Oops\Logger;
use System\Foundation\Oops\Debugger;

class OopsLoggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Temporary log directory.
     *
     * @var string
     */
    private $dir;

    /**
     * Configuration and state replaced by the tests.
     *
     * @var array
     */
    private $backup = [];

    /**
     * Emails handed to the test mailer.
     *
     * @var array
     */
    private $sent = [];

    /**
     * Log entries written during the test.
     *
     * @var array
     */
    private $logs = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->dir = sys_get_temp_dir().DS.'rakit-oops-logger-'.md5(uniqid('', true));
        mkdir($this->dir, 0777, true);

        $this->backup = [
            'log' => Config::get('log'),
            'email' => Config::get('email.driver'),
            'drivers' => Email::$drivers,
            'debugger' => Debugger::$email,
        ];

        $this->sent = [];
        $this->logs = [];

        Config::set('log', ['default' => 'null', 'channels' => ['null' => ['driver' => 'null']]]);
        Log::$drivers = [];

        $logs = &$this->logs;
        Hook::listen('rakit.log', function ($type, $message, $context) use (&$logs) {
            $logs[] = [$type, $message, $context];
        });
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Config::set('log', $this->backup['log']);
        Config::set('email.driver', $this->backup['email']);
        Email::$drivers = $this->backup['drivers'];
        Debugger::$email = $this->backup['debugger'];
        Log::$drivers = [];
        unset(Hook::$events['rakit.log']);

        foreach ((array) glob($this->dir.DS.'*') as $file) {
            is_file($file) && @unlink($file);
        }

        is_dir($this->dir) && @rmdir($this->dir);
    }

    /**
     * Make a logger whose mailer records the emails.
     *
     * @param string|null $email
     * @param string|null $directory
     *
     * @return Logger
     */
    private function logger($email = 'dev@example.com', $directory = null)
    {
        $sent = &$this->sent;
        $logger = new Logger(is_null($directory) ? $this->dir : $directory, $email);
        $logger->mailer = function ($message, $to) use (&$sent) {
            $sent[] = [$message, $to];
        };

        return $logger;
    }

    /**
     * Get the log entries of a level.
     *
     * @param string $level
     *
     * @return array
     */
    private function logged($level)
    {
        return array_values(array_filter($this->logs, function ($log) use ($level) {
            return $log[0] === $level;
        }));
    }

    /**
     * Test for Logger::notify() - sends once per snooze period.
     *
     * @group system
     */
    public function testNotifySendsOncePerSnoozePeriod()
    {
        $logger = $this->logger('dev@example.com, ops@example.com');
        $e = new \RuntimeException('First');

        $this->assertTrue($logger->notify($e));
        $this->assertFalse($logger->notify(new \RuntimeException('Second')));
        $this->assertCount(1, $this->sent);
        $this->assertSame($e, $this->sent[0][0]);
        $this->assertSame('dev@example.com, ops@example.com', $this->sent[0][1]);
        $this->assertFileExists($this->dir.DS.'email-sent');

        $logger->emailSnooze = 60;
        touch($this->dir.DS.'email-sent', time() - 120);

        $this->assertTrue($logger->notify(new \RuntimeException('Third')));
        $this->assertCount(2, $this->sent);
        $this->assertCount(0, $this->logged('warning'));
    }

    /**
     * Test for Logger::notify() - does nothing without an address.
     *
     * @group system
     */
    public function testNotifyWithoutAddressDoesNothing()
    {
        $this->assertFalse($this->logger('')->notify(new \RuntimeException('Nope')));
        $this->assertCount(0, $this->sent);
        $this->assertFileNotExists($this->dir.DS.'email-sent');
    }

    /**
     * Test for Logger::notify() - failures are logged, never thrown.
     *
     * @group system
     */
    public function testNotifyLogsFailures()
    {
        $throwing = $this->logger();
        $throwing->mailer = function () {
            throw new \Exception('SMTP is down');
        };

        $this->assertFalse($throwing->notify('Boom'));
        @unlink($this->dir.DS.'email-sent');

        $refusing = $this->logger();
        $refusing->mailer = function () {
            return false;
        };

        $this->assertFalse($refusing->notify('Boom'));
        $this->assertFalse($this->logger('dev@example.com', $this->dir.DS.'missing')->notify('Boom'));

        $warnings = $this->logged('warning');

        $this->assertCount(3, $warnings);
        $this->assertEquals('Unable to send the error email: SMTP is down', $warnings[0][1]);
        $this->assertEquals('Unable to send the error email: The mailer reported a failure.', $warnings[1][1]);
        $this->assertContains('The log directory is needed', $warnings[2][1]);
    }

    /**
     * Test for Logger::log() - only errors send an email.
     *
     * @group system
     */
    public function testLogSendsEmailForErrorsOnly()
    {
        $logger = $this->logger();

        $logger->log('Just a warning', Logger::WARNING);
        $this->assertCount(0, $this->sent);

        $logger->log(new \RuntimeException('Real problem'), Logger::EXCEPTION);
        $this->assertCount(1, $this->sent);
    }

    /**
     * Test for Logger::defaultMailer() - sends through the Email component.
     *
     * @group system
     */
    public function testDefaultMailerUsesEmailComponent()
    {
        Config::set('email.driver', 'log');
        Email::$drivers = [];

        // An email the application is still composing must not be touched.
        $draft = Email::driver()->to('user@example.com')->subject('Draft');

        $logger = new Logger($this->dir, 'dev@example.com');
        $logger->fromEmail = 'debugger@example.com';
        $result = $logger->defaultMailer(new \RuntimeException('Mail me'), 'dev@example.com, ops@example.com');

        $this->assertTrue($result);
        $this->assertSame($draft, Email::driver());

        $emails = array_values(array_filter($this->logs, function ($log) {
            return 0 === strpos($log[1], 'Email sent: ');
        }));

        $this->assertCount(1, $emails);
        $this->assertContains('PHP: An error occurred on the server', $emails[0][1]);
        $this->assertContains('dev@example.com', $emails[0][2]['to']);
        $this->assertContains('ops@example.com', $emails[0][2]['to']);
        $this->assertNotContains('user@example.com', $emails[0][2]['to']);
        $this->assertContains('debugger@example.com', $emails[0][2]['header']);
        $this->assertContains('Mail me', $emails[0][2]['body']);
        $this->assertContains('source: ', $emails[0][2]['body']);
    }

    /**
     * Test for Debugger::notify() - needs a configured address.
     *
     * @group system
     */
    public function testDebuggerNotifyNeedsAddress()
    {
        Debugger::$email = '';

        $this->assertFalse(Debugger::notify(new \RuntimeException('Nope')));
    }

    /**
     * A debug bar payload holds the request data, the session contents and every
     * query the request ran. The file carries its own guard, so a web server that
     * serves the storage directory hands out none of it.
     *
     * @group system
     */
    public function testDebugBarPayloadIsGuardedAgainstDirectAccess()
    {
        require_once path('system').'foundation'.DS.'oops'.DS.'storage.php';

        $dir = $this->dir.DS.'debugbar-probe';
        $storage = new \System\Foundation\Oops\Storage($dir, 5);
        $payload = ['meta' => ['ts' => 1.0, 'url' => '/probe'], 'session' => 'token-abcdef'];

        $this->assertTrue($storage->save('probe', $payload));

        $files = glob($dir.DS.'*');
        $this->assertNotEmpty($files);

        foreach ($files as $file) {
            $this->assertStringStartsWith(\System\Foundation\Oops\Storage::GUARD, file_get_contents($file));

            $output = (string) shell_exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($file).' 2>&1');

            $this->assertContains('No direct access.', $output);
            $this->assertNotContains('token-abcdef', $output);
        }

        // The guard must stay invisible to the debug bar itself.
        $this->assertEquals($payload, $storage->get('probe'));

        $recent = $storage->recent(5);
        $this->assertCount(1, $recent);
        $this->assertEquals('probe', $recent[0]['id']);

        foreach (glob($dir.DS.'*') as $file) {
            @unlink($file);
        }

        @rmdir($dir);
    }
}
