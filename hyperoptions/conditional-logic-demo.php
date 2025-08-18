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
    $general_section = $container->addSection('general', 'General Settings', 'Configure general options');

    $general_section
        ->addField(HyperFields::makeField('select', 'layout_type', 'Layout Type')
            ->setOptions([
                'default' => 'Default Layout',
                'custom' => 'Custom Layout',
                'landing' => 'Landing Page'
            ])
        ->setDefault('default'))

        ->addField(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
            ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'custom'
                ]]
            ])
        ->setHelp('Add custom CSS for your custom layout'))

        ->addField(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
            ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
        ->setPlaceholder('Enter a compelling headline'))

        ->addField(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
            ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'layout_type',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
            ->setPlaceholder('https://example.com'));

    // Advanced Settings Section
    $advanced_section = $container->addSection('advanced', 'Advanced Settings', 'Advanced configuration options');

    $advanced_section
        ->addField(HyperFields::makeField('checkbox', 'enable_advanced', 'Enable Advanced Features')
            ->setHelp('Toggle advanced features on/off'))

        ->addField(HyperFields::makeField('number', 'cache_timeout', 'Cache Timeout (minutes)')
            ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'enable_advanced',
                    'compare' => '=',
                    'value' => true
                ]]
            ])
        ->setDefault(60)
        ->setHelp('How long to cache data in minutes'))

        ->addField(HyperFields::makeField('select', 'debug_mode', 'Debug Mode')
            ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'enable_advanced',
                    'compare' => '=',
                    'value' => true
                ]]
            ])
        ->setOptions([
                'none' => 'None',
                'basic' => 'Basic',
                'verbose' => 'Verbose'
            ])
            ->setDefault('none')
            ->setHelp('Set the level of debug information'));

    $container->register();
}

// Activate the demo
add_action('init', 'hyperfields_conditional_logic_demo');
