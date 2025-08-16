<?php

/**
 * Conditional Logic Test
 *
 * Simple test to verify field-level conditional logic functionality.
 */

use HMApi\Fields\HyperFields;

// Create a test options page with conditional fields
function hyperfields_conditional_logic_test() {
    $container = HyperFields::makeOptionPage('Conditional Logic Test', 'conditional-logic-test')
        ->set_icon('dashicons-admin-generic')
        ->set_position(100);

    // Test Section
    $test_section = $container->add_section('test', 'Test Section', 'Test conditional logic functionality');

    $test_section
        ->add_field(HyperFields::makeField('select', 'show_extra_fields', 'Show Extra Fields?')
            ->set_options([
                'no' => 'No',
                'yes' => 'Yes'
            ])
            ->set_default('no'))

        ->add_field(HyperFields::makeField('text', 'extra_text_field', 'Extra Text Field')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'show_extra_fields',
                    'compare' => '=',
                    'value' => 'yes'
                ]]
            ])
            ->set_placeholder('This field is shown conditionally'))

        ->add_field(HyperFields::makeField('textarea', 'extra_textarea_field', 'Extra Textarea Field')
            ->set_conditional_logic([
                'relation' => 'AND',
                'conditions' => [[
                    'field' => 'show_extra_fields',
                    'compare' => '=',
                    'value' => 'yes'
                ]]
            ])
            ->set_placeholder('Another conditionally shown field'));

    $container->register();
}

// Activate the test
add_action('init', 'hyperfields_conditional_logic_test');