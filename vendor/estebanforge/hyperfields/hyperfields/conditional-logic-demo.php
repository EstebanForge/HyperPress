<?php

/**
 * Conditional Logic Demo for Metaboxes
 *
 * This file demonstrates how to use field-level conditional logic
 * in HyperFields metaboxes.
 */

use HyperPress\Fields\HyperFields;

// Create a demo metabox with conditional fields
function hyperfields_metabox_conditional_logic_demo() {
    $container = HyperFields::makePostMeta('Post Conditional Logic Demo', 'post_conditional_demo')
        ->where('post')
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('select', 'post_layout', 'Layout Type')
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
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'custom'
                ]]
            ])
        ->setHelp('Add custom CSS for your custom layout'))

        ->addField(HyperFields::makeField('text', 'landing_headline', 'Landing Headline')
        ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
        ->setPlaceholder('Enter a compelling headline'))

        ->addField(HyperFields::makeField('url', 'cta_url', 'Call-to-Action URL')
        ->setConditionalLogic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'post_layout',
                    'compare' => '=',
                    'value' => 'landing'
                ]]
            ])
        ->setPlaceholder('https://example.com'))

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
}

// Activate the demo
add_action('init', 'hyperfields_metabox_conditional_logic_demo');
