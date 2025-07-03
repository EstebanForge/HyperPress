<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class Textarea extends OptionAbstract
{
    public $view = 'textarea';

    public function sanitize($value)
    {
        return sanitize_textarea_field($value);
    }
}
