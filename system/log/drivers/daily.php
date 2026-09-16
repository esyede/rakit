<?php

namespace System\Log\Drivers;

defined('DS') or exit('No direct access.');

class Daily extends Single
{
    /**
     * Get the path of today's log file.
     *
     * @param array $record
     *
     * @return string
     */
    protected function path(array $record)
    {
        $directory = rtrim((string) $this->option('directory', path('storage').'logs'), '\\/');
        return $directory.DS.$this->name($record).'_'.$record['datetime']->format('Y-m-d').'.log.php';
    }

    /**
     * Run after the log file has been created.
     * A new file means a new day, so this is when the old files get pruned.
     *
     * @param string $file
     * @param array  $record
     *
     * @return void
     */
    protected function created($file, array $record)
    {
        parent::created($file, $record);

        $days = (int) $this->option('days', 0);

        if ($days < 1) {
            return;
        }

        $cutoff = clone $record['datetime'];
        $cutoff = $cutoff->modify(sprintf('-%d days', $days))->format('Y-m-d');
        $files = glob(dirname($file).DS.$this->name($record).'_*.log.php');

        if (! is_array($files)) {
            return;
        }

        foreach ($files as $old) {
            if (preg_match('/_(\d{4}-\d{2}-\d{2})\.log\.php$/', $old, $matches) && $matches[1] <= $cutoff) {
                @unlink($old);
            }
        }
    }
}
