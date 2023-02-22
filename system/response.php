<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Response
{
    /**
     * Berisi konten response.
     *
     * @var mixed
     */
    public $content;

    /**
     * Berisi instanve http foundation response.
     *
     * @var \System\Faundation\Http\Response
     */
    protected $foundation;

    /**
     * Buat instance Response baru.
     *
     * @param mixed $content
     * @param int   $status
     * @param array $headers
     */
    public function __construct($content, $status = 200, array $headers = [])
    {
        $this->content = $content;
        $this->foundation = new Foundation\Http\Response('', $status, $headers);
    }

    /**
     * Ambil instance foundation rersponse.
     *
     * @return \System\Foundation\Http\Response
     */
    public function foundation()
    {
        return $this->foundation;
    }

    /**
     * Buat instance Response baru.
     *
     * <code>
     *
     *      // Buat sebuah instance response dengan konten berupa string
     *      return Response::make(json_encode($user));
     *
     *      // Buat sebuah instance response dengan status code kustom
     *      return Response::make('Not Found', 404);
     *
     *      // Buat sebuah instance response dengan beberapa custom headers
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
     * Buat sebuah instance response baru berupa view.
     *
     * <code>
     *
     *      // Buat sebuah instance response berupa sebuah view
     *      return Response::view('home.index');
     *
     *      // Buat sebuah instance response berupa sebuah view dan data
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
     * Buat sebuah instance response JSON.
     *
     * <code>
     *
     *      // Buat sebuah instance response berupa JSON.
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
     * Buat sebuah instance response JSONP.
     *
     * <code>
     *
     *      // Buat sebuah instance response JSONP.
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
        $headers['Content-Type'] = 'application/javascript; charset=utf-8';
        return new static($callback . '(' . json_encode($data) . ');', $status, $headers);
    }

    /**
     * Buat sebuah instance response dari Facile Model yang diubah ke JSON.
     *
     * <code>
     *
     *      // Buat sebuah instance response dari Facile Model yang diubah ke JSON
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
     * Buat instance response error.
     * Status code dari response errornya harus menggunakan HTTP status codes.
     * Error code yang dipilih juga harus cocok dengan nama file view
     * di dalam folder application/views/error/.
     * Silahkan tambahkan file view error baru jika belum ada.
     *
     * <code>
     *
     *      // Buat response 404
     *      return Response::error(404);
     *
     *      // Buat response error dengan custom header
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

        $view = View::exists('error.' . $code) ? 'error.' . $code : 'error.unknown';
        return static::view($view, compact('code', 'message'), $code, $headers);
    }

    /**
     * Buat instance response download.
     *
     * <code>
     *
     *      // Buat response download ke sebuah file
     *      return Response::download('path/to/file.jpg');
     *
     *      // Buat response download ke sebuah file dengan nama kustom
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

        $name = is_null($name) ? basename($path) : $name;
        $response = new static(Storage::get($path), 200, array_merge($headers, [
            'Content-Description' => 'File Transfer',
            'Content-Type' => Storage::mime($path),
            'Content-Transfer-Encoding' => 'binary',
            'Expires' => 0,
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Pragma' => 'public',
            'Content-Length' => Storage::size($path),
            'Content-Disposition' => sprintf('attachment; filename="%s"', $name),
        ]));

        if (Config::get('session.driver')) {
            Session::save();
        }

        // Lihat: https://www.php.net/manual/en/function.fpassthru.php#55519
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
        exit;
    }

    /**
     * Siapkan sebuah response dari value yang diberikan.
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
     * Kirim haeder dan konten response ke browser.
     */
    public function send()
    {
        $this->cookies();
        $this->foundation()->prepare(Request::foundation());
        $this->foundation()->send();
    }

    /**
     * Ubah konten response menjadi string.
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
     * Kirim semua headers ke browser.
     */
    public function send_headers()
    {
        $this->foundation()->prepare(Request::foundation());
        $this->foundation()->sendHeaders();
    }

    /**
     * Set cookie di http foundation response.
     */
    protected function cookies()
    {
        $reflector = new \ReflectionClass('\System\Foundation\Http\Cookie');

        foreach (Cookie::$jar as $name => $data) {
            $this->foundation()->headers->setCookie($reflector->newInstanceArgs(array_values($data)));
        }
    }

    /**
     * Tambahkan header ke array response headers.
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
     * Ambil headers dari http foundation response.
     *
     * @return \System\Foundation\Http\Parameter
     */
    public function headers()
    {
        return $this->foundation()->headers;
    }

    /**
     * Get / set status code response.
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
     * Merender response ketika di cast ke string.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->render();
    }
}
