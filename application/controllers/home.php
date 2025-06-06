<?php

defined('DS') or exit('No direct access.');

class Home_Controller extends Controller
{
    /**
     * Buat instance controller baru.
     */
    public function __construct()
    {
        $this->middleware('before', 'csrf');
    }

    /**
     * Handle GET /.
     *
     * @return View
     */
    public function action_index()
    {
        return View::make('home');
    }
}
