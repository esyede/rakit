<?php

defined('DS') or exit('No direct access.');

class Probe_Transformer extends Transformer
{
    public function to_array()
    {
        return ['probe' => true];
    }
}
