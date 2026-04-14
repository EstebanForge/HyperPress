<?php

/**
 * Test file for HyperFields React integration.
 *
 * This file demonstrates how to use ReactField instances.
 * Add this to your theme's functions.php or a plugin to test.
 */

use HyperFields\Field;
use HyperFields\Field\ReactField;
use HyperFields\OptionsPage;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register a test options page with React fields.
 */
function hyperfields_react_test_page()
{
    $page = OptionsPage::make('React Test Page', 'hyperfields-react-test')
        ->setMenuTitle('React Test')
        ->setCapability('manage_options')
        ->setOptionName('hyperfields_react_test_options');

    // Section 1: Basic HTML fields (for comparison)
    $section1 = $page->addSection('html-fields', 'HTML Fields', 'Traditional HTML rendering');
    $section1
        ->addField(
            Field::make('text', 'html_text', 'HTML Text Field')
                ->setDefault('This is a traditional HTML field')
                ->setHelp('This field uses the classic HTML rendering')
        )
        ->addField(
            Field::make('checkbox', 'html_checkbox', 'HTML Checkbox')
                ->setDefault(false)
                ->setHelp('Traditional checkbox field')
        );

    // Section 2: React-enhanced fields
    $section2 = $page->addSection('react-fields', 'React Fields', 'React-enhanced rendering');
    $section2
        ->addField(
            ReactField::make('text', 'react_text', 'React Text Field')
                ->setDefault('This is a React field!')
                ->setHelp('This field uses React rendering with modern UI')
        )
        ->addField(
            ReactField::make('number', 'react_number', 'React Number Field')
                ->setDefault(42)
                ->setHelp('Number input with min/max support')
        )
        ->addField(
            ReactField::make('email', 'react_email', 'React Email Field')
                ->setPlaceholder('user@example.com')
                ->setHelp('Email validation included')
        )
        ->addField(
            ReactField::make('url', 'react_url', 'React URL Field')
                ->setPlaceholder('https://example.com')
                ->setHelp('URL field with validation')
        )
        ->addField(
            ReactField::make('textarea', 'react_textarea', 'React Textarea')
                ->setDefault('Multi-line text input')
                ->setHelp('Textarea with React rendering')
                ->setReactProp('rows', 6)
        );

    // Section 3: Complex React fields
    $section3 = $page->addSection('complex-react', 'Complex React Fields', 'Advanced React components');
    $section3
        ->addField(
            ReactField::make('color', 'brand_color', 'Brand Color')
                ->setDefault('#2271b1')
                ->setHelp('WordPress color picker with React')
                ->setReactProp('alpha', false)
        )
        ->addField(
            ReactField::make('image', 'site_logo', 'Site Logo')
                ->setHelp('Upload a logo using the media library')
                ->setReactProp('maxWidth', 400)
                ->setReactProp('maxHeight', 200)
        )
        ->addField(
            ReactField::make('image', 'hero_image', 'Hero Background Image')
                ->setHelp('Hero section background')
                ->setReactProp('maxWidth', 1200)
                ->setReactProp('maxHeight', 600)
                ->setReactProp('buttonLabel', 'Choose Hero Image')
        )
        ->addField(
            ReactField::make('checkbox', 'enable_feature', 'Enable Feature')
                ->setDefault(true)
                ->setHelp('Toggle switch with React rendering')
        )
        ->addField(
            ReactField::make('select', 'select_option', 'Select Option')
                ->setDefault('option1')
                ->setHelp('Dropdown select with React')
                ->setOptions([
                    'option1' => 'Option 1',
                    'option2' => 'Option 2',
                    'option3' => 'Option 3',
                ])
        );

    $page->register();
}

// Hook into admin_menu to register the page
add_action('admin_menu', 'hyperfields_react_test_page', 20);

/**
 * Optional: Add a settings link to the plugins page.
 */
function hyperfields_react_test_settings_link($links)
{
    $settings_link = '<a href="admin.php?page=hyperfields-react-test">React Test</a>';
    array_push($links, $settings_link);

    return $links;
}
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'hyperfields_react_test_settings_link');
