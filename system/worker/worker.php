<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

use System\Log;
use System\Hook;
use System\Config;
use System\Request;
use System\Response;
use System\Session;
use System\URI;
use System\Routing\Router;
use System\Foundation\Oops\Debugger;
use System\Foundation\Oops\Helpers;

class Worker
{
    /**
     * The bridge adapter instance.
     *
     * @var \System\Worker\Bridge
     */
    protected $bridge;

    /**
     * Create a new worker.
     *
     * @param \System\Worker\Bridge $bridge
     */
    public function __construct(Bridge $bridge)
    {
        $this->bridge = $bridge;
    }

    /**
     * Create a worker for the given adapter name.
     *
     * @param string $adapter  frankenphp|roadrunner|swoole
     *
     * @return static
     */
    public static function create($adapter)
    {
        $map = [
            'frankenphp' => 'System\Worker\Frankenphp',
            'roadrunner' => 'System\Worker\Roadrunner',
            'swoole' => 'System\Worker\Swoole',
        ];

        $adapter = strtolower($adapter);

        if (! isset($map[$adapter])) {
            throw new \Exception('Unknown bridge adapter: '.$adapter.'. Available: '.implode(', ', array_keys($map)));
        }

        return new static(new $map[$adapter]());
    }

    /**
     * Run the worker loop.
     *
     * @return void
     */
    public function run()
    {
        $this->bridge->init();

        $worker = $this;
        $handler = function () use ($worker) {
            $worker->handle();
        };

        while ($this->bridge->wait_request($handler)) {
            // Collect cycles between requests rather than in the middle of one.
            gc_collect_cycles();
        }
    }

    /**
     * Serve the request the bridge has just received.
     * Nothing may escape from here, an uncaught exception takes the whole worker down.
     *
     * @return void
     */
    public function handle()
    {
        $level = ob_get_level();

        try {
            $this->bridge->reset();
            ob_start();
            $this->bridge->capture();
            $response = static::dispatch();
        } catch (\Throwable $e) {
            $response = $this->error_response($e, $level);
        } catch (\Exception $e) {
            $response = $this->error_response($e, $level);
        }

        try {
            $this->bridge->send_response($response, $level);
        } catch (\Throwable $e) {
            $this->report($e);
            $this->bridge->abort($level);
        } catch (\Exception $e) {
            $this->report($e);
            $this->bridge->abort($level);
        }
    }

    /**
     * Build the response for an exception thrown while handling the request.
     *
     * @param \Throwable|\Exception $e
     * @param int                   $level
     *
     * @return \System\Response
     */
    protected function error_response($e, $level)
    {
        // Whatever the request printed so far is half a page, drop it.
        Bridge::discard($level);
        ob_start();

        try {
            $response = $this->render_exception($e);
            $response->render();

            return $response;
        } catch (\Throwable $ignored) {
            // Fall back to the plain response below
        } catch (\Exception $ignored) {
            // Fall back to the plain response below
        }

        Bridge::discard($level);
        ob_start();

        return Response::make('Internal Server Error', 500, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    /**
     * Render an exception the way the debugger would, but into a response.
     * The debugger itself writes straight to the output and may exit,
     * which would end the worker rather than the request.
     *
     * @param \Throwable|\Exception $e
     *
     * @return \System\Response
     */
    protected function render_exception($e)
    {
        if (Debugger::$productionMode) {
            $this->report($e);
            return Response::error(500);
        }

        if (Request::wants_json()) {
            return Response::json(['status' => 500, 'message' => $e->getMessage()], 500);
        }

        Helpers::improveException($e);

        ob_start();
        Debugger::getPanic()->render($e);

        return Response::make(ob_get_clean(), 500, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * Write the exception to the log.
     *
     * @param \Throwable|\Exception $e
     *
     * @return void
     */
    protected function report($e)
    {
        try {
            Log::error('Exception occurred', ['exception' => $e]);
        } catch (\Throwable $ignored) {
            // skip errors
        } catch (\Exception $ignored) {
            // skip errors
        }
    }

    /**
     * Dispatch a single request through the Rakit pipeline.
     * Mirrors the request part of boot.php so workers can call it per request.
     *
     * @return \System\Response
     */
    public static function dispatch()
    {
        // A regular request loads the session in application/boot.php,
        // which a worker runs only once, before any request arrives.
        if (filled(Config::get('session.driver'))) {
            Session::load();
        }

        // Read URI and locale.
        $languages = Config::get('application.languages', ['en']);
        $languages[] = Config::get('application.language', 'en');
        $languages = array_filter($languages, function ($lang) {
            return is_string($lang) && preg_match('/^[a-zA-Z0-9_-]+$/', $lang);
        });

        usort($languages, function ($a, $b) {
            return strlen($b) - strlen($a);
        });

        $uri = URI::current();
        $uri = (! is_string($uri) || empty($uri)) ? '/' : $uri;

        foreach ($languages as $language) {
            if (preg_match('#^'.$language.'(?:$|/)#i', $uri)) {
                Config::set('application.language', $language);
                $uri = trim(substr((string) $uri, strlen($language)), '/');
                break;
            }
        }

        URI::$uri = ('' === $uri) ? '/' : $uri;

        // Route and execute.
        $domain = Request::foundation()->getHost();
        Request::$route = Router::route(Request::method(), URI::$uri, $domain);

        // The catch-all route from boot.php only covers the HTTP methods the router knows.
        if (is_null(Request::$route)) {
            $response = Hook::first('404');
            $response = Response::prepare($response ?: Response::error(404));
        } else {
            $response = Request::$route->call();
        }

        // Persist session.
        if (Config::get('session.driver') && Session::started()) {
            Session::save();
        }

        return $response;
    }
}
