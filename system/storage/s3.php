<?php

namespace System\Storage;

defined('DS') or exit('No direct access.');

use System\Curl;

class S3
{
    /**
     * The disk configuration.
     *
     * @var array
     */
    protected $config;

    /**
     * Constructor.
     *
     * @param array $config key, secret, region, bucket, and optionally endpoint, url, path_style, root
     */
    public function __construct(array $config)
    {
        foreach (['key', 'secret', 'region', 'bucket'] as $required) {
            if (empty($config[$required])) {
                throw new \Exception(sprintf('The S3 disk needs a "%s" in its configuration.', $required));
            }
        }

        $this->config = array_merge(['endpoint' => null, 'url' => null, 'path_style' => false, 'root' => ''], $config);
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return bool
     */
    public function exists($path)
    {
        return 200 === $this->request('HEAD', $path, '', [], [], [200, 404])->code;
    }

    /**
     * Get a file's contents, or $default when it does not exist.
     *
     * @param string $path
     * @param mixed  $default
     *
     * @return string|mixed
     */
    public function get($path, $default = null)
    {
        $response = $this->request('GET', $path, '', [], [], [200, 404]);
        return (200 === $response->code) ? $response->raw_body : $default;
    }

    /**
     * Store a file.
     *
     * @param string $path
     * @param string $contents
     * @param array  $options 'visibility' => 'public', 'content_type', 'cache_control'
     *
     * @return bool
     */
    public function put($path, $contents, array $options = [])
    {
        $contents = (string) $contents;
        $headers = ['content-type' => isset($options['content_type']) ? $options['content_type'] : static::mime($contents)];

        if (isset($options['visibility']) && 'public' === $options['visibility']) {
            $headers['x-amz-acl'] = 'public-read';
        }

        if (isset($options['cache_control'])) {
            $headers['cache-control'] = $options['cache_control'];
        }

        $this->request('PUT', $path, $contents, $headers);
        return true;
    }

    /**
     * Store an uploaded or local file.
     *
     * @param string $path
     * @param string $file
     * @param array  $options
     *
     * @return bool
     */
    public function put_file($path, $file, array $options = [])
    {
        if (! is_file($file) || false === ($contents = file_get_contents($file))) {
            throw new \Exception(sprintf('Unable to read: %s', $file));
        }

        return $this->put($path, $contents, $options);
    }

    /**
     * Delete one or more files. Deleting a missing file is not an error.
     *
     * @param string|array $paths
     *
     * @return bool
     */
    public function delete($paths)
    {
        foreach ((array) $paths as $path) {
            $this->request('DELETE', $path, '', [], [], [204, 200, 404]);
        }

        return true;
    }

    /**
     * Copy a file inside the bucket.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function copy($from, $to)
    {
        $source = '/'.$this->config['bucket'].'/'.static::encode($this->key($from));
        $this->request('PUT', $to, '', ['x-amz-copy-source' => $source]);

        return true;
    }

    /**
     * Move a file inside the bucket.
     *
     * @param string $from
     * @param string $to
     *
     * @return bool
     */
    public function move($from, $to)
    {
        return $this->copy($from, $to) && $this->delete($from);
    }

    /**
     * Get a file's size in bytes.
     *
     * @param string $path
     *
     * @return int
     */
    public function size($path)
    {
        return (int) static::header($this->request('HEAD', $path), 'content-length');
    }

    /**
     * Get when a file was last changed, as a UNIX timestamp.
     *
     * @param string $path
     *
     * @return int
     */
    public function modified($path)
    {
        return (int) strtotime((string) static::header($this->request('HEAD', $path), 'last-modified'));
    }

    /**
     * List the files under a directory, every level deep.
     *
     * @param string $directory
     *
     * @return array paths relative to the disk root
     */
    public function files($directory = '')
    {
        $prefix = trim($this->key($directory), '/');
        $prefix = ('' === $prefix) ? '' : $prefix.'/';
        $root = $this->root();
        $files = [];
        $token = null;

        do {
            $query = ['list-type' => '2', 'prefix' => $prefix];

            if (! is_null($token)) {
                $query['continuation-token'] = $token;
            }

            $xml = $this->request('GET', null, '', [], $query)->raw_body;
            preg_match_all('#<Key>(.*?)</Key>#s', $xml, $keys);

            foreach ($keys[1] as $key) {
                $key = html_entity_decode($key, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $files[] = ('' === $root) ? $key : substr($key, strlen($root));
            }

            $token = (preg_match('#<IsTruncated>true</IsTruncated>#', $xml)
                    && preg_match('#<NextContinuationToken>(.*?)</NextContinuationToken>#s', $xml, $next))
                ? html_entity_decode($next[1], ENT_QUOTES | ENT_XML1, 'UTF-8')
                : null;
        } while (! is_null($token));

        return $files;
    }

    /**
     * Get a file's public URL (the object must be public, see put()'s visibility).
     *
     * @param string $path
     *
     * @return string
     */
    public function url($path)
    {
        if (! empty($this->config['url'])) {
            return rtrim($this->config['url'], '/').'/'.static::encode($this->key($path));
        }

        list($host, $base) = $this->location();
        return $this->scheme().'://'.$host.$base.'/'.static::encode($this->key($path));
    }

    /**
     * Get a URL that downloads a private file until it expires, without credentials.
     *
     * @param string                  $path
     * @param int|\DateTime|\System\Carbon $expires seconds from now (at most 7 days), or a time
     *
     * @return string
     */
    public function temporary_url($path, $expires = 3600)
    {
        $seconds = is_object($expires) ? ($expires->getTimestamp() - time()) : (int) $expires;
        $seconds = max(1, min(604800, $seconds));

        list($host, $base) = $this->location();
        $uri = $base.'/'.static::encode($this->key($path));
        $time = $this->now();
        $scope = gmdate('Ymd', $time).'/'.$this->config['region'].'/s3/aws4_request';

        $query = [
            'X-Amz-Algorithm' => 'AWS4-HMAC-SHA256',
            'X-Amz-Credential' => $this->config['key'].'/'.$scope,
            'X-Amz-Date' => gmdate('Ymd\THis\Z', $time),
            'X-Amz-Expires' => (string) $seconds,
            'X-Amz-SignedHeaders' => 'host',
        ];

        $signature = $this->signature('GET', $uri, $query, ['host' => $host], 'UNSIGNED-PAYLOAD', $time);

        return $this->scheme().'://'.$host.$uri.'?'.static::query($query).'&X-Amz-Signature='.$signature;
    }

    /**
     * Send a signed request.
     *
     * @param string      $method
     * @param string|null $path    NULL for the bucket itself
     * @param string      $body
     * @param array       $headers lower-cased names
     * @param array       $query
     * @param array       $accept  status codes that are not errors
     *
     * @return \stdClass
     */
    protected function request($method, $path, $body = '', array $headers = [], array $query = [], array $accept = [200])
    {
        list($host, $base) = $this->location();
        $uri = $base.(is_null($path) ? '/' : '/'.static::encode($this->key($path)));
        $time = $this->now();
        $hash = hash('sha256', (string) $body);

        $headers = array_merge($headers, [
            'host' => $host,
            'x-amz-content-sha256' => $hash,
            'x-amz-date' => gmdate('Ymd\THis\Z', $time),
        ]);

        $headers['authorization'] = $this->authorization($method, $uri, $query, $headers, $hash, $time);
        $url = $this->scheme().'://'.$host.$uri.(empty($query) ? '' : '?'.static::query($query));

        unset($headers['host']);
        $response = Curl::send($method, $url, ('GET' === $method || 'HEAD' === $method) ? null : (string) $body, $headers);

        if (! in_array((int) $response->code, $accept, true)) {
            $xml = (string) $response->raw_body;
            $code = preg_match('#<Code>(.*?)</Code>#s', $xml, $match) ? $match[1] : 'HTTP '.$response->code;
            $message = preg_match('#<Message>(.*?)</Message>#s', $xml, $match) ? ': '.$match[1] : '';

            throw new \Exception(sprintf('S3 %s %s failed: %s%s', $method, is_null($path) ? '/' : $path, $code, $message));
        }

        return $response;
    }

    /**
     * Build the Authorization header (AWS Signature Version 4).
     *
     * @param string $method
     * @param string $uri
     * @param array  $query
     * @param array  $headers
     * @param string $hash
     * @param int    $time
     *
     * @return string
     */
    public function authorization($method, $uri, array $query, array $headers, $hash, $time)
    {
        $headers = array_change_key_case($headers, CASE_LOWER);
        ksort($headers);

        return sprintf(
            'AWS4-HMAC-SHA256 Credential=%s/%s/%s/s3/aws4_request,SignedHeaders=%s,Signature=%s',
            $this->config['key'],
            gmdate('Ymd', $time),
            $this->config['region'],
            implode(';', array_keys($headers)),
            $this->signature($method, $uri, $query, $headers, $hash, $time)
        );
    }

    /**
     * Sign a request.
     *
     * @param string $method
     * @param string $uri     already encoded
     * @param array  $query
     * @param array  $headers lower-cased names, every one is signed
     * @param string $hash    the payload's SHA-256, or UNSIGNED-PAYLOAD
     * @param int    $time
     *
     * @return string
     */
    protected function signature($method, $uri, array $query, array $headers, $hash, $time)
    {
        ksort($headers);
        $canonical_headers = '';

        foreach ($headers as $name => $value) {
            $canonical_headers .= $name.':'.trim(preg_replace('/\s+/', ' ', (string) $value))."\n";
        }

        $request = implode("\n", [
            $method,
            $uri,
            static::query($query),
            $canonical_headers,
            implode(';', array_keys($headers)),
            $hash,
        ]);

        $date = gmdate('Ymd', $time);
        $scope = $date.'/'.$this->config['region'].'/s3/aws4_request';
        $string = "AWS4-HMAC-SHA256\n".gmdate('Ymd\THis\Z', $time)."\n".$scope."\n".hash('sha256', $request);

        $key = hash_hmac('sha256', $date, 'AWS4'.$this->config['secret'], true);
        $key = hash_hmac('sha256', $this->config['region'], $key, true);
        $key = hash_hmac('sha256', 's3', $key, true);
        $key = hash_hmac('sha256', 'aws4_request', $key, true);

        return hash_hmac('sha256', $string, $key);
    }

    /**
     * Get the host and the path before the object key.
     *
     * @return array [host, base path]
     */
    protected function location()
    {
        $bucket = $this->config['bucket'];

        if (! empty($this->config['endpoint'])) {
            $endpoint = parse_url($this->config['endpoint']);
            $host = $endpoint['host'].(isset($endpoint['port']) ? ':'.$endpoint['port'] : '');
        } else {
            $host = 's3.'.$this->config['region'].'.amazonaws.com';
        }

        if ($this->config['path_style'] || false !== strpos($bucket, '.')) {
            return [$host, '/'.$bucket];
        }

        return [$bucket.'.'.$host, ''];
    }

    /**
     * Get the scheme of the endpoint.
     *
     * @return string
     */
    protected function scheme()
    {
        return empty($this->config['endpoint']) ? 'https' : (parse_url($this->config['endpoint'], PHP_URL_SCHEME) ?: 'https');
    }

    /**
     * Get the object key of a path: under the disk root, never climbing out of it.
     *
     * @param string $path
     *
     * @return string
     */
    protected function key($path)
    {
        $path = str_replace('\\', '/', (string) $path);

        if (in_array('..', explode('/', $path), true)) {
            throw new \Exception(sprintf('Path traversal not allowed: %s', $path));
        }

        return $this->root().ltrim($path, '/');
    }

    /**
     * Get the disk root prefix, with a trailing slash when set.
     *
     * @return string
     */
    protected function root()
    {
        $root = trim((string) $this->config['root'], '/');
        return ('' === $root) ? '' : $root.'/';
    }

    /**
     * Get the signing time.
     *
     * @return int
     */
    protected function now()
    {
        return time();
    }

    /**
     * Encode an object key for a URL, keeping its slashes.
     *
     * @param string $key
     *
     * @return string
     */
    protected static function encode($key)
    {
        return str_replace('%2F', '/', rawurlencode($key));
    }

    /**
     * Build a canonical query string: sorted, RFC 3986 encoded.
     *
     * @param array $query
     *
     * @return string
     */
    protected static function query(array $query)
    {
        ksort($query);
        $pairs = [];

        foreach ($query as $key => $value) {
            $pairs[] = rawurlencode($key).'='.rawurlencode((string) $value);
        }

        return implode('&', $pairs);
    }

    /**
     * Read a response header, whatever its case.
     *
     * @param \stdClass $response
     * @param string    $name
     *
     * @return string|null
     */
    protected static function header($response, $name)
    {
        foreach ((array) $response->headers as $key => $value) {
            if (is_string($key) && strtolower($key) === $name) {
                return is_array($value) ? end($value) : $value;
            }
        }

        return null;
    }

    /**
     * Guess the content type of contents.
     *
     * @param string $contents
     *
     * @return string
     */
    protected static function mime($contents)
    {
        if (function_exists('finfo_open') && false !== ($finfo = @finfo_open(FILEINFO_MIME_TYPE))) {
            $mime = @finfo_buffer($finfo, $contents);
            /** @disregard */
            @finfo_close($finfo);

            if ($mime) {
                return $mime;
            }
        }

        return 'application/octet-stream';
    }
}
