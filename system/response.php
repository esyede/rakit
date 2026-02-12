<?php

namespace System;

defined('DS') or exit('No direct access.');

class Response
{
    /**
     * Contains the response content.
     *
     * @var mixed
     */
    public $content;

    /**
     * Contains the instance of http foundation response.
     *
     * @var \System\Foundation\Http\Response
     */
    protected $foundation;

    /**
     * Create a new Response instance.
     *
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     */
    public function __construct($content, $status = 200, array $headers = [])
    {
        if ($status < 100 || $status > 599) {
            throw new \Exception('Invalid HTTP status code: ' . $status);
        }

        $this->content = $content;
        $this->foundation = new Foundation\Http\Response('', $status, $headers);
    }

    /**
     * Get the instance of the foundation response.
     *
     * @return \System\Foundation\Http\Response
     */
    public function foundation()
    {
        return $this->foundation;
    }

    /**
     * Create a new Response instance.
     *
     * <code>
     *
     *      // Create a response instance with content as string
     *      return Response::make(json_encode($user));
     *
     *      // Create a response instance with custom status code
     *      return Response::make('Not Found', 404);
     *
     *      // Create a response instance with custom status code and headers
     *      return Response::make(json_encode($user), 200, ['header' => 'value']);
     *
     * </code>
     *
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     *
     * @return Response
     */
    public static function make($content, $status = 200, array $headers = [])
    {
        return new static($content, $status, $headers);
    }

    /**
     * Create a new Response instance with a view.
     *
     * <code>
     *
     *      // Create a response instance with a view
     *      return Response::view('home.index');
     *
     *      // Create a response instance with a view and data
     *      return Response::view('home.index', ['name' => 'Budi']);
     *
     * </code>
     *
     * @param string $view
     * @param array  $data
     * @param int    $status
     * @param array  $headers
     *
     * @return Response
     */
    public static function view($view, array $data = [], $status = 200, array $headers = [])
    {
        return new static(View::make($view, $data), $status, $headers);
    }

