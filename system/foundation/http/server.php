<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Server extends Parameter
{
    /**
     * Ambil seluruh data header.
     *
     * @return array
     */
    public function getHeaders()
    {
        $headers = [];

        foreach ($this->parameters as $key => $value) {
            if (0 === strpos($key, 'HTTP_')) {
                $headers[substr($key, 5)] = $value;
            } elseif (in_array($key, ['CONTENT_LENGTH', 'CONTENT_MD5', 'CONTENT_TYPE'])) {
                $headers[$key] = $value;
            }
        }

        if (isset($this->parameters['PHP_AUTH_USER'])) {
            $headers['PHP_AUTH_USER'] = $this->parameters['PHP_AUTH_USER'];
            $headers['PHP_AUTH_PW'] = isset($this->parameters['PHP_AUTH_PW'])
                ? $this->parameters['PHP_AUTH_PW']
                : '';
        } else {
            /**
             * Secara default, php-cgi dibawah apache tidak mengoper user/password http basic auth
             * Untuk menangani masalah ini, tambahkan rule berikut ke file .htaccess anda:.
             *
             * RewriteCond %{HTTP:Authorization} ^(.+)$
             *
             * <code>
             *
             *      RewriteEngine On
             *      RewriteCond %{HTTP:Authorization} ^(.+)$
             *
             *      RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}] # baris ini
             *
             *      RewriteCond %{REQUEST_FILENAME} !-f
             *      RewriteRule ^(.*)$ index.php [QSA,L]
             *
             * <code>
             */
            $authHeader = null;

            if (isset($this->parameters['HTTP_AUTHORIZATION'])) {
                $authHeader = $this->parameters['HTTP_AUTHORIZATION'];
            } elseif (isset($this->parameters['REDIRECT_HTTP_AUTHORIZATION'])) {
                $authHeader = $this->parameters['REDIRECT_HTTP_AUTHORIZATION'];
            }

            if ((null !== $authHeader)
            && (0 === stripos($authHeader, 'basic'))) {
                $exploded = explode(':', base64_decode(substr($authHeader, 6)));

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
