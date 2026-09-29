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
     * Whether the body was already streamed to the client (Response::download()).
     *
     * @var bool
     */
    public $streamed = false;

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
     * @param \System\Database\Facile\Model|array $data
     * @param int                                 $status
     * @param array                               $headers
     *
     * @return Response
     */
    public static function facile($data, $status = 200, array $headers = [])
    {
        $headers['Content-Type'] = 'application/json; charset=utf-8';
        return new static(facile_to_json($data), $status, $headers);
    }

    /**
     * Create an error response. The HTTP status code names the view file in
     * application/views/error/.
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

        $view = View::exists('error.'.$code)
            ? 'error.'.$code
            : (View::exists('error.unknown') ? 'error.unknown' : false);

        if (! $view) {
            ob_start();
            require path('system').'foundation'.DS.'oops'.DS.'assets'.DS.'debugger'.DS.'500.phtml';
            return static::make(ob_get_clean(), ($code >= 400 && $code <= 599) ? $code : 500, $headers);
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
     * Create a response that displays a file inline instead of downloading it.
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
            'Content-Type' => static::mime($path),
            'Content-Length' => filesize($path),
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

        $response = new static('', 200, array_merge([
            'Content-Description' => 'File Transfer',
            'Content-Type' => static::mime($path),
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
            'Content-Length' => filesize($path),
            'Content-Disposition' => static::disposition('attachment', $name ?: basename($path)),
        ], $headers));

        if (defined('RAKIT_WORKER_MODE') && 'frankenphp' !== RAKIT_WORKER_MODE) {
            $response->content = file_get_contents($path);
            return $response;
        }

        if (Config::get('session.driver')) {
            Session::save();
        }

        session_write_close();

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $response->send_headers();
        $chunksize = max(1, (int) Config::get('application.chunk_size', 4)) * 1024 * 1024;

        if ($file = fopen($path, 'rb')) {
            while (! feof($file) && 0 === connection_status() && ! connection_aborted()) {
                echo fread($file, $chunksize);
                flush();
            }

            fclose($file);
        }

        $response->streamed = true;

        return $response;
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
        if ($this->streamed) {
            return;
        }

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
     * Validate that a path is a real file inside an allowed directory.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function validate_path($path)
    {
        if (!is_string($path) || '' === trim($path) || false !== strpos($path, "\0")) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        if (preg_match('#(?:^|[\\\\/])\.\.(?:[\\\\/]|$)#', $path)) {
            throw new \Exception(sprintf('Path traversal not allowed: %s', $path));
        }

        if (!is_file($path)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        $real = realpath($path);
        if (false === $real || !is_file($real)) {
            throw new \Exception(sprintf('Target file does not exists: %s', $path));
        }

        $allowed_roots = [];

        foreach (['base', 'storage', 'app'] as $key) {
            try {
                $p = path($key);
                $rp = realpath(rtrim($p, DS));

                if ($rp) {
                    $allowed_roots[] = $rp;
                }
            } catch (\Throwable $e) {
                // ignore errors
            } catch (\Exception $e) {
                // ignore errors
            }
        }

        $extra_roots = Config::get('application.download_roots', []);

        if (is_array($extra_roots)) {
            foreach ($extra_roots as $extra) {
                $rp = realpath($extra);

                if ($rp) {
                    $allowed_roots[] = $rp;
                }
            }
        }

        $inside = false;

        if (count($allowed_roots) === 0) {
            $inside = true;
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

    /**
     * Detect the MIME type of an already validated file.
     *
     * @param string $path
     *
     * @return string
     */
    protected static function mime($path)
    {
        $finfo = function_exists('finfo_open') ? @finfo_open(FILEINFO_MIME_TYPE) : false;
        $mime = $finfo ? @finfo_file($finfo, $path) : false;

        if ($finfo && PHP_VERSION_ID < 80100) {
            finfo_close($finfo);
        }

        return $mime ?: 'application/octet-stream';
    }

    /**
     * Build a Content-Disposition header, stripping anything in the name that could
     * end the quoted string or start a new header line.
     *
     * @param string $type
     * @param string $name
     *
     * @return string
     */
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
     * @param string $samesite
     *
     * @return Response
     */
    public function with_cookie(
        $name,
        $value = '',
        $minutes = 0,
        $path = '/',
        $domain = null,
        $secure = false,
        $samesite = 'lax'
    ) {
        Cookie::put($name, $value, $minutes, $path, $domain, $secure, $samesite);
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
     * @return \System\Foundation\Http\Helper
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
