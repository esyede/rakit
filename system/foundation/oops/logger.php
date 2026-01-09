<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct access.');

class Logger
{
    const DEBUG = 'debug';
    const INFO = 'info';
    const WARNING = 'warning';
    const ERROR = 'error';
    const EXCEPTION = 'exception';
    const CRITICAL = 'critical';

    /**
     * Path ke di direktori penyimpanan file log.
     *
     * @var string|null
     */
    public $directory;

    /**
     * Email yang akan menerima notifikasi error.
     *
     * @var string|array
     */
    public $email;

    /**
     * Email pengirim notifikasi error.
     *
     * @var string
     */
    public $fromEmail;

    /**
     * Interval pengiriman email notifikasi (default 2 hari).
     *
     * @var mixed
     */
    public $emailSnooze = '2 days';

    /**
     * Handler pengiriman email.
     *
     * @var callable
     */
    public $mailer;

    /**
     * Berisi object kelas Panic.
     *
     * @var Panic
     */
    private $panic;

    /**
     * @param string|null       $directory
     * @param string|array|null $email
     */
    public function __construct($directory, $email = null, Panic $panic = null)
    {
        $this->directory = $directory;
        $this->email = $email;
        $this->panic = $panic;
        $this->mailer = [$this, 'defaultMailer'];
    }

    /**
     * Log pesan atau exception ke file dan kirim ke email.
     *
     * @param mixed  $message
     * @param string $priority
     *
     * @return string|null
     */
    public function log($message, $priority = self::INFO)
    {
        if (!$this->directory) {
            throw new \LogicException('Logging directory is not specified.');
        } elseif (!is_dir($this->directory)) {
            throw new \RuntimeException(
                sprintf('Logging directory cannot be found or is not directory: %s', $this->directory)
            );
        }

        $excfile = (($message instanceof \Exception) || (class_exists('\Throwable') && ($message instanceof \Throwable)))
            ? $this->getExceptionFile($message)
            : null;
        $line = static::formatLogLine($message, $excfile, $priority);
        $prefix = \System\Config::get('application.name')
            ? \System\Str::slug(\System\Config::get('application.name')) . '_'
            : '';
        $file = $this->directory . DIRECTORY_SEPARATOR . $prefix . date('Y-m-d') . '.log.php';

        try {
            if (is_file($file)) {
                file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
            } else {
                file_put_contents($file, $line . PHP_EOL, LOCK_EX);
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(
                sprintf("Unable to write to log file '%s'. Is that directory writable?", $file)
            );
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
        if (($message instanceof \Exception)
            || (class_exists('\Throwable') && ($message instanceof \Throwable))
        ) {
            return static::formatExceptionForRakitLog($message);
        } elseif (!is_string($message)) {
            return static::formatValueForRakitLog($message);
        }

        return trim($message);
    }

    /**
     * @param mixed $message
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

        $dir .= 'html' . DIRECTORY_SEPARATOR;
        return $dir . 'exception--' . @date('Y-m-d--H-i') . "--$hash.html";
    }

    /**
     * Log exception ke file.
     *
     * @param \Throwable|\Exception $exception
     *
     * @return string
     */
    protected function logException($exception, $file = null)
    {
        $file = $file ?: $this->getExceptionFile($exception);
        $panic = $this->panic ?: new Panic();

        // FIXME: Apakah log html detail error juga perlu dirender?
        // $panic->renderToFile($exception, $file);

        return $file;
    }

    /**
     * @param mixed $message
     *
     * @return void
     */
    protected function sendEmail($message)
    {
        $snooze = is_numeric($this->emailSnooze)
            ? $this->emailSnooze
            : (@strtotime($this->emailSnooze) - time());

        if (
            $this->email
            && $this->mailer
            && @filemtime($this->directory . '/email-sent') + $snooze < time()
            && @file_put_contents($this->directory . '/email-sent', 'sent')
        ) {
            call_user_func($this->mailer, $message, implode(', ', (array) $this->email));
        }
    }

    /**
     * Mailer default.
     *
     * @param mixed  $message
     * @param string $email
     *
     * @return void
     */
    public function defaultMailer($message, $email)
    {
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : php_uname('n');
        $host = preg_replace('#[^\w.-]+#', '', $host);
        $parts = str_replace(
            ["\r\n", "\n"],
            ["\n", PHP_EOL],
            [
                'headers' => implode("\n", [
                    'From: ' . ($this->fromEmail ?: "noreply@$host"),
                    'X-Mailer: Rakit debugger',
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                ]) . "\n",
                'subject' => "PHP: An error occurred on the server $host",
                'body' => static::formatMessage($message) . "\n\nsource: " . Helpers::getSource(),
            ]
        );

        mail($email, $parts['subject'], $parts['body'], $parts['headers']);
    }

    /**
     * Format nilai untuk logging dengan aman.
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
     * Format exception untuk logging.
     *
     * @param \Exception|object $e
     *
     * @return string
     */
    protected static function formatExceptionForRakitLog($e)
    {
        $class = get_class($e);
        $message = $e->getMessage();
        $file = $e->getFile();
        $line = $e->getLine();
        $trace = $e->getTraceAsString();
        $output = sprintf('[object] (%s(code: %s): %s at %s:%s)', $class, $e->getCode(), $message, $file, $line);

        if ($trace) {
            $output .= PHP_EOL . $trace;
        }

        return $output;
    }
}
