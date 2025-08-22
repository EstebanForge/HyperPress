<?php

/**
 * Example: Creating an Options Page with Hyper Fields (Helper Functions)
 *
 * This demonstrates how to use the helper functions API
 * to create plugin/theme options pages in WordPress.
 */

declare(strict_types=1);

use HyperPress\Fields\HyperFields;

// No imports needed - functions are available globally in the namespace

// Example 1: Basic Plugin Options Page
$plugin_options = HyperFields::makeOptionPage(__('My Plugin Settings', 'api-for-htmx'), 'my-plugin-settings')
    ->setMenuTitle(__('My Plugin', 'api-for-htmx'))
    ->setParentSlug('options-general.php')
    ->setFooterContent('<span>' . __('Demo Footer: Powered by HyperFields', 'api-for-htmx') . '</span>');

// Add sections and fields
$general_section = $plugin_options->addSection('general', __('General Settings', 'api-for-htmx'), __('Configure basic plugin settings', 'api-for-htmx'));
$general_section->addField(
    HyperFields::makeField('text', 'plugin_title', __('Plugin Title', 'api-for-htmx'))
        ->setDefault(__('My Awesome Plugin', 'api-for-htmx'))
        ->setPlaceholder(__('Enter plugin title...', 'api-for-htmx'))
);

$general_section->addField(
    HyperFields::makeField('textarea', 'plugin_description', __('Plugin Description', 'api-for-htmx'))
        ->setPlaceholder(__('Describe your plugin...', 'api-for-htmx'))
        ->setHelp(__('This description will appear in the plugin header.', 'api-for-htmx'))
);

$general_section->addField(
    HyperFields::makeField('color', 'primary_color', __('Primary Color', 'api-for-htmx'))
        ->setDefault('#007cba')
        ->setHelp(__('Choose the primary color for your plugin interface', 'api-for-htmx'))
);

$general_section->addField(
    HyperFields::makeField('image', 'plugin_logo', __('Plugin Logo', 'api-for-htmx'))
        ->setHelp(__('Recommended size: 300x100px', 'api-for-htmx'))
);

$general_section->addField(
    HyperFields::makeField('checkbox', 'enable_feature_x', __('Enable Feature X', 'api-for-htmx'))
        ->setDefault(true)
        ->setHelp(__('Turn on the advanced Feature X functionality', 'api-for-htmx'))
);

// API Endpoint field using helper
$api_url = hp_get_endpoint_url();

$general_section->addField(
    HyperFields::makeField('html', 'api_endpoint', __('API Endpoint', 'api-for-htmx'))
        ->setHtmlContent('<div><input type="text" readonly value="' . esc_attr($api_url) . '" style="width:100%" /></div>')
        ->setHelp(__('This is the base API endpoint for your integration.', 'api-for-htmx'))
);

// HTML/script field demo
$general_section->addField(
    HyperFields::makeField('html', 'custom_script', __('Custom Script Demo', 'api-for-htmx'))
        ->setHtmlContent('<button id="demo-btn">' . esc_html__('Click Me', 'api-for-htmx') . '</button><script>document.getElementById("demo-btn").onclick=function(){alert("' . esc_js(__('Hello from HyperFields!', 'api-for-htmx')) . '");}</script>')
        ->setHelp(__('Demo of HTML field with script.', 'api-for-htmx'))
);

// Example 2: Advanced Settings with Tabs
$advanced_section = $plugin_options->addSection('advanced', 'Advanced Settings', 'Configure advanced functionality');

// Tabs field for organizing complex settings
$tabs_field = HyperFields::makeTabs('settings_tabs', 'Configuration Tabs')
    ->addTab('api', 'API Settings', [
    HyperFields::makeField('text', 'api_key', 'API Key')
    ->setPlaceholder('Enter your API key...')
    ->setRequired(true),
    HyperFields::makeField('url', 'api_endpoint', 'API Endpoint')
    ->setDefault('https://api.example.com/v1')
    ->setRequired(true),
    HyperFields::makeField('select', 'api_version', 'API Version')
    ->setOptions(['v1' => 'Version 1', 'v2' => 'Version 2'])
    ->setDefault('v1')
    ])
    ->addTab('notifications', 'Notifications', [
    HyperFields::makeField('email', 'notification_email', 'Notification Email')
    ->setDefault(get_option('admin_email')),
    HyperFields::makeField('multiselect', 'notification_types', 'Notification Types')
    ->setOptions([
        'new_user' => 'New User Registration',
        'new_order' => 'New Orders',
        'system_errors' => 'System Errors'
        ])
    ]);

$advanced_section->addField($tabs_field);

