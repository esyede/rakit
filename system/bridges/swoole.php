<?php

namespace System\Bridges;

defined('DS') or exit('No direct access.');

use System\Hook;
use System\Cookie;

class Swoole extends Bridge
{
    /**
     * Previous exception handler to chain.
     *
     * @var callable|null
     */
    protected $previous_handler = null;

    /**
     * Install exception handler (preserve previous).
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        $this->previous_handler = set_exception_handler(function ($e) {
            try {
                \System\Foundation\Oops\Debugger::exceptionHandler($e, false);
            } catch (\Throwable $inner) {
                if ($this->previous_handler && is_callable($this->previous_handler)) {
                    call_user_func($this->previous_handler, $e);
                    return;
                }

                if (! headers_sent()) {
                    http_response_code(500);
                }

                echo 'Internal Server Error';
            } catch (\Exception $inner) {
                if ($this->previous_handler && is_callable($this->previous_handler)) {
                    call_user_func($this->previous_handler, $e);
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
     * Wait for the next request.
     * Swoole uses event-driven model, so this always returns true.
     *
     * @return bool
     */
    public function wait_request()
    {
        return true;
    }

    /**
     * Handle a Swoole HTTP request.
     * Unified flow: reset -> populate -> capture -> dispatch -> send.
     *
     * @param mixed $request  Swoole\Http\Request
     * @param mixed $swoole_response Swoole\Http\Response
     */
    public function handle_request($request, $swoole_response)
    {
        $this->reset();
        $this->populate_globals($request, $swoole_response);
        $this->capture();

        try {
            $response = Worker::dispatch();
            $this->send_swoole_response($response, $swoole_response);
        } catch (\Throwable $e) {
            try {
                \System\Foundation\Oops\Debugger::exceptionHandler($e, false);
            } catch (\Throwable $ignored) {
                // skip errors
            } catch (\Exception $ignored) {
                //skip errors
            }

            $swoole_response->status(500);
            $swoole_response->header('Content-Type', 'text/plain; charset=utf-8');
            $swoole_response->end('Internal Server Error');
        } catch (\Exception $e) {
            try {
                \System\Foundation\Oops\Debugger::exceptionHandler($e, false);
            } catch (\Throwable $ignored) {
                // skip errors
            } catch (\Exception $ignored) {
                //skip errors
            }

            $swoole_response->status(500);
            $swoole_response->header('Content-Type', 'text/plain; charset=utf-8');
            $swoole_response->end('Internal Server Error');
        }
    }

    /**
     * Send framework Response via Swoole response object.
     *
     * @param \System\Response $response
     * @param mixed $swoole_response
     */
    protected function send_swoole_response($response, $swoole_response)
    {
        $response->render();

        // Push Cookie jar into foundation headers
        $this->push_cookies_to_foundation($response);
        $foundation = $response->foundation();
        $foundation->prepare(\System\Request::foundation());

        // Status
        $swoole_response->status($foundation->getStatusCode());

        // Headers
        foreach ($foundation->headers->all() as $name => $values) {
            // Skip cookies here, handled separately
            if (strtolower($name) === 'set-cookie') {
                continue;
            }

            foreach ((array) $values as $value) {
                $swoole_response->header($name, $value);
            }
        }

        // Cookies
        foreach ($foundation->headers->getCookies() as $cookie) {
            $swoole_response->cookie(
                $cookie->getName(),
                $cookie->getValue(),
                $cookie->getExpiresTime(),
                $cookie->getPath(),
                $cookie->getDomain(),
                $cookie->isSecure(),
                $cookie->isHttpOnly(),
                $cookie->getSameSite() ?: 'lax'
            );
        }

        // If no cookies via foundation (some drivers use jar only), also try jar
        if (empty($foundation->headers->getCookies()) && ! empty(Cookie::$jar)) {
            foreach (Cookie::$jar as $data) {
                $swoole_response->cookie(
                    $data['name'],
                    $data['value'],
                    $data['expiration'],
                    $data['path'],
                    $data['domain'] ?? '',
                    $data['secure'] ?? false,
                    true,
                    $data['samesite'] ?? 'lax'
                );
            }
        }

        Hook::fire('rakit.done', [$response]);
        $swoole_response->end($foundation->getContent());
        $foundation->finish();
    }

    /**
     * Push Cookie::$jar into Response foundation headers (mirrors System\Response::cookies).
     *
     * @param \System\Response $response
     */
    protected function push_cookies_to_foundation($response)
    {
        foreach (Cookie::$jar as $name => $data) {
            $response->foundation()->headers->setCookie(new \System\Foundation\Http\Cookie(
                $data['name'],
                $data['value'],
                $data['expiration'],
                $data['path'],
                $data['domain'] ?? null,
                $data['secure'] ?? false,
                true,
                isset($data['samesite']) ? $data['samesite'] : 'lax'
            ));
        }
    }

