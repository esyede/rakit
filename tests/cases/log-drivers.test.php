<?php

defined('DS') or exit('No direct access.');

use System\Log;
use System\Hook;
use System\Config;
use System\Carbon;

class LogDriversTestMemoryDriver extends \System\Log\Drivers\Driver
{
    /**
     * Records written by every instance.
     *
     * @var array
     */
    public static $records = [];

    /**
     * Keep the record in memory.
     *
     * @param array $record
     *
     * @return bool
     */
    protected function write(array $record)
    {
        static::$records[] = ['channel' => $this->channel, 'config' => $this->config, 'record' => $record];
        return true;
    }
}

class LogDriversTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Original log config.
     *
     * @var mixed
     */
    private $config;

    /**
     * Temporary directory for log files.
     *
     * @var string
     */
    private $dir;

    /**
     * Original content of the emergency log file, false when absent.
     *
     * @var string|false
     */
    private $emergency;

    /**
     * Setup.
     */
    public function setUp()
    {
        $this->config = Config::get('log');
        $this->dir = sys_get_temp_dir().DS.'rakit-log-'.md5(uniqid('', true));
        mkdir($this->dir, 0777, true);

        $file = $this->emergencyFile();
        $this->emergency = is_file($file) ? file_get_contents($file) : false;

        Log::channel(null);
        Log::$drivers = [];
        Log::$registrar = [];
        LogDriversTestMemoryDriver::$records = [];
    }

    /**
     * Tear down.
     */
    public function tearDown()
    {
        Log::channel(null);
        Log::$drivers = [];
        Log::$registrar = [];
        Config::set('log', $this->config);
        Carbon::setNow(null);
        unset(Hook::$events['rakit.log']);

        foreach ((array) glob($this->dir.DS.'*') as $file) {
            is_file($file) && @unlink($file);
        }

        is_dir($this->dir) && @rmdir($this->dir);

        $file = $this->emergencyFile();

        if (false === $this->emergency) {
            is_file($file) && @unlink($file);
        } else {
            file_put_contents($file, $this->emergency);
        }
    }

    /**
     * Get the emergency log file path.
     *
     * @return string
     */
    private function emergencyFile()
    {
        return path('storage').'logs'.DS.'rakit.log.php';
    }

    /**
     * Get what the test wrote into the emergency log file.
     *
     * @return string
     */
    private function emergencyContent()
    {
        $file = $this->emergencyFile();
        $content = is_file($file) ? file_get_contents($file) : '';

        return (false === $this->emergency) ? $content : substr($content, strlen($this->emergency));
    }

    /**
     * Configure the log channels.
     *
     * @param string $default
     * @param array  $channels
     */
    private function channels($default, array $channels)
    {
        Config::set('log', ['default' => $default, 'channels' => $channels]);
        Log::$drivers = [];
    }

    /**
     * Read the JSON lines of a file.
     *
     * @param string $file
     *
     * @return array
     */
    private function jsonLines($file)
    {
        $lines = array_filter(explode(PHP_EOL, (string) @file_get_contents($file)), 'strlen');
        return array_values(array_map(function ($line) {
            return json_decode($line, true);
        }, $lines));
    }

    /**
     * Test for Log::driver() - uses a daily driver when the config is absent.
     *
     * @group system
     */
    public function testDefaultsToDailyDriverWithoutConfig()
    {
        Config::set('log', ['default' => null, 'channels' => null]);

        $this->assertInstanceOf('System\Log\Drivers\Daily', Log::driver());
    }

    /**
     * Test for Log::driver() - resolves every built-in driver.
     *
     * @group system
     */
    public function testResolvesBuiltInDrivers()
    {
        $this->channels('daily', [
            'daily' => ['driver' => 'daily'],
            'single' => ['driver' => 'single'],
            'stream' => ['driver' => 'stream'],
            'syslog' => ['driver' => 'syslog'],
            'errorlog' => ['driver' => 'errorlog'],
            'stack' => ['driver' => 'stack', 'channels' => []],
            'null' => ['driver' => 'null'],
        ]);

        $this->assertInstanceOf('System\Log\Drivers\Daily', Log::driver('daily'));
        $this->assertInstanceOf('System\Log\Drivers\Single', Log::driver('single'));
        $this->assertInstanceOf('System\Log\Drivers\Stream', Log::driver('stream'));
        $this->assertInstanceOf('System\Log\Drivers\Syslog', Log::driver('syslog'));
        $this->assertInstanceOf('System\Log\Drivers\Errorlog', Log::driver('errorlog'));
        $this->assertInstanceOf('System\Log\Drivers\Stack', Log::driver('stack'));
        $this->assertInstanceOf('System\Log\Drivers\Discard', Log::driver('null'));
        $this->assertSame(Log::driver('daily'), Log::driver('daily'));
    }

    /**
     * Test for the stream driver - writes JSON lines.
     *
     * @group system
     */
    public function testStreamDriverWritesJsonLines()
    {
        $file = $this->dir.DS.'stream.log';
        $this->channels('stderr', ['stderr' => ['driver' => 'stream', 'stream' => $file, 'format' => 'json']]);

        Log::info('First', ['user' => ['id' => 1]]);
        Log::error('Second');

        $lines = $this->jsonLines($file);

        $this->assertCount(2, $lines);
        $this->assertEquals('INFO', $lines[0]['level']);
        $this->assertEquals('First', $lines[0]['message']);
        $this->assertEquals(['user' => ['id' => 1]], $lines[0]['context']);
        $this->assertEquals('Rakit', $lines[0]['channel']);
        $this->assertContains($lines[0]['env'], ['local', 'production', 'unknown']);
        $this->assertRegExp('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}[+-]\d{2}:\d{2}$/', $lines[0]['datetime']);
        $this->assertEquals('ERROR', $lines[1]['level']);
        $this->assertSame([], $lines[1]['context']);
    }

    /**
     * Test for the JSON format - keeps entries on one line and encodable.
     *
     * @group system
     */
    public function testJsonFormatNormalizesContext()
    {
        $file = $this->dir.DS.'json.log';
        $this->channels('json', ['json' => ['driver' => 'stream', 'stream' => $file, 'format' => 'json']]);

        $nested = ['level' => 0];
        $pointer = &$nested;

        for ($i = 1; $i < 15; ++$i) {
            $pointer['child'] = ['level' => $i];
            $pointer = &$pointer['child'];
        }

        unset($pointer);

        $resource = fopen('php://memory', 'r');
        $previous = new \LogicException('root cause');

        Log::error("Invalid \xB1 byte", [
            'exception' => new \RuntimeException("multi\nline", 3, $previous),
            'infinite' => INF,
            'resource' => $resource,
            'date' => new \DateTime('2020-01-02 03:04:05'),
            'object' => (object) ['name' => 'rakit'],
            'nested' => $nested,
        ]);

        fclose($resource);

        $content = file_get_contents($file);
        $lines = $this->jsonLines($file);

        $this->assertEquals(1, substr_count($content, PHP_EOL));
        $this->assertCount(1, $lines);
        $this->assertStringStartsWith('Invalid ', $lines[0]['message']);

        $context = $lines[0]['context'];

        $this->assertEquals('RuntimeException', $context['exception']['class']);
        $this->assertEquals("multi\nline", $context['exception']['message']);
        $this->assertEquals(3, $context['exception']['code']);
        $this->assertInternalType('array', $context['exception']['trace']);
        $this->assertEquals('LogicException', $context['exception']['previous']['class']);
        $this->assertEquals('INF', $context['infinite']);
        $this->assertContains('[resource]', $context['resource']);
        $this->assertStringStartsWith('2020-01-02T03:04:05', $context['date']);
        $this->assertEquals(['stdClass' => ['name' => 'rakit']], $context['object']);
        $this->assertContains('[max depth reached]', json_encode($context['nested']));
    }

    /**
     * Test for the "level" option - skips lower levels.
     *
     * @group system
     */
    public function testLevelOptionSkipsLowerLevels()
    {
        $file = $this->dir.DS.'level.log';
        $this->channels('main', ['main' => ['driver' => 'single', 'path' => $file, 'level' => 'warning']]);

        Log::info('Skipped');
        Log::debug('Skipped too');
        Log::warning('Written');
        Log::emergency('Written too');

        $content = file_get_contents($file);

        $this->assertNotContains('Skipped', $content);
        $this->assertContains('WARNING: Written', $content);
        $this->assertContains('EMERGENCY: Written too', $content);
    }

    /**
     * Test for the single driver - defaults to the log name inside storage/logs.
     *
     * @group system
     */
    public function testSingleDriverDefaultsToLogName()
    {
        $this->channels('single', ['single' => ['driver' => 'single']]);

        $file = path('storage').'logs'.DS.'phpunit-single-test.log.php';
        Log::channel('phpunit-single-test');
        Log::info('Single message');

        $content = is_file($file) ? file_get_contents($file) : '';
        @unlink($file);

        $this->assertContains('INFO: Single message', $content);
    }

    /**
     * Test for the daily driver - applies the directory and prunes old files.
     *
     * @group system
     */
    public function testDailyDriverPrunesOldFiles()
    {
        Carbon::setNow(new Carbon('2026-03-10 12:00:00'));

        $this->channels('daily', ['daily' => ['driver' => 'daily', 'directory' => $this->dir, 'days' => 2]]);

        foreach (['rakit_2026-03-07', 'rakit_2026-03-08', 'rakit_2026-03-09', 'other_2026-01-01'] as $name) {
            file_put_contents($this->dir.DS.$name.'.log.php', 'old');
        }

        Log::info('Daily message');

        $this->assertContains('INFO: Daily message', file_get_contents($this->dir.DS.'rakit_2026-03-10.log.php'));
        $this->assertFileNotExists($this->dir.DS.'rakit_2026-03-07.log.php');
        $this->assertFileNotExists($this->dir.DS.'rakit_2026-03-08.log.php');
        $this->assertFileExists($this->dir.DS.'rakit_2026-03-09.log.php');
        $this->assertFileExists($this->dir.DS.'other_2026-01-01.log.php');
    }

    /**
     * Test for Log::channel() - routes to a configured channel.
     *
     * @group system
     */
    public function testChannelRoutesToConfiguredChannel()
    {
        $main = $this->dir.DS.'main.log';
        $audit = $this->dir.DS.'audit.log';

        $this->channels('main', [
            'main' => ['driver' => 'stream', 'stream' => $main, 'format' => 'json'],
            'audit' => ['driver' => 'stream', 'stream' => $audit, 'format' => 'json'],
        ]);

        Log::channel('audit');
        Log::info('To audit');
        Log::channel(null);
        Log::info('To main');

        $this->assertEquals(['To audit'], array_map(function ($line) {
            return $line['message'];
        }, $this->jsonLines($audit)));

        $lines = $this->jsonLines($main);

        $this->assertCount(1, $lines);
        $this->assertEquals('To main', $lines[0]['message']);
    }

    /**
     * Test for Log::channel() - an undefined channel uses the default one, named.
     *
     * @group system
     */
    public function testUndefinedChannelUsesDefaultChannelWithName()
    {
        $file = $this->dir.DS.'main.log';
        $this->channels('main', ['main' => ['driver' => 'stream', 'stream' => $file, 'format' => 'json']]);

        Log::channel('jobs');
        Log::info('Job done');

        $lines = $this->jsonLines($file);

        $this->assertEquals('jobs', $lines[0]['channel']);
        $this->assertEquals('Job done', $lines[0]['message']);
    }

    /**
     * Test for the stack driver - writes into every channel.
     *
     * @group system
     */
    public function testStackDriverWritesIntoEveryChannel()
    {
        $this->channels('stack', [
            'stack' => ['driver' => 'stack', 'channels' => ['a', 'b']],
            'a' => ['driver' => 'single', 'path' => $this->dir.DS.'a.log'],
            'b' => ['driver' => 'stream', 'stream' => $this->dir.DS.'b.log', 'format' => 'json'],
        ]);

        Log::notice('Stacked');

        $this->assertContains('NOTICE: Stacked', file_get_contents($this->dir.DS.'a.log'));
        $this->assertContains('"message":"Stacked"', file_get_contents($this->dir.DS.'b.log'));
        $this->assertEquals('', $this->emergencyContent());
    }

    /**
     * Test for the stack driver - a failing channel does not stop the others.
     *
     * @group system
     */
    public function testStackDriverReportsFailingChannel()
    {
        $this->channels('stack', [
            'stack' => ['driver' => 'stack', 'channels' => ['missing', 'a']],
            'a' => ['driver' => 'single', 'path' => $this->dir.DS.'a.log'],
        ]);

        Log::info('Still written');

        $this->assertContains('INFO: Still written', file_get_contents($this->dir.DS.'a.log'));
        $this->assertContains('Log channel is not defined: missing', $this->emergencyContent());
    }

    /**
     * Test for the stack driver - "ignore_exceptions" silences failing channels.
     *
     * @group system
     */
    public function testStackDriverCanIgnoreExceptions()
    {
        $this->channels('stack', [
            'stack' => ['driver' => 'stack', 'channels' => ['missing', 'a'], 'ignore_exceptions' => true],
            'a' => ['driver' => 'single', 'path' => $this->dir.DS.'a.log'],
        ]);

        Log::info('Quiet');

        $this->assertContains('INFO: Quiet', file_get_contents($this->dir.DS.'a.log'));
        $this->assertEquals('', $this->emergencyContent());
    }

    /**
     * Test for the stack driver - circular references are not followed.
     *
     * @group system
     */
    public function testStackDriverStopsCircularReference()
    {
        $this->channels('loop', [
            'loop' => ['driver' => 'stack', 'channels' => ['loop', 'a']],
            'a' => ['driver' => 'single', 'path' => $this->dir.DS.'a.log'],
        ]);

        Log::info('Once');

        $this->assertEquals(1, substr_count(file_get_contents($this->dir.DS.'a.log'), 'Once'));
        $this->assertContains('Circular reference in log stack', $this->emergencyContent());
    }

    /**
     * Test for the null driver - discards entries.
     *
     * @group system
     */
    public function testNullDriverDiscardsEntries()
    {
        $this->channels('null', ['null' => ['driver' => 'null']]);

        Log::error('Nothing');

        $this->assertEquals('', $this->emergencyContent());
        $this->assertCount(0, glob($this->dir.DS.'*'));
    }

    /**
     * Test for the errorlog driver - writes through error_log().
     *
     * @group system
     */
    public function testErrorlogDriverWritesThroughErrorLog()
    {
        $file = $this->dir.DS.'php-error.log';
        $original = ini_get('error_log');
        ini_set('error_log', $file);

        $this->channels('errorlog', ['errorlog' => ['driver' => 'errorlog']]);
        Log::warning('Through error_log');

        ini_set('error_log', (string) $original);

        $this->assertContains('WARNING: Through error_log', file_get_contents($file));
    }

    /**
     * Test for the syslog driver - honors the level before touching syslog.
     *
     * @group system
     */
    public function testSyslogDriverHonorsLevel()
    {
        $this->channels('syslog', ['syslog' => ['driver' => 'syslog', 'level' => 'emergency']]);

        $driver = Log::driver('syslog');

        $this->assertFalse($driver->handles('alert'));
        $this->assertTrue($driver->handles('emergency'));
        $this->assertTrue($driver->handle(['level' => 'info', 'message' => 'x', 'context' => []]));
    }

    /**
     * Test for Log::extend() - registers a third-party driver.
     *
     * @group system
     */
    public function testExtendRegistersDriver()
    {
        $this->channels('memory', ['memory' => ['driver' => 'memory', 'foo' => 'bar']]);

        Log::extend('memory', function (array $config, $channel) {
            return new LogDriversTestMemoryDriver($config, $channel);
        });

        Log::info('In memory', ['a' => 1]);

        $this->assertCount(1, LogDriversTestMemoryDriver::$records);

        $written = LogDriversTestMemoryDriver::$records[0];

        $this->assertEquals('memory', $written['channel']);
        $this->assertEquals('bar', $written['config']['foo']);
        $this->assertEquals('In memory', $written['record']['message']);
        $this->assertEquals(['a' => 1], $written['record']['context']);
    }

    /**
     * Test for the custom driver - resolves the "via" class and closure.
     *
     * @group system
     */
    public function testCustomDriverResolvesVia()
    {
        $this->channels('class', [
            'class' => ['driver' => 'custom', 'via' => 'LogDriversTestMemoryDriver'],
            'closure' => ['driver' => 'custom', 'via' => function (array $config, $channel) {
                return new LogDriversTestMemoryDriver($config, $channel);
            }],
        ]);

        Log::info('Via class');
        Log::channel('closure');
        Log::info('Via closure');

        $this->assertCount(2, LogDriversTestMemoryDriver::$records);
        $this->assertEquals('class', LogDriversTestMemoryDriver::$records[0]['channel']);
        $this->assertEquals('closure', LogDriversTestMemoryDriver::$records[1]['channel']);
    }

    /**
     * Test for Log::write() - misconfigured channels fall back to the emergency file.
     *
     * @group system
     */
    public function testMisconfiguredChannelsFallBack()
    {
        $this->channels('unknown', ['unknown' => ['driver' => 'nope']]);
        Log::info('Unsupported driver');

        $this->channels('level', ['level' => ['driver' => 'null', 'level' => 'loud']]);
        Log::info('Unsupported level');

        $this->channels('stream', ['stream' => ['driver' => 'stream', 'stream' => $this->dir.DS.'missing'.DS.'x.log']]);
        Log::info('Unwritable stream');

        $this->channels('undefined', []);
        Log::info('Undefined channel');

        $content = $this->emergencyContent();

        $this->assertContains('Unsupported log driver: nope', $content);
        $this->assertContains('INFO: Unsupported driver', $content);
        $this->assertContains('Unsupported log level: loud', $content);
        $this->assertContains('Log stream is not writable', $content);
        $this->assertContains('Log channel is not defined: undefined', $content);
        $this->assertContains('INFO: Undefined channel', $content);
    }

    /**
     * Test for Log::log() - writes with an arbitrary level.
     *
     * @group system
     */
    public function testLogMethodWritesArbitraryLevel()
    {
        $type = null;
        Hook::listen('rakit.log', function ($t) use (&$type) {
            $type = $t;
        });

        $this->channels('null', ['null' => ['driver' => 'null']]);
        Log::log('CRITICAL', 'Critical!');

        $this->assertEquals('critical', $type);
    }

    /**
     * Test for Log::log() - rejects unknown levels.
     *
     * @group system
     * @expectedException InvalidArgumentException
     */
    public function testLogMethodRejectsUnknownLevel()
    {
        Log::log('verbose', 'Nope');
    }

    /**
     * Test for the debugger logger - writes through the Log class.
     *
     * @group system
     */
    public function testDebuggerLoggerWritesThroughLog()
    {
        $captured = [];
        Hook::listen('rakit.log', function ($type, $message, $context) use (&$captured) {
            $captured[] = [$type, $message, $context];
        });

        $this->channels('null', ['null' => ['driver' => 'null']]);

        $exception = new \RuntimeException('Debugger exception');
        $logger = new \System\Foundation\Oops\Logger($this->dir);
        $logger->log('Debugger message', \System\Foundation\Oops\Logger::WARNING);
        $logger->log($exception, \System\Foundation\Oops\Logger::EXCEPTION);
        $logger->log(new \LogicException(''), \System\Foundation\Oops\Logger::CRITICAL);
        $logger->log(['a' => 1], \System\Foundation\Oops\Logger::INFO);

        $this->assertEquals(['warning', 'Debugger message', []], $captured[0]);
        $this->assertEquals(['error', 'Debugger exception', ['exception' => $exception]], $captured[1]);
        $this->assertEquals(['critical', 'LogicException'], array_slice($captured[2], 0, 2));
        $this->assertEquals(['info', '{"a":1}', []], $captured[3]);
        $this->assertCount(0, glob($this->dir.DS.'*.log.php'));
    }

    /**
     * Test for the debugger - an uncaught exception is logged exactly once.
     *
     * @group system
     */
    public function testDebuggerLogsUncaughtExceptionOnce()
    {
        $development = $this->runUncaughtException(false);

        $this->assertCount(1, $development['lines']);
        $this->assertEquals('ERROR', $development['lines'][0]['level']);
        $this->assertEquals('Uncaught in a subprocess', $development['lines'][0]['message']);
        $this->assertEquals('RuntimeException', $development['lines'][0]['context']['exception']['class']);
        $this->assertContains('RuntimeException: Uncaught in a subprocess', $development['stdout']);
        $this->assertNotContains('stored in', $development['stdout']);

        $production = $this->runUncaughtException(true);

        $this->assertCount(1, $production['lines']);
        $this->assertEquals('Exception occurred', $production['lines'][0]['message']);
        $this->assertEquals('RuntimeException', $production['lines'][0]['context']['exception']['class']);
        $this->assertContains('Error was logged.', $production['stderr']);
    }

    /**
     * Test for the debugger - production errors send the error email.
     *
     * @group system
     */
    public function testDebuggerEmailsUncaughtExceptionInProduction()
    {
        $production = $this->runUncaughtException(true, 'dev@example.com');

        // The "log" email driver writes the email it sends as a log entry.
        $this->assertCount(2, $production['lines']);
        $this->assertEquals('Exception occurred', $production['lines'][0]['message']);
        $this->assertStringStartsWith('Email sent: PHP: An error occurred on the server', $production['lines'][1]['message']);
        $this->assertContains('dev@example.com', $production['lines'][1]['context']['to']);
        $this->assertContains('Uncaught in a subprocess', $production['lines'][1]['context']['body']);
        $this->assertFileExists($this->dir.DS.'email-sent');
    }

    /**
     * Throw an uncaught exception in a PHP subprocess with the debugger enabled,
     * since the exception handler ends the process.
     *
     * @param bool        $production
     * @param string|null $email
     *
     * @return array
     */
    private function runUncaughtException($production, $email = null)
    {
        $tests = dirname(__DIR__).DS;
        $prefix = $this->dir.DS.($production ? 'production' : 'development');
        $log = $prefix.'.json';
        $script = $prefix.'.php';

        file_put_contents($script, implode("\n", [
            '<?php',
            'ob_start();',
            "define('RAKIT_START', microtime(true));",
            "define('DS', DIRECTORY_SEPARATOR);",
            "define('CRLF', \"\\r\\n\");",
            "define('TAB', \"\\t\");",
            "define('CR', \"\\r\");",
            "define('LF', \"\\n\");",
            'require '.var_export(dirname($tests).DS.'paths.php', true).';',
            "set_path('app', ".var_export($tests.'fixtures'.DS.'application'.DS, true).');',
            "set_path('package', ".var_export($tests.'fixtures'.DS.'packages'.DS, true).');',
            "set_path('storage', ".var_export($tests.'fixtures'.DS.'storage'.DS, true).');',
            "set_path('rakit_key', ".var_export($tests.'key.php', true).');',
            "require path('system').'core.php';",
            'System\Package::boot(DEFAULT_PACKAGE);',
            "System\Config::set('log', ['default' => 'test', 'channels' => ['test' => ".var_export([
                'driver' => 'stream',
                'stream' => $log,
                'format' => 'json',
            ], true).']]);',
            "System\Config::set('email.driver', 'log');",
            'System\Foundation\Oops\Debugger::$email = '.var_export($email, true).';',
            'System\Foundation\Oops\Debugger::$productionMode = '.($production ? 'true' : 'false').';',
            'System\Foundation\Oops\Debugger::enable(null, '.var_export($this->dir, true).');',
            "throw new RuntimeException('Uncaught in a subprocess');",
        ]));

        $descriptors = [
            0 => ['file', (DIRECTORY_SEPARATOR === '\\') ? 'NUL' : '/dev/null', 'r'],
            1 => ['file', $prefix.'.out', 'w'],
            2 => ['file', $prefix.'.err', 'w'],
        ];

        $options = (DIRECTORY_SEPARATOR === '\\') ? ['bypass_shell' => true] : null;
        $process = proc_open(escapeshellarg(PHP_BINARY).' '.escapeshellarg($script), $descriptors, $pipes, null, null, $options);

        $this->assertTrue(is_resource($process), 'Unable to start the PHP subprocess.');
        proc_close($process);

        return [
            'lines' => $this->jsonLines($log),
            'stdout' => (string) file_get_contents($prefix.'.out'),
            'stderr' => (string) file_get_contents($prefix.'.err'),
        ];
    }
}
