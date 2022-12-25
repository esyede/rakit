<?php

namespace System\Email\Drivers;

defined('DS') or exit('No direct script access.');

use System\Storage;

class Log extends Driver
{
    /**
     * Mulai proses transmisi data.
     *
     * @return bool
     */
    protected function transmit()
    {
        $message = $this->build();
        $message['header'] = sprintf('%s', $message['header']);
        $message['body'] = sprintf('%s', $message['body']);

        $data = date('Y-m-d') . ' - NEW EMAIL!' . PHP_EOL;
        $data .= 'To      : ' . e(static::format($this->to)) . PHP_EOL;
        $data .= 'Subject : ' . e($this->subject) . PHP_EOL;
        $data .= 'Header  : ' . e($message['header']) . PHP_EOL;
        $data .= 'Body    : ' . e($message['body']) . PHP_EOL;
        $data .= '------------------------------------------' . PHP_EOL;
        $data .= PHP_EOL;

        $path = path('storage') . 'logs' . DS . date('Y-m-d') . '.email.log.php';

        if (is_file($path)) {
            Storage::append($path, $data);
        } else {
            $guard = "<?php defined('DS') or exit('No direct script access.'); ?>" . PHP_EOL;
            Storage::put($path, $guard . $data);
        }

        return true;
    }
}
