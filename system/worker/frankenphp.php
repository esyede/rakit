<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

class Frankenphp extends Bridge
{
    /**
     * Wait for the next FrankenPHP request and handle it. The superglobals and
     * php://input only exist inside the handler, and an escaping exception is fatal.
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
