<?php

defined('DS') or exit('No direct access.');

class Example_Job extends Jobable
{
    /**
     * Sample job command.
     *
     * @return void
     */
    public function run(array $arguments = [])
    {
        $message = $this->get('message', 'Example_Job executed');
        return $message;
    }
}
