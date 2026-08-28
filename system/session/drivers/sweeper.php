<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

interface Sweeper
{
    /**
     * Delete all sessions that have expired from storage.
     *
     * @param int $expiration
     */
    public function sweep($expiration);
}
