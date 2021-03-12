<?php

defined('DS') or exit('No direct script access.');

class Base_Controller extends Controller
{
    /**
     * Handle request yang tidak cocok dengan definsi rute yang ada.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Response
     */
    public function __call($method, $parameters)
    {
        return Response::error('404');
    }
}
