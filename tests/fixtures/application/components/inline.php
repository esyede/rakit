<?php

defined('DS') or exit('No direct access.');

class Inline_Component extends Component
{
    public $text = '';

    /**
     * Get what the component renders to.
     *
     * @return string
     */
    public function render()
    {
        return '<b>' . e($this->text) . '</b>';
    }
}
