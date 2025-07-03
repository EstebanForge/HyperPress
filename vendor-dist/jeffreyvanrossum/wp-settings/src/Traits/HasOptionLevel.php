<?php

namespace HMApi\Jeffreyvr\WPSettings\Traits;

trait HasOptionLevel
{
    public $is_option_level = false;

    public function option_level($flag = true)
    {
        $this->is_option_level = $flag;

        return $this;
    }

    public function is_option_level()
    {
        return $this->is_option_level;
    }
}
