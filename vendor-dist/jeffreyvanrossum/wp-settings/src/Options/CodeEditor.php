<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

class CodeEditor extends OptionAbstract
{
    public $view = 'code-editor';

    public function __construct($section, $args = [])
    {
        add_action('wp_settings_before_render_settings_page', [$this, 'enqueue']);

        parent::__construct($section, $args);
    }

    public function enqueue()
    {
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');

        $settings_name = str_replace('-', '_', $this->get_id_attribute());

        wp_localize_script('jquery', $settings_name, wp_enqueue_code_editor(['type' => $this->get_arg('editor_type', 'text/html')]));

        wp_add_inline_script('wp-theme-plugin-editor', 'jQuery(function($){
            if($("#'.$this->get_id_attribute().'").length > 0) {
                wp.codeEditor.initialize($("#'.$this->get_id_attribute().'"), '.$settings_name.');
            }
        });');
    }

    public function sanitize($value)
    {
        return $value;
    }
}
