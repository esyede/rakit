<?php

defined('DS') or exit('No direct access.');

class Dummy_Lencana_Component extends Component
{
    public $label = 'kosong';

    /**
     * Get the view of the component.
     *
     * @return string
     */
    public function render()
    {
        return 'dummy::components.lencana';
    }
}
