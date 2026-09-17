<?php

defined('DS') or exit('No direct access.');

class Dummy_Probe_Transformer extends Transformer
{
    public function to_array()
    {
        return ['probe' => true];
    }
}
