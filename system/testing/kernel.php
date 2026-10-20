<?php

namespace System\Testing;

defined('DS') or exit('No direct access.');

use System\Worker\Worker;

class Kernel extends Worker
{
    /**
     * Rethrow instead of rendering the error page: a test wants the exception, with
     * its message and trace, not a 500 page to search through.
     *
     * @param \Throwable|\Exception $e
     * @param int                   $level
     *
     * @return void
     */
    protected function error_response($e, $level)
    {
        throw $e;
    }
}
