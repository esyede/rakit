<?php

defined('DS') or exit('No direct access.');

class Home_Controller extends Controller
{
    /**
     * Constructor.
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
        bd([[get_declared_classes(), get_declared_classes()]]);
        return View::make('home');
    }
}
