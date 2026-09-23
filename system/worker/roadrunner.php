<?php

namespace System\Worker;

defined('DS') or exit('No direct access.');

use System\Response;

class Roadrunner extends Bridge
{
    /**
     * RoadRunner HTTP worker instance.
     *
     * @var \Spiral\RoadRunner\Http\HttpWorker
     */
    protected $http;

    /**
     * The request being handled.
     *
     * @var \Spiral\RoadRunner\Http\Request|null
     */
    protected $request;

    /**
     * The $_SERVER array of the worker process, the base of every request's $_SERVER.
     *
     * @var array
     */
    protected $server = [];

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
     * Connect to RoadRunner.
     *
     * @return void
     */
    public function init()
    {
        parent::init();

        if ($this->http) {
            return;
        }

        if (! class_exists('Spiral\RoadRunner\Http\HttpWorker')) {
            throw new \Exception(
                'RoadRunner support requires the spiral/roadrunner-http package: '
                . 'composer require spiral/roadrunner-http'
            );
        }

        $this->server = $_SERVER;

        /** @disregard */
        $this->http = new \Spiral\RoadRunner\Http\HttpWorker(\Spiral\RoadRunner\Worker::create());
    }

    /**
     * Wait for the next RoadRunner request and handle it.
     *
     * @param \Closure $handler
     *
     * @return bool
     */
    public function wait_request(\Closure $handler)
    {
        try {
            $request = $this->http->waitRequest();
        } catch (\Throwable $e) {
            // Only this payload is broken, the relay can still take the next one.
            $this->http->respond(400, 'Bad Request');
            return true;
        } catch (\Exception $e) {
            $this->http->respond(400, 'Bad Request');
            return true;
        }

        if (is_null($request)) {
            return false;
        }

        $this->request = $request;
        $this->responded = false;

        $handler();

        $this->request = null;

        return true;
    }

    /**
     * Fill the PHP globals from the RoadRunner request, then build the request state.
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
     * Send the response back to RoadRunner.
     *
     * @param \System\Response $response
     * @param int              $level
     */
    public function send_response(Response $response, $level)
    {
        // Anything echoed would otherwise reach STDOUT, which is RoadRunner's relay.
        $output = static::output($level);
        $foundation = $this->prepare($response);
        $headers = [];

        foreach ($foundation->headers->all() as $name => $values) {
            // RoadRunner derives it from the body it actually sends.
            if ('Content-Length' !== $name) {
                $headers[$name] = array_map('strval', $values);
            }
        }

        foreach ($foundation->headers->getCookies() as $cookie) {
            $headers['Set-Cookie'][] = (string) $cookie;
        }

        $this->http->respond($foundation->getStatusCode(), $this->body($foundation, $output), $headers);
        $this->responded = true;

        $this->done($response, $level);
    }

    /**
     * {@inheritdoc}
     */
    public function abort($level)
    {
        static::discard($level);

        if (! $this->responded) {
            $this->responded = true;
            $this->http->respond(500, 'Internal Server Error', ['Content-Type' => ['text/plain; charset=UTF-8']]);
        }
    }

    /**
     * Convert the RoadRunner request into PHP superglobals.
     *
     * @param \Spiral\RoadRunner\Http\Request $request
     */
    protected function populate($request)
    {
        // The URI is absolute: scheme and host come along with the path.
        $url = parse_url($request->uri);
        $path = isset($url['path']) ? $url['path'] : '/';
        $query = isset($url['query']) ? $url['query'] : '';
        $https = isset($url['scheme']) && 'https' === strtolower($url['scheme']);

        $server = $this->server;
        unset($server['HTTPS']);

        $server['REQUEST_METHOD'] = $request->method;
        $server['REQUEST_URI'] = $path.(('' === $query) ? '' : '?'.$query);
        $server['QUERY_STRING'] = $query;
        $server['SERVER_PROTOCOL'] = $request->protocol;
        $server['SERVER_NAME'] = isset($url['host']) ? $url['host'] : 'localhost';
        $server['SERVER_PORT'] = isset($url['port']) ? $url['port'] : ($https ? 443 : 80);
        $server['REMOTE_ADDR'] = isset($request->attributes['ipAddress'])
            ? $request->attributes['ipAddress']
            : $request->remoteAddr;
        $server['REQUEST_TIME'] = time();
        $server['REQUEST_TIME_FLOAT'] = microtime(true);
        $server['DOCUMENT_ROOT'] = rtrim(path('base'), DS);
        $server['SCRIPT_FILENAME'] = path('base').'index.php';
        $server['SCRIPT_NAME'] = '/index.php';
        $server['PHP_SELF'] = '/index.php';

        if ($https) {
            $server['HTTPS'] = 'on';
        }

        // Go keeps the Host header out of the header list.
        if (isset($url['host'])) {
            $server['HTTP_HOST'] = $url['host'].(isset($url['port']) ? ':'.$url['port'] : '');
        }

        foreach ($request->headers as $name => $values) {
            $key = strtoupper(str_replace('-', '_', $name));
            $value = implode(', ', (array) $values);

            if ('CONTENT_TYPE' === $key || 'CONTENT_LENGTH' === $key) {
                $server[$key] = $value;
            } else {
                $server['HTTP_'.$key] = $value;
            }
        }

        $post = $request->parsed ? (array) $request->getParsedBody() : [];

        $_SERVER = $server;
        $_GET = $request->query;
        $_POST = $post;
        $_COOKIE = $request->cookies;
        $_FILES = $this->files($request->uploads);
        $_REQUEST = array_merge($_GET, $_POST);

        // RoadRunner hands decoded form bodies over as JSON, so rebuild an url-encoded
        // body for PUT and PATCH forms.
        $type = isset($server['CONTENT_TYPE']) ? $server['CONTENT_TYPE'] : '';

        if (! $request->parsed) {
            $this->body = $request->body;
        } elseif (0 === stripos($type, 'application/x-www-form-urlencoded')) {
            $this->body = http_build_query($post, '', '&');
        } else {
            $this->body = '';
        }
    }

    /**
     * Map the RoadRunner upload tree to the $_FILES structure.
     *
     * @param array $uploads
     *
     * @return array
     */
    protected function files(array $uploads)
    {
        $files = [];

        foreach ($uploads as $key => $upload) {
            if (! isset($upload['name']) || ! is_string($upload['name'])) {
                $files[$key] = $this->files((array) $upload);
                continue;
            }

            $error = (int) $upload['error'];

            $files[$key] = [
                'name' => $upload['name'],
                'type' => $upload['mime'],
                'tmp_name' => (UPLOAD_ERR_OK === $error) ? $upload['tmpName'] : '',
                'error' => $error,
                'size' => (int) $upload['size'],
            ];
        }

        return $files;
    }
}