    /**
     * Convert Swoole request into PHP superglobals.
     *
     * @param mixed $request
     * @param mixed $swoole_response
     */
    protected function populate_globals($request, $swoole_response)
    {
        $server = isset($request->server) ? (array) $request->server : [];
        $header = isset($request->header) ? (array) $request->header : [];

        // Normalize header keys to lower
        $header_lc = [];

        foreach ($header as $k => $v) {
            $header_lc[strtolower($k)] = $v;
        }

        $method = strtoupper($server['request_method'] ?? $header_lc['x-http-method-override'] ?? 'GET');
        $uri = $server['request_uri'] ?? '/';

        // Swoole splits query_string already
        $query_string = $server['query_string'] ?? '';

        if ($query_string === '' && false !== strpos($uri, '?')) {
            $parts = explode('?', $uri, 2);
            $uri = $parts[0];
            $query_string = $parts[1];
        }

        $full_uri = $uri . ($query_string !== '' ? '?' . $query_string : '');

        $host = $header_lc['host'] ?? ($server['http_host'] ?? 'localhost');
        $server_name = explode(':', $host)[0];
        $server_port = $server['server_port'] ?? ($header_lc['x-forwarded-port'] ?? 80);
        $remote_addr = $server['remote_addr'] ?? '127.0.0.1';
        $server_protocol = $server['server_protocol'] ?? 'HTTP/1.1';
        $content_type = $header_lc['content-type'] ?? ($server['content_type'] ?? '');
        $content_length = $header_lc['content-length'] ?? ($server['content_length'] ?? null);

        $_SERVER = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $full_uri,
            'QUERY_STRING' => $query_string,
            'SERVER_NAME' => $server_name,
            'SERVER_PORT' => $server_port,
            'HTTP_HOST' => $host,
            'REMOTE_ADDR' => $remote_addr,
            'SERVER_PROTOCOL' => $server_protocol,
            'DOCUMENT_ROOT' => getcwd(),
            'SCRIPT_FILENAME' => getcwd().'/index.php',
            'SCRIPT_NAME' => '/index.php',
            'PHP_SELF' => '/index.php',
            'REQUEST_TIME' => time(),
            'REQUEST_TIME_FLOAT' => microtime(true),
        ];

        if ($content_type !== '') {
            $_SERVER['CONTENT_TYPE'] = $content_type;
            $_SERVER['HTTP_CONTENT_TYPE'] = $content_type;
        }

        if ($content_length !== null) {
            $_SERVER['CONTENT_LENGTH'] = $content_length;
            $_SERVER['HTTP_CONTENT_LENGTH'] = $content_length;
        }

        if (isset($header_lc['https']) || (isset($server['https']) && $server['https'] !== 'off')) {
            $_SERVER['HTTPS'] = 'on';
        }

        // Map all headers to HTTP_*, skipping keys (CONTENT_*) already set above
        foreach ($header as $key => $value) {
            $norm = 'HTTP_' . strtoupper(str_replace('-', '_', $key));

            if (! isset($_SERVER[$norm])) {
                $_SERVER[$norm] = $value;
            }
        }

        $_GET = isset($request->get) ? (array) $request->get : [];
        $_POST = isset($request->post) ? (array) $request->post : [];
        $_COOKIE = isset($request->cookie) ? (array) $request->cookie : [];

        // Swoole files need normalization but keep as-is; Input::file uses $_FILES directly
        $_FILES = isset($request->files) ? (array) $request->files : [];

        // Handle raw content for PUT/PATCH/DELETE json etc.
        if (isset($request->rawContent) && is_callable([$request, 'rawContent'])) {
            $raw = $request->rawContent();

            if ($raw !== '' && $raw !== false) {
                // Store for Request::foundation()->getContent() fallback via php://input emulation
                // We fake by setting content in a global that createFromGlobals will read?
                // Instead, we can override after capture: set foundation content manually
                $GLOBALS['_swoole_raw_content'] = $raw;
            }
        }

        // Also handle x-forwarded headers for trusted proxies (left to app config)

        // Rebuild $_REQUEST
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);
    }

    /**
     * Override capture to inject raw content if present.
     */
    public function capture()
    {
        parent::capture();

        if (isset($GLOBALS['_swoole_raw_content'])) {
            $raw = $GLOBALS['_swoole_raw_content'];
            // Inject into foundation request content
            $ref = new \ReflectionProperty(\System\Request::$foundation, 'content');
            // Foundation content is protected; use setter via initialize if needed
            // Simpler: set via property if accessible
            try {
                $req = \System\Request::$foundation;
                // Use reflection to set protected $content
                $rp = new \ReflectionObject($req);

                if ($rp->hasProperty('content')) {
                    $prop = $rp->getProperty('content');
                    $prop->setAccessible(true);
                    $prop->setValue($req, $raw);
                }
            } catch (\Throwable $ignored) {
                // skip errors
            } catch (\Exception $ignored) {
                //skip errors
            }

            unset($GLOBALS['_swoole_raw_content']);
        }
    }
}
