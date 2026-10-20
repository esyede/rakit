<?php

namespace System\Testing;

defined('DS') or exit('No direct access.');

use System\Config;
use System\Session;
use System\Worker\Worker;

trait Http
{
    /**
     * Headers sent with every following request.
     *
     * @var array
     */
    protected $http_headers = [];

    /**
     * Cookies kept between requests, name => sealed value, as a browser keeps them.
     *
     * @var array
     */
    protected $http_cookies = [];

    /**
     * The CSRF token of the session the cookies carry.
     *
     * @var string|null
     */
    protected $http_token;

    /**
     * The user logged in for following requests.
     *
     * @var mixed
     */
    protected $http_user;

    /**
     * Send headers with the following requests.
     *
     * @param array $headers
     *
     * @return $this
     */
    public function with_headers(array $headers)
    {
        $this->http_headers = array_merge($this->http_headers, $headers);
        return $this;
    }

    /**
     * Send cookies with the following requests (plain values; they are sealed here).
     *
     * @param array $cookies
     *
     * @return $this
     */
    public function with_cookies(array $cookies)
    {
        foreach ($cookies as $name => $value) {
            $this->http_cookies[$name] = \System\Cookie::seal($name, (string) $value);
        }

        return $this;
    }

    /**
     * Log a user in for the following requests, without going through the login form.
     *
     * @param mixed $user
     *
     * @return $this
     */
    public function acting_as($user)
    {
        $this->http_user = $user;
        return $this;
    }

    /**
     * Forget the cookies, the session and the acting user, as a fresh browser.
     *
     * @return $this
     */
    public function forget_client()
    {
        $this->http_cookies = [];
        $this->http_token = null;
        $this->http_user = null;

        return $this;
    }

    /**
     * Send a GET request.
     *
     * @param string $uri
     * @param array  $headers
     *
     * @return Response
     */
    public function get($uri, array $headers = [])
    {
        return $this->call('GET', $uri, [], $headers);
    }

    /**
     * Send a POST request with form data.
     *
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return Response
     */
    public function post($uri, array $data = [], array $headers = [])
    {
        return $this->call('POST', $uri, $data, $headers);
    }

    /**
     * Send a PUT request with form data.
     *
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return Response
     */
    public function put($uri, array $data = [], array $headers = [])
    {
        return $this->call('PUT', $uri, $data, $headers);
    }

    /**
     * Send a PATCH request with form data.
     *
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return Response
     */
    public function patch($uri, array $data = [], array $headers = [])
    {
        return $this->call('PATCH', $uri, $data, $headers);
    }

    /**
     * Send a DELETE request.
     *
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return Response
     */
    public function delete($uri, array $data = [], array $headers = [])
    {
        return $this->call('DELETE', $uri, $data, $headers);
    }

    /**
     * Send a request with a JSON body, asking for JSON back.
     *
     * @param string $method
     * @param string $uri
     * @param array  $data
     * @param array  $headers
     *
     * @return Response
     */
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $headers = array_merge(['Content-Type' => 'application/json', 'Accept' => 'application/json'], $headers);
        return $this->call($method, $uri, [], $headers, json_encode($data));
    }

    /**
     * Send a request through the application and return its response.
     *
     * @param string      $method
     * @param string      $uri
     * @param array       $parameters
     * @param array       $headers
     * @param string|null $content
     *
     * @return Response
     */
    public function call($method, $uri, array $parameters = [], array $headers = [], $content = null)
    {
        $method = strtoupper($method);
        $headers = array_merge($this->http_headers, $headers);
        $safe = in_array($method, ['GET', 'HEAD', 'OPTIONS'], true);

        if (! $safe && ! $this->has_header($headers, 'X-Csrf-Token') && Config::get('session.driver')) {
            if (is_null($this->http_token)) {
                $this->start_session();
            }

            $headers['X-Csrf-Token'] = $this->http_token;
        }

        $globals = [$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES];
        $this->globals($method, $uri, $parameters, $headers);

        $bridge = new Bridge();
        $bridge->content = $content;
        $bridge->user = $this->http_user;

        $kernel = new Kernel($bridge);
        $level = ob_get_level();

        try {
            $kernel->handle();
        } catch (\Throwable $e) {
            $this->clean_up($level, $globals);
            throw $e;
        } catch (\Exception $e) {
            $this->clean_up($level, $globals);
            throw $e;
        }

        $this->clean_up($level, $globals);
        $this->keep_cookies();

        list($response, $foundation, $body) = $bridge->sent;

        return new Response($response, $foundation, $body);
    }

    /**
     * Fill the PHP globals for a request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $parameters
     * @param array  $headers
     */
    protected function globals($method, $uri, array $parameters, array $headers)
    {
        $path = '/'.ltrim((string) parse_url($uri, PHP_URL_PATH), '/');
        $query = [];
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        $server = [
            'REQUEST_METHOD' => $method,
            'REQUEST_URI' => $path.(empty($query) ? '' : '?'.http_build_query($query)),
            'QUERY_STRING' => http_build_query($query),
            'SCRIPT_NAME' => '/index.php',
            'SCRIPT_FILENAME' => path('base').'index.php',
            'HTTP_HOST' => 'localhost',
            'SERVER_NAME' => 'localhost',
            'SERVER_PORT' => 80,
            'REMOTE_ADDR' => '127.0.0.1',
            'REQUEST_TIME' => time(),
        ];

        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $key = in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_'.$key;
            $server[$key] = $value;
        }

        $_SERVER = array_merge($_SERVER, $server);
        $_GET = ('GET' === $method) ? array_merge($query, $parameters) : $query;
        $_POST = ('GET' === $method) ? [] : $parameters;
        $_COOKIE = $this->http_cookies;
        $_FILES = [];
    }

    /**
     * Start a session before the first request that needs its CSRF token.
     */
    protected function start_session()
    {
        $globals = [$_SERVER, $_GET, $_POST, $_COOKIE, $_FILES];
        $this->globals('GET', '/', [], []);

        $bridge = new Bridge();
        $bridge->reset();
        $bridge->capture();

        Session::load();
        $this->http_token = Session::token();
        Session::save();

        $this->keep_cookies();
        list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES) = $globals;
    }

    /**
     * Keep the cookies the response set, and drop the ones it removed.
     */
    protected function keep_cookies()
    {
        list($set, $removed) = Bridge::cookies();

        $this->http_cookies = array_merge($this->http_cookies, $set);

        foreach ($removed as $name) {
            unset($this->http_cookies[$name]);
        }

        if (Session::started()) {
            $this->http_token = Session::token();
        }
    }

    /**
     * Close the buffers a request left open, end the worker flag, put the globals back.
     *
     * @param int   $level
     * @param array $globals
     */
    protected function clean_up($level, array $globals)
    {
        Bridge::discard($level);
        Worker::$handling = false;

        list($_SERVER, $_GET, $_POST, $_COOKIE, $_FILES) = $globals;
    }

    /**
     * Check whether a header is among the given ones, ignoring case.
     *
     * @param array  $headers
     * @param string $name
     *
     * @return bool
     */
    protected function has_header(array $headers, $name)
    {
        foreach (array_keys($headers) as $header) {
            if (0 === strcasecmp($header, $name)) {
                return true;
            }
        }

        return false;
    }
}
