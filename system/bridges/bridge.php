<?php

namespace System\Bridges;

defined('DS') or exit('No direct access.');

use System\Request;
use System\URI;
use System\URL;
use System\Input;
use System\Session;
use System\Cookie;
use System\Container;
use System\Hook;
use System\Config;
use System\Blade;
use System\Section;
use System\Auth;
use System\Blade\Component;

abstract class Bridge
{
    /**
     * Snapshot of boot-time language to restore per request.
     *
     * @var string|null
     */
    protected static $boot_language = null;

    /**
     * Whether boot snapshot has been taken.
     *
     * @var bool
     */
    protected static $boot_snapshot = false;

    /**
     * One-time initialization after framework boots.
     * Override to install exit guards, register signals, etc.
     */
    public function init()
    {
        $this->snapshot_boot();
    }

    /**
     * Store boot-time immutable snapshot (language, etc).
     *
     * @return void
     */
    protected function snapshot_boot()
    {
        if (static::$boot_snapshot) {
            return;
        }

        static::$boot_snapshot = true;

        try {
            static::$boot_language = Config::get('application.language');
        } catch (\Throwable $e) {
            static::$boot_language = null;
        } catch (\Exception $e) {
            static::$boot_language = null;
        }
    }

    /**
     * Wait for the next request and return true, or return false to stop.
     *
     * @return bool
     */
    abstract public function wait_request();

    /**
     * Send the response back to the client.
     * Override if the adapter uses its own response API.
     *
     * @param \System\Response $response
     */
    public function send_response($response)
    {
        $response->render();
        $response->send();
        Hook::fire('rakit.done', [$response]);
        $response->foundation()->finish();
    }

    /**
     * Reset all per-request static state.
     * Boot-time state (Hook events, Router routes, Package booted) is kept.
     */
    public function reset()
    {
        $this->snapshot_boot();

        // Request / Routing - per-request only
        Request::$route = null;
        Request::$foundation = null;
        Request::reset_foundation();

        URI::$uri = null;
        URI::$segments = [];

        URL::$base = null;

        if (class_exists('System\Routing\Router')) {
            \System\Routing\Router::$package = null;
            \System\Routing\Router::$groups = [];
            \System\Routing\Router::$group = null;
            // Do NOT clear ::$routes / ::$fallback / ::$names / ::$uses / ::$compiled / ::$domains
            // they are boot-time immutable now.
        }

        // Input
        Input::$json = null;

        // Session
        Session::$instance = null;

        // Cookies
        Cookie::flush();

        // Container singletons + building tracker
        if (class_exists('System\Container')) {
            Container::flush();
        }

        // Auth driver cache (holds authenticated user)
        if (class_exists('System\Auth')) {
            Auth::$drivers = [];
        }

        // Hooks - only clear per-request queued payloads, keep events/flushers
        Hook::$queued = [];

        // Debug collectors - clear per-request traces without triggering config hooks (foundation is null here)
        if (class_exists('System\Foundation\Oops\Collectors')) {
            try {
                $ref = new \ReflectionClass('System\Foundation\Oops\Collectors');
                $prop = $ref->getProperty('data');
                $prop->setAccessible(true);
                $prop->setValue(null, [
                    'request' => [], 'routes' => [], 'events' => [], 'views' => [], 'cache' => [],
                    'logs' => [], 'timers' => [], 'exceptions' => [], 'deprecations' => [], 'http' => [], 'mails' => [],
                ]);
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            }
        }

        // Database query log - prevent memory leak across requests
        if (class_exists('System\Database\Connection')) {
            \System\Database\Connection::$queries = [];
        }

        // Blade per-request state
        Blade::reset_state();
        // Section stacks left over from a view that threw
        Section::$sections = [];
        Section::$last = [];
        Section::$stacks = [];
        // Blade component stack if view threw mid-component
        if (class_exists('System\Blade\Component')) {
            Component::unwind();
        }
        // View render counters
        if (class_exists('System\View')) {
            \System\View::$last = null;
            \System\View::$rendered = 0;
        }

        // Config: restore language mutated by Worker::dispatch per request
        if (! is_null(static::$boot_language)) {
            // Restore without invalidating unrelated caches more than needed
            try {
                Config::set('application.language', static::$boot_language);
            } catch (\Throwable $e) {
                // skip errors
            } catch (\Exception $e) {
                // skip errors
            }
        }

        // Output buffers - drain any leftover buffers from previous request
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        // Superglobals - clear per-request input, keep SERVER/COOKIE to be overwritten by populate
        $_GET = [];
        $_POST = [];
        $_FILES = [];
        $_REQUEST = [];
        // $_COOKIE and $_SERVER are repopulated by populate_globals/capture; do not wipe blindly
        // but ensure stale REQUEST_TIME etc will be overwritten
    }

    /**
     * Re-populate request state from current PHP globals.
     */
    public function capture()
    {
        Request::$foundation = \System\Foundation\Http\Request::createFromGlobals();
        Request::reset_foundation();

        Cookie::$jar = [];

        if (isset($_COOKIE) && is_array($_COOKIE)) {
            foreach ($_COOKIE as $name => $value) {
                Cookie::$jar[$name] = ['name' => $name, 'value' => $value];
            }
        }
    }
}
