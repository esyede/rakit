<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Response extends Responder
{
    /**
     * Kirim response ke browser klien.
     *
     * @return Response
     */
    public function send()
    {
        $this->sendHeaders();
        $this->sendContent();

        return $this;
    }

    /**
     * Selesaikan request fastcgi.
     */
    public function finish()
    {
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request();
        }
    }
}
