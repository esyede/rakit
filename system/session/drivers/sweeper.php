<?php

namespace System\Session\Drivers;

defined('DS') or exit('No direct access.');

interface Sweeper
{
    /**
     * Hapus seluruh session yang telah kedaluwarsa.
     *
     * @param int $expiration
     */
    public function sweep($expiration);
}
