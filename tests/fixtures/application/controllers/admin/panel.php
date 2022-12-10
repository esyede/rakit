<?php

defined('DS') or exit('No direct script access.');

use System\Routing\Controller;

class Admin_Panel_Controller extends Controller
{
    public function action_index()
    {
        return 'Admin_Panel_Index';
    }
}
