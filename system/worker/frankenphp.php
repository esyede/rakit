<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

class Frankenphp extends Bridge
{
    /**
     * Wait for the next FrankenPHP request and handle it.
     * FrankenPHP fills the superglobals and php://input only while the handler runs,
     * and turns an exception escaping from it into a fatal error.
     *
     * @param \Closure $handler
     *
     * @return bool
     */
    public function wait_request(\Closure $handler)
    {
        /** @disregard */
        return (bool) \frankenphp_handle_request($handler);
    }
}
