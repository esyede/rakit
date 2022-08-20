<?php

defined('DS') or exit('No direct script access.');

class Home_Controller extends Base_Controller
{
    /**
     * Handle GET /.
     *
     * @return View
     */
    public function action_index()
    {
        return view('home.index');
    }
}
