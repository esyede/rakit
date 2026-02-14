<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Debugger
{
    const DEVELOPMENT = false;
    const PRODUCTION = true;
    const DETECT = null;

    const DEBUG = Logger::DEBUG;
    const INFO = Logger::INFO;
    const WARNING = Logger::WARNING;
    const ERROR = Logger::ERROR;
    const EXCEPTION = Logger::EXCEPTION;
    const CRITICAL = Logger::CRITICAL;

    const COOKIE_SECRET = 'oops-debug';

    /**
     * Switch to production mode?
     *
     * @var bool
     */
    public static $productionMode = self::DETECT;

    /**
     * Show debug bar?
     *
     * @var bool
     */
    public static $showBar = true;

    /**
     * Stop the script on strict errors?
     * Fill with boolean or error level constants (E_NOTICE, E_WARNING, etc.).
     *
     * @var bool|int
     */
    public static $strictMode = false;

    /**
     * Show all errors, even those suppressed by @ operator?
     *
     * @var bool
     */
    public static $scream = false;

    /**
     * Contains callbacks that will be executed on fatal error.
     *
     * @var array|callable
     */
    public static $onFatalError = [];

    /**
     * How deep the dump() should go into nested structure? 0 means no limit.
     *
     * @var int
     */
    public static $maxDepth = 0;

    /**
     * How many characters should dump() output at most? 0 means no limit.
     *
     * @var int
     */
    public static $maxLength = 0;

    /**
     * Show location (file and line) for dumped variables?
     *
     * @var bool
     */
    public static $showLocation = false;

    /**
     * Contains directory where log files will be stored.
     *
     * @var string|null
     */
    public static $logDirectory;

    /**
     * Log severity level. This controls which errors are logged.
     * Fill with 0 (all) or PHP error constants (E_NOTICE, E_WARNING, etc.).
     *
     * @var int
     */
    public static $logSeverity = 0;

    /**
     * Contains email address or array of email addresses where error should be sent.
     *
     * @var string|array
     */
    public static $email;

    /**
     * Initial request time.
     *
     * @var int
     */
    public static $time;

    /**
     * Contains path to error template file.
     *
     * @var string
     */
    public static $errorTemplate;

    /**
     * Activate the debugger?
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * Reserved memory to handle fatal errors.
     *
     * @var string|null
    */
    private static $reserved;

    /**
     * Contains output buffering level at the time of enabling debugger.
     *
     * @var int
     */
    private static $obLevel;

    /**
     * Contains CPU usage data at the time of enabling debugger.
     *
     * @var int
     */
    private static $cpuUsage;

    /**
     * Contains the Panic object.
     *
     * @var Panic
     */
    private static $panic;

    /**
     * Contains the Bar object.
     *
     * @var Bar
     */
    private static $bar;

    /**
     * Contains the Logger object.
     *
     * @var Logger
     */
    private static $logger;

    /**
     * Disable instance creation.
     */
    final public function __construct()
    {
        throw new \LogicException();
    }

    /**
     * Enable debugger?
     *
     * @param mixed  $mode
     * @param string $logDirectory
     * @param string $email
     *
     * @return void
     */
    public static function enable($mode = null, $logDirectory = null, $email = null)
    {
        if (null !== $mode || null === self::$productionMode) {
            self::$productionMode = is_bool($mode) ? $mode : !self::detectDebugMode($mode);
        }

        self::$reserved = str_repeat('t', 30000);
        self::$time = isset($_SERVER['REQUEST_TIME_FLOAT'])
            ? $_SERVER['REQUEST_TIME_FLOAT']
            : microtime(true);

        self::$obLevel = ob_get_level();
        self::$cpuUsage = (!self::$productionMode && function_exists('getrusage'))
            ? getrusage()
            : null;

        if ($email !== null) {
            self::$email = $email;
        }

        if ($logDirectory !== null) {
            self::$logDirectory = $logDirectory;
        }

        if (self::$logDirectory) {
            if (!preg_match('#([a-z]+:)?[/\\\\]#Ai', self::$logDirectory)) {
                self::exceptionHandler(new \RuntimeException('Log directory must be absolute path.'));
                self::$logDirectory = null;
            } elseif (!is_dir(self::$logDirectory)) {
                self::exceptionHandler(new \RuntimeException(
                    sprintf('Logging directory cannot not be found: %s', self::$logDirectory)
                ));

                self::$logDirectory = null;
            }
        }

        if (function_exists('ini_set')) {
            ini_set('display_errors', self::$productionMode ? '0' : '1');
            ini_set('html_errors', '0');
            ini_set('log_errors', '0');
        } elseif (
            ini_get('display_errors') != (!self::$productionMode) // != is intentional to cover '1' and '0'
            && ini_get('display_errors') !== (self::$productionMode ? 'stderr' : 'stdout')
        ) {
            self::exceptionHandler(new \RuntimeException("Unable to set 'display_errors' because function ini_set() is disabled."));
        }

        error_reporting(E_ALL);

        if (self::$enabled) {
            return;
        }

        register_shutdown_function(['\System\Foundation\Oops\Debugger', 'shutdownHandler']);
        set_exception_handler(['\System\Foundation\Oops\Debugger', 'exceptionHandler']);
        set_error_handler(['\System\Foundation\Oops\Debugger', 'errorHandler']);

        array_map('class_exists', [
            '\System\Foundation\Oops\Bar',
            '\System\Foundation\Oops\Panic',
            '\System\Foundation\Oops\Defaults',
            '\System\Foundation\Oops\Dumper',
            '\System\Foundation\Oops\Helpers',
            '\System\Foundation\Oops\Logger',
        ]);

        self::dispatch();
        self::$enabled = true;
    }

    /**
     * Restart the debugger dispatching.
     * Use this if a new session is created after Debugger::enable() is called.
     *
     * @return void
     */
    public static function dispatch()
    {
        if (self::$productionMode || 'cli' === PHP_SAPI) {
            return;
        } elseif (headers_sent($file, $line)) {
            throw new \LogicException(
                'Debugger::dispatch() called after some output has been sent. '
                    . ($file
                        ? "Output started at $file:$line."
                        : 'Try System\Foundation\Oops\Outputs to find where output started.'
                    )
            );
        }

        $bufferedOutput = (ob_get_length() > 0) ? ob_get_clean() : '';

        if (self::$enabled && session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.save_path', sys_get_temp_dir());
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_path', '/');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_httponly', '1');
            ini_set('session.name', 'rakit_debugger');
            session_start();
        }

        if (self::getBar()->dispatchAssets()) {
            exit;
        }

        // Restore buffered output
        if (!empty($bufferedOutput)) {
            echo $bufferedOutput;
        }
    }

    /**
     * Render the debug bar loader.
     *
     * @return void
     */
    public static function renderLoader()
    {
        if (!self::$productionMode) {
            self::getBar()->renderLoader();
        }
    }

    /**
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * Shutdown handler.
     *
     * @return void
     */
    public static function shutdownHandler()
    {
        if (!self::$reserved) {
            return;
        }
        self::$reserved = null;

        $error = error_get_last();
        $errors = [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE, E_RECOVERABLE_ERROR, E_USER_ERROR];

        if (isset($error['type']) && in_array($error['type'], $errors, true)) {
            self::exceptionHandler(
                Helpers::fixStack(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line'])),
                false
            );
        } elseif (self::$showBar && !self::$productionMode) {
            self::removeOutputBuffers(false);
            self::getBar()->render();
        }
    }

    /**
     * Exception handler.
     *
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    public static function exceptionHandler($e, $exit = true)
    {
        if (!self::$reserved && $exit) {
            return;
        }
        self::$reserved = null;

        if (!headers_sent()) {
            $code = (isset($_SERVER['HTTP_USER_AGENT'])
                && false !== strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE ')
            ) ? 503 : 500;
            http_response_code($code);

            if (Helpers::isHtmlMode()) {
                header('Content-Type: text/html; charset=UTF-8');
            }
        }

        Helpers::improveException($e);
        self::removeOutputBuffers(true);

        if (PHP_SAPI !== 'cli' && !headers_sent()) {
            http_response_code(500);
        }

        if (self::$productionMode) {
            try {
                \System\Log::error('Exception occurred', ['exception' => $e]);
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }

            if (Helpers::isHtmlMode()) {
                if (is_file(static::$errorTemplate)) {
                    if (\System\Event::exists('rakit.view.engine') && substr(static::$errorTemplate, -10) === '.blade.php') {
                        try {
                            echo \System\View::make('error.500')->render();
                        } catch (\Throwable $e) {
                            require __DIR__ . '/assets/debugger/500.phtml';
                        } catch (\Exception $e) {
                            require __DIR__ . '/assets/debugger/500.phtml';
                        }
                    } else {
                        require static::$errorTemplate;
                    }
                } else {
                    require __DIR__ . '/assets/debugger/500.phtml';
                }
            } elseif ('cli' === PHP_SAPI) {
                // FIXME: BC-break di PHP 7.4+: @ mentrigger E_NOTICE ketika stderr tidak bisa diakses
                @fwrite(STDERR, 'ERROR: application encountered an error and can not continue. '
                    . (isset($e) ? "Unable to log error.\n" : "Error was logged.\n"));
            }
        } elseif (!connection_aborted() && (Helpers::isHtmlMode() || Helpers::isAjax())) {
            $isJsonRequest = isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
            if (Helpers::isAjax() && $isJsonRequest) {
                \System\Log::error($e->getMessage(), [
                    'exception' => $e,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                header('Content-Type: application/json; charset=UTF-8');
                echo json_encode(['status' => 500, 'message' => $e->getMessage()]);
                if (function_exists('fastcgi_finish_request')) {
                    fastcgi_finish_request();
                }
                exit(255);
            }
            self::getPanic()->render($e);

            if (self::$showBar) {
                self::getBar()->render();
            }
        } else {
            $s = get_class($e) . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                . ' in ' . $e->getFile() . ':' . $e->getLine()
                . "\nStack trace:\n" . $e->getTraceAsString();

            try {
                $file = null;
                if (self::$logDirectory) {
                    \System\Log::error($e->getMessage(), [
                        'exception' => $e,
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                    $file = self::log($e, self::EXCEPTION);
                    if ($file && !headers_sent()) {
                        header('X-Oops-Error-Log: ' . $file);
                    }
                }

                if ($file) {
                    echo "$s\n" . ("(stored in $file)\n");
                } elseif ($exit) {
                    echo "$s\n";
                }
            } catch (\Throwable $ex) {
                if ($exit) {
                    echo "$s\nUnable to log error: {$ex->getMessage()}\n";
                }
            } catch (\Exception $ex) {
                if ($exit) {
                    echo "$s\nUnable to log error: {$ex->getMessage()}\n";
                }
            }
        }

        try {
            $e = null;
            foreach (self::$onFatalError as $handler) {
                call_user_func($handler, $e);
            }
        } catch (\Throwable $e) {
            // Skip error
        } catch (\Exception $e) {
            // Skip error
        }

        if ($e) {
            try {
                \System\Log::error('Exception occurred', ['exception' => $e]);
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }
        }

        if ($exit) {
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            }
            exit(255);
        }
    }

    /**
     * Error handler.
     *
     * @throws ErrorException
     *
     * @return bool|null
     */
    public static function errorHandler($severity, $message, $file, $line, $context = null)
    {
        if (self::$scream) {
            error_reporting(E_ALL);
        }

        $context = (array) $context;

        if ($severity === E_RECOVERABLE_ERROR || $severity === E_USER_ERROR) {
            if (Helpers::findTrace(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS), '*::__toString')) {
                $previous = (isset($context['e'])
                    && (($context['e'] instanceof \Exception) || ($context['e'] instanceof \Throwable))
                ) ? $context['e'] : null;
                $e = new \ErrorException($message, 0, $severity, $file, $line, $previous);
                Context::setContext($e, $context);
                self::exceptionHandler($e);
            }

            $e = new \ErrorException($message, 0, $severity, $file, $line);
            Context::setContext($e, $context);
            throw $e;
        } elseif (($severity & error_reporting()) !== $severity) {
            return false;
        } elseif (self::$productionMode && ($severity & self::$logSeverity) === $severity) {
            $e = new \ErrorException($message, 0, $severity, $file, $line);
            Context::setContext($e, $context);
            Helpers::improveException($e);

            try {
                \System\Log::error('Error occurred', ['error' => $e]);
            } catch (\Throwable $foo) {
                // Skip error
            } catch (\Exception $foo) {
                // Skip error
            }

            return;
        } elseif (
            !self::$productionMode
            && !isset($_GET['_oops_skip_error'])
            && (is_bool(self::$strictMode) ? self::$strictMode : ((self::$strictMode & $severity) === $severity))
        ) {
            $e = new \ErrorException($message, 0, $severity, $file, $line);
            Context::setContext($e, $context);
            Context::setSkippable($e, true);
            self::exceptionHandler($e);
        }

        $message = 'PHP ' . Helpers::errorTypeToString($severity)
            . ': ' . Helpers::improveError($message, $context);
        $count = &self::getBar()->getPanel('Oops:info')->data["$file|$line|$message"];

        if ($count++) {
            return;
        } elseif (self::$productionMode) {
            try {
                \System\Log::error($message, ['file' => $file, 'line' => $line]);
            } catch (\Throwable $foo) {
                // Skip error
            } catch (\Exception $foo) {
                // Skip error
            }
            return;
        } else {
            // 'FALSE' will let the normal error handler continue
            return (Helpers::isHtmlMode() || Helpers::isAjax()) ? null : false;
        }
    }

    private static function removeOutputBuffers($errorOccurred)
    {
        while (ob_get_level() > self::$obLevel) {
            $status = ob_get_status();

            if (in_array($status['name'], ['ob_gzhandler', 'zlib output compression'], true)) {
                break;
            }

            $fnc = $status['chunk_size'] || !$errorOccurred ? 'ob_end_flush' : 'ob_end_clean';

            if (!@$fnc()) { // @ to suppress potential warnings
                break;
            }
        }
    }

    /**
     * @return Panic
     */
    public static function getPanic()
    {
        if (!self::$panic) {
            self::$panic = new Panic();
            self::$panic->info = [
                'PHP ' . PHP_VERSION,
                isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : null,
            ];
        }

        return self::$panic;
    }

    /**
     * @return Bar
     */
    public static function getBar()
    {
        if (!self::$bar) {
            self::$bar = new Bar();
            self::$bar->addPanel($info = new Defaults('info'), 'Oops:info');

            $info->cpuUsage = self::$cpuUsage;

            self::$bar->addPanel(new Defaults('errors'), 'Oops:errors');
            self::$bar->addPanel(new Defaults('db'), 'db');

            // Initialize collectors for new panels
            Collectors::initialize();

            // Add new debug panels
            self::$bar->addPanel($request = new Defaults('request'), 'Oops:request');
            self::$bar->addPanel($routes = new Defaults('routes'), 'Oops:routes');
            self::$bar->addPanel($events = new Defaults('events'), 'Oops:events');
            self::$bar->addPanel($view = new Defaults('view'), 'Oops:view');
            self::$bar->addPanel($cache = new Defaults('cache'), 'Oops:cache');

            // Collect data for new panels
            $routes->data = Collectors::collectRoutes();
            $events->data = Collectors::getData('events');
            $view->data = Collectors::getData('views');
            $cache->data = Collectors::getData('cache');
        }

        return self::$bar;
    }

    /**
     * @return void
     */
    public static function setLogger(Logger $logger)
    {
        self::$logger = $logger;
    }

    /**
     * @return Logger
     */
    public static function getLogger()
    {
        if (!self::$logger) {
            self::$logger = new Logger(self::$logDirectory, self::$email, self::getPanic());
            self::$logger->directory = &self::$logDirectory; // back compatiblity
            self::$logger->email = &self::$email;
        }

        return self::$logger;
    }

    /**
     * Dump variable into a more readable format.
     * This method can be used in production mode.
     *
     * @param mixed $var
     * @param bool  $return
     *
     * @return mixed variable itself or dump
     */
    public static function dump($var, $return = false)
    {
        if ($return) {
            ob_start(function () {
                return '';
            }); // disable output buffering

            Dumper::dump($var, [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
            ]);

            return ob_get_clean();
        } elseif (!self::$productionMode) {
            Dumper::dump($var, [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
                Dumper::LOCATION => self::$showLocation,
            ]);
        }

        return $var;
    }

    /**
     * Start or stop a timer and return the elapsed time in seconds.
     *
     * @param string $name
     *
     * @return float
     */
    public static function timer($name = null)
    {
        static $time = [];

        $now = microtime(true);
        $delta = isset($time[$name]) ? $now - $time[$name] : 0;
        $time[$name] = $now;

        return $delta;
    }

    /**
     * Dump variable to the debug bar.
     * This method can be used in production mode.
     *
     * @param mixed  $var
     * @param string $title
     * @param array  $options
     *
     * @return mixed
     */
    public static function barDump($var, $title = null, array $options = [])
    {
        if (!self::$productionMode) {
            static $panel;

            if (!$panel) {
                self::getBar()->addPanel($panel = new Defaults('dumps'), 'Oops:dumps');
            }

            $panel->data[] = [
            'title' => is_scalar($title) ? (string) $title : null,
            'dump' => Dumper::toHtml($var, (array) $options + [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
                Dumper::LOCATION => self::$showLocation ?: (Dumper::LOCATION_CLASS | Dumper::LOCATION_SOURCE),
            ])];
        }

        return $var;
    }

    /**
     * Log a message into log file.
     *
     * @param mixed $message
     *
     * @return mixed
     */
    public static function log($message, $priority = Logger::INFO)
    {
        return self::getLogger()->log($message, $priority);
    }

    /**
     * Detect debug mode based on IP address list.
     *
     * @param string|array $list IP
     *
     * @return bool
     */
    public static function detectDebugMode($list = null)
    {
        $addr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : php_uname('n');
        $secret = (isset($_COOKIE[self::COOKIE_SECRET]) && is_string($_COOKIE[self::COOKIE_SECRET])) ? $_COOKIE[self::COOKIE_SECRET] : null;
        $list = is_string($list) ? preg_split('#[,\s]+#', $list) : (array) $list;

        if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !isset($_SERVER['HTTP_FORWARDED'])) {
            $list[] = '127.0.0.1';
            $list[] = '::1';
        }

        return in_array($addr, $list, true) || in_array("$secret@$addr", $list, true);
    }
}
