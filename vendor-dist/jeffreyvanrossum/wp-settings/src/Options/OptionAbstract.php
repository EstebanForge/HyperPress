<?php

namespace HMApi\Jeffreyvr\WPSettings\Options;

use HMApi\Adbar\Dot;
use function HMApi\Jeffreyvr\WPSettings\view as view;

abstract class OptionAbstract
{
    public $section;

    public $args = [];

    public $view;

    public function __construct($section, $args = [])
    {
        $this->section = $section;
        $this->args = $args;
    }

    public function render()
    {
        return view('options/'.$this->view, ['option' => $this]);
    }

    public function has_error()
    {
        return $this->section->tab->settings->errors->get($this->get_arg('name'));
    }

    public function sanitize($value)
    {
        return sanitize_text_field($value);
    }

    public function validate($value)
    {
        return true;
    }

    public function get_arg($key, $fallback = null)
    {
        if (empty($this->args[$key])) {
            return $fallback;
        }

        if (\is_callable($this->args[$key])) {
            return $this->args[$key]();
        }

        return $this->args[$key];
    }

    public function get_label()
    {
        return \esc_attr($this->get_arg('label'));
    }

    public function get_id_attribute()
    {
        return $this->get_arg('id', sanitize_title($this->get_name_attribute()));
    }

    public function get_name()
    {
        return $this->get_arg('name');
    }

    public function get_css()
    {
        return $this->get_arg('css', []);
    }

    public function get_input_class_attribute()
    {
        $class = $this->get_css()['input_class'] ?? null;

        return ! empty($class) ? esc_attr($class) : null;
    }

    public function get_label_class_attribute()
    {
        $class = $this->get_css()['label_class'] ?? null;

        return ! empty($class) ? esc_attr($class) : null;
    }

    public function get_name_attribute()
    {
        $keys = explode('.', $this->get_option_key_path());

        $wrapped = array_map(function ($key) {
            return '['.$key.']';
        }, $keys);

        $inputName = implode('', $wrapped);

        return $this->section->tab->settings->option_name.$inputName;
    }

    public function get_option_key_path()
    {
        $keys = [];

        if ($this->section->tab->is_option_level()) {
            $keys[] = str_replace('-', '_', $this->section->tab->slug);
        }

        if ($this->section->is_option_level()) {
            $keys[] = str_replace('-', '_', $this->section->slug);
        }

        $keys[] = $this->get_arg('name');

        return implode('.', $keys);
    }

    public function get_default_value()
    {
        return $this->args['default'] ?? null;
    }

    public function get_value_attribute()
    {
        $options = get_option($this->section->tab->settings->option_name);

        $dot = new Dot($options);

        return $dot->get($this->get_option_key_path(), $this->get_default_value());
    }
}
