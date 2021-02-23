<?php

namespace System\Foundation\Http;

defined('DS') or exit('No direct script access.');

class Request extends Requester
{
    /**
     * Buat object request baru menggunakan data milik PHP.
     *
     * @return Request
     */
    public static function createFromGlobals()
    {
        $request = new static($_GET, $_POST, [], $_COOKIE, $_FILES, $_SERVER);

        $type = $request->server->get('CONTENT_TYPE');
        $httpType = $request->server->get('HTTP_CONTENT_TYPE');
        $method = $request->server->get('REQUEST_METHOD', 'GET');

        if ((0 === strpos($type, 'application/x-www-form-urlencoded')
        || (0 === strpos($httpType, 'application/x-www-form-urlencoded')))
        && in_array(strtoupper($method), ['PUT', 'DELETE', 'PATCH'])) {
            parse_str($request->getContent(), $data);
            $request->request = new Parameter($data);
        }

        return $request;
    }

    /**
     * Ambil URL root aplikasi.
     *
     * @return string
     */
    public function getRootUrl()
    {
        return $this->getScheme().'://'.$this->getHttpHost().$this->getBasePath();
    }
}
