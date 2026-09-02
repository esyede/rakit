<?php

namespace System\Bridges;

defined('DS') or exit('No direct access.');

class Frankenphp extends Bridge
{
    /**
     * Whether the exit guard is installed.
     *
     * @var bool
     */
    protected static $exit_guard_installed = false;

    /**
     * Previous handler for chaining.
     *
     * @var callable|null
     */
    protected static $previous_handler = null;

    /**
     * Install exception handler once (preserve previous).
     *
     * @return void
     */
    public function init()
    {
        parent::init();
        if (static::$exit_guard_installed) {
            return;
        }

        static::$exit_guard_installed = true;

        static::$previous_handler = set_exception_handler(function ($e) {
            try {
                \System\Foundation\Oops\Debugger::exceptionHandler($e, false);
            } catch (\Throwable $inner) {
                if (static::$previous_handler && is_callable(static::$previous_handler)) {
                    call_user_func(static::$previous_handler, $e);
                    return;
                }

                if (! headers_sent()) {
                    http_response_code(500);
                }

                echo 'Internal Server Error';
            } catch (\Exception $inner) {
                if (static::$previous_handler && is_callable(static::$previous_handler)) {
                    call_user_func(static::$previous_handler, $e);
                    return;
                }

                if (! headers_sent()) {
                    http_response_code(500);
                }

                echo 'Internal Server Error';
            }
        });
    }

    /**
     * Wait for the next FrankenPHP request.
     *
     * @return bool
     */
    public function wait_request()
    {
        /** @disregard */
        return frankenphp_handle_request();
    }
}
