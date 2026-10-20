<?php

namespace System\Exceptions;

defined('DS') or exit('No direct access.');

use System\Response;

class HttpResponseException extends \RuntimeException
{
    /**
     * Contains the response to send instead of the current one.
     *
     * @var \System\Response
     */
    protected $response;

    /**
     * Constructor.
     *
     * @param \System\Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;

        parent::__construct('HTTP response exception', $response->status());
    }

    /**
     * Get the response to send.
     *
     * @return \System\Response
     */
    public function getResponse()
    {
        return $this->response;
    }
}
