<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

use System\Auth;
use System\Blade;
use System\Config;
use System\Container;
use System\Cookie;
use System\Hook;
use System\Input;
use System\Request;
use System\Response;
use System\Section;
use System\Session;
use System\URI;
use System\URL;
use System\View;
use System\Blade\Component;
use System\Routing\Router;
use System\Foundation\Oops\Debugger;
use System\Foundation\Oops\Collectors;
use System\Foundation\Http\Cookie as FoundationCookie;
use System\Foundation\Http\Request as FoundationRequest;

abstract class Bridge
{
    /**
     * Framework state taken right after the worker booted.
     * It is restored at the start of every request.
     *
     * @var array|null
     */
    protected static $snapshot;

    /**
     * One-time initialization after the framework boots.
     */
    public function init()
    {
        if (! is_null(self::$snapshot)) {
            return;
        }

        self::$snapshot = [
            'language' => Config::get('application.language'),
            'env' => Request::env(),
            'singletons' => Container::$singletons,
        ];
    }

    /**
     * Wait for the next request and run the handler for it.
     * Return FALSE once the server asks the worker to stop.
     *
     * @param \Closure $handler
     *
     * @return bool
     */
    abstract public function wait_request(\Closure $handler);

    /**
     * Get the raw body of the current request.
     * NULL lets the foundation read it from php://input.
     *
     * @return string|null
     */
    protected function content()
    {
        return null;
    }

    /**
     * Send the response back to the client.
     * This implementation suits servers that emit PHP output natively (FrankenPHP).
     *
     * @param \System\Response $response
     * @param int              $level
     */
    public function send_response(Response $response, $level)
    {
        $response->render();
        $response->send();

        Hook::fire('rakit.done', [$response]);

        // Anything the request echoed is still buffered in front of the body,
        // just like behind the buffer index.php opens for a regular request.
        while (ob_get_level() > $level && static::removable()) {
            ob_end_flush();
        }
    }

    /**
     * Answer with a bare 500 after sending the real response failed.
     *
     * @param int $level
     */
    public function abort($level)
    {
        static::discard($level);

        if (! headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo 'Internal Server Error';
        }
    }

    /**
     * Reset all per-request static state.
     * Boot-time state (hook listeners, routes, booted packages) is kept.
     */
    public function reset()
    {
        $this->init();

        Request::$route = null;
        Request::$foundation = null;
        Request::reset_foundation();

        URI::$uri = null;
        URI::$segments = [];
        URL::$base = null;

        // Routes stay registered, only the registration context is dropped.
        Router::$package = null;
        Router::$groups = [];
        Router::$group = null;

        Input::$json = null;
        Session::$instance = null;
        Cookie::flush();
        Hook::$queued = [];

        // Singletons resolved while serving a request may hold that request's
        // data, so only the ones that already existed after booting survive.
        Container::flush();
        Container::$singletons = self::$snapshot['singletons'];

        if (class_exists('System\Auth', false)) {
            Auth::$drivers = [];
        }

        if (class_exists('System\Database\Connection', false)) {
            \System\Database\Connection::$queries = [];
        }

        Blade::reset_state();

        // A view that threw leaves its sections, components and counters behind.
        if (class_exists('System\Section', false)) {
            Section::$sections = [];
            Section::$last = [];
            Section::$stacks = [];
        }

        if (class_exists('System\Blade\Component', false)) {
            Component::unwind();
        }

        if (class_exists('System\View', false)) {
            View::$last = null;
            View::$rendered = 0;
        }

        // Worker::dispatch() switches the language when the URI carries a locale.
        if (Config::get('application.language') !== self::$snapshot['language']) {
            Config::set('application.language', self::$snapshot['language']);
        }
    }

    /**
     * Build the request state from the current PHP globals.
     */
    public function capture()
    {
        Request::$foundation = FoundationRequest::createFromGlobals($this->content());
        Request::reset_foundation();

        // The environment was stamped on the request that booted the worker.
        if (! is_null(self::$snapshot['env']) && ! Request::$foundation->server->has('RAKIT_ENV')) {
            Request::set_env(self::$snapshot['env']);
        }

        // Collectors record every event, view and query of the request. Nothing
        // renders the debug bar in a worker, so they would only keep growing.
        if (! Debugger::$productionMode && class_exists('System\Foundation\Oops\Collectors', false)) {
            Collectors::reset();
        }
    }

    /**
     * Drop the output buffers opened above the given level.
     *
     * @param int $level
     */
    public static function discard($level)
    {
        while (ob_get_level() > $level && static::removable()) {
            ob_end_clean();
        }
    }

    /**
     * Close the output buffers opened above the given level and return their content.
     *
     * @param int $level
     *
     * @return string
     */
    protected static function output($level)
    {
        $output = '';

        while (ob_get_level() > $level && static::removable()) {
            $output = ob_get_clean().$output;
        }

        return $output;
    }

    /**
     * Check whether the topmost output buffer can be cleaned, flushed and removed.
     * Failing to do so raises a notice, and the debugger's scream mode reports
     * even the silenced ones.
     *
     * @return bool
     */
    protected static function removable()
    {
        $status = ob_get_status();

        return isset($status['flags'])
            && PHP_OUTPUT_HANDLER_STDFLAGS === ($status['flags'] & PHP_OUTPUT_HANDLER_STDFLAGS);
    }

    /**
     * Render the response and prepare its foundation for servers that take
     * the status, headers and body as values instead of PHP output.
     *
     * @param \System\Response $response
     *
     * @return \System\Foundation\Http\Response
     */
    protected function prepare(Response $response)
    {
        $response->render();

        // Same as Response::cookies(), which only runs inside Response::send().
        foreach (Cookie::$jar as $data) {
            $response->foundation()->headers->setCookie(new FoundationCookie(
                $data['name'],
                $data['value'],
                $data['expiration'],
                $data['path'],
                $data['domain'],
                $data['secure'],
                true,
                isset($data['samesite']) ? $data['samesite'] : 'lax'
            ));
        }

        return $response->foundation()->prepare(Request::foundation());
    }

    /**
     * Get the body to send, with the output the request echoed in front of it.
     *
     * @param \System\Foundation\Http\Response $foundation
     * @param string                           $output
     *
     * @return string
     */
    protected function body($foundation, $output)
    {
        if ($foundation->isInformational() || $foundation->isEmpty() || 'HEAD' === Request::foundation()->getMethod()) {
            return '';
        }

        return $output.$foundation->getContent();
    }

    /**
     * Fire the 'rakit.done' event once the client has its response.
     * Whatever the listeners print has nowhere to go, so it is dropped.
     *
     * @param \System\Response $response
     * @param int              $level
     */
    protected function done(Response $response, $level)
    {
        ob_start();
        Hook::fire('rakit.done', [$response]);
        static::discard($level);
    }
}
