<?php

/**
 * Conditional Logic Demo for Metaboxes
 *
 * This file demonstrates how to use field-level conditional logic
 * in HyperFields metaboxes.
 */

use HMApi\Fields\HyperFields;

// Create a demo metabox with conditional fields
function hyperfields_metabox_conditional_logic_demo() {
    $container = HyperFields::makePostMeta('Post Conditional Logic Demo', 'post_conditional_demo')
        ->where('post')
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('select', 'post_layout', 'Layout Type')
            ->set_options([
                'default' => 'Default Layout',
                'custom' => 'Custom Layout',
                'landing' => 'Landing Page'
            ])
            ->set_default('default'))

        ->addField(HyperFields::makeField('textarea', 'custom_css', 'Custom CSS')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'custom'
                ]]
            ])
            ->set_help('Add custom CSS for your custom layout'))

        ->addField(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
            ->set_placeholder('Enter a compelling headline'))

        ->addField(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
            ->set_placeholder('https://example.com'))

        ->addField(HyperFields::makeField('checkbox', 'enable_advanced', 'Enable Advanced Features')
            ->set_help('Toggle advanced features on/off'))

        ->addField(HyperFields::makeField('number', 'cache_timeout', 'Cache Timeout (minutes)')
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

        ->addField(HyperFields::makeField('select', 'debug_mode', 'Debug Mode')
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
}

// Activate the demo
add_action('init', 'hyperfields_metabox_conditional_logic_demo');