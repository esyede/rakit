<?php

defined('DS') or exit('No direct access.');

use System\Routing\Controller;

class Dashboard_Panel_Controller extends Controller
{
    public function action_index()
    {
        return 'Dashboard_Panel_Index';
    }
}
