<?php

namespace System\Foundation\Oops;

defined('DS') or exit('No direct script access.');

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
        if (! $this->directory) {
            throw new \LogicException('Logging directory is not specified.');
        } elseif (! is_dir($this->directory)) {
            throw new \RuntimeException(
                sprintf('Logging directory cannot be found or is not directory: %s', $this->directory)
            );
        }

        $excfile = (($message instanceof \Exception) || ($message instanceof \Throwable))
            ? $this->getExceptionFile($message)
            : null;
        $line = static::formatLogLine($message, $excfile);
        $file = $this->directory.'/'.strtolower($priority ? $priority : self::INFO).'.log';

        if (! @file_put_contents($file, $line.PHP_EOL, FILE_APPEND | LOCK_EX)) {
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
        if ($message instanceof \Exception || $message instanceof \Throwable) {
            while ($message) {
                $tmp[] = (($message instanceof \ErrorException)
                    ? Helpers::errorTypeToString($message->getSeverity()).': '.$message->getMessage()
                    : Helpers::getClass($message).': '.$message->getMessage().
                        ($message->getCode() ? ' #'.$message->getCode() : '')
                ).' in '.$message->getFile().':'.$message->getLine();

                $message = $message->getPrevious();
            }

            $message = implode("\ncaused by ", $tmp);
        } elseif (! is_string($message)) {
            $message = Dumper::toText($message);
        }

        return trim($message);
    }

    /**
     * @param mixed $message
     *
     * @return string
     */
    public static function formatLogLine($message, $excfile = null)
    {
        return implode(' ', [
            @date('[Y-m-d H-i-s]'),
            preg_replace('#\s*\r?\n\s*#', ' ', static::formatMessage($message)),
            ' @  '.Helpers::getSource(),
            $excfile ? ' @@  '.basename($excfile) : null,
        ]);
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
        $dir = strtr($this->directory.'/', '\\/', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);

        foreach (new \DirectoryIterator($this->directory) as $file) {
            if (strpos($file->getBasename(), $hash)) {
                return $dir.$file;
            }
        }

        return $dir.'exception--'.@date('Y-m-d--H-i')."--$hash.html";
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
        $file = $file ? $file : $this->getExceptionFile($exception);
        $panic = $this->panic ? $this->panic : new Panic();
        $panic->renderToFile($exception, $file);

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

        if ($this->email
        && $this->mailer
        && @filemtime($this->directory.'/email-sent') + $snooze < time()
        && @file_put_contents($this->directory.'/email-sent', 'sent')) {
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
                    'From: '.($this->fromEmail ? $this->fromEmail : "noreply@$host"),
                    'X-Mailer: Rakit debugger',
                    'Content-Type: text/plain; charset=UTF-8',
                    'Content-Transfer-Encoding: 8bit',
                ])."\n",
                'subject' => "PHP: An error occurred on the server $host",
                'body' => static::formatMessage($message)."\n\nsource: ".Helpers::getSource(),
            ]
        );

        mail($email, $parts['subject'], $parts['body'], $parts['headers']);
    }
}