    /**
     * Create a new Response instance with JSON content.
     *
     * <code>
     *
     *      // Create a response instance with JSON content.
     *      return Response::json($data, 200, ['header' => 'value']);
     *
     * </code>
     *
     * @param mixed $data
     * @param int   $status
     * @param array $headers
     * @param int   $json_options
     *
     * @return Response
     */
    public static function json($data, $status = 200, array $headers = [], $json_options = 0)
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new static(json_encode($data, $json_options), $status, $headers);
    }

    /**
     * Create a new Response instance with JSONP content.
     *
     * <code>
     *
     *      // Create a response instance with JSONP content.
     *      return Response::jsonp('myFunctionCall', $data, 200, ['header' => 'value']);
     *
     * </code>
     *
     * @param mixed $data
     * @param int   $status
     * @param array $headers
     *
     * @return Response
     */
    public static function jsonp($callback, $data, $status = 200, array $headers = [])
    {
        if (!preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $callback)) {
            throw new \Exception('Invalid JSONP callback name: ' . $callback);
        }

        $headers['Content-Type'] = 'application/javascript; charset=utf-8';
        return new static($callback . '(' . json_encode($data) . ');', $status, $headers);
    }

    /**
     * Create a new Response instance with Facile Model content.
     *
     * <code>
     *
     *      // Create a response instance with Facile Model content.
     *      return Response::facile($data, 200, ['header' => 'value']);
     *
     * </code>
     *
     * @param Facile|array $data
     * @param int          $status
     * @param array        $headers
     *
     * @return Response
     */
    public static function facile($data, $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new static(facile_to_json($data), $status, $headers);
    }

    /**
     * Create a new Response instance with error content.
     * Status code of the error response must use HTTP status codes.
     * The error code must match the name of the view file in the application/views/error/ folder.
     * If the view file does not exist, you can add a new one there.
     *
     * <code>
     *
     *      // Create a 404 response
     *      return Response::error(404);
     *
     *      // Create a response error with custom header
     *      return Response::error(429, ['Retry-After' => 1234567]);
     *
     * </code>
     *
     * @param int $code
     *
     * @return Response
     */
    public static function error($code, array $headers = [])
    {
        $code = (int) $code;
        $message = Foundation\Http\Response::$statusTexts;
        $message = isset($message[$code]) ? $message[$code] : 'Unknown Error';

        if (Request::wants_json()) {
            $status = $code;
            return static::json(compact('status', 'message'), $code, $headers);
        }

        $view = View::exists('error.' . $code)
            ? 'error.' . $code
            : (View::exists('error.unknown') ? 'error.unknown' : false);

        if (!$view) {
            $view = Storage::get(path('system') . DS . 'foundation' . DS . 'oops' . DS . 'assets' . DS . 'debugger' . DS . '500.phtml');
            return static::make($view, 500, $headers);
        }

        return static::view($view, compact('code', 'message'), $code, $headers);
    }

    /**
     * Create a new Response instance with download content.
     *
     * <code>
     *
     *      // Create a response download to a file
     *      return Response::download('path/to/file.jpg');
     *
     *      // Create a response download to a file with a custom name
     *      return Response::download('path/to/file.jpg', 'kittens.jpg');
     *
     * </code>
     *
     * @param string $path
     * @param string $name
     * @param array  $headers
     *
     * @return Response
     */
    public static function download($path, $name = null, array $headers = [])
    {
        if (!is_file($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        $response = new static(Storage::get($path), 200, array_merge($headers, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => Storage::mime($path),
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
            'Content-Length' => Storage::size($path),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $name ?: basename($path)),
        ]));

        if (Config::get('session.driver')) {
            Session::save();
        }

        // See: https://www.php.net/manual/en/function.fpassthru.php#55519
        session_write_close();
        ob_end_clean();

        $response->send_headers();

        $chunksize = (int) Config::get('application.chunk_size', 4) * 1024;

        if ($file = fopen($path, 'rb')) {
            while (!feof($file) && 0 === connection_status() && !connection_aborted()) {
                echo fread($file, $chunksize);
                flush();
            }

            fclose($file);
        }

        Event::fire('rakit.done', [$response]);
        $response->foundation()->finish();
    }

    /**
     * Prepare a new Response instance with download content.
     *
     * @param mixed $response
     *
     * @return Response
     */
    public static function prepare($response)
    {
        return ($response instanceof Response) ? $response : new static($response);
    }

    /**
     * Send the response to the browser.
     */
    public function send()
    {
        $this->cookies();
        $this->foundation()->prepare(Request::foundation());
        $this->foundation()->send();
    }

    /**
     * Render the content of the response to a string.
     *
     * @return string
     */
    public function render()
    {
        $this->content = (is_object($this->content) && method_exists($this->content, '__toString'))
            ? $this->content->__toString()
            : (string) $this->content;

        $this->foundation()->setContent($this->content);
        return $this->content;
    }

    /**
     * Send all headers to the browser.
     */
    public function send_headers()
    {
        $this->foundation()->prepare(Request::foundation());
        $this->foundation()->sendHeaders();
    }

    /**
     * Set cookie in http foundation response.
     */
    protected function cookies()
    {
        $reflector = new \ReflectionClass('\System\Foundation\Http\Cookie');

        foreach (Cookie::$jar as $name => $data) {
            $this->foundation()->headers->setCookie($reflector->newInstanceArgs(array_values($data)));
        }
    }

    /**
     * Add a header to the response headers array.
     *
     * @param string $name
     * @param string $value
     *
     * @return Response
     */
    public function header($name, $value)
    {
        $this->foundation()->headers->set($name, $value);
        return $this;
    }

    /**
     * Set multiple headers with chaining.
     *
     * @param array $headers
     *
     * @return Response
     */
    public function with_headers(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->header($name, $value);
        }

        return $this;
    }

    /**
     * Set cookie with chaining.
     *
     * @param string $name
     * @param string $value
     * @param int    $minutes
     * @param string $path
     * @param string $domain
     * @param bool   $secure
     *
     * @return Response
     */
    public function with_cookie($name, $value = null, $minutes = 0, $path = '/', $domain = null, $secure = false)
    {
        Cookie::put($name, $value, $minutes, $path, $domain, $secure);
        return $this;
    }

    /**
     * Set status code with chaining.
     *
     * @param int $code
     *
     * @return Response
     */
    public function with_status_code($code)
    {
        $this->status($code);
        return $this;
    }

    /**
     * Get response headers.
     *
     * @return \System\Foundation\Http\Parameter
     */
    public function headers()
    {
        return $this->foundation()->headers;
    }

    /**
     * Get or set response status code.
     *
     * @param int $status
     *
     * @return mixed
     */
    public function status($status = null)
    {
        if (is_null($status)) {
            return $this->foundation()->getStatusCode();
        }

        $this->foundation()->setStatusCode($status);
        return $this;
    }

    /**
     * Render response when cast to string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
