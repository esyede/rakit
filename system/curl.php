<?php

namespace System;

defined('DS') or exit('No direct script access.');

class Curl
{
    /**
     * Kirim sebuah GET request.
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return \stdClass
     */
    public static function get($url, array $params = [], array $options = [])
    {
        return static::request('get', $url, $params, $options);
    }

    /**
     * Kirim sebuah POST request.
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return \stdClass
     */
    public static function post($url, array $params = [], array $options = [])
    {
        return static::request('post', $url, $params, $options);
    }

    /**
     * Kirim sebuah PUT request.
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return \stdClass
     */
    public static function put($url, array $params = [], array $options = [])
    {
        return static::request('put', $url, $params, $options);
    }

    /**
     * Kirim sebuah DELETE request.
     *
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return \stdClass
     */
    public static function delete($url, array $params = [], array $options = [])
    {
        return static::request('delete', $url, $params, $options);
    }

    /**
     * Kirim sebuah request.
     *
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $options
     *
     * @return \stdClass
     */
    public static function request($method, $url, array $params = [], array $options = [])
    {
        if (! static::available()) {
            throw new \RuntimeException('cURL extension is not available.');
        }

        $method = (string) $method;

        if (! in_array(strtolower($method), ['get', 'post', 'put', 'delete'])) {
            throw new \InvalidArgumentException(sprintf('Unsupported request method: %s', $method));
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_VERBOSE, get_cli_option('verbose') ? 1 : 0);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, static::agent());

        $query = empty($params) ? null : http_build_query($params, '', '&', PHP_QUERY_RFC1738);

        switch (strtolower($method)) {
            case 'get':
                $url .= $query ? '?'.$query : '';
                curl_setopt($curl, CURLOPT_HTTPGET, 1);
                break;

            case 'post':
                if ($query) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                }

                if (isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])) {
                    $options[CURLOPT_HTTPHEADER] = array_merge(
                        $options[CURLOPT_HTTPHEADER],
                        ['Content-Type: application/x-www-form-urlencoded']
                    );
                } else {
                    $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
                }

                curl_setopt($curl, CURLOPT_POST, 1);
                break;

            case 'put':
                if ($query) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                }

                if (isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])) {
                    $options[CURLOPT_HTTPHEADER] = array_merge(
                        $options[CURLOPT_HTTPHEADER],
                        ['Content-Type: application/x-www-form-urlencoded']
                    );
                } else {
                    $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
                }

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                break;

            case 'delete':
                if ($query) {
                    curl_setopt($curl, CURLOPT_POSTFIELDS, $query);
                }

                if (isset($options[CURLOPT_HTTPHEADER]) && is_array($options[CURLOPT_HTTPHEADER])) {
                    $options[CURLOPT_HTTPHEADER] = array_merge(
                        $options[CURLOPT_HTTPHEADER],
                        ['Content-Type: application/x-www-form-urlencoded']
                    );
                } else {
                    $options[CURLOPT_HTTPHEADER] = ['Content-Type: application/x-www-form-urlencoded'];
                }

                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
                break;

            default:
                throw new \Exception(sprintf('Usupported request method: %s', strtoupper($method)));
                break;
        }

        if (! empty($options)) {
            curl_setopt_array($curl, $options);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        $body = curl_exec($curl);

        if (false === $body) {
            $code = curl_errno($curl);
            $message = curl_error($curl);

            curl_close($curl);

            throw new \Exception($message, $code);
        }

        $header = (object) curl_getinfo($curl);

        curl_close($curl);

        if (false !== strpos($header->content_type, '/json')) {
            $body = json_decode($body, false, 512, JSON_BIGINT_AS_STRING | JSON_PRETTY_PRINT);
        }

        return (object) compact('header', 'body');
    }

    /**
     * Download file URL yang diberikan.
     *
     * @param string $url
     * @param string $destination
     * @param array  $options
     *
     * @return void
     */
    public static function download($url, $destination, array $options = [])
    {
        if (! static::available()) {
            throw new \Exception('cURL extension is not available.');
        }

        if (is_file($destination)) {
            throw new \Exception(sprintf('Destination path already exists: %s', $destination));
        }

        if (false === ($fopen = fopen($destination, 'w+'))) {
            throw new \Exception(sprintf('Unable to create destination path: %s', $destination));
        }

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_VERBOSE, get_cli_option('verbose') ? 1 : 0);

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);

        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_BINARYTRANSFER, 1);
        curl_setopt($curl, CURLOPT_USERAGENT, static::agent());

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_FILE, $fopen);

        if (! empty($options)) {
            curl_setopt_array($curl, $options);
        }

        $body = curl_exec($curl);

        if (false === $body) {
            $code = curl_errno($curl);
            $message = curl_error($curl);

            curl_close($curl);
            fclose($fopen);

            throw new \Exception($message, $code);
        }

        curl_close($curl);
        fclose($fopen);

        return true;
    }

    /**
     * Cek ketersediaan cURL extension.
     *
     * @return bool
     */
    public static function available()
    {
        return extension_loaded('curl') && is_callable('curl_init');
    }

    /**
     * Buat string user-agent palsu untuk request.
     * Beberapa situs seperti github menolak koneksi jika
     * request yang kita kirim tidak memiliki header User-Agent.
     *
     * @return string
     */
    public static function agent()
    {
        $year = (int) gmdate('Y');
        $year = ($year < 2020) ? 2020 : $year;

        // Buat nomor versinya bertambah mengikuti tahun.
        $version = 79 + ($year - 2020);

        $agents = [
            'Windows' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:[v].0) Gecko/20100101 Firefox/[v].0',
            'Linux' => 'Mozilla/5.0 (Linux x86_64; rv:[v].0) Gecko/20100101 Firefox/[v].0',
            'Darwin' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10.15; rv:[v].0) Gecko/20100101 Firefox/[v].0',
            'BSD' => 'Mozilla/5.0 (X11; FreeBSD amd64; rv:[v].0) Gecko/20100101 Firefox/[v].0',
            'Solaris' => 'Mozilla/5.0 (Solaris; Solaris x86_64; rv:[v].0) Gecko/20100101 Firefox/[v].0',
        ];

        $platform = static::platform();
        $platform = ('Unknown' === $platform) ? 'Linux' : $platform;

        return str_replace('[v]', $version, $agents[$platform]);
    }

    /**
     * Ambil platform / sistem operasi server.
     *
     * @return string
     */
    public static function platform()
    {
        if ('\\' === DIRECTORY_SEPARATOR) {
            return 'Windows';
        }

        $platforms = [
            'Darwin' => 'Darwin',
            'DragonFly' => 'BSD',
            'FreeBSD' => 'BSD',
            'NetBSD' => 'BSD',
            'OpenBSD' => 'BSD',
            'Linux' => 'Linux',
            'SunOS' => 'Solaris',
        ];

        return isset($platforms[PHP_OS]) ? $platforms[PHP_OS] : 'Unknown';
    }
}