// Example 3: Repeater Field for Multiple Items
$repeater_field = HyperFields::makeRepeater('social_links', 'Social Media Links')
    ->setMinRows(1)
    ->setMaxRows(10)
    ->setLabelTemplate('{platform} ({url})')
    ->addSubField(
        HyperFields::makeField('select', 'platform', 'Platform')
        ->setOptions([
                'facebook' => 'Facebook',
                'twitter' => 'Twitter',
                'instagram' => 'Instagram',
                'linkedin' => 'LinkedIn',
                'youtube' => 'YouTube'
            ])
        ->setRequired(true)
    )
    ->addSubField(
        HyperFields::makeField('url', 'url', 'URL')
        ->setPlaceholder('https://...')
        ->setRequired(true)
    )
    ->addSubField(
        HyperFields::makeField('color', 'color', 'Brand Color')
    );

$advanced_section->addField($repeater_field);

// Example 4: Conditional Logic
$advanced_section->addField(
    HyperFields::makeField('radio', 'display_mode', 'Display Mode')
        ->setOptions([
            'simple' => 'Simple Display',
            'advanced' => 'Advanced Display'
        ])
        ->setDefault('simple')
);

$advanced_section->addField(
    HyperFields::makeField('number', 'items_per_page', 'Items Per Page')
        ->setDefault(10)
        // ->setMin(1)
        // ->setMax(100)
        ->setConditionalLogic([
            'relation' => 'AND',
            'conditions' => [[
                'field' => 'display_mode',
                'operator' => '=',
                'value' => 'advanced'
            ]]
        ])
);

// Register the options page
$plugin_options->register();

// Example 5: Theme Options Page
$theme_options = HyperFields::makeOptionPage('Theme Settings', 'theme-settings')
    ->setMenuTitle('Theme Options')
    ->setParentSlug('themes.php')
    ->setIconUrl('dashicons-admin-customizer');

// Header settings
$header_section = $theme_options->addSection('header', 'Header Configuration', 'Customize your theme header');
$header_section->addField(
    HyperFields::makeField('image', 'header_logo', 'Header Logo')
        ->setHelp('Recommended: transparent PNG, 200x60px')
);

$header_section->addField(
    HyperFields::makeField('radio_image', 'header_layout', 'Header Layout')
        ->setOptions([
            'default' => 'https://via.placeholder.com/150x60/007cba/ffffff?text=Default',
            'centered' => 'https://via.placeholder.com/150x60/28a745/ffffff?text=Centered',
            'minimal' => 'https://via.placeholder.com/150x60/dc3545/ffffff?text=Minimal'
        ])
        ->setDefault('default')
);

// Typography settings
$typography_section = $theme_options->addSection('typography', 'Typography', 'Font and text settings');
$typography_section->addField(
    HyperFields::makeField('select', 'primary_font', 'Primary Font')
        ->setOptions([
            'system' => 'System Fonts',
            'roboto' => 'Roboto',
            'opensans' => 'Open Sans',
            'lato' => 'Lato',
            'montserrat' => 'Montserrat'
        ])
        ->setDefault('system')
);

$typography_section->addField(
    HyperFields::makeField('number', 'base_font_size', 'Base Font Size (px)')
        ->setDefault(16)
    // ->setMin(12)
    // ->setMax(24)
);

// Footer settings
$footer_section = $theme_options->addSection('footer', 'Footer Configuration', 'Customize your theme footer');
$footer_section->addField(
    HyperFields::makeField('textarea', 'footer_text', 'Footer Text')
        ->setDefault('© ' . date('Y') . ' All rights reserved.')
        ->setHelp('You can use HTML tags here')
);

$footer_section->addField(
    HyperFields::makeField('footer_scripts', 'footer_scripts', 'Footer Scripts')
        ->setHelp('Add tracking codes or custom JavaScript here')
);

// Register theme options
$theme_options->register();

// Example 6: Custom Top-Level Menu
$custom_menu = HyperFields::makeOptionPage('Custom Plugin', 'custom-plugin')
    ->setMenuTitle('Custom Plugin')
    ->setIconUrl('dashicons-admin-generic')
    ->setPosition(30);

// Dashboard section
$dashboard_section = $custom_menu->addSection('dashboard', 'Dashboard', 'Welcome to your custom plugin dashboard');
$dashboard_section->addField(
    HyperFields::makeField('html', 'dashboard_welcome', 'Welcome Message')
        ->setHtmlContent('
        <div class="welcome-panel">
            <h2>Welcome to Custom Plugin!</h2>
            <p>Use the tabs below to configure your plugin settings.</p>
        </div>
        ')
);

// Settings sections
$settings_section = $custom_menu->addSection('settings', 'Plugin Settings', 'Configure your plugin behavior');
$settings_section->addField(
    HyperFields::makeField('map', 'business_location', 'Business Location')
        // ->set_map_options([
        // 'zoom' => 15,
        // 'type' => 'roadmap'
        // ])
);

$settings_section->addField(
    HyperFields::makeField('media_gallery', 'gallery_images', 'Gallery Images')
        // ->set_multiple(true)
);

// Register custom menu
$custom_menu->register();
