<?php

/**
 * Conditional Logic Test.
 *
 * Simple test to verify field-level conditional logic functionality.
 */

use HyperFields\HyperFields;

// Create a test options page with conditional fields
function hyperfields_conditional_logic_test()
{
    $container = HyperFields::makeOptionPage('Conditional Logic Test', 'conditional-logic-test')
        ->set_icon('dashicons-admin-generic')
        ->setPosition(100);

    // Test Section
    $test_section = $container->addSection('test', 'Test Section', 'Test conditional logic functionality');

    $test_section
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

    $container->register();
}

// Activate the test
add_action('init', 'hyperfields_conditional_logic_test');
