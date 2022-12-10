<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;

class Home_Controller extends Controller
{
    /**
     * Handler untuk request ke root aplikasi.
     *
     * @return View
     */
    public function action_index()
    {
        return view('home.index');
    }
}
