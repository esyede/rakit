<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct access.');

class Server extends Parameter
{
    /**
     * Get all headers.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];

        foreach ($this->parameters as $key => $value) {
            $key = (string) $key;

            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
                $headers[$key] = $value;
            }
        }

        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW']) ? $this->parameters['PHP_AUTH_PW'] : '';
        } else {
            /**
             * php-cgi under Apache does not pass the Basic Auth user and
             * password along. Adding this line to .htaccess gets them through.
             *
             *      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
             */
            $authHeader = null;

            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $authHeader = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if (null !== $authHeader && 0 === stripos((string) $authHeader, 'basic')) {
                $exploded = explode(':', base64_decode(substr((string) $authHeader, 6)), 2);

                if (2 === count($exploded)) {
                    list($headers['PHP_AUTH_USER'], $headers['PHP_AUTH_PW']) = $exploded;
                }
            }
        }

        if (isset($headers['PHP_AUTH_USER'])) {
            $basic = 'Basic '.base64_encode($headers['PHP_AUTH_USER'].':'.$headers['PHP_AUTH_PW']);
            $headers['AUTHORIZATION'] = $basic;
        }

        return $headers;
    }
}
