<?php

/**
 * Conditional Logic Test for Metaboxes
 *
 * Simple test to verify field-level conditional logic functionality in metaboxes.
 */

use HMApi\Fields\HyperFields;

// Create a test metabox with conditional fields
function hyperfields_metabox_conditional_logic_test() {
    $container = HyperFields::makePostMeta('Conditional Logic Test', 'conditional_logic_test')
        ->where('post')
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('select', 'show_extra_fields', 'Show Extra Fields?')
            ->set_options([
                'no' => 'No',
                'yes' => 'Yes'
            ])
            ->set_default('no'))

        ->addField(HyperFields::makeField('text', 'extra_text_field', 'Extra Text Field')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'show_extra_fields',
                    'compare' => '=',
                    'value' => 'yes'
                ]]
            ])
            ->set_placeholder('This field is shown conditionally'))

        ->addField(HyperFields::makeField('textarea', 'extra_textarea_field', 'Extra Textarea Field')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'show_extra_fields',
                    'compare' => '=',
                    'value' => 'yes'
                ]]
            ])
            ->set_placeholder('Another conditionally shown field'));

    // Register the container
    $container->register();
}

// Activate the test
add_action('init', 'hyperfields_metabox_conditional_logic_test');