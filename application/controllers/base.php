<?php

defined('DS') or exit('No direct script access.');

class Base_Controller extends Controller
{
    /**
     * Jalankan CSRF middleware di setiap POST request.
     */
    public function __construct()
    {
        $this->middleware('before', 'csrf')->on('post');
    }

    /**
     * Handle request yang tidak cocok dengan definisi rute yang ada.
     *
     * @param string $method
     * @param array  $parameters
     *
     * @return Response
     */
    public function __call($method, array $parameters)
    {
        return abort(404);
    }
}
