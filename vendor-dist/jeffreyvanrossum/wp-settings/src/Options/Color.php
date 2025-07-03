<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

use HMApi\Jeffreyvr\WPSettings\Enqueuer;

class Color extends OptionAbstract
{
    public $view = 'color';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function enqueue()
    {
        Enqueuer::add('wp-color-picker', function () {
            wp_enqueue_script('wp-color-picker');
            wp_enqueue_style('wp-color-picker');

            wp_add_inline_script('wp-color-picker', 'jQuery(function($){
                $(\'.wps-color-picker\').wpColorPicker();
            })');
        });
    }
}
