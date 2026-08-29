<?php

defined('DS') or exit('No direct access.');

class Badge_Component extends Component
{
    public $label = 'none';

    public $colour = 'grey';

    /**
     * Get the view of the component.
     *
     * @return string
     */
    public function render()
    {
        return 'components.badge';
    }
}
