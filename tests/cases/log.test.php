<?php

defined('DS') or exit('No direct access.');

use System\Log;
use System\Hook;
use System\Config;
use System\Log\Formatter;

class LogTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Log files created during tests, to be cleaned up.
     *
     * @var array
     */
    private $createdFiles = [];

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->createdFiles = [];
        Log::channel(null);
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Log::channel(null);

        foreach ($this->createdFiles as $file) {
            is_file($file) && @unlink($file);
        }

        unset(Hook::$events['rakit.log']);
    }

    /**
     * Track a log file for cleanup.
     *
     * @param string $file
     */
    private function trackFile($file)
    {
        $this->createdFiles[] = $file;
    }

    /**
     * Get the expected default log file path.
     *
     * @return string
     */
    private function defaultLogFile()
    {
        $date = date('Y-m-d');
        $name = Config::get('application.name');
        $slug = $name ? \System\Str::slug($name) : 'rakit';
        $file = $slug . '_' . $date . '.log.php';
        return path('storage') . 'logs' . DS . $file;
    }

    /**
     * Test for Log::channel() - sets channel name.
     *
     * @group system
     */
    public function testChannelSetsChannelName()
    {
        Log::channel('my-channel');

        $ref = new \ReflectionClass('System\Log');
        $prop = $ref->getProperty('channel');
        PHP_VERSION_ID < 80100 && $prop->setAccessible(true);
        $value = $prop->getValue(null);

        $this->assertEquals('my-channel', $value);
    }

    /**
     * Test for Log::channel() - null resets channel.
     *
     * @group system
     */
    public function testChannelNullResetsChannel()
    {
        Log::channel('test');
        Log::channel(null);

        $ref = new \ReflectionClass('System\Log');
        $prop = $ref->getProperty('channel');
        PHP_VERSION_ID < 80100 && $prop->setAccessible(true);
        $value = $prop->getValue(null);

        $this->assertNull($value);
    }

    /**
     * Test for Log::channel() - non-string is ignored (set to null).
     *
     * @group system
     */
    public function testChannelIgnoresNonString()
    {
        Log::channel(123);

        $ref = new \ReflectionClass('System\Log');
        $prop = $ref->getProperty('channel');
        PHP_VERSION_ID < 80100 && $prop->setAccessible(true);
        $value = $prop->getValue(null);

        $this->assertNull($value);
    }

    /**
     * Test for Log::info() - writes log via hook.
     *
     * @group system
     */
    public function testInfoWritesLogViaHook()
    {
        $captured = [];
        Hook::listen('rakit.log', function ($type, $message, $context) use (&$captured) {
            $captured = compact('type', 'message', 'context');
        });

        Log::info('Test info message');

        $this->assertEquals('info', $captured['type']);
        $this->assertEquals('Test info message', $captured['message']);
        $this->assertEquals([], $captured['context']);
    }

    /**
     * Test for Log::emergency() - fires hook with correct type.
     *
     * @group system
     */
    public function testEmergencyFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::emergency('Emergency!');
        $this->assertEquals('emergency', $type);
    }

    /**
     * Test for Log::alert() - fires hook with correct type.
     *
     * @group system
     */
    public function testAlertFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::alert('Alert!');
        $this->assertEquals('alert', $type);
    }

    /**
     * Test for Log::critical() - fires hook with correct type.
     *
     * @group system
     */
    public function testCriticalFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::critical('Critical!');
        $this->assertEquals('critical', $type);
    }

    /**
     * Test for Log::error() - fires hook with correct type.
     *
     * @group system
     */
    public function testErrorFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::error('Error!');
        $this->assertEquals('error', $type);
    }

    /**
     * Test for Log::warning() - fires hook with correct type.
     *
     * @group system
     */
    public function testWarningFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::warning('Warning!');
        $this->assertEquals('warning', $type);
    }

    /**
     * Test for Log::notice() - fires hook with correct type.
     *
     * @group system
     */
    public function testNoticeFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::notice('Notice!');
        $this->assertEquals('notice', $type);
    }

    /**
     * Test for Log::debug() - fires hook with correct type.
     *
     * @group system
     */
    public function testDebugFiresHookWithCorrectType()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        Log::debug('Debug!');
        $this->assertEquals('debug', $type);
    }

    /**
     * Test for Log::info() - context is passed via hook.
     *
     * @group system
     */
    public function testContextIsPassedViaHook()
    {
        $captured = [];
        Hook::listen('rakit.log', function ($type, $message, $context) use (&$captured) {
            $captured = $context;
        });

        Log::info('Message', ['key' => 'value', 'num' => 42]);
        $this->assertEquals(['key' => 'value', 'num' => 42], $captured);
    }

    /**
     * Test for Log::write() - throws Exception when message is not a string.
     *
     * @group system
     * @expectedException Exception
     */
    public function testWriteThrowsExceptionWhenMessageIsNotString()
    {
        Log::info(123);
    }

    /**
     * Test for Log::write() - throws Exception when array passed as message.
     *
     * @group system
     * @expectedException Exception
     */
    public function testWriteThrowsExceptionWhenArrayPassedAsMessage()
    {
        Log::error(['error message']);
    }

    /**
     * Test for Log::write() - actually writes a file to storage.
     *
     * @group system
     */
    public function testWriteCreatesLogFile()
    {
        Log::channel('phpunit-log-write-test');
        $date = \System\Carbon::now()->format('Y-m-d');
        $logFile = path('storage') . 'logs' . DS . 'phpunit-log-write-test_' . $date . '.log.php';
        $this->trackFile($logFile);

        Log::info('Test log write', []);

        $this->assertTrue(is_file($logFile));

        $content = file_get_contents($logFile);
        $this->assertContains('Test log write', $content);
        $this->assertContains('INFO', $content);
        $this->assertStringEndsWith(PHP_EOL, $content);

        Log::channel(null);
    }

    /**
     * Test for Log::channel() - uses channel name in log file.
     *
     * @group system
     */
    public function testChannelNameUsedInLogFile()
    {
        Log::channel('phpunit-channel-test');

        $date = \System\Carbon::now()->format('Y-m-d');
        $logFile = path('storage') . 'logs' . DS . 'phpunit-channel-test_' . $date . '.log.php';
        $this->trackFile($logFile);

        Log::info('Channel test message');

        $this->assertTrue(is_file($logFile));

        $content = file_get_contents($logFile);
        $this->assertContains('Channel test message', $content);

        Log::channel(null);
    }

    /**
     * Build a log record for the formatter.
     *
     * @param string $level
     * @param string $message
     * @param array  $context
     *
     * @return array
     */
    private function record($level, $message, array $context = [])
    {
        return [
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'channel' => 'rakit',
            'env' => 'local',
            'datetime' => \System\Carbon::now(),
        ];
    }

    /**
     * Test for Formatter::line() - output contains level and message.
     *
     * @group system
     */
    public function testFormatOutputContainsLevelAndMessage()
    {
        $result = Formatter::line($this->record('info', 'Test message'));

        $this->assertContains('INFO', $result);
        $this->assertContains('Test message', $result);
    }

    /**
     * Test for Formatter::line() - context is included when not empty.
     *
     * @group system
     */
    public function testFormatIncludesContextWhenNotEmpty()
    {
        $result = Formatter::line($this->record('debug', 'Msg', ['foo' => 'bar']));

        $this->assertContains('foo', $result);
        $this->assertContains('bar', $result);
    }

    /**
     * Test for Formatter::line() - timestamp can be omitted.
     *
     * @group system
     */
    public function testFormatLineTimestampIsOptional()
    {
        $record = $this->record('info', 'Test');

        $this->assertRegExp('/^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] local\.INFO: Test$/', Formatter::line($record));
        $this->assertEquals('local.INFO: Test', Formatter::line($record, false));
    }

    /**
     * Test for Formatter::context() - returns empty string for empty context.
     *
     * @group system
     */
    public function testFormatContextReturnsEmptyStringForEmptyContext()
    {
        $this->assertEquals('', Formatter::context([]));
    }

    /**
     * Test for Formatter::context() - returns JSON for non-empty context.
     *
     * @group system
     */
    public function testFormatContextReturnsJsonForNonEmptyContext()
    {
        $decoded = json_decode(Formatter::context(['key' => 'value']), true);
        $this->assertEquals('value', $decoded['key']);
    }

    /**
     * Test for Formatter::value() - returns string, int, bool as-is.
     *
     * @group system
     */
    public function testFormatValueReturnsPrimitivesAsIs()
    {
        $this->assertEquals('hello', Formatter::value('hello'));
        $this->assertEquals(42, Formatter::value(42));
        $this->assertTrue(Formatter::value(true));
        $this->assertFalse(Formatter::value(false));
    }

    /**
     * Test for Formatter::value() - formats objects.
     *
     * @group system
     */
    public function testFormatValueFormatsObject()
    {
        $obj = new \stdClass();
        $obj->name = 'test';

        $this->assertContains('test', (string) json_encode(Formatter::value($obj)));
    }

    /**
     * Test for Formatter::value() - formats arrays.
     *
     * @group system
     */
    public function testFormatValueFormatsArrays()
    {
        $result = Formatter::value(['a' => 1, 'b' => 2]);

        $this->assertInternalType('array', $result);
        $this->assertEquals(1, $result['a']);
        $this->assertEquals(2, $result['b']);
    }

    /**
     * Test for Formatter::value() - formats resources.
     *
     * @group system
     */
    public function testFormatValueFormatsResources()
    {
        $resource = fopen('php://temp', 'r');
        $result = Formatter::value($resource);
        fclose($resource);

        $this->assertContains('[resource]', $result);
    }

    /**
     * Test for Formatter::value() - formats exceptions.
     *
     * @group system
     */
    public function testFormatValueFormatsException()
    {
        $result = Formatter::value(new \RuntimeException('Something went wrong', 42));

        $this->assertInternalType('string', $result);
        $this->assertContains('RuntimeException', $result);
        $this->assertContains('Something went wrong', $result);
    }

    /**
     * Test for Formatter::value() - handles circular object references.
     *
     * @group system
     */
    public function testFormatValueHandlesCircularObjectReference()
    {
        $obj = new \stdClass();

        $id = function_exists('spl_object_id') ? spl_object_id($obj) : spl_object_hash($obj);
        $objects = [$id => true];
        $arrays = [];

        $this->assertContains('[circular]', Formatter::value($obj, $objects, $arrays));
    }

    /**
     * Test for Formatter::value() - handles circular array references.
     *
     * @group system
     */
    public function testFormatValueHandlesCircularArrayReference()
    {
        $arr = ['a' => 1];
        $objects = [];
        $arrays = [md5(serialize($arr)) => true];

        $this->assertEquals('[array] [circular]', Formatter::value($arr, $objects, $arrays));
    }

    /**
     * Test for Formatter::exception() - formats exception details.
     *
     * @group system
     */
    public function testFormatExceptionFormatsDetails()
    {
        $result = Formatter::exception(new \InvalidArgumentException('Bad argument', 5));

        $this->assertInternalType('string', $result);
        $this->assertContains('InvalidArgumentException', $result);
        $this->assertContains('Bad argument', $result);
        $this->assertContains('code: 5', $result);
    }

    /**
     * Test for Log::write() - passes context via hook.
     *
     * @group system
     */
    public function testWritePassesContextViaHook()
    {
        $captured = null;
        Hook::listen('rakit.log', function ($type, $message, $context) use (&$captured) {
            $captured = $context;
        });

        $context = ['user' => 'admin', 'action' => 'login'];
        Log::warning('User action', $context);

        $this->assertEquals($context, $captured);
    }

    /**
     * Test for Log::write() - log file contains formatted output with context.
     *
     * @group system
     */
    public function testWriteLogFileContainsContextData()
    {
        Log::channel('phpunit-log-ctx-test');
        $date = \System\Carbon::now()->format('Y-m-d');
        $logFile = path('storage') . 'logs' . DS . 'phpunit-log-ctx-test_' . $date . '.log.php';
        $this->trackFile($logFile);

        Log::error('Error with context', ['error_code' => 500, 'detail' => 'Not found']);

        $this->assertTrue(is_file($logFile));

        $content = file_get_contents($logFile);
        $this->assertContains('error_code', $content);
        $this->assertContains('500', $content);

        Log::channel(null);
    }
}
