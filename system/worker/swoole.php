<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

use System\Response;

class Swoole extends Bridge
{
    /**
     * Worker that serves the requests.
     *
     * @var \System\Worker\Worker|null
     */
    protected $worker;

    /**
     * The request being handled.
     *
     * @var \Swoole\Http\Request|null
     */
    protected $request;

    /**
     * The response of the request being handled.
     *
     * @var \Swoole\Http\Response|null
     */
    protected $response;

    /**
     * Raw body of the request being handled.
     *
     * @var string
     */
    protected $body = '';

    /**
     * Whether the current request has been answered.
     *
     * @var bool
     */
    protected $responded = false;

    /**
     * Swoole pushes requests through events instead of a blocking loop.
     *
     * @param \Closure $handler
     *
     * @return bool
     */
    public function wait_request(\Closure $handler)
    {
        throw new \LogicException(
            'Swoole dispatches requests through events: call handle_request() from the "request" callback of your Swoole\Http\Server.'
        );
    }

    /**
     * Handle a Swoole HTTP request.
     *
     * @param \Swoole\Http\Request  $request
     * @param \Swoole\Http\Response $response
     */
    public function handle_request($request, $response)
    {
        if (! $this->worker) {
            $this->init();
            $this->worker = new Worker($this);
        }

        $this->request = $request;
        $this->response = $response;
        $this->responded = false;

        $this->worker->handle();

        $this->request = null;
        $this->response = null;
    }

    /**
     * Fill the PHP globals from the Swoole request, then build the request state.
     */
    public function capture()
    {
        $this->populate($this->request);
        parent::capture();
    }

    /**
     * {@inheritdoc}
     */
    protected function content()
    {
        return $this->body;
    }

    /**
     * Send the response through the Swoole response object.
     *
     * @param \System\Response $response
     * @param int              $level
     */
    public function send_response(Response $response, $level)
    {
        $output = static::output($level);
        $foundation = $this->prepare($response);

        $this->response->status($foundation->getStatusCode());

        foreach ($foundation->headers->all() as $name => $values) {
            // Swoole derives it from the body it actually sends.
            if ('Content-Length' !== $name) {
                // Swoole keeps a single value per header name.
                $this->response->header($name, implode(', ', $values));
            }
        }

        foreach ($foundation->headers->getCookies() as $cookie) {
            $this->response->cookie(
                $cookie->getName(),
                (string) $cookie->getValue(),
                (int) $cookie->getExpiresTime(),
                (string) $cookie->getPath(),
                (string) $cookie->getDomain(),
                (bool) $cookie->isSecure(),
                (bool) $cookie->isHttpOnly(),
                (string) $cookie->getSameSite()
            );
        }

        $this->response->end($this->body($foundation, $output));
        $this->responded = true;

        $this->done($response, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function abort($level)
    {
        static::discard($level);

        if (! $this->responded && $this->response) {
            $this->responded = true;
            $this->response->status(500);
            $this->response->header('Content-Type', 'text/plain; charset=UTF-8');
            $this->response->end('Internal Server Error');
        }
    }

    /**
     * Convert the Swoole request into PHP superglobals.
     *
     * @param \Swoole\Http\Request $request
     */
    protected function populate($request)
    {
        $server = isset($request->server) ? array_change_key_case((array) $request->server, CASE_UPPER) : [];
        $headers = isset($request->header) ? (array) $request->header : [];

        $uri = isset($server['REQUEST_URI']) ? $server['REQUEST_URI'] : '/';
        $query = isset($server['QUERY_STRING']) ? $server['QUERY_STRING'] : '';

        if ('' === $query && false !== ($position = strpos($uri, '?'))) {
            $query = (string) substr($uri, $position + 1);
            $uri = substr($uri, 0, $position);
        }

        $host = isset($headers['host']) ? $headers['host'] : 'localhost';

        $server['REQUEST_METHOD'] = isset($server['REQUEST_METHOD']) ? strtoupper($server['REQUEST_METHOD']) : 'GET';
        $server['REQUEST_URI'] = $uri.(('' === $query) ? '' : '?'.$query);
        $server['QUERY_STRING'] = $query;
        $server['SERVER_NAME'] = preg_replace('/:\d+$/', '', $host);
        $server['SERVER_PORT'] = isset($server['SERVER_PORT']) ? $server['SERVER_PORT'] : 80;
        $server['SERVER_PROTOCOL'] = isset($server['SERVER_PROTOCOL']) ? $server['SERVER_PROTOCOL'] : 'HTTP/1.1';
        $server['REMOTE_ADDR'] = isset($server['REMOTE_ADDR']) ? $server['REMOTE_ADDR'] : '127.0.0.1';
        $server['DOCUMENT_ROOT'] = rtrim(path('base'), DS);
        $server['SCRIPT_FILENAME'] = path('base').'index.php';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PHP_SELF'] = '/index.php';

        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));

            if ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
                $server[$key] = $value;
            } else {
                $server['HTTP_'.$key] = $value;
            }
        }

        $_SERVER = $server;
        $_GET = isset($request->get) ? (array) $request->get : [];
        $_POST = isset($request->post) ? (array) $request->post : [];
        $_COOKIE = isset($request->cookie) ? (array) $request->cookie : [];
        $_FILES = isset($request->files) ? (array) $request->files : [];
        $_REQUEST = array_merge($_GET, $_POST);

        $body = $request->rawContent();
        $this->body = is_string($body) ? $body : '';
    }
}
