<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class SelectMultiple extends OptionAbstract
{
    public $view = 'select-multiple';

    public function get_name_attribute()
    {
        $name = parent::get_name_attribute();

        return "{$name}[]";
    }

    public function sanitize($value)
    {
        return (array) $value;
    }
}
