<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Logger
{
    /** @var string */
    const DEBUG = 'debug';

    /** @var string */
    const INFO = 'info';

    /** @var string */
    const WARNING = 'warning';

    /** @var string */
    const ERROR = 'error';

    /** @var string */
    const EXCEPTION = 'exception';

    /** @var string */
    const CRITICAL = 'critical';

    /**
     * Path to the directory log files are written to.
     *
     * @var string|null
     */
    public $directory;

    /**
     * Email address that receives error notifications.
     *
     * @var string|array
     */
    public $email;

    /**
     * Sender address for error notifications.
     *
     * @var string
     */
    public $fromEmail;

    /**
     * How often a notification email may be sent (default: 2 days).
     *
     * @var mixed
     */
    public $emailSnooze = '2 days';

    /**
     * Mail sending handler.
     *
     * @var callable
     */
    public $mailer;

    /**
     * The Panic instance.
     *
     * @var Panic
     */
    private $panic;

    /**
     * @param string|null       $directory
     * @param string|array|null $email
     * @param Panic|null        $panic
     */
    public function __construct($directory, $email = null, $panic = null)
    {
        $this->directory = $directory;
        $this->email = $email;
        $this->panic = $panic;
        $this->mailer = [$this, 'defaultMailer'];
    }

    /**
     * Log a message or exception through the configured log channel and send it by email.
     *
     * @param mixed  $message
     * @param string $priority
     *
     * @return string|null
     */
    public function log($message, $priority = self::INFO)
    {
        if (! $this->directory) {
            throw new \LogicException('Logging directory is not specified.');
        } elseif (! is_dir($this->directory)) {
            throw new \RuntimeException(
                sprintf('Logging directory cannot be found or is not directory: %s', $this->directory)
            );
        }

        $excfile = (($message instanceof \Exception) || (class_exists('\Throwable') && ($message instanceof \Throwable)))
            ? $this->getExceptionFile($message)
            : null;

        // Written through the Log class, so the configured log channel applies here too.
        $levels = [
            self::DEBUG => 'debug',
            self::INFO => 'info',
            self::WARNING => 'warning',
            self::ERROR => 'error',
            self::EXCEPTION => 'error',
            self::CRITICAL => 'critical',
        ];
        $level = isset($levels[$priority]) ? $levels[$priority] : 'error';

        if ($excfile) {
            // Passed as context, so the JSON format keeps the trace structured.
            \System\Log::log($level, ('' === (string) $message->getMessage()) ? get_class($message) : $message->getMessage(), ['exception' => $message]);
        } else {
            \System\Log::log($level, static::formatText($message));
        }

        if ($excfile) {
            $this->logException($message, $excfile);
        }

        if (in_array($priority, [self::ERROR, self::EXCEPTION, self::CRITICAL], true)) {
            $this->sendEmail($message);
        }

        return $excfile;
    }

    /**
     * @param mixed $message
     *
     * @return string
     */
    public static function formatMessage($message)
    {
        if (($message instanceof \Exception) || (class_exists('\Throwable') && ($message instanceof \Throwable))) {
            return static::formatExceptionForRakitLog($message);
        } elseif (! is_string($message)) {
            return static::formatValueForRakitLog($message);
        }

        return trim($message);
    }

    /**
     * Format a message, or any other value, as text.
     *
     * @param mixed $message
     *
     * @return string
     */
    protected static function formatText($message)
    {
        $text = static::formatMessage($message);
        return is_string($text) ? $text : (string) json_encode($text, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param mixed  $message
     * @param string $excfile
     * @param string $priority
     *
     * @return string
     */
    public static function formatLogLine($message, $excfile = null, $priority = self::INFO)
    {
        $env = (Debugger::$productionMode ? 'production' : 'local');
        $date = \System\Carbon::now()->format('Y-m-d H:i:s');
        $level = strtoupper((string) $priority);
        $output = sprintf('[%s] %s.%s: %s', $date, $env, $level, static::formatMessage($message));

        return $output . PHP_EOL;
    }

    /**
     * @param \Throwable|\Exception $exception
     *
     * @return string
     */
    public function getExceptionFile($exception)
    {
        $data = [];

        while ($exception) {
            $data[] = [
                get_class($exception),
                $exception->getMessage(),
                $exception->getCode(),
                $exception->getFile(),
                $exception->getLine(),
                array_map(function ($item) {
                    unset($item['args']);
                    return $item;
                }, $exception->getTrace()),
            ];
            $exception = $exception->getPrevious();
        }

        $hash = substr(md5(serialize($data)), 0, 10);
        $dir = strtr($this->directory . '/', '\\/', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);

        foreach (new \DirectoryIterator($this->directory) as $file) {
            if (strpos($file->getBasename(), $hash)) {
                return $dir . $file;
            }
        }

        return $dir . 'html' . DIRECTORY_SEPARATOR . 'exception--' . @date('Y-m-d--H-i') . '--' . $hash . '.html';
    }

    /**
     * Log an exception to file.
     *
     * @param \Throwable|\Exception $exception
     * @param string                $file
     *
     * @return string
     */
    protected function logException($exception, $file = null)
    {
        $file = $file ?: $this->getExceptionFile($exception);
        $panic = $this->panic ?: new Panic();
        // FIXME: render the detailed HTML error log here too?
        return $file;
    }

    /**
     * Email a message or exception logged elsewhere. Never throws: a failure is warned.
     *
     * @param mixed $message
     *
     * @return bool
     */
    public function notify($message)
    {
        return $this->sendEmail($message);
    }

    /**
     * Send the error email, unless one was already sent within the snooze period.
     *
     * @param mixed $message
     *
     * @return bool
     */
    protected function sendEmail($message)
    {
        if (! $this->email || ! $this->mailer) {
            return false;
        }

        try {
            if (! $this->directory || ! is_dir($this->directory)) {
                throw new \RuntimeException('The log directory is needed to remember when the last error email was sent.');
            }

            $snooze = is_numeric($this->emailSnooze) ? (int) $this->emailSnooze : (strtotime($this->emailSnooze) - time());
            $marker = rtrim($this->directory, '\\/') . DIRECTORY_SEPARATOR . 'email-sent';

            // Long-running workers would otherwise see a stale modification time.
            clearstatcache();

            if (is_file($marker) && filemtime($marker) + $snooze >= time()) {
                return false;
            }

            // Marked before sending, so a failing mailer is not retried on every error either.
            if (! (is_file($marker) ? is_writable($marker) : is_writable($this->directory))
                || false === @file_put_contents($marker, 'sent')
            ) {
                throw new \RuntimeException(sprintf('Unable to write the error email marker: %s', $marker));
            }

            if (false === call_user_func($this->mailer, $message, implode(', ', (array) $this->email))) {
                throw new \RuntimeException('The mailer reported a failure.');
            }

            return true;
        } catch (\Throwable $e) {
            $this->reportEmailFailure($e);
        } catch (\Exception $e) {
            $this->reportEmailFailure($e);
        }

        return false;
    }

    /**
     * Log why the error email could not be sent.
     *
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    protected function reportEmailFailure($e)
    {
        try {
            \System\Log::warning('Unable to send the error email: ' . $e->getMessage());
        } catch (\Throwable $ex) {
            // Nowhere left to report to
        } catch (\Exception $ex) {
            // Nowhere left to report to
        }
    }

    /**
     * Mailer default, sends through the Email component (see config/email.php).
     *
     * @param mixed  $message
     * @param string $email
     *
     * @return bool
     */
    public function defaultMailer($message, $email)
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n');
        $host = preg_replace('#[^\w.-]+#', '', $host);
        $recipients = array_values(array_filter(array_map('trim', explode(',', (string) $email)), 'strlen'));

        // A new driver instance, since the shared one may hold an email the application was composing.
        $drivers = \System\Email::$drivers;
        \System\Email::$drivers = [];

        try {
            $mailer = \System\Email::driver();
        } catch (\Throwable $e) {
            \System\Email::$drivers = $drivers;
            throw $e;
        } catch (\Exception $e) {
            \System\Email::$drivers = $drivers;
            throw $e;
        }

        \System\Email::$drivers = $drivers;

        if ($this->fromEmail) {
            $mailer->from($this->fromEmail);
        }

        return $mailer->to($recipients)
            ->subject("PHP: An error occurred on the server $host")
            ->body(static::formatText($message) . "\n\nsource: " . Helpers::getSource())
            ->send();
    }

    /**
     * Safely format a value for logging.
     *
     * @param mixed $value
     * @param array $objects
     * @param array $arrays
     *
     * @return mixed
     */
    protected static function formatValueForRakitLog($value, array &$objects = [], array &$arrays = [])
    {
        $exception = (PHP_VERSION_ID < 70000) ? ($value instanceof \Exception) : ($value instanceof \Throwable || $value instanceof \Exception);

        if ($exception) {
            return static::formatExceptionForRakitLog($value);
        }

        if (is_object($value)) {
            $id = function_exists('spl_object_id') ? spl_object_id($value) : spl_object_hash($value);

            if (isset($objects[$id])) {
                return sprintf('[object] (%s) [circular]', get_class($value));
            }

            $objects[$id] = true;
            $json = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            unset($objects[$id]);
            return (json_last_error() === JSON_ERROR_NONE) ? $json : sprintf('[object] (%s)', get_class($value));
        }

        if (is_resource($value)) {
            return sprintf('[resource] (%s)', get_resource_type($value));
        }

        if (is_array($value)) {
            $hash = md5(serialize($value));

            if (isset($arrays[$hash])) {
                return '[array] [circular]';
            }

            $arrays[$hash] = true;
            $formatted = [];

            foreach ($value as $k => $v) {
                $formatted[$k] = static::formatValueForRakitLog($v, $objects, $arrays);
            }

            unset($arrays[$hash]);
            return $formatted;
        }

        return $value;
    }

    /**
     * Format an exception for logging.
     *
     * @param \Exception|object $e
     *
     * @return string
     */
    protected static function formatExceptionForRakitLog($e)
    {
        $output = sprintf('[object] (%s(code: %s): %s at %s:%s)', get_class($e), $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine());
        return $e->getTraceAsString() ? $output . PHP_EOL . $e->getTraceAsString() : $output;
    }
}
