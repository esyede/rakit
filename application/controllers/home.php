<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;
use System\View;

class Home_Controller extends Controller
{
    /**
     * Buat instance controller baru.
     */
    public function __construct()
    {
        $this->middleware('before', 'csrf|throttle:60,1');
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
