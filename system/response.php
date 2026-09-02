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
            throw new \Exception('Invalid HTTP status code: '.$status);
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
     * @param string $callback
     * @param mixed  $data
     * @param int    $status
     * @param array  $headers
     *
     * @return Response
     */
    public static function jsonp($callback, $data, $status = 200, array $headers = [])
    {
        if (! is_string($callback) || ! preg_match('/^[a-zA-Z_$][a-zA-Z0-9_$]*$/', $callback)) {
            throw new \Exception('Invalid JSONP callback name: '.$callback);
        }

        $headers['Content-Type'] = 'application/javascript; charset=utf-8';
        return new static($callback.'('.json_encode($data).');', $status, $headers);
    }

    /**
     * Create a new Response instance with Facile Model content.
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
     * @param int   $code
     * @param array $headers
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

        $view = View::exists('error.'.$code) ? 'error.'.$code : (View::exists('error.unknown') ? 'error.unknown' : false);

        if (! $view) {
            ob_start();
            require path('system').'foundation'.DS.'oops'.DS.'assets'.DS.'debugger'.DS.'500.phtml';
            return static::make(ob_get_clean(), 500, $headers);
        }

        return static::view($view, compact('code', 'message'), $code, $headers);
    }

    /**
     * Create an empty response.
     *
     * @param int   $status
     * @param array $headers
     *
     * @return Response
     */
    public static function no_content($status = 204, array $headers = [])
    {
        return new static('', $status, $headers);
    }

    /**
     * Create a response that displays the given file inline in the browser
     * instead of downloading it.
     *
     * @param string $path
     * @param array  $headers
     *
     * @return Response
     */
    public static function file($path, array $headers = [])
    {
        $path = static::validate_path($path);

        $headers = array_merge([
            'Content-Type' => Storage::mime($path),
            'Content-Length' => Storage::size($path),
            'Content-Disposition' => static::disposition('inline', basename($path)),
        ], $headers);

        return new static(file_get_contents($path), 200, $headers);
    }

    /**
     * Create a new Response instance with download content.
     *
     * @param string $path
     * @param string $name
     * @param array  $headers
     *
     * @return Response
     */
    public static function download($path, $name = null, array $headers = [])
    {
        $path = static::validate_path($path);

        $response = new static('', 200, array_merge($headers, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => Storage::mime($path),
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
            'Content-Length' => Storage::size($path),
            'Content-Disposition' => static::disposition('attachment', $name ?: basename($path)),
        ]));

        if (Config::get('session.driver')) {
            Session::save();
        }

        // See: https://www.php.net/manual/en/function.fpassthru.php#55519
        session_write_close();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $response->send_headers();

        $chunksize = (int) Config::get('application.chunk_size', 4) * 1024;

        if ($file = fopen($path, 'rb')) {
            while (! feof($file) && 0 === connection_status() && ! connection_aborted()) {
                echo fread($file, $chunksize);
                flush();
            }

            fclose($file);
        }

        Hook::fire('rakit.done', [$response]);
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
        foreach (Cookie::$jar as $name => $data) {
            $this->foundation()->headers->setCookie(new Foundation\Http\Cookie(
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
    }

    /**
     * Build a Content-Disposition header. The name often comes from the request,
     * so anything that could end the quoted string or start a new header line is
     * taken out of it first.
     *
     * @param string $type
     * @param string $name
     *
     * @return string
     */
    /**
     * Validate that a file path is inside an allowed directory and is a real file.
     * Prevents path traversal disclosure.
     *
     * @param string $path
     * @return string Real path
     */
    protected static function validate_path($path)
    {
        if (!is_string($path) || '' === trim($path) || false !== strpos($path, "\0")) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        // Block stream wrappers
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        if (!is_file($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        $real = realpath($path);
        if (false === $real || !is_file($real)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        // Confine to allowed roots: storage, base, app, public
        $allowed_roots = [];
        foreach (['base', 'storage', 'app'] as $key) {
            try {
                $p = path($key);
                $rp = realpath(rtrim($p, DS));
                if ($rp) {
                    $allowed_roots[] = $rp;
                }
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            }
        }

        // Allow explicitly configured download roots via config
        $extraRoots = Config::get('application.download_roots', []);
        if (is_array($extraRoots)) {
            foreach ($extraRoots as $extra) {
                $rp = realpath($extra);
                if ($rp) {
                    $allowed_roots[] = $rp;
                }
            }
        }

        // If no allowed roots could be determined, at least ensure inside base
        // For BC, if path is outside all allowed roots but still inside base, allow
        // Otherwise block traversal like /etc/passwd which is outside base
        $inside = false;
        if (count($allowed_roots) === 0) {
            $inside = true; // fallback allow if not configured
        } else {
            foreach ($allowed_roots as $root) {
                $root = rtrim($root, DS);
                if ($real === $root || 0 === strpos($real, $root . DS)) {
                    $inside = true;
                    break;
                }
            }
        }

        if (!$inside) {
            throw new \Exception(sprintf('Target file is outside allowed directory: %s', $path));
        }

        return $real;
    }

    protected static function disposition($type, $name)
    {
        $name = str_replace(["\r", "\n", "\0", '"', '\\'], '', (string) $name);
        $name = basename($name);

        return sprintf('%s; filename="%s"', $type, ('' === $name) ? 'download' : $name);
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
