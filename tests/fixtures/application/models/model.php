<?php

defined('DS') or exit('No direct script access.');

class Model extends \System\Database\Facile\Model
{
    public function set_setter($setter)
    {
        $this->set_attribute('setter', 'setter: ' . $setter);
    }

    public function get_getter()
    {
        return 'getter: ' . $this->get_attribute('getter');
    }
}
