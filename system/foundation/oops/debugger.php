<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Debugger
{
    const DEVELOPMENT = false;
    const PRODUCTION = true;
    const DETECT = null;

    const COOKIE_SECRET = 'oops-debug';

    /**
     * Beralih ke mode produksi (matikan seluruh fitur debugger).
     *
     * @var bool
     */
    public static $productionMode = self::DETECT;

    /**
     * Tampilkan debug bar?
     *
     * @var bool
     */
    public static $showBar = true;

    /**
     * Kirimkan data ke Firelogger?
     *
     * @var bool
     */
    public static $showFirelog = true;

    /**
     * Aktifkan debugger?
     *
     * @var bool
     */
    private static $enabled = false;

    /**
     * Indikator untuk menghindari output ganda.
     *
     * @var string|null
     */
    private static $reserved;

    /**
     * Output buffer level awal.
     *
     * @var int
     */
    private static $obLevel;

    /**
     * Langsung hentinkan aplikasi saat terjadi error?
     * Isi dengan boolean atau konstanta error PHP (E_NOTICE, E_WARNING dsb.).
     *
     * @var bool|int
     */
    public static $strictMode = false;

    /**
     * Abaikan operator @ (diam!) agar seluruh error bisa ditampilkan.
     *
     * @var bool
     */
    public static $scream = false;

    /**
     * Berisi closure yang akan otomatis terpanggil saat terjadi fatal error.
     *
     * @var array|callable
     */
    public static $onFatalError = [];

    /**
     * Berapa dalam array/object yang harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public static $maxDepth = 0;

    /**
     * Berapa banyak karakter harus ditampilkan oleh dump()?
     *
     * @var int
     */
    public static $maxLength = 0;

    /**
     * Tampilkan juga lokasi file ketika memanggil dump()?
     *
     * @var bool
     */
    public static $showLocation = false;

    /**
     * Path ke direktori tempat menyimpan log error.
     *
     * @var string|null
     */
    public static $logDirectory;

    /**
     * Log error - error ini saat berada di mode produksi.
     * Isi dengan 0 (semua) atau konstanta error PHP (E_NOTICE, E_WARNING dsb.).
     *
     * @var int
     */
    public static $logSeverity = 0;

    /**
     * Alamat email untuk yang akan dikirimi log ketika terjadi error.
     *
     * @var string|array
     */
    public static $email;

    /**
     * Konstants untuk Debugger::log() dan Debugger::fireLog().
     */
    const DEBUG = Logger::DEBUG;
    const INFO = Logger::INFO;
    const WARNING = Logger::WARNING;
    const ERROR = Logger::ERROR;
    const EXCEPTION = Logger::EXCEPTION;
    const CRITICAL = Logger::CRITICAL;

    /**
     * Timestamp awal request dimulai (dalam microsecond).
     *
     * @var int
     */
    public static $time;

    /**
     * URI untuk fitur open in editor.
     *
     * @var string
     */
    public static $editor = 'editor://%action/?file=%file&line=%line&search=%search&replace=%replace';

    /**
     * Mapping path editor.
     *
     * @var array
     */
    public static $editorMapping = [];

    /**
     * Perintah untuk membuka browser (gunakan 'start ""' di Windows).
     *
     * @var string
     */
    public static $browser;

    /**
     * Path view untuk halaman error.
     *
     * @var string
     */
    public static $errorTemplate;

    /**
     * Path untuk css kustom.
     *
     * @var array
     */
    public static $customCssFiles = [];

    /**
     * Path untuk js kustom.
     *
     * @var array
     */
    public static $customJsFiles = [];

    /**
     * Data penggunaan CPU.
     *
     * @var int
     */
    private static $cpuUsage;

    /**
     * Berisi object kelas Panic.
     *
     * @var Panic
     */
    private static $panic;

    /**
     * Berisi object kelas Bar.
     *
     * @var Bar
     */
    private static $bar;

    /**
     * Berisi object kelas Logger.
     *
     * @var Logger
     */
    private static $logger;

    /**
     * Berisi object kelas Firelog.
     *
     * @var Firelog
     */
    private static $fireLogger;

    /**
     * Jangan izinkan instansiasi kelas.
     */
    final public function __construct()
    {
        throw new \LogicException();
    }

    /**
     * Aktifkan debugger?
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
            ini_set('display_errors', self::$productionMode ? '0' : '1'); // atau 'stderr'
            ini_set('html_errors', '0');
            ini_set('log_errors', '0');
        } elseif (
            ini_get('display_errors') != (!self::$productionMode) // != memang sengaja
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
            '\System\Foundation\Oops\Firelog',
            '\System\Foundation\Oops\Helpers',
            '\System\Foundation\Oops\Logger',
        ]);

        self::dispatch();
        self::$enabled = true;
    }

    /**
     * Restart debugger.
     * Gunakan ini jika ada session baru yang dibuat setelah Debugger::enable() dipanggil.
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

        $bufferedOutput = '';
        if (ob_get_length() > 0) {
            // Simpan output yang dibuffer dan bersihkan
            $bufferedOutput = ob_get_clean();
        }

        if (self::$enabled && session_status() !== PHP_SESSION_ACTIVE) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_path', '/');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_trans_sid', '0');
            ini_set('session.cookie_path', '/');
            ini_set('session.cookie_httponly', '1');
            session_start();
        }

        if (self::getBar()->dispatchAssets()) {
            exit;
        }

        // Kembalikan output yang dibuffer
        if (!empty($bufferedOutput)) {
            echo $bufferedOutput;
        }
    }

    /**
     * Render loading tag.
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
        $errors = [
            E_ERROR,
            E_CORE_ERROR,
            E_COMPILE_ERROR,
            E_PARSE,
            E_RECOVERABLE_ERROR,
            E_USER_ERROR,
        ];

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

        if (self::$productionMode) {
            try {
                self::log($e, self::EXCEPTION);
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }

            if (Helpers::isHtmlMode()) {
                $logged = empty($e);
                if (is_file(static::$errorTemplate)) {
                    require static::$errorTemplate;
                } else {
                    require __DIR__ . '/assets/debugger/500.phtml';
                }
            } elseif ('cli' === PHP_SAPI) {
                // FIXME: BC-break di PHP 7.4+: @ mentrigger E_NOTICE ketika stderr tidak bisa diakses
                @fwrite(STDERR, 'ERROR: application encountered an error and can not continue. '
                    . (isset($e) ? "Unable to log error.\n" : "Error was logged.\n"));
            }
        } elseif (!connection_aborted() && (Helpers::isHtmlMode() || Helpers::isAjax())) {
            self::getPanic()->render($e);

            if (self::$showBar) {
                self::getBar()->render();
            }
        } else {
            self::fireLog($e);
            $s = get_class($e) . (('' === $e->getMessage()) ? '' : ': ' . $e->getMessage())
                . ' in ' . $e->getFile() . ':' . $e->getLine()
                . "\nStack trace:\n" . $e->getTraceAsString();

            try {
                $file = null;

                // Only attempt to write a file if a log directory is configured.
                if (self::$logDirectory) {
                    $file = self::log($e, self::EXCEPTION);

                    if ($file && !headers_sent()) {
                        header('X-Oops-Error-Log: ' . $file);
                    }
                }

                // In CLI/non-html mode, avoid noisy output when exceptionHandler was
                // explicitly called with $exit = false (used by tests). Only print
                // details when we have a file or when exit is true.
                if ($file) {
                    echo "$s\n" . ("(stored in $file)\n");

                    if (self::$browser) {
                        exec(self::$browser . ' ' . escapeshellarg($file));
                    }
                } elseif ($exit) {
                    // Fallback: print basic info when this is a real fatal handling.
                    echo "$s\n";
                }
            } catch (\Throwable $ex) {
                if ($exit) {
                    echo "$s\nUnable to log error: {$ex->getMessage()}\n";
                }
                // otherwise suppress logging errors during non-fatal invocation
            } catch (\Exception $ex) {
                if ($exit) {
                    echo "$s\nUnable to log error: {$ex->getMessage()}\n";
                }
                // otherwise suppress logging errors during non-fatal invocation
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
                self::log($e, self::EXCEPTION);
            } catch (\Throwable $e) {
                // Skip error
            } catch (\Exception $e) {
                // Skip error
            }
        }

        if ($exit) {
            exit(255);
        }
    }

    /**
     * Error hander.
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
                // Store context via Context helper instead of dynamic property
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
                self::log($e, self::ERROR);
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
                self::log("$message in $file:$line", self::ERROR);
            } catch (\Throwable $foo) {
                // Skip error
            } catch (\Exception $foo) {
                // Skip error
            }
            return;
        } else {
            self::fireLog(new \ErrorException($message, 0, $severity, $file, $line));

            // 'FALSE' akan memanggil error handler bawaan PHP
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

            if (!@$fnc()) { // @ untuk menghindari error
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
     * @return Logger
     */
    public static function getFirelog()
    {
        if (!self::$fireLogger) {
            self::$fireLogger = new Firelog();
        }

        return self::$fireLogger;
    }

    /**
     * Dump variable ke format yang lebih mudah dibaca.
     * Method ini bisa dipakai di mode produksi.
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
                // ..
            });

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
     * Mulai/hentikan timer (untuk benchmark).
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
     * Dump variable ke debug bar.
     * Method ini bisa dipakai di mode produksi.
     *
     * @param mixed  $var
     * @param string $title
     * @param array  $options
     *
     * @return mixed variable itself
     */
    public static function barDump($var, $title = null, array $options = null)
    {
        if (!self::$productionMode) {
            static $panel;

            if (!$panel) {
                self::getBar()->addPanel($panel = new Defaults('dumps'), 'Oops:dumps');
            }

            $panel->data[] = ['title' => $title, 'dump' => Dumper::toHtml($var, (array) $options + [
                Dumper::DEPTH => self::$maxDepth,
                Dumper::TRUNCATE => self::$maxLength,
                Dumper::LOCATION => self::$showLocation ?: (Dumper::LOCATION_CLASS | Dumper::LOCATION_SOURCE),
            ])];
        }

        return $var;
    }

    /**
     * Log pesab atau exception.
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
     * Kirim pesan ke konsolFirelog.
     *
     * @param mixed $message
     *
     * @return bool was successful?
     */
    public static function fireLog($message)
    {
        if (!self::$productionMode && self::$showFirelog) {
            return self::getFirelog()->log($message);
        }
    }

    /**
     * Deteksi mode debugging berdasarkan alamat IP.
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
