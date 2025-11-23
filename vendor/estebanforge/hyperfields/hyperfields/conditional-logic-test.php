<?php

/**
 * Conditional Logic Test for Metaboxes.
 *
 * Simple test to verify field-level conditional logic functionality in metaboxes.
 */

use HyperFields\HyperFields;

// Create a test metabox with conditional fields
function hyperfields_metabox_conditional_logic_test()
{
    $container = HyperFields::makePostMeta('Conditional Logic Test', 'conditional_logic_test')
        ->where('post')
        ->setContext('normal')
        ->setPriority('high');

    $container
        ->addField(HyperFields::makeField('select', 'show_extra_fields', 'Show Extra Fields?')
        ->setOptions([
            'no' => 'No',
            'yes' => 'Yes',
        ])
        ->setDefault('no'))

        ->addField(HyperFields::makeField('text', 'extra_text_field', 'Extra Text Field')
        ->setConditionalLogic([
            'relation' => 'AND',
            'conditions' => [[
                'field' => 'show_extra_fields',
                'compare' => '=',
                'value' => 'yes',
            ]],
        ])
        ->setPlaceholder('This field is shown conditionally'))

        ->addField(HyperFields::makeField('textarea', 'extra_textarea_field', 'Extra Textarea Field')
        ->setConditionalLogic([
            'relation' => 'AND',
            'conditions' => [[
                'field' => 'show_extra_fields',
                'compare' => '=',
                'value' => 'yes',
            ]],
        ])
            ->setPlaceholder('Another conditionally shown field'));

    // Register the container
    $container->register();
}

// Activate the test
add_action('init', 'hyperfields_metabox_conditional_logic_test');
