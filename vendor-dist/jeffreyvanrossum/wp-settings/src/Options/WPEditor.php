<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class WPEditor extends OptionAbstract
{
    public $view = 'wp-editor';

    public function sanitize($value)
    {
        return $value;
    }
}
