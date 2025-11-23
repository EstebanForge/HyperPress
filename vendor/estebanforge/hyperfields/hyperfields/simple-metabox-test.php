<?php

declare(strict_types=1);

/**
 * HyperFields Simple Example
 * Basic usage demonstration for HyperFields metaboxes.
 *
 * ⚠️  EXAMPLE FILE - NOT AUTO-ACTIVATED
 *
 * To use this example:
 * 1. Copy this function to your theme/plugin
 * 2. Uncomment the add_action line at the bottom
 * 3. Or manually call hyperfields_simple_example() in your code
 *
 * @since 2025-08-04
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

use HyperFields\HyperFields;

/**
 * Simple post meta example.
 */
function hyperfields_simple_metabox_test(): void
{
    $container = HyperFields::makePostMeta('test_meta', 'Test Fields')
        ->where('post')
        ->setContext('side');

    $container
        ->addField(HyperFields::makeField('text', 'test_text', 'Test Text'))
        ->addField(HyperFields::makeField('textarea', 'test_textarea', 'Test Textarea'))
        ->addField(HyperFields::makeField('checkbox', 'test_checkbox', 'Test Checkbox'));
}

// Activate with: add_action('init', 'hyperfields_simple_metabox_test');
