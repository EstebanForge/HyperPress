<?php

namespace HMApi\Jeffreyvr\WPSettings;

class Error
{
    public $settings;

    public $errors;

    public function __construct($settings)
    {
        $this->errors = new \WP_Error;
        $this->settings = $settings;
    }

    public function get_all()
    {
        global $wp_settings_error;

        return $wp_settings_error[$this->settings->option_name] ?? false;
    }

    public function get($key)
    {
        $errors = $this->get_all();

        if (! is_wp_error($errors)) {
            return;
        }

        return $errors->get_error_message($key);
    }

    public function add($key, $message)
    {
        global $wp_settings_error;

        $this->errors->add($key, $message);

        $wp_settings_error[$this->settings->option_name] = $this->errors;
    }
}
