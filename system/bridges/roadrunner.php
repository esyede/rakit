<?php

namespace System\Bridges;

defined('DS') or exit('No direct access.');

use System\Cookie;

class Roadrunner extends Bridge
{
    /**
     * RoadRunner worker instance.
     *
     * @var mixed
     */
    protected $rr_worker;

    /**
     * Current PSR-7 request (for files handling).
     *
     * @var mixed
     */
    protected $psr_request;

    /**
     * Previous exception handler.
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
     * Wait for the next RoadRunner request.
     *
     * @return bool
     */
    public function wait_request()
    {
        if (! $this->rr_worker) {
            /** @disregard */
            $this->rr_worker = \Spiral\RoadRunner\Worker::create();
        }

        try {
            $request = $this->rr_worker->waitRequest();
        } catch (\Throwable $e) {
            if (class_exists('Spiral\\RoadRunner\\Http\\Exception\\StopWorkerException')
                && $e instanceof \Spiral\RoadRunner\Http\Exception\StopWorkerException) {
                return false;
            }

            throw $e;
        } catch (\Exception $e) {
            if (class_exists('Spiral\\RoadRunner\\Http\\Exception\\StopWorkerException')
                && $e instanceof \Spiral\RoadRunner\Http\Exception\StopWorkerException) {
                return false;
            }

            throw $e;
        }

        if ($request === null) {
            return false;
        }

        $this->psr_request = $request;
        $this->populate_globals($request);

        return true;
    }

    /**
     * Send the response back to RoadRunner.
     *
     * @param \System\Response $response
     */
    public function send_response($response)
    {
        $response->render();

        // Push Cookie jar into foundation (mirrors System\Response::cookies)
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

        $foundation = $response->foundation();
        $foundation->prepare(\System\Request::foundation());

        $headers = [];

        foreach ($foundation->headers->all() as $name => $values) {
            // Skip Set-Cookie from all(), we handle via cookies separate? Actually getCookies will emit them as headers too.
            // Keep them; implode is fine for RoadRunner.
            $headers[$name] = implode(', ', $values);
        }

        // Add cookies as Set-Cookie headers if not already present in all()
        foreach ($foundation->headers->getCookies() as $cookie) {
            // Nyholm expects header string, we add via headers array merging is already done above via headers->all() ?
            // Some implementations require separate; we ensure header present
            $headers['Set-Cookie'][] = $cookie->__toString();
        }

        // Normalize Set-Cookie if array
        if (isset($headers['Set-Cookie']) && is_array($headers['Set-Cookie'])) {
            $headers['Set-Cookie'] = implode("\r\n", $headers['Set-Cookie']);
        }

        /** @disregard */
        $psr_response = new \Nyholm\Psr7\Response(
            $foundation->getStatusCode(),
            $headers,
            $foundation->getContent()
        );

        $this->rr_worker->respond($psr_response);

        \System\Hook::fire('rakit.done', [$response]);
        $foundation->finish();
    }

    /**
     * Convert RoadRunner PSR-7 request into PHP superglobals.
     *
     * @param mixed $request
     */
    protected function populate_globals($request)
    {
        $server = $request->getServerParams();
        $server['REQUEST_METHOD'] = $request->getMethod();
        $uri = $request->getUri();
        $server['REQUEST_URI'] = (string) $uri;
        // Preserve original path+query
        $path = $uri->getPath();
        $query = $uri->getQuery();
        $server['QUERY_STRING'] = $query ?: '';

        if (empty($server['REQUEST_URI'])) {
            $server['REQUEST_URI'] = $path . ($query ? '?' . $query : '');
        }

        // Ensure required SERVER keys
        $server['SERVER_NAME'] = $server['SERVER_NAME'] ?? ($uri->getHost() ?: 'localhost');
        $server['SERVER_PORT'] = $server['SERVER_PORT'] ?? ($uri->getPort() ?: 80);
        $server['HTTP_HOST'] = $server['HTTP_HOST'] ?? $uri->getHost();
        $server['SERVER_PROTOCOL'] = $server['SERVER_PROTOCOL'] ?? 'HTTP/1.1';
        $server['REMOTE_ADDR'] = $server['REMOTE_ADDR'] ?? '127.0.0.1';
        $server['REQUEST_TIME'] = $server['REQUEST_TIME'] ?? time();
        $server['REQUEST_TIME_FLOAT'] = $server['REQUEST_TIME_FLOAT'] ?? microtime(true);

        $_SERVER = $server;
        $_GET = $request->getQueryParams();
        $parsed = $request->getParsedBody();

        // RoadRunner may return object; normalize to array
        if (is_object($parsed)) {
            $parsed = (array) $parsed;
        }

        $_POST = is_array($parsed) ? $parsed : [];
        $_COOKIE = $request->getCookieParams();
        $_FILES = $this->map_uploaded_files($request->getUploadedFiles());
        $_REQUEST = array_merge($_GET, $_POST, $_COOKIE);

        $GLOBALS['rr_request'] = $request;

        // Store raw body for foundation content
        $body = (string) $request->getBody();

        if ($body !== '') {
            $GLOBALS['_rr_raw_body'] = $body;
        }
    }

    /**
     * Map PSR-7 UploadedFileInterface tree to $_FILES structure.
     *
     * @param array $uploadedFiles
     * @return array
     */
    protected function map_uploaded_files(array $uploadedFiles)
    {
        $result = [];

        foreach ($uploadedFiles as $key => $file) {
            if (is_array($file)) {
                $result[$key] = $this->map_uploaded_files($file);
            } elseif (class_exists('Psr\Http\Message\UploadedFileInterface')
                && $file instanceof \Psr\Http\Message\UploadedFileInterface) {
                // Emulate $_FILES entry
                $result[$key] = [
                    'name' => $file->getClientFilename(),
                    'type' => $file->getClientMediaType(),
                    'tmp_name' => $file->getStream()->getMetadata('uri') ?: tempnam(sys_get_temp_dir(), 'rr'),
                    'error' => $file->getError(),
                    'size' => $file->getSize(),
                ];

                // If stream uri is not a file, write it
                if (! is_file($result[$key]['tmp_name']) || filesize($result[$key]['tmp_name']) === 0) {
                    $tmp = tempnam(sys_get_temp_dir(), 'rr_upload');
                    file_put_contents($tmp, (string) $file->getStream());
                    $result[$key]['tmp_name'] = $tmp;
                }
            }
        }

        return $result;
    }

    /**
     * Override capture to inject raw body.
     */
    public function capture()
    {
        parent::capture();

        if (isset($GLOBALS['_rr_raw_body'])) {
            $raw = $GLOBALS['_rr_raw_body'];

            try {
                $req = \System\Request::$foundation;
                $rp = new \ReflectionObject($req);

                if ($rp->hasProperty('content')) {
                    $prop = $rp->getProperty('content');
                    $prop->setAccessible(true);
                    $prop->setValue($req, $raw);
                }
            } catch (\Throwable $e) {
                // skip errors
            } catch (\Exception $e) {
                // skip errors
            }

            unset($GLOBALS['_rr_raw_body']);
        }
    }
}
