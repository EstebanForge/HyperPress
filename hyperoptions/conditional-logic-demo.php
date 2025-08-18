<?php

/**
 * Conditional Logic Demo
 *
 * This file demonstrates how to use field-level conditional logic
 * in HyperFields options pages.
 */

use HyperPress\Fields\HyperFields;

// Create a demo options page with conditional fields
function hyperfields_conditional_logic_demo() {
    $container = HyperFields::makeOptionPage('Conditional Logic Demo', 'conditional-logic-demo')
        ->set_icon('dashicons-admin-generic')
        ->set_position(100);

    // General Settings Section
    $general_section = $container->add_section('general', 'General Settings', 'Configure general options');

    $general_section
        ->add_field(HyperFields::makeField('select', 'layout_type', 'Layout Type')
            ->set_options([
                'default' => 'Default Layout',
                'custom' => 'Custom Layout',
                'landing' => 'Landing Page'
            ])
            ->set_default('default'))

        ->add_field(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'custom'
                ]]
            ])
            ->set_help('Add custom CSS for your custom layout'))

        ->add_field(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
            ->set_placeholder('Enter a compelling headline'))

        ->add_field(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
            ->set_placeholder('https://example.com'));

    // Advanced Settings Section
    $advanced_section = $container->add_section('advanced', 'Advanced Settings', 'Advanced configuration options');

    $advanced_section
        ->add_field(HyperFields::makeField('checkbox', 'enable_advanced', 'Enable Advanced Features')
            ->set_help('Toggle advanced features on/off'))

        ->add_field(HyperFields::makeField('number', 'cache_timeout', 'Cache Timeout (minutes)')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'enable_advanced',
                    'compare' => '=',
                    'value' => true
                ]]
            ])
            ->set_default(60)
            ->set_help('How long to cache data in minutes'))

        ->add_field(HyperFields::makeField('select', 'debug_mode', 'Debug Mode')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'enable_advanced',
                    'compare' => '=',
                    'value' => true
                ]]
            ])
            ->set_options([
                'none' => 'None',
                'basic' => 'Basic',
                'verbose' => 'Verbose'
            ])
            ->set_default('none')
            ->set_help('Set the level of debug information'));

    $container->register();
}

// Activate the demo
add_action('init', 'hyperfields_conditional_logic_demo');