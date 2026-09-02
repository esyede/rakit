<?php

namespace System\Bridges;

defined('DS') or exit('No direct access.');

use System\Request;
use System\Routing\Router;
use System\Config;
use System\Session;
use System\URI;

class Worker
{
    /**
     * The bridge adapter instance.
     *
     * @var \System\Bridges\Bridge
     */
    protected $bridge;

    /**
     * Create a new worker.
     *
     * @param \System\Bridges\Bridge $bridge
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
            'frankenphp' => Frankenphp::class,
            'roadrunner' => Roadrunner::class,
            'swoole' => Swoole::class,
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

        while ($this->bridge->wait_request()) {
            $this->bridge->reset();
            $this->bridge->capture();

            try {
                $response = static::dispatch();
            } catch (\Throwable $e) {
                $response = $this->error_response($e);
            } catch (\Exception $e) {
                $response = $this->error_response($e);
            }

            try {
                $this->bridge->send_response($response);
            } catch (\Throwable $e) {
                // Last resort: try to output 500
                if (! headers_sent()) {
                    http_response_code(500);
                }

                echo 'Internal Server Error';

                try {
                    \System\Hook::fire('rakit.done', [$response]);
                } catch (\Throwable $ignored) {
                    // skip errors
                } catch (\Exception $ignored) {
                    //skip errors
                }
            } catch (\Exception $e) {
                if (! headers_sent()) {
                    http_response_code(500);
                }

                echo 'Internal Server Error';
            }
        }
    }

    /**
     * Build error response for uncaught dispatch exceptions.
     *
     * @param \Throwable|\Exception $e
     * @return \System\Response
     */
    protected function error_response($e)
    {
        try {
            \System\Foundation\Oops\Debugger::exceptionHandler($e, false);
        } catch (\Throwable $ignored) {
            // skip errors
        } catch (\Exception $ignored) {
            //skip errors
        }

        // Return a minimal 500 response; bridge will send it
        return \System\Response::make('Internal Server Error', 500);
    }

    /**
     * Dispatch a single request through the Rakit pipeline.
     * Extracted from boot.php so bridges can call it per-request.
     *
     * @return \System\Response
     */
    public static function dispatch()
    {
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

        $original_language = Config::get('application.language');
        $matched = false;
        foreach ($languages as $language) {
            if (preg_match('#^'.$language.'(?:$|/)#i', $uri)) {
                Config::set('application.language', $language);
                $uri = trim(substr((string) $uri, strlen($language)), '/');
                $matched = true;
                break;
            }
        }

        URI::$uri = ('' === $uri) ? '/' : $uri;

        // Route and execute.
        $domain = Request::foundation()->getHost();
        Request::$route = Router::route(Request::method(), URI::$uri, $domain);

        // Router::route() returns null if nothing matches (boot.php always had catch-all,
        // but guard anyway for worker where fallback could be missing)
        if (is_null(Request::$route)) {
            $action = \System\Hook::first('404');
            Request::$route = is_callable($action) ? new \System\Routing\Route(Request::method(), URI::$uri, ['uses' => $action]) : null;

            if (is_null(Request::$route)) {
                return \System\Response::error(404);
            }
        }
        $response = Request::$route->call();

        // Persist session.
        if (Config::get('session.driver') && Session::started()) {
            Session::save();
        }

        return $response;
    }
}
