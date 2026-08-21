<?php

namespace System\Foundation\Faker\Provider;

defined('DS') or exit('No direct access.');

class Phone extends Base
{
    protected static $formats = ['###-###-###'];

    public function phoneNumber()
    {
        // Note: the formats of some locales carry '{{areaCode}}' style
        // placeholders, so they have to go through the generator first. Without
        // that they ended up in the result verbatim.
        return static::numerify($this->generator->parse(static::randomElement(static::$formats)));
    }
}
