<?php

defined('DS') or exit('No direct access.');

class Dummy_Badge_Component extends Component
{
    public $label = 'empty';

    /**
     * Get the view of the component.
     *
     * @return string
     */
    public function render()
    {
        return 'dummy::components.badge';
    }
}
