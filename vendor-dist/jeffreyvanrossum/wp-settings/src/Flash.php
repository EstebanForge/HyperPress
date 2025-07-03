<?php

namespace HMApi\Jeffreyvr\WPSettings;

class Flash
{
    public $settings;

    public function __construct($settings)
    {
        $this->settings = $settings;
    }

    public function has()
    {
        global $wp_settings_flash;

        return $wp_settings_flash[$this->settings->option_name] ?? null;
    }

    public function set($status, $message)
    {
        global $wp_settings_flash;

        $wp_settings_flash[$this->settings->option_name] = compact('status', 'message');
    }
}
